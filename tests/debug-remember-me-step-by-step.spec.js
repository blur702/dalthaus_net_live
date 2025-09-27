import { test, expect } from '@playwright/test';

const PROD_URL = 'https://dalthaus.net';
const USERNAME = 'kevin';
const PASSWORD = '(130Bpm)';

test('Debug remember me step by step', async ({ page }) => {
  console.log('=== DEBUGGING REMEMBER ME STEP BY STEP ===\n');

  // Step 1: First verify normal login works
  console.log('Step 1: Testing normal login (without remember me)...');
  await page.goto(`${PROD_URL}/admin/login`);
  await page.fill('input[name="username"]', USERNAME);
  await page.fill('input[name="password"]', PASSWORD);

  // Do NOT check remember me for this test
  await page.click('button[type="submit"]');

  try {
    await page.waitForURL(`${PROD_URL}/admin/dashboard`, { timeout: 10000 });
    console.log('✓ Normal login works perfectly');
  } catch (error) {
    console.log('❌ Normal login failed');
    throw error;
  }

  // Check cookies after normal login
  const normalCookies = await page.context().cookies();
  const sessionCookie = normalCookies.find(c => c.name === 'cms_session');
  const rememberCookieNormal = normalCookies.find(c => c.name === 'remember_token');

  console.log('Normal login cookies:');
  console.log(`  - Session cookie: ${sessionCookie ? 'Present' : 'Missing'}`);
  console.log(`  - Remember token: ${rememberCookieNormal ? 'Present (unexpected!)' : 'Missing (expected)'}`);

  // Logout
  console.log('\nLogging out...');
  await page.click('a:has-text("Logout")');
  await page.waitForURL(`${PROD_URL}/admin/login`, { timeout: 5000 });
  console.log('✓ Logout successful');

  // Step 2: Test login WITH remember me
  console.log('\nStep 2: Testing login WITH remember me...');

  // Clear page and start fresh
  await page.goto(`${PROD_URL}/admin/login`);

  // Fill credentials
  await page.fill('input[name="username"]', USERNAME);
  await page.fill('input[name="password"]', PASSWORD);

  // Check the remember me checkbox
  const rememberCheckbox = page.locator('input[name="remember_me"]');
  await rememberCheckbox.check();
  const isChecked = await rememberCheckbox.isChecked();
  console.log(`Remember me checkbox checked: ${isChecked}`);

  if (!isChecked) {
    throw new Error('Failed to check remember me checkbox');
  }

  // Listen for network requests during login
  const loginRequests = [];
  page.on('request', request => {
    if (request.url().includes('/admin/login')) {
      loginRequests.push({
        method: request.method(),
        url: request.url(),
        postData: request.postData()
      });
    }
  });

  const loginResponses = [];
  page.on('response', response => {
    if (response.url().includes('/admin/login')) {
      loginResponses.push({
        status: response.status(),
        url: response.url(),
        headers: response.headers()
      });
    }
  });

  // Submit the form
  console.log('Submitting login form with remember me...');
  await page.click('button[type="submit"]');

  // Wait a moment to see what happens
  await page.waitForTimeout(3000);

  console.log('\nNetwork activity during login:');
  console.log('Requests:', loginRequests);
  console.log('Responses:', loginResponses);

  const currentUrl = page.url();
  console.log(`Current URL after submit: ${currentUrl}`);

  if (currentUrl.includes('/admin/dashboard')) {
    console.log('✓ Login with remember me successful!');

    // Check cookies after remember me login
    const rememberCookies = await page.context().cookies();
    console.log('\nCookies after remember me login:');

    for (const cookie of rememberCookies) {
      console.log(`  - ${cookie.name}: ${cookie.value.substring(0, 30)}...`);
      if (cookie.name === 'remember_token') {
        console.log(`    * HttpOnly: ${cookie.httpOnly}`);
        console.log(`    * Secure: ${cookie.secure}`);
        console.log(`    * SameSite: ${cookie.sameSite}`);

        const now = Date.now() / 1000;
        const expiresIn = cookie.expires - now;
        const daysUntilExpiry = expiresIn / (60 * 60 * 24);
        console.log(`    * Expires in: ${daysUntilExpiry.toFixed(1)} days`);
      }
    }

    const rememberToken = rememberCookies.find(c => c.name === 'remember_token');
    if (rememberToken) {
      console.log('✅ remember_token cookie successfully created!');

      // Test navigation
      console.log('\nTesting navigation with remember me...');
      await page.click('a:has-text("Articles")');

      try {
        await page.waitForURL(`${PROD_URL}/admin/content?type=article`, { timeout: 5000 });
        console.log('✓ Navigation to Articles successful');
      } catch (error) {
        console.log('❌ Navigation failed:', error.message);
        console.log('Current URL:', page.url());
      }

    } else {
      console.log('❌ remember_token cookie was NOT created');
    }

  } else if (currentUrl.includes('/admin/login')) {
    console.log('❌ Still on login page - login with remember me failed');

    // Check for error messages
    const pageText = await page.textContent('body');
    console.log('Page contains error indicators:');
    console.log(`  - "invalid": ${pageText.toLowerCase().includes('invalid')}`);
    console.log(`  - "incorrect": ${pageText.toLowerCase().includes('incorrect')}`);
    console.log(`  - "failed": ${pageText.toLowerCase().includes('failed')}`);

    // Take screenshot for debugging
    await page.screenshot({ path: 'remember-me-login-failed.png', fullPage: true });

  } else {
    console.log(`❓ Unexpected redirect to: ${currentUrl}`);
  }
});