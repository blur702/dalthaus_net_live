const { test, expect } = require('@playwright/test');

test.describe('CSRF Token Debug', () => {
  test('Check CSRF token presence and form structure', async ({ page }) => {
    console.log('=== CSRF Token Analysis ===');

    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('domcontentloaded');

    // Get the full page HTML
    const pageHTML = await page.content();

    // Check for CSRF token in various formats
    const csrfInputToken = await page.locator('input[name="_token"]').getAttribute('value').catch(() => null);
    const csrfInputCsrf = await page.locator('input[name="csrf_token"]').getAttribute('value').catch(() => null);
    const csrfMeta = await page.locator('meta[name="csrf-token"]').getAttribute('content').catch(() => null);

    console.log(`CSRF token (_token): ${csrfInputToken || 'NOT FOUND'}`);
    console.log(`CSRF token (csrf_token): ${csrfInputCsrf || 'NOT FOUND'}`);
    console.log(`CSRF meta tag: ${csrfMeta || 'NOT FOUND'}`);

    // Check all hidden inputs
    const hiddenInputs = await page.locator('input[type="hidden"]').all();
    console.log(`Found ${hiddenInputs.length} hidden inputs:`);

    for (let i = 0; i < hiddenInputs.length; i++) {
      const name = await hiddenInputs[i].getAttribute('name');
      const value = await hiddenInputs[i].getAttribute('value');
      console.log(`  Hidden input: name="${name}" value="${value ? value.substring(0, 20) + '...' : 'empty'}"`);
    }

    // Check the form structure
    const forms = await page.locator('form').all();
    console.log(`Found ${forms.length} forms on page`);

    for (let i = 0; i < forms.length; i++) {
      const action = await forms[i].getAttribute('action');
      const method = await forms[i].getAttribute('method');
      console.log(`  Form ${i + 1}: action="${action}" method="${method}"`);
    }

    // Check if the page contains any PHP errors or debugging info
    if (pageHTML.includes('error') || pageHTML.includes('Error') || pageHTML.includes('warning')) {
      console.log('⚠️ Page may contain error messages');

      // Look for specific error indicators
      if (pageHTML.includes('PHP') || pageHTML.includes('Fatal')) {
        console.log('❌ PHP errors detected in page source');
      }
    }

    // Check for login form specifically
    const loginForm = await page.locator('form').first();
    if (await loginForm.isVisible()) {
      const formHTML = await loginForm.innerHTML();
      console.log('Login form HTML:');
      console.log(formHTML);
    }

    // Take a screenshot
    await page.screenshot({
      path: 'tests/screenshots/csrf-debug.png',
      fullPage: true
    });

    // Test if we can make a POST request without CSRF token
    console.log('\n=== Testing Form Submission ===');

    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');

    // Monitor network for the POST request
    const responsePromise = page.waitForResponse(response =>
      response.url().includes('login') && response.request().method() === 'POST'
    );

    await page.click('button[type="submit"]');

    try {
      const response = await responsePromise;
      console.log(`POST response status: ${response.status()}`);
      console.log(`POST response URL: ${response.url()}`);

      const responseText = await response.text();
      console.log(`Response text (first 500 chars): ${responseText.substring(0, 500)}`);

    } catch (error) {
      console.log(`No POST response captured: ${error.message}`);
    }

    await page.waitForLoadState('networkidle');
    const finalURL = page.url();
    console.log(`Final URL after submission: ${finalURL}`);
  });
});