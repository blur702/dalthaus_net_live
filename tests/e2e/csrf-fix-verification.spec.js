/**
 * CSRF Fix Verification Test
 *
 * This test specifically verifies that the CSRF token fix is working correctly
 * and that the data transformation fix allows order saving to work properly.
 */

import { test, expect } from '@playwright/test';

const PRODUCTION_URL = 'https://dalthaus.net';
const LOGIN_CREDENTIALS = {
    username: 'kevin',
    password: '(130Bpm)'
};

test.describe('CSRF Fix Verification', () => {
    test('should successfully save order changes after the fix', async ({ page }) => {
        test.setTimeout(90000);

        // Login to admin
        await page.goto(`${PRODUCTION_URL}/admin/login`);
        await page.waitForLoadState('networkidle');

        await page.fill('input[name="username"]', LOGIN_CREDENTIALS.username);
        await page.fill('input[name="password"]', LOGIN_CREDENTIALS.password);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Navigate to content reorder
        await page.goto(`${PRODUCTION_URL}/admin/content/reorder`);
        await page.waitForLoadState('networkidle');

        const contentItems = await page.locator('.sortable-item').count();
        console.log(`Found ${contentItems} content items`);

        if (contentItems >= 2) {
            // Capture all network responses for update-order
            const responses = [];
            page.on('response', response => {
                if (response.url().includes('/admin/content/update-order')) {
                    responses.push(response);
                }
            });

            // Get initial order for comparison
            const initialOrder = await getItemOrder(page);
            console.log('Initial order:', initialOrder.slice(0, 3)); // Show first 3 items

            // Perform drag and drop
            const firstItem = page.locator('.sortable-item').first();
            const secondItem = page.locator('.sortable-item').nth(1);

            await page.waitForFunction(() => typeof window.Sortable !== 'undefined');

            await firstItem.hover();
            await page.mouse.down();
            await secondItem.hover();
            await page.mouse.up();

            await page.waitForTimeout(1000);

            // Get new order to confirm change
            const modifiedOrder = await getItemOrder(page);
            console.log('Modified order:', modifiedOrder.slice(0, 3)); // Show first 3 items

            // Verify the order actually changed
            expect(initialOrder[0]).not.toEqual(modifiedOrder[0]);

            // Save the changes
            const saveButton = page.locator('button', { hasText: 'Save New Order' });
            await saveButton.click();

            // Wait for the response
            await page.waitForTimeout(5000);

            // Verify the response
            expect(responses.length).toBeGreaterThan(0);
            const response = responses[0];

            console.log('Response status:', response.status());
            expect(response.status()).toBe(200);

            const responseBody = await response.text();
            console.log('Response body:', responseBody);

            const jsonResponse = JSON.parse(responseBody);

            // CRITICAL: Verify no CSRF errors
            expect(jsonResponse.message).not.toContain('Security token validation failed');
            expect(jsonResponse.message).not.toContain('CSRF');
            expect(jsonResponse.message).not.toContain('Invalid token');

            // CRITICAL: Verify success
            expect(jsonResponse.success).toBe(true);
            expect(jsonResponse.message).toContain('successfully');

            console.log('✅ Order saved successfully:', jsonResponse.message);

            // Check UI feedback
            const statusElement = page.locator('#save-status');
            await expect(statusElement).toHaveClass(/bg-green-100/);

            const statusMessage = await page.locator('#save-message').textContent();
            expect(statusMessage).toContain('successfully');

            console.log('✅ UI shows success feedback:', statusMessage);

            // FINAL VERIFICATION: Reload page and check persistence
            await page.reload();
            await page.waitForLoadState('networkidle');

            const reloadedOrder = await getItemOrder(page);
            console.log('Persisted order:', reloadedOrder.slice(0, 3)); // Show first 3 items

            // Verify the changes persisted
            expect(reloadedOrder[0]).toEqual(modifiedOrder[0]);
            expect(reloadedOrder).not.toEqual(initialOrder);

            console.log('✅ Order changes persisted after page reload');

        } else {
            console.log('⚠️ Not enough content items for order testing');
        }
    });

    test('should show detailed error information if save fails', async ({ page }) => {
        test.setTimeout(60000);

        // Login and navigate to content reorder
        await page.goto(`${PRODUCTION_URL}/admin/login`);
        await page.fill('input[name="username"]', LOGIN_CREDENTIALS.username);
        await page.fill('input[name="password"]', LOGIN_CREDENTIALS.password);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        await page.goto(`${PRODUCTION_URL}/admin/content/reorder`);
        await page.waitForLoadState('networkidle');

        // Test save without any changes to see if that works
        console.log('Testing save without changes...');

        const responses = [];
        page.on('response', response => {
            if (response.url().includes('/admin/content/update-order')) {
                responses.push(response);
            }
        });

        await page.click('button', { hasText: 'Save New Order' });
        await page.waitForTimeout(3000);

        if (responses.length > 0) {
            const response = responses[0];
            const responseBody = await response.text();
            console.log('Save without changes response:', responseBody);

            const jsonResponse = JSON.parse(responseBody);

            // Should still not have CSRF errors
            expect(jsonResponse.message).not.toContain('Security token validation failed');

            if (jsonResponse.success) {
                console.log('✅ Save without changes succeeded');
            } else {
                console.log('❌ Save without changes failed:', jsonResponse.message);
                // But it shouldn't be a CSRF issue
                expect(jsonResponse.message).not.toContain('CSRF');
            }
        }
    });
});

// Helper function to get current item order
async function getItemOrder(page) {
    return await page.evaluate(() => {
        const items = document.querySelectorAll('.sortable-item');
        return Array.from(items).map(item => ({
            id: item.dataset.id,
            title: item.querySelector('h3')?.textContent?.trim() || 'Unknown'
        }));
    });
}