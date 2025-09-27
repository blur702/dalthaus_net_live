const { test, expect } = require('@playwright/test');

test.describe('Authentication Final Test', () => {
    test.beforeEach(async ({ page }) => {
        // Clear all storage to start fresh
        await page.context().clearCookies();
        // Skip localStorage clearing to avoid security errors
    });

    test('Test 1: Basic authentication without remember me', async ({ page }) => {
        console.log('\n=== TEST 1: Basic Authentication (no remember me) ===');

        // Navigate to login page
        console.log('1. Navigating to login page...');
        await page.goto('https://dalthaus.net/admin/login');
        await page.waitForLoadState('networkidle');

        // Take screenshot of login page
        await page.screenshot({ path: 'test-results/01-login-page.png', fullPage: true });

        // Check if we're already logged in (redirect to dashboard)
        if (page.url().includes('/admin/dashboard')) {
            console.log('Already logged in - logging out first');
            await page.goto('https://dalthaus.net/admin/logout');
            await page.waitForLoadState('networkidle');
            await page.goto('https://dalthaus.net/admin/login');
            await page.waitForLoadState('networkidle');
        }

        // Inspect the login form
        console.log('2. Analyzing login form...');
        const form = page.locator('form');
        const formAction = await form.getAttribute('action');
        const formMethod = await form.getAttribute('method');
        console.log(`Form action: ${formAction}`);
        console.log(`Form method: ${formMethod}`);

        // Get all form inputs
        const inputs = await page.locator('input').all();
        console.log(`Found ${inputs.length} input fields:`);
        for (let i = 0; i < inputs.length; i++) {
            const input = inputs[i];
            const name = await input.getAttribute('name');
            const type = await input.getAttribute('type');
            const id = await input.getAttribute('id');
            console.log(`  Input ${i + 1}: name="${name}", type="${type}", id="${id}"`);
        }

        // Fill in credentials WITHOUT remember me
        console.log('3. Filling credentials (without remember me)...');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');

        // Make sure remember me is NOT checked
        const rememberCheckbox = page.locator('input[name="remember_me"]');
        if (await rememberCheckbox.isVisible()) {
            await rememberCheckbox.uncheck();
            console.log('Unchecked remember me checkbox');
        }

        // Take screenshot before submission
        await page.screenshot({ path: 'test-results/02-before-submit.png', fullPage: true });

        // Monitor network requests
        const requests = [];
        page.on('request', request => {
            requests.push({
                url: request.url(),
                method: request.method(),
                headers: request.headers()
            });
        });

        const responses = [];
        page.on('response', response => {
            responses.push({
                url: response.url(),
                status: response.status(),
                headers: response.headers()
            });
        });

        // Submit the form
        console.log('4. Submitting login form...');
        await page.click('button[type="submit"]');

        // Wait for response
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000); // Give time for any redirects

        // Check the result
        const currentUrl = page.url();
        console.log(`Current URL after login: ${currentUrl}`);

        // Take screenshot of result
        await page.screenshot({ path: 'test-results/03-after-submit.png', fullPage: true });

        // Check for success indicators
        const isDashboard = currentUrl.includes('/admin/dashboard');
        const hasErrorMessage = await page.locator('.error, .alert-danger, [class*="error"]').count() > 0;

        console.log(`Is on dashboard: ${isDashboard}`);
        console.log(`Has error message: ${hasErrorMessage}`);

        if (hasErrorMessage) {
            const errorText = await page.locator('.error, .alert-danger, [class*="error"]').first().textContent();
            console.log(`Error message: ${errorText}`);
        }

        // Log network activity
        console.log('\nNetwork Activity:');
        console.log('Requests:');
        requests.forEach((req, i) => {
            console.log(`  ${i + 1}. ${req.method} ${req.url}`);
        });
        console.log('Responses:');
        responses.forEach((res, i) => {
            console.log(`  ${i + 1}. ${res.status} ${res.url}`);
        });

        // Check cookies
        const cookies = await page.context().cookies();
        console.log('\nCookies after login:');
        cookies.forEach(cookie => {
            console.log(`  ${cookie.name}: ${cookie.value} (expires: ${cookie.expires})`);
        });

        expect(isDashboard).toBe(true);
    });

    test('Test 2: Authentication WITH remember me', async ({ page }) => {
        console.log('\n=== TEST 2: Authentication WITH Remember Me ===');

        // Navigate to login page
        console.log('1. Navigating to login page...');
        await page.goto('https://dalthaus.net/admin/login');
        await page.waitForLoadState('networkidle');

        // Fill in credentials WITH remember me
        console.log('2. Filling credentials (WITH remember me)...');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');

        // Check remember me
        const rememberCheckbox = page.locator('input[name="remember_me"]');
        if (await rememberCheckbox.isVisible()) {
            await rememberCheckbox.check();
            console.log('Checked remember me checkbox');
        }

        // Take screenshot before submission
        await page.screenshot({ path: 'test-results/04-remember-me-before.png', fullPage: true });

        // Submit the form
        console.log('3. Submitting login form with remember me...');
        await page.click('button[type="submit"]');

        // Wait for response
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        const currentUrl = page.url();
        console.log(`Current URL after login with remember me: ${currentUrl}`);

        // Take screenshot of result
        await page.screenshot({ path: 'test-results/05-remember-me-after.png', fullPage: true });

        // Check cookies for remember token
        const cookies = await page.context().cookies();
        console.log('\nCookies after remember me login:');
        let hasRememberToken = false;
        cookies.forEach(cookie => {
            console.log(`  ${cookie.name}: ${cookie.value} (expires: ${cookie.expires})`);
            if (cookie.name.includes('remember') || cookie.name.includes('token')) {
                hasRememberToken = true;
            }
        });

        console.log(`Has remember token: ${hasRememberToken}`);

        const isDashboard = currentUrl.includes('/admin/dashboard');
        expect(isDashboard).toBe(true);
    });

    test('Test 3: Session persistence check', async ({ page }) => {
        console.log('\n=== TEST 3: Session Persistence Check ===');

        // First login
        console.log('1. Initial login...');
        await page.goto('https://dalthaus.net/admin/login');
        await page.waitForLoadState('networkidle');

        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        if (page.url().includes('/admin/dashboard')) {
            console.log('✓ Initial login successful');

            // Navigate to another admin page
            console.log('2. Testing navigation to other admin pages...');
            await page.goto('https://dalthaus.net/admin/content');
            await page.waitForLoadState('networkidle');

            const contentUrl = page.url();
            console.log(`Content page URL: ${contentUrl}`);

            if (contentUrl.includes('/admin/content')) {
                console.log('✓ Can access admin content page');
            } else {
                console.log('✗ Redirected away from admin content page');
            }

            // Try to access dashboard again
            await page.goto('https://dalthaus.net/admin/dashboard');
            await page.waitForLoadState('networkidle');

            const dashboardUrl = page.url();
            console.log(`Dashboard URL: ${dashboardUrl}`);

            if (dashboardUrl.includes('/admin/dashboard')) {
                console.log('✓ Session persists across navigation');
            } else {
                console.log('✗ Session lost during navigation');
            }
        } else {
            console.log('✗ Initial login failed');
        }

        await page.screenshot({ path: 'test-results/06-session-check.png', fullPage: true });
    });
});