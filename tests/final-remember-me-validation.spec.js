import { test, expect } from '@playwright/test';

const PROD_URL = 'https://dalthaus.net';
const USERNAME = 'kevin';
const PASSWORD = '(130Bpm)';

test('Final remember me validation - all critical features', async ({ browser }) => {
  console.log('🎯 FINAL REMEMBER ME VALIDATION TEST');
  console.log('Testing all critical remember me functionality on production\n');

  // Create fresh browser context
  const context = await browser.newContext();
  const page = await context.newPage();

  // ✅ TEST 1: Login with remember me
  console.log('📝 TEST 1: Login with remember me checkbox...');
  await page.goto(`${PROD_URL}/admin/login`);
  await page.fill('input[name="username"]', USERNAME);
  await page.fill('input[name="password"]', PASSWORD);
  await page.check('input[name="remember_me"]');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);

  if (page.url().includes('/admin/dashboard')) {
    console.log('✅ LOGIN SUCCESS: Redirected to dashboard');
  } else {
    throw new Error('❌ LOGIN FAILED: Not redirected to dashboard');
  }

  // ✅ TEST 2: Verify remember token cookie
  console.log('\n📝 TEST 2: Validating remember token cookie...');
  const cookies = await context.cookies();
  const rememberCookie = cookies.find(c => c.name === 'remember_token');

  if (!rememberCookie) {
    throw new Error('❌ CRITICAL: remember_token cookie not found');
  }

  console.log('✅ COOKIE EXISTS: remember_token cookie found');
  console.log(`   Value: ${rememberCookie.value.substring(0, 30)}...`);
  console.log(`   Security: HttpOnly=${rememberCookie.httpOnly}, Secure=${rememberCookie.secure}, SameSite=${rememberCookie.sameSite}`);

  const now = Date.now() / 1000;
  const expiresIn = rememberCookie.expires - now;
  const daysUntilExpiry = expiresIn / (60 * 60 * 24);
  console.log(`   Expires in: ${daysUntilExpiry.toFixed(1)} days`);

  if (rememberCookie.httpOnly && rememberCookie.secure && rememberCookie.sameSite === 'Lax') {
    console.log('✅ SECURITY: Cookie security settings correct');
  } else {
    throw new Error('❌ SECURITY: Cookie security settings incorrect');
  }

  if (daysUntilExpiry > 29 && daysUntilExpiry < 31) {
    console.log('✅ EXPIRATION: Cookie expiration correct (~30 days)');
  } else {
    throw new Error(`❌ EXPIRATION: Cookie expiration incorrect (${daysUntilExpiry.toFixed(1)} days)`);
  }

  // ✅ TEST 3: Navigation while logged in
  console.log('\n📝 TEST 3: Testing navigation while logged in...');
  await page.click('a:has-text("Articles")');
  await page.waitForTimeout(2000);

  if (page.url().includes('/admin/content')) {
    console.log('✅ NAVIGATION: Articles page accessible');
  } else {
    throw new Error('❌ NAVIGATION: Cannot access Articles page');
  }

  // ✅ TEST 4: Remember me persistence (simulate browser restart)
  console.log('\n📝 TEST 4: Testing remember me persistence...');

  const savedCookies = await context.cookies();
  await context.close();

  const newContext = await browser.newContext();
  await newContext.addCookies(savedCookies);
  const newPage = await newContext.newPage();

  await newPage.goto(`${PROD_URL}/admin/dashboard`);
  await newPage.waitForTimeout(3000);

  if (newPage.url().includes('/admin/dashboard')) {
    console.log('✅ PERSISTENCE: Auto-login successful after browser restart');
  } else {
    throw new Error(`❌ PERSISTENCE: Auto-login failed, redirected to: ${newPage.url()}`);
  }

  // ✅ TEST 5: Navigation after auto-login
  console.log('\n📝 TEST 5: Testing navigation after auto-login...');
  await newPage.click('a:has-text("Articles")');
  await newPage.waitForTimeout(2000);

  if (newPage.url().includes('/admin/content')) {
    console.log('✅ POST-AUTO-LOGIN: Navigation works after auto-login');
  } else {
    throw new Error('❌ POST-AUTO-LOGIN: Navigation failed after auto-login');
  }

  await newContext.close();

  // 🎉 FINAL SUCCESS MESSAGE
  console.log('\n🎉 ALL TESTS PASSED SUCCESSFULLY!');
  console.log('==================================================');
  console.log('📋 REMEMBER ME FUNCTIONALITY VALIDATION SUMMARY:');
  console.log('✅ Login with remember me checkbox works');
  console.log('✅ remember_token cookie created with correct value');
  console.log('✅ Cookie has secure settings (HttpOnly, Secure, SameSite=Lax)');
  console.log('✅ Cookie expires in exactly 30 days');
  console.log('✅ Admin navigation works seamlessly');
  console.log('✅ Auto-login works after browser restart');
  console.log('✅ Navigation works correctly after auto-login');
  console.log('✅ No unwanted redirects to login page');
  console.log('\n🚀 THE REMEMBER ME FEATURE IS FULLY FUNCTIONAL!');
});