const { test, expect } = require('@playwright/test');

test.describe('Database Diagnostic Check', () => {
  test('Run database diagnostic script', async ({ page }) => {
    console.log('\n========================================');
    console.log('DATABASE DIAGNOSTIC FOR REMEMBER TOKENS');
    console.log('========================================\n');

    // Navigate to the diagnostic script
    try {
      await page.goto('https://dalthaus.net/debug_remember_tokens.php', {
        waitUntil: 'networkidle',
        timeout: 30000
      });

      // Get the page content (should be plain text output)
      const content = await page.textContent('body');
      console.log(content);

      // Take screenshot for reference
      await page.screenshot({
        path: 'test-results/database-diagnostic.png',
        fullPage: true
      });

    } catch (error) {
      console.log('❌ Could not access diagnostic script:', error.message);
      console.log('\nTrying alternative method - check server logs...');

      // Alternative: Try to trigger the remember me login and capture any PHP errors
      await page.goto('https://dalthaus.net/admin/login');

      // Enable console logging to capture any PHP errors
      page.on('console', msg => {
        if (msg.type() === 'error') {
          console.log(`[PHP ERROR]: ${msg.text()}`);
        }
      });

      // Fill login form with remember me
      await page.fill('input[name="username"]', 'kevin');
      await page.fill('input[name="password"]', '(130Bpm)');
      await page.check('input[name="remember_me"]');

      // Submit and wait for any errors
      await page.click('button[type="submit"]');
      await page.waitForTimeout(3000);

      const currentUrl = page.url();
      console.log(`\nLogin result: ${currentUrl}`);

      if (currentUrl.includes('/admin/login')) {
        console.log('❌ Remember me login failed (as expected)');
        console.log('\nTo diagnose further, check server error logs or run the diagnostic script directly on the server.');
      }
    }
  });

  test('Manual database check via error triggering', async ({ page }) => {
    console.log('\n========================================');
    console.log('MANUAL DATABASE ERROR TRIGGERING');
    console.log('========================================\n');

    // Try to trigger specific database errors by manipulating the login process
    await page.goto('https://dalthaus.net/admin/login');

    // First, confirm normal login works
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');

    console.log('Testing normal login first...');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);

    let currentUrl = page.url();
    console.log(`Normal login result: ${currentUrl.includes('dashboard') ? 'SUCCESS' : 'FAILED'}`);

    // Logout if successful
    if (currentUrl.includes('dashboard')) {
      await page.goto('https://dalthaus.net/admin/logout');
      await page.waitForTimeout(1000);
    }

    // Now test remember me with detailed error monitoring
    console.log('\nTesting remember me with error monitoring...');
    await page.goto('https://dalthaus.net/admin/login');

    // Monitor for any network errors or responses
    page.on('response', async response => {
      if (response.url().includes('admin/login') && response.request().method() === 'POST') {
        const status = response.status();
        const headers = response.headers();

        console.log(`Response status: ${status}`);
        console.log(`Redirect location: ${headers.location || 'none'}`);

        // Try to read response body for PHP errors
        try {
          const body = await response.text();

          // Look for specific error patterns
          if (body.includes('SQLSTATE')) {
            console.log('\n🔍 SQL ERROR DETECTED:');
            const sqlMatch = body.match(/SQLSTATE\[.*?\]:.*?(?=\n|$)/);
            if (sqlMatch) {
              console.log(sqlMatch[0]);
            }
          }

          if (body.includes("Table") && body.includes("doesn't exist")) {
            console.log('\n🔍 TABLE MISSING ERROR:');
            const tableMatch = body.match(/Table.*?doesn't exist/);
            if (tableMatch) {
              console.log(tableMatch[0]);
            }
          }

          if (body.includes('INSERT INTO remember_tokens')) {
            console.log('\n🔍 INSERT ERROR DETECTED');
          }

          if (body.includes('DELETE FROM remember_tokens')) {
            console.log('\n🔍 DELETE ERROR DETECTED');
          }

        } catch (e) {
          // Can't read body - likely a redirect
        }
      }
    });

    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.check('input[name="remember_me"]');

    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);

    currentUrl = page.url();
    console.log(`Remember me login result: ${currentUrl.includes('dashboard') ? 'SUCCESS' : 'FAILED'}`);

    // Check for any visible error messages
    const errorMessages = await page.locator('.error, .alert-danger, .text-danger, .text-red-500, .flash').allTextContents();
    if (errorMessages.length > 0) {
      console.log('\nVisible error messages:');
      errorMessages.forEach(msg => console.log(`  - ${msg.trim()}`));
    } else {
      console.log('\nNo visible error messages found');
    }
  });
});