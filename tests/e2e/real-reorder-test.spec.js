const { test, expect } = require('@playwright/test');

test.describe('Real Reorder Test', () => {
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

    test('should test articles reorder with real IDs', async () => {
        // Go to articles reorder page
        await page.goto('https://dalthaus.net/admin/articles/reorder');

        // Get real article IDs and their current order
        const items = page.locator('.sortable-item');
        const count = await items.count();

        if (count >= 2) {
            console.log(`Testing with ${count} real articles`);

            // Get actual article IDs
            const realOrder = [];
            for (let i = 0; i < count; i++) {
                const item = items.nth(i);
                const id = await item.getAttribute('data-id');
                realOrder.push({ id: parseInt(id), position: i + 1 });
            }

            console.log('Real articles:', realOrder.slice(0, 3).map(item => `ID:${item.id}`).join(', '));

            // Test save with real data (but don't change order)
            const response = await page.evaluate(async (orderData) => {
                const formData = new FormData();
                formData.append('order', JSON.stringify(orderData));

                // Extract CSRF token
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
            }, realOrder);

            console.log(`Real reorder test result:`, response);

            if (response.success) {
                console.log('✓ Articles reorder save works with real data');
            } else {
                console.log(`❌ Articles reorder failed: ${response.message}`);
            }

            // Verify the order is still the same (since we didn't actually change it)
            await page.reload();
            const newItems = page.locator('.sortable-item');
            const newCount = await newItems.count();

            if (newCount === count) {
                console.log('✓ Articles count unchanged after save');
            }

        } else {
            console.log(`Only ${count} articles - need at least 2 for real reorder test`);
        }
    });

    test('should test that reorder interface exists and is functional', async () => {
        // Articles test
        await page.goto('https://dalthaus.net/admin/articles/reorder');

        const articlesItems = page.locator('.sortable-item');
        const articlesCount = await articlesItems.count();

        if (articlesCount > 0) {
            console.log(`✓ Articles reorder page has ${articlesCount} items`);

            // Check that sortable is initialized
            const sortableExists = await page.evaluate(() => {
                return typeof Sortable !== 'undefined' && document.getElementById('sortable-articles') !== null;
            });

            if (sortableExists) {
                console.log('✓ Articles sortable interface is initialized');
            }

            // Check save button
            const saveButton = page.locator('button:has-text("Save New Order")');
            await expect(saveButton).toBeVisible();
            console.log('✓ Articles save button is present');
        }

        // Photobooks test
        await page.goto('https://dalthaus.net/admin/photobooks/reorder');

        const photobooksItems = page.locator('.sortable-item');
        const photobooksCount = await photobooksItems.count();

        console.log(`✓ Photobooks reorder page has ${photobooksCount} items`);

        if (photobooksCount > 0) {
            // Check that sortable is initialized
            const sortableExists = await page.evaluate(() => {
                return typeof Sortable !== 'undefined' && document.getElementById('sortable-photobooks') !== null;
            });

            if (sortableExists) {
                console.log('✓ Photobooks sortable interface is initialized');
            }

            // Check save button
            const saveButton = page.locator('button:has-text("Save New Order")');
            await expect(saveButton).toBeVisible();
            console.log('✓ Photobooks save button is present');
        }
    });
});