import { test, expect } from '@playwright/test';

test.describe('Debug Login Issues', () => {
  test('should capture detailed login flow and error messages', async ({ page }) => {
    // Enable detailed logging
    page.on('console', msg => {
      console.log(`Console ${msg.type()}: ${msg.text()}`);
    });

    page.on('response', async response => {
      if (response.url().includes('/admin/login') && response.request().method() === 'POST') {
        console.log(`Login POST Response: ${response.status()}`);

        // Try to get response headers
        const headers = response.headers();
        console.log('Response headers:', headers);

        if (headers.location) {
          console.log(`Redirect location: ${headers.location}`);
        }

        // Try to get response text (if any)
        try {
          const text = await response.text();
          if (text && text.length < 1000) {
            console.log('Response body:', text);
          }
        } catch (e) {
          console.log('Could not read response body');
        }
      }
    });

    console.log('Navigating to login page...');
    await page.goto('https://dalthaus.net/admin/login');

    // Check for any existing error messages on page load
    const initialErrors = await page.locator('.alert, .error, .message, .notification').all();
    if (initialErrors.length > 0) {
      for (const error of initialErrors) {
        const text = await error.textContent();
        console.log(`Initial error message: ${text}`);
      }
    }

    // Fill form
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.check('input[name="remember_me"]');

    console.log('Submitting form...');

    // Listen for the response and then the redirect
    const responsePromise = page.waitForResponse(response =>
      response.url().includes('/admin/login') && response.request().method() === 'POST'
    );

    await page.click('button[type="submit"]');

    // Wait for the POST response
    const response = await responsePromise;
    console.log(`Login response status: ${response.status()}`);

    // Wait for page to stabilize after redirect
    await page.waitForTimeout(2000);

    // Check final URL
    const finalUrl = page.url();
    console.log(`Final URL: ${finalUrl}`);

    // Look for any error messages after redirect
    const errorElements = await page.locator('.alert, .error, .message, .notification, .flash').all();

    if (errorElements.length > 0) {
      console.log('Error messages found:');
      for (const error of errorElements) {
        const text = await error.textContent();
        console.log(`  - ${text}`);
      }
    } else {
      console.log('No error messages found on page');
    }

    // Check if we have any session/auth cookies
    const cookies = await page.context().cookies();
    const sessionCookie = cookies.find(c => c.name === 'cms_session');

    if (sessionCookie) {
      console.log(`Session cookie found: ${sessionCookie.value.substring(0, 20)}...`);
      console.log(`Session cookie secure: ${sessionCookie.secure}`);
      console.log(`Session cookie httpOnly: ${sessionCookie.httpOnly}`);
    } else {
      console.log('No session cookie found');
    }

    // Check page content for any hidden error indicators
    const pageText = await page.textContent('body');
    if (pageText.toLowerCase().includes('invalid') ||
        pageText.toLowerCase().includes('incorrect') ||
        pageText.toLowerCase().includes('failed')) {
      console.log('Page contains failure-related text');
    }

    // Try to access the dashboard directly to see if we're actually logged in
    console.log('Testing direct dashboard access...');
    await page.goto('https://dalthaus.net/admin/dashboard');
    await page.waitForTimeout(1000);

    const dashboardUrl = page.url();
    console.log(`Dashboard access URL: ${dashboardUrl}`);

    if (dashboardUrl.includes('/login')) {
      console.log('❌ Not logged in - redirected to login page');
    } else if (dashboardUrl.includes('/dashboard') || dashboardUrl.includes('/admin')) {
      console.log('✅ Successfully logged in - can access dashboard');
    } else {
      console.log('🤷 Unclear login status');
    }

    await page.screenshot({
      path: 'tests/screenshots/debug-final.png',
      fullPage: true
    });
  });
});