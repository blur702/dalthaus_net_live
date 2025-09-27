import { test, expect } from '@playwright/test';

test.describe('Remember Me Database Investigation', () => {
  const baseURL = 'https://dalthaus.net';
  const username = 'kevin';
  const password = '(130Bpm)';

  test('Test remember me with detailed analysis', async ({ page, context }) => {
    console.log('Starting detailed remember me database analysis...');

    // Step 1: First verify basic login works
    console.log('\n=== STEP 1: Verify basic login works ===');
    await page.goto(`${baseURL}/admin/login`);
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="username"]', username);
    await page.fill('input[name="password"]', password);

    // Ensure remember me is NOT checked
    const rememberCheckbox = page.locator('input[name="remember_me"]');
    await rememberCheckbox.uncheck();
    await expect(rememberCheckbox).not.toBeChecked();

    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);

    if (page.url().includes('/admin/dashboard')) {
      console.log('✅ Basic login (without remember me) works correctly');
    } else {
      console.log('❌ Basic login failed - stopping test');
      return;
    }

    // Logout to reset state
    await page.goto(`${baseURL}/admin/logout`);
    await page.waitForURL(`${baseURL}/admin/login`);
    console.log('✅ Logged out successfully');

    // Step 2: Test remember me functionality
    console.log('\n=== STEP 2: Test remember me functionality ===');
    await page.goto(`${baseURL}/admin/login`);
    await page.waitForLoadState('networkidle');

    // Monitor all network requests
    const requests = [];
    const responses = [];

    page.on('request', request => {
      requests.push({
        url: request.url(),
        method: request.method(),
        postData: request.postData()
      });
    });

    page.on('response', response => {
      responses.push({
        url: response.url(),
        status: response.status(),
        headers: response.headers()
      });
    });

    // Fill credentials and check remember me
    await page.fill('input[name="username"]', username);
    await page.fill('input[name="password"]', password);
    await rememberCheckbox.check();
    await expect(rememberCheckbox).toBeChecked();

    console.log('Form filled with remember me checked');

    // Submit and analyze
    console.log('Submitting form with remember me...');
    await page.click('button[type="submit"]');

    // Wait for potential redirect or error
    await page.waitForTimeout(5000);

    console.log('\n=== NETWORK ANALYSIS ===');

    // Find the login POST request
    const loginRequest = requests.find(r =>
      r.url.includes('/admin/login') && r.method === 'POST'
    );

    if (loginRequest) {
      console.log('Login POST request found:');
      console.log('  URL:', loginRequest.url);
      console.log('  Method:', loginRequest.method);
      if (loginRequest.postData) {
        // Don't log password, but show if remember_me is included
        const hasRememberMe = loginRequest.postData.includes('remember_me');
        console.log('  Has remember_me parameter:', hasRememberMe);
      }
    }

    // Find the corresponding response
    const loginResponse = responses.find(r =>
      r.url.includes('/admin/login') && r.status
    );

    if (loginResponse) {
      console.log('Login response:');
      console.log('  Status:', loginResponse.status);
      console.log('  Location header:', loginResponse.headers.location || 'None');
    }

    // Check final state
    const finalUrl = page.url();
    console.log('\n=== FINAL STATE ===');
    console.log('Final URL:', finalUrl);

    const cookies = await context.cookies();
    console.log('Final cookies:', cookies.map(c => c.name));

    const rememberToken = cookies.find(c => c.name === 'remember_token');

    if (finalUrl.includes('/admin/dashboard')) {
      console.log('🎉 SUCCESS: Login with remember me succeeded!');

      if (rememberToken) {
        console.log('✅ Remember token cookie created successfully');
        console.log('Token details:', {
          expires: new Date(rememberToken.expires * 1000).toISOString(),
          httpOnly: rememberToken.httpOnly,
          secure: rememberToken.secure
        });
      } else {
        console.log('⚠️ Login succeeded but no remember token cookie');
      }
    } else if (finalUrl.includes('/admin/login')) {
      console.log('❌ FAILED: Still on login page');
      console.log('Remember me functionality is blocking login');

      // Check if there are any error elements visible
      const errorElements = await page.locator('.error, .alert-danger, [class*="error"]').all();
      if (errorElements.length > 0) {
        console.log('Visible errors:');
        for (const element of errorElements) {
          const text = await element.textContent();
          if (text && text.trim()) {
            console.log('  -', text.trim());
          }
        }
      }

      // Take a screenshot for debugging
      await page.screenshot({
        path: 'testing/results/remember-me-failure-analysis.png',
        fullPage: true
      });
    }

    // Step 3: If remember me failed, test if it's a database issue
    if (finalUrl.includes('/admin/login')) {
      console.log('\n=== STEP 3: Database connectivity test ===');

      // Try to navigate to a page that would test database connectivity
      await page.goto(`${baseURL}/admin/dashboard`);
      await page.waitForTimeout(2000);

      if (page.url().includes('/admin/login')) {
        console.log('Redirected to login - not authenticated (expected)');
      } else {
        console.log('Unexpected behavior in database connectivity test');
      }
    }

    // Final assessment
    console.log('\n=== ASSESSMENT ===');
    if (finalUrl.includes('/admin/dashboard') && rememberToken) {
      console.log('✅ REMEMBER ME FUNCTIONALITY IS WORKING!');
      console.log('✅ Database table creation was successful');
      console.log('✅ Cookie is being set correctly');
    } else if (finalUrl.includes('/admin/dashboard') && !rememberToken) {
      console.log('⚠️ Login works but remember token not created');
      console.log('❓ Possible issue: Database insertion failing silently');
    } else {
      console.log('❌ REMEMBER ME FUNCTIONALITY IS BROKEN');
      console.log('❓ Likely cause: Exception in token storage preventing login');
      console.log('❓ Recommendation: Check PHP error logs on server');
    }
  });
});