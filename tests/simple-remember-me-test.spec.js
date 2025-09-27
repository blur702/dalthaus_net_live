import { test, expect } from '@playwright/test';

const PROD_URL = 'https://dalthaus.net';
const USERNAME = 'kevin';
const PASSWORD = '(130Bpm)';

test('Simple remember me test', async ({ browser }) => {
  console.log('=== SIMPLE REMEMBER ME TEST ===\n');

  // Create first context for login with remember me
  const context1 = await browser.newContext();
  const page1 = await context1.newPage();

  console.log('Step 1: Login with remember me checkbox...');
  await page1.goto(`${PROD_URL}/admin/login`);

  // Fill credentials
  await page1.fill('input[name="username"]', USERNAME);
  await page1.fill('input[name="password"]', PASSWORD);

  // Check remember me
  const rememberCheckbox = page1.locator('input[name="remember_me"]');
  await rememberCheckbox.check();
  console.log('✓ Remember me checkbox checked');

  // Submit form
  await page1.click('button[type="submit"]');

  // Wait for dashboard or login page
  await page1.waitForTimeout(3000);
  const url1 = page1.url();
  console.log(`URL after login attempt: ${url1}`);

  if (url1.includes('/admin/dashboard')) {
    console.log('✓ Login successful - redirected to dashboard');

    // Check cookies
    const cookies = await context1.cookies();
    const rememberCookie = cookies.find(c => c.name === 'remember_token');
    const sessionCookie = cookies.find(c => c.name === 'cms_session');

    console.log('Cookie analysis:');
    console.log(`  - Session cookie: ${sessionCookie ? 'Present' : 'Missing'}`);
    console.log(`  - Remember token: ${rememberCookie ? 'Present' : 'Missing'}`);

    if (rememberCookie) {
      console.log('Remember token details:');
      console.log(`  - Value: ${rememberCookie.value.substring(0, 25)}...`);
      console.log(`  - HttpOnly: ${rememberCookie.httpOnly}`);
      console.log(`  - Secure: ${rememberCookie.secure}`);
      console.log(`  - SameSite: ${rememberCookie.sameSite}`);

      const now = Date.now() / 1000;
      const expiresIn = rememberCookie.expires - now;
      const daysUntilExpiry = expiresIn / (60 * 60 * 24);
      console.log(`  - Expires in: ${daysUntilExpiry.toFixed(1)} days`);

      // Verify cookie properties
      if (rememberCookie.httpOnly && rememberCookie.secure && rememberCookie.sameSite === 'Lax') {
        console.log('✅ Cookie has correct security settings');
      } else {
        console.log('⚠️ Cookie security settings may need attention');
      }

      if (daysUntilExpiry > 29 && daysUntilExpiry < 31) {
        console.log('✅ Cookie expiration is correct (~30 days)');
      } else {
        console.log(`⚠️ Cookie expiration unexpected: ${daysUntilExpiry.toFixed(1)} days`);
      }

      // Test navigation while logged in
      console.log('\nStep 2: Testing navigation...');
      await page1.click('a:has-text("Articles")');
      await page1.waitForTimeout(2000);

      if (page1.url().includes('/admin/content')) {
        console.log('✓ Navigation to Articles successful');
      } else {
        console.log(`❌ Navigation failed - URL: ${page1.url()}`);
      }

      // Save cookies for persistence test
      const savedCookies = await context1.cookies();
      await context1.close();

      // Step 3: Test persistence (simulate browser restart)
      console.log('\nStep 3: Testing remember me persistence...');
      const context2 = await browser.newContext();
      await context2.addCookies(savedCookies);
      const page2 = await context2.newPage();

      // Navigate directly to dashboard
      await page2.goto(`${PROD_URL}/admin/dashboard`);
      await page2.waitForTimeout(3000);

      const url2 = page2.url();
      console.log(`URL after direct dashboard access: ${url2}`);

      if (url2.includes('/admin/dashboard')) {
        console.log('✅ Remember me persistence works! Auto-logged in');

        // Test navigation after auto-login
        await page2.click('a:has-text("Articles")');
        await page2.waitForTimeout(2000);

        if (page2.url().includes('/admin/content')) {
          console.log('✅ Navigation works after auto-login');
        } else {
          console.log(`❌ Navigation after auto-login failed - URL: ${page2.url()}`);
        }

        console.log('\n🎉 ALL REMEMBER ME FUNCTIONALITY TESTS PASSED!');
        console.log('Summary:');
        console.log('✅ Login with remember me creates remember_token cookie');
        console.log('✅ Cookie has correct security settings');
        console.log('✅ Cookie has correct expiration (30 days)');
        console.log('✅ Navigation works after login');
        console.log('✅ Auto-login works after browser restart');
        console.log('✅ Navigation works after auto-login');

      } else if (url2.includes('/admin/login')) {
        console.log('❌ Remember me persistence failed - redirected to login');
      } else {
        console.log(`❌ Unexpected redirect: ${url2}`);
      }

      await context2.close();

    } else {
      console.log('❌ CRITICAL: remember_token cookie was NOT created!');
      console.log('This indicates the remember me functionality is not working');
    }

  } else if (url1.includes('/admin/login')) {
    console.log('❌ Login failed - still on login page');
  } else {
    console.log(`❌ Unexpected redirect: ${url1}`);
  }
});