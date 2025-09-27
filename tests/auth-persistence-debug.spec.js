const { test, expect } = require('@playwright/test');
const fs = require('fs').promises;
const path = require('path');

test.describe('Authentication Persistence Debug - Live Server', () => {
  test('Complete authentication flow analysis', async ({ page, context }) => {
    const debugData = {
      timestamp: new Date().toISOString(),
      testServer: 'https://dalthaus.net',
      steps: []
    };

    // Enable detailed request/response logging
    const networkLogs = [];

    page.on('request', request => {
      if (request.url().includes('/admin')) {
        networkLogs.push({
          type: 'request',
          timestamp: new Date().toISOString(),
          method: request.method(),
          url: request.url(),
          headers: request.headers(),
          postData: request.postData()
        });
      }
    });

    page.on('response', response => {
      if (response.url().includes('/admin')) {
        networkLogs.push({
          type: 'response',
          timestamp: new Date().toISOString(),
          url: response.url(),
          status: response.status(),
          statusText: response.statusText(),
          headers: response.headers()
        });
      }
    });

    // Log console messages
    const consoleLogs = [];
    page.on('console', msg => {
      consoleLogs.push({
        timestamp: new Date().toISOString(),
        type: msg.type(),
        text: msg.text()
      });
    });

    // Step 1: Navigate to login page
    console.log('Step 1: Navigating to login page...');
    await page.goto('https://dalthaus.net/admin/login', {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    // Capture initial state
    const initialCookies = await context.cookies();
    debugData.steps.push({
      step: 1,
      action: 'Navigate to login page',
      url: page.url(),
      cookies: initialCookies.map(c => ({
        name: c.name,
        value: c.value.substring(0, 20) + '...',
        domain: c.domain,
        path: c.path,
        httpOnly: c.httpOnly,
        secure: c.secure,
        sameSite: c.sameSite
      }))
    });

    await page.screenshot({
      path: 'debug-screenshots/01-login-page.png',
      fullPage: true
    });

    // Step 2: Login without remember me
    console.log('Step 2: Logging in without remember me...');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');

    // Ensure remember me is NOT checked
    const rememberCheckbox = await page.locator('input[name="remember_me"]');
    if (await rememberCheckbox.isChecked()) {
      await rememberCheckbox.uncheck();
    }

    // Intercept the login response
    const loginResponsePromise = page.waitForResponse(
      response => response.url().includes('/admin/login') && response.request().method() === 'POST',
      { timeout: 30000 }
    );

    await page.click('button[type="submit"]');

    const loginResponse = await loginResponsePromise;

    // Wait for navigation
    await page.waitForURL('**/admin/dashboard', {
      timeout: 30000,
      waitUntil: 'networkidle'
    });

    // Step 3: Verify dashboard access
    console.log('Step 3: Verifying dashboard access...');
    const dashboardCookies = await context.cookies();
    const dashboardUrl = page.url();

    debugData.steps.push({
      step: 2,
      action: 'Login and redirect to dashboard',
      loginResponseStatus: loginResponse.status(),
      loginResponseHeaders: loginResponse.headers(),
      currentUrl: dashboardUrl,
      cookiesAfterLogin: dashboardCookies.map(c => ({
        name: c.name,
        value: c.value.substring(0, 20) + '...',
        domain: c.domain,
        path: c.path,
        httpOnly: c.httpOnly,
        secure: c.secure,
        sameSite: c.sameSite,
        expires: c.expires
      }))
    });

    await page.screenshot({
      path: 'debug-screenshots/02-dashboard.png',
      fullPage: true
    });

    // Find session cookies
    const sessionCookie = dashboardCookies.find(c =>
      c.name === 'cms_session' || c.name === 'PHPSESSID' || c.name.includes('session')
    );

    console.log('Session cookie found:', sessionCookie ? sessionCookie.name : 'NONE');

    // Step 4: Click on Articles menu item
    console.log('Step 4: Clicking on Articles menu item...');

    // Clear network logs for this specific action
    networkLogs.length = 0;

    // Set up response interceptor for the Articles click
    const articlesResponsePromise = page.waitForResponse(
      response => response.url().includes('/admin'),
      { timeout: 30000 }
    ).catch(e => null);

    // Try to click Articles link
    const articlesLink = await page.locator('a:has-text("Articles")').first();
    const articlesHref = await articlesLink.getAttribute('href');

    console.log('Articles link href:', articlesHref);

    // Capture cookies before click
    const cookiesBeforeClick = await context.cookies();

    await articlesLink.click();

    // Wait a bit for any redirects
    await page.waitForTimeout(3000);

    const articlesResponse = await articlesResponsePromise;

    // Capture state after click
    const cookiesAfterClick = await context.cookies();
    const urlAfterClick = page.url();

    debugData.steps.push({
      step: 3,
      action: 'Click Articles menu item',
      articlesHref: articlesHref,
      cookiesBeforeClick: cookiesBeforeClick.map(c => ({
        name: c.name,
        value: c.value.substring(0, 20) + '...',
        domain: c.domain,
        path: c.path
      })),
      cookiesAfterClick: cookiesAfterClick.map(c => ({
        name: c.name,
        value: c.value.substring(0, 20) + '...',
        domain: c.domain,
        path: c.path
      })),
      urlAfterClick: urlAfterClick,
      responseStatus: articlesResponse ? articlesResponse.status() : 'No response captured',
      responseHeaders: articlesResponse ? articlesResponse.headers() : {},
      networkRequests: networkLogs.filter(l => l.type === 'request'),
      networkResponses: networkLogs.filter(l => l.type === 'response')
    });

    await page.screenshot({
      path: 'debug-screenshots/03-after-articles-click.png',
      fullPage: true
    });

    // Step 5: Check if we're still authenticated
    console.log('Step 5: Checking authentication status...');
    const isOnLoginPage = page.url().includes('/admin/login');
    const isOnArticlesPage = page.url().includes('/admin/content') || page.url().includes('/admin/articles');

    debugData.steps.push({
      step: 4,
      action: 'Authentication check after Articles click',
      isOnLoginPage: isOnLoginPage,
      isOnArticlesPage: isOnArticlesPage,
      currentUrl: page.url(),
      pageTitle: await page.title()
    });

    // Step 6: Test other menu items
    console.log('Step 6: Testing other menu items...');

    // If we're back on login, log in again
    if (isOnLoginPage) {
      console.log('Redirected to login! Logging in again to test other items...');
      await page.fill('input[name="username"]', 'kevin');
      await page.fill('input[name="password"]', '(130Bpm)');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/admin/dashboard', { timeout: 30000 });
    }

    // Test other menu items
    const menuItems = ['Content', 'Pages', 'Users', 'Settings'];

    for (const menuItem of menuItems) {
      console.log(`Testing ${menuItem} menu item...`);

      // Clear network logs
      networkLogs.length = 0;

      const link = await page.locator(`a:has-text("${menuItem}")`).first();
      const href = await link.getAttribute('href').catch(() => 'not found');

      if (href !== 'not found') {
        const beforeUrl = page.url();
        const beforeCookies = await context.cookies();

        await link.click();
        await page.waitForTimeout(2000);

        const afterUrl = page.url();
        const afterCookies = await context.cookies();

        debugData.steps.push({
          step: 5 + menuItems.indexOf(menuItem),
          action: `Click ${menuItem} menu item`,
          href: href,
          beforeUrl: beforeUrl,
          afterUrl: afterUrl,
          redirectedToLogin: afterUrl.includes('/admin/login'),
          cookieCountBefore: beforeCookies.length,
          cookieCountAfter: afterCookies.length,
          networkRequests: networkLogs.filter(l => l.type === 'request').length,
          networkResponses: networkLogs.filter(l => l.type === 'response').length
        });

        await page.screenshot({
          path: `debug-screenshots/0${6 + menuItems.indexOf(menuItem)}-${menuItem.toLowerCase()}.png`,
          fullPage: true
        });

        // If redirected to login, log back in for next test
        if (afterUrl.includes('/admin/login')) {
          console.log(`${menuItem} redirected to login! Logging in again...`);
          await page.fill('input[name="username"]', 'kevin');
          await page.fill('input[name="password"]', '(130Bpm)');
          await page.click('button[type="submit"]');
          await page.waitForURL('**/admin/dashboard', { timeout: 30000 });
        }
      }
    }

    // Step 7: Direct URL access test
    console.log('Step 7: Testing direct URL access...');

    // Try direct access to admin/content
    networkLogs.length = 0;
    await page.goto('https://dalthaus.net/admin/content', {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    const directAccessUrl = page.url();
    const directAccessCookies = await context.cookies();

    debugData.steps.push({
      step: 10,
      action: 'Direct URL access to /admin/content',
      attemptedUrl: 'https://dalthaus.net/admin/content',
      resultUrl: directAccessUrl,
      redirectedToLogin: directAccessUrl.includes('/admin/login'),
      cookies: directAccessCookies.map(c => ({
        name: c.name,
        domain: c.domain,
        path: c.path
      })),
      networkLogs: networkLogs
    });

    await page.screenshot({
      path: 'debug-screenshots/10-direct-access.png',
      fullPage: true
    });

    // Step 8: Analyze session persistence
    console.log('Step 8: Analyzing session persistence...');

    const finalCookies = await context.cookies();
    const sessionAnalysis = {
      totalCookies: finalCookies.length,
      sessionCookies: finalCookies.filter(c =>
        c.name.toLowerCase().includes('session') ||
        c.name === 'PHPSESSID'
      ).map(c => ({
        name: c.name,
        domain: c.domain,
        path: c.path,
        secure: c.secure,
        httpOnly: c.httpOnly,
        sameSite: c.sameSite,
        expires: c.expires
      })),
      authCookies: finalCookies.filter(c =>
        c.name.toLowerCase().includes('auth') ||
        c.name.toLowerCase().includes('token')
      ).map(c => ({
        name: c.name,
        domain: c.domain,
        path: c.path
      }))
    };

    debugData.sessionAnalysis = sessionAnalysis;
    debugData.consoleLogs = consoleLogs;

    // Save debug data
    await fs.mkdir('debug-screenshots', { recursive: true });
    await fs.writeFile(
      'debug-data.json',
      JSON.stringify(debugData, null, 2)
    );

    // Generate summary report
    const report = `
# Authentication Persistence Debug Report
Generated: ${new Date().toISOString()}
Server: https://dalthaus.net

## Summary
- Initial Login: ${debugData.steps[1].currentUrl.includes('dashboard') ? 'SUCCESS' : 'FAILED'}
- Articles Navigation: ${debugData.steps[2].urlAfterClick.includes('login') ? 'FAILED - Redirected to login' : 'SUCCESS'}
- Direct URL Access: ${debugData.steps[debugData.steps.length - 1].redirectedToLogin ? 'FAILED - Redirected to login' : 'SUCCESS'}

## Session Analysis
- Total Cookies: ${sessionAnalysis.totalCookies}
- Session Cookies Found: ${sessionAnalysis.sessionCookies.length}
- Auth Cookies Found: ${sessionAnalysis.authCookies.length}

## Navigation Test Results
${debugData.steps.filter(s => s.action && s.action.includes('Click')).map(s =>
  `- ${s.action}: ${s.redirectedToLogin ? '❌ REDIRECTED TO LOGIN' : '✓ Success'}`
).join('\n')}

## Network Analysis
Total Requests Logged: ${networkLogs.length}

## Console Logs
Total Console Messages: ${consoleLogs.length}
${consoleLogs.filter(l => l.type === 'error').map(l => `ERROR: ${l.text}`).join('\n')}

## Detailed Debug Data
See debug-data.json for complete network traces and response headers.
    `;

    await fs.writeFile('debug-report.md', report);

    console.log('\n' + report);

    // Assertions for test validation
    expect(debugData.steps[1].currentUrl).toContain('/admin/dashboard');

    // Check if Articles click maintains authentication
    const articlesClickStep = debugData.steps.find(s => s.action === 'Click Articles menu item');
    if (articlesClickStep) {
      console.log('\nArticles Click Analysis:');
      console.log('- Redirected to login:', articlesClickStep.urlAfterClick.includes('/admin/login'));
      console.log('- Response status:', articlesClickStep.responseStatus);
      console.log('- Network requests:', articlesClickStep.networkRequests?.length || 0);
    }
  });
});

// Additional focused test for session cookie analysis
test('Session cookie deep analysis', async ({ page, context }) => {
  console.log('Starting deep session cookie analysis...');

  // Login first
  await page.goto('https://dalthaus.net/admin/login');
  await page.fill('input[name="username"]', 'kevin');
  await page.fill('input[name="password"]', '(130Bpm)');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/admin/dashboard', { timeout: 30000 });

  // Get all cookies
  const cookies = await context.cookies();

  console.log('\n=== COOKIE ANALYSIS ===');
  console.log(`Total cookies: ${cookies.length}`);

  for (const cookie of cookies) {
    console.log(`\nCookie: ${cookie.name}`);
    console.log(`  Domain: ${cookie.domain}`);
    console.log(`  Path: ${cookie.path}`);
    console.log(`  Secure: ${cookie.secure}`);
    console.log(`  HttpOnly: ${cookie.httpOnly}`);
    console.log(`  SameSite: ${cookie.sameSite}`);
    console.log(`  Expires: ${cookie.expires ? new Date(cookie.expires * 1000).toISOString() : 'Session'}`);

    if (cookie.name.toLowerCase().includes('session') || cookie.name === 'PHPSESSID') {
      console.log('  >>> This is a SESSION cookie');
    }
  }

  // Test cookie persistence across navigation
  console.log('\n=== TESTING COOKIE PERSISTENCE ===');

  // Navigate to Articles
  await page.goto('https://dalthaus.net/admin/content', { waitUntil: 'networkidle' });
  const afterNavCookies = await context.cookies();

  console.log(`\nCookies after navigation: ${afterNavCookies.length}`);
  console.log(`URL after navigation: ${page.url()}`);

  // Check if session cookie still exists
  const sessionCookie = afterNavCookies.find(c =>
    c.name.toLowerCase().includes('session') || c.name === 'PHPSESSID'
  );

  if (sessionCookie) {
    console.log(`Session cookie still present: ${sessionCookie.name}`);
  } else {
    console.log('WARNING: No session cookie found after navigation!');
  }
});

// Test with network inspection
test('Network request inspection', async ({ page }) => {
  const requests = [];
  const responses = [];

  // Log all requests and responses
  page.on('request', request => {
    if (request.url().includes('/admin')) {
      requests.push({
        url: request.url(),
        method: request.method(),
        headers: request.headers()
      });
      console.log(`\n→ REQUEST: ${request.method()} ${request.url()}`);
      console.log('  Headers:', JSON.stringify(request.headers(), null, 2));
    }
  });

  page.on('response', response => {
    if (response.url().includes('/admin')) {
      responses.push({
        url: response.url(),
        status: response.status(),
        headers: response.headers()
      });
      console.log(`\n← RESPONSE: ${response.status()} ${response.url()}`);
      console.log('  Headers:', JSON.stringify(response.headers(), null, 2));
    }
  });

  // Login
  await page.goto('https://dalthaus.net/admin/login');
  await page.fill('input[name="username"]', 'kevin');
  await page.fill('input[name="password"]', '(130Bpm)');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/admin/dashboard', { timeout: 30000 });

  console.log('\n=== ATTEMPTING ARTICLES NAVIGATION ===');

  // Clear arrays for focused logging
  requests.length = 0;
  responses.length = 0;

  // Click Articles
  await page.click('a:has-text("Articles")');
  await page.waitForTimeout(3000);

  console.log(`\nTotal requests: ${requests.length}`);
  console.log(`Total responses: ${responses.length}`);
  console.log(`Final URL: ${page.url()}`);

  // Check for authentication headers
  const authHeaders = requests.filter(r =>
    r.headers['cookie'] && r.headers['cookie'].includes('session')
  );

  console.log(`\nRequests with session cookie: ${authHeaders.length}`);

  // Check for redirect responses
  const redirects = responses.filter(r => r.status >= 300 && r.status < 400);
  console.log(`Redirect responses: ${redirects.length}`);

  if (redirects.length > 0) {
    console.log('Redirect chain:');
    redirects.forEach(r => {
      console.log(`  ${r.status} ${r.url} → ${r.headers['location'] || 'unknown'}`);
    });
  }
});