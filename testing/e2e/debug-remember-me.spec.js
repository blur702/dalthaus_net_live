const { test, expect } = require('@playwright/test');

test.describe('Remember Me Functionality Debugging', () => {
  test('Debug remember me checkbox functionality', async ({ page, context }) => {
    // Enable console logging
    page.on('console', msg => {
      const type = msg.type();
      const text = msg.text();
      if (type === 'error') {
        console.log(`[CONSOLE ERROR]: ${text}`);
      } else if (type === 'warning') {
        console.log(`[CONSOLE WARN]: ${text}`);
      }
    });

    // Monitor all network requests
    const networkLogs = [];
    page.on('request', request => {
      if (request.url().includes('/admin/login') && request.method() === 'POST') {
        console.log(`\n[REQUEST] ${request.method()} ${request.url()}`);
        const postData = request.postData();
        if (postData) {
          console.log('[POST DATA]:', postData);
        }
      }
    });

    page.on('response', async response => {
      if (response.url().includes('/admin/login') && response.request().method() === 'POST') {
        console.log(`[RESPONSE] Status: ${response.status()}`);
        console.log(`[RESPONSE] Headers:`, response.headers());

        try {
          const responseBody = await response.text();
          console.log('[RESPONSE BODY]:', responseBody.substring(0, 500));

          // Check for database errors
          if (responseBody.includes('SQLSTATE') || responseBody.includes('Fatal error') || responseBody.includes('Warning')) {
            console.log('\n⚠️ DATABASE/PHP ERROR DETECTED:');
            console.log(responseBody);
          }
        } catch (e) {
          console.log('[RESPONSE] Could not read body');
        }

        networkLogs.push({
          url: response.url(),
          status: response.status(),
          headers: response.headers()
        });
      }
    });

    // Test 1: Login WITH Remember Me checked
    console.log('\n========================================');
    console.log('TEST 1: LOGIN WITH REMEMBER ME CHECKED');
    console.log('========================================\n');

    await page.goto('https://dalthaus.net/admin/login', { waitUntil: 'networkidle' });

    // Take initial screenshot
    await page.screenshot({
      path: 'test-results/remember-me-initial.png',
      fullPage: true
    });

    // Fill in credentials
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');

    // Check the remember me checkbox
    const rememberCheckbox = page.locator('input[name="remember_me"]');
    const checkboxExists = await rememberCheckbox.count() > 0;

    if (checkboxExists) {
      console.log('✓ Remember me checkbox found');
      await rememberCheckbox.check();

      // Verify it's checked
      const isChecked = await rememberCheckbox.isChecked();
      console.log(`✓ Remember me checkbox is checked: ${isChecked}`);

      // Take screenshot before submission
      await page.screenshot({
        path: 'test-results/remember-me-checked.png',
        fullPage: true
      });
    } else {
      console.log('✗ Remember me checkbox NOT found on page');
    }

    // Submit the form
    console.log('\nSubmitting form WITH remember me...');
    const submitButton = page.locator('button[type="submit"]');

    // Wait for navigation or error
    const navigationPromise = page.waitForNavigation({
      waitUntil: 'networkidle',
      timeout: 10000
    }).catch(e => null);

    await submitButton.click();

    // Wait a bit for any immediate errors
    await page.waitForTimeout(2000);

    // Take screenshot after submission
    await page.screenshot({
      path: 'test-results/remember-me-after-submit.png',
      fullPage: true
    });

    const navigationResult = await navigationPromise;

    // Check where we ended up
    const currentUrl = page.url();
    console.log(`\nCurrent URL after submission: ${currentUrl}`);

    // Check for any visible error messages
    const errorMessages = await page.locator('.error, .alert-danger, .text-danger, .text-red-500').allTextContents();
    if (errorMessages.length > 0) {
      console.log('\n⚠️ ERROR MESSAGES FOUND:');
      errorMessages.forEach(msg => console.log(`  - ${msg}`));
    }

    // Check cookies for remember_token
    const cookies = await context.cookies();
    const rememberCookie = cookies.find(c => c.name === 'remember_token' || c.name.includes('remember'));
    if (rememberCookie) {
      console.log('\n✓ Remember cookie found:', {
        name: rememberCookie.name,
        domain: rememberCookie.domain,
        expires: rememberCookie.expires,
        httpOnly: rememberCookie.httpOnly,
        secure: rememberCookie.secure
      });
    } else {
      console.log('\n✗ No remember cookie found');
    }

    // Log out if we successfully logged in
    if (currentUrl.includes('/admin/dashboard')) {
      console.log('\n✓ Successfully logged in, now logging out for next test...');
      await page.goto('https://dalthaus.net/admin/logout');
    }

    // Test 2: Login WITHOUT Remember Me checked
    console.log('\n\n========================================');
    console.log('TEST 2: LOGIN WITHOUT REMEMBER ME');
    console.log('========================================\n');

    await page.goto('https://dalthaus.net/admin/login', { waitUntil: 'networkidle' });

    // Clear any existing cookies
    await context.clearCookies();

    // Fill in credentials
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');

    // Make sure remember me is NOT checked
    if (checkboxExists) {
      const rememberCheckbox2 = page.locator('input[name="remember_me"]');
      await rememberCheckbox2.uncheck();
      const isUnchecked = !(await rememberCheckbox2.isChecked());
      console.log(`✓ Remember me checkbox is unchecked: ${isUnchecked}`);
    }

    // Submit the form
    console.log('\nSubmitting form WITHOUT remember me...');

    const navigationPromise2 = page.waitForNavigation({
      waitUntil: 'networkidle',
      timeout: 10000
    }).catch(e => null);

    await submitButton.click();

    await page.waitForTimeout(2000);

    // Take screenshot
    await page.screenshot({
      path: 'test-results/no-remember-me-after-submit.png',
      fullPage: true
    });

    const navigationResult2 = await navigationPromise2;

    // Check where we ended up
    const currentUrl2 = page.url();
    console.log(`\nCurrent URL after submission: ${currentUrl2}`);

    // Check for any visible error messages
    const errorMessages2 = await page.locator('.error, .alert-danger, .text-danger, .text-red-500').allTextContents();
    if (errorMessages2.length > 0) {
      console.log('\n⚠️ ERROR MESSAGES FOUND:');
      errorMessages2.forEach(msg => console.log(`  - ${msg}`));
    }

    // Check cookies again
    const cookies2 = await context.cookies();
    const rememberCookie2 = cookies2.find(c => c.name === 'remember_token' || c.name.includes('remember'));
    if (rememberCookie2) {
      console.log('\n✗ Unexpected remember cookie found (should not exist):', rememberCookie2.name);
    } else {
      console.log('\n✓ No remember cookie (as expected)');
    }

    // Summary
    console.log('\n========================================');
    console.log('DEBUGGING SUMMARY');
    console.log('========================================');
    console.log(`\nWith Remember Me: ${currentUrl.includes('dashboard') ? '✓ SUCCESS' : '✗ FAILED'}`);
    console.log(`Without Remember Me: ${currentUrl2.includes('dashboard') ? '✓ SUCCESS' : '✗ FAILED'}`);

    if (networkLogs.length > 0) {
      console.log('\nNetwork Requests Summary:');
      networkLogs.forEach(log => {
        console.log(`  - ${log.url}: Status ${log.status}`);
      });
    }

    // Additional checks for database table issues
    console.log('\n========================================');
    console.log('POTENTIAL ISSUES TO CHECK');
    console.log('========================================');
    console.log('\n1. Check if remember_tokens table exists:');
    console.log('   mysql -u cms_user -p\'cms_password\' cms_db -e "SHOW TABLES LIKE \'remember_tokens\';"');
    console.log('\n2. Check remember_tokens table structure:');
    console.log('   mysql -u cms_user -p\'cms_password\' cms_db -e "DESCRIBE remember_tokens;"');
    console.log('\n3. Check for any entries in remember_tokens:');
    console.log('   mysql -u cms_user -p\'cms_password\' cms_db -e "SELECT * FROM remember_tokens;"');
  });

  test('Inspect login form HTML structure', async ({ page }) => {
    console.log('\n========================================');
    console.log('FORM STRUCTURE INSPECTION');
    console.log('========================================\n');

    await page.goto('https://dalthaus.net/admin/login', { waitUntil: 'networkidle' });

    // Get the form HTML
    const formHTML = await page.locator('form').first().innerHTML();
    console.log('Form HTML structure:');
    console.log(formHTML);

    // Check specific elements
    const hasUsername = await page.locator('input[name="username"]').count() > 0;
    const hasPassword = await page.locator('input[name="password"]').count() > 0;
    const hasRememberMe = await page.locator('input[name="remember_me"]').count() > 0;
    const hasCSRF = await page.locator('input[name="_token"]').count() > 0;

    console.log('\nForm elements check:');
    console.log(`  Username field: ${hasUsername ? '✓' : '✗'}`);
    console.log(`  Password field: ${hasPassword ? '✓' : '✗'}`);
    console.log(`  Remember me checkbox: ${hasRememberMe ? '✓' : '✗'}`);
    console.log(`  CSRF token: ${hasCSRF ? '✓' : '✗'}`);

    if (hasRememberMe) {
      const rememberMeElement = await page.locator('input[name="remember_me"]').first();
      const rememberMeType = await rememberMeElement.getAttribute('type');
      const rememberMeValue = await rememberMeElement.getAttribute('value');
      console.log(`\n  Remember me input type: ${rememberMeType}`);
      console.log(`  Remember me input value: ${rememberMeValue}`);
    }
  });
});