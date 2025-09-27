const { test, expect } = require('@playwright/test');

test.describe('Debug Live Site Login', () => {
  test('debug login process step by step', async ({ page }) => {
    console.log('Starting login debug test...\n');

    // Navigate to login page
    console.log('Navigating to login page...');
    await page.goto('https://dalthaus.net/admin/login');
    console.log('Current URL after navigation:', page.url());

    // Check if login form exists
    console.log('\nChecking login form elements...');
    const usernameField = page.locator('input[name="username"]');
    const passwordField = page.locator('input[name="password"]');
    const rememberCheckbox = page.locator('input[name="remember_me"]');
    const submitButton = page.locator('button[type="submit"]');

    console.log('Username field exists:', await usernameField.isVisible());
    console.log('Password field exists:', await passwordField.isVisible());
    console.log('Remember me checkbox exists:', await rememberCheckbox.isVisible());
    console.log('Submit button exists:', await submitButton.isVisible());

    // Fill form
    console.log('\nFilling login form...');
    await usernameField.fill('kevin');
    await passwordField.fill('(130Bpm)');
    await rememberCheckbox.check();

    // Check form values
    console.log('Username value:', await usernameField.inputValue());
    console.log('Password value:', await passwordField.inputValue());
    console.log('Remember me checked:', await rememberCheckbox.isChecked());

    // Monitor network and console
    const responses = [];
    page.on('response', response => {
      responses.push({
        url: response.url(),
        status: response.status(),
        statusText: response.statusText()
      });
    });

    const consoleMessages = [];
    page.on('console', msg => {
      consoleMessages.push(`${msg.type()}: ${msg.text()}`);
    });

    // Submit form
    console.log('\nSubmitting form...');
    await submitButton.click();

    // Wait a bit and check what happened
    await page.waitForTimeout(3000);
    console.log('\nAfter form submission:');
    console.log('Current URL:', page.url());

    // Check for error messages
    const errorMessages = await page.locator('.error, .alert, [class*="error"], [class*="alert"]').allTextContents();
    if (errorMessages.length > 0) {
      console.log('Error messages found:', errorMessages);
    } else {
      console.log('No error messages found');
    }

    // Show network responses
    console.log('\nNetwork responses:');
    responses.forEach(response => {
      if (response.url.includes('admin') || response.url.includes('login')) {
        console.log(`  ${response.status} ${response.statusText}: ${response.url}`);
      }
    });

    // Show console messages
    if (consoleMessages.length > 0) {
      console.log('\nConsole messages:');
      consoleMessages.forEach(msg => console.log(`  ${msg}`));
    }

    // Check page content for clues
    const pageTitle = await page.title();
    console.log('Page title:', pageTitle);

    const headings = await page.locator('h1, h2, h3').allTextContents();
    console.log('Page headings:', headings);

    // Take a screenshot for debugging
    await page.screenshot({ path: 'debug-login-state.png', fullPage: true });
    console.log('\nScreenshot saved as debug-login-state.png');
  });
});