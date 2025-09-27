import { test, expect } from '@playwright/test';

// Local development server
const LOCAL_URL = 'http://localhost:8000';
const USERNAME = 'kevin';
const PASSWORD = '(130Bpm)';

test.describe('Local Remember Me Functionality Test', () => {

  test('Complete remember me workflow on local server', async ({ browser }) => {
    console.log('=== TESTING REMEMBER ME ON LOCAL SERVER ===\n');

    // Create a new browser context with clean state
    const context = await browser.newContext({
      storageState: undefined,
    });

    const page = await context.newPage();

    // Enable console logging
    page.on('console', msg => {
      if (msg.type() === 'error') {
        console.log('Browser console error:', msg.text());
      }
    });

    // Step 1: Clear all browser data (starting with new context ensures this)
    console.log('Step 1: Starting with clean browser state');
    const initialCookies = await context.cookies();
    console.log(`Initial cookies count: ${initialCookies.length}`);

    // Step 2: Login with remember me checkbox
    console.log('\nStep 2: Logging in with remember me checkbox...');
    await page.goto(`${LOCAL_URL}/admin/login`);

    // Check if we can reach the login page
    const pageTitle = await page.title().catch(() => 'Page not accessible');
    console.log('Page title:', pageTitle);

    if (!pageTitle.includes('Login')) {
      console.log('❌ Cannot access local login page - server might not be running');
      throw new Error('Local server not accessible');
    }

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
    try {
      await page.waitForURL(`${LOCAL_URL}/admin/dashboard`, { timeout: 10000 });
      console.log('✓ Successfully logged in and redirected to dashboard');
    } catch (error) {
      console.log('❌ Login failed or no redirect to dashboard');
      console.log('Current URL:', page.url());

      // Check for error messages
      const pageContent = await page.textContent('body');
      if (pageContent.includes('Invalid') || pageContent.includes('incorrect')) {
        console.log('❌ Invalid credentials detected');
      }
      throw error;
    }

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

      // Verify cookie properties for local dev
      expect(rememberCookie.httpOnly).toBe(true);
      // Note: Secure flag may be false for localhost
      expect(rememberCookie.sameSite).toBe('Lax');
      expect(daysUntilExpiry).toBeGreaterThan(29);
      expect(daysUntilExpiry).toBeLessThan(31);
      console.log('✓ Cookie has correct settings and expiration');
    } else {
      console.log('❌ remember_token cookie NOT found!');
      console.log('Available cookies:', cookiesAfterLogin.map(c => c.name).join(', '));
      throw new Error('remember_token cookie was not created');
    }

    // Step 4: Test admin navigation
    console.log('\nStep 4: Testing admin navigation...');

    // Click Articles link
    console.log('Navigating to Articles...');
    await page.click('a:has-text("Articles")');
    await page.waitForURL(`${LOCAL_URL}/admin/content?type=article`, { timeout: 10000 });
    console.log('✓ Successfully navigated to Articles page');

    // Navigate back to dashboard
    console.log('Navigating back to Dashboard...');
    await page.click('a:has-text("Dashboard")');
    await page.waitForURL(`${LOCAL_URL}/admin/dashboard`, { timeout: 10000 });
    console.log('✓ Successfully navigated back to Dashboard');

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
    await newPage.goto(`${LOCAL_URL}/admin/dashboard`);

    // Check if we're automatically logged in
    const currentUrl = newPage.url();
    if (currentUrl.includes('/admin/dashboard')) {
      console.log('✓ Automatically logged in! No redirect to login page');
      await expect(newPage.locator('h1')).toContainText('Dashboard');
      console.log('✓ Dashboard loaded successfully');
    } else {
      console.log(`❌ Redirected to: ${currentUrl}`);
      throw new Error('Remember me auto-login failed - redirected to login');
    }

    // Step 6: Test navigation after auto-login
    console.log('\nStep 6: Testing navigation after auto-login...');

    // Click Articles from the auto-logged-in state
    console.log('Navigating to Articles from auto-logged-in state...');
    await newPage.click('a:has-text("Articles")');
    await newPage.waitForURL(`${LOCAL_URL}/admin/content?type=article`, { timeout: 10000 });
    console.log('✓ Successfully navigated to Articles without login redirect');

    console.log('\n=== LOCAL REMEMBER ME TEST PASSED ===');
    console.log('The remember me functionality is working correctly on local server!');

    // Clean up
    await newContext.close();
  });
});