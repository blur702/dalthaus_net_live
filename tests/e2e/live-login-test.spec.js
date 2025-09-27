import { test, expect } from '@playwright/test';

test.describe('Live Site Login Test with Remember Me', () => {
  test('should test login with remember me checkbox on dalthaus.net', async ({ page }) => {
    // Enable console logging
    page.on('console', msg => {
      console.log(`Console ${msg.type()}: ${msg.text()}`);
    });

    // Capture any page errors
    page.on('pageerror', error => {
      console.error('Page error:', error.message);
    });

    // Monitor network requests
    page.on('requestfailed', request => {
      console.error(`Request failed: ${request.url()} - ${request.failure()?.errorText}`);
    });

    // Monitor responses for errors
    page.on('response', response => {
      if (response.status() >= 400) {
        console.error(`HTTP ${response.status()} error for: ${response.url()}`);
      }
    });

    console.log('Step 1: Navigating to login page...');
    await page.goto('https://dalthaus.net/admin/login', {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    // Take screenshot of login page
    await page.screenshot({
      path: 'tests/screenshots/01-login-page.png',
      fullPage: true
    });
    console.log('Screenshot taken: Login page loaded');

    // Check if login form exists
    const loginForm = await page.locator('form').first();
    const formExists = await loginForm.isVisible();
    console.log(`Login form exists: ${formExists}`);

    console.log('Step 2: Filling in username...');
    const usernameField = page.locator('input[name="username"], input[type="text"]').first();
    await usernameField.waitFor({ state: 'visible', timeout: 5000 });
    await usernameField.fill('kevin');
    console.log('Username filled: kevin');

    console.log('Step 3: Filling in password...');
    const passwordField = page.locator('input[name="password"], input[type="password"]').first();
    await passwordField.waitFor({ state: 'visible', timeout: 5000 });
    await passwordField.fill('(130Bpm)');
    console.log('Password filled: (130Bpm)');

    console.log('Step 4: Checking the Remember Me checkbox...');
    // Try multiple selectors for the remember me checkbox
    const rememberSelectors = [
      'input[name="remember"]',
      'input[name="remember_me"]',
      'input[type="checkbox"]',
      '#remember',
      '#remember_me'
    ];

    let rememberCheckbox = null;
    for (const selector of rememberSelectors) {
      const element = page.locator(selector).first();
      if (await element.count() > 0) {
        rememberCheckbox = element;
        console.log(`Found remember checkbox with selector: ${selector}`);
        break;
      }
    }

    if (rememberCheckbox) {
      await rememberCheckbox.check();
      const isChecked = await rememberCheckbox.isChecked();
      console.log(`Remember Me checkbox checked: ${isChecked}`);
    } else {
      console.log('Warning: Remember Me checkbox not found');
    }

    // Take screenshot before submitting
    await page.screenshot({
      path: 'tests/screenshots/02-form-filled.png',
      fullPage: true
    });
    console.log('Screenshot taken: Form filled with credentials');

    console.log('Step 5: Submitting the form...');

    // Prepare to capture network activity
    const responsePromise = page.waitForResponse(response =>
      response.url().includes('/admin/login') && response.request().method() === 'POST',
      { timeout: 10000 }
    ).catch(e => {
      console.log('No POST response captured (might be using GET or different URL)');
      return null;
    });

    // Try multiple ways to submit the form
    const submitButton = await page.locator('button[type="submit"], input[type="submit"], button:has-text("Login"), button:has-text("Sign In")').first();

    if (await submitButton.count() > 0) {
      console.log('Found submit button, clicking...');
      await submitButton.click();
    } else {
      console.log('No submit button found, trying form submit...');
      await loginForm.press('Enter');
    }

    // Wait for navigation or response
    try {
      await Promise.race([
        page.waitForURL('**/admin/dashboard', { timeout: 10000 }),
        page.waitForURL('**/admin', { timeout: 10000 }),
        page.waitForURL('**/dashboard', { timeout: 10000 }),
        page.waitForSelector('.error, .alert, .message', { timeout: 5000 })
      ]);
    } catch (e) {
      console.log('Navigation/error wait timed out, checking current state...');
    }

    // Check the response if we got one
    const response = await responsePromise;
    if (response) {
      console.log(`Login POST response status: ${response.status()}`);
      if (response.status() >= 400) {
        console.error(`Login failed with HTTP ${response.status()}`);
      }
    }

    // Wait a moment for any redirects or error messages
    await page.waitForTimeout(2000);

    // Get final URL
    const finalUrl = page.url();
    console.log(`Final URL after login attempt: ${finalUrl}`);

    // Take screenshot after submission
    await page.screenshot({
      path: 'tests/screenshots/03-after-submit.png',
      fullPage: true
    });
    console.log('Screenshot taken: After form submission');

    // Check for error messages
    const errorSelectors = [
      '.error',
      '.alert-danger',
      '.alert-error',
      '.message.error',
      '[role="alert"]',
      '.flash-message',
      '.notification.is-danger'
    ];

    let errorFound = false;
    for (const selector of errorSelectors) {
      const errorElement = page.locator(selector).first();
      if (await errorElement.count() > 0) {
        const errorText = await errorElement.textContent();
        console.error(`Error message found: ${errorText}`);
        errorFound = true;
      }
    }

    // Check if we're on the dashboard (successful login)
    const isDashboard = finalUrl.includes('/dashboard') || finalUrl.includes('/admin') && !finalUrl.includes('/login');
    const isStillOnLogin = finalUrl.includes('/login');

    console.log('=== Login Test Results ===');
    console.log(`Login successful: ${isDashboard}`);
    console.log(`Still on login page: ${isStillOnLogin}`);
    console.log(`Error messages found: ${errorFound}`);

    // Check for cookies (including remember me cookie)
    const cookies = await page.context().cookies();
    const sessionCookie = cookies.find(c => c.name.includes('session') || c.name.includes('PHPSESSID'));
    const rememberCookie = cookies.find(c => c.name.includes('remember'));

    console.log('=== Cookie Information ===');
    if (sessionCookie) {
      console.log(`Session cookie found: ${sessionCookie.name}`);
      console.log(`Session cookie expires: ${sessionCookie.expires ? new Date(sessionCookie.expires * 1000) : 'Session cookie'}`);
    }
    if (rememberCookie) {
      console.log(`Remember cookie found: ${rememberCookie.name}`);
      console.log(`Remember cookie expires: ${rememberCookie.expires ? new Date(rememberCookie.expires * 1000) : 'Session cookie'}`);
    }

    // Get page content for debugging
    const pageTitle = await page.title();
    console.log(`Page title: ${pageTitle}`);

    // Check page content
    const bodyText = await page.locator('body').textContent();
    if (bodyText.toLowerCase().includes('error') || bodyText.toLowerCase().includes('failed')) {
      console.error('Page contains error-related text');
    }

    // Final status
    if (isDashboard) {
      console.log('✅ Login test PASSED - Successfully logged in');
    } else {
      console.error('❌ Login test FAILED - Could not log in');

      // Try to get more diagnostic information
      const pageContent = await page.content();
      if (pageContent.length < 5000) { // Only log if page is reasonably small
        console.log('Page HTML for debugging:', pageContent.substring(0, 2000));
      }
    }
  });
});