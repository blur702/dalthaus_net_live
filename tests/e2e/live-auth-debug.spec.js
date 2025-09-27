const { test, expect } = require('@playwright/test');

test.describe('Live Authentication Debug', () => {
  test('Debug live login with network monitoring', async ({ page, context }) => {
    console.log('Starting live authentication debug...');

    // Enable request/response logging
    page.on('request', request => {
      if (request.url().includes('login')) {
        console.log(`REQUEST: ${request.method()} ${request.url()}`);
        if (request.method() === 'POST') {
          console.log(`POST DATA: ${request.postData()}`);
        }
      }
    });

    page.on('response', response => {
      if (response.url().includes('login') || response.status() >= 400) {
        console.log(`RESPONSE: ${response.status()} ${response.url()}`);
      }
    });

    // Clear cookies and navigate
    await context.clearCookies();
    await page.goto('https://dalthaus.net/admin/login');
    console.log('✅ Navigated to login page');

    // Wait for page to be fully loaded
    await page.waitForLoadState('domcontentloaded');
    await page.waitForLoadState('networkidle');

    // Check for any error messages already on page
    const existingError = await page.locator('.alert-danger, .error').textContent().catch(() => null);
    if (existingError) {
      console.log(`Existing error on page: ${existingError}`);
    }

    // Check if CSRF token is present
    const csrfToken = await page.locator('input[name="_token"]').getAttribute('value').catch(() => null);
    console.log(`CSRF token present: ${csrfToken ? 'Yes' : 'No'}`);
    if (csrfToken) {
      console.log(`CSRF token: ${csrfToken.substring(0, 10)}...`);
    }

    // Fill form step by step
    const usernameField = page.locator('input[name="username"]');
    const passwordField = page.locator('input[name="password"]');
    const rememberField = page.locator('input[name="remember_me"]');
    const submitButton = page.locator('button[type="submit"]');

    // Check if fields exist
    const usernameExists = await usernameField.isVisible();
    const passwordExists = await passwordField.isVisible();
    const rememberExists = await rememberField.isVisible();
    const submitExists = await submitButton.isVisible();

    console.log(`Username field visible: ${usernameExists}`);
    console.log(`Password field visible: ${passwordExists}`);
    console.log(`Remember field visible: ${rememberExists}`);
    console.log(`Submit button visible: ${submitExists}`);

    if (!usernameExists || !passwordExists || !submitExists) {
      console.log('❌ Required form fields not found');
      const pageContent = await page.content();
      console.log('Page HTML contains:', pageContent.substring(0, 500));
      return;
    }

    // Fill the form
    await usernameField.fill('kevin');
    console.log('✅ Username filled');

    await passwordField.fill('(130Bpm)');
    console.log('✅ Password filled');

    if (rememberExists) {
      await rememberField.check();
      const isChecked = await rememberField.isChecked();
      console.log(`✅ Remember me checked: ${isChecked}`);
    }

    // Take screenshot before submit
    await page.screenshot({
      path: 'tests/screenshots/live-debug-before-submit.png',
      fullPage: true
    });

    // Submit and wait for response
    console.log('Submitting form...');
    await submitButton.click();

    // Wait a bit for the request to complete
    await page.waitForTimeout(3000);
    await page.waitForLoadState('networkidle');

    const finalUrl = page.url();
    console.log(`Final URL: ${finalUrl}`);

    // Take screenshot after submit
    await page.screenshot({
      path: 'tests/screenshots/live-debug-after-submit.png',
      fullPage: true
    });

    // Check for any error messages
    const errorMessage = await page.locator('.alert-danger, .error, .alert').textContent().catch(() => null);
    if (errorMessage) {
      console.log(`Error message found: ${errorMessage}`);
    }

    // Check page title
    const pageTitle = await page.title();
    console.log(`Page title: ${pageTitle}`);

    // If still on login page, check what happened
    if (finalUrl.includes('/admin/login')) {
      console.log('❌ Still on login page');

      // Check form validation
      const formErrors = await page.locator('.invalid-feedback, .error-message').allTextContents();
      if (formErrors.length > 0) {
        console.log('Form validation errors:', formErrors);
      }

      // Check browser console for errors
      page.on('console', msg => {
        if (msg.type() === 'error') {
          console.log(`Console error: ${msg.text()}`);
        }
      });

    } else if (finalUrl.includes('/admin/dashboard')) {
      console.log('✅ Successfully logged in!');

      // Check cookies
      const cookies = await context.cookies();
      const sessionCookie = cookies.find(c => c.name === 'cms_session');
      const rememberCookie = cookies.find(c => c.name === 'remember_token');

      console.log(`Session cookie: ${sessionCookie ? 'Present' : 'Missing'}`);
      console.log(`Remember token: ${rememberCookie ? 'Present' : 'Missing'}`);

      if (rememberCookie) {
        console.log(`Remember token value length: ${rememberCookie.value.length}`);
        if (rememberCookie.expires) {
          const expiryDate = new Date(rememberCookie.expires * 1000);
          const daysDiff = Math.round((expiryDate - Date.now()) / (1000 * 60 * 60 * 24));
          console.log(`Remember token expires in ${daysDiff} days`);
        }
      }
    } else {
      console.log(`❓ Unexpected redirect to: ${finalUrl}`);
    }
  });

  test('Manual test with different credentials', async ({ page, context }) => {
    console.log('\n=== Testing with explicit credential verification ===');

    await context.clearCookies();
    await page.goto('https://dalthaus.net/admin/login');

    // Try to verify what the actual admin user is
    console.log('Attempting login with documented credentials...');

    // First try: kevin / (130Bpm)
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.check('input[name="remember_me"]');

    await page.screenshot({
      path: 'tests/screenshots/credentials-test-1.png',
      fullPage: true
    });

    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    const url1 = page.url();
    console.log(`Test 1 result URL: ${url1}`);

    if (url1.includes('dashboard')) {
      console.log('✅ Success with kevin/(130Bpm)');
      return;
    }

    // Check for specific error message
    const errorMsg = await page.locator('.alert-danger, .error').textContent().catch(() => null);
    console.log(`Error message: ${errorMsg || 'None found'}`);

    // If first attempt failed, the issue might be elsewhere
    console.log('Login failed - this suggests a deeper issue with the authentication system');
  });
});