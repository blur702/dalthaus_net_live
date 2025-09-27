import { test, expect } from '@playwright/test';

const PROD_URL = 'https://dalthaus.net';

test('Debug login form structure', async ({ page }) => {
  console.log('Debugging login form structure...');

  await page.goto(`${PROD_URL}/admin/login`);

  // Get the form HTML
  const form = page.locator('form');
  const formHTML = await form.innerHTML();
  console.log('Form HTML:', formHTML);

  // Check form action and method
  const formAction = await form.getAttribute('action');
  const formMethod = await form.getAttribute('method');
  console.log('Form action:', formAction);
  console.log('Form method:', formMethod);

  // Check for CSRF token
  const csrfInput = page.locator('input[name="_token"]');
  const hasCsrfToken = await csrfInput.count() > 0;
  console.log('Has CSRF token:', hasCsrfToken);

  if (hasCsrfToken) {
    const csrfValue = await csrfInput.getAttribute('value');
    console.log('CSRF token value:', csrfValue?.substring(0, 20) + '...');
  }

  // Check all input fields
  const inputs = await page.locator('input').all();
  console.log('\nAll form inputs:');
  for (const input of inputs) {
    const name = await input.getAttribute('name');
    const type = await input.getAttribute('type');
    const value = await input.getAttribute('value');
    console.log(`  - ${name}: type=${type}, value=${value || '(empty)'}`);
  }

  // Try to find any error messages or validation hints
  const pageText = await page.textContent('body');
  console.log('\nPage contains "Invalid":', pageText.includes('Invalid'));
  console.log('Page contains "incorrect":', pageText.includes('incorrect'));
  console.log('Page contains "error":', pageText.includes('error'));

  // Test manual login to see what happens
  await page.fill('input[name="username"]', 'kevin');
  await page.fill('input[name="password"]', '(130Bpm)');
  await page.check('input[name="remember_me"]');

  // Listen for all network requests
  page.on('request', request => {
    console.log(`Request: ${request.method()} ${request.url()}`);
  });

  page.on('response', response => {
    console.log(`Response: ${response.status()} ${response.url()}`);
  });

  console.log('\nSubmitting form...');
  await page.click('button[type="submit"]');

  // Wait for navigation or response
  await page.waitForTimeout(2000);

  console.log('Final URL:', page.url());

  // Check for any error messages now
  const errorMessage = await page.locator('.error, .alert-danger, .invalid-feedback').textContent().catch(() => 'No error message found');
  console.log('Error message after submit:', errorMessage);

  // Check page content after submit
  const afterSubmitText = await page.textContent('body');
  if (afterSubmitText.includes('Dashboard')) {
    console.log('SUCCESS: Found dashboard content');
  } else if (afterSubmitText.includes('Username')) {
    console.log('STILL ON LOGIN: Login form still present');
  }
});