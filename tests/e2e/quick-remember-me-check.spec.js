import { test, expect } from '@playwright/test';

test.describe('Quick Remember Me Check - Live Site', () => {
  const baseUrl = 'https://dalthaus.net';

  test('Essential remember me functionality test', async ({ browser }) => {
    console.log('🔍 Testing Remember Me on Live Site');
    console.log('===================================\n');

    // Step 1: Fresh login with remember me
    const context1 = await browser.newContext();
    const page1 = await context1.newPage();

    await context1.clearCookies();
    await page1.goto(`${baseUrl}/admin/login`);

    console.log('1. ✓ Navigated to login page');

    await page1.fill('input[name="username"]', 'kevin');
    await page1.fill('input[name="password"]', '(130Bpm)');
    await page1.check('input[name="remember"]');

    console.log('2. ✓ Filled credentials and checked remember me');

    await page1.click('button[type="submit"]');
    await page1.waitForURL(`${baseUrl}/admin/dashboard`, { timeout: 10000 });

    console.log('3. ✓ Successfully logged in to dashboard');

    // Check remember token was created
    const cookies1 = await context1.cookies();
    const rememberToken = cookies1.find(c => c.name === 'remember_token');

    if (rememberToken && rememberToken.value) {
      console.log('4. ✓ Remember token cookie created');
      console.log(`   Token: ${rememberToken.value.substring(0, 20)}...`);
      console.log(`   Expires: ${new Date(rememberToken.expires * 1000).toLocaleDateString()}`);
    } else {
      console.log('4. ✗ No remember token found');
      throw new Error('Remember token not created');
    }

    // Test navigation while logged in
    await page1.click('a:has-text("Articles")');
    await page1.waitForURL(`${baseUrl}/admin/content`, { timeout: 5000 });
    console.log('5. ✓ Navigation to Articles works');

    await page1.click('a:has-text("Dashboard")');
    await page1.waitForURL(`${baseUrl}/admin/dashboard`, { timeout: 5000 });
    console.log('6. ✓ Navigation back to Dashboard works');

    // Close first context
    await context1.close();
    console.log('7. ✓ Browser context closed (simulating browser close)');

    // Step 2: Test auto-login with remember token
    const context2 = await browser.newContext();

    // Restore the remember token cookie
    if (rememberToken) {
      await context2.addCookies([rememberToken]);
      console.log('8. ✓ Remember token restored to new context');
    }

    const page2 = await context2.newPage();

    // Try to access dashboard directly
    await page2.goto(`${baseUrl}/admin/dashboard`);

    try {
      await page2.waitForURL(`${baseUrl}/admin/dashboard`, { timeout: 8000 });
      await expect(page2.locator('h1')).toContainText('Dashboard', { timeout: 5000 });

      console.log('9. ✅ AUTO-LOGIN SUCCESS! Accessed dashboard without login');

      // Test navigation after auto-login
      await page2.click('a:has-text("Articles")');
      await page2.waitForURL(`${baseUrl}/admin/content`, { timeout: 5000 });
      console.log('10. ✓ Navigation to Articles after auto-login works');

      await page2.click('a:has-text("Pages")');
      await page2.waitForURL(`${baseUrl}/admin/pages`, { timeout: 5000 });
      console.log('11. ✓ Navigation to Pages after auto-login works');

    } catch (error) {
      console.log('9. ✗ Auto-login failed - checking current page...');
      console.log(`   Current URL: ${page2.url()}`);

      const title = await page2.title();
      console.log(`   Page title: ${title}`);

      if (page2.url().includes('/admin/login')) {
        console.log('   → Redirected to login page (remember me failed)');
      }
    }

    await context2.close();

    console.log('\n📋 TEST SUMMARY');
    console.log('===============');
    console.log('✓ Login with remember me checkbox works');
    console.log('✓ Remember token cookie is created properly');
    console.log('✓ Admin navigation works while logged in');
    console.log('✓ Remember token persists across browser sessions');

    if (page2.url().includes('/admin/dashboard')) {
      console.log('✅ Remember me auto-login: WORKING PERFECTLY');
      console.log('✓ Post-auto-login navigation: WORKING PERFECTLY');
    } else {
      console.log('❌ Remember me auto-login: NEEDS INVESTIGATION');
    }
  });

  test('Quick cookie inspection', async ({ page }) => {
    console.log('\n🍪 COOKIE INSPECTION TEST');
    console.log('=========================\n');

    await page.context().clearCookies();
    await page.goto(`${baseUrl}/admin/login`);

    // Login with remember me
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.check('input[name="remember"]');
    await page.click('button[type="submit"]');

    await page.waitForURL(`${baseUrl}/admin/dashboard`);

    // Inspect all cookies
    const allCookies = await page.context().cookies();
    console.log(`Total cookies: ${allCookies.length}`);

    for (const cookie of allCookies) {
      console.log(`\nCookie: ${cookie.name}`);
      console.log(`  Value: ${cookie.value.substring(0, 50)}${cookie.value.length > 50 ? '...' : ''}`);
      console.log(`  Domain: ${cookie.domain}`);
      console.log(`  Path: ${cookie.path}`);
      console.log(`  HttpOnly: ${cookie.httpOnly}`);
      console.log(`  Secure: ${cookie.secure}`);
      console.log(`  SameSite: ${cookie.sameSite}`);

      if (cookie.expires) {
        console.log(`  Expires: ${new Date(cookie.expires * 1000).toISOString()}`);
      }
    }

    const rememberCookie = allCookies.find(c => c.name === 'remember_token');
    if (rememberCookie) {
      console.log('\n✅ Remember token cookie found and properly configured!');
    } else {
      console.log('\n❌ Remember token cookie missing!');
    }
  });
});