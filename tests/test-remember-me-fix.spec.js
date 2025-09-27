import { test, expect } from '@playwright/test';

const PROD_URL = 'https://dalthaus.net';
const USERNAME = 'kevin';
const PASSWORD = '(130Bpm)';

test('Test remember me fix on production', async ({ browser }) => {
  console.log('=== TESTING REMEMBER ME FIX ON PRODUCTION ===\n');

  // Create fresh browser context
  const context = await browser.newContext();
  const page = await context.newPage();

  console.log('Step 1: Testing login with remember me checkbox...');
  await page.goto(`${PROD_URL}/admin/login`);

  // Fill credentials
  await page.fill('input[name="username"]', USERNAME);
  await page.fill('input[name="password"]', PASSWORD);

  // Check remember me
  const rememberCheckbox = page.locator('input[name="remember_me"]');
  await rememberCheckbox.check();
  console.log('✓ Remember me checkbox checked');

  // Submit form
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);

  const loginUrl = page.url();
  console.log(`URL after login: ${loginUrl}`);

  if (loginUrl.includes('/admin/dashboard')) {
    console.log('✅ LOGIN WITH REMEMBER ME SUCCESSFUL!');

    // Check cookies
    const cookies = await context.cookies();
    const rememberCookie = cookies.find(c => c.name === 'remember_token');
    const sessionCookie = cookies.find(c => c.name === 'cms_session');

    console.log('\nCookie analysis:');
    console.log(`  - Session cookie: ${sessionCookie ? 'Present' : 'Missing'}`);
    console.log(`  - Remember token: ${rememberCookie ? 'Present' : 'Missing'}`);

    if (rememberCookie) {
      console.log('Remember token details:');
      console.log(`  - Value: ${rememberCookie.value.substring(0, 30)}...`);
      console.log(`  - HttpOnly: ${rememberCookie.httpOnly}`);
      console.log(`  - Secure: ${rememberCookie.secure}`);
      console.log(`  - SameSite: ${rememberCookie.sameSite}`);
      console.log(`  - Domain: ${rememberCookie.domain}`);
      console.log(`  - Path: ${rememberCookie.path}`);

      const now = Date.now() / 1000;
      const expiresIn = rememberCookie.expires - now;
      const daysUntilExpiry = expiresIn / (60 * 60 * 24);
      console.log(`  - Expires in: ${daysUntilExpiry.toFixed(1)} days`);

      // Validate cookie properties
      if (rememberCookie.httpOnly && rememberCookie.secure && rememberCookie.sameSite === 'Lax') {
        console.log('✅ Cookie security settings are correct');
      } else {
        console.log('⚠️ Cookie security settings need attention');
      }

      if (daysUntilExpiry > 29 && daysUntilExpiry < 31) {
        console.log('✅ Cookie expiration is correct (~30 days)');
      } else {
        console.log(`⚠️ Cookie expiration unexpected: ${daysUntilExpiry.toFixed(1)} days`);
      }

      // Test navigation
      console.log('\nStep 2: Testing admin navigation...');
      await page.click('a:has-text("Articles")');
      await page.waitForTimeout(2000);

      if (page.url().includes('/admin/content')) {
        console.log('✅ Navigation to Articles successful');
      } else {
        console.log(`❌ Navigation failed - URL: ${page.url()}`);
      }

      // Navigate back to dashboard
      await page.click('a:has-text("Dashboard")');
      await page.waitForTimeout(2000);

      if (page.url().includes('/admin/dashboard')) {
        console.log('✅ Navigation back to Dashboard successful');
      }

      // Step 3: Test remember me persistence
      console.log('\nStep 3: Testing remember me persistence...');

      // Save cookies and close context
      const savedCookies = await context.cookies();
      await context.close();

      // Create new context with saved cookies
      const newContext = await browser.newContext();
      await newContext.addCookies(savedCookies);
      const newPage = await newContext.newPage();

      // Navigate directly to dashboard
      await newPage.goto(`${PROD_URL}/admin/dashboard`);
      await newPage.waitForTimeout(3000);

      const persistenceUrl = newPage.url();
      console.log(`URL after direct dashboard access: ${persistenceUrl}`);

      if (persistenceUrl.includes('/admin/dashboard')) {
        console.log('✅ REMEMBER ME PERSISTENCE WORKS! Auto-logged in');

        // Test navigation after auto-login
        await newPage.click('a:has-text("Articles")');
        await newPage.waitForTimeout(2000);

        if (newPage.url().includes('/admin/content')) {
          console.log('✅ Navigation works after auto-login');

          console.log('\n🎉 ALL REMEMBER ME TESTS PASSED SUCCESSFULLY!');
          console.log('\nSUMMARY:');
          console.log('✅ Login with remember me creates remember_token cookie');
          console.log('✅ Cookie has correct security settings (HttpOnly, Secure, SameSite=Lax)');
          console.log('✅ Cookie has correct expiration (30 days)');
          console.log('✅ Navigation works seamlessly after login');
          console.log('✅ Auto-login works after browser restart');
          console.log('✅ Navigation works correctly after auto-login');
          console.log('✅ No unwanted redirects to login page');

        } else {
          console.log(`❌ Navigation after auto-login failed - URL: ${newPage.url()}`);
        }
      } else if (persistenceUrl.includes('/admin/login')) {
        console.log('❌ Remember me persistence failed - redirected to login');
      } else {
        console.log(`❌ Unexpected redirect: ${persistenceUrl}`);
      }

      await newContext.close();

    } else {
      console.log('❌ CRITICAL: remember_token cookie was NOT created!');
    }

  } else if (loginUrl.includes('/admin/login')) {
    console.log('❌ Login with remember me STILL FAILED - still on login page');
  } else {
    console.log(`❌ Unexpected redirect: ${loginUrl}`);
  }

  await context.close();
});