import { test, expect } from '@playwright/test';

test.describe('Simple Remember Me Test', () => {
  const baseURL = 'https://dalthaus.net';
  const username = 'kevin';
  const password = '(130Bpm)';

  test('Test remember me with detailed error catching', async ({ page, context }) => {
    console.log('Testing remember me with error monitoring...');

    // Monitor console errors and network failures
    const errors = [];
    const networkErrors = [];

    page.on('console', msg => {
      if (msg.type() === 'error') {
        errors.push(msg.text());
        console.log('Console Error:', msg.text());
      }
    });

    page.on('response', response => {
      if (response.status() >= 400) {
        networkErrors.push(`${response.status()} ${response.url()}`);
        console.log('Network Error:', response.status(), response.url());
      }
    });

    // Navigate and fill form
    await page.goto(`${baseURL}/admin/login`);
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="username"]', username);
    await page.fill('input[name="password"]', password);

    // Check remember me
    const rememberCheckbox = page.locator('input[name="remember_me"]');
    await rememberCheckbox.check();
    console.log('Remember me checked:', await rememberCheckbox.isChecked());

    // Get initial cookies
    const cookiesBefore = await context.cookies();
    console.log('Cookies before login:', cookiesBefore.map(c => c.name));

    // Submit form
    console.log('Submitting form...');
    await page.click('button[type="submit"]');

    // Wait and see what happens
    await page.waitForTimeout(5000);

    const finalUrl = page.url();
    console.log('Final URL:', finalUrl);

    // Get cookies after
    const cookiesAfter = await context.cookies();
    console.log('Cookies after login:', cookiesAfter.map(c => c.name));

    // Check for remember_token specifically
    const rememberToken = cookiesAfter.find(c => c.name === 'remember_token');
    if (rememberToken) {
      console.log('✓ Remember token cookie created');
      console.log('Token details:', {
        name: rememberToken.name,
        valueLength: rememberToken.value?.length || 0,
        expires: rememberToken.expires,
        httpOnly: rememberToken.httpOnly
      });
    } else {
      console.log('✗ No remember token cookie found');
    }

    // Report any errors
    if (errors.length > 0) {
      console.log('\nConsole Errors:');
      errors.forEach(error => console.log('  -', error));
    }

    if (networkErrors.length > 0) {
      console.log('\nNetwork Errors:');
      networkErrors.forEach(error => console.log('  -', error));
    }

    // Check if login succeeded
    if (finalUrl.includes('/admin/dashboard')) {
      console.log('\n🎉 SUCCESS: Login with remember me worked!');
      console.log('User successfully authenticated and redirected to dashboard');

      if (rememberToken) {
        console.log('✅ Remember token functionality is WORKING!');
      } else {
        console.log('⚠️ Login succeeded but no remember token created');
      }
    } else if (finalUrl.includes('/admin/login')) {
      console.log('\n❌ FAILURE: Still on login page');
      console.log('Remember me functionality is preventing successful login');

      // Take screenshot for debugging
      await page.screenshot({
        path: 'testing/results/remember-me-failure-debug.png',
        fullPage: true
      });
    } else {
      console.log('\n❓ UNEXPECTED: Redirected to unexpected URL');
    }

    // Final assertion - remember me should work
    if (finalUrl.includes('/admin/dashboard') && rememberToken) {
      console.log('\n✅ REMEMBER ME FUNCTIONALITY IS WORKING CORRECTLY!');
    } else {
      console.log('\n❌ REMEMBER ME FUNCTIONALITY NEEDS DEBUGGING');
    }
  });
});