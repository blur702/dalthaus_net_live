import { test, expect } from '@playwright/test';

test.describe('Live Site - Authentication Diagnosis', () => {
  const baseURL = 'https://dalthaus.net';
  const adminCredentials = {
    username: 'kevin',
    password: '(130Bpm)'
  };

  test('Detailed authentication flow analysis', async ({ page, context }) => {
    console.log('='.repeat(80));
    console.log('COMPREHENSIVE AUTHENTICATION DIAGNOSIS');
    console.log('='.repeat(80));

    // Step 1: Clear state and navigate to login
    console.log('\n1. INITIAL SETUP');
    console.log('-'.repeat(40));
    await context.clearCookies();
    await page.goto(`${baseURL}/admin/login`);
    console.log(`✓ Navigated to: ${page.url()}`);

    // Check login page elements
    const usernameField = page.locator('input[name="username"]');
    const passwordField = page.locator('input[name="password"]');
    const rememberCheckbox = page.locator('input[name="remember_me"]');
    const submitButton = page.locator('button[type="submit"], input[type="submit"]');

    console.log(`  Username field present: ${await usernameField.count() > 0}`);
    console.log(`  Password field present: ${await passwordField.count() > 0}`);
    console.log(`  Remember me checkbox present: ${await rememberCheckbox.count() > 0}`);
    console.log(`  Submit button present: ${await submitButton.count() > 0}`);

    // Take screenshot of login page
    await page.screenshot({
      path: 'test-results/diagnosis-01-login-page.png',
      fullPage: true
    });

    // Step 2: Fill form and attempt login
    console.log('\n2. LOGIN ATTEMPT');
    console.log('-'.repeat(40));

    await usernameField.fill(adminCredentials.username);
    await passwordField.fill(adminCredentials.password);

    if (await rememberCheckbox.count() > 0) {
      await rememberCheckbox.check();
      console.log('✓ Remember me checkbox checked');
    } else {
      console.log('✗ No remember me checkbox found');
    }

    // Take screenshot before submit
    await page.screenshot({
      path: 'test-results/diagnosis-02-before-submit.png',
      fullPage: true
    });

    // Monitor network requests during login
    const requests = [];
    page.on('request', request => {
      requests.push({
        url: request.url(),
        method: request.method(),
        headers: request.headers()
      });
    });

    const responses = [];
    page.on('response', response => {
      responses.push({
        url: response.url(),
        status: response.status(),
        headers: response.headers()
      });
    });

    // Submit form
    await submitButton.click();
    await page.waitForLoadState('networkidle');

    // Step 3: Analyze login result
    console.log('\n3. LOGIN RESULT ANALYSIS');
    console.log('-'.repeat(40));

    const currentUrl = page.url();
    console.log(`Current URL: ${currentUrl}`);

    // Check for error messages
    const errorSelectors = [
      '.alert-danger',
      '.error',
      '.alert.alert-danger',
      '[class*="error"]',
      '[class*="alert"]'
    ];

    for (const selector of errorSelectors) {
      const errorElement = page.locator(selector);
      if (await errorElement.count() > 0) {
        const errorText = await errorElement.textContent();
        console.log(`  Error message found (${selector}): ${errorText}`);
      }
    }

    // Take screenshot after submit
    await page.screenshot({
      path: 'test-results/diagnosis-03-after-submit.png',
      fullPage: true
    });

    // Step 4: Cookie analysis
    console.log('\n4. COOKIE ANALYSIS');
    console.log('-'.repeat(40));

    const cookies = await context.cookies();
    console.log(`Total cookies: ${cookies.length}`);

    cookies.forEach(cookie => {
      console.log(`  Cookie: ${cookie.name}`);
      console.log(`    Value: ${cookie.value.substring(0, 50)}${cookie.value.length > 50 ? '...' : ''}`);
      console.log(`    Domain: ${cookie.domain}`);
      console.log(`    Path: ${cookie.path}`);
      console.log(`    Expires: ${cookie.expires ? new Date(cookie.expires * 1000).toLocaleString() : 'Session'}`);
      console.log(`    HttpOnly: ${cookie.httpOnly}`);
      console.log(`    Secure: ${cookie.secure}`);
      console.log();
    });

    // Step 5: Navigation test
    console.log('\n5. NAVIGATION TEST');
    console.log('-'.repeat(40));

    if (currentUrl.includes('/admin') && !currentUrl.includes('/admin/login')) {
      console.log('✓ Login successful - testing navigation...');

      // Try to navigate to different admin pages
      const testPages = [
        '/admin/dashboard',
        '/admin/content',
        '/admin/pages'
      ];

      for (const testPage of testPages) {
        console.log(`  Testing: ${testPage}`);
        await page.goto(`${baseURL}${testPage}`);
        await page.waitForLoadState('networkidle');

        const pageUrl = page.url();
        if (pageUrl.includes('/admin/login')) {
          console.log(`    ✗ Redirected to login`);
        } else if (pageUrl.includes(testPage)) {
          console.log(`    ✓ Successfully accessed`);
        } else {
          console.log(`    ⚠ Unexpected redirect: ${pageUrl}`);
        }
      }
    } else {
      console.log('✗ Login failed - cannot test navigation');
    }

    // Step 6: Page refresh test
    console.log('\n6. SESSION PERSISTENCE TEST');
    console.log('-'.repeat(40));

    if (!currentUrl.includes('/admin/login')) {
      console.log('Testing page refresh...');
      await page.reload();
      await page.waitForLoadState('networkidle');

      const afterRefreshUrl = page.url();
      if (afterRefreshUrl.includes('/admin/login')) {
        console.log('✗ Session lost after refresh');
      } else {
        console.log('✓ Session persisted after refresh');
      }

      await page.screenshot({
        path: 'test-results/diagnosis-04-after-refresh.png',
        fullPage: true
      });
    }

    // Step 7: Network request analysis
    console.log('\n7. NETWORK REQUEST ANALYSIS');
    console.log('-'.repeat(40));

    console.log('Login requests made:');
    requests.forEach((req, index) => {
      if (req.url.includes('/admin/login') || req.method === 'POST') {
        console.log(`  ${index + 1}. ${req.method} ${req.url}`);
        if (req.headers['content-type']) {
          console.log(`     Content-Type: ${req.headers['content-type']}`);
        }
      }
    });

    console.log('\nLogin responses received:');
    responses.forEach((res, index) => {
      if (res.url.includes('/admin/login') || res.status >= 300) {
        console.log(`  ${index + 1}. ${res.status} ${res.url}`);
        if (res.headers['location']) {
          console.log(`     Location: ${res.headers['location']}`);
        }
        if (res.headers['set-cookie']) {
          console.log(`     Set-Cookie: ${res.headers['set-cookie']}`);
        }
      }
    });

    // Final summary
    console.log('\n' + '='.repeat(80));
    console.log('DIAGNOSIS SUMMARY');
    console.log('='.repeat(80));

    const sessionCookie = cookies.find(c => c.name === 'cms_session' || c.name === 'PHPSESSID');
    const rememberCookie = cookies.find(c => c.name === 'remember_token' || c.name.includes('remember'));

    console.log(`Login Success: ${!currentUrl.includes('/admin/login')}`);
    console.log(`Session Cookie: ${sessionCookie ? 'Present' : 'Missing'}`);
    console.log(`Remember Cookie: ${rememberCookie ? 'Present' : 'Missing'}`);
    console.log(`Current URL: ${currentUrl}`);

    if (sessionCookie) {
      console.log(`Session Cookie Name: ${sessionCookie.name}`);
      console.log(`Session Cookie Expires: ${sessionCookie.expires ? new Date(sessionCookie.expires * 1000).toLocaleString() : 'Session'}`);
    }

    if (rememberCookie) {
      console.log(`Remember Cookie Name: ${rememberCookie.name}`);
      console.log(`Remember Cookie Expires: ${new Date(rememberCookie.expires * 1000).toLocaleString()}`);
    }

    console.log('='.repeat(80));
  });

  test('Remember me functionality isolation test', async ({ browser }) => {
    console.log('\n' + '='.repeat(80));
    console.log('REMEMBER ME ISOLATION TEST');
    console.log('='.repeat(80));

    // Create first context for login
    const context1 = await browser.newContext();
    const page1 = await context1.newPage();

    console.log('\n1. Initial login with remember me...');
    await page1.goto(`${baseURL}/admin/login`);

    // Fill form
    await page1.fill('input[name="username"]', adminCredentials.username);
    await page1.fill('input[name="password"]', adminCredentials.password);

    const rememberCheckbox = page1.locator('input[name="remember_me"]');
    if (await rememberCheckbox.count() > 0) {
      await rememberCheckbox.check();
      console.log('✓ Remember me checkbox checked');
    }

    // Submit and wait
    await page1.click('button[type="submit"], input[type="submit"]');
    await page1.waitForLoadState('networkidle');

    // Check result
    const loginUrl = page1.url();
    console.log(`After login URL: ${loginUrl}`);

    // Get cookies from first context
    const cookies1 = await context1.cookies();
    const rememberCookie = cookies1.find(c => c.name === 'remember_token' || c.name.includes('remember'));

    console.log(`\nCookies after login:`);
    cookies1.forEach(cookie => {
      console.log(`  ${cookie.name}: ${cookie.value.substring(0, 30)}...`);
    });

    // Close first context
    await context1.close();
    console.log('\n2. Browser context closed (simulating restart)');

    // Create second context
    const context2 = await browser.newContext();

    // Add remember cookie if it exists
    if (rememberCookie) {
      console.log('3. Restoring remember cookie to new context...');
      await context2.addCookies([rememberCookie]);
    } else {
      console.log('3. No remember cookie to restore');
    }

    const page2 = await context2.newPage();

    // Try to access admin area directly
    console.log('4. Attempting direct access to admin area...');
    await page2.goto(`${baseURL}/admin/dashboard`);
    await page2.waitForLoadState('networkidle');

    const finalUrl = page2.url();
    console.log(`Final URL: ${finalUrl}`);

    if (finalUrl.includes('/admin/dashboard')) {
      console.log('✓ Remember me works! Direct access successful');
    } else if (finalUrl.includes('/admin/login')) {
      console.log('✗ Remember me failed - redirected to login');
    } else {
      console.log(`⚠ Unexpected result: ${finalUrl}`);
    }

    await page2.screenshot({
      path: 'test-results/diagnosis-05-remember-test.png',
      fullPage: true
    });

    await context2.close();
    console.log('='.repeat(80));
  });
});