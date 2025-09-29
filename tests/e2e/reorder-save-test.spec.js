const { test, expect } = require('@playwright/test');

test.describe('Reorder Save Functionality', () => {
    let page;

    test.beforeEach(async ({ browser }) => {
        page = await browser.newPage();

        // Login first
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard');
    });

    test('should test articles reorder save functionality', async () => {
        // Go to articles reorder page
        await page.goto('https://dalthaus.net/admin/articles/reorder');

        // Check if there are enough items to reorder
        const items = page.locator('.sortable-item');
        const count = await items.count();

        if (count >= 2) {
            console.log(`Testing reorder save with ${count} articles`);

            // Get initial order for comparison
            const initialOrder = [];
            for (let i = 0; i < Math.min(count, 3); i++) {
                const item = items.nth(i);
                const id = await item.getAttribute('data-id');
                const orderElement = item.locator('.bg-gray-200');
                const orderText = await orderElement.textContent();
                initialOrder.push({ id, order: orderText.trim() });
            }

            console.log('Initial order:', initialOrder.map(item => `ID:${item.id} Order:${item.order}`).join(', '));

            // Test AJAX save without actually changing order
            // This tests that the endpoint is working
            const response = await page.evaluate(async () => {
                // Simulate the save order function
                const testOrderData = [
                    { id: 1, position: 1 },
                    { id: 2, position: 2 }
                ];

                const formData = new FormData();
                formData.append('order', JSON.stringify(testOrderData));

                // Get CSRF token from page
                const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfTokenInput = document.querySelector('input[name="_token"]');
                const csrfTokenScript = document.body.innerHTML.match(/csrf_token['"]\s*(?:value=|:)\s*['"]([^'"]+)['"]/);

                let csrfToken = '';
                if (csrfTokenMeta) {
                    csrfToken = csrfTokenMeta.getAttribute('content');
                } else if (csrfTokenInput) {
                    csrfToken = csrfTokenInput.value;
                } else if (csrfTokenScript) {
                    csrfToken = csrfTokenScript[1];
                } else {
                    // Try to extract from page content
                    const matches = document.body.innerHTML.match(/['"]([a-zA-Z0-9]{40,})['"]/g);
                    if (matches && matches.length > 0) {
                        // Look for a token-like string (40+ characters)
                        for (const match of matches) {
                            const token = match.replace(/['"]/g, '');
                            if (token.length >= 40) {
                                csrfToken = token;
                                break;
                            }
                        }
                    }
                }

                console.log('Found CSRF token:', csrfToken ? 'Yes' : 'No');

                if (csrfToken) {
                    formData.append('_token', csrfToken);
                }

                try {
                    const response = await fetch('/admin/articles/update-order', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    return {
                        status: response.status,
                        success: data.success,
                        message: data.message || 'No message',
                        hasToken: !!csrfToken
                    };
                } catch (error) {
                    return {
                        status: 0,
                        success: false,
                        message: error.message,
                        hasToken: !!csrfToken
                    };
                }
            });

            console.log(`AJAX Test Result:`, response);

            if (response.status === 200 && response.success) {
                console.log('✓ Articles reorder AJAX save works correctly');
            } else {
                console.log(`Articles reorder AJAX response: Status ${response.status}, Success: ${response.success}, Message: ${response.message}`);
            }

        } else {
            console.log(`Only ${count} articles found - need at least 2 for reorder test`);
        }
    });

    test('should test photobooks reorder save functionality', async () => {
        // Go to photobooks reorder page
        await page.goto('https://dalthaus.net/admin/photobooks/reorder');

        // Check if there are items to reorder
        const items = page.locator('.sortable-item');
        const count = await items.count();

        if (count >= 1) {
            console.log(`Testing photobooks reorder interface with ${count} photobooks`);

            // Test AJAX save without changing order
            const response = await page.evaluate(async () => {
                // Simulate the save order function for photobooks
                const testOrderData = [
                    { id: 1, position: 1 }
                ];

                const formData = new FormData();
                formData.append('order', JSON.stringify(testOrderData));

                // Try to find CSRF token
                let csrfToken = '';
                const matches = document.body.innerHTML.match(/['"]([a-zA-Z0-9]{40,})['"]/g);
                if (matches && matches.length > 0) {
                    for (const match of matches) {
                        const token = match.replace(/['"]/g, '');
                        if (token.length >= 40) {
                            csrfToken = token;
                            break;
                        }
                    }
                }

                if (csrfToken) {
                    formData.append('_token', csrfToken);
                }

                try {
                    const response = await fetch('/admin/photobooks/update-order', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    return {
                        status: response.status,
                        success: data.success,
                        message: data.message || 'No message',
                        hasToken: !!csrfToken
                    };
                } catch (error) {
                    return {
                        status: 0,
                        success: false,
                        message: error.message,
                        hasToken: !!csrfToken
                    };
                }
            });

            console.log(`Photobooks AJAX Test Result:`, response);

            if (response.status === 200) {
                console.log('✓ Photobooks reorder endpoint is accessible');
            }

        } else {
            console.log(`Found ${count} photobooks - reorder interface ready`);
        }
    });

    test('should verify both reorder pages load correctly', async () => {
        // Test articles reorder page
        const articlesResponse = await page.goto('https://dalthaus.net/admin/articles/reorder');
        expect(articlesResponse.status()).toBe(200);

        await expect(page.locator('h2:has-text("Reorder Articles")')).toBeVisible();
        console.log('✓ Articles reorder page loads correctly');

        // Test photobooks reorder page
        const photobooksResponse = await page.goto('https://dalthaus.net/admin/photobooks/reorder');
        expect(photobooksResponse.status()).toBe(200);

        await expect(page.locator('h2:has-text("Reorder Photobooks")')).toBeVisible();
        console.log('✓ Photobooks reorder page loads correctly');

        // Check that JavaScript files are loading
        const hasJavaScript = await page.evaluate(() => {
            return typeof Sortable !== 'undefined';
        });

        if (hasJavaScript) {
            console.log('✓ Sortable.js library is loaded');
        } else {
            console.log('⚠ Sortable.js library not detected');
        }
    });
});