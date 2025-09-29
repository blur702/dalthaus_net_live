import { test, expect } from '@playwright/test';

test.describe('Production Site Reordering Tests', () => {
  const baseUrl = 'https://dalthaus.net';
  const adminCredentials = {
    username: 'kevin',
    password: '(130Bpm)'
  };

  test('Complete reordering functionality test on production', async ({ page }) => {
    // Enhanced debugging
    page.on('console', msg => {
      console.log(`[Console ${msg.type()}]: ${msg.text()}`);
    });

    page.on('pageerror', error => {
      console.error('[Page Error]:', error.message);
    });

    page.on('response', response => {
      if (response.url().includes('/admin/') && response.status() >= 400) {
        console.error(`[HTTP ${response.status()}] ${response.url()}`);
      }
      // Log redirects
      if (response.status() === 302 || response.status() === 303) {
        console.log(`[Redirect ${response.status()}] ${response.url()} -> ${response.headers()['location'] || 'unknown'}`);
      }
    });

    // Step 1: Login to admin panel
    console.log('\n=== STEP 1: Logging in to admin panel ===');
    await page.goto(`${baseUrl}/admin/login`, {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    // Take screenshot of login page
    await page.screenshot({
      path: 'screenshots/01-login-page.png',
      fullPage: true
    });

    // Fill and submit login form
    await page.fill('input[name="username"]', adminCredentials.username);
    await page.fill('input[name="password"]', adminCredentials.password);

    // Check if remember me checkbox exists and check it
    const rememberMe = page.locator('input[name="remember_me"]');
    if (await rememberMe.count() > 0) {
      await rememberMe.check();
      console.log('Checked remember me checkbox');
    }

    await page.click('button[type="submit"]');

    // Wait for navigation
    await page.waitForLoadState('networkidle');

    // Verify successful login
    const currentUrl = page.url();
    console.log(`After login, current URL: ${currentUrl}`);

    if (currentUrl.includes('/admin/dashboard')) {
      console.log('✓ Successfully logged in and reached dashboard');
      await page.screenshot({
        path: 'screenshots/02-dashboard.png',
        fullPage: true
      });
    } else if (currentUrl.includes('/admin/login')) {
      console.error('✗ Still on login page - authentication may have failed');
      await page.screenshot({
        path: 'screenshots/02-login-failed.png',
        fullPage: true
      });
      throw new Error('Login failed - still on login page');
    }

    // Step 2: Test Content Reordering
    console.log('\n=== STEP 2: Testing Content Reordering ===');

    // Navigate to content reorder page
    console.log('Navigating to /admin/content/reorder...');
    await page.goto(`${baseUrl}/admin/content/reorder`, {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    // Check where we ended up
    const contentReorderUrl = page.url();
    console.log(`Current URL after navigation: ${contentReorderUrl}`);

    if (contentReorderUrl.includes('/admin/login')) {
      console.error('✗ Redirected to login page - session may have been lost');
      await page.screenshot({
        path: 'screenshots/03-content-reorder-redirect-login.png',
        fullPage: true
      });

      // Try logging in again
      console.log('Attempting to log in again...');
      await page.fill('input[name="username"]', adminCredentials.username);
      await page.fill('input[name="password"]', adminCredentials.password);
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');

      // Try navigating to reorder page again
      await page.goto(`${baseUrl}/admin/content/reorder`, {
        waitUntil: 'networkidle',
        timeout: 30000
      });
    }

    // Take screenshot of content reorder page
    await page.screenshot({
      path: 'screenshots/03-content-reorder.png',
      fullPage: true
    });

    // Verify content reorder page loaded correctly
    const contentReorderLoaded = await page.evaluate(() => {
      const title = document.querySelector('h2');
      const hasReorderTitle = title && title.textContent.includes('Reorder');
      const hasSortable = document.querySelector('#sortable-content') !== null;
      const hasFilter = document.querySelector('#type_filter') !== null;
      return {
        url: window.location.href,
        title: title ? title.textContent : 'No title found',
        hasReorderTitle,
        hasSortable,
        hasFilter,
        bodyContent: document.body.textContent.substring(0, 500)
      };
    });

    console.log('Content Reorder Page Analysis:', JSON.stringify(contentReorderLoaded, null, 2));

    if (contentReorderLoaded.hasReorderTitle) {
      console.log('✓ Content reorder page loaded successfully');
    } else {
      console.error('✗ Content reorder page did not load correctly');
    }

    // Step 3: Test Pages Reordering
    console.log('\n=== STEP 3: Testing Pages Reordering ===');

    // Navigate to pages reorder page
    console.log('Navigating to /admin/pages/reorder...');
    await page.goto(`${baseUrl}/admin/pages/reorder`, {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    // Check where we ended up
    const pagesReorderUrl = page.url();
    console.log(`Current URL after navigation: ${pagesReorderUrl}`);

    if (pagesReorderUrl.includes('/admin/login')) {
      console.error('✗ Redirected to login page - session may have been lost');
      await page.screenshot({
        path: 'screenshots/04-pages-reorder-redirect-login.png',
        fullPage: true
      });

      // Try logging in again
      console.log('Attempting to log in again...');
      await page.fill('input[name="username"]', adminCredentials.username);
      await page.fill('input[name="password"]', adminCredentials.password);
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');

      // Try navigating to reorder page again
      await page.goto(`${baseUrl}/admin/pages/reorder`, {
        waitUntil: 'networkidle',
        timeout: 30000
      });
    }

    // Take screenshot of pages reorder page
    await page.screenshot({
      path: 'screenshots/04-pages-reorder.png',
      fullPage: true
    });

    // Verify pages reorder page loaded correctly
    const pagesReorderLoaded = await page.evaluate(() => {
      const title = document.querySelector('h2');
      const hasReorderTitle = title && title.textContent.includes('Reorder');
      const hasSortable = document.querySelector('#sortable-pages') !== null;
      const hasSaveButton = document.querySelector('button') !== null;
      return {
        url: window.location.href,
        title: title ? title.textContent : 'No title found',
        hasReorderTitle,
        hasSortable,
        hasSaveButton,
        bodyContent: document.body.textContent.substring(0, 500)
      };
    });

    console.log('Pages Reorder Page Analysis:', JSON.stringify(pagesReorderLoaded, null, 2));

    if (pagesReorderLoaded.hasReorderTitle) {
      console.log('✓ Pages reorder page loaded successfully');
    } else {
      console.error('✗ Pages reorder page did not load correctly');
    }

    // Step 4: Check Sortable.js is loaded
    console.log('\n=== STEP 4: Checking Sortable.js Library ===');

    const sortableCheck = await page.evaluate(() => {
      return {
        sortableExists: typeof window.Sortable !== 'undefined',
        jqueryExists: typeof window.$ !== 'undefined' || typeof window.jQuery !== 'undefined',
        jqueryUiExists: typeof window.jQuery !== 'undefined' && typeof window.jQuery.ui !== 'undefined'
      };
    });

    console.log('JavaScript Libraries Check:', JSON.stringify(sortableCheck, null, 2));

    // Step 5: Test navigation from main content/pages management
    console.log('\n=== STEP 5: Testing Reorder Button Navigation ===');

    // Go to content management
    await page.goto(`${baseUrl}/admin/content`, {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    // Look for reorder button
    const contentReorderButton = page.locator('a:has-text("Reorder")').first();
    if (await contentReorderButton.count() > 0) {
      console.log('✓ Found reorder button on content management page');
      await page.screenshot({
        path: 'screenshots/05-content-management-with-reorder.png',
        fullPage: true
      });
    } else {
      console.error('✗ Reorder button not found on content management page');
    }

    // Go to pages management
    await page.goto(`${baseUrl}/admin/pages`, {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    // Look for reorder button
    const pagesReorderButton = page.locator('a:has-text("Reorder")').first();
    if (await pagesReorderButton.count() > 0) {
      console.log('✓ Found reorder button on pages management page');
      await page.screenshot({
        path: 'screenshots/06-pages-management-with-reorder.png',
        fullPage: true
      });
    } else {
      console.error('✗ Reorder button not found on pages management page');
    }

    // Final Summary
    console.log('\n=== TEST SUMMARY ===');
    console.log('Test completed. Check screenshots directory for visual verification.');
    console.log('Key findings:');
    console.log(`- Login successful: ${currentUrl.includes('/admin/dashboard') ? 'Yes' : 'No'}`);
    console.log(`- Content reorder accessible: ${!contentReorderUrl.includes('/admin/login') ? 'Yes' : 'No'}`);
    console.log(`- Pages reorder accessible: ${!pagesReorderUrl.includes('/admin/login') ? 'Yes' : 'No'}`);
    console.log(`- Sortable.js loaded: ${sortableCheck.sortableExists ? 'Yes' : 'No'}`);
  });

  test('Test session persistence across reorder pages', async ({ page }) => {
    console.log('\n=== Testing Session Persistence ===');

    // Login first
    await page.goto(`${baseUrl}/admin/login`);
    await page.fill('input[name="username"]', adminCredentials.username);
    await page.fill('input[name="password"]', adminCredentials.password);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Rapid navigation test
    const pages = [
      '/admin/dashboard',
      '/admin/content',
      '/admin/content/reorder',
      '/admin/pages',
      '/admin/pages/reorder',
      '/admin/content/reorder',
      '/admin/dashboard'
    ];

    for (const pagePath of pages) {
      console.log(`Navigating to ${pagePath}...`);
      await page.goto(`${baseUrl}${pagePath}`, {
        waitUntil: 'domcontentloaded',
        timeout: 30000
      });

      const currentUrl = page.url();
      if (currentUrl.includes('/admin/login')) {
        console.error(`✗ Lost session at ${pagePath} - redirected to login`);
      } else {
        console.log(`✓ Session maintained at ${pagePath}`);
      }
    }
  });
});