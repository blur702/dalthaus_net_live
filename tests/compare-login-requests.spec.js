import { test, expect } from '@playwright/test';

const PROD_URL = 'https://dalthaus.net';
const USERNAME = 'kevin';
const PASSWORD = '(130Bpm)';

test('Compare login requests with and without remember me', async ({ page }) => {
  console.log('=== COMPARING LOGIN REQUESTS ===\n');

  // Track all network requests
  const requests = [];
  const responses = [];

  page.on('request', request => {
    if (request.url().includes('/admin/login') && request.method() === 'POST') {
      requests.push({
        url: request.url(),
        method: request.method(),
        headers: request.headers(),
        postData: request.postData()
      });
    }
  });

  page.on('response', response => {
    if (response.url().includes('/admin/login')) {
      responses.push({
        url: response.url(),
        status: response.status(),
        headers: response.headers()
      });
    }
  });

  // Test 1: Login WITHOUT remember me
  console.log('Test 1: Login WITHOUT remember me...');
  await page.goto(`${PROD_URL}/admin/login`);

  await page.fill('input[name="username"]', USERNAME);
  await page.fill('input[name="password"]', PASSWORD);
  // Ensure remember me is NOT checked
  const rememberCheckbox = page.locator('input[name="remember_me"]');
  await rememberCheckbox.uncheck();

  console.log('Submitting without remember me...');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);

  const url1 = page.url();
  console.log(`Result: ${url1}`);
  console.log(`Success: ${url1.includes('/admin/dashboard')}`);

  // Clear arrays for next test
  const requestsWithoutRemember = [...requests];
  const responsesWithoutRemember = [...responses];
  requests.length = 0;
  responses.length = 0;

  // Test 2: Login WITH remember me
  console.log('\nTest 2: Login WITH remember me...');
  await page.goto(`${PROD_URL}/admin/login`);

  await page.fill('input[name="username"]', USERNAME);
  await page.fill('input[name="password"]', PASSWORD);
  // Check remember me
  await rememberCheckbox.check();
  console.log('Remember me checkbox checked:', await rememberCheckbox.isChecked());

  console.log('Submitting with remember me...');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);

  const url2 = page.url();
  console.log(`Result: ${url2}`);
  console.log(`Success: ${url2.includes('/admin/dashboard')}`);

  const requestsWithRemember = [...requests];
  const responsesWithRemember = [...responses];

  // Compare the requests
  console.log('\n=== REQUEST COMPARISON ===');

  if (requestsWithoutRemember.length > 0) {
    console.log('\nRequest WITHOUT remember me:');
    console.log('POST Data:', requestsWithoutRemember[0].postData);
  }

  if (requestsWithRemember.length > 0) {
    console.log('\nRequest WITH remember me:');
    console.log('POST Data:', requestsWithRemember[0].postData);
  }

  // Compare responses
  console.log('\n=== RESPONSE COMPARISON ===');

  if (responsesWithoutRemember.length > 0) {
    console.log('\nResponse WITHOUT remember me:');
    console.log('Status:', responsesWithoutRemember[0].status);
    console.log('Location header:', responsesWithoutRemember[0].headers.location);
  }

  if (responsesWithRemember.length > 0) {
    console.log('\nResponse WITH remember me:');
    console.log('Status:', responsesWithRemember[0].status);
    console.log('Location header:', responsesWithRemember[0].headers.location);
  }

  // Check if remember me value is being sent
  if (requestsWithRemember.length > 0) {
    const postData = requestsWithRemember[0].postData;
    if (postData) {
      console.log('\nChecking remember me parameter:');
      console.log('POST data includes "remember_me":', postData.includes('remember_me'));
      console.log('POST data includes "remember_me=1":', postData.includes('remember_me=1'));
      console.log('POST data includes "remember_me=on":', postData.includes('remember_me=on'));
    }
  }

  // Take screenshots for comparison
  await page.screenshot({ path: 'login-comparison-final-state.png', fullPage: true });
  console.log('\nScreenshot saved for manual inspection');
});