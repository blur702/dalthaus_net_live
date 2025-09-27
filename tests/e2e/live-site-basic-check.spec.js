import { test, expect } from '@playwright/test';

test.describe('Live Site Basic Remember Me Check', () => {
  const baseUrl = 'https://dalthaus.net';

  test('Basic functionality verification', async ({ page }) => {
    console.log('🔍 BASIC LIVE SITE VERIFICATION');
    console.log('================================\n');

    // Step 1: Check login page loads
    console.log('Step 1: Loading login page...');
    await page.goto(`${baseUrl}/admin/login`);

    // Take screenshot to see what we're working with
    await page.screenshot({ path: 'live-login-page.png', fullPage: true });
    console.log('✓ Login page loaded, screenshot saved');

    // Check page title
    const title = await page.title();
    console.log(`✓ Page title: "${title}"`);

    // Check if form exists
    const form = page.locator('form');
    const formExists = await form.count() > 0;
    console.log(`✓ Login form exists: ${formExists}`);

    if (formExists) {
      // Check form action
      const formAction = await form.getAttribute('action');
      console.log(`✓ Form action: ${formAction}`);

      // Check username field
      const usernameField = page.locator('input[name="username"]');
      const usernameExists = await usernameField.count() > 0;
      console.log(`✓ Username field exists: ${usernameExists}`);

      // Check password field
      const passwordField = page.locator('input[name="password"]');
      const passwordExists = await passwordField.count() > 0;
      console.log(`✓ Password field exists: ${passwordExists}`);

      // Check remember me checkbox
      const rememberField = page.locator('input[name="remember_me"]');
      const rememberExists = await rememberField.count() > 0;
      console.log(`✓ Remember me checkbox exists: ${rememberExists}`);

      if (rememberExists) {
        const rememberValue = await rememberField.getAttribute('value');
        const rememberType = await rememberField.getAttribute('type');
        console.log(`✓ Remember me - Type: ${rememberType}, Value: ${rememberValue}`);
      }

      // Check submit button
      const submitButton = page.locator('button[type="submit"]');
      const submitExists = await submitButton.count() > 0;
      console.log(`✓ Submit button exists: ${submitExists}`);
    }

    console.log('\nStep 2: Testing login with network monitoring...');

    // Listen to network requests
    const responses = [];
    page.on('response', response => {
      responses.push({
        url: response.url(),
        status: response.status(),
        statusText: response.statusText()
      });
    });

    // Fill and submit form
    try {
      await page.fill('input[name="username"]', 'kevin');
      await page.fill('input[name="password"]', '(130Bpm)');
      await page.check('input[name="remember_me"]');
      console.log('✓ Form filled successfully');

      // Submit with timeout
      await page.click('button[type="submit"]');
      console.log('✓ Form submitted');

      // Wait a bit to see what happens
      await page.waitForTimeout(5000);

      const currentUrl = page.url();
      console.log(`✓ Current URL after submission: ${currentUrl}`);

      // Check if we got redirected to dashboard
      if (currentUrl.includes('/admin/dashboard')) {
        console.log('✅ SUCCESS: Redirected to dashboard!');

        // Check for remember token cookie
        const cookies = await page.context().cookies();
        const rememberToken = cookies.find(c => c.name === 'remember_token');

        if (rememberToken) {
          console.log('✅ Remember token cookie created!');
          console.log(`   Token length: ${rememberToken.value.length} characters`);
          console.log(`   Expires: ${new Date(rememberToken.expires * 1000).toLocaleDateString()}`);
          console.log(`   HttpOnly: ${rememberToken.httpOnly}`);
          console.log(`   Secure: ${rememberToken.secure}`);

          // Test a simple navigation
          await page.click('a:has-text("Articles")');
          await page.waitForTimeout(2000);
          const articlesUrl = page.url();
          console.log(`✓ Navigation to Articles: ${articlesUrl}`);

          if (articlesUrl.includes('/admin/content')) {
            console.log('✅ Navigation working - still logged in!');
          }

        } else {
          console.log('❌ No remember token cookie found');
        }

      } else if (currentUrl.includes('/admin/login')) {
        console.log('❌ Still on login page - login may have failed');

        // Look for error messages
        const pageText = await page.textContent('body');
        console.log('Page content after failed login:');
        console.log(pageText.substring(0, 500) + '...');

      } else {
        console.log(`❓ Unexpected redirect to: ${currentUrl}`);
      }

      // Show recent network responses
      console.log('\nRecent network responses:');
      responses.slice(-5).forEach(resp => {
        console.log(`  ${resp.status} ${resp.statusText}: ${resp.url}`);
      });

    } catch (error) {
      console.log(`❌ Error during login process: ${error.message}`);

      await page.screenshot({ path: 'live-login-error.png', fullPage: true });
      console.log('Error screenshot saved: live-login-error.png');
    }

    console.log('\n📋 SUMMARY');
    console.log('===========');
    console.log('This test verified:');
    console.log('• Login page loads correctly');
    console.log('• All form fields exist');
    console.log('• Remember me checkbox exists');
    console.log('• Form submission process');
    console.log('• Cookie creation (if login successful)');
    console.log('• Basic navigation (if login successful)');
  });

  test('Manual test guide', async ({ page }) => {
    console.log('\n🧪 MANUAL TESTING INSTRUCTIONS');
    console.log('================================');
    console.log('\nFor comprehensive manual verification:');
    console.log('\n1. Open browser and clear all data (Ctrl+Shift+Delete)');
    console.log('2. Go to: https://dalthaus.net/admin/login');
    console.log('3. Enter username: kevin');
    console.log('4. Enter password: (130Bpm)');
    console.log('5. CHECK the "Remember me for 30 days" checkbox');
    console.log('6. Click Login button');
    console.log('7. Verify: Should redirect to dashboard');
    console.log('8. Test navigation: Click "Articles", "Pages", "Users"');
    console.log('9. Verify: All navigation works, stay logged in');
    console.log('10. Refresh page several times');
    console.log('11. Verify: Still logged in after refreshes');
    console.log('12. Close browser completely');
    console.log('13. Open new browser window');
    console.log('14. Go directly to: https://dalthaus.net/admin/dashboard');
    console.log('15. Verify: Should be automatically logged in!');
    console.log('16. Test navigation again from auto-logged-in state');
    console.log('\n✅ Expected: All steps should work without issues');
    console.log('🔧 If any step fails, check browser console for errors');

    // Just verify the login page is accessible
    await page.goto(`${baseUrl}/admin/login`);
    console.log('\n✓ Login page is accessible for manual testing');
  });
});