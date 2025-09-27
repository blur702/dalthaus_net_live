const { test, expect } = require('@playwright/test');

test.describe('Detailed Login Analysis', () => {
  test('analyze login failure in detail', async ({ page, context }) => {
    console.log('Starting detailed login analysis...\n');

    // Clear all cookies first
    await context.clearCookies();
    console.log('Cleared all cookies to start fresh\n');

    // Navigate to login page
    console.log('Navigating to login page...');
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('networkidle');

    // Check initial page state
    console.log('Initial page loaded. Title:', await page.title());
    console.log('URL:', page.url());

    // Wait for form to be ready
    await page.waitForSelector('input[name="username"]', { state: 'visible' });

    // Look for any existing error messages
    const initialErrors = await page.locator('.error, .alert, .text-red-500, .text-red-600, [class*="error"], [class*="alert"]').allTextContents();
    console.log('Initial error messages:', initialErrors.length > 0 ? initialErrors : 'None');

    // Fill form carefully
    console.log('\nFilling form fields...');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');

    // Verify fields are filled
    const username = await page.inputValue('input[name="username"]');
    const password = await page.inputValue('input[name="password"]');
    console.log('Username filled:', username);
    console.log('Password length:', password.length);

    // Check remember me checkbox
    await page.check('input[name="remember_me"]');
    const rememberChecked = await page.isChecked('input[name="remember_me"]');
    console.log('Remember me checked:', rememberChecked);

    // Get CSRF token
    const csrfToken = await page.inputValue('input[name="_token"]');
    console.log('CSRF token present:', csrfToken ? 'Yes' : 'No');
    console.log('CSRF token length:', csrfToken ? csrfToken.length : 0);

    // Set up response monitoring
    const responses = [];
    page.on('response', response => {
      responses.push({
        url: response.url(),
        status: response.status(),
        statusText: response.statusText(),
        headers: Object.fromEntries(response.headers())
      });
    });

    // Monitor requests
    const requests = [];
    page.on('request', request => {
      if (request.url().includes('admin') || request.url().includes('login')) {
        requests.push({
          url: request.url(),
          method: request.method(),
          headers: Object.fromEntries(request.headers()),
          postData: request.postData()
        });
      }
    });

    // Submit form
    console.log('\nSubmitting form...');
    await page.click('button[type="submit"]');

    // Wait for response
    await page.waitForTimeout(5000);

    console.log('\nAfter submission:');
    console.log('Current URL:', page.url());

    // Check for any error messages after submission
    const postSubmitErrors = await page.locator('.error, .alert, .text-red-500, .text-red-600, [class*="error"], [class*="alert"]').allTextContents();
    console.log('Post-submit error messages:', postSubmitErrors.length > 0 ? postSubmitErrors : 'None');

    // Check if fields are cleared (indicating form was processed)
    const usernameAfter = await page.inputValue('input[name="username"]');
    const passwordAfter = await page.inputValue('input[name="password"]');
    console.log('Username after submit:', usernameAfter);
    console.log('Password after submit:', passwordAfter);

    // Check network activity
    console.log('\nNetwork Activity:');
    requests.forEach((req, i) => {
      console.log(`Request ${i + 1}:`);
      console.log(`  Method: ${req.method}`);
      console.log(`  URL: ${req.url}`);
      if (req.postData) {
        console.log(`  POST Data: ${req.postData.substring(0, 200)}...`);
      }
    });

    responses.forEach((resp, i) => {
      if (resp.url.includes('admin')) {
        console.log(`Response ${i + 1}:`);
        console.log(`  Status: ${resp.status} ${resp.statusText}`);
        console.log(`  URL: ${resp.url}`);
        if (resp.headers.location) {
          console.log(`  Redirect to: ${resp.headers.location}`);
        }
      }
    });

    // Check cookies after submission
    const cookies = await context.cookies();
    console.log('\nCookies after submission:');
    cookies.forEach(cookie => {
      console.log(`  ${cookie.name}: ${cookie.value.substring(0, 20)}...`);
    });

    // Check if we can manually navigate to dashboard
    console.log('\nTesting manual navigation to dashboard...');
    await page.goto('https://dalthaus.net/admin/dashboard');
    await page.waitForTimeout(2000);
    console.log('Dashboard URL after manual navigation:', page.url());

    // Check page source for PHP errors
    const pageContent = await page.content();
    if (pageContent.includes('Fatal error') || pageContent.includes('Warning:') || pageContent.includes('Notice:')) {
      console.log('\nPHP errors detected in page source!');
      const lines = pageContent.split('\n');
      lines.forEach((line, i) => {
        if (line.includes('Fatal error') || line.includes('Warning:') || line.includes('Notice:')) {
          console.log(`Line ${i}: ${line.trim()}`);
        }
      });
    } else {
      console.log('\nNo PHP errors detected in page source');
    }

    // Try a simple test - check if user exists
    console.log('\nTesting if we can at least access any admin page...');
    await page.goto('https://dalthaus.net/admin/');
    await page.waitForTimeout(2000);
    console.log('Admin root URL result:', page.url());
  });
});