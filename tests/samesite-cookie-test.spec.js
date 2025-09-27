const { test, expect } = require('@playwright/test');

test.describe('SameSite Cookie Issue Analysis', () => {
  test('Demonstrate SameSite=Strict cookie issue', async ({ page, context, browser }) => {
    console.log('\n=== SAMESITE COOKIE ISSUE ANALYSIS ===');

    // Monitor all requests and their cookie headers
    const requests = [];
    const responses = [];

    page.on('request', request => {
      const url = request.url();
      if (url.includes('/admin')) {
        const headers = request.headers();
        requests.push({
          url: url,
          method: request.method(),
          cookies: headers.cookie || 'NO COOKIES',
          referer: headers.referer || 'NO REFERER'
        });
        console.log(`\n→ ${request.method()} ${url}`);
        console.log(`  Cookies: ${headers.cookie || 'NONE'}`);
        console.log(`  Referer: ${headers.referer || 'NONE'}`);
      }
    });

    page.on('response', response => {
      const url = response.url();
      if (url.includes('/admin')) {
        const headers = response.headers();
        responses.push({
          url: url,
          status: response.status(),
          setCookie: headers['set-cookie'] || 'NONE',
          location: headers.location || 'NONE'
        });
        console.log(`\n← ${response.status()} ${url}`);
        console.log(`  Set-Cookie: ${headers['set-cookie'] || 'NONE'}`);
        console.log(`  Location: ${headers.location || 'NONE'}`);
      }
    });

    console.log('\n1. LOGGING IN TO ESTABLISH SESSION');

    // Step 1: Login to establish session
    await page.goto('https://dalthaus.net/admin/login');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard', { timeout: 30000 });

    // Check current cookies after login
    const postLoginCookies = await context.cookies();
    const sessionCookie = postLoginCookies.find(c => c.name === 'cms_session');

    console.log('\n2. SESSION COOKIE ANALYSIS');
    if (sessionCookie) {
      console.log(`✓ Session cookie found: ${sessionCookie.name}`);
      console.log(`  Domain: ${sessionCookie.domain}`);
      console.log(`  Path: ${sessionCookie.path}`);
      console.log(`  SameSite: ${sessionCookie.sameSite}`);
      console.log(`  Secure: ${sessionCookie.secure}`);
      console.log(`  HttpOnly: ${sessionCookie.httpOnly}`);
      console.log(`  Value: ${sessionCookie.value.substring(0, 20)}...`);
    } else {
      console.log('❌ No session cookie found!');
    }

    console.log('\n3. TESTING DIFFERENT NAVIGATION METHODS');

    // Clear request logs for focused testing
    requests.length = 0;
    responses.length = 0;

    // Method 1: Direct URL navigation (this fails)
    console.log('\n3a. Direct URL navigation (page.goto)');
    await page.goto('https://dalthaus.net/admin/content');
    console.log(`Result: ${page.url()}`);
    console.log(`Failed: ${page.url().includes('/admin/login')}`);

    const directNavRequest = requests.find(r => r.url.includes('/admin/content'));
    if (directNavRequest) {
      console.log(`Direct nav cookies: ${directNavRequest.cookies}`);
    }

    // Method 2: Click navigation (this also might fail due to SameSite)
    console.log('\n3b. Click navigation from dashboard');

    // Go back to dashboard first
    await page.goto('https://dalthaus.net/admin/login');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard', { timeout: 30000 });

    // Clear logs and try click navigation
    requests.length = 0;
    responses.length = 0;

    const articlesLink = await page.locator('a:has-text("Articles")').first();
    await articlesLink.click();
    await page.waitForTimeout(3000);

    console.log(`Click result: ${page.url()}`);
    console.log(`Failed: ${page.url().includes('/admin/login')}`);

    const clickNavRequest = requests.find(r => r.url.includes('/admin/content'));
    if (clickNavRequest) {
      console.log(`Click nav cookies: ${clickNavRequest.cookies}`);
      console.log(`Click nav referer: ${clickNavRequest.referer}`);
    }

    console.log('\n4. SAMESITE ANALYSIS');
    console.log('The issue is SameSite=Strict on the session cookie.');
    console.log('SameSite=Strict cookies are not sent with:');
    console.log('- Cross-site requests (different domain)');
    console.log('- Some same-site requests that appear "cross-site" to the browser');
    console.log('- Playwright navigation that doesn\'t set proper referer headers');
    console.log('');
    console.log('SOLUTION: Change SameSite from "Strict" to "Lax" or "None"');
    console.log('- Lax: Allows same-site navigation but blocks cross-site POST');
    console.log('- None: Allows all requests (requires Secure=true)');

    // Analyze request chain
    console.log('\n5. REQUEST CHAIN ANALYSIS');
    console.log('All requests made:');
    requests.forEach((req, i) => {
      console.log(`${i + 1}. ${req.method} ${req.url}`);
      console.log(`   Cookies: ${req.cookies}`);
      console.log(`   Referer: ${req.referer}`);
    });

    console.log('\nAll responses:');
    responses.forEach((res, i) => {
      console.log(`${i + 1}. ${res.status} ${res.url}`);
      console.log(`   Set-Cookie: ${res.setCookie}`);
      console.log(`   Location: ${res.location}`);
    });
  });

  test('Test with SameSite=Lax simulation', async ({ page, context }) => {
    console.log('\n=== TESTING SAMESITE=LAX SIMULATION ===');

    // First, log in normally
    await page.goto('https://dalthaus.net/admin/login');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard', { timeout: 30000 });

    // Get the session cookie
    const cookies = await context.cookies();
    const sessionCookie = cookies.find(c => c.name === 'cms_session');

    if (sessionCookie) {
      console.log(`Original session cookie SameSite: ${sessionCookie.sameSite}`);

      // Try to override the cookie with Lax setting
      console.log('Attempting to modify cookie to SameSite=Lax...');

      await context.addCookies([{
        name: 'cms_session',
        value: sessionCookie.value,
        domain: 'dalthaus.net',
        path: '/',
        httpOnly: true,
        secure: true,
        sameSite: 'Lax' // Change from Strict to Lax
      }]);

      // Test navigation with Lax cookie
      await page.goto('https://dalthaus.net/admin/content');
      console.log(`With SameSite=Lax: ${page.url()}`);
      console.log(`Success: ${!page.url().includes('/admin/login')}`);

      // Also test with None
      console.log('\nAttempting to modify cookie to SameSite=None...');
      await context.addCookies([{
        name: 'cms_session',
        value: sessionCookie.value,
        domain: 'dalthaus.net',
        path: '/',
        httpOnly: true,
        secure: true,
        sameSite: 'None' // Change to None
      }]);

      await page.goto('https://dalthaus.net/admin/content');
      console.log(`With SameSite=None: ${page.url()}`);
      console.log(`Success: ${!page.url().includes('/admin/login')}`);
    }
  });

  test('Browser behavior analysis', async ({ page, context }) => {
    console.log('\n=== BROWSER BEHAVIOR ANALYSIS ===');

    // Test with different browser contexts to see how cookies behave
    await page.goto('https://dalthaus.net/admin/login');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');

    // Monitor the login response for Set-Cookie headers
    const loginResponsePromise = page.waitForResponse(
      response => response.url().includes('/admin/login') && response.request().method() === 'POST'
    );

    await page.click('button[type="submit"]');
    const loginResponse = await loginResponsePromise;

    const setCookieHeader = loginResponse.headers()['set-cookie'];
    console.log('Set-Cookie header from login response:');
    console.log(setCookieHeader || 'NONE');

    if (setCookieHeader) {
      console.log('\nParsing Set-Cookie header:');
      const cookieParams = setCookieHeader.split(';').map(p => p.trim());
      cookieParams.forEach(param => {
        console.log(`  ${param}`);
      });

      // Check for SameSite in the header
      const sameSiteParam = cookieParams.find(p => p.toLowerCase().startsWith('samesite='));
      if (sameSiteParam) {
        console.log(`\n>>> Found SameSite setting: ${sameSiteParam}`);
      } else {
        console.log('\n>>> No SameSite parameter in Set-Cookie header');
      }
    }

    await page.waitForURL('**/admin/dashboard', { timeout: 30000 });

    // Check final cookie state
    const finalCookies = await context.cookies();
    const finalSessionCookie = finalCookies.find(c => c.name === 'cms_session');

    if (finalSessionCookie) {
      console.log('\nFinal session cookie in browser:');
      console.log(`  Name: ${finalSessionCookie.name}`);
      console.log(`  SameSite: ${finalSessionCookie.sameSite}`);
      console.log(`  Domain: ${finalSessionCookie.domain}`);
      console.log(`  Path: ${finalSessionCookie.path}`);
      console.log(`  Secure: ${finalSessionCookie.secure}`);
      console.log(`  HttpOnly: ${finalSessionCookie.httpOnly}`);
    }
  });
});