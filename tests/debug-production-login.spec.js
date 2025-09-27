import { test, expect } from '@playwright/test';

const PROD_URL = 'https://dalthaus.net';
const USERNAME = 'kevin';
const PASSWORD = '(130Bpm)';

test('Debug production login', async ({ page }) => {
  console.log('Debugging production login...');

  // Navigate to login page
  await page.goto(`${PROD_URL}/admin/login`);
  console.log('Current URL after navigation:', page.url());

  // Take screenshot of login page
  await page.screenshot({ path: 'login-page.png', fullPage: true });
  console.log('Login page screenshot saved');

  // Check page content
  const pageContent = await page.content();
  console.log('Page title:', await page.title());

  // Look for form elements
  const usernameInput = page.locator('input[name="username"]');
  const passwordInput = page.locator('input[name="password"]');
  const rememberCheckbox = page.locator('input[name="remember_me"]');
  const submitButton = page.locator('button[type="submit"]');

  console.log('Username input visible:', await usernameInput.isVisible());
  console.log('Password input visible:', await passwordInput.isVisible());
  console.log('Remember checkbox visible:', await rememberCheckbox.isVisible());
  console.log('Submit button visible:', await submitButton.isVisible());

  // Fill form
  if (await usernameInput.isVisible()) {
    await usernameInput.fill(USERNAME);
    console.log('Username filled');
  }

  if (await passwordInput.isVisible()) {
    await passwordInput.fill(PASSWORD);
    console.log('Password filled');
  }

  if (await rememberCheckbox.isVisible()) {
    await rememberCheckbox.check();
    console.log('Remember me checked');
  }

  // Take screenshot before submit
  await page.screenshot({ path: 'before-submit.png', fullPage: true });
  console.log('Before submit screenshot saved');

  // Listen for network requests
  page.on('response', response => {
    if (response.url().includes('/admin/login')) {
      console.log(`Login response: ${response.status()} ${response.statusText()}`);
    }
  });

  // Submit form
  if (await submitButton.isVisible()) {
    await submitButton.click();
    console.log('Submit button clicked');
  }

  // Wait a moment for any response
  await page.waitForTimeout(3000);

  // Check current state
  console.log('URL after submit:', page.url());
  await page.screenshot({ path: 'after-submit.png', fullPage: true });
  console.log('After submit screenshot saved');

  // Check for any error messages
  const errorElements = await page.locator('.error, .alert-danger, .alert-error').all();
  if (errorElements.length > 0) {
    for (const element of errorElements) {
      const text = await element.textContent();
      console.log('Error message:', text);
    }
  }

  // Check if we're on dashboard
  if (page.url().includes('/admin/dashboard')) {
    console.log('SUCCESS: Redirected to dashboard');
  } else {
    console.log('FAILED: Not on dashboard');

    // Check for any form errors or messages
    const bodyText = await page.locator('body').textContent();
    console.log('Page content includes login form:', bodyText.includes('Username'));
    console.log('Page content includes error:', bodyText.includes('error') || bodyText.includes('Error'));
  }
});