import { test, expect } from '@playwright/test';

const PROD_URL = 'https://dalthaus.net';
const USERNAME = 'kevin';
const PASSWORD = '(130Bpm)';

test('Debug remember me server errors', async ({ page }) => {
  console.log('=== DEBUGGING REMEMBER ME SERVER ERRORS ===\n');

  // Enable request/response logging
  const requestLogs = [];
  const responseLogs = [];

  page.on('request', request => {
    if (request.url().includes('/admin')) {
      requestLogs.push({
        method: request.method(),
        url: request.url(),
        headers: request.headers(),
        postData: request.postData()
      });
    }
  });

  page.on('response', async response => {
    if (response.url().includes('/admin')) {
      const headers = response.headers();
      let body = '';
      try {
        if (response.status() !== 200) {
          body = await response.text();
        }
      } catch (e) {
        body = 'Could not read response body';
      }

      responseLogs.push({
        url: response.url(),
        status: response.status(),
        statusText: response.statusText(),
        headers: headers,
        body: body.substring(0, 500) // First 500 chars
      });
    }
  });

  // Test normal login first
  console.log('Step 1: Testing normal login (control test)...');
  await page.goto(`${PROD_URL}/admin/login`);
  await page.fill('input[name="username"]', USERNAME);
  await page.fill('input[name="password"]', PASSWORD);
  // Ensure remember me is OFF
  const rememberCheckbox = page.locator('input[name="remember_me"]');
  await rememberCheckbox.uncheck();

  console.log('Submitting normal login...');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);

  const normalUrl = page.url();
  console.log(`Normal login result: ${normalUrl}`);
  console.log(`Normal login success: ${normalUrl.includes('/admin/dashboard')}`);

  // Clear logs for next test
  requestLogs.length = 0;
  responseLogs.length = 0;

  // Now test with remember me
  console.log('\nStep 2: Testing remember me login...');
  await page.goto(`${PROD_URL}/admin/login`);
  await page.fill('input[name="username"]', USERNAME);
  await page.fill('input[name="password"]', PASSWORD);
  await rememberCheckbox.check();

  console.log('Submitting remember me login...');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(5000); // Wait longer to capture potential errors

  const rememberUrl = page.url();
  console.log(`Remember me login result: ${rememberUrl}`);
  console.log(`Remember me login success: ${rememberUrl.includes('/admin/dashboard')}`);

  // Analyze the requests and responses
  console.log('\n=== REQUEST/RESPONSE ANALYSIS ===');

  if (requestLogs.length > 0) {
    console.log('\nRequests made:');
    requestLogs.forEach((req, index) => {
      console.log(`  ${index + 1}. ${req.method} ${req.url}`);
      if (req.postData) {
        console.log(`     POST Data: ${req.postData}`);
      }
    });
  }

  if (responseLogs.length > 0) {
    console.log('\nResponses received:');
    responseLogs.forEach((resp, index) => {
      console.log(`  ${index + 1}. ${resp.status} ${resp.statusText} - ${resp.url}`);

      // Check for error indicators in headers
      if (resp.headers['location']) {
        console.log(`     Redirect to: ${resp.headers['location']}`);
      }

      // Check for server errors in response body
      if (resp.body && (resp.body.includes('error') || resp.body.includes('Error') || resp.body.includes('exception'))) {
        console.log(`     Error in body: ${resp.body.substring(0, 200)}...`);
      }
    });
  }

  // Check for any network errors or timeouts
  console.log('\n=== ADDITIONAL DIAGNOSTICS ===');

  // Check if we're still on login page and why
  if (rememberUrl.includes('/admin/login')) {
    console.log('\nAnalyzing failed login page...');

    // Check for error messages on the page
    const pageText = await page.textContent('body');
    const hasError = pageText.toLowerCase().includes('error') ||
                    pageText.toLowerCase().includes('invalid') ||
                    pageText.toLowerCase().includes('failed');

    console.log(`Page contains error message: ${hasError}`);

    if (hasError) {
      // Try to extract the actual error message
      const errorSelectors = [
        '.error',
        '.alert-danger',
        '.alert-error',
        '.flash-error',
        '[role="alert"]',
        '.text-red-500',
        '.text-red-600'
      ];

      for (const selector of errorSelectors) {
        try {
          const errorElement = page.locator(selector);
          if (await errorElement.count() > 0) {
            const errorText = await errorElement.textContent();
            console.log(`Error message found (${selector}): ${errorText}`);
          }
        } catch (e) {
          // Ignore selector errors
        }
      }
    }

    // Take screenshot for manual inspection
    await page.screenshot({ path: 'remember-me-failed-login.png', fullPage: true });
    console.log('Screenshot saved for manual inspection');
  }

  // Test if the issue is with remember me processing specifically
  console.log('\n=== TESTING REMEMBER ME PARAMETER VARIATIONS ===');

  // Test with different remember me values
  const variations = [
    { value: '1', description: 'remember_me=1' },
    { value: 'on', description: 'remember_me=on' },
    { value: 'true', description: 'remember_me=true' }
  ];

  for (const variation of variations) {
    console.log(`\nTesting ${variation.description}...`);

    await page.goto(`${PROD_URL}/admin/login`);
    await page.fill('input[name="username"]', USERNAME);
    await page.fill('input[name="password"]', PASSWORD);

    // Manually set the checkbox value
    await page.evaluate((value) => {
      const checkbox = document.querySelector('input[name="remember_me"]');
      if (checkbox) {
        checkbox.value = value;
        checkbox.checked = true;
      }
    }, variation.value);

    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);

    const testUrl = page.url();
    const success = testUrl.includes('/admin/dashboard');
    console.log(`  Result: ${success ? 'SUCCESS' : 'FAILED'} - ${testUrl}`);

    if (success) {
      console.log(`  ✅ Found working variation: ${variation.description}`);
      break;
    }
  }
});
