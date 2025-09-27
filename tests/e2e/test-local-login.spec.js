const { test, expect } = require('@playwright/test');

test.describe('Local Login Test', () => {
  test('test login on local server', async ({ page, context }) => {
    console.log('Testing login on local server...\n');

    // Clear cookies first
    await context.clearCookies();

    try {
      // Navigate to local login page
      await page.goto('http://localhost:8000/admin/login');
      await page.waitForSelector('input[name="username"]', { timeout: 5000 });

      console.log('Local login page loaded successfully');

      // Fill the form
      await page.fill('input[name="username"]', 'kevin');
      await page.fill('input[name="password"]', '(130Bpm)');
      await page.check('input[name="remember_me"]');

      console.log('Form filled with credentials');

      // Submit form
      await page.click('button[type="submit"]');

      // Wait for navigation
      await page.waitForLoadState('networkidle');

      const finalUrl = page.url();
      console.log('Final URL after login attempt:', finalUrl);

      if (finalUrl.includes('/admin/dashboard')) {
        console.log('✓ Local login successful!');

        // Check cookies
        const cookies = await context.cookies();
        const sessionCookie = cookies.find(c => c.name === 'cms_session');
        const rememberCookie = cookies.find(c => c.name === 'remember_token');

        console.log('Session cookie present:', !!sessionCookie);
        console.log('Remember token cookie present:', !!rememberCookie);

        if (rememberCookie) {
          console.log('Remember token details:');
          console.log('  Value length:', rememberCookie.value.length);
          console.log('  Expires:', rememberCookie.expires !== -1 ? new Date(rememberCookie.expires * 1000) : 'Session');
        }

        // Test logout and auto-login
        console.log('\nTesting logout and auto-login...');
        await page.goto('http://localhost:8000/admin/logout');
        await page.waitForURL('http://localhost:8000/admin/login');
        console.log('Logged out successfully');

        // Check if remember token still exists
        const cookiesAfterLogout = await context.cookies();
        const rememberAfterLogout = cookiesAfterLogout.find(c => c.name === 'remember_token');
        console.log('Remember token persists after logout:', !!rememberAfterLogout);

        // Test auto-login
        await page.goto('http://localhost:8000/admin/dashboard');
        await page.waitForLoadState('networkidle');
        const autoLoginUrl = page.url();
        console.log('Auto-login result URL:', autoLoginUrl);
        console.log('Auto-login successful:', autoLoginUrl.includes('/admin/dashboard'));

      } else {
        console.log('✗ Local login failed');
        console.log('Current URL:', finalUrl);

        // Check for error messages
        const errorElements = await page.locator('.error, .alert, .text-red-500').all();
        if (errorElements.length > 0) {
          console.log('Error messages:');
          for (const el of errorElements) {
            const text = await el.textContent();
            if (text && text.trim()) {
              console.log('  -', text.trim());
            }
          }
        }
      }

    } catch (error) {
      console.log('Error connecting to local server:', error.message);
      console.log('Make sure the local server is running with: php -S localhost:8000 router.php');
    }
  });
});