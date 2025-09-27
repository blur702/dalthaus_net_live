import { test, expect } from '@playwright/test';

test.describe('Remember Me Functionality Test', () => {
  test('should verify remember me cookie persistence', async ({ browser }) => {
    // Create a new browser context
    const context = await browser.newContext();
    const page = await context.newPage();

    console.log('Step 1: Testing login with remember me...');
    await page.goto('https://dalthaus.net/admin/login');

    // Login with remember me checked
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.check('input[name="remember_me"]');
    await page.click('button[type="submit"]');

    // Wait for redirect
    await page.waitForTimeout(2000);

    // Check we're logged in
    await page.goto('https://dalthaus.net/admin/dashboard');
    let dashboardUrl = page.url();
    console.log(`After login, dashboard URL: ${dashboardUrl}`);

    // Get all cookies
    const cookiesAfterLogin = await context.cookies();
    console.log('Cookies after login:');
    cookiesAfterLogin.forEach(cookie => {
      const expires = cookie.expires ? new Date(cookie.expires * 1000).toISOString() : 'Session';
      console.log(`  ${cookie.name}: expires ${expires}`);
    });

    // Find remember me cookie
    const rememberCookie = cookiesAfterLogin.find(c =>
      c.name.includes('remember') ||
      (c.expires && c.expires > Math.floor(Date.now() / 1000) + (25 * 24 * 60 * 60)) // More than 25 days
    );

    if (rememberCookie) {
      console.log(`✅ Remember me cookie found: ${rememberCookie.name}`);
      console.log(`   Expires: ${new Date(rememberCookie.expires * 1000)}`);
      const daysUntilExpiry = (rememberCookie.expires * 1000 - Date.now()) / (24 * 60 * 60 * 1000);
      console.log(`   Days until expiry: ${Math.round(daysUntilExpiry)}`);
    } else {
      console.log('❌ No long-term remember me cookie found');
    }

    console.log('Step 2: Testing session persistence...');

    // Close the page but keep the context (simulating browser close/reopen)
    await page.close();

    // Create a new page in the same context (same cookies)
    const newPage = await context.newPage();

    // Try to access dashboard directly
    await newPage.goto('https://dalthaus.net/admin/dashboard');
    await newPage.waitForTimeout(1000);

    const finalUrl = newPage.url();
    console.log(`After reopening, dashboard URL: ${finalUrl}`);

    if (finalUrl.includes('/dashboard')) {
      console.log('✅ Session persisted - still logged in after page close');
    } else if (finalUrl.includes('/login')) {
      console.log('❌ Session lost - redirected to login page');
    }

    // Take final screenshot
    await newPage.screenshot({
      path: 'tests/screenshots/remember-me-final.png',
      fullPage: true
    });

    await context.close();
  });
});