const { test, expect } = require('@playwright/test');

test.describe('Autosave Functionality', () => {
    let page;

    test.beforeEach(async ({ browser }) => {
        page = await browser.newPage();

        // Login first
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');

        // Wait for dashboard to load
        await page.waitForURL('**/admin/dashboard', { timeout: 10000 });

        // Navigate to create photobook page
        await page.goto('https://dalthaus.net/admin/content/create?type=photobook');
        await page.waitForLoadState('networkidle');

        // Wait for TinyMCE to initialize
        await page.waitForTimeout(3000);
    });

    test.afterEach(async () => {
        if (page) {
            await page.close();
        }
    });

    test('should autosave without errors when typing', async () => {
        console.log('Testing autosave functionality...');

        // Track ALL network requests for debugging
        const allRequests = [];
        page.on('request', request => {
            const url = request.url();
            if (url.includes('/admin/content')) {
                allRequests.push({ url, method: request.method() });
                console.log(`Request: ${request.method()} ${url}`);
            }
        });

        // Track network requests to autosave endpoint
        const autosaveRequests = [];
        page.on('request', request => {
            if (request.url().includes('/admin/content/autosave')) {
                autosaveRequests.push(request);
                console.log('Autosave request intercepted:', request.url());
            }
        });

        // Track ALL responses for debugging
        const allResponses = [];
        page.on('response', async response => {
            const url = response.url();
            if (url.includes('/admin/content')) {
                const status = response.status();
                const contentType = response.headers()['content-type'] || '';
                allResponses.push({ url, status, contentType });
                console.log(`Response: ${status} ${contentType} ${url}`);
            }
        });

        // Track responses
        const autosaveResponses = [];
        page.on('response', async response => {
            if (response.url().includes('/admin/content/autosave')) {
                const status = response.status();
                console.log('Autosave response status:', status);

                try {
                    const body = await response.json();
                    autosaveResponses.push({ status, body });
                    console.log('Autosave response body:', body);
                } catch (e) {
                    console.error('Failed to parse autosave response:', e);
                    autosaveResponses.push({ status, error: 'Failed to parse JSON' });
                }
            }
        });

        // Track console errors
        const consoleErrors = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                consoleErrors.push(msg.text());
                console.log('Console error:', msg.text());
            }
        });

        // Fill in the title field
        console.log('Filling title field...');
        await page.fill('input[name="title"]', 'Test Autosave Photobook');

        // Fill in the teaser field
        console.log('Filling teaser field...');
        await page.fill('textarea[name="teaser"]', 'This is a test teaser for autosave');

        // Get TinyMCE iframe and fill content
        console.log('Waiting for TinyMCE iframe...');
        const iframe = page.frameLocator('iframe[id^="body_ifr"]');
        await iframe.locator('body#tinymce').click();
        await page.waitForTimeout(500);
        await iframe.locator('body#tinymce').fill('This is test content for autosave functionality.');

        // Wait for autosave to trigger (blur events + debounce delay)
        console.log('Waiting for autosave to trigger...');
        await page.waitForTimeout(5000);

        // Verify no console errors related to JSON parsing
        const jsonErrors = consoleErrors.filter(err =>
            err.includes('Unexpected token') ||
            err.includes('JSON') ||
            err.includes('<!DOCTYPE')
        );

        console.log('JSON-related errors:', jsonErrors);
        expect(jsonErrors.length, 'Should have no JSON parsing errors').toBe(0);

        // Verify autosave request was made
        console.log('Total autosave requests:', autosaveRequests.length);
        expect(autosaveRequests.length, 'Should have made at least one autosave request').toBeGreaterThan(0);

        // Verify autosave response was successful
        console.log('Total autosave responses:', autosaveResponses.length);
        expect(autosaveResponses.length, 'Should have received at least one autosave response').toBeGreaterThan(0);

        const lastResponse = autosaveResponses[autosaveResponses.length - 1];
        console.log('Last autosave response:', lastResponse);

        expect(lastResponse.status, 'Autosave response should be 200 OK').toBe(200);
        expect(lastResponse.body?.success, 'Autosave response should indicate success').toBe(true);

        // Verify autosave status indicator shows saved
        const statusText = await page.locator('#autosave-status').textContent();
        console.log('Autosave status text:', statusText);
        expect(statusText).toMatch(/saved|ago/i);

        console.log('✅ Autosave test passed!');
    });

    test('should show autosave status indicator', async () => {
        // Check that autosave status element exists
        const statusElement = page.locator('#autosave-status');
        await expect(statusElement).toBeVisible();

        // Initially should be empty or show "Not saved"
        const initialText = await statusElement.textContent();
        console.log('Initial autosave status:', initialText);

        // Type in title to enable autosave
        await page.fill('input[name="title"]', 'Test Status Indicator');

        // Wait a moment for autosave to trigger
        await page.waitForTimeout(5000);

        // Status should update
        const updatedText = await statusElement.textContent();
        console.log('Updated autosave status:', updatedText);
        expect(updatedText).not.toBe(initialText);
    });

    test('should return proper JSON response on autosave', async () => {
        // Monitor the autosave response
        const autosavePromise = page.waitForResponse(
            response => response.url().includes('/admin/content/autosave') && response.status() === 200,
            { timeout: 10000 }
        );

        // Trigger autosave by typing
        await page.fill('input[name="title"]', 'JSON Response Test');
        await page.fill('textarea[name="teaser"]', 'Testing JSON response');

        // Wait for the autosave response
        const response = await autosavePromise;

        // Verify it's JSON
        const contentType = response.headers()['content-type'];
        console.log('Content-Type:', contentType);
        expect(contentType).toMatch(/application\/json/);

        // Parse and verify JSON structure
        const json = await response.json();
        console.log('Autosave JSON response:', json);

        expect(json).toHaveProperty('success');
        expect(json.success).toBe(true);
        expect(json).toHaveProperty('autosave_id');
        expect(json).toHaveProperty('message');
    });
});
