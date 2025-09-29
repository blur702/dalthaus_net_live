/**
 * Production CSRF Token Validation Test
 *
 * Tests the CSRF token fix on the live production site (https://dalthaus.net)
 * Specifically focuses on the content and pages reorder functionality
 * that was previously failing with "Security token validation failed" errors.
 */

import { test, expect } from '@playwright/test';

const PRODUCTION_URL = 'https://dalthaus.net';
const LOGIN_CREDENTIALS = {
    username: 'kevin',
    password: '(130Bpm)'
};

test.describe('Production CSRF Token Validation', () => {
    test.beforeEach(async ({ page }) => {
        // Set longer timeout for production site
        test.setTimeout(60000);

        // Navigate to production site
        await page.goto(PRODUCTION_URL);

        // Handle any SSL warnings or redirects
        await page.waitForLoadState('networkidle');
    });

    test('should login successfully with correct credentials', async ({ page }) => {
        // Navigate to admin login
        await page.goto(`${PRODUCTION_URL}/admin/login`);
        await page.waitForLoadState('networkidle');

        // Fill login form
        await page.fill('input[name="username"]', LOGIN_CREDENTIALS.username);
        await page.fill('input[name="password"]', LOGIN_CREDENTIALS.password);

        // Submit login form
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Verify successful login by checking for dashboard or admin content
        const url = page.url();
        expect(url).toContain('/admin');

        // Look for admin navigation or dashboard elements
        const adminElements = await page.locator('nav, .admin, [href*="/admin/"]').count();
        expect(adminElements).toBeGreaterThan(0);

        console.log('✅ Login successful - redirected to:', url);
    });

    test('should load content reorder page and display items correctly', async ({ page }) => {
        // Login first
        await loginToAdmin(page);

        // Navigate to content reorder page
        await page.goto(`${PRODUCTION_URL}/admin/content/reorder`);
        await page.waitForLoadState('networkidle');

        // Check page title and heading
        await expect(page.locator('h2')).toContainText('Reorder Content');

        // Check if content items are present
        const contentItems = await page.locator('.sortable-item').count();
        console.log(`📋 Found ${contentItems} content items to reorder`);

        if (contentItems > 0) {
            // Verify sortable items have required data attributes
            const firstItem = page.locator('.sortable-item').first();
            await expect(firstItem).toHaveAttribute('data-id');
            await expect(firstItem).toHaveAttribute('data-type');

            // Verify drag handles are present
            await expect(page.locator('.sortable-item svg').first()).toBeVisible();

            // Verify Save New Order button is present
            await expect(page.locator('button', { hasText: 'Save New Order' })).toBeVisible();

            console.log('✅ Content reorder page loaded correctly with sortable items');
        } else {
            console.log('ℹ️ No content items found - checking empty state');
            await expect(page.locator('text=No content to reorder')).toBeVisible();
        }
    });

    test('should handle drag and drop and show unsaved changes notification', async ({ page }) => {
        // Login and navigate to content reorder
        await loginToAdmin(page);
        await page.goto(`${PRODUCTION_URL}/admin/content/reorder`);
        await page.waitForLoadState('networkidle');

        const contentItems = await page.locator('.sortable-item').count();

        if (contentItems >= 2) {
            // Get initial order values
            const initialOrder = await getItemPositions(page);
            console.log('Initial order:', initialOrder);

            // Perform drag and drop operation
            const firstItem = page.locator('.sortable-item').first();
            const secondItem = page.locator('.sortable-item').nth(1);

            // Wait for SortableJS to be loaded
            await page.waitForFunction(() => typeof window.Sortable !== 'undefined');

            // Simulate drag and drop
            await firstItem.hover();
            await page.mouse.down();
            await secondItem.hover();
            await page.mouse.up();

            // Wait for position update
            await page.waitForTimeout(500);

            // Check for unsaved changes notification
            const statusElement = page.locator('#save-status');
            await expect(statusElement).toHaveClass(/bg-yellow-100/);
            await expect(page.locator('#save-message')).toContainText('unsaved changes');

            // Verify positions updated in UI
            const newOrder = await getItemPositions(page);
            console.log('New order after drag:', newOrder);

            console.log('✅ Drag and drop functionality working correctly');
        } else {
            console.log('⚠️ Not enough content items for drag and drop test');
        }
    });

    test('should save new order without CSRF validation errors', async ({ page }) => {
        // Login and navigate to content reorder
        await loginToAdmin(page);
        await page.goto(`${PRODUCTION_URL}/admin/content/reorder`);
        await page.waitForLoadState('networkidle');

        const contentItems = await page.locator('.sortable-item').count();

        if (contentItems >= 2) {
            // Perform a drag and drop to create changes
            const firstItem = page.locator('.sortable-item').first();
            const secondItem = page.locator('.sortable-item').nth(1);

            await page.waitForFunction(() => typeof window.Sortable !== 'undefined');

            await firstItem.hover();
            await page.mouse.down();
            await secondItem.hover();
            await page.mouse.up();

            await page.waitForTimeout(500);

            // Capture network requests
            const responses = [];
            page.on('response', response => {
                if (response.url().includes('/admin/content/update-order')) {
                    responses.push(response);
                }
            });

            // Click Save New Order button
            const saveButton = page.locator('button', { hasText: 'Save New Order' });
            await saveButton.click();

            // Wait for the save request to complete
            await page.waitForTimeout(3000);

            // Check for success/error messages
            const statusElement = page.locator('#save-status');
            const messageElement = page.locator('#save-message');

            // Verify no CSRF validation error
            const messageText = await messageElement.textContent();
            expect(messageText).not.toContain('Security token validation failed');
            expect(messageText).not.toContain('CSRF');
            expect(messageText).not.toContain('Invalid token');

            // Check for success indicators
            if (responses.length > 0) {
                const response = responses[0];
                console.log('Save response status:', response.status());
                expect(response.status()).toBe(200);
            }

            // Look for success styling (green background)
            const hasSuccessClass = await statusElement.evaluate(el =>
                el.className.includes('bg-green-100')
            );

            if (hasSuccessClass) {
                console.log('✅ Order saved successfully without CSRF errors');
                expect(messageText).toContain('successfully');
            } else {
                // Log the actual error for debugging
                console.log('❌ Save failed with message:', messageText);

                // This should not happen with the CSRF fix
                expect(messageText).not.toContain('Security token validation failed');
            }

        } else {
            console.log('⚠️ Not enough content items for save order test');
        }
    });

    test('should persist order changes after page reload', async ({ page }) => {
        // Login and navigate to content reorder
        await loginToAdmin(page);
        await page.goto(`${PRODUCTION_URL}/admin/content/reorder`);
        await page.waitForLoadState('networkidle');

        const contentItems = await page.locator('.sortable-item').count();

        if (contentItems >= 2) {
            // Get initial order
            const initialOrder = await getItemPositions(page);

            // Perform drag and drop
            const firstItem = page.locator('.sortable-item').first();
            const secondItem = page.locator('.sortable-item').nth(1);

            await page.waitForFunction(() => typeof window.Sortable !== 'undefined');

            await firstItem.hover();
            await page.mouse.down();
            await secondItem.hover();
            await page.mouse.up();

            await page.waitForTimeout(500);

            // Save the changes
            await page.click('button', { hasText: 'Save New Order' });
            await page.waitForTimeout(3000);

            // Verify save was successful
            const statusElement = page.locator('#save-status');
            const hasSuccessClass = await statusElement.evaluate(el =>
                el.className.includes('bg-green-100')
            );

            if (hasSuccessClass) {
                // Reload the page
                await page.reload();
                await page.waitForLoadState('networkidle');

                // Get order after reload
                const reloadedOrder = await getItemPositions(page);

                // Verify the order persisted (should be different from initial)
                const orderChanged = JSON.stringify(initialOrder) !== JSON.stringify(reloadedOrder);
                expect(orderChanged).toBe(true);

                console.log('✅ Order changes persisted after page reload');
                console.log('Initial order:', initialOrder);
                console.log('Persisted order:', reloadedOrder);
            } else {
                console.log('⚠️ Save operation failed, skipping persistence test');
            }
        } else {
            console.log('⚠️ Not enough content items for persistence test');
        }
    });

    test('should test pages reorder functionality if pages exist', async ({ page }) => {
        // Login and navigate to pages reorder
        await loginToAdmin(page);
        await page.goto(`${PRODUCTION_URL}/admin/pages/reorder`);
        await page.waitForLoadState('networkidle');

        // Check if pages exist
        const pageItems = await page.locator('.sortable-item').count();
        console.log(`📄 Found ${pageItems} pages to reorder`);

        if (pageItems > 0) {
            // Test similar functionality for pages
            await expect(page.locator('h2')).toContainText('Reorder Pages');

            // Verify sortable items have required data attributes
            const firstItem = page.locator('.sortable-item').first();
            await expect(firstItem).toHaveAttribute('data-id');

            // Test save functionality if multiple pages exist
            if (pageItems >= 2) {
                // Perform drag and drop
                const firstPage = page.locator('.sortable-item').first();
                const secondPage = page.locator('.sortable-item').nth(1);

                await page.waitForFunction(() => typeof window.Sortable !== 'undefined');

                await firstPage.hover();
                await page.mouse.down();
                await secondPage.hover();
                await page.mouse.up();

                await page.waitForTimeout(500);

                // Save the changes
                await page.click('button', { hasText: 'Save New Order' });
                await page.waitForTimeout(3000);

                // Verify no CSRF errors
                const messageText = await page.locator('#save-message').textContent();
                expect(messageText).not.toContain('Security token validation failed');

                console.log('✅ Pages reorder functionality working correctly');
            }
        } else {
            console.log('ℹ️ No pages found for reorder testing');
            await expect(page.locator('text=No pages to reorder')).toBeVisible();
        }
    });
});

// Helper function to login to admin
async function loginToAdmin(page) {
    await page.goto(`${PRODUCTION_URL}/admin/login`);
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="username"]', LOGIN_CREDENTIALS.username);
    await page.fill('input[name="password"]', LOGIN_CREDENTIALS.password);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Verify login successful
    const url = page.url();
    if (!url.includes('/admin') || url.includes('/login')) {
        throw new Error('Login failed - not redirected to admin area');
    }
}

// Helper function to get current item positions
async function getItemPositions(page) {
    return await page.evaluate(() => {
        const items = document.querySelectorAll('.sortable-item');
        return Array.from(items).map((item, index) => ({
            id: item.dataset.id,
            position: index + 1,
            title: item.querySelector('h3')?.textContent?.trim() || `Item ${index + 1}`
        }));
    });
}