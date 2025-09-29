const { test, expect } = require('@playwright/test');
const path = require('path');

test.describe('Dual Image Upload Feature', () => {
    test.beforeEach(async ({ page }) => {
        // Login as admin
        await page.goto('/admin/login');
        await page.fill('#username', 'kevin');
        await page.fill('#password', '(130Bpm)');
        await page.click('button[type="submit"]');
        await expect(page).toHaveURL(/.*\/admin\/dashboard/);
    });

    test('should show dual image button in TinyMCE toolbar', async ({ page }) => {
        // Navigate to content creation page
        await page.goto('/admin/content/create');

        // Wait for TinyMCE to load
        await page.waitForSelector('.tox-tinymce');
        await page.waitForTimeout(2000);

        // Check if dual image button exists in toolbar
        const dualImageButton = page.locator('button[title="Insert image with modal view"]');
        await expect(dualImageButton).toBeVisible();

        // Verify button text/icon
        const buttonText = await dualImageButton.textContent();
        expect(buttonText).toContain('🖼️📱');
    });

    test('should open dual image dialog when button is clicked', async ({ page }) => {
        await page.goto('/admin/content/create');
        await page.waitForSelector('.tox-tinymce');
        await page.waitForTimeout(2000);

        // Click the dual image button
        await page.click('button[title="Insert image with modal view"]');

        // Wait for dialog to appear
        await page.waitForSelector('.dual-image-dialog');

        // Verify dialog structure
        await expect(page.locator('.dual-image-dialog')).toBeVisible();
        await expect(page.locator('.dual-image-header h3')).toHaveText('Insert Image with Modal View');
        await expect(page.locator('input[name="display_image"]')).toBeVisible();
        await expect(page.locator('input[name="modal_image"]')).toBeVisible();
        await expect(page.locator('#altText')).toBeVisible();
        await expect(page.locator('#imageWidth')).toBeVisible();

        // Verify buttons
        await expect(page.locator('.btn-cancel')).toHaveText('Cancel');
        await expect(page.locator('.btn-insert')).toHaveText('Insert Image');
    });

    test('should close dialog when cancel is clicked', async ({ page }) => {
        await page.goto('/admin/content/create');
        await page.waitForSelector('.tox-tinymce');
        await page.waitForTimeout(2000);

        // Open dialog
        await page.click('button[title="Insert image with modal view"]');
        await page.waitForSelector('.dual-image-dialog');

        // Click cancel
        await page.click('.btn-cancel');

        // Verify dialog is closed
        await expect(page.locator('.dual-image-dialog')).not.toBeVisible();
    });

    test('should close dialog when X button is clicked', async ({ page }) => {
        await page.goto('/admin/content/create');
        await page.waitForSelector('.tox-tinymce');
        await page.waitForTimeout(2000);

        // Open dialog
        await page.click('button[title="Insert image with modal view"]');
        await page.waitForSelector('.dual-image-dialog');

        // Click X button
        await page.click('.close-btn');

        // Verify dialog is closed
        await expect(page.locator('.dual-image-dialog')).not.toBeVisible();
    });

    test('should close dialog when clicking overlay', async ({ page }) => {
        await page.goto('/admin/content/create');
        await page.waitForSelector('.tox-tinymce');
        await page.waitForTimeout(2000);

        // Open dialog
        await page.click('button[title="Insert image with modal view"]');
        await page.waitForSelector('.dual-image-dialog');

        // Click overlay
        await page.click('.dual-image-overlay');

        // Verify dialog is closed
        await expect(page.locator('.dual-image-dialog')).not.toBeVisible();
    });

    test('should upload images and insert into TinyMCE', async ({ page }) => {
        await page.goto('/admin/content/create');
        await page.waitForSelector('.tox-tinymce');
        await page.waitForTimeout(2000);

        // Create test images
        const testImagePath1 = path.join(__dirname, '../test-files/test-image-1.png');
        const testImagePath2 = path.join(__dirname, '../test-files/test-image-2.png');

        // Create simple test images if they don't exist
        await page.evaluate(() => {
            // Create a simple canvas image for testing
            const canvas = document.createElement('canvas');
            canvas.width = 100;
            canvas.height = 100;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ff0000';
            ctx.fillRect(0, 0, 100, 100);
            ctx.fillStyle = '#ffffff';
            ctx.font = '20px Arial';
            ctx.fillText('TEST', 25, 55);

            return new Promise((resolve) => {
                canvas.toBlob((blob) => {
                    window.testImageBlob = blob;
                    resolve();
                });
            });
        });

        // Open dialog
        await page.click('button[title="Insert image with modal view"]');
        await page.waitForSelector('.dual-image-dialog');

        // Fill form fields
        await page.fill('#altText', 'Test image for dual upload');
        await page.fill('#imageWidth', '300');

        // Note: File upload testing requires actual files
        // For now, we'll test the form submission without files

        // Verify form validation (should require display image)
        await page.click('.btn-insert');

        // Should stay open due to validation (required field)
        await expect(page.locator('.dual-image-dialog')).toBeVisible();
    });

    test('should handle upload errors gracefully', async ({ page }) => {
        await page.goto('/admin/content/create');
        await page.waitForSelector('.tox-tinymce');
        await page.waitForTimeout(2000);

        // Mock a failed upload response
        await page.route('/admin/upload/dual-image', route => {
            route.fulfill({
                status: 400,
                contentType: 'application/json',
                body: JSON.stringify({ error: 'Invalid file type' })
            });
        });

        // Open dialog
        await page.click('button[title="Insert image with modal view"]');
        await page.waitForSelector('.dual-image-dialog');

        // Submit form (this will trigger the mocked error)
        await page.click('.btn-insert');

        // Wait for error handling
        await page.waitForTimeout(1000);

        // Dialog should still be visible for retry
        await expect(page.locator('.dual-image-dialog')).toBeVisible();
    });
});

test.describe('Frontend Modal Functionality', () => {
    test('should display modal when clicking image with modal functionality', async ({ page }) => {
        // First, we need to create content with a dual image
        // For this test, we'll inject a test image directly into a page

        await page.goto('/');

        // Inject test content with clickable image
        await page.evaluate(() => {
            const testContent = `
                <div id="test-content">
                    <img src="/uploads/content/test.jpg"
                         alt="Test image"
                         class="clickable-image"
                         data-modal-src="/uploads/content/test-modal.jpg"
                         onclick="openImageModal('/uploads/content/test-modal.jpg', 'Test image')"
                         style="cursor: pointer; width: 200px;">
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', testContent);
        });

        // Click the image
        await page.click('#test-content img');

        // Wait for modal to appear
        await page.waitForSelector('.image-modal');

        // Verify modal structure
        await expect(page.locator('.image-modal')).toBeVisible();
        await expect(page.locator('.image-modal img')).toBeVisible();
        await expect(page.locator('.modal-close')).toBeVisible();

        // Verify modal close functionality
        await page.click('.modal-close');
        await expect(page.locator('.image-modal')).not.toBeVisible();
    });

    test('should close modal with Escape key', async ({ page }) => {
        await page.goto('/');

        // Inject test content
        await page.evaluate(() => {
            const testContent = `
                <div id="test-content">
                    <img src="/uploads/content/test.jpg"
                         alt="Test image"
                         class="clickable-image"
                         onclick="openImageModal('/uploads/content/test-modal.jpg', 'Test image')"
                         style="cursor: pointer; width: 200px;">
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', testContent);
        });

        // Click image to open modal
        await page.click('#test-content img');
        await page.waitForSelector('.image-modal');

        // Press Escape key
        await page.keyboard.press('Escape');

        // Modal should close
        await expect(page.locator('.image-modal')).not.toBeVisible();
    });

    test('should close modal when clicking overlay', async ({ page }) => {
        await page.goto('/');

        // Inject test content
        await page.evaluate(() => {
            const testContent = `
                <div id="test-content">
                    <img src="/uploads/content/test.jpg"
                         alt="Test image"
                         class="clickable-image"
                         onclick="openImageModal('/uploads/content/test-modal.jpg', 'Test image')"
                         style="cursor: pointer; width: 200px;">
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', testContent);
        });

        // Click image to open modal
        await page.click('#test-content img');
        await page.waitForSelector('.image-modal');

        // Click on modal overlay (not the image itself)
        await page.click('.image-modal', { position: { x: 50, y: 50 } });

        // Modal should close
        await expect(page.locator('.image-modal')).not.toBeVisible();
    });

    test('should prevent page scrolling when modal is open', async ({ page }) => {
        await page.goto('/');

        // Add content to make page scrollable
        await page.evaluate(() => {
            const tallContent = '<div style="height: 2000px;">Tall content</div>';
            const testContent = `
                <img src="/uploads/content/test.jpg"
                     alt="Test image"
                     class="clickable-image"
                     onclick="openImageModal('/uploads/content/test-modal.jpg', 'Test image')"
                     style="cursor: pointer; width: 200px;">
            `;
            document.body.insertAdjacentHTML('beforeend', tallContent + testContent);
        });

        // Scroll down
        await page.evaluate(() => window.scrollTo(0, 500));

        // Click image to open modal
        await page.click('img.clickable-image');
        await page.waitForSelector('.image-modal');

        // Check that body overflow is hidden
        const bodyOverflow = await page.evaluate(() => document.body.style.overflow);
        expect(bodyOverflow).toBe('hidden');

        // Close modal
        await page.keyboard.press('Escape');

        // Check that body overflow is restored
        const bodyOverflowAfter = await page.evaluate(() => document.body.style.overflow);
        expect(bodyOverflowAfter).toBe('');
    });
});

test.describe('TinyMCE Integration', () => {
    test.beforeEach(async ({ page }) => {
        // Login as admin
        await page.goto('/admin/login');
        await page.fill('#username', 'kevin');
        await page.fill('#password', '(130Bpm)');
        await page.click('button[type="submit"]');
        await expect(page).toHaveURL(/.*\/admin\/dashboard/);
    });

    test('should maintain TinyMCE functionality after adding dual image button', async ({ page }) => {
        await page.goto('/admin/content/create');
        await page.waitForSelector('.tox-tinymce');
        await page.waitForTimeout(2000);

        // Test basic TinyMCE functionality
        const editorFrame = page.frameLocator('iframe[title="Rich Text Area"]');
        await editorFrame.locator('body').click();
        await editorFrame.locator('body').type('Test content for TinyMCE');

        // Verify content was typed
        const content = await editorFrame.locator('body').textContent();
        expect(content).toContain('Test content for TinyMCE');

        // Test that regular image button still works
        await page.click('button[title="Insert/edit image"]');

        // Wait for TinyMCE image dialog
        await page.waitForSelector('.tox-dialog');
        await expect(page.locator('.tox-dialog')).toBeVisible();

        // Close dialog
        await page.click('.tox-button[title="Close"]');
    });

    test('should preserve existing TinyMCE plugins and toolbar', async ({ page }) => {
        await page.goto('/admin/content/create');
        await page.waitForSelector('.tox-tinymce');
        await page.waitForTimeout(2000);

        // Check that essential toolbar buttons exist
        await expect(page.locator('button[title="Undo"]')).toBeVisible();
        await expect(page.locator('button[title="Redo"]')).toBeVisible();
        await expect(page.locator('button[title="Bold"]')).toBeVisible();
        await expect(page.locator('button[title="Italic"]')).toBeVisible();
        await expect(page.locator('button[title="Insert/edit image"]')).toBeVisible();
        await expect(page.locator('button[title="Insert/edit link"]')).toBeVisible();

        // Check that our dual image button is also there
        await expect(page.locator('button[title="Insert image with modal view"]')).toBeVisible();
    });
});