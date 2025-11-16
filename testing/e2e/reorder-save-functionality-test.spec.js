const { test, expect } = require('@playwright/test');

/**
 * Reorder Save Functionality Test
 *
 * Tests the save functionality on reorder pages to ensure:
 * 1. Save operations work correctly
 * 2. Proper JSON responses are returned
 * 3. Session expiration is handled properly
 * 4. No console or network errors occur
 */

test.describe('Reorder Save Functionality', () => {
    let context;
    let page;
    const consoleErrors = [];
    const networkErrors = [];

    test.beforeAll(async ({ browser }) => {
        context = await browser.newContext();
        page = await context.newPage();

        // Capture console errors
        page.on('console', msg => {
            if (msg.type() === 'error') {
                consoleErrors.push({
                    text: msg.text(),
                    location: msg.location(),
                    timestamp: new Date().toISOString()
                });
                console.log(`[CONSOLE ERROR] ${msg.text()}`);
            }
        });

        // Capture network errors
        page.on('response', response => {
            if (response.status() >= 400) {
                networkErrors.push({
                    url: response.url(),
                    status: response.status(),
                    statusText: response.statusText(),
                    timestamp: new Date().toISOString()
                });
                console.log(`[NETWORK ERROR] ${response.status()} - ${response.url()}`);
            }
        });

        // Login
        console.log('[SETUP] Logging in...');
        await page.goto('https://dalthaus.net/admin/login', { waitUntil: 'networkidle' });
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
        console.log('[SETUP] Login successful\n');
    });

    test.afterAll(async () => {
        console.log(`\n========================================`);
        console.log(`[FINAL SUMMARY]`);
        console.log(`Total Console Errors: ${consoleErrors.length}`);
        console.log(`Total Network Errors: ${networkErrors.length}`);
        console.log(`========================================\n`);

        if (consoleErrors.length > 0) {
            console.log('[CONSOLE ERRORS DETAILS]');
            consoleErrors.forEach((err, i) => {
                console.log(`\n${i + 1}. ${err.text}`);
                console.log(`   Time: ${err.timestamp}`);
            });
        }

        if (networkErrors.length > 0) {
            console.log('\n[NETWORK ERRORS DETAILS]');
            networkErrors.forEach((err, i) => {
                console.log(`\n${i + 1}. ${err.status} ${err.statusText}`);
                console.log(`   URL: ${err.url}`);
                console.log(`   Time: ${err.timestamp}`);
            });
        }

        await context.close();
    });

    test('1. Articles Reorder - Save Without Changes', async () => {
        console.log('[TEST 1] Testing articles reorder save...');

        const errorsBefore = consoleErrors.length;
        const networkErrorsBefore = networkErrors.length;

        // Navigate to articles reorder page
        await page.goto('https://dalthaus.net/admin/articles/reorder', { waitUntil: 'networkidle' });
        console.log('[TEST 1] Page loaded');

        // Check if there are items to reorder
        const items = page.locator('.sortable-item');
        const itemCount = await items.count();
        console.log(`[TEST 1] Found ${itemCount} articles`);

        if (itemCount > 0) {
            // Intercept the update-order request
            const responsePromise = page.waitForResponse(
                response => response.url().includes('/admin/articles/update-order'),
                { timeout: 10000 }
            );

            // Click save button
            const saveButton = page.locator('button:has-text("Save New Order")');
            await expect(saveButton).toBeVisible();
            await saveButton.click();
            console.log('[TEST 1] Save button clicked');

            // Wait for response
            const response = await responsePromise;
            const contentType = response.headers()['content-type'];
            const status = response.status();

            console.log(`[TEST 1] Response status: ${status}`);
            console.log(`[TEST 1] Content-Type: ${contentType}`);

            // Verify response is JSON
            expect(contentType).toContain('application/json');
            expect(status).toBe(200);

            // Parse response
            const responseData = await response.json();
            console.log(`[TEST 1] Response data:`, responseData);

            expect(responseData.success).toBe(true);
            expect(responseData.message).toBeTruthy();

            // Verify success message is shown
            await page.waitForSelector('#save-status', { state: 'visible', timeout: 5000 });
            const successMessage = await page.locator('#save-status').textContent();
            console.log(`[TEST 1] Success message: ${successMessage}`);
            expect(successMessage).toContain('success');

            // Check for new errors
            const newConsoleErrors = consoleErrors.length - errorsBefore;
            const newNetworkErrors = networkErrors.length - networkErrorsBefore;

            console.log(`[TEST 1] New console errors: ${newConsoleErrors}`);
            console.log(`[TEST 1] New network errors: ${newNetworkErrors}`);

            expect(newConsoleErrors).toBe(0);
            expect(newNetworkErrors).toBe(0);

            console.log('[TEST 1] ✅ PASSED\n');
        } else {
            console.log('[TEST 1] ⚠️  SKIPPED - No articles to test\n');
        }
    });

    test('2. Photobooks Reorder - Save Without Changes', async () => {
        console.log('[TEST 2] Testing photobooks reorder save...');

        const errorsBefore = consoleErrors.length;
        const networkErrorsBefore = networkErrors.length;

        // Navigate to photobooks reorder page
        await page.goto('https://dalthaus.net/admin/photobooks/reorder', { waitUntil: 'networkidle' });
        console.log('[TEST 2] Page loaded');

        // Check if there are items to reorder
        const items = page.locator('.sortable-item');
        const itemCount = await items.count();
        console.log(`[TEST 2] Found ${itemCount} photobooks`);

        if (itemCount > 0) {
            // Intercept the update-order request
            const responsePromise = page.waitForResponse(
                response => response.url().includes('/admin/photobooks/update-order'),
                { timeout: 10000 }
            );

            // Click save button
            const saveButton = page.locator('button:has-text("Save New Order")');
            await expect(saveButton).toBeVisible();
            await saveButton.click();
            console.log('[TEST 2] Save button clicked');

            // Wait for response
            const response = await responsePromise;
            const contentType = response.headers()['content-type'];
            const status = response.status();

            console.log(`[TEST 2] Response status: ${status}`);
            console.log(`[TEST 2] Content-Type: ${contentType}`);

            // Verify response is JSON
            expect(contentType).toContain('application/json');
            expect(status).toBe(200);

            // Parse response
            const responseData = await response.json();
            console.log(`[TEST 2] Response data:`, responseData);

            expect(responseData.success).toBe(true);
            expect(responseData.message).toBeTruthy();

            // Verify success message is shown
            await page.waitForSelector('#save-status', { state: 'visible', timeout: 5000 });
            const successMessage = await page.locator('#save-status').textContent();
            console.log(`[TEST 2] Success message: ${successMessage}`);
            expect(successMessage).toContain('success');

            // Check for new errors
            const newConsoleErrors = consoleErrors.length - errorsBefore;
            const newNetworkErrors = networkErrors.length - networkErrorsBefore;

            console.log(`[TEST 2] New console errors: ${newConsoleErrors}`);
            console.log(`[TEST 2] New network errors: ${newNetworkErrors}`);

            expect(newConsoleErrors).toBe(0);
            expect(newNetworkErrors).toBe(0);

            console.log('[TEST 2] ✅ PASSED\n');
        } else {
            console.log('[TEST 2] ⚠️  SKIPPED - No photobooks to test\n');
        }
    });

    test('3. Content Reorder - Save Without Changes', async () => {
        console.log('[TEST 3] Testing content reorder save...');

        const errorsBefore = consoleErrors.length;
        const networkErrorsBefore = networkErrors.length;

        // Navigate to content reorder page
        await page.goto('https://dalthaus.net/admin/content/reorder', { waitUntil: 'networkidle' });
        console.log('[TEST 3] Page loaded');

        // Check if there are items to reorder
        const items = page.locator('.sortable-item');
        const itemCount = await items.count();
        console.log(`[TEST 3] Found ${itemCount} content items`);

        if (itemCount > 0) {
            // Intercept the update-order request
            const responsePromise = page.waitForResponse(
                response => response.url().includes('/admin/content/update-order'),
                { timeout: 10000 }
            );

            // Click save button
            const saveButton = page.locator('button:has-text("Save New Order")');
            await expect(saveButton).toBeVisible();
            await saveButton.click();
            console.log('[TEST 3] Save button clicked');

            // Wait for response
            const response = await responsePromise;
            const contentType = response.headers()['content-type'];
            const status = response.status();

            console.log(`[TEST 3] Response status: ${status}`);
            console.log(`[TEST 3] Content-Type: ${contentType}`);

            // Verify response is JSON
            expect(contentType).toContain('application/json');
            expect(status).toBe(200);

            // Parse response
            const responseData = await response.json();
            console.log(`[TEST 3] Response data:`, responseData);

            expect(responseData.success).toBe(true);
            expect(responseData.message).toBeTruthy();

            // Verify success message is shown
            await page.waitForSelector('#save-status', { state: 'visible', timeout: 5000 });
            const successMessage = await page.locator('#save-status').textContent();
            console.log(`[TEST 3] Success message: ${successMessage}`);
            expect(successMessage).toContain('success');

            // Check for new errors
            const newConsoleErrors = consoleErrors.length - errorsBefore;
            const newNetworkErrors = networkErrors.length - networkErrorsBefore;

            console.log(`[TEST 3] New console errors: ${newConsoleErrors}`);
            console.log(`[TEST 3] New network errors: ${newNetworkErrors}`);

            expect(newConsoleErrors).toBe(0);
            expect(newNetworkErrors).toBe(0);

            console.log('[TEST 3] ✅ PASSED\n');
        } else {
            console.log('[TEST 3] ⚠️  SKIPPED - No content items to test\n');
        }
    });

    test('4. Session Expiration Handling', async () => {
        console.log('[TEST 4] Testing session expiration handling...');

        // Navigate to photobooks reorder page
        await page.goto('https://dalthaus.net/admin/photobooks/reorder', { waitUntil: 'networkidle' });
        console.log('[TEST 4] Page loaded');

        // Check if there are items
        const items = page.locator('.sortable-item');
        const itemCount = await items.count();

        if (itemCount > 0) {
            // Clear session cookies to simulate expiration
            console.log('[TEST 4] Clearing session cookies...');
            const cookies = await context.cookies();
            const sessionCookie = cookies.find(c => c.name === 'cms_session');

            if (sessionCookie) {
                await context.clearCookies();
                console.log('[TEST 4] Session cleared');

                // Intercept the update-order request
                const responsePromise = page.waitForResponse(
                    response => response.url().includes('/admin/photobooks/update-order'),
                    { timeout: 10000 }
                );

                // Click save button
                const saveButton = page.locator('button:has-text("Save New Order")');
                await saveButton.click();
                console.log('[TEST 4] Save button clicked');

                // Wait for response
                const response = await responsePromise;
                const contentType = response.headers()['content-type'];
                const status = response.status();

                console.log(`[TEST 4] Response status: ${status}`);
                console.log(`[TEST 4] Content-Type: ${contentType}`);

                // Verify response is JSON (not HTML)
                expect(contentType).toContain('application/json');
                expect(status).toBe(401);

                // Parse response
                const responseData = await response.json();
                console.log(`[TEST 4] Response data:`, responseData);

                expect(responseData.error).toBeTruthy();
                expect(responseData.error).toContain('Unauthorized');

                // Verify error message is shown
                await page.waitForSelector('#save-status', { state: 'visible', timeout: 5000 });
                const errorMessage = await page.locator('#save-status').textContent();
                console.log(`[TEST 4] Error message: ${errorMessage}`);

                console.log('[TEST 4] ✅ PASSED - Expired session handled correctly\n');
            } else {
                console.log('[TEST 4] ⚠️  SKIPPED - No session cookie found\n');
            }
        } else {
            console.log('[TEST 4] ⚠️  SKIPPED - No items to test\n');
        }
    });
});
