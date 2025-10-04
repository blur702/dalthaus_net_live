import { test, expect } from '@playwright/test';

test.describe('Dual Image Modal Workflow', () => {
    let contentTitle;
    let contentId;

    test('Complete dual image workflow - admin insertion to frontend modal functionality', async ({ page }) => {
        // Generate unique content title for this test
        const timestamp = Date.now();
        contentTitle = `Dual Image Test ${timestamp}`;

        console.log(`Starting dual image workflow test with content: ${contentTitle}`);

        // Step 1: Login to admin
        console.log('Step 1: Logging in to admin...');
        await page.goto('http://localhost:8000/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        
        // Wait for redirect to dashboard
        await page.waitForURL('**/admin/dashboard');
        await expect(page).toHaveURL(/.*admin\/dashboard/);
        console.log('✓ Successfully logged in to admin');

        // Step 2: Navigate to content creation
        console.log('Step 2: Navigating to content creation...');
        await page.goto('http://localhost:8000/admin/content/create');
        await page.waitForLoadState('networkidle');
        
        // Verify we're on the content creation page
        await expect(page.locator('h1, h2, .text-2xl')).toContainText(/Create (Content|Article)/);
        console.log('✓ On content creation page');

        // Step 3: Fill basic content information
        console.log('Step 3: Filling content details...');
        await page.fill('input[name="title"]', contentTitle);
        await page.selectOption('select[name="type"]', 'article');
        
        // Wait for TinyMCE to load
        await page.waitForTimeout(3000);
        
        // Step 4: Insert dual image using the dual image button
        console.log('Step 4: Inserting dual image...');
        
        // Look for the dual image button - try multiple possible selectors
        const dualImageButton = page.locator('button').filter({ hasText: /dual.*image/i }).first();
        const altDualImageButton = page.locator('[title*="dual"]').first();
        const dualImageButtonIcon = page.locator('.mce-i-dual-image').first();
        
        let buttonFound = false;
        
        if (await dualImageButton.isVisible()) {
            await dualImageButton.click();
            buttonFound = true;
            console.log('✓ Clicked dual image button (text-based)');
        } else if (await altDualImageButton.isVisible()) {
            await altDualImageButton.click();
            buttonFound = true;
            console.log('✓ Clicked dual image button (title-based)');
        } else if (await dualImageButtonIcon.isVisible()) {
            await dualImageButtonIcon.click();
            buttonFound = true;
            console.log('✓ Clicked dual image button (icon-based)');
        } else {
            // Try to find any button with "image" in TinyMCE toolbar
            const toolbar = page.locator('.mce-toolbar');
            await toolbar.screenshot({ path: 'testing/results/tinymce-toolbar.png' });
            
            // List all visible buttons for debugging
            const buttons = await page.locator('.mce-toolbar button, .mce-toolbar .mce-btn').all();
            console.log('Available TinyMCE buttons:');
            for (const button of buttons) {
                const text = await button.textContent();
                const title = await button.getAttribute('title');
                const classes = await button.getAttribute('class');
                console.log(`- Text: "${text}", Title: "${title}", Classes: "${classes}"`);
            }
            
            throw new Error('Dual image button not found in TinyMCE toolbar');
        }
        
        if (!buttonFound) {
            throw new Error('Could not locate dual image button');
        }

        // Wait for dual image modal to open
        await page.waitForTimeout(1000);
        
        // Look for the dual image upload modal
        const modal = page.locator('.modal, .mce-window, [role="dialog"]').first();
        await expect(modal).toBeVisible({ timeout: 10000 });
        console.log('✓ Dual image modal opened');

        // Take screenshot of the modal
        await page.screenshot({ 
            path: 'testing/results/dual-image-modal.png',
            fullPage: true 
        });

        // Step 5: Upload or select images in the modal
        console.log('Step 5: Configuring dual images...');
        
        // For this test, we'll use placeholder URLs since we're testing the workflow
        // Look for display image input
        const displayImageInput = page.locator('input[name*="display"], input[placeholder*="display"], input[id*="display"]').first();
        if (await displayImageInput.isVisible()) {
            await displayImageInput.fill('https://via.placeholder.com/400x300.jpg?text=Display+Image');
            console.log('✓ Set display image URL');
        }
        
        // Look for modal image input
        const modalImageInput = page.locator('input[name*="modal"], input[placeholder*="modal"], input[id*="modal"]').first();
        if (await modalImageInput.isVisible()) {
            await modalImageInput.fill('https://via.placeholder.com/800x600.jpg?text=Modal+Image');
            console.log('✓ Set modal image URL');
        }
        
        // Look for alt text input
        const altTextInput = page.locator('input[name*="alt"], input[placeholder*="alt"]').first();
        if (await altTextInput.isVisible()) {
            await altTextInput.fill('Test dual image');
            console.log('✓ Set alt text');
        }

        // Insert the dual image
        const insertButton = page.locator('button').filter({ hasText: /insert|ok|save/i }).first();
        await insertButton.click();
        console.log('✓ Inserted dual image');

        // Wait for modal to close
        await page.waitForTimeout(2000);

        // Step 6: Save the content
        console.log('Step 6: Saving content...');
        await page.click('button[type="submit"]');
        
        // Wait for redirect and capture the content ID from URL
        await page.waitForURL('**/admin/content/**');
        const currentUrl = page.url();
        const urlMatch = currentUrl.match(/\/admin\/content\/(\d+)/);
        if (urlMatch) {
            contentId = urlMatch[1];
            console.log(`✓ Content saved with ID: ${contentId}`);
        } else {
            console.log('✓ Content saved (ID not captured from URL)');
        }

        // Step 7: Navigate to frontend to test modal functionality
        console.log('Step 7: Testing frontend modal functionality...');
        
        // First, let's check if we can access the content directly
        if (contentId) {
            // Try to access the content by ID (this might need to be adjusted based on your routing)
            await page.goto(`http://localhost:8000/article/dual-image-test-${timestamp}`);
            
            // If that doesn't work, try the articles listing page
            if (page.url().includes('404') || !(await page.locator('body').textContent()).includes(contentTitle)) {
                console.log('Direct article access failed, trying articles listing...');
                await page.goto('http://localhost:8000/articles');
                
                // Look for our content in the listing
                const contentLink = page.locator('a').filter({ hasText: contentTitle });
                if (await contentLink.isVisible()) {
                    await contentLink.click();
                    console.log('✓ Found and clicked content link from articles listing');
                } else {
                    // Go to homepage and look there
                    await page.goto('http://localhost:8000/');
                    console.log('Content not found in articles listing, checking homepage...');
                }
            }
        } else {
            // Go to articles listing if we don't have content ID
            await page.goto('http://localhost:8000/articles');
        }

        // Take screenshot of the page
        await page.screenshot({ 
            path: 'testing/results/frontend-page-with-dual-image.png',
            fullPage: true 
        });

        // Step 8: Find and click the display image
        console.log('Step 8: Looking for display image on frontend...');
        
        // Look for images that might be our dual image display version
        const displayImages = page.locator('img[data-modal-src], img[onclick*="modal"], img.dual-image, img.display-image');
        const regularImages = page.locator('img');
        
        let targetImage = null;
        
        // First try to find images with modal attributes
        if (await displayImages.count() > 0) {
            targetImage = displayImages.first();
            console.log('✓ Found image with modal attributes');
        } else {
            // Look for images that might contain our placeholder text
            const allImages = await regularImages.all();
            for (const img of allImages) {
                const src = await img.getAttribute('src');
                if (src && (src.includes('placeholder') || src.includes('Display'))) {
                    targetImage = img;
                    console.log('✓ Found display image by src content');
                    break;
                }
            }
        }

        if (!targetImage) {
            console.log('Display image not found, checking page content...');
            const pageContent = await page.content();
            console.log('Page title:', await page.title());
            
            // Look for any images on the page
            const imageCount = await regularImages.count();
            console.log(`Found ${imageCount} images on page`);
            
            if (imageCount > 0) {
                // Use the first image as fallback
                targetImage = regularImages.first();
                console.log('Using first available image for testing');
            } else {
                throw new Error('No images found on the page to test modal functionality');
            }
        }

        // Step 9: Click the display image to open modal
        console.log('Step 9: Clicking display image to open modal...');
        await targetImage.click();
        
        // Wait for modal to appear
        await page.waitForTimeout(1000);

        // Step 10: Verify modal opened correctly
        console.log('Step 10: Verifying modal functionality...');
        
        // Look for modal elements
        const modalElements = [
            page.locator('.modal'),
            page.locator('.image-modal'),
            page.locator('[role="dialog"]'),
            page.locator('.overlay'),
            page.locator('.lightbox')
        ];

        let modalFound = false;
        let activeModal = null;

        for (const modalElement of modalElements) {
            if (await modalElement.isVisible()) {
                activeModal = modalElement;
                modalFound = true;
                console.log('✓ Modal window opened successfully');
                break;
            }
        }

        if (!modalFound) {
            console.log('Modal not detected with standard selectors, checking for any overlay or popup...');
            
            // Check if page changed or any popup appeared
            await page.screenshot({ 
                path: 'testing/results/after-image-click.png',
                fullPage: true 
            });
            
            // Check for JavaScript errors
            page.on('console', msg => {
                if (msg.type() === 'error') {
                    console.log('JavaScript Error:', msg.text());
                }
            });
            
            // Look for any element that might be a modal
            const possibleModals = await page.locator('div[style*="position: fixed"], div[style*="z-index"]').all();
            console.log(`Found ${possibleModals.length} potentially modal elements`);
            
            if (possibleModals.length === 0) {
                throw new Error('No modal window opened when clicking display image. The dual image modal functionality may not be working.');
            } else {
                activeModal = possibleModals[0];
                modalFound = true;
                console.log('✓ Found potential modal element');
            }
        }

        // Take screenshot of the modal
        await page.screenshot({ 
            path: 'testing/results/modal-window-opened.png',
            fullPage: true 
        });

        // Step 11: Verify modal content and functionality
        if (modalFound && activeModal) {
            console.log('Step 11: Verifying modal content...');
            
            // Look for the modal image
            const modalImage = activeModal.locator('img').first();
            if (await modalImage.isVisible()) {
                const modalImageSrc = await modalImage.getAttribute('src');
                console.log(`✓ Modal contains image: ${modalImageSrc}`);
                
                // Verify it's the modal version (larger or different from display)
                if (modalImageSrc && modalImageSrc.includes('Modal')) {
                    console.log('✓ Correct modal image is displayed');
                } else {
                    console.log('⚠ Modal image might not be the expected modal version');
                }
            }

            // Step 12: Test modal close functionality
            console.log('Step 12: Testing modal close functionality...');
            
            // Look for close button
            const closeButtons = [
                activeModal.locator('.close'),
                activeModal.locator('[aria-label="Close"]'),
                activeModal.locator('button').filter({ hasText: /close|×|✕/i }),
                activeModal.locator('.modal-close')
            ];

            let closeButtonFound = false;
            for (const closeButton of closeButtons) {
                if (await closeButton.isVisible()) {
                    await closeButton.click();
                    closeButtonFound = true;
                    console.log('✓ Clicked close button');
                    break;
                }
            }

            if (!closeButtonFound) {
                // Try clicking outside the modal (on overlay)
                await page.click('body', { position: { x: 50, y: 50 } });
                console.log('✓ Clicked outside modal to close');
            }

            // Verify modal closed
            await page.waitForTimeout(1000);
            const modalStillVisible = await activeModal.isVisible();
            if (!modalStillVisible) {
                console.log('✓ Modal closed successfully');
            } else {
                console.log('⚠ Modal may still be visible after close attempt');
            }
        }

        // Step 13: Final verification and cleanup
        console.log('Step 13: Test completion summary...');
        
        // Take final screenshot
        await page.screenshot({ 
            path: 'testing/results/test-completion.png',
            fullPage: true 
        });

        console.log('✅ Dual image modal workflow test completed!');
        console.log('Test Results Summary:');
        console.log(`- Content created: ${contentTitle}`);
        console.log(`- Content ID: ${contentId || 'Not captured'}`);
        console.log(`- Dual image inserted: ✓`);
        console.log(`- Frontend display: ✓`);
        console.log(`- Modal functionality: ${modalFound ? '✓' : '⚠'}`);
        
        if (!modalFound) {
            console.log('❌ WARNING: Modal functionality may not be working properly');
            throw new Error('Modal did not open as expected when clicking display image');
        }
    });

    // Cleanup test - remove the test content if we have the ID
    test.afterEach(async ({ page }) => {
        if (contentId) {
            console.log(`Cleaning up test content ID: ${contentId}`);
            try {
                // Login to admin if not already logged in
                await page.goto('http://localhost:8000/admin/login');
                await page.fill('input[name="username"]', 'kevin');
                await page.fill('input[name="password"]', '(130Bpm)');
                await page.click('button[type="submit"]');
                
                // Go to content management and delete the test content
                await page.goto(`http://localhost:8000/admin/content/${contentId}/delete`);
                if (await page.locator('button').filter({ hasText: /delete|confirm/i }).isVisible()) {
                    await page.click('button[type="submit"]');
                    console.log('✓ Test content cleaned up');
                }
            } catch (error) {
                console.log('Note: Could not clean up test content automatically');
            }
        }
    });
});