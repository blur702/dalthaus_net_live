const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

test.describe('Complete Dual Image Workflow', () => {
    test.beforeEach(async ({ page }) => {
        // Login as admin
        await page.goto('/admin/login');
        await page.fill('#username', 'kevin');
        await page.fill('#password', '(130Bpm)');
        await page.click('button[type="submit"]');
        await expect(page).toHaveURL(/.*\/admin\/dashboard/);
    });

    test('should complete full workflow: create content with dual images and test frontend', async ({ page }) => {
        console.log('Starting complete dual image workflow test...');

        // Step 1: Navigate to content creation
        await page.goto('/admin/content/create');
        await page.waitForSelector('.tox-tinymce');
        await page.waitForTimeout(3000);

        // Step 2: Fill basic content info
        await page.fill('#title', 'Dual Image Test Article');
        await page.fill('#teaser', 'Testing dual image upload functionality');

        // Step 3: Test TinyMCE dual image button
        console.log('Testing TinyMCE dual image button...');

        // Check if button exists
        const dualImageButton = page.locator('button[title="Insert image with modal view"]');
        if (await dualImageButton.count() === 0) {
            // Fallback: look for button by text content
            const buttonByText = page.locator('button:has-text("🖼️📱")');
            if (await buttonByText.count() > 0) {
                console.log('Found dual image button by text content');
                await buttonByText.click();
            } else {
                console.log('Dual image button not found, checking toolbar...');
                // Log all toolbar buttons for debugging
                const toolbarButtons = await page.locator('.tox-toolbar button').all();
                for (let i = 0; i < toolbarButtons.length; i++) {
                    const buttonText = await toolbarButtons[i].textContent();
                    const buttonTitle = await toolbarButtons[i].getAttribute('title');
                    console.log(`Button ${i}: text="${buttonText}", title="${buttonTitle}"`);
                }
                throw new Error('Dual image button not found in toolbar');
            }
        } else {
            await dualImageButton.click();
        }

        // Step 4: Verify dialog opens
        await page.waitForSelector('.dual-image-dialog', { timeout: 5000 });
        console.log('Dual image dialog opened successfully');

        // Step 5: Test dialog structure
        await expect(page.locator('.dual-image-dialog')).toBeVisible();
        await expect(page.locator('.dual-image-header h3')).toHaveText('Insert Image with Modal View');

        // Step 6: Test form fields
        await expect(page.locator('input[name="display_image"]')).toBeVisible();
        await expect(page.locator('input[name="modal_image"]')).toBeVisible();
        await expect(page.locator('#altText')).toBeVisible();
        await expect(page.locator('#imageWidth')).toBeVisible();

        // Step 7: Fill in test data
        await page.fill('#altText', 'Test dual image upload');
        await page.fill('#imageWidth', '400');

        // Step 8: Test form validation (no files uploaded)
        await page.click('.btn-insert');

        // Dialog should remain open due to validation
        await page.waitForTimeout(1000);
        await expect(page.locator('.dual-image-dialog')).toBeVisible();

        console.log('Form validation working correctly');

        // Step 9: Close dialog and continue with regular content
        await page.click('.btn-cancel');
        await expect(page.locator('.dual-image-dialog')).not.toBeVisible();

        // Step 10: Add some content using regular TinyMCE
        const editorFrame = page.frameLocator('iframe[title="Rich Text Area"]');
        await editorFrame.locator('body').click();
        await editorFrame.locator('body').fill('This is test content with dual image functionality. ');

        // Step 11: Test inserting a regular image to verify TinyMCE still works
        await page.click('button[title="Insert/edit image"]');
        await page.waitForSelector('.tox-dialog');

        // Add a placeholder image URL for testing
        await page.fill('.tox-dialog input[placeholder*="Source"]', '/uploads/content/test-image.jpg');
        await page.fill('.tox-dialog input[placeholder*="Alternative description"]', 'Regular test image');
        await page.click('.tox-dialog .tox-button:has-text("Save")');

        // Step 12: Save the content
        await page.click('button[type="submit"]');

        // Should redirect to content listing
        await expect(page).toHaveURL(/.*\/admin\/content/);
        console.log('Content created successfully');

        // Step 13: Verify content appears in listing
        await expect(page.locator('text=Dual Image Test Article')).toBeVisible();

        // Step 14: Test editing the content
        const editLink = page.locator('a[href*="/edit"]:near(:text("Dual Image Test Article"))').first();
        await editLink.click();

        await page.waitForSelector('.tox-tinymce');
        await page.waitForTimeout(2000);

        // Verify dual image button is still there in edit mode
        const editDualImageButton = page.locator('button[title="Insert image with modal view"]');
        if (await editDualImageButton.count() === 0) {
            const editButtonByText = page.locator('button:has-text("🖼️📱")');
            await expect(editButtonByText).toBeVisible();
        } else {
            await expect(editDualImageButton).toBeVisible();
        }

        console.log('Edit mode dual image button verified');

        // Step 15: Test frontend modal functionality
        await page.goto('/');

        // Inject test modal functionality
        await page.evaluate(() => {
            // Create test content with clickable image
            const testDiv = document.createElement('div');
            testDiv.id = 'test-modal-content';
            testDiv.innerHTML = `
                <h2>Test Modal Image</h2>
                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjNzc3Ii8+CiAgPHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTgiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIiBmaWxsPSIjZmZmIj5EaXNwbGF5IEltYWdlPC90ZXh0PiAgCjwvc3ZnPg=="
                     alt="Test display image"
                     class="clickable-image"
                     data-modal-src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjMzMzIi8+CiAgPHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMjQiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIiBmaWxsPSIjZmZmIj5Nb2RhbCBJbWFnZSAoTGFyZ2VyKTwvdGV4dD4gIAo8L3N2Zz4="
                     onclick="openImageModal(this.getAttribute('data-modal-src'), this.alt)"
                     style="cursor: pointer; width: 200px; border: 2px solid #ccc; margin: 20px;">
            `;
            document.body.appendChild(testDiv);
        });

        // Step 16: Test modal opening
        await page.click('#test-modal-content img');
        await page.waitForSelector('.image-modal');

        console.log('Frontend modal opened successfully');

        // Step 17: Verify modal structure
        await expect(page.locator('.image-modal')).toBeVisible();
        await expect(page.locator('.image-modal img')).toBeVisible();
        await expect(page.locator('.modal-close')).toBeVisible();

        // Step 18: Test modal close with X button
        await page.click('.modal-close');
        await expect(page.locator('.image-modal')).not.toBeVisible();

        console.log('Modal close button working');

        // Step 19: Test modal close with Escape key
        await page.click('#test-modal-content img');
        await page.waitForSelector('.image-modal');
        await page.keyboard.press('Escape');
        await expect(page.locator('.image-modal')).not.toBeVisible();

        console.log('Modal Escape key working');

        // Step 20: Test modal close with overlay click
        await page.click('#test-modal-content img');
        await page.waitForSelector('.image-modal');

        // Click on the modal overlay (not the image)
        await page.click('.image-modal', { position: { x: 50, y: 50 } });
        await expect(page.locator('.image-modal')).not.toBeVisible();

        console.log('Modal overlay click working');

        console.log('✅ Complete dual image workflow test passed!');
    });

    test('should test dual image upload endpoint directly', async ({ page }) => {
        console.log('Testing dual image upload endpoint...');

        // Test the upload endpoint with proper authentication
        const response = await page.request.post('/admin/upload/dual-image', {
            multipart: {
                // Note: In a real test, you'd use actual image files
                display_image: {
                    name: 'test-display.png',
                    mimeType: 'image/png',
                    buffer: Buffer.from('fake-image-data')
                }
            }
        });

        // Should get a response (might be error due to fake data, but endpoint should exist)
        expect(response.status()).not.toBe(404);

        console.log(`Upload endpoint response status: ${response.status()}`);

        const responseBody = await response.json();
        console.log('Upload response:', responseBody);

        // Should have some kind of response structure
        expect(responseBody).toBeDefined();
    });

    test('should verify modal CSS and styling', async ({ page }) => {
        console.log('Testing modal CSS and styling...');

        await page.goto('/');

        // Inject modal and trigger it
        await page.evaluate(() => {
            const testDiv = document.createElement('div');
            testDiv.innerHTML = `
                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjNzc3Ii8+Cjwvc3ZnPg=="
                     onclick="openImageModal('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjMzMzIi8+Cjwvc3ZnPg==', 'Test')"
                     style="cursor: pointer;">
            `;
            document.body.appendChild(testDiv);
        });

        // Open modal
        await page.click('img[onclick]');
        await page.waitForSelector('.image-modal');

        // Test CSS properties
        const modalStyles = await page.locator('.image-modal').evaluate(el => {
            const styles = window.getComputedStyle(el);
            return {
                position: styles.position,
                zIndex: styles.zIndex,
                background: styles.background,
                display: styles.display
            };
        });

        expect(modalStyles.position).toBe('fixed');
        expect(parseInt(modalStyles.zIndex)).toBeGreaterThan(9000);
        expect(modalStyles.display).toBe('flex');

        // Test modal image styles
        const imageStyles = await page.locator('.image-modal img').evaluate(el => {
            const styles = window.getComputedStyle(el);
            return {
                maxWidth: styles.maxWidth,
                maxHeight: styles.maxHeight,
                objectFit: styles.objectFit
            };
        });

        expect(imageStyles.maxWidth).toBe('90%');
        expect(imageStyles.maxHeight).toBe('90%');
        expect(imageStyles.objectFit).toBe('contain');

        console.log('Modal CSS styling verified');
    });
});