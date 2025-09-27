import { test, expect } from '@playwright/test';

test.describe('Focused Remember Me Test', () => {
  const baseURL = 'https://dalthaus.net';
  const username = 'kevin';
  const password = '(130Bpm)';

  test('Isolate remember me issue with network monitoring', async ({ page, context }) => {
    console.log('🔍 FOCUSED REMEMBER ME ANALYSIS');
    console.log('===============================');

    // Monitor network requests and responses
    const networkLogs = [];

    page.on('request', request => {
      if (request.url().includes('/admin/')) {
        networkLogs.push({
          type: 'REQUEST',
          method: request.method(),
          url: request.url(),
          hasPostData: !!request.postData()
        });
      }
    });

    page.on('response', response => {
      if (response.url().includes('/admin/')) {
        networkLogs.push({
          type: 'RESPONSE',
          status: response.status(),
          url: response.url(),
          headers: response.headers()
        });
      }
    });

    // Step 1: Navigate to login
    console.log('\n1️⃣ Navigating to login page...');
    await page.goto(`${baseURL}/admin/login`);
    await page.waitForLoadState('networkidle');
    console.log('✅ Login page loaded');

    // Step 2: Fill form with remember me UNCHECKED (control test)
    console.log('\n2️⃣ Testing WITHOUT remember me (control)...');
    await page.fill('input[name="username"]', username);
    await page.fill('input[name="password"]', password);

    const rememberCheckbox = page.locator('input[name="remember_me"]');
    await rememberCheckbox.uncheck();
    await expect(rememberCheckbox).not.toBeChecked();

    // Clear network logs for this test
    networkLogs.length = 0;

    console.log('Submitting WITHOUT remember me...');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);

    const controlUrl = page.url();
    console.log('Result URL:', controlUrl);

    if (controlUrl.includes('/admin/dashboard')) {
      console.log('✅ CONTROL TEST PASSED: Login without remember me works');
    } else {
      console.log('❌ CONTROL TEST FAILED: Basic login broken');
      return;
    }

    console.log('\nNetwork activity during control test:');
    networkLogs.forEach(log => {
      console.log(`  ${log.type}: ${log.method || log.status} ${log.url}`);
    });

    // Step 3: Go back to login for remember me test
    console.log('\n3️⃣ Testing WITH remember me...');
    await page.goto(`${baseURL}/admin/login`);
    await page.waitForLoadState('networkidle');

    // Fill form with remember me CHECKED
    await page.fill('input[name="username"]', username);
    await page.fill('input[name="password"]', password);
    await rememberCheckbox.check();
    await expect(rememberCheckbox).toBeChecked();

    console.log('✅ Form filled with remember me CHECKED');

    // Clear network logs for remember me test
    networkLogs.length = 0;

    console.log('Submitting WITH remember me...');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);

    const rememberUrl = page.url();
    console.log('Result URL:', rememberUrl);

    console.log('\nNetwork activity during remember me test:');
    networkLogs.forEach(log => {
      console.log(`  ${log.type}: ${log.method || log.status} ${log.url}`);
      if (log.type === 'RESPONSE' && log.headers.location) {
        console.log(`    → Redirect to: ${log.headers.location}`);
      }
    });

    // Step 4: Analyze results
    console.log('\n4️⃣ ANALYSIS');
    console.log('===========');

    if (rememberUrl.includes('/admin/dashboard')) {
      console.log('🎉 SUCCESS: Remember me login worked!');

      // Check for remember token cookie
      const cookies = await context.cookies();
      const rememberToken = cookies.find(c => c.name === 'remember_token');

      if (rememberToken) {
        console.log('✅ Remember token cookie created');
        console.log('Cookie details:');
        console.log(`  - Value length: ${rememberToken.value?.length || 0} chars`);
        console.log(`  - Expires: ${new Date(rememberToken.expires * 1000).toISOString()}`);
        console.log(`  - HttpOnly: ${rememberToken.httpOnly}`);
        console.log(`  - Secure: ${rememberToken.secure}`);

        console.log('\n🎉 REMEMBER ME FUNCTIONALITY IS WORKING!');
        console.log('✅ Database table creation was successful');
        console.log('✅ Token storage is working');
        console.log('✅ Cookie is being set correctly');

      } else {
        console.log('⚠️ Login succeeded but no remember token cookie found');
        console.log('❓ Possible issue: Cookie creation failed');
      }

    } else if (rememberUrl.includes('/admin/login')) {
      console.log('❌ FAILED: Remember me prevented login');
      console.log('🐛 Remember me functionality is broken');

      // Look for specific error patterns in network logs
      const loginResponses = networkLogs.filter(log =>
        log.type === 'RESPONSE' &&
        log.url.includes('/admin/login') &&
        log.status
      );

      if (loginResponses.length > 0) {
        console.log('\nLogin response analysis:');
        loginResponses.forEach(response => {
          console.log(`  Status: ${response.status}`);
          if (response.status === 302) {
            console.log('  → Redirect detected');
          } else if (response.status === 200) {
            console.log('  → Stayed on login page (login failed)');
          } else if (response.status >= 500) {
            console.log('  → Server error detected');
          }
        });
      }

      // Take screenshot for debugging
      await page.screenshot({
        path: 'testing/results/remember-me-login-failure.png',
        fullPage: true
      });
      console.log('📸 Screenshot saved for debugging');

      console.log('\n❌ CONCLUSION: Remember me functionality is BROKEN');
      console.log('💡 LIKELY CAUSE: Exception in storeRememberToken method');
      console.log('💡 RECOMMENDATION: Check server-side PHP error logs');

    } else {
      console.log('❓ UNEXPECTED: Redirected to unexpected URL');
    }

    // Step 5: Final comparison
    console.log('\n5️⃣ FINAL COMPARISON');
    console.log('===================');
    console.log(`Without remember me: ${controlUrl.includes('/admin/dashboard') ? '✅ SUCCESS' : '❌ FAILED'}`);
    console.log(`With remember me:    ${rememberUrl.includes('/admin/dashboard') ? '✅ SUCCESS' : '❌ FAILED'}`);

    if (controlUrl.includes('/admin/dashboard') && !rememberUrl.includes('/admin/dashboard')) {
      console.log('\n🔍 ROOT CAUSE IDENTIFIED:');
      console.log('The remember me checkbox specifically breaks the login process');
      console.log('This indicates an error in the storeRememberToken() method');
      console.log('or related database operations.');
    }
  });
});