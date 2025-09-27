const { test, expect } = require('@playwright/test');

test.describe('Remember Me Specific Debugging', () => {
  test('Compare normal login vs remember me login', async ({ page, context }) => {
    // Enable all logging
    page.on('console', msg => {
      const type = msg.type();
      const text = msg.text();
      console.log(`[CONSOLE ${type.toUpperCase()}]: ${text}`);
    });

    // Track requests and responses
    const networkLogs = [];
    page.on('request', request => {
      if (request.url().includes('admin/login') && request.method() === 'POST') {
        const postData = request.postData();
        console.log(`\n[REQUEST] POST ${request.url()}`);
        console.log(`[POST DATA] ${postData}`);
        networkLogs.push({ type: 'request', data: postData });
      }
    });

    page.on('response', async response => {
      if (response.url().includes('admin/login') && response.request().method() === 'POST') {
        const headers = response.headers();
        console.log(`[RESPONSE] Status: ${response.status()}`);
        console.log(`[RESPONSE] Location: ${headers.location || 'none'}`);
        networkLogs.push({
          type: 'response',
          status: response.status(),
          location: headers.location
        });

        // Try to get response body to check for PHP errors
        try {
          const body = await response.text();
          if (body.includes('Fatal error') || body.includes('SQLSTATE') || body.includes('Warning:')) {
            console.log('\n⚠️ PHP/DATABASE ERROR DETECTED:');
            console.log(body.substring(0, 500));
          }
        } catch (e) {
          // Ignore if can't read body
        }
      }
    });

    console.log('\n========================================');
    console.log('REMEMBER ME SPECIFIC TESTING');
    console.log('========================================\n');

    // Test 1: Normal login (without remember me)
    console.log('TEST 1: NORMAL LOGIN (control test)');
    console.log('=====================================\n');

    await page.goto('https://dalthaus.net/admin/login', { waitUntil: 'networkidle' });

    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');

    // Ensure remember me is NOT checked
    const rememberCheckbox1 = page.locator('input[name="remember_me"]');
    await rememberCheckbox1.uncheck();

    console.log('Submitting normal login...');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);

    const url1 = page.url();
    console.log(`Result: ${url1.includes('dashboard') ? 'SUCCESS' : 'FAILED'} - URL: ${url1}`);

    // Log out if successful
    if (url1.includes('dashboard')) {
      await page.goto('https://dalthaus.net/admin/logout');
      await page.waitForTimeout(1000);
    }

    // Clear session
    await context.clearCookies();

    // Test 2: Login WITH remember me
    console.log('\n\nTEST 2: LOGIN WITH REMEMBER ME');
    console.log('===============================\n');

    await page.goto('https://dalthaus.net/admin/login', { waitUntil: 'networkidle' });

    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');

    // Check remember me checkbox
    const rememberCheckbox2 = page.locator('input[name="remember_me"]');
    await rememberCheckbox2.check();

    const isChecked = await rememberCheckbox2.isChecked();
    console.log(`Remember me checkbox checked: ${isChecked}`);

    console.log('Submitting login with remember me...');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);

    const url2 = page.url();
    console.log(`Result: ${url2.includes('dashboard') ? 'SUCCESS' : 'FAILED'} - URL: ${url2}`);

    // Check for error messages on the page
    const errorMessages = await page.locator('.error, .alert-danger, .text-danger, .text-red-500, .flash').allTextContents();
    if (errorMessages.length > 0) {
      console.log('\nError messages found:');
      errorMessages.forEach(msg => console.log(`  - ${msg.trim()}`));
    }

    // Check cookies
    const cookies = await context.cookies();
    const rememberCookie = cookies.find(c => c.name === 'remember_token' || c.name.includes('remember'));
    const sessionCookie = cookies.find(c => c.name.includes('session') || c.name === 'PHPSESSID');

    console.log(`\nCookies after remember me login:`);
    console.log(`  Session cookie: ${sessionCookie ? 'EXISTS' : 'NOT FOUND'}`);
    console.log(`  Remember cookie: ${rememberCookie ? 'EXISTS' : 'NOT FOUND'}`);

    if (rememberCookie) {
      console.log(`    Name: ${rememberCookie.name}`);
      console.log(`    Value: ${rememberCookie.value.substring(0, 20)}...`);
      console.log(`    Expires: ${new Date(rememberCookie.expires * 1000).toISOString()}`);
      console.log(`    HttpOnly: ${rememberCookie.httpOnly}`);
      console.log(`    Secure: ${rememberCookie.secure}`);
    }

    // Summary
    console.log('\n========================================');
    console.log('SUMMARY');
    console.log('========================================');
    console.log(`Normal login: ${url1.includes('dashboard') ? 'SUCCESS' : 'FAILED'}`);
    console.log(`Remember me login: ${url2.includes('dashboard') ? 'SUCCESS' : 'FAILED'}`);

    if (networkLogs.length > 0) {
      console.log('\nNetwork activity:');
      networkLogs.forEach((log, index) => {
        if (log.type === 'request') {
          console.log(`  Request ${Math.floor(index/2) + 1}: ${log.data}`);
        } else {
          console.log(`  Response ${Math.floor(index/2) + 1}: Status ${log.status}, Location: ${log.location || 'none'}`);
        }
      });
    }

    // Detailed diagnosis
    if (!url2.includes('dashboard')) {
      console.log('\n⚠️ REMEMBER ME LOGIN FAILED - POSSIBLE CAUSES:');
      console.log('1. Database table "remember_tokens" might not exist');
      console.log('2. Database permissions issue for INSERT/SELECT on remember_tokens');
      console.log('3. PHP error in storeRememberToken() method');
      console.log('4. Cookie setting issue (domain, path, security settings)');
      console.log('5. Exception caught in Auth::attempt() method');
    }
  });
});