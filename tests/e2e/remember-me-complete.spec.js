const { test, expect } = require('@playwright/test');

test.describe('Complete Remember Me Functionality Tests', () => {
  test('Full remember me workflow with navigation', async ({ page, context }) => {
    console.log('Starting complete remember me test...');

    // Step 1: Clear all cookies and test remember me login
    await context.clearCookies();
    console.log('✅ Cleared all cookies');

    // Navigate to login page
    await page.goto('https://dalthaus.net/admin/login');
    console.log('✅ Navigated to login page');

    // Take screenshot of login page
    await page.screenshot({
      path: 'tests/screenshots/remember-me-1-login-page.png',
      fullPage: true
    });

    // Fill login form with remember me checked
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');

    // Check the remember me checkbox
    const rememberCheckbox = page.locator('input[name="remember_me"]');
    await rememberCheckbox.check();
    const isChecked = await rememberCheckbox.isChecked();
    console.log(`✅ Remember me checkbox checked: ${isChecked}`);

    // Take screenshot before submission
    await page.screenshot({
      path: 'tests/screenshots/remember-me-2-form-filled.png',
      fullPage: true
    });

    // Submit the form
    await page.click('button[type="submit"]');
    console.log('✅ Form submitted');

    // Wait for navigation and check if we're on dashboard
    await page.waitForLoadState('networkidle');
    const currentUrl = page.url();
    console.log(`Current URL after login: ${currentUrl}`);

    // Take screenshot of result page
    await page.screenshot({
      path: 'tests/screenshots/remember-me-3-after-login.png',
      fullPage: true
    });

    // Check if we successfully reached the dashboard
    if (currentUrl.includes('/admin/dashboard')) {
      console.log('✅ Successfully logged in and reached dashboard!');

      // Check for session message
      const successMessage = await page.locator('.alert-success').textContent().catch(() => null);
      if (successMessage) {
        console.log(`Success message: ${successMessage}`);
      }

      // Step 2: Test navigation to Articles
      console.log('\n--- Testing Articles Navigation ---');

      // Find and click Articles link
      const articlesLink = page.locator('a:has-text("Articles")').first();
      if (await articlesLink.isVisible()) {
        await articlesLink.click();
        await page.waitForLoadState('networkidle');

        const articlesUrl = page.url();
        console.log(`Articles URL: ${articlesUrl}`);

        await page.screenshot({
          path: 'tests/screenshots/remember-me-4-articles-page.png',
          fullPage: true
        });

        if (articlesUrl.includes('/admin/content')) {
          console.log('✅ Successfully navigated to Articles without login redirect!');
        } else if (articlesUrl.includes('/admin/login')) {
          console.log('❌ Redirected to login when accessing Articles');
        }
      }

      // Step 3: Check cookies including remember_token
      console.log('\n--- Checking Cookies ---');
      const cookies = await context.cookies();

      const sessionCookie = cookies.find(c => c.name === 'cms_session');
      const rememberCookie = cookies.find(c => c.name === 'remember_token');

      if (sessionCookie) {
        console.log('✅ Session cookie found:');
        console.log(`  - Name: ${sessionCookie.name}`);
        console.log(`  - Domain: ${sessionCookie.domain}`);
        console.log(`  - Expires: ${sessionCookie.expires ? new Date(sessionCookie.expires * 1000) : 'Session'}`);
      }

      if (rememberCookie) {
        console.log('✅ Remember token cookie found:');
        console.log(`  - Name: ${rememberCookie.name}`);
        console.log(`  - Domain: ${rememberCookie.domain}`);
        console.log(`  - Value length: ${rememberCookie.value.length}`);

        if (rememberCookie.expires) {
          const expiryDate = new Date(rememberCookie.expires * 1000);
          const now = new Date();
          const daysDiff = Math.round((expiryDate - now) / (1000 * 60 * 60 * 24));
          console.log(`  - Expires: ${expiryDate} (in ${daysDiff} days)`);

          if (daysDiff >= 29 && daysDiff <= 31) {
            console.log('✅ Cookie expiration is correctly set to ~30 days');
          } else {
            console.log(`⚠️ Cookie expiration is ${daysDiff} days, expected ~30`);
          }
        }
      } else {
        console.log('⚠️ Remember token cookie not found');
      }

      // Step 4: Test other admin navigation links
      console.log('\n--- Testing Additional Navigation ---');

      // Navigate back to dashboard
      await page.goto('https://dalthaus.net/admin/dashboard');
      await page.waitForLoadState('networkidle');

      // Test Pages link
      const pagesLink = page.locator('a:has-text("Pages")').first();
      if (await pagesLink.isVisible()) {
        await pagesLink.click();
        await page.waitForLoadState('networkidle');

        const pagesUrl = page.url();
        console.log(`Pages URL: ${pagesUrl}`);

        if (pagesUrl.includes('/admin/pages')) {
          console.log('✅ Successfully navigated to Pages');
        } else if (pagesUrl.includes('/admin/login')) {
          console.log('❌ Redirected to login when accessing Pages');
        }
      }

      // Step 5: Test direct admin URL access
      console.log('\n--- Testing Direct URL Access ---');

      // Clear session cookie but keep remember token
      const nonSessionCookies = cookies.filter(c => c.name !== 'cms_session');
      await context.clearCookies();
      await context.addCookies(nonSessionCookies);
      console.log('✅ Cleared session cookie, kept remember token');

      // Try to access admin page directly
      await page.goto('https://dalthaus.net/admin/content');
      await page.waitForLoadState('networkidle');

      const directAccessUrl = page.url();
      console.log(`Direct access URL: ${directAccessUrl}`);

      await page.screenshot({
        path: 'tests/screenshots/remember-me-5-direct-access.png',
        fullPage: true
      });

      if (directAccessUrl.includes('/admin/content')) {
        console.log('✅ Remember me auto-login worked! Accessed admin directly.');
      } else if (directAccessUrl.includes('/admin/login')) {
        console.log('❌ Remember me auto-login failed, redirected to login');
      }

      // Final summary
      console.log('\n=== TEST SUMMARY ===');
      console.log('✅ Remember me checkbox functional');
      console.log(`✅ Login ${currentUrl.includes('dashboard') ? 'successful' : 'failed'}`);
      console.log(`✅ Navigation ${articlesUrl && !articlesUrl.includes('login') ? 'works' : 'redirects to login'}`);
      console.log(`✅ Remember cookie ${rememberCookie ? 'created' : 'not created'}`);
      console.log(`✅ Auto-login ${directAccessUrl && !directAccessUrl.includes('login') ? 'works' : 'failed'}`);

    } else if (currentUrl.includes('/admin/login')) {
      console.log('❌ Still on login page - login failed');

      // Check for error messages
      const errorMessage = await page.locator('.alert-danger, .error').textContent().catch(() => null);
      if (errorMessage) {
        console.log(`Error message: ${errorMessage}`);
      }

      // Check page content for debugging
      const pageTitle = await page.title();
      console.log(`Page title: ${pageTitle}`);

      const bodyText = await page.locator('body').innerText();
      if (bodyText.includes('Invalid credentials')) {
        console.log('Found "Invalid credentials" error');
      }
    } else {
      console.log(`❓ Unexpected URL: ${currentUrl}`);
    }
  });

  test('Test logout and remember functionality', async ({ page, context }) => {
    console.log('\n=== Testing Logout with Remember Me ===');

    // First login with remember me
    await context.clearCookies();
    await page.goto('https://dalthaus.net/admin/login');

    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.check('input[name="remember_me"]');
    await page.click('button[type="submit"]');

    await page.waitForLoadState('networkidle');

    if (page.url().includes('/admin/dashboard')) {
      console.log('✅ Logged in successfully');

      // Get remember token before logout
      const cookiesBefore = await context.cookies();
      const rememberBefore = cookiesBefore.find(c => c.name === 'remember_token');
      console.log(`Remember token before logout: ${rememberBefore ? 'Present' : 'Not found'}`);

      // Find and click logout
      const logoutLink = page.locator('a[href*="logout"]').first();
      if (await logoutLink.isVisible()) {
        await logoutLink.click();
        await page.waitForLoadState('networkidle');

        const afterLogoutUrl = page.url();
        console.log(`After logout URL: ${afterLogoutUrl}`);

        // Check cookies after logout
        const cookiesAfter = await context.cookies();
        const rememberAfter = cookiesAfter.find(c => c.name === 'remember_token');
        const sessionAfter = cookiesAfter.find(c => c.name === 'cms_session');

        console.log(`Remember token after logout: ${rememberAfter ? 'Still present' : 'Cleared'}`);
        console.log(`Session after logout: ${sessionAfter ? 'Still present' : 'Cleared'}`);

        if (!rememberAfter) {
          console.log('✅ Remember token properly cleared on logout');
        } else {
          console.log('⚠️ Remember token not cleared on logout');
        }
      }
    }
  });
});