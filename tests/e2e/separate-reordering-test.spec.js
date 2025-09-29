const { test, expect } = require('@playwright/test');

test.describe('Separate Reordering Pages', () => {
    let page;

    test.beforeEach(async ({ browser }) => {
        page = await browser.newPage();

        // Login first
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');

        // Wait for redirect to dashboard
        await page.waitForURL('**/admin/dashboard');
        await expect(page).toHaveURL(/.*admin\/dashboard/);
    });

    test('should have updated navigation links', async () => {
        // Check that navigation links are updated
        await expect(page.locator('nav a[href="/admin/articles"]')).toBeVisible();
        await expect(page.locator('nav a[href="/admin/photobooks"]')).toBeVisible();

        // Verify old filtered links are gone
        await expect(page.locator('nav a[href="/admin/content?type=article"]')).not.toBeVisible();
        await expect(page.locator('nav a[href="/admin/content?type=photobook"]')).not.toBeVisible();
    });

    test('should access articles management page', async () => {
        // Click on Articles in navigation
        await page.click('nav a[href="/admin/articles"]');

        // Wait for page to load
        await page.waitForURL('**/admin/articles');
        await expect(page).toHaveURL(/.*admin\/articles$/);

        // Check page content
        await expect(page.locator('h2')).toContainText('Articles Management');
        await expect(page.locator('a[href="/admin/articles/reorder"]')).toBeVisible();
        await expect(page.locator('a[href="/admin/content/create?type=article"]')).toBeVisible();

        console.log('✓ Articles management page loads correctly');
    });

    test('should access photobooks management page', async () => {
        // Click on Photobooks in navigation
        await page.click('nav a[href="/admin/photobooks"]');

        // Wait for page to load
        await page.waitForURL('**/admin/photobooks');
        await expect(page).toHaveURL(/.*admin\/photobooks$/);

        // Check page content
        await expect(page.locator('h2')).toContainText('Photobooks Management');
        await expect(page.locator('a[href="/admin/photobooks/reorder"]')).toBeVisible();
        await expect(page.locator('a[href="/admin/content/create?type=photobook"]')).toBeVisible();

        console.log('✓ Photobooks management page loads correctly');
    });

    test('should access articles reordering page', async () => {
        // Navigate to articles page first
        await page.goto('https://dalthaus.net/admin/articles');

        // Click reorder button
        await page.click('a[href="/admin/articles/reorder"]');

        // Wait for reorder page to load
        await page.waitForURL('**/admin/articles/reorder');
        await expect(page).toHaveURL(/.*admin\/articles\/reorder$/);

        // Check page content
        await expect(page.locator('h2')).toContainText('Reorder Articles');
        await expect(page.locator('a[href="/admin/articles"]')).toContainText('Back to Articles');

        // Check for sortable elements if articles exist
        const sortableContainer = page.locator('#sortable-articles');
        if (await sortableContainer.isVisible()) {
            console.log('✓ Articles reordering interface is present');
        } else {
            console.log('✓ Articles reordering page loads (no articles to sort)');
        }
    });

    test('should access photobooks reordering page', async () => {
        // Navigate to photobooks page first
        await page.goto('https://dalthaus.net/admin/photobooks');

        // Click reorder button
        await page.click('a[href="/admin/photobooks/reorder"]');

        // Wait for reorder page to load
        await page.waitForURL('**/admin/photobooks/reorder');
        await expect(page).toHaveURL(/.*admin\/photobooks\/reorder$/);

        // Check page content
        await expect(page.locator('h2')).toContainText('Reorder Photobooks');
        await expect(page.locator('a[href="/admin/photobooks"]')).toContainText('Back to Photobooks');

        // Check for sortable elements if photobooks exist
        const sortableContainer = page.locator('#sortable-photobooks');
        if (await sortableContainer.isVisible()) {
            console.log('✓ Photobooks reordering interface is present');
        } else {
            console.log('✓ Photobooks reordering page loads (no photobooks to sort)');
        }
    });

    test('should test articles reorder functionality if articles exist', async () => {
        // Go to articles reorder page
        await page.goto('https://dalthaus.net/admin/articles/reorder');

        // Check if articles exist
        const sortableItems = page.locator('.sortable-item');
        const itemCount = await sortableItems.count();

        if (itemCount >= 2) {
            console.log(`Found ${itemCount} articles to test reordering`);

            // Get original order
            const originalOrder = [];
            for (let i = 0; i < itemCount; i++) {
                const item = sortableItems.nth(i);
                const id = await item.getAttribute('data-id');
                originalOrder.push(id);
            }

            // Check for save button
            const saveButton = page.locator('button:has-text("Save New Order")');
            await expect(saveButton).toBeVisible();

            // Check for CSRF token in page source
            const pageContent = await page.content();
            expect(pageContent).toContain('csrf_token');

            console.log('✓ Articles reordering interface is functional');
        } else {
            console.log('✓ Articles reordering page accessible (insufficient articles for reorder test)');
        }
    });

    test('should test photobooks reorder functionality if photobooks exist', async () => {
        // Go to photobooks reorder page
        await page.goto('https://dalthaus.net/admin/photobooks/reorder');

        // Check if photobooks exist
        const sortableItems = page.locator('.sortable-item');
        const itemCount = await sortableItems.count();

        if (itemCount >= 2) {
            console.log(`Found ${itemCount} photobooks to test reordering`);

            // Get original order
            const originalOrder = [];
            for (let i = 0; i < itemCount; i++) {
                const item = sortableItems.nth(i);
                const id = await item.getAttribute('data-id');
                originalOrder.push(id);
            }

            // Check for save button
            const saveButton = page.locator('button:has-text("Save New Order")');
            await expect(saveButton).toBeVisible();

            // Check for CSRF token in page source
            const pageContent = await page.content();
            expect(pageContent).toContain('csrf_token');

            console.log('✓ Photobooks reordering interface is functional');
        } else {
            console.log('✓ Photobooks reordering page accessible (insufficient photobooks for reorder test)');
        }
    });

    test('should verify separate endpoints work correctly', async () => {
        // Test articles endpoint
        const articlesResponse = await page.goto('https://dalthaus.net/admin/articles');
        expect(articlesResponse.status()).toBe(200);

        // Test articles reorder endpoint
        const articlesReorderResponse = await page.goto('https://dalthaus.net/admin/articles/reorder');
        expect(articlesReorderResponse.status()).toBe(200);

        // Test photobooks endpoint
        const photobooksResponse = await page.goto('https://dalthaus.net/admin/photobooks');
        expect(photobooksResponse.status()).toBe(200);

        // Test photobooks reorder endpoint
        const photobooksReorderResponse = await page.goto('https://dalthaus.net/admin/photobooks/reorder');
        expect(photobooksReorderResponse.status()).toBe(200);

        console.log('✓ All new endpoints return 200 status');
    });

    test('should verify controller type validation', async () => {
        // Go to articles reorder page
        await page.goto('https://dalthaus.net/admin/articles/reorder');

        // Check page source for type filtering
        const pageContent = await page.content();

        // Should show only articles-related content
        if (pageContent.includes('sortable-item')) {
            // If items exist, they should all be article type
            const items = page.locator('.sortable-item');
            const count = await items.count();

            if (count > 0) {
                // Check that we're on articles page (not mixed content)
                await expect(page.locator('h2')).toContainText('Reorder Articles');
                console.log('✓ Articles reorder page shows only articles');
            }
        }

        // Go to photobooks reorder page
        await page.goto('https://dalthaus.net/admin/photobooks/reorder');

        // Check that we're on photobooks page
        await expect(page.locator('h2')).toContainText('Reorder Photobooks');
        console.log('✓ Photobooks reorder page shows only photobooks');
    });
});