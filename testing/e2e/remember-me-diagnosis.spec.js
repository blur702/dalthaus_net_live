const { test, expect } = require('@playwright/test');

test.describe('Remember Me Functionality Diagnosis', () => {
  test('Diagnose remember me checkbox issue on live site', async ({ page, context }) => {
    console.log('='.repeat(80));
    console.log('REMEMBER ME FUNCTIONALITY DIAGNOSIS');
    console.log('='.repeat(80));

    // Enable console logging
    page.on('console', msg => {
      if (msg.type() === 'error') {
        console.log('❌ CONSOLE ERROR:', msg.text());
      } else if (msg.type() === 'warning') {
        console.log('⚠️ CONSOLE WARNING:', msg.text());
      } else {
        console.log('📝 CONSOLE LOG:', msg.text());
      }
    });

    // Monitor network requests
    const networkLogs = [];
    page.on('request', request => {
      if (request.url().includes('/admin/login')) {
        console.log(`\n📤 REQUEST: ${request.method()} ${request.url()}`);
        console.log('Headers:', request.headers());
        if (request.method() === 'POST') {
          console.log('POST Data:', request.postData());
        }
      }
    });

    page.on('response', response => {
      if (response.url().includes('/admin/login')) {
        networkLogs.push({
          url: response.url(),
          status: response.status(),
          statusText: response.statusText(),
          headers: response.headers()
        });
        console.log(`\n📥 RESPONSE: ${response.status()} ${response.statusText()}`);
        console.log('URL:', response.url());
        console.log('Headers:', response.headers());
      }
    });

    // Monitor page errors
    page.on('pageerror', error => {
      console.log('🔴 PAGE ERROR:', error.message);
    });

    try {
      console.log('\n1. Navigating to login page...');
      await page.goto('https://dalthaus.net/admin/login', {
        waitUntil: 'networkidle',
        timeout: 30000
      });
      console.log('✅ Page loaded');

      // Check initial page state
      console.log('\n2. Checking initial page state...');
      const pageTitle = await page.title();
      console.log('Page title:', pageTitle);

      // Check if login form exists
      const loginForm = await page.locator('form').first();
      const formExists = await loginForm.count() > 0;
      console.log('Login form exists:', formExists);

      if (formExists) {
        // Get form action
        const formAction = await loginForm.getAttribute('action');
        const formMethod = await loginForm.getAttribute('method');
        console.log('Form action:', formAction);
        console.log('Form method:', formMethod);
      }

      // Check for remember me checkbox
      console.log('\n3. Looking for remember me checkbox...');
      const checkboxSelectors = [
        'input[type="checkbox"][name="remember_me"]',
        'input[type="checkbox"][name="remember"]',
        'input[type="checkbox"]#remember_me',
        'input[type="checkbox"]#remember',
        'input[type="checkbox"]',
        '[name="remember_me"]',
        '[name="remember"]'
      ];

      let rememberCheckbox = null;
      let foundSelector = null;

      for (const selector of checkboxSelectors) {
        const count = await page.locator(selector).count();
        console.log(`Checking selector "${selector}": found ${count} element(s)`);
        if (count > 0) {
          rememberCheckbox = page.locator(selector).first();
          foundSelector = selector;
          break;
        }
      }

      if (!rememberCheckbox) {
        console.log('❌ No remember me checkbox found!');

        // Get all input elements for debugging
        const allInputs = await page.locator('input').all();
        console.log(`\nFound ${allInputs.length} input elements:`);
        for (let i = 0; i < allInputs.length; i++) {
          const input = allInputs[i];
          const type = await input.getAttribute('type');
          const name = await input.getAttribute('name');
          const id = await input.getAttribute('id');
          const value = await input.getAttribute('value');
          console.log(`  Input ${i + 1}: type="${type}", name="${name}", id="${id}", value="${value}"`);
        }

        // Get form HTML for inspection
        console.log('\nForm HTML structure:');
        const formHTML = await page.locator('form').first().innerHTML();
        console.log(formHTML.substring(0, 1000)); // First 1000 chars

      } else {
        console.log(`✅ Found checkbox with selector: ${foundSelector}`);

        // Get checkbox attributes
        const checkboxName = await rememberCheckbox.getAttribute('name');
        const checkboxId = await rememberCheckbox.getAttribute('id');
        const checkboxType = await rememberCheckbox.getAttribute('type');
        const checkboxValue = await rememberCheckbox.getAttribute('value');
        console.log(`Checkbox attributes: name="${checkboxName}", id="${checkboxId}", type="${checkboxType}", value="${checkboxValue}"`);

        // Check if checkbox is visible and enabled
        const isVisible = await rememberCheckbox.isVisible();
        const isEnabled = await rememberCheckbox.isEnabled();
        const isChecked = await rememberCheckbox.isChecked();
        console.log(`Checkbox state: visible=${isVisible}, enabled=${isEnabled}, checked=${isChecked}`);
      }

      // Fill in credentials
      console.log('\n4. Filling in credentials...');
      await page.fill('input[name="username"]', 'kevin');
      console.log('✅ Username entered');

      await page.fill('input[name="password"]', '(130Bpm)');
      console.log('✅ Password entered');

      // Try to check the remember me checkbox if found
      if (rememberCheckbox) {
        console.log('\n5. Attempting to check remember me checkbox...');

        try {
          // Try different methods to check the checkbox
          const wasChecked = await rememberCheckbox.isChecked();
          console.log('Initial checked state:', wasChecked);

          if (!wasChecked) {
            // Method 1: Direct check
            await rememberCheckbox.check({ timeout: 5000 });
            console.log('✅ Checkbox checked using check() method');
          }

          // Verify it's checked
          const isNowChecked = await rememberCheckbox.isChecked();
          console.log('Final checked state:', isNowChecked);

          if (!isNowChecked) {
            console.log('⚠️ Checkbox check failed, trying click method...');
            await rememberCheckbox.click();
            const afterClick = await rememberCheckbox.isChecked();
            console.log('After click, checked state:', afterClick);
          }

        } catch (error) {
          console.log('❌ Error checking checkbox:', error.message);

          // Try JavaScript evaluation as fallback
          console.log('Attempting JavaScript checkbox check...');
          const jsResult = await page.evaluate((selector) => {
            const checkbox = document.querySelector(selector);
            if (checkbox) {
              checkbox.checked = true;
              // Trigger change event
              checkbox.dispatchEvent(new Event('change', { bubbles: true }));
              return { success: true, checked: checkbox.checked };
            }
            return { success: false, error: 'Checkbox not found in DOM' };
          }, foundSelector);
          console.log('JavaScript result:', jsResult);
        }
      }

      // Take screenshot before submission
      console.log('\n6. Taking pre-submission screenshot...');
      await page.screenshot({
        path: 'tests/screenshots/remember-me-before-submit.png',
        fullPage: true
      });
      console.log('✅ Screenshot saved');

      // Prepare to capture network response
      console.log('\n7. Submitting form...');

      // Set up response promise before clicking
      const responsePromise = page.waitForResponse(
        response => response.url().includes('/admin/login') && response.request().method() === 'POST',
        { timeout: 10000 }
      ).catch(err => {
        console.log('⚠️ No POST response captured:', err.message);
        return null;
      });

      // Click submit button
      const submitButton = page.locator('button[type="submit"], input[type="submit"]').first();
      await submitButton.click();
      console.log('✅ Submit button clicked');

      // Wait for response
      const response = await responsePromise;

      if (response) {
        console.log('\n8. Analyzing response...');
        console.log('Response status:', response.status());
        console.log('Response URL:', response.url());

        // Try to get response body
        try {
          const responseBody = await response.text();
          console.log('Response body (first 500 chars):');
          console.log(responseBody.substring(0, 500));

          // Look for error messages in response
          if (responseBody.includes('error') || responseBody.includes('Error')) {
            console.log('\n⚠️ Response contains error messages');
            const errorMatch = responseBody.match(/error[^<]*/gi);
            if (errorMatch) {
              console.log('Error snippets found:', errorMatch);
            }
          }
        } catch (err) {
          console.log('Could not read response body:', err.message);
        }

        // Check cookies
        const cookies = await context.cookies();
        console.log('\n9. Checking cookies after submission:');
        const relevantCookies = cookies.filter(c =>
          c.name.includes('session') ||
          c.name.includes('remember') ||
          c.name.includes('cms')
        );

        if (relevantCookies.length > 0) {
          relevantCookies.forEach(cookie => {
            console.log(`Cookie: ${cookie.name}`);
            console.log(`  Value: ${cookie.value.substring(0, 20)}...`);
            console.log(`  Domain: ${cookie.domain}`);
            console.log(`  Path: ${cookie.path}`);
            console.log(`  Expires: ${cookie.expires ? new Date(cookie.expires * 1000) : 'Session'}`);
            console.log(`  HttpOnly: ${cookie.httpOnly}`);
            console.log(`  Secure: ${cookie.secure}`);
          });
        } else {
          console.log('❌ No relevant cookies found');
        }
      }

      // Wait a moment for any redirects
      await page.waitForTimeout(3000);

      // Check final URL
      const finalUrl = page.url();
      console.log('\n10. Final state:');
      console.log('Final URL:', finalUrl);

      if (finalUrl.includes('/admin/dashboard')) {
        console.log('✅ Login successful - redirected to dashboard');

        // Check for remember me cookie
        const cookies = await context.cookies();
        const rememberCookie = cookies.find(c => c.name === 'remember_token' || c.name.includes('remember'));

        if (rememberCookie) {
          console.log('✅ Remember me cookie found:', rememberCookie.name);
        } else {
          console.log('❌ No remember me cookie found after successful login');
        }
      } else if (finalUrl.includes('/admin/login')) {
        console.log('⚠️ Still on login page - checking for error messages');

        // Look for error messages
        const errorMessages = await page.locator('.error, .alert-danger, .message, [class*="error"], [class*="alert"]').all();
        if (errorMessages.length > 0) {
          console.log('Found error message elements:');
          for (const msg of errorMessages) {
            const text = await msg.textContent();
            console.log('  Error:', text);
          }
        }

        // Take screenshot of final state
        await page.screenshot({
          path: 'tests/screenshots/remember-me-final-state.png',
          fullPage: true
        });
        console.log('Final screenshot saved');
      }

      // Get page content for debugging
      console.log('\n11. Checking page content for debugging info...');
      const pageContent = await page.content();

      // Look for PHP debugging output
      if (pageContent.includes('Remember me processing') ||
          pageContent.includes('Setting remember cookie') ||
          pageContent.includes('AUTH_DEBUG')) {
        console.log('⚠️ Found debugging output in page:');
        const debugMatches = pageContent.match(/.*(?:Remember me|AUTH_DEBUG|Setting cookie).*/gi);
        if (debugMatches) {
          debugMatches.forEach(match => console.log('  Debug:', match));
        }
      }

    } catch (error) {
      console.log('\n❌ Test failed with error:', error.message);
      console.log('Stack trace:', error.stack);

      // Take error screenshot
      await page.screenshot({
        path: 'tests/screenshots/remember-me-error.png',
        fullPage: true
      });
      console.log('Error screenshot saved');

      throw error;
    }

    console.log('\n' + '='.repeat(80));
    console.log('DIAGNOSIS COMPLETE');
    console.log('='.repeat(80));
  });
});