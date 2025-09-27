import { test, expect } from '@playwright/test';

test.describe('Login Debug', () => {
  const baseURL = 'https://dalthaus.net';
  const username = 'kevin';
  const password = '(130Bpm)';

  test('Debug login process step by step', async ({ page }) => {
    console.log('Starting login debug test...');

    // Navigate to login page
    console.log('1. Navigating to login page...');
    await page.goto(`${baseURL}/admin/login`);
    await page.waitForLoadState('networkidle');

    console.log('Current URL:', page.url());
    console.log('Page title:', await page.title());

    // Check if form elements exist
    console.log('\n2. Checking form elements...');
    const usernameField = page.locator('input[name="username"]');
    const passwordField = page.locator('input[name="password"]');
    const submitButton = page.locator('button[type="submit"]');
    const rememberCheckbox = page.locator('input[name="remember_me"]');

    await expect(usernameField).toBeVisible();
    await expect(passwordField).toBeVisible();
    await expect(submitButton).toBeVisible();
    await expect(rememberCheckbox).toBeVisible();
    console.log('✓ All form elements are visible');

    // Fill credentials
    console.log('\n3. Filling credentials...');
    await usernameField.fill(username);
    await passwordField.fill(password);

    console.log('Username value:', await usernameField.inputValue());
    console.log('Password field filled (length):', (await passwordField.inputValue()).length);

    // Check for CSRF token
    console.log('\n4. Checking for CSRF token...');
    const csrfToken = page.locator('input[name="_token"]');
    if (await csrfToken.count() > 0) {
      console.log('✓ CSRF token found');
      console.log('CSRF token value:', await csrfToken.inputValue());
    } else {
      console.log('✗ No CSRF token found');
    }

    // Check remember me checkbox
    console.log('\n5. Testing remember me checkbox...');
    await rememberCheckbox.check();
    console.log('Remember me checked:', await rememberCheckbox.isChecked());

    // Monitor network requests
    console.log('\n6. Setting up network monitoring...');
    page.on('response', response => {
      if (response.url().includes('/admin/login')) {
        console.log(`Login response: ${response.status()} ${response.statusText()}`);
      }
    });

    // Submit form
    console.log('\n7. Submitting form...');
    await submitButton.click();

    // Wait a moment and check what happened
    await page.waitForTimeout(3000);
    console.log('URL after submit:', page.url());

    // Check for error messages
    const errorMessages = await page.locator('.error, .alert-danger, .text-red-500').all();
    if (errorMessages.length > 0) {
      console.log('\n8. Error messages found:');
      for (const error of errorMessages) {
        const text = await error.textContent();
        console.log('Error:', text);
      }
    } else {
      console.log('\n8. No visible error messages found');
    }

    // Check if we're still on login page or redirected
    if (page.url().includes('/admin/login')) {
      console.log('\n❌ Still on login page - login failed');

      // Take screenshot for debugging
      await page.screenshot({ path: 'testing/results/login-debug-failure.png', fullPage: true });
      console.log('Screenshot saved to testing/results/login-debug-failure.png');

      // Get page content for debugging
      const pageContent = await page.content();
      console.log('Page HTML length:', pageContent.length);

    } else if (page.url().includes('/admin/dashboard')) {
      console.log('\n✅ Successfully redirected to dashboard');
      console.log('Login successful!');
    } else {
      console.log('\n❓ Redirected to unexpected URL:', page.url());
    }
  });

  test('Test basic credentials without remember me', async ({ page }) => {
    console.log('\nTesting basic login without remember me...');

    await page.goto(`${baseURL}/admin/login`);
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="username"]', username);
    await page.fill('input[name="password"]', password);

    // Don't check remember me
    const rememberCheckbox = page.locator('input[name="remember_me"]');
    await rememberCheckbox.uncheck();

    await page.click('button[type="submit"]');

    // Wait for potential redirect
    await page.waitForTimeout(5000);

    console.log('Final URL:', page.url());

    if (page.url().includes('/admin/dashboard')) {
      console.log('✅ Basic login works - remember me functionality can be tested');
    } else {
      console.log('❌ Basic login failed - need to fix authentication first');
    }
  });
});