const { test, expect } = require('@playwright/test');

test.describe('Simple Login Test', () => {
  test('test login process step by step', async ({ page, context }) => {
    console.log('Starting simple login test...\n');

    // Clear cookies first
    await context.clearCookies();

    // Navigate to login page
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForSelector('input[name="username"]');

    console.log('Login page loaded successfully');

    // Fill the form
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.check('input[name="remember_me"]');

    console.log('Form filled with credentials');

    // Monitor the next response
    const responsePromise = page.waitForResponse(response =>
      response.url().includes('admin/login') && response.request().method() === 'POST'
    );

    // Submit form
    await page.click('button[type="submit"]');

    // Wait for the POST response
    const response = await responsePromise;
    console.log('POST response status:', response.status());

    // Wait for navigation to complete
    await page.waitForLoadState('networkidle');

    const finalUrl = page.url();
    console.log('Final URL after login attempt:', finalUrl);

    if (finalUrl.includes('/admin/dashboard')) {
      console.log('✓ Login successful - redirected to dashboard');

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
        console.log('  HttpOnly:', rememberCookie.httpOnly);
        console.log('  Secure:', rememberCookie.secure);
      }

    } else if (finalUrl.includes('/admin/login')) {
      console.log('✗ Login failed - stayed on login page');

      // Check for error messages
      const errorElements = await page.locator('.error, .alert, .text-red-500, .text-red-600').all();
      if (errorElements.length > 0) {
        console.log('Error messages found:');
        for (const el of errorElements) {
          const text = await el.textContent();
          if (text && text.trim()) {
            console.log('  -', text.trim());
          }
        }
      } else {
        console.log('No visible error messages found');
      }

      // Check if form fields were cleared
      const usernameValue = await page.inputValue('input[name="username"]');
      const passwordValue = await page.inputValue('input[name="password"]');
      console.log('Username field after submit:', usernameValue || '(empty)');
      console.log('Password field after submit:', passwordValue || '(empty)');

    } else {
      console.log('? Unexpected redirect to:', finalUrl);
    }

    // Take a screenshot for debugging
    await page.screenshot({ path: 'login-result.png', fullPage: true });
    console.log('Screenshot saved as login-result.png');
  });
});