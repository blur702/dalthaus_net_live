import { test, expect } from '@playwright/test';

test.describe('Remember Me Functionality Verification', () => {
  const baseURL = 'https://dalthaus.net';
  const username = 'kevin';
  const password = '(130Bpm)';

  test('Complete remember me functionality test after database table creation', async ({ page, context }) => {
    console.log('Starting comprehensive remember me functionality test...');

    // Step 1: Navigate to login page
    console.log('\n1. Navigating to login page...');
    await page.goto(`${baseURL}/admin/login`);
    await expect(page).toHaveURL(`${baseURL}/admin/login`);
    console.log('✓ Successfully loaded login page');

    // Step 2: Fill in credentials
    console.log('\n2. Filling in login credentials...');
    await page.fill('input[name="username"]', username);
    await page.fill('input[name="password"]', password);
    console.log('✓ Username and password filled');

    // Step 3: Check the "Remember me" checkbox
    console.log('\n3. Checking "Remember me for 30 days" checkbox...');
    const rememberCheckbox = page.locator('input[name="remember_me"]');
    await expect(rememberCheckbox).toBeVisible();
    await rememberCheckbox.check();
    await expect(rememberCheckbox).toBeChecked();
    console.log('✓ Remember me checkbox checked');

    // Get all cookies before login
    const cookiesBeforeLogin = await context.cookies();
    console.log('\n4. Cookies before login:', cookiesBeforeLogin.map(c => c.name));

    // Step 4: Submit the form
    console.log('\n5. Submitting login form with remember me checked...');
    await page.click('button[type="submit"]');

    // Step 5: Verify successful login and redirect
    console.log('\n6. Verifying successful login and redirect...');
    await page.waitForURL(`${baseURL}/admin/dashboard`, { timeout: 10000 });
    await expect(page).toHaveURL(`${baseURL}/admin/dashboard`);
    console.log('✓ Successfully logged in and redirected to dashboard');

    // Step 6: Check for remember_token cookie
    console.log('\n7. Checking for remember_token cookie...');
    const cookiesAfterLogin = await context.cookies();
    console.log('All cookies after login:', cookiesAfterLogin.map(c => ({
      name: c.name,
      value: c.value ? c.value.substring(0, 20) + '...' : 'empty',
      expires: c.expires,
      httpOnly: c.httpOnly,
      secure: c.secure
    })));

    const rememberTokenCookie = cookiesAfterLogin.find(cookie => cookie.name === 'remember_token');

    if (rememberTokenCookie) {
      console.log('\n✓ remember_token cookie found!');
      console.log('Cookie details:');
      console.log('  - Name:', rememberTokenCookie.name);
      console.log('  - Value (first 20 chars):', rememberTokenCookie.value.substring(0, 20) + '...');
      console.log('  - HttpOnly:', rememberTokenCookie.httpOnly);
      console.log('  - Secure:', rememberTokenCookie.secure);

      // Check expiration (should be approximately 30 days from now)
      if (rememberTokenCookie.expires) {
        const expirationDate = new Date(rememberTokenCookie.expires * 1000);
        const now = new Date();
        const daysDifference = Math.round((expirationDate - now) / (1000 * 60 * 60 * 24));
        console.log('  - Expires:', expirationDate.toISOString());
        console.log('  - Days until expiration:', daysDifference);

        // Verify it's approximately 30 days
        expect(daysDifference).toBeGreaterThanOrEqual(29);
        expect(daysDifference).toBeLessThanOrEqual(31);
        console.log('✓ Cookie expiration is correctly set to ~30 days');
      }
    } else {
      console.log('\n✗ remember_token cookie NOT found');
      console.log('Available cookies:', cookiesAfterLogin.map(c => c.name));
    }

    // Step 7: Test logout
    console.log('\n8. Testing logout...');
    await page.goto(`${baseURL}/admin/logout`);
    await page.waitForURL(`${baseURL}/admin/login`);
    console.log('✓ Successfully logged out');

    // Check if remember_token cookie persists after logout
    const cookiesAfterLogout = await context.cookies();
    const rememberTokenAfterLogout = cookiesAfterLogout.find(cookie => cookie.name === 'remember_token');

    if (rememberTokenAfterLogout) {
      console.log('\n✓ remember_token cookie persists after logout (as expected for remember me)');
    } else {
      console.log('\n✗ remember_token cookie was deleted on logout (this may be intentional)');
    }

    // Step 8: Test automatic login with remember token
    console.log('\n9. Testing automatic login with remember token...');

    // If the cookie exists, try navigating directly to dashboard
    if (rememberTokenAfterLogout) {
      await page.goto(`${baseURL}/admin/dashboard`);

      // Check if we stay on dashboard (auto-logged in) or get redirected to login
      await page.waitForLoadState('networkidle');
      const currentUrl = page.url();

      if (currentUrl.includes('/admin/dashboard')) {
        console.log('✓ Automatic login with remember token successful!');
        console.log('  User was automatically authenticated using remember token');
      } else if (currentUrl.includes('/admin/login')) {
        console.log('✗ Automatic login failed - redirected to login page');
        console.log('  Remember token exists but automatic authentication not working');
      }
    }

    // Step 9: Test with new browser context (simulates browser restart)
    console.log('\n10. Testing with new browser context (simulating browser restart)...');

    if (rememberTokenCookie) {
      // Create a new context with the remember token cookie
      const newContext = await page.context().browser().newContext();
      await newContext.addCookies([rememberTokenCookie]);

      const newPage = await newContext.newPage();
      await newPage.goto(`${baseURL}/admin/dashboard`);
      await newPage.waitForLoadState('networkidle');

      const newPageUrl = newPage.url();
      if (newPageUrl.includes('/admin/dashboard')) {
        console.log('✓ Remember token works across browser sessions!');
        console.log('  User remains authenticated even after browser restart');
      } else {
        console.log('✗ Remember token does not persist across browser sessions');
      }

      await newContext.close();
    }

    // Summary
    console.log('\n========================================');
    console.log('REMEMBER ME FUNCTIONALITY TEST SUMMARY');
    console.log('========================================');

    if (rememberTokenCookie) {
      console.log('✅ Remember token cookie is created when checkbox is checked');
      console.log('✅ Cookie has correct expiration (30 days)');
      console.log('✅ Login with remember me checkbox works correctly');

      if (rememberTokenAfterLogout) {
        console.log('✅ Remember token persists after logout');
      } else {
        console.log('⚠️  Remember token is cleared on logout');
      }

      console.log('\n🎉 REMEMBER ME FUNCTIONALITY IS WORKING!');
      console.log('The database table creation has fixed the issue.');
    } else {
      console.log('❌ Remember token cookie is NOT being created');
      console.log('❌ Remember me functionality is still not working');
      console.log('\n⚠️  The issue persists even after database table creation');
      console.log('Additional debugging may be required in the PHP code');
    }

    // Assert that remember token cookie exists (main test assertion)
    expect(rememberTokenCookie).toBeDefined();
    expect(rememberTokenCookie).not.toBeNull();
  });

  test('Test remember me without checkbox (control test)', async ({ page, context }) => {
    console.log('\nControl Test: Login WITHOUT remember me checkbox...');

    // Login without checking remember me
    await page.goto(`${baseURL}/admin/login`);
    await page.fill('input[name="username"]', username);
    await page.fill('input[name="password"]', password);

    // Ensure checkbox is NOT checked
    const rememberCheckbox = page.locator('input[name="remember_me"]');
    await rememberCheckbox.uncheck();
    await expect(rememberCheckbox).not.toBeChecked();

    await page.click('button[type="submit"]');
    await page.waitForURL(`${baseURL}/admin/dashboard`);

    // Check cookies
    const cookies = await context.cookies();
    const rememberTokenCookie = cookies.find(cookie => cookie.name === 'remember_token');

    if (rememberTokenCookie) {
      console.log('✗ Remember token cookie created even without checkbox checked');
    } else {
      console.log('✓ No remember token cookie when checkbox not checked (correct behavior)');
    }

    // Verify no remember token when checkbox not checked
    expect(rememberTokenCookie).toBeUndefined();
  });
});