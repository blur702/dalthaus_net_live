import { test, expect } from '@playwright/test';

// Production site credentials
const PROD_URL = 'https://dalthaus.net';
const USERNAME = 'kevin';
const PASSWORD = '(130Bpm)';

test.describe('Production Remember Me Functionality - Complete Test Suite', () => {

  test('Complete remember me workflow with navigation', async ({ browser }) => {
    console.log('=== STARTING COMPLETE REMEMBER ME TEST ON PRODUCTION ===\n');

    // Create a new browser context with clean state
    const context = await browser.newContext({
      // Start with completely clean state
      storageState: undefined,
      // Accept all cookies including third-party
      acceptDownloads: true,
      ignoreHTTPSErrors: false
    });

    const page = await context.newPage();

    // Enable console logging
    page.on('console', msg => {
      if (msg.type() === 'error') {
        console.log('Browser console error:', msg.text());
      }
    });

    // Step 1: Clear all browser data (starting with new context ensures this)
    console.log('Step 1: Starting with clean browser state (no cookies, cache, or storage)');
    const initialCookies = await context.cookies();
    console.log(`Initial cookies count: ${initialCookies.length}`);
    expect(initialCookies.length).toBe(0);

    // Step 2: Login with remember me checkbox
    console.log('\nStep 2: Logging in with remember me checkbox...');
    await page.goto(`${PROD_URL}/admin/login`);
    await expect(page).toHaveURL(`${PROD_URL}/admin/login`);

    // Fill in credentials
    await page.fill('input[name="username"]', USERNAME);
    await page.fill('input[name="password"]', PASSWORD);

    // CHECK the remember me checkbox
    const rememberCheckbox = page.locator('input[name="remember_me"]');
    await expect(rememberCheckbox).toBeVisible();
    await rememberCheckbox.check();
    await expect(rememberCheckbox).toBeChecked();
    console.log('✓ Remember me checkbox checked');

    // Submit login form
    await page.click('button[type="submit"]');

    // Wait for navigation to dashboard
    await page.waitForURL(`${PROD_URL}/admin/dashboard`, { timeout: 10000 });
    console.log('✓ Successfully logged in and redirected to dashboard');

    // Verify we're on the dashboard
    await expect(page.locator('h1')).toContainText('Dashboard');

    // Step 3: Check remember token cookie
    console.log('\nStep 3: Checking remember_token cookie...');
    const cookiesAfterLogin = await context.cookies();
    console.log(`Total cookies after login: ${cookiesAfterLogin.length}`);

    // Find and analyze remember_token cookie
    const rememberCookie = cookiesAfterLogin.find(c => c.name === 'remember_token');

    if (rememberCookie) {
      console.log('✓ remember_token cookie found!');
      console.log('Cookie details:');
      console.log(`  - Name: ${rememberCookie.name}`);
      console.log(`  - Value: ${rememberCookie.value.substring(0, 20)}...`);
      console.log(`  - Domain: ${rememberCookie.domain}`);
      console.log(`  - Path: ${rememberCookie.path}`);
      console.log(`  - HttpOnly: ${rememberCookie.httpOnly}`);
      console.log(`  - Secure: ${rememberCookie.secure}`);
      console.log(`  - SameSite: ${rememberCookie.sameSite}`);

      // Check expiration (should be ~30 days from now)
      const now = Date.now() / 1000;
      const expiresIn = rememberCookie.expires - now;
      const daysUntilExpiry = expiresIn / (60 * 60 * 24);
      console.log(`  - Expires in: ${daysUntilExpiry.toFixed(1)} days`);

      // Verify cookie properties
      expect(rememberCookie.httpOnly).toBe(true);
      expect(rememberCookie.secure).toBe(true);
      expect(rememberCookie.sameSite).toBe('Lax');
      expect(daysUntilExpiry).toBeGreaterThan(29);
      expect(daysUntilExpiry).toBeLessThan(31);
      console.log('✓ Cookie has correct security settings and expiration');
    } else {
      console.log('✗ remember_token cookie NOT found!');
      console.log('Available cookies:', cookiesAfterLogin.map(c => c.name).join(', '));
      throw new Error('remember_token cookie was not created');
    }

    // Also check session cookie
    const sessionCookie = cookiesAfterLogin.find(c => c.name === 'cms_session');
    if (sessionCookie) {
      console.log('\nSession cookie details:');
      console.log(`  - Name: ${sessionCookie.name}`);
      console.log(`  - HttpOnly: ${sessionCookie.httpOnly}`);
      console.log(`  - Secure: ${sessionCookie.secure}`);
      console.log(`  - SameSite: ${sessionCookie.sameSite}`);
    }

    // Step 4: Test admin navigation
    console.log('\nStep 4: Testing admin navigation...');

    // Click Articles link
    console.log('Navigating to Articles...');
    await page.click('a:has-text("Articles")');
    await page.waitForURL(`${PROD_URL}/admin/content?type=article`, { timeout: 10000 });
    console.log('✓ Successfully navigated to Articles page');
    await expect(page.locator('h1')).toContainText('Articles');

    // Navigate back to dashboard
    console.log('Navigating back to Dashboard...');
    await page.click('a:has-text("Dashboard")');
    await page.waitForURL(`${PROD_URL}/admin/dashboard`, { timeout: 10000 });
    console.log('✓ Successfully navigated back to Dashboard');

    // Test another navigation link
    console.log('Navigating to Pages...');
    await page.click('a:has-text("Pages")');
    await page.waitForURL(`${PROD_URL}/admin/pages`, { timeout: 10000 });
    console.log('✓ Successfully navigated to Pages');
    await expect(page.locator('h1')).toContainText('Pages');

    console.log('✓ All navigation working correctly while logged in');

    // Step 5: Test remember me persistence (simulate browser restart)
    console.log('\nStep 5: Testing remember me persistence after browser restart...');

    // Save the cookies
    const savedCookies = await context.cookies();

    // Close the context (simulating browser close)
    await context.close();
    console.log('Browser context closed');

    // Create a new context with the saved cookies (simulating browser restart)
    const newContext = await browser.newContext();
    await newContext.addCookies(savedCookies);
    const newPage = await newContext.newPage();
    console.log('New browser context created with saved cookies');

    // Navigate directly to admin dashboard
    console.log('Navigating directly to admin dashboard...');
    await newPage.goto(`${PROD_URL}/admin/dashboard`);

    // Check if we're automatically logged in
    const currentUrl = newPage.url();
    if (currentUrl.includes('/admin/dashboard')) {
      console.log('✓ Automatically logged in! No redirect to login page');
      await expect(newPage.locator('h1')).toContainText('Dashboard');
      console.log('✓ Dashboard loaded successfully');
    } else {
      console.log(`✗ Redirected to: ${currentUrl}`);
      throw new Error('Remember me auto-login failed - redirected to login');
    }

    // Step 6: Test navigation after auto-login
    console.log('\nStep 6: Testing navigation after auto-login...');

    // Click Articles from the auto-logged-in state
    console.log('Navigating to Articles from auto-logged-in state...');
    await newPage.click('a:has-text("Articles")');
    await newPage.waitForURL(`${PROD_URL}/admin/content?type=article`, { timeout: 10000 });
    console.log('✓ Successfully navigated to Articles without login redirect');
    await expect(newPage.locator('h1')).toContainText('Articles');

    // Test one more navigation
    console.log('Navigating to Users...');
    await newPage.click('a:has-text("Users")');
    await newPage.waitForURL(`${PROD_URL}/admin/users`, { timeout: 10000 });
    console.log('✓ Successfully navigated to Users without login redirect');

    console.log('\n=== ALL TESTS PASSED SUCCESSFULLY ===');
    console.log('Summary:');
    console.log('✓ Login with remember me creates remember_token cookie');
    console.log('✓ Cookie has correct security settings (HttpOnly, Secure, SameSite=Lax)');
    console.log('✓ Cookie has correct expiration (30 days)');
    console.log('✓ Admin navigation works seamlessly after login');
    console.log('✓ Auto-login works after browser restart');
    console.log('✓ Navigation works correctly after auto-login');
    console.log('✓ No unwanted redirects to login page');

    // Clean up
    await newContext.close();
  });

  test('Verify logout clears remember token', async ({ page }) => {
    console.log('\n=== TESTING LOGOUT CLEARS REMEMBER TOKEN ===\n');

    // Login with remember me
    await page.goto(`${PROD_URL}/admin/login`);
    await page.fill('input[name="username"]', USERNAME);
    await page.fill('input[name="password"]', PASSWORD);
    await page.check('input[name="remember_me"]');
    await page.click('button[type="submit"]');
    await page.waitForURL(`${PROD_URL}/admin/dashboard`);

    // Verify remember token exists
    let cookies = await page.context().cookies();
    let rememberCookie = cookies.find(c => c.name === 'remember_token');
    expect(rememberCookie).toBeTruthy();
    console.log('✓ remember_token cookie created after login');

    // Logout
    await page.click('a:has-text("Logout")');
    await page.waitForURL(`${PROD_URL}/admin/login`);
    console.log('✓ Successfully logged out');

    // Check that remember token is cleared
    cookies = await page.context().cookies();
    rememberCookie = cookies.find(c => c.name === 'remember_token');

    if (!rememberCookie || rememberCookie.value === '') {
      console.log('✓ remember_token cookie properly cleared after logout');
    } else {
      console.log('✗ remember_token cookie still exists after logout!');
      throw new Error('Remember token not properly cleared on logout');
    }
  });

  test('Verify login without remember me does not create token', async ({ page }) => {
    console.log('\n=== TESTING LOGIN WITHOUT REMEMBER ME ===\n');

    // Login WITHOUT remember me
    await page.goto(`${PROD_URL}/admin/login`);
    await page.fill('input[name="username"]', USERNAME);
    await page.fill('input[name="password"]', PASSWORD);

    // Ensure checkbox is NOT checked
    const rememberCheckbox = page.locator('input[name="remember_me"]');
    await rememberCheckbox.uncheck();
    await expect(rememberCheckbox).not.toBeChecked();
    console.log('✓ Remember me checkbox is unchecked');

    await page.click('button[type="submit"]');
    await page.waitForURL(`${PROD_URL}/admin/dashboard`);
    console.log('✓ Successfully logged in');

    // Check that NO remember token is created
    const cookies = await page.context().cookies();
    const rememberCookie = cookies.find(c => c.name === 'remember_token');

    if (!rememberCookie) {
      console.log('✓ No remember_token cookie created (as expected)');
    } else {
      console.log('✗ remember_token cookie created even without checkbox!');
      throw new Error('Remember token should not be created without checkbox');
    }

    // Verify session cookie still exists
    const sessionCookie = cookies.find(c => c.name === 'cms_session');
    expect(sessionCookie).toBeTruthy();
    console.log('✓ Session cookie exists for normal login');
  });
});