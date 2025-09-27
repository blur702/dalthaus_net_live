const { test, expect } = require('@playwright/test');

test.describe('Diagnose Live Login Issue', () => {
  test('diagnose why live login is failing', async ({ page, context }) => {
    console.log('Diagnosing live login issue...\n');

    // Clear cookies
    await context.clearCookies();

    // Test 1: Check if the login page loads correctly
    console.log('Test 1: Checking login page accessibility...');
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForSelector('input[name="username"]');
    console.log('✓ Login page loads and form is present\n');

    // Test 2: Check if admin routes are accessible without auth
    console.log('Test 2: Testing admin route accessibility...');
    await page.goto('https://dalthaus.net/admin/dashboard');
    await page.waitForLoadState('networkidle');
    const dashboardUrl = page.url();
    console.log('Dashboard access without auth redirects to:', dashboardUrl);
    console.log('Auth protection working:', dashboardUrl.includes('/admin/login') ? 'Yes' : 'No');
    console.log('');

    // Test 3: Try login with wrong credentials first
    console.log('Test 3: Testing with wrong credentials...');
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForSelector('input[name="username"]');

    await page.fill('input[name="username"]', 'wronguser');
    await page.fill('input[name="password"]', 'wrongpass');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    const wrongCredsUrl = page.url();
    console.log('Wrong credentials result URL:', wrongCredsUrl);

    // Check for error messages with wrong creds
    const errorMessages = await page.locator('.error, .alert, .text-red-500, .text-red-600, .text-danger').allTextContents();
    console.log('Error messages with wrong creds:', errorMessages.length > 0 ? errorMessages : 'None visible');
    console.log('');

    // Test 4: Try with correct credentials but no remember me
    console.log('Test 4: Testing correct credentials WITHOUT remember me...');
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForSelector('input[name="username"]');

    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    // Don't check remember me this time
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    const noRememberUrl = page.url();
    console.log('Correct creds (no remember me) result URL:', noRememberUrl);

    if (noRememberUrl.includes('/admin/dashboard')) {
      console.log('✓ Login works WITHOUT remember me!');

      // Check cookies
      const cookies = await context.cookies();
      const sessionCookie = cookies.find(c => c.name === 'cms_session');
      console.log('Session cookie present:', !!sessionCookie);

      // Now test logout
      await page.goto('https://dalthaus.net/admin/logout');
      await page.waitForURL('https://dalthaus.net/admin/login');
      console.log('Logout successful');

    } else {
      console.log('✗ Login failed even WITHOUT remember me');
      const errorMsgs = await page.locator('.error, .alert, .text-red-500').allTextContents();
      console.log('Error messages:', errorMsgs.length > 0 ? errorMsgs : 'None visible');
    }
    console.log('');

    // Test 5: Now try WITH remember me if login works without it
    if (noRememberUrl.includes('/admin/dashboard')) {
      console.log('Test 5: Testing correct credentials WITH remember me...');
      await page.goto('https://dalthaus.net/admin/login');
      await page.waitForSelector('input[name="username"]');

      await page.fill('input[name="username"]', 'kevin');
      await page.fill('input[name="password"]', '(130Bpm)');
      await page.check('input[name="remember_me"]');

      console.log('Remember me checkbox checked:', await page.isChecked('input[name="remember_me"]'));

      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');

      const withRememberUrl = page.url();
      console.log('Correct creds (WITH remember me) result URL:', withRememberUrl);

      if (withRememberUrl.includes('/admin/dashboard')) {
        console.log('✓ Login works WITH remember me!');

        // Check cookies
        const cookies = await context.cookies();
        const sessionCookie = cookies.find(c => c.name === 'cms_session');
        const rememberCookie = cookies.find(c => c.name === 'remember_token');

        console.log('Session cookie present:', !!sessionCookie);
        console.log('Remember token cookie present:', !!rememberCookie);

        if (rememberCookie) {
          console.log('Remember token details:');
          console.log('  Value length:', rememberCookie.value.length);
          console.log('  Domain:', rememberCookie.domain);
          console.log('  Path:', rememberCookie.path);
          console.log('  HttpOnly:', rememberCookie.httpOnly);
          console.log('  Secure:', rememberCookie.secure);

          if (rememberCookie.expires !== -1) {
            const expirationDate = new Date(rememberCookie.expires * 1000);
            const now = new Date();
            const daysDiff = Math.round((expirationDate - now) / (1000 * 60 * 60 * 24));
            console.log('  Expires in days:', daysDiff);
          } else {
            console.log('  Expires: Session cookie (should be persistent!)');
          }
        }

        // Test logout and persistent auth
        console.log('\nTesting logout and persistent authentication...');
        await page.goto('https://dalthaus.net/admin/logout');
        await page.waitForURL('https://dalthaus.net/admin/login');
        console.log('Logged out successfully');

        // Check if remember token persists
        const cookiesAfterLogout = await context.cookies();
        const rememberAfterLogout = cookiesAfterLogout.find(c => c.name === 'remember_token');
        console.log('Remember token persists after logout:', !!rememberAfterLogout);

        if (rememberAfterLogout) {
          // Test auto-login
          console.log('Testing auto-login...');
          await page.goto('https://dalthaus.net/admin/dashboard');
          await page.waitForLoadState('networkidle');
          const autoLoginUrl = page.url();
          console.log('Auto-login result URL:', autoLoginUrl);
          console.log('Auto-login successful:', autoLoginUrl.includes('/admin/dashboard') ? '✓ YES' : '✗ NO');
        }

      } else {
        console.log('✗ Login failed WITH remember me (but worked without it)');
        console.log('This suggests an issue with the remember me functionality specifically');
      }
    }

    console.log('\n' + '='.repeat(60));
    console.log('DIAGNOSIS COMPLETE');
    console.log('='.repeat(60));
  });
});