const { test, expect } = require('@playwright/test');

test.describe('Live Site - Remember Me Functionality', () => {
  test('should implement remember me functionality correctly', async ({ page, context }) => {
    console.log('Starting Remember Me functionality test on live site...\n');

    // Step 1: Navigate to login page
    console.log('Step 1: Navigating to https://dalthaus.net/admin/login');
    await page.goto('https://dalthaus.net/admin/login');
    await expect(page).toHaveURL('https://dalthaus.net/admin/login');
    console.log('✓ Successfully loaded login page\n');

    // Step 2: Fill in credentials
    console.log('Step 2: Filling in login credentials');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    console.log('✓ Username and password entered\n');

    // Step 3: Check the remember me checkbox
    console.log('Step 3: Checking "Remember me for 30 days" checkbox');
    const rememberCheckbox = page.locator('input[name="remember_me"]');
    await expect(rememberCheckbox).toBeVisible();
    await rememberCheckbox.check();
    await expect(rememberCheckbox).toBeChecked();
    console.log('✓ Remember me checkbox is checked\n');

    // Step 4: Submit the form
    console.log('Step 4: Submitting login form');
    await page.click('button[type="submit"]');

    // Step 5: Verify successful login
    console.log('Step 5: Verifying successful login and redirect');
    await page.waitForURL('https://dalthaus.net/admin/dashboard', { timeout: 10000 });
    await expect(page).toHaveURL('https://dalthaus.net/admin/dashboard');
    console.log('✓ Successfully logged in and redirected to dashboard\n');

    // Step 6: Check for remember_token cookie
    console.log('Step 6: Checking for remember_token cookie creation');
    const cookies = await context.cookies();
    const rememberCookie = cookies.find(c => c.name === 'remember_token');

    if (rememberCookie) {
      console.log('✓ Remember token cookie found!');
      console.log('Cookie details:');
      console.log(`  - Name: ${rememberCookie.name}`);
      console.log(`  - Value: ${rememberCookie.value.substring(0, 20)}...`);
      console.log(`  - Domain: ${rememberCookie.domain}`);
      console.log(`  - Path: ${rememberCookie.path}`);
      console.log(`  - HttpOnly: ${rememberCookie.httpOnly}`);
      console.log(`  - Secure: ${rememberCookie.secure}`);
      console.log(`  - SameSite: ${rememberCookie.sameSite}`);

      // Check expiration (should be ~30 days from now)
      if (rememberCookie.expires !== -1) {
        const expirationDate = new Date(rememberCookie.expires * 1000);
        const now = new Date();
        const daysDiff = Math.round((expirationDate - now) / (1000 * 60 * 60 * 24));
        console.log(`  - Expires: ${expirationDate.toISOString()} (${daysDiff} days from now)`);

        // Verify it's approximately 30 days
        expect(daysDiff).toBeGreaterThanOrEqual(29);
        expect(daysDiff).toBeLessThanOrEqual(31);
        console.log('✓ Cookie expiration is correctly set to ~30 days\n');
      } else {
        console.log('  - Expires: Session cookie (expires = -1)');
        console.log('✗ WARNING: Cookie is a session cookie, not persistent!\n');
      }
    } else {
      console.log('✗ Remember token cookie NOT found!\n');
      console.log('All cookies found:');
      cookies.forEach(c => {
        console.log(`  - ${c.name}: ${c.value.substring(0, 20)}...`);
      });
    }

    // Step 7: Test logout
    console.log('Step 7: Testing logout');
    await page.goto('https://dalthaus.net/admin/logout');
    await page.waitForURL('https://dalthaus.net/admin/login', { timeout: 10000 });
    console.log('✓ Successfully logged out\n');

    // Step 8: Check if remember token cookie persists after logout
    console.log('Step 8: Checking if remember_token cookie persists after logout');
    const cookiesAfterLogout = await context.cookies();
    const rememberCookieAfterLogout = cookiesAfterLogout.find(c => c.name === 'remember_token');

    if (rememberCookieAfterLogout) {
      console.log('✓ Remember token cookie persists after logout');
      console.log(`  - Value: ${rememberCookieAfterLogout.value.substring(0, 20)}...`);
    } else {
      console.log('✗ Remember token cookie was removed on logout\n');
    }

    // Step 9: Test auto-login by accessing admin page
    console.log('\nStep 9: Testing persistent authentication (auto-login)');
    console.log('Attempting to access admin dashboard without logging in...');
    await page.goto('https://dalthaus.net/admin/dashboard');
    await page.waitForLoadState('networkidle');

    const currentUrl = page.url();
    if (currentUrl.includes('/admin/dashboard')) {
      console.log('✓ Auto-login successful! User was automatically authenticated');
      console.log('  - Current URL: ' + currentUrl);

      // Verify we're actually logged in by checking for dashboard content
      const dashboardContent = await page.locator('h1').textContent();
      console.log(`  - Dashboard heading: ${dashboardContent}`);
    } else if (currentUrl.includes('/admin/login')) {
      console.log('✗ Auto-login FAILED - redirected to login page');
      console.log('  - Current URL: ' + currentUrl);
    } else {
      console.log('? Unexpected URL after auto-login attempt');
      console.log('  - Current URL: ' + currentUrl);
    }

    // Final summary
    console.log('\n' + '='.repeat(60));
    console.log('REMEMBER ME FUNCTIONALITY TEST SUMMARY');
    console.log('='.repeat(60));

    const testResults = {
      loginSuccess: currentUrl.includes('/admin/dashboard'),
      cookieCreated: !!rememberCookie,
      cookieExpiration: rememberCookie && rememberCookie.expires !== -1 ? 'Correct (~30 days)' : 'Incorrect',
      cookiePersistsAfterLogout: !!rememberCookieAfterLogout,
      autoLoginWorks: false // Will be set based on final test
    };

    // Do a final definitive auto-login test
    if (!rememberCookieAfterLogout) {
      // If cookie doesn't persist, we need to login again with remember me
      console.log('\nPerforming fresh login with remember me for final test...');
      await page.goto('https://dalthaus.net/admin/login');
      await page.fill('input[name="username"]', 'kevin');
      await page.fill('input[name="password"]', '(130Bpm)');
      await page.locator('input[name="remember_me"]').check();
      await page.click('button[type="submit"]');
      await page.waitForURL('https://dalthaus.net/admin/dashboard');

      // Now logout and test auto-login
      await page.goto('https://dalthaus.net/admin/logout');
      await page.waitForURL('https://dalthaus.net/admin/login');
    }

    // Final auto-login test
    console.log('\nFinal auto-login test...');
    await page.goto('https://dalthaus.net/admin/dashboard');
    await page.waitForLoadState('networkidle');
    testResults.autoLoginWorks = page.url().includes('/admin/dashboard');

    // Print final results
    console.log('\nTest Results:');
    console.log(`  ✓ Login with remember me: ${testResults.loginSuccess ? 'PASSED' : 'FAILED'}`);
    console.log(`  ${testResults.cookieCreated ? '✓' : '✗'} Remember token cookie created: ${testResults.cookieCreated ? 'PASSED' : 'FAILED'}`);
    console.log(`  ${testResults.cookieExpiration === 'Correct (~30 days)' ? '✓' : '✗'} Cookie expiration: ${testResults.cookieExpiration}`);
    console.log(`  ${testResults.cookiePersistsAfterLogout ? '✓' : '✗'} Cookie persists after logout: ${testResults.cookiePersistsAfterLogout ? 'PASSED' : 'FAILED'}`);
    console.log(`  ${testResults.autoLoginWorks ? '✓' : '✗'} Auto-login functionality: ${testResults.autoLoginWorks ? 'PASSED' : 'FAILED'}`);

    const allPassed = testResults.loginSuccess &&
                      testResults.cookieCreated &&
                      testResults.cookieExpiration === 'Correct (~30 days)' &&
                      testResults.cookiePersistsAfterLogout &&
                      testResults.autoLoginWorks;

    console.log('\n' + '='.repeat(60));
    console.log(`OVERALL RESULT: ${allPassed ? '✓ ALL TESTS PASSED' : '✗ SOME TESTS FAILED'}`);
    console.log('='.repeat(60));

    // Assert all conditions for test to pass
    expect(testResults.loginSuccess).toBe(true);
    expect(testResults.cookieCreated).toBe(true);
    expect(testResults.cookiePersistsAfterLogout).toBe(true);
    expect(testResults.autoLoginWorks).toBe(true);
  });
});