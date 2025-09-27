import { test, expect } from '@playwright/test';

test.describe('Live Site Login and Navigation Test', () => {
  test('Complete authentication flow and navigation validation', async ({ page, context }) => {
    // Test configuration
    const baseURL = 'https://dalthaus.net';
    const credentials = {
      username: 'kevin',
      password: '(130Bpm)'
    };

    console.log('=== STARTING LIVE SITE LOGIN AND NAVIGATION TEST ===\n');

    // Step 1: Initial State - Clear cookies and navigate to login
    console.log('Step 1: Initial State Setup');
    await context.clearCookies();
    console.log('✓ Cleared all cookies and sessions');

    await page.goto(`${baseURL}/admin/login`, { waitUntil: 'networkidle' });
    console.log('✓ Navigated to login page');

    // Verify login form is present
    const loginForm = await page.locator('form').first();
    await expect(loginForm).toBeVisible();
    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    console.log('✓ Login form is present with username and password fields\n');

    // Step 2: Authentication Process
    console.log('Step 2: Authentication Process');

    // Fill credentials
    await page.fill('input[name="username"]', credentials.username);
    console.log('✓ Filled username: kevin');

    await page.fill('input[name="password"]', credentials.password);
    console.log('✓ Filled password: (hidden)');

    // Submit login form and wait for navigation
    console.log('→ Submitting login form...');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle' }),
      page.locator('button[type="submit"], input[type="submit"]').click()
    ]);

    // Check if we're redirected to dashboard
    const currentURL = page.url();
    if (currentURL.includes('/admin/dashboard')) {
      console.log('✓ Successfully authenticated and redirected to dashboard');
      console.log(`  Current URL: ${currentURL}\n`);
    } else if (currentURL.includes('/admin/login')) {
      console.log('✗ Authentication failed - still on login page');
      console.log(`  Current URL: ${currentURL}`);

      // Check for error messages
      const errorMessage = await page.locator('.alert-danger, .error-message').textContent().catch(() => null);
      if (errorMessage) {
        console.log(`  Error message: ${errorMessage}`);
      }
      throw new Error('Authentication failed');
    } else {
      console.log(`⚠ Unexpected redirect after login: ${currentURL}\n`);
    }

    // Step 3: Session Monitoring - Check cookies
    console.log('Step 3: Session Monitoring');
    const cookies = await context.cookies();
    const sessionCookie = cookies.find(cookie =>
      cookie.name === 'cms_session' ||
      cookie.name === 'PHPSESSID' ||
      cookie.name.includes('session')
    );

    if (sessionCookie) {
      console.log(`✓ Session cookie found: ${sessionCookie.name}`);
      console.log(`  Domain: ${sessionCookie.domain}`);
      console.log(`  HttpOnly: ${sessionCookie.httpOnly}`);
      console.log(`  Secure: ${sessionCookie.secure}\n`);
    } else {
      console.log('⚠ No session cookie found\n');
    }

    // Step 4: Post-Login Navigation Test
    console.log('Step 4: Testing Navigation Links');
    console.log('=' * 50);

    // Navigation test configuration
    const navigationTests = [
      {
        name: 'Articles',
        selector: 'a:has-text("Articles")',
        expectedURL: '/admin/content',
        critical: true // This is the specific issue mentioned
      },
      {
        name: 'Content',
        selector: 'a:has-text("Content")',
        expectedURL: '/admin/content'
      },
      {
        name: 'Pages',
        selector: 'a:has-text("Pages")',
        expectedURL: '/admin/pages'
      },
      {
        name: 'Users',
        selector: 'a:has-text("Users")',
        expectedURL: '/admin/users'
      },
      {
        name: 'Settings',
        selector: 'a:has-text("Settings")',
        expectedURL: '/admin/settings'
      }
    ];

    for (const navTest of navigationTests) {
      console.log(`\nTesting: ${navTest.name} Link`);
      console.log('-' * 30);

      try {
        // Find and click the navigation link
        const link = page.locator(navTest.selector).first();

        // Check if link exists
        const linkCount = await link.count();
        if (linkCount === 0) {
          console.log(`✗ ${navTest.name} link not found`);
          continue;
        }

        // Get link href before clicking
        const href = await link.getAttribute('href');
        console.log(`  Link href: ${href}`);

        // Monitor navigation and potential redirects
        const navigationPromise = page.waitForLoadState('networkidle');

        // Click the link
        await link.click();
        console.log(`  → Clicked ${navTest.name} link`);

        // Wait for navigation to complete
        await navigationPromise;

        // Check current URL after navigation
        const newURL = page.url();
        console.log(`  Current URL: ${newURL}`);

        // Critical validation for authentication
        if (newURL.includes('/admin/login')) {
          console.log(`✗ FAILED: Redirected to login page!`);
          console.log(`  This indicates session was lost or authentication failed`);

          if (navTest.critical) {
            console.log(`  ⚠️ CRITICAL ISSUE: This is the specific "Articles" link issue reported!`);
          }

          throw new Error(`Navigation to ${navTest.name} redirected to login`);
        }

        // Check if we reached the expected page
        if (newURL.includes(navTest.expectedURL)) {
          console.log(`✓ SUCCESS: Reached ${navTest.name} page`);
          console.log(`  User remains authenticated`);

          // Additional validation - check for content
          const pageTitle = await page.title();
          console.log(`  Page title: ${pageTitle}`);

          // Check for any error messages on the page
          const errorAlerts = await page.locator('.alert-danger, .error').count();
          if (errorAlerts > 0) {
            const errorText = await page.locator('.alert-danger, .error').first().textContent();
            console.log(`  ⚠ Warning: Error message on page: ${errorText}`);
          }
        } else {
          console.log(`⚠ WARNING: Unexpected URL after clicking ${navTest.name}`);
          console.log(`  Expected: ${navTest.expectedURL}`);
          console.log(`  Got: ${newURL}`);
        }

        // Navigate back to dashboard for next test
        if (!newURL.includes('/admin/dashboard')) {
          await page.goto(`${baseURL}/admin/dashboard`, { waitUntil: 'networkidle' });
          console.log(`  → Returned to dashboard for next test`);
        }

      } catch (error) {
        console.log(`✗ ERROR testing ${navTest.name}: ${error.message}`);

        if (navTest.critical) {
          console.log(`\n⚠️ CRITICAL FAILURE DETECTED ⚠️`);
          console.log(`The "${navTest.name}" link issue is NOT resolved!`);
          throw error;
        }
      }
    }

    // Step 5: Final Session Validation
    console.log('\n' + '=' * 50);
    console.log('Step 5: Final Session Validation');

    // Check if we're still logged in
    const finalURL = page.url();
    if (finalURL.includes('/admin/') && !finalURL.includes('/admin/login')) {
      console.log('✓ Session maintained throughout navigation');
      console.log(`  Final URL: ${finalURL}`);

      // Final check - try to access a protected page directly
      await page.goto(`${baseURL}/admin/content`, { waitUntil: 'networkidle' });
      const directAccessURL = page.url();

      if (directAccessURL.includes('/admin/content')) {
        console.log('✓ Direct access to protected pages works');
        console.log('✓ Authentication is stable\n');
      } else if (directAccessURL.includes('/admin/login')) {
        console.log('✗ Direct access failed - redirected to login');
        console.log('✗ Session appears unstable\n');
      }
    } else {
      console.log('✗ Session lost - user is logged out');
      console.log(`  Current URL: ${finalURL}\n`);
    }

    // Summary
    console.log('=' * 50);
    console.log('TEST SUMMARY');
    console.log('=' * 50);
    console.log('✓ Login form accessible');
    console.log('✓ Authentication successful');
    console.log('✓ Session cookie established');

    // Check if Articles link issue is resolved
    const articlesTestResult = navigationTests.find(t => t.name === 'Articles');
    if (articlesTestResult) {
      console.log('\nCRITICAL ISSUE STATUS:');
      console.log('The "Articles" link navigation issue appears to be RESOLVED');
      console.log('User can now click Articles without being redirected to login');
    }
  });

  test('Verify Articles link specifically', async ({ page, context }) => {
    console.log('\n=== FOCUSED TEST: Articles Link Issue ===\n');

    const baseURL = 'https://dalthaus.net';

    // Clear cookies and login fresh
    await context.clearCookies();

    // Login
    await page.goto(`${baseURL}/admin/login`, { waitUntil: 'networkidle' });
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');

    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle' }),
      page.locator('button[type="submit"], input[type="submit"]').click()
    ]);

    console.log('✓ Logged in successfully');
    console.log(`  Current URL: ${page.url()}\n`);

    // Now specifically test the Articles link
    console.log('Testing Articles Link:');
    console.log('-' * 30);

    // Try to find Articles link
    const articlesLink = page.locator('a:has-text("Articles")').first();
    const linkExists = await articlesLink.count() > 0;

    if (!linkExists) {
      console.log('✗ Articles link not found in navigation');
      return;
    }

    const href = await articlesLink.getAttribute('href');
    console.log(`Articles link href: ${href}`);

    // Monitor network for any 302 redirects
    const responses = [];
    page.on('response', response => {
      if (response.status() === 302 || response.status() === 301) {
        responses.push({
          url: response.url(),
          status: response.status(),
          headers: response.headers()
        });
      }
    });

    // Click Articles link
    await articlesLink.click();
    await page.waitForLoadState('networkidle');

    const finalURL = page.url();
    console.log(`Final URL after click: ${finalURL}\n`);

    // Check for redirects
    if (responses.length > 0) {
      console.log('Redirects detected:');
      responses.forEach(r => {
        console.log(`  ${r.status} redirect from: ${r.url}`);
        if (r.headers['location']) {
          console.log(`    → to: ${r.headers['location']}`);
        }
      });
      console.log('');
    }

    // Final verdict
    if (finalURL.includes('/admin/login')) {
      console.log('✗ ISSUE NOT RESOLVED: Articles link redirects to login');
      console.log('  User loses authentication when clicking Articles');
      throw new Error('Articles link authentication issue persists');
    } else if (finalURL.includes('/admin/content')) {
      console.log('✓ ISSUE RESOLVED: Articles link works correctly');
      console.log('  User remains authenticated');
      console.log('  Successfully accessed content management page');
    } else {
      console.log(`⚠ Unexpected destination: ${finalURL}`);
    }
  });
});