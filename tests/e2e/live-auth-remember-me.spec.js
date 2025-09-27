import { test, expect, chromium } from '@playwright/test';

test.describe('Live Site - Remember Me Authentication Workflow', () => {
  const baseURL = 'https://dalthaus.net';
  const adminCredentials = {
    username: 'kevin',
    password: '(130Bpm)'
  };

  test('Complete remember me authentication workflow', async ({ page, context }) => {
    console.log('Starting comprehensive remember me authentication test...\n');

    // Step 1: Clear browser state
    console.log('Step 1: Clearing browser state...');
    await context.clearCookies();
    await page.goto(baseURL);
    console.log('✓ Browser state cleared\n');

    // Step 2: Navigate to login
    console.log('Step 2: Navigating to login page...');
    await page.goto(`${baseURL}/admin/login`);
    await expect(page).toHaveURL(`${baseURL}/admin/login`);
    console.log(`✓ Reached login page: ${page.url()}\n`);

    // Take screenshot of login page
    await page.screenshot({
      path: 'test-results/01-login-page.png',
      fullPage: true
    });

    // Step 3: Login with remember me checked
    console.log('Step 3: Logging in with "Remember me" checked...');

    // Fill in credentials
    await page.fill('input[name="username"]', adminCredentials.username);
    await page.fill('input[name="password"]', adminCredentials.password);

    // Check the remember me checkbox
    const rememberCheckbox = page.locator('input[name="remember_me"]');
    await rememberCheckbox.check();
    await expect(rememberCheckbox).toBeChecked();
    console.log('✓ Credentials entered and "Remember me" checkbox checked');

    // Take screenshot before login
    await page.screenshot({
      path: 'test-results/02-before-login.png',
      fullPage: true
    });

    // Submit login form
    await page.click('button[type="submit"]');

    // Wait for navigation and check URL
    await page.waitForLoadState('networkidle');

    // Step 4: Verify successful login
    console.log('\nStep 4: Verifying successful login...');
    const currentUrl = page.url();

    if (currentUrl.includes('/admin/dashboard') || currentUrl.includes('/admin')) {
      console.log(`✓ Successfully logged in and reached admin area: ${currentUrl}`);
    } else if (currentUrl.includes('/admin/login')) {
      console.log(`✗ Login failed - still on login page: ${currentUrl}`);
      const errorMessage = await page.locator('.alert-danger, .error').textContent().catch(() => 'No error message found');
      console.log(`  Error message: ${errorMessage}`);

      // Take screenshot of failed login
      await page.screenshot({
        path: 'test-results/03-login-failed.png',
        fullPage: true
      });

      throw new Error('Login failed - unable to proceed with test');
    } else {
      console.log(`⚠ Unexpected redirect after login: ${currentUrl}`);
    }

    // Take screenshot of dashboard
    await page.screenshot({
      path: 'test-results/03-dashboard.png',
      fullPage: true
    });

    // Check for cookies (especially remember me cookie)
    const cookies = await context.cookies();
    const rememberCookie = cookies.find(c => c.name === 'remember_token' || c.name.includes('remember'));
    const sessionCookie = cookies.find(c => c.name === 'cms_session' || c.name === 'PHPSESSID');

    console.log('\nCookie Status:');
    console.log(`  Session cookie found: ${sessionCookie ? 'Yes' : 'No'}`);
    console.log(`  Remember cookie found: ${rememberCookie ? 'Yes' : 'No'}`);
    if (rememberCookie) {
      const expiryDate = new Date(rememberCookie.expires * 1000);
      console.log(`  Remember cookie expires: ${expiryDate.toLocaleString()}`);
    }

    // Step 5: Test Articles navigation
    console.log('\nStep 5: Testing navigation to Articles page...');

    // Look for Articles link in navigation
    const articlesLink = page.locator('a:has-text("Articles"), nav a:has-text("Articles"), .nav a:has-text("Articles")').first();

    if (await articlesLink.count() > 0) {
      await articlesLink.click();
      await page.waitForLoadState('networkidle');

      const afterNavUrl = page.url();
      console.log(`  Navigated to: ${afterNavUrl}`);

      // Step 6: Verify no redirect
      if (afterNavUrl.includes('/admin/login')) {
        console.log('✗ ERROR: Got redirected to login page after clicking Articles!');
        console.log('  This indicates session was lost');

        // Take screenshot of unexpected redirect
        await page.screenshot({
          path: 'test-results/04-redirect-error.png',
          fullPage: true
        });
      } else if (afterNavUrl.includes('/admin/content') || afterNavUrl.includes('articles')) {
        console.log('✓ Successfully navigated to Articles without logout');

        // Take screenshot of articles page
        await page.screenshot({
          path: 'test-results/04-articles-page.png',
          fullPage: true
        });
      } else {
        console.log(`⚠ Unexpected page after Articles click: ${afterNavUrl}`);
      }
    } else {
      console.log('⚠ Articles link not found in navigation');
      console.log('  Available links:');
      const allLinks = await page.locator('nav a, .nav a').allTextContents();
      allLinks.forEach(link => console.log(`    - ${link}`));
    }

    // Step 7: Test session persistence with refresh
    console.log('\nStep 7: Testing session persistence across page refresh...');
    await page.reload();
    await page.waitForLoadState('networkidle');

    const afterRefreshUrl = page.url();
    if (afterRefreshUrl.includes('/admin/login')) {
      console.log('✗ Session lost after refresh - redirected to login');
    } else if (afterRefreshUrl.includes('/admin')) {
      console.log('✓ Session persisted after refresh - still in admin area');
    } else {
      console.log(`⚠ Unexpected state after refresh: ${afterRefreshUrl}`);
    }

    // Take screenshot after refresh
    await page.screenshot({
      path: 'test-results/05-after-refresh.png',
      fullPage: true
    });

    // Additional navigation test - try multiple admin pages
    console.log('\nAdditional Navigation Tests:');

    const adminPages = [
      { name: 'Dashboard', url: '/admin/dashboard' },
      { name: 'Content', url: '/admin/content' },
      { name: 'Pages', url: '/admin/pages' }
    ];

    for (const adminPage of adminPages) {
      console.log(`  Testing navigation to ${adminPage.name}...`);
      await page.goto(`${baseURL}${adminPage.url}`);
      await page.waitForLoadState('networkidle');

      const pageUrl = page.url();
      if (pageUrl.includes('/admin/login')) {
        console.log(`    ✗ Redirected to login when accessing ${adminPage.name}`);
      } else if (pageUrl.includes(adminPage.url)) {
        console.log(`    ✓ Successfully accessed ${adminPage.name}`);
      } else {
        console.log(`    ⚠ Unexpected redirect: ${pageUrl}`);
      }
    }

    // Final summary
    console.log('\n' + '='.repeat(60));
    console.log('TEST SUMMARY');
    console.log('='.repeat(60));
    console.log(`Login with Remember Me: ${currentUrl.includes('/admin') ? 'SUCCESS' : 'FAILED'}`);
    console.log(`Session Cookie Present: ${sessionCookie ? 'YES' : 'NO'}`);
    console.log(`Remember Cookie Present: ${rememberCookie ? 'YES' : 'NO'}`);
    console.log(`Navigation Without Logout: ${!afterNavUrl.includes('/admin/login') ? 'SUCCESS' : 'FAILED'}`);
    console.log(`Session Persistence: ${!afterRefreshUrl.includes('/admin/login') ? 'SUCCESS' : 'FAILED'}`);
    console.log('='.repeat(60));
  });

  test('Remember me functionality after browser restart', async ({ browser }) => {
    console.log('\nStep 8: Testing remember me after browser restart...\n');

    // First, login with remember me
    const context1 = await browser.newContext();
    const page1 = await context1.newPage();

    console.log('Initial login with remember me...');
    await page1.goto(`${baseURL}/admin/login`);
    await page1.fill('input[name="username"]', adminCredentials.username);
    await page1.fill('input[name="password"]', adminCredentials.password);
    await page1.check('input[name="remember_me"]');
    await page1.click('button[type="submit"]');
    await page1.waitForLoadState('networkidle');

    // Save cookies
    const cookies = await context1.cookies();
    const rememberCookie = cookies.find(c => c.name === 'remember_token' || c.name.includes('remember'));

    if (rememberCookie) {
      console.log('✓ Remember cookie created');
      console.log(`  Cookie name: ${rememberCookie.name}`);
      console.log(`  Expires: ${new Date(rememberCookie.expires * 1000).toLocaleString()}`);
    } else {
      console.log('✗ No remember cookie found');
    }

    // Close first context (simulating browser close)
    await context1.close();
    console.log('Browser context closed\n');

    // Create new context with saved cookies (simulating browser restart)
    const context2 = await browser.newContext();

    // Add the remember cookie to new context
    if (rememberCookie) {
      await context2.addCookies([rememberCookie]);
      console.log('Remember cookie restored to new browser context');
    }

    const page2 = await context2.newPage();

    // Navigate directly to admin area
    console.log('Navigating directly to admin dashboard...');
    await page2.goto(`${baseURL}/admin/dashboard`);
    await page2.waitForLoadState('networkidle');

    const finalUrl = page2.url();

    if (finalUrl.includes('/admin/dashboard')) {
      console.log('✓ Remember me WORKS! Stayed logged in after browser restart');
      console.log(`  Current URL: ${finalUrl}`);
    } else if (finalUrl.includes('/admin/login')) {
      console.log('✗ Remember me FAILED - redirected to login after browser restart');
      console.log(`  Current URL: ${finalUrl}`);

      // Check if there's a message about expired session
      const message = await page2.locator('.alert, .message').textContent().catch(() => '');
      if (message) {
        console.log(`  Message on page: ${message}`);
      }
    } else {
      console.log(`⚠ Unexpected redirect: ${finalUrl}`);
    }

    // Take final screenshot
    await page2.screenshot({
      path: 'test-results/06-remember-me-test.png',
      fullPage: true
    });

    await context2.close();

    console.log('\n' + '='.repeat(60));
    console.log('REMEMBER ME TEST COMPLETE');
    console.log('='.repeat(60));
  });
});