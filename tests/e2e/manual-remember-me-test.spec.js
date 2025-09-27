const { test, expect } = require('@playwright/test');

test.describe('Manual Remember Me Test', () => {
  test('Step-by-step remember me validation', async ({ page, context }) => {
    console.log('=== Manual Remember Me Test ===');

    // Step 1: Clear all cookies and navigate to login
    await context.clearCookies();
    console.log('✅ Cleared all cookies');

    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('domcontentloaded');
    console.log('✅ Navigated to login page');

    // Step 2: Check form elements are present
    const usernameField = page.locator('input[name="username"]');
    const passwordField = page.locator('input[name="password"]');
    const rememberField = page.locator('input[name="remember_me"]');
    const submitButton = page.locator('button[type="submit"]');

    await expect(usernameField).toBeVisible();
    await expect(passwordField).toBeVisible();
    await expect(rememberField).toBeVisible();
    await expect(submitButton).toBeVisible();
    console.log('✅ All form elements are visible');

    // Step 3: Fill credentials and check remember me
    await usernameField.fill('kevin');
    await passwordField.fill('(130Bpm)');
    await rememberField.check();

    const isRememberChecked = await rememberField.isChecked();
    console.log(`✅ Remember me checked: ${isRememberChecked}`);

    // Step 4: Take screenshot before submission
    await page.screenshot({
      path: 'tests/screenshots/manual-test-before-submit.png',
      fullPage: true
    });

    // Step 5: Capture network activity during submission
    let postRequest = null;
    let postResponse = null;

    page.on('request', request => {
      if (request.method() === 'POST' && request.url().includes('login')) {
        postRequest = request;
        console.log(`📤 POST request to: ${request.url()}`);
      }
    });

    page.on('response', response => {
      if (response.request() === postRequest) {
        postResponse = response;
        console.log(`📥 POST response: ${response.status()}`);
      }
    });

    // Step 6: Submit the form
    await submitButton.click();
    console.log('✅ Form submitted');

    // Wait for the response
    await page.waitForTimeout(3000);
    await page.waitForLoadState('networkidle');

    const currentUrl = page.url();
    console.log(`Current URL: ${currentUrl}`);

    // Step 7: Take screenshot after submission
    await page.screenshot({
      path: 'tests/screenshots/manual-test-after-submit.png',
      fullPage: true
    });

    // Step 8: Analyze the result
    if (currentUrl.includes('/admin/dashboard')) {
      console.log('🎉 LOGIN SUCCESSFUL! Dashboard reached.');

      // Check cookies after successful login
      const cookies = await context.cookies();
      const sessionCookie = cookies.find(c => c.name === 'cms_session');
      const rememberCookie = cookies.find(c => c.name === 'remember_token');

      console.log('\n--- Cookie Analysis ---');
      if (sessionCookie) {
        console.log(`✅ Session cookie found: ${sessionCookie.name}`);
        console.log(`   Value length: ${sessionCookie.value.length}`);
        console.log(`   Domain: ${sessionCookie.domain}`);
        console.log(`   Secure: ${sessionCookie.secure}`);
        console.log(`   HttpOnly: ${sessionCookie.httpOnly}`);
      } else {
        console.log('❌ Session cookie NOT found');
      }

      if (rememberCookie) {
        console.log(`✅ Remember token found: ${rememberCookie.name}`);
        console.log(`   Value length: ${rememberCookie.value.length}`);
        console.log(`   Domain: ${rememberCookie.domain}`);
        console.log(`   Secure: ${rememberCookie.secure}`);
        console.log(`   HttpOnly: ${rememberCookie.httpOnly}`);

        if (rememberCookie.expires) {
          const expiryDate = new Date(rememberCookie.expires * 1000);
          const now = new Date();
          const daysDiff = Math.round((expiryDate - now) / (1000 * 60 * 60 * 24));
          console.log(`   Expires: ${expiryDate.toLocaleString()}`);
          console.log(`   Days until expiry: ${daysDiff}`);

          if (daysDiff >= 29 && daysDiff <= 31) {
            console.log('✅ Remember token expiry is correctly set (~30 days)');
          } else {
            console.log(`⚠️ Remember token expiry is ${daysDiff} days (expected ~30)`);
          }
        } else {
          console.log('⚠️ Remember token has no expiry date');
        }
      } else {
        console.log('❌ Remember token NOT found');
      }

      // Test navigation to ensure authentication persists
      console.log('\n--- Testing Navigation ---');

      // Navigate to Articles
      const articlesLink = page.locator('a:has-text("Articles")').first();
      if (await articlesLink.isVisible()) {
        await articlesLink.click();
        await page.waitForLoadState('networkidle');

        const articlesUrl = page.url();
        console.log(`Articles page URL: ${articlesUrl}`);

        if (articlesUrl.includes('/admin/content')) {
          console.log('✅ Successfully navigated to Articles page');

          await page.screenshot({
            path: 'tests/screenshots/manual-test-articles-page.png',
            fullPage: true
          });
        } else if (articlesUrl.includes('/admin/login')) {
          console.log('❌ Redirected to login when accessing Articles');
        }
      }

      // Test direct admin access (simulate remember me functionality)
      console.log('\n--- Testing Remember Me Auto-Login ---');

      // Clear session cookie but keep remember token
      const allCookies = await context.cookies();
      const nonSessionCookies = allCookies.filter(c => c.name !== 'cms_session');

      await context.clearCookies();
      await context.addCookies(nonSessionCookies);
      console.log('✅ Cleared session cookie, kept remember token');

      // Try to access admin page directly
      await page.goto('https://dalthaus.net/admin/dashboard');
      await page.waitForLoadState('networkidle');

      const directAccessUrl = page.url();
      console.log(`Direct access URL: ${directAccessUrl}`);

      await page.screenshot({
        path: 'tests/screenshots/manual-test-direct-access.png',
        fullPage: true
      });

      if (directAccessUrl.includes('/admin/dashboard')) {
        console.log('🎉 REMEMBER ME AUTO-LOGIN SUCCESSFUL!');
      } else if (directAccessUrl.includes('/admin/login')) {
        console.log('❌ Remember me auto-login failed - redirected to login');
      }

    } else if (currentUrl.includes('/admin/login')) {
      console.log('❌ LOGIN FAILED - Still on login page');

      // Check for error messages
      const errorMessages = await page.locator('.alert-danger, .error, .alert-error').allTextContents();
      if (errorMessages.length > 0) {
        console.log('Error messages found:', errorMessages);
      } else {
        console.log('No visible error messages found');
      }

      // Check response details if captured
      if (postResponse) {
        console.log(`POST response status: ${postResponse.status()}`);
        const responseText = await postResponse.text().catch(() => 'Could not read response');
        console.log(`Response text (first 200 chars): ${responseText.substring(0, 200)}`);
      }
    } else {
      console.log(`❓ Unexpected URL: ${currentUrl}`);
    }

    // Final summary
    console.log('\n=== TEST SUMMARY ===');
    console.log(`Login result: ${currentUrl.includes('dashboard') ? 'SUCCESS' : 'FAILED'}`);
    console.log(`Remember me implemented: ${rememberCookie ? 'YES' : 'NO'}`);
    console.log(`Auto-login working: ${directAccessUrl && directAccessUrl.includes('dashboard') ? 'YES' : 'NO'}`);
  });
});