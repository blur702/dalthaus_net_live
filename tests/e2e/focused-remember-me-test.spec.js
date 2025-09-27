const { test, expect } = require('@playwright/test');

test.describe('Focused Remember Me Test', () => {
  test('test remember me functionality specifically', async ({ page, context }) => {
    console.log('Testing remember me functionality specifically...\n');

    // Clear cookies
    await context.clearCookies();

    // Step 1: Confirm basic login works (without remember me)
    console.log('Step 1: Testing basic login without remember me...');
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForSelector('input[name="username"]');

    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    // DON'T check remember me
    await page.click('button[type="submit"]');
    await page.waitForURL('https://dalthaus.net/admin/dashboard', { timeout: 10000 });

    console.log('✓ Basic login confirmed working');

    // Check cookies after basic login
    const basicCookies = await context.cookies();
    const sessionCookie = basicCookies.find(c => c.name === 'cms_session');
    const rememberCookie = basicCookies.find(c => c.name === 'remember_token');

    console.log('Session cookie after basic login:', !!sessionCookie);
    console.log('Remember token after basic login (should be none):', !!rememberCookie);

    // Step 2: Logout manually by going to logout URL
    console.log('\nStep 2: Logging out...');
    await page.goto('https://dalthaus.net/admin/logout');
    await page.waitForLoadState('networkidle');

    // Give it time to process logout
    await page.waitForTimeout(2000);

    // Navigate to login page to confirm logout
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForSelector('input[name="username"]');
    console.log('✓ Logout completed - back at login page');

    // Step 3: Now test login WITH remember me
    console.log('\nStep 3: Testing login WITH remember me...');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');

    // Check the remember me checkbox
    const rememberCheckbox = page.locator('input[name="remember_me"]');
    await rememberCheckbox.check();
    console.log('Remember me checkbox checked:', await rememberCheckbox.isChecked());

    // Submit the form
    await page.click('button[type="submit"]');

    // Wait for response but don't assume success
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    const currentUrl = page.url();
    console.log('Current URL after login with remember me:', currentUrl);

    if (currentUrl.includes('/admin/dashboard')) {
      console.log('✓ Login with remember me SUCCESSFUL!');

      // Check cookies
      const cookiesWithRemember = await context.cookies();
      const sessionAfterRemember = cookiesWithRemember.find(c => c.name === 'cms_session');
      const rememberTokenAfter = cookiesWithRemember.find(c => c.name === 'remember_token');

      console.log('\nCookie analysis:');
      console.log('Session cookie present:', !!sessionAfterRemember);
      console.log('Remember token cookie present:', !!rememberTokenAfter);

      if (rememberTokenAfter) {
        console.log('\nRemember token details:');
        console.log('  Name:', rememberTokenAfter.name);
        console.log('  Value length:', rememberTokenAfter.value.length);
        console.log('  Domain:', rememberTokenAfter.domain);
        console.log('  Path:', rememberTokenAfter.path);
        console.log('  HttpOnly:', rememberTokenAfter.httpOnly);
        console.log('  Secure:', rememberTokenAfter.secure);
        console.log('  SameSite:', rememberTokenAfter.sameSite);

        // Check expiration
        if (rememberTokenAfter.expires !== -1) {
          const expirationDate = new Date(rememberTokenAfter.expires * 1000);
          const now = new Date();
          const daysDiff = Math.round((expirationDate - now) / (1000 * 60 * 60 * 24));
          console.log('  Expires:', expirationDate.toISOString());
          console.log('  Days from now:', daysDiff);

          if (daysDiff >= 29 && daysDiff <= 31) {
            console.log('  ✓ Expiration is correct (~30 days)');
          } else {
            console.log('  ✗ Expiration is not 30 days');
          }
        } else {
          console.log('  ✗ Cookie is session-only (expires = -1)');
        }

        // Step 4: Test logout behavior with remember token
        console.log('\nStep 4: Testing logout with remember token...');
        await page.goto('https://dalthaus.net/admin/logout');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // Check cookies after logout
        const cookiesAfterLogout = await context.cookies();
        const rememberAfterLogout = cookiesAfterLogout.find(c => c.name === 'remember_token');
        console.log('Remember token persists after logout:', !!rememberAfterLogout);

        if (rememberAfterLogout) {
          // Step 5: Test auto-login
          console.log('\nStep 5: Testing auto-login functionality...');
          await page.goto('https://dalthaus.net/admin/dashboard');
          await page.waitForLoadState('networkidle');
          await page.waitForTimeout(3000);

          const autoLoginUrl = page.url();
          console.log('Auto-login attempt result URL:', autoLoginUrl);

          if (autoLoginUrl.includes('/admin/dashboard')) {
            console.log('✓ AUTO-LOGIN SUCCESSFUL!');
            console.log('✓ Remember me functionality is working correctly!');

            // Final verification - check if we're actually logged in
            const pageTitle = await page.title();
            console.log('Dashboard page title:', pageTitle);

            try {
              const dashboardContent = await page.locator('h1').first().textContent({ timeout: 5000 });
              console.log('Dashboard heading:', dashboardContent);
            } catch (e) {
              console.log('Could not get dashboard heading, but URL indicates success');
            }

          } else {
            console.log('✗ Auto-login failed - redirected to:', autoLoginUrl);
          }
        } else {
          console.log('✗ Remember token was removed on logout - cannot test auto-login');
        }

      } else {
        console.log('✗ Remember token cookie was not created');
      }

    } else if (currentUrl.includes('/admin/login')) {
      console.log('✗ Login with remember me FAILED - stayed on login page');

      // Check for error messages
      const errorMessages = await page.locator('.error, .alert, .text-red-500').allTextContents();
      console.log('Error messages:', errorMessages.length > 0 ? errorMessages : 'None visible');

      // Check form state
      const usernameValue = await page.inputValue('input[name="username"]');
      console.log('Username field after failed attempt:', usernameValue || '(empty)');

    } else {
      console.log('? Unexpected URL after login with remember me:', currentUrl);
    }

    console.log('\n' + '='.repeat(60));
    console.log('REMEMBER ME TEST COMPLETE');
    console.log('='.repeat(60));
  });
});