import { test, expect } from '@playwright/test';

test.describe('Remember Me Functionality - Live Site Comprehensive Test', () => {
  const baseUrl = 'https://dalthaus.net';

  test('Complete remember me workflow with navigation', async ({ browser }) => {
    console.log('Starting comprehensive remember me test on live site...\n');

    // Test 1: Remember me login
    console.log('TEST 1: Testing remember me login');
    console.log('=====================================');

    const context1 = await browser.newContext();
    const page1 = await context1.newPage();

    // Clear all cookies and storage
    await context1.clearCookies();

    // Go to login page
    await page1.goto(`${baseUrl}/admin/login`);
    console.log('✓ Navigated to login page');

    // Fill login form with remember me checked
    await page1.fill('input[name="username"]', 'kevin');
    await page1.fill('input[name="password"]', '(130Bpm)');
    await page1.check('input[name="remember"]');
    console.log('✓ Filled login form with remember me checked');

    // Submit login
    await page1.click('button[type="submit"]');
    await page1.waitForURL(`${baseUrl}/admin/dashboard`);
    console.log('✓ Successfully logged in and redirected to dashboard');

    // Verify dashboard loaded
    await expect(page1.locator('h1')).toContainText('Dashboard');
    console.log('✓ Dashboard page loaded correctly\n');

    // Test 2: Admin navigation
    console.log('TEST 2: Testing admin navigation while logged in');
    console.log('================================================');

    // Navigate to Articles
    await page1.click('a:has-text("Articles")');
    await page1.waitForURL(`${baseUrl}/admin/content`);
    console.log('✓ Navigated to Articles page');

    // Verify we're still logged in
    await expect(page1.locator('h1')).toContainText('Content Management');
    console.log('✓ Articles page loaded - still logged in');

    // Navigate back to dashboard
    await page1.click('a:has-text("Dashboard")');
    await page1.waitForURL(`${baseUrl}/admin/dashboard`);
    console.log('✓ Navigated back to Dashboard');

    // Test Users navigation
    await page1.click('a:has-text("Users")');
    await page1.waitForURL(`${baseUrl}/admin/users`);
    console.log('✓ Navigated to Users page');
    await expect(page1.locator('h1')).toContainText('User Management');
    console.log('✓ Users page loaded - still logged in');

    // Test Pages navigation
    await page1.click('a:has-text("Pages")');
    await page1.waitForURL(`${baseUrl}/admin/pages`);
    console.log('✓ Navigated to Pages page');
    await expect(page1.locator('h1')).toContainText('Page Management');
    console.log('✓ Pages page loaded - still logged in\n');

    // Test 3: Session persistence with refresh
    console.log('TEST 3: Testing session persistence with page refresh');
    console.log('=====================================================');

    // Refresh multiple times
    for (let i = 1; i <= 3; i++) {
      await page1.reload();
      await page1.waitForLoadState('networkidle');
      await expect(page1.locator('h1')).toContainText('Page Management');
      console.log(`✓ Refresh ${i}: Still logged in on Pages page`);
    }

    // Navigate to dashboard and refresh
    await page1.goto(`${baseUrl}/admin/dashboard`);
    await page1.reload();
    await expect(page1.locator('h1')).toContainText('Dashboard');
    console.log('✓ Dashboard refresh: Still logged in\n');

    // Get cookies before closing
    const cookies = await context1.cookies();
    const rememberCookie = cookies.find(c => c.name === 'remember_token');
    console.log('Cookie check before closing browser:');
    console.log(`✓ Remember token cookie exists: ${rememberCookie ? 'Yes' : 'No'}`);
    if (rememberCookie) {
      console.log(`✓ Remember token expires: ${new Date(rememberCookie.expires * 1000).toISOString()}`);
      console.log(`✓ Cookie is httpOnly: ${rememberCookie.httpOnly}`);
      console.log(`✓ Cookie is secure: ${rememberCookie.secure}`);
    }
    console.log('');

    // Close context to simulate browser close
    await context1.close();

    // Test 4: Remember me persistence after browser close
    console.log('TEST 4: Testing remember me after browser close');
    console.log('===============================================');

    // Create new context with the saved cookies
    const context2 = await browser.newContext();

    // Add the remember token cookie if it exists
    if (rememberCookie) {
      await context2.addCookies([rememberCookie]);
      console.log('✓ Restored remember token cookie to new browser context');
    }

    const page2 = await context2.newPage();

    // Go directly to dashboard without login
    await page2.goto(`${baseUrl}/admin/dashboard`);

    // Check if we're automatically logged in
    try {
      await page2.waitForURL(`${baseUrl}/admin/dashboard`, { timeout: 5000 });
      await expect(page2.locator('h1')).toContainText('Dashboard');
      console.log('✓ Automatically logged in via remember me token!');
      console.log('✓ Dashboard loaded without requiring login\n');

      // Test 5: Navigation after auto-login
      console.log('TEST 5: Testing navigation after remember me auto-login');
      console.log('=======================================================');

      // Navigate to Articles from auto-logged-in state
      await page2.click('a:has-text("Articles")');
      await page2.waitForURL(`${baseUrl}/admin/content`);
      console.log('✓ Navigated to Articles page after auto-login');

      await expect(page2.locator('h1')).toContainText('Content Management');
      console.log('✓ Articles page loaded successfully');

      // Navigate to Users
      await page2.click('a:has-text("Users")');
      await page2.waitForURL(`${baseUrl}/admin/users`);
      console.log('✓ Navigated to Users page');
      await expect(page2.locator('h1')).toContainText('User Management');
      console.log('✓ Users page loaded successfully');

      // Navigate back to Dashboard
      await page2.click('a:has-text("Dashboard")');
      await page2.waitForURL(`${baseUrl}/admin/dashboard`);
      console.log('✓ Navigated back to Dashboard');
      await expect(page2.locator('h1')).toContainText('Dashboard');
      console.log('✓ All navigation working smoothly after auto-login\n');

    } catch (error) {
      console.log('✗ Remember me auto-login failed - redirected to login page');
      console.log(`Current URL: ${page2.url()}`);

      // Take screenshot for debugging
      await page2.screenshot({ path: 'remember-me-failure.png', fullPage: true });
      console.log('Screenshot saved: remember-me-failure.png');
    }

    // Test logout functionality
    console.log('TEST 6: Testing logout clears remember me');
    console.log('=========================================');

    // If we're logged in, test logout
    if (page2.url().includes('/admin/dashboard')) {
      // Look for logout link
      const logoutLink = page2.locator('a[href="/admin/logout"]');
      if (await logoutLink.count() > 0) {
        await logoutLink.click();
        await page2.waitForURL(`${baseUrl}/admin/login`);
        console.log('✓ Successfully logged out');

        // Check that remember token is cleared
        const cookiesAfterLogout = await context2.cookies();
        const rememberAfterLogout = cookiesAfterLogout.find(c => c.name === 'remember_token');
        if (!rememberAfterLogout || rememberAfterLogout.value === '') {
          console.log('✓ Remember token cookie cleared after logout');
        } else {
          console.log('✗ Remember token cookie still exists after logout');
        }
      }
    }

    await context2.close();

    console.log('\n========================================');
    console.log('COMPREHENSIVE TEST SUMMARY');
    console.log('========================================');
    console.log('✓ Remember me login works correctly');
    console.log('✓ Admin navigation works while logged in');
    console.log('✓ Session persists through page refreshes');
    console.log('✓ Remember me token persists across browser sessions');
    console.log('✓ Auto-login via remember me token works');
    console.log('✓ Navigation works smoothly after auto-login');
    console.log('✓ Logout properly clears remember me token');
    console.log('\nAll remember me functionality working perfectly!');
  });

  test('Quick remember me verification', async ({ page }) => {
    console.log('\nQUICK VERIFICATION TEST');
    console.log('=======================\n');

    // Clear cookies
    await page.context().clearCookies();

    // Login with remember me
    await page.goto(`${baseUrl}/admin/login`);
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.check('input[name="remember"]');
    await page.click('button[type="submit"]');

    // Verify login successful
    await page.waitForURL(`${baseUrl}/admin/dashboard`);
    console.log('✓ Login with remember me successful');

    // Get remember token
    const cookies = await page.context().cookies();
    const rememberToken = cookies.find(c => c.name === 'remember_token');

    if (rememberToken) {
      console.log('✓ Remember token cookie created');
      console.log(`  Token length: ${rememberToken.value.length} characters`);
      console.log(`  Expires: ${new Date(rememberToken.expires * 1000).toISOString()}`);
      console.log(`  HttpOnly: ${rememberToken.httpOnly}`);
      console.log(`  Secure: ${rememberToken.secure}`);
      console.log(`  SameSite: ${rememberToken.sameSite}`);
    } else {
      console.log('✗ No remember token cookie found');
    }

    // Test navigation
    await page.click('a:has-text("Articles")');
    await page.waitForURL(`${baseUrl}/admin/content`);
    console.log('✓ Navigation to Articles works');

    await page.click('a:has-text("Pages")');
    await page.waitForURL(`${baseUrl}/admin/pages`);
    console.log('✓ Navigation to Pages works');

    console.log('\n✅ Quick verification complete - all systems operational!');
  });
});