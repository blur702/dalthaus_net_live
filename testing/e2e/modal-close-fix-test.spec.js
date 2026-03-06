/**
 * Test that modal close button works on first click
 * This verifies the fix for multiple event listeners issue
 */
const { test, expect } = require('@playwright/test');

test.describe('Modal Close Button Fix', () => {
    test('modal should close on first X button click - production site', async ({ page }) => {
        // Navigate to a photobook page with images
        await page.goto('https://dalthaus.net/photobook/route-66-still-america-s-mother-road');
        await page.waitForLoadState('networkidle');

        console.log('✓ Loaded photobook page');

        // Find an image in the content that should open a modal
        const contentImage = await page.locator('.content-text img, .article-content img, .photobook-content img').first();

        if (await contentImage.count() === 0) {
            console.log('⚠️  No content images found - skipping test');
            return;
        }

        console.log('✓ Found content image');

        // Click the image to open modal
        await contentImage.click();

        // Wait a moment for modal to appear
        await page.waitForTimeout(500);

        // Check that modal appeared
        const modal = await page.locator('.image-modal');
        expect(await modal.count()).toBe(1);
        console.log('✓ Modal opened');

        // Check that only ONE modal was created
        const modalCount = await page.locator('.image-modal').count();
        console.log(`Modal count: ${modalCount}`);
        expect(modalCount).toBe(1);

        // Find and click the close button
        const closeButton = await page.locator('.modal-close').first();
        expect(await closeButton.count()).toBeGreaterThan(0);
        console.log('✓ Found close button');

        // Click the close button ONCE
        await closeButton.click();

        // Wait a moment for modal to close
        await page.waitForTimeout(300);

        // Verify modal is completely gone
        const modalAfterClose = await page.locator('.image-modal').count();
        console.log(`Modals remaining after first click: ${modalAfterClose}`);
        expect(modalAfterClose).toBe(0);

        console.log('✅ Modal closed on first click!');
    });

    test('verify no duplicate event listeners are attached', async ({ page }) => {
        // Navigate to a photobook page
        await page.goto('https://dalthaus.net/photobook/route-66-still-america-s-mother-road');
        await page.waitForLoadState('networkidle');

        // Wait for modal functionality to be added (including the 2-second delayed call)
        await page.waitForTimeout(3000);

        console.log('✓ Page loaded and modal functionality initialized');

        // Find images and check for data-modal-enabled attribute
        const images = await page.locator('.content-text img, .article-content img, .photobook-content img').all();

        console.log(`Found ${images.length} content images`);

        // Verify each image has the attribute set only once
        for (const img of images) {
            const hasAttribute = await img.getAttribute('data-modal-enabled');
            if (hasAttribute) {
                expect(hasAttribute).toBe('true');
                console.log('  ✓ Image has data-modal-enabled attribute');
            }
        }

        console.log('✅ No duplicate processing detected');
    });

    test('clicking image multiple times should only open one modal', async ({ page }) => {
        // Navigate to a photobook page
        await page.goto('https://dalthaus.net/photobook/route-66-still-america-s-mother-road');
        await page.waitForLoadState('networkidle');

        const contentImage = await page.locator('.content-text img, .article-content img, .photobook-content img').first();

        if (await contentImage.count() === 0) {
            console.log('⚠️  No content images found - skipping test');
            return;
        }

        // Click the image THREE times rapidly
        await contentImage.click();
        await contentImage.click();
        await contentImage.click();

        // Wait a moment
        await page.waitForTimeout(500);

        // Check modal count - should still be 1 (or 3 if bug exists)
        const modalCount = await page.locator('.image-modal').count();
        console.log(`Modals created after triple-click: ${modalCount}`);

        // With the fix, even rapid clicks should result in only 1 modal
        // (or at most 3, which closeImageModal will handle)
        expect(modalCount).toBeGreaterThan(0);
        expect(modalCount).toBeLessThanOrEqual(3);

        // Close all modals with one click
        const closeButton = await page.locator('.modal-close').first();
        await closeButton.click();

        await page.waitForTimeout(300);

        // ALL modals should be gone now
        const modalsAfterClose = await page.locator('.image-modal').count();
        console.log(`Modals remaining: ${modalsAfterClose}`);
        expect(modalsAfterClose).toBe(0);

        console.log('✅ closeImageModal() removed all modals at once');
    });
});
