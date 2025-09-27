const { test, expect } = require('@playwright/test');

test.describe('Remember Me Functionality Diagnosis', () => {
  const baseUrl = 'https://dalthaus.net';
  const loginUrl = `${baseUrl}/admin/login`;
  const dashboardUrl = `${baseUrl}/admin/dashboard`;
  const credentials = {
    username: 'kevin',
    password: '(130Bpm)'
  };

  test.beforeEach(async ({ context }) => {
    // Clear all cookies and local storage before each test
    await context.clearCookies();
  });

  test('1. BASELINE: Login WITHOUT remember me (should work)', async ({ page, context }) => {
    console.log('\n=== BASELINE TEST: Login WITHOUT Remember Me ===\n');

    // Navigate to login page
    await page.goto(loginUrl);
    console.log('✓ Navigated to login page');

    // Monitor network requests
    const requests = [];
    page.on('request', request => {
      if (request.url().includes('/admin/login') && request.method() === 'POST') {
        requests.push({
          url: request.url(),
          method: request.method(),
          postData: request.postData(),
          headers: request.headers()
        });
      }
    });

    // Monitor responses
    const responses = [];
    page.on('response', response => {
      if (response.url().includes('/admin/login') && response.request().method() === 'POST') {
        responses.push({
          status: response.status(),
          url: response.url(),
          headers: response.headers()
        });
      }
    });

    // Fill login form WITHOUT checking remember me
    await page.fill('input[name="username"]', credentials.username);
    await page.fill('input[name="password"]', credentials.password);

    // Verify remember me checkbox is NOT checked
    const rememberCheckbox = page.locator('input[name="remember_me"]');
    const isCheckedBefore = await rememberCheckbox.isChecked();
    console.log(`Remember me checkbox before: ${isCheckedBefore ? 'CHECKED' : 'NOT CHECKED'}`);
    expect(isCheckedBefore).toBe(false);

    // Submit form
    console.log('Submitting login form...');
    await Promise.all([
      page.waitForNavigation(),
      page.click('button[type="submit"]')
    ]);

    // Check if we're on dashboard
    const currentUrl = page.url();
    console.log(`After login URL: ${currentUrl}`);

    if (currentUrl === dashboardUrl) {
      console.log('✓ BASELINE SUCCESS: Login worked without remember me');
    } else {
      console.log('✗ BASELINE FAILED: Did not reach dashboard');
    }

    // Log request details
    if (requests.length > 0) {
      console.log('\nPOST Request Data (WITHOUT remember me):');
      console.log('Post data:', requests[0].postData);
    }

    // Log response details
    if (responses.length > 0) {
      console.log('\nResponse Status:', responses[0].status);
    }

    // Check cookies
    const cookies = await context.cookies();
    console.log('\nCookies after baseline login:');
    cookies.forEach(cookie => {
      console.log(`  - ${cookie.name}: ${cookie.value.substring(0, 20)}... (domain: ${cookie.domain})`);
    });

    // Verify we can access admin pages
    await page.goto(`${baseUrl}/admin/content`);
    const contentPageLoaded = page.url().includes('/admin/content');
    console.log(`Can access admin content: ${contentPageLoaded ? 'YES' : 'NO'}`);

    expect(currentUrl).toBe(dashboardUrl);
  });

  test('2. FAILURE TEST: Login WITH remember me checkbox', async ({ page, context }) => {
    console.log('\n=== FAILURE TEST: Login WITH Remember Me ===\n');

    // Navigate to login page
    await page.goto(loginUrl);
    console.log('✓ Navigated to login page');

    // Monitor console errors
    const consoleMessages = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleMessages.push(msg.text());
      }
    });

    // Monitor network requests in detail
    const requests = [];
    page.on('request', request => {
      if (request.url().includes('/admin/login') && request.method() === 'POST') {
        requests.push({
          url: request.url(),
          method: request.method(),
          postData: request.postData(),
          headers: request.headers()
        });
      }
    });

    // Monitor responses in detail
    const responses = [];
    page.on('response', async response => {
      if (response.url().includes('/admin/login') && response.request().method() === 'POST') {
        let body = null;
        try {
          body = await response.text();
        } catch (e) {
          body = 'Could not read response body';
        }
        responses.push({
          status: response.status(),
          url: response.url(),
          headers: response.headers(),
          body: body ? body.substring(0, 500) : null
        });
      }
    });

    // Fill login form
    await page.fill('input[name="username"]', credentials.username);
    await page.fill('input[name="password"]', credentials.password);

    // CHECK the remember me checkbox
    const rememberCheckbox = page.locator('input[name="remember_me"]');
    await rememberCheckbox.check();

    // Verify checkbox is checked
    const isCheckedAfter = await rememberCheckbox.isChecked();
    console.log(`Remember me checkbox after checking: ${isCheckedAfter ? 'CHECKED' : 'NOT CHECKED'}`);
    expect(isCheckedAfter).toBe(true);

    // Take screenshot before submission
    await page.screenshot({ path: 'test-results/before-remember-me-submit.png' });

    // Submit form and monitor what happens
    console.log('Submitting login form WITH remember me...');

    let navigationHappened = false;
    let errorOccurred = false;

    try {
      await Promise.all([
        page.waitForNavigation({ timeout: 10000 }),
        page.click('button[type="submit"]')
      ]);
      navigationHappened = true;
    } catch (error) {
      console.log('Navigation error or timeout:', error.message);
      errorOccurred = true;
    }

    // Wait a bit for any processing
    await page.waitForTimeout(2000);

    // Check current state
    const currentUrl = page.url();
    console.log(`After login attempt URL: ${currentUrl}`);

    // Take screenshot after submission
    await page.screenshot({ path: 'test-results/after-remember-me-submit.png' });

    // Check if we're still on login page
    if (currentUrl.includes('/admin/login')) {
      console.log('✗ FAILURE CONFIRMED: Still on login page after submission');

      // Look for error messages
      const errorMessages = await page.locator('.alert-danger, .error, .text-danger').allTextContents();
      if (errorMessages.length > 0) {
        console.log('Error messages found on page:');
        errorMessages.forEach(msg => console.log(`  - ${msg}`));
      }
    } else if (currentUrl === dashboardUrl) {
      console.log('✓ UNEXPECTED: Successfully reached dashboard WITH remember me');
    } else {
      console.log(`✗ Redirected to unexpected page: ${currentUrl}`);
    }

    // Log request details
    if (requests.length > 0) {
      console.log('\nPOST Request Data (WITH remember me):');
      console.log('Post data:', requests[0].postData);
      console.log('Content-Type:', requests[0].headers['content-type']);
    } else {
      console.log('\n✗ NO POST REQUEST CAPTURED - Form may not have submitted');
    }

    // Log response details
    if (responses.length > 0) {
      console.log('\nResponse Details:');
      console.log('Status:', responses[0].status);
      console.log('Response headers:', JSON.stringify(responses[0].headers, null, 2));
      if (responses[0].body) {
        console.log('Response body (first 500 chars):');
        console.log(responses[0].body);
      }
    } else {
      console.log('\n✗ NO RESPONSE CAPTURED');
    }

    // Check console errors
    if (consoleMessages.length > 0) {
      console.log('\nConsole Errors:');
      consoleMessages.forEach(msg => console.log(`  - ${msg}`));
    }

    // Check cookies
    const cookies = await context.cookies();
    console.log('\nCookies after remember me attempt:');
    cookies.forEach(cookie => {
      console.log(`  - ${cookie.name}: ${cookie.value.substring(0, 20)}... (domain: ${cookie.domain})`);
    });

    // Check for remember_token cookie specifically
    const rememberTokenCookie = cookies.find(c => c.name === 'remember_token');
    if (rememberTokenCookie) {
      console.log('\n✓ Remember token cookie WAS created');
    } else {
      console.log('\n✗ Remember token cookie was NOT created');
    }
  });

  test('3. NETWORK COMPARISON: Compare working vs failing requests', async ({ page, context }) => {
    console.log('\n=== NETWORK COMPARISON TEST ===\n');

    let workingRequest = null;
    let failingRequest = null;

    // Test 1: Capture working request (no remember me)
    await page.goto(loginUrl);

    page.on('request', request => {
      if (request.url().includes('/admin/login') && request.method() === 'POST') {
        if (!workingRequest) {
          workingRequest = {
            postData: request.postData(),
            headers: request.headers()
          };
        }
      }
    });

    await page.fill('input[name="username"]', credentials.username);
    await page.fill('input[name="password"]', credentials.password);

    await Promise.all([
      page.waitForNavigation(),
      page.click('button[type="submit"]')
    ]);

    console.log('Working request captured');

    // Clear and test 2: Capture failing request (with remember me)
    await context.clearCookies();
    await page.goto(loginUrl);

    page.removeAllListeners('request');
    page.on('request', request => {
      if (request.url().includes('/admin/login') && request.method() === 'POST') {
        if (!failingRequest) {
          failingRequest = {
            postData: request.postData(),
            headers: request.headers()
          };
        }
      }
    });

    await page.fill('input[name="username"]', credentials.username);
    await page.fill('input[name="password"]', credentials.password);
    await page.check('input[name="remember_me"]');

    try {
      await Promise.all([
        page.waitForNavigation({ timeout: 5000 }),
        page.click('button[type="submit"]')
      ]);
    } catch (e) {
      console.log('Failed request may have timed out');
    }

    // Compare the requests
    console.log('\n=== REQUEST COMPARISON ===');
    console.log('\nWORKING Request (no remember):');
    console.log(workingRequest?.postData || 'No data captured');

    console.log('\nFAILING Request (with remember):');
    console.log(failingRequest?.postData || 'No data captured');

    if (workingRequest && failingRequest) {
      console.log('\n=== DIFFERENCES ===');

      // Parse form data
      const parseFormData = (data) => {
        const params = new URLSearchParams(data);
        return Object.fromEntries(params);
      };

      const workingData = parseFormData(workingRequest.postData);
      const failingData = parseFormData(failingRequest.postData);

      console.log('\nWorking form fields:', Object.keys(workingData));
      console.log('Failing form fields:', Object.keys(failingData));

      // Find differences
      const allKeys = new Set([...Object.keys(workingData), ...Object.keys(failingData)]);
      for (const key of allKeys) {
        if (workingData[key] !== failingData[key]) {
          console.log(`\nDifference in '${key}':`);
          console.log(`  Working: ${workingData[key] || 'undefined'}`);
          console.log(`  Failing: ${failingData[key] || 'undefined'}`);
        }
      }
    }
  });

  test('4. FORM INSPECTION: Analyze form structure and submission', async ({ page }) => {
    console.log('\n=== FORM STRUCTURE INSPECTION ===\n');

    await page.goto(loginUrl);

    // Inspect form structure
    const formExists = await page.locator('form').count() > 0;
    console.log(`Form exists: ${formExists}`);

    if (formExists) {
      // Get form attributes
      const formAction = await page.locator('form').first().getAttribute('action');
      const formMethod = await page.locator('form').first().getAttribute('method');
      console.log(`Form action: ${formAction}`);
      console.log(`Form method: ${formMethod}`);

      // Check all form fields
      const inputs = await page.locator('form input').all();
      console.log('\nForm inputs found:');
      for (const input of inputs) {
        const name = await input.getAttribute('name');
        const type = await input.getAttribute('type');
        const value = await input.getAttribute('value');
        console.log(`  - ${name} (type: ${type}, value: ${value || 'empty'})`);
      }

      // Check CSRF token
      const csrfToken = await page.locator('input[name="_token"]').getAttribute('value');
      console.log(`\nCSRF Token present: ${csrfToken ? 'YES' : 'NO'}`);
      if (csrfToken) {
        console.log(`CSRF Token length: ${csrfToken.length}`);
      }

      // Check remember me checkbox specifically
      const rememberCheckbox = page.locator('input[name="remember_me"]');
      const checkboxExists = await rememberCheckbox.count() > 0;
      console.log(`\nRemember me checkbox exists: ${checkboxExists}`);

      if (checkboxExists) {
        const checkboxType = await rememberCheckbox.getAttribute('type');
        const checkboxValue = await rememberCheckbox.getAttribute('value');
        console.log(`Checkbox type: ${checkboxType}`);
        console.log(`Checkbox value attribute: ${checkboxValue}`);
      }
    }
  });

  test('5. JAVASCRIPT BEHAVIOR: Monitor JS errors and form handling', async ({ page }) => {
    console.log('\n=== JAVASCRIPT BEHAVIOR TEST ===\n');

    // Monitor all console messages
    const consoleMessages = [];
    page.on('console', msg => {
      consoleMessages.push({
        type: msg.type(),
        text: msg.text()
      });
    });

    // Monitor page errors
    const pageErrors = [];
    page.on('pageerror', error => {
      pageErrors.push(error.message);
    });

    await page.goto(loginUrl);

    // Check if there's any JavaScript preventing form submission
    const hasSubmitHandler = await page.evaluate(() => {
      const form = document.querySelector('form');
      if (form) {
        // Check for jQuery submit handlers
        if (typeof jQuery !== 'undefined' && jQuery._data) {
          const events = jQuery._data(form, 'events');
          return events && events.submit ? true : false;
        }
        // Check for native submit handler
        return form.onsubmit !== null;
      }
      return false;
    });

    console.log(`Form has JavaScript submit handler: ${hasSubmitHandler ? 'YES' : 'NO'}`);

    // Try to submit with remember me and capture any JS errors
    await page.fill('input[name="username"]', credentials.username);
    await page.fill('input[name="password"]', credentials.password);
    await page.check('input[name="remember_me"]');

    // Inject custom monitoring
    await page.evaluate(() => {
      const form = document.querySelector('form');
      if (form) {
        const originalSubmit = form.submit;
        form.submit = function() {
          console.log('Form.submit() called');
          return originalSubmit.apply(this, arguments);
        };

        form.addEventListener('submit', (e) => {
          console.log('Submit event triggered');
          console.log('Form data:', new FormData(form));
        }, true);
      }
    });

    // Click submit
    await page.click('button[type="submit"]');

    // Wait for any async operations
    await page.waitForTimeout(3000);

    // Report console messages
    if (consoleMessages.length > 0) {
      console.log('\nConsole messages:');
      consoleMessages.forEach(msg => {
        console.log(`  [${msg.type}] ${msg.text}`);
      });
    }

    // Report page errors
    if (pageErrors.length > 0) {
      console.log('\nPage errors:');
      pageErrors.forEach(error => {
        console.log(`  - ${error}`);
      });
    }
  });
});