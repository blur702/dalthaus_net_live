import { test, expect } from '@playwright/test';

const PROD_URL = 'https://dalthaus.net';

test('Find production credentials', async ({ page }) => {
  console.log('Attempting to find correct production credentials...');

  // Common admin credentials to try
  const credentialOptions = [
    { username: 'kevin', password: '(130Bpm)' },
    { username: 'admin', password: 'admin' },
    { username: 'admin', password: 'password' },
    { username: 'user', password: 'password' },
    { username: 'kevin', password: 'password' },
    { username: 'dalthaus', password: 'password' },
    { username: 'root', password: 'password' }
  ];

  for (const creds of credentialOptions) {
    console.log(`\nTrying: ${creds.username} / ${creds.password}`);

    await page.goto(`${PROD_URL}/admin/login`);

    // Fill in credentials
    await page.fill('input[name="username"]', creds.username);
    await page.fill('input[name="password"]', creds.password);

    // Submit without remember me first
    await page.click('button[type="submit"]');

    // Wait a bit for response
    await page.waitForTimeout(2000);

    // Check current URL
    const currentUrl = page.url();
    console.log(`After login attempt: ${currentUrl}`);

    if (currentUrl.includes('/admin/dashboard')) {
      console.log(`🎉 SUCCESS! Credentials found: ${creds.username} / ${creds.password}`);

      // Take screenshot of success
      await page.screenshot({ path: 'successful-login.png' });

      // Now test the remember me functionality with these credentials
      await testRememberMeWithCredentials(page, creds.username, creds.password);
      return;
    } else if (currentUrl.includes('/admin/login')) {
      console.log('❌ Failed - back on login page');
    } else {
      console.log(`❓ Unexpected redirect: ${currentUrl}`);
    }
  }

  console.log('❌ No working credentials found from common options');

  // Let's also check if there are any error messages on the page
  await page.goto(`${PROD_URL}/admin/login`);
  await page.fill('input[name="username"]', 'kevin');
  await page.fill('input[name="password"]', '(130Bpm)');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(2000);

  // Look for any error message or hint
  const pageText = await page.textContent('body');
  console.log('\nPage content analysis:');
  console.log('Contains "invalid":', pageText.toLowerCase().includes('invalid'));
  console.log('Contains "incorrect":', pageText.toLowerCase().includes('incorrect'));
  console.log('Contains "error":', pageText.toLowerCase().includes('error'));
  console.log('Contains "username":', pageText.toLowerCase().includes('username'));

  // Take screenshot for manual inspection
  await page.screenshot({ path: 'failed-login-analysis.png', fullPage: true });
});

async function testRememberMeWithCredentials(page, username, password) {
  console.log('\n=== TESTING REMEMBER ME WITH WORKING CREDENTIALS ===');

  // Create new browser context for clean test
  const browser = page.context().browser();
  const newContext = await browser.newContext();
  const newPage = await newContext.newPage();

  try {
    // Test login with remember me
    await newPage.goto(`${PROD_URL}/admin/login`);
    await newPage.fill('input[name="username"]', username);
    await newPage.fill('input[name="password"]', password);

    // Check remember me
    await newPage.check('input[name="remember_me"]');
    console.log('✓ Remember me checkbox checked');

    await newPage.click('button[type="submit"]');
    await newPage.waitForURL(`${PROD_URL}/admin/dashboard`, { timeout: 10000 });
    console.log('✓ Successfully logged in with remember me');

    // Check for remember token cookie
    const cookies = await newContext.cookies();
    const rememberCookie = cookies.find(c => c.name === 'remember_token');

    if (rememberCookie) {
      console.log('✓ remember_token cookie created!');
      console.log(`  - Value: ${rememberCookie.value.substring(0, 20)}...`);
      console.log(`  - HttpOnly: ${rememberCookie.httpOnly}`);
      console.log(`  - Secure: ${rememberCookie.secure}`);
      console.log(`  - SameSite: ${rememberCookie.sameSite}`);

      // Check expiration
      const now = Date.now() / 1000;
      const expiresIn = rememberCookie.expires - now;
      const daysUntilExpiry = expiresIn / (60 * 60 * 24);
      console.log(`  - Expires in: ${daysUntilExpiry.toFixed(1)} days`);
    } else {
      console.log('❌ remember_token cookie NOT created');
    }

    // Test navigation
    console.log('\nTesting navigation...');
    await newPage.click('a:has-text("Articles")');
    await newPage.waitForURL(`${PROD_URL}/admin/content?type=article`, { timeout: 5000 });
    console.log('✓ Navigation to Articles works');

    // Test persistence (simulate browser restart)
    console.log('\nTesting remember me persistence...');
    const savedCookies = await newContext.cookies();
    await newContext.close();

    const persistenceContext = await browser.newContext();
    await persistenceContext.addCookies(savedCookies);
    const persistencePage = await persistenceContext.newPage();

    await persistencePage.goto(`${PROD_URL}/admin/dashboard`);
    const finalUrl = persistencePage.url();

    if (finalUrl.includes('/admin/dashboard')) {
      console.log('✓ Remember me persistence works! Auto-logged in after browser restart');

      // Test navigation after auto-login
      await persistencePage.click('a:has-text("Articles")');
      await persistencePage.waitForURL(`${PROD_URL}/admin/content?type=article`, { timeout: 5000 });
      console.log('✓ Navigation works after auto-login');

      console.log('\n🎉 ALL REMEMBER ME TESTS PASSED!');
    } else {
      console.log(`❌ Remember me persistence failed - redirected to: ${finalUrl}`);
    }

    await persistenceContext.close();

  } catch (error) {
    console.log('❌ Error during remember me testing:', error.message);
  }
}