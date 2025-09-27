const { test, expect } = require('@playwright/test');

test.describe('Cookie Analysis - Authentication Issue', () => {
  test('Detailed cookie behavior analysis', async ({ page, context }) => {
    console.log('\n=== COOKIE ANALYSIS START ===');

    // Set up request/response monitoring
    const requestHeaders = [];
    const responseHeaders = [];

    page.on('request', request => {
      if (request.url().includes('/admin')) {
        const headers = request.headers();
        requestHeaders.push({
          url: request.url(),
          method: request.method(),
          cookies: headers.cookie || 'NO COOKIE HEADER',
          allHeaders: headers
        });
        console.log(`\n→ REQUEST: ${request.method()} ${request.url()}`);
        console.log(`   Cookie header: ${headers.cookie || 'MISSING'}`);
      }
    });

    page.on('response', response => {
      if (response.url().includes('/admin')) {
        const headers = response.headers();
        responseHeaders.push({
          url: response.url(),
          status: response.status(),
          setCookie: headers['set-cookie'] || 'NO SET-COOKIE',
          allHeaders: headers
        });
        console.log(`\n← RESPONSE: ${response.status()} ${response.url()}`);
        console.log(`   Set-Cookie: ${headers['set-cookie'] || 'NONE'}`);
      }
    });

    // Step 1: Check initial cookies
    console.log('\n1. Initial state - checking existing cookies');
    await page.goto('https://dalthaus.net/admin/login');

    let cookies = await context.cookies();
    console.log(`Initial cookies: ${cookies.length}`);
    cookies.forEach(c => {
      console.log(`  ${c.name}: domain=${c.domain}, path=${c.path}, secure=${c.secure}, httpOnly=${c.httpOnly}, sameSite=${c.sameSite}`);
    });

    // Step 2: Login
    console.log('\n2. Performing login');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');

    // Check for remember me checkbox and ensure it's unchecked
    const rememberCheckbox = await page.locator('input[name="remember_me"]').first();
    if (await rememberCheckbox.isVisible()) {
      if (await rememberCheckbox.isChecked()) {
        await rememberCheckbox.uncheck();
        console.log('  Unchecked remember me');
      }
    }

    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard', { timeout: 30000 });

    // Step 3: Check cookies after login
    console.log('\n3. Cookies after successful login');
    cookies = await context.cookies();
    console.log(`Post-login cookies: ${cookies.length}`);

    let sessionCookie = null;
    cookies.forEach(c => {
      console.log(`  ${c.name}: domain=${c.domain}, path=${c.path}, secure=${c.secure}, httpOnly=${c.httpOnly}, sameSite=${c.sameSite}`);
      if (c.name === 'cms_session' || c.name === 'PHPSESSID') {
        sessionCookie = c;
        console.log(`    >>> SESSION COOKIE FOUND: ${c.name}`);
        console.log(`        Value length: ${c.value.length}`);
        console.log(`        Expires: ${c.expires ? new Date(c.expires * 1000).toISOString() : 'Session'}`);
      }
    });

    if (!sessionCookie) {
      console.log('    ❌ NO SESSION COOKIE FOUND!');
    }

    // Step 4: Test direct navigation
    console.log('\n4. Testing direct navigation to /admin/content');

    // Clear monitoring arrays
    requestHeaders.length = 0;
    responseHeaders.length = 0;

    // Navigate directly
    await page.goto('https://dalthaus.net/admin/content', {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    console.log(`\nResult URL: ${page.url()}`);
    console.log(`Redirected to login: ${page.url().includes('/admin/login')}`);

    // Step 5: Analyze the failed request
    console.log('\n5. Analyzing the navigation request');

    const contentRequest = requestHeaders.find(r => r.url.includes('/admin/content'));
    if (contentRequest) {
      console.log('Content request details:');
      console.log(`  URL: ${contentRequest.url}`);
      console.log(`  Method: ${contentRequest.method}`);
      console.log(`  Cookie header: ${contentRequest.cookies}`);
      console.log(`  All headers:`, JSON.stringify(contentRequest.allHeaders, null, 2));
    } else {
      console.log('No content request found in logs!');
    }

    const contentResponse = responseHeaders.find(r => r.url.includes('/admin/content'));
    if (contentResponse) {
      console.log('Content response details:');
      console.log(`  Status: ${contentResponse.status}`);
      console.log(`  Set-Cookie: ${contentResponse.setCookie}`);
      console.log(`  Location header: ${contentResponse.allHeaders.location || 'NONE'}`);
    }

    // Step 6: Cookie domain analysis
    console.log('\n6. Cookie domain analysis');
    cookies = await context.cookies();

    const dalthausCookies = cookies.filter(c => c.domain === 'dalthaus.net');
    const dotDalthausCookies = cookies.filter(c => c.domain === '.dalthaus.net');

    console.log(`Cookies for 'dalthaus.net': ${dalthausCookies.length}`);
    dalthausCookies.forEach(c => console.log(`  ${c.name}: ${c.value.substring(0, 20)}...`));

    console.log(`Cookies for '.dalthaus.net': ${dotDalthausCookies.length}`);
    dotDalthausCookies.forEach(c => console.log(`  ${c.name}: ${c.value.substring(0, 20)}...`));

    // Step 7: Manual cookie test
    console.log('\n7. Manual cookie verification');

    // Get the session cookie value
    const currentCookies = await context.cookies();
    const cmsSession = currentCookies.find(c => c.name === 'cms_session');

    if (cmsSession) {
      console.log(`Session cookie value: ${cmsSession.value.substring(0, 30)}...`);
      console.log(`Session cookie expires: ${cmsSession.expires ? new Date(cmsSession.expires * 1000).toISOString() : 'Session'}`);

      // Try to manually set cookie and navigate
      console.log('\n8. Testing manual cookie setting');

      await context.addCookies([{
        name: 'cms_session',
        value: cmsSession.value,
        domain: 'dalthaus.net',
        path: '/',
        httpOnly: true,
        secure: true,
        sameSite: 'Strict'
      }]);

      // Clear logs and try again
      requestHeaders.length = 0;
      responseHeaders.length = 0;

      await page.goto('https://dalthaus.net/admin/content', {
        waitUntil: 'networkidle',
        timeout: 30000
      });

      console.log(`After manual cookie set - URL: ${page.url()}`);
      console.log(`Still redirected: ${page.url().includes('/admin/login')}`);

      const manualRequest = requestHeaders.find(r => r.url.includes('/admin/content'));
      if (manualRequest) {
        console.log(`Manual request cookie header: ${manualRequest.cookies}`);
      }
    }

    // Final summary
    console.log('\n=== SUMMARY ===');
    console.log(`Session cookie exists: ${!!sessionCookie}`);
    console.log(`Session cookie name: ${sessionCookie?.name || 'N/A'}`);
    console.log(`Navigation succeeds: ${!page.url().includes('/admin/login')}`);
    console.log(`Total requests logged: ${requestHeaders.length}`);
    console.log(`Requests with cookies: ${requestHeaders.filter(r => r.cookies !== 'NO COOKIE HEADER').length}`);
  });

  test('Test session cookie lifetime', async ({ page, context }) => {
    console.log('\n=== SESSION LIFETIME TEST ===');

    // Login first
    await page.goto('https://dalthaus.net/admin/login');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard', { timeout: 30000 });

    // Get session cookie
    let cookies = await context.cookies();
    const sessionCookie = cookies.find(c => c.name === 'cms_session');

    if (sessionCookie) {
      console.log(`Session cookie found: ${sessionCookie.name}`);
      console.log(`Expires: ${sessionCookie.expires ? new Date(sessionCookie.expires * 1000).toISOString() : 'Session'}`);
      console.log(`Max-Age: ${sessionCookie.expires ? sessionCookie.expires - Math.floor(Date.now() / 1000) : 'N/A'} seconds`);

      // Wait a moment and check if cookie is still there
      await page.waitForTimeout(2000);

      cookies = await context.cookies();
      const stillThere = cookies.find(c => c.name === 'cms_session');
      console.log(`Cookie still present after 2s: ${!!stillThere}`);

      // Try navigation after wait
      await page.goto('https://dalthaus.net/admin/content');
      console.log(`Navigation after wait - URL: ${page.url()}`);
    }
  });
});