const { test, expect } = require('@playwright/test');

test.describe('Detailed Authentication Debugging', () => {
  test('Capture detailed authentication flow and error messages', async ({ page, context }) => {
    // Enable all logging
    page.on('console', msg => {
      const type = msg.type();
      const text = msg.text();
      console.log(`[CONSOLE ${type.toUpperCase()}]: ${text}`);
    });

    page.on('pageerror', error => {
      console.log(`[PAGE ERROR]: ${error.message}`);
    });

    // Track all requests and responses
    page.on('request', request => {
      if (request.url().includes('admin')) {
        console.log(`\n[REQUEST] ${request.method()} ${request.url()}`);

        if (request.method() === 'POST') {
          const postData = request.postData();
          if (postData) {
            console.log('[POST DATA]:', postData);
          }
        }
      }
    });

    page.on('response', async response => {
      if (response.url().includes('admin')) {
        console.log(`[RESPONSE] ${response.status()} ${response.url()}`);

        // Get all response headers
        const headers = response.headers();
        console.log('[RESPONSE HEADERS]:');
        Object.entries(headers).forEach(([key, value]) => {
          if (['location', 'set-cookie', 'content-type'].includes(key.toLowerCase())) {
            console.log(`  ${key}: ${value}`);
          }
        });

        // Try to get response body for error analysis
        if (response.status() >= 400 || response.url().includes('/admin/login')) {
          try {
            const contentType = headers['content-type'] || '';
            if (contentType.includes('text/html')) {
              const body = await response.text();

              // Look for PHP errors
              if (body.includes('Fatal error') || body.includes('Warning:') || body.includes('SQLSTATE')) {
                console.log('\n⚠️ PHP/DATABASE ERROR DETECTED:');
                console.log(body.substring(0, 1000));
              }

              // Look for specific error messages in the HTML
              const errorRegex = /<div[^>]*class="[^"]*(?:error|alert-danger|text-danger|text-red-500)[^"]*"[^>]*>(.*?)<\/div>/gi;
              let match;
              while ((match = errorRegex.exec(body)) !== null) {
                console.log(`\n📝 ERROR MESSAGE FOUND: ${match[1].trim()}`);
              }

              // Check for flash messages
              const flashRegex = /<div[^>]*class="[^"]*flash[^"]*"[^>]*>(.*?)<\/div>/gi;
              while ((match = flashRegex.exec(body)) !== null) {
                console.log(`\n💬 FLASH MESSAGE: ${match[1].trim()}`);
              }
            }
          } catch (e) {
            console.log('[RESPONSE] Could not read body:', e.message);
          }
        }
      }
    });

    console.log('\n========================================');
    console.log('DETAILED AUTHENTICATION DEBUG');
    console.log('========================================\n');

    // Navigate to login page
    await page.goto('https://dalthaus.net/admin/login', {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    // Take screenshot of login page
    await page.screenshot({
      path: 'test-results/auth-debug-login-page.png',
      fullPage: true
    });

    // Check if we can see any error messages already on the page
    const existingErrors = await page.locator('.error, .alert-danger, .text-danger, .text-red-500, .flash').allTextContents();
    if (existingErrors.length > 0) {
      console.log('\n⚠️ EXISTING ERROR MESSAGES ON LOGIN PAGE:');
      existingErrors.forEach(msg => console.log(`  - ${msg.trim()}`));
    }

    // Fill in login form
    console.log('\n📝 Filling login form...');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');

    // Get CSRF token for debugging
    const csrfToken = await page.locator('input[name="_token"]').getAttribute('value');
    console.log(`\n🔐 CSRF Token: ${csrfToken?.substring(0, 16)}...`);

    // Take screenshot before submission
    await page.screenshot({
      path: 'test-results/auth-debug-before-submit.png',
      fullPage: true
    });

    console.log('\n🚀 Submitting login form...');

    // Submit form and wait for response
    const submitButton = page.locator('button[type="submit"]');

    const [response] = await Promise.all([
      page.waitForResponse(response =>
        response.url().includes('/admin/login') && response.request().method() === 'POST'
      ),
      submitButton.click()
    ]);

    console.log(`\n📨 Login form submitted, response status: ${response.status()}`);

    // Wait a moment for any redirects
    await page.waitForTimeout(3000);

    // Take screenshot after submission
    await page.screenshot({
      path: 'test-results/auth-debug-after-submit.png',
      fullPage: true
    });

    const finalUrl = page.url();
    console.log(`\n📍 Final URL: ${finalUrl}`);

    // Check for error messages after submission
    const postSubmitErrors = await page.locator('.error, .alert-danger, .text-danger, .text-red-500, .flash').allTextContents();
    if (postSubmitErrors.length > 0) {
      console.log('\n⚠️ ERROR MESSAGES AFTER SUBMISSION:');
      postSubmitErrors.forEach(msg => console.log(`  - ${msg.trim()}`));
    }

    // Check session cookies
    const cookies = await context.cookies();
    const sessionCookie = cookies.find(c => c.name.includes('session') || c.name === 'PHPSESSID');
    if (sessionCookie) {
      console.log(`\n🍪 Session cookie found: ${sessionCookie.name} = ${sessionCookie.value.substring(0, 10)}...`);
    } else {
      console.log('\n❌ No session cookie found');
    }

    // Check authentication status by trying to access dashboard
    console.log('\n🔍 Testing dashboard access...');
    await page.goto('https://dalthaus.net/admin/dashboard', {
      waitUntil: 'networkidle',
      timeout: 10000
    }).catch(e => console.log('Dashboard access failed:', e.message));

    const dashboardUrl = page.url();
    console.log(`📍 Dashboard redirect URL: ${dashboardUrl}`);

    if (dashboardUrl.includes('dashboard')) {
      console.log('✅ Authentication successful - accessed dashboard');
    } else {
      console.log('❌ Authentication failed - redirected away from dashboard');
    }

    // Final analysis
    console.log('\n========================================');
    console.log('AUTHENTICATION ANALYSIS SUMMARY');
    console.log('========================================');

    if (finalUrl.includes('dashboard')) {
      console.log('✅ LOGIN SUCCESSFUL');
    } else if (finalUrl.includes('login')) {
      console.log('❌ LOGIN FAILED - REDIRECTED BACK TO LOGIN');
      console.log('   Possible causes:');
      console.log('   - Invalid credentials');
      console.log('   - Database connection issue');
      console.log('   - Session configuration problem');
      console.log('   - CSRF token validation failure');
      console.log('   - Authentication logic error');
    } else {
      console.log(`⚠️ UNEXPECTED REDIRECT TO: ${finalUrl}`);
    }
  });

  test('Test database connectivity by checking user creation', async ({ page }) => {
    // This test will help us understand if the database is accessible
    console.log('\n========================================');
    console.log('DATABASE CONNECTIVITY TEST');
    console.log('========================================\n');

    await page.goto('https://dalthaus.net/admin/login');

    // Try to trigger a database error by using invalid credentials
    // This should help us see if we get database-specific error messages
    await page.fill('input[name="username"]', 'nonexistent_user_test_12345');
    await page.fill('input[name="password"]', 'invalid_password');

    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);

    const errorMessages = await page.locator('.error, .alert-danger, .text-danger, .text-red-500, .flash').allTextContents();

    console.log('Error messages with invalid credentials:');
    if (errorMessages.length > 0) {
      errorMessages.forEach(msg => console.log(`  - ${msg.trim()}`));
    } else {
      console.log('  No error messages displayed');
    }

    // Check if we're still on login page
    const currentUrl = page.url();
    console.log(`Current URL after invalid login: ${currentUrl}`);
  });
});