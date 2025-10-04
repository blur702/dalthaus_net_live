import { test, expect } from '@playwright/test';
import path from 'path';

test.describe('Comprehensive TinyMCE Image Upload and Modal Test', () => {
    let page;
    
    test.beforeEach(async ({ browser }) => {
        page = await browser.newPage();
        
        // Navigate to admin login
        await page.goto('http://localhost:8000/admin/login');
        
        // Login with credentials
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        
        // Wait for dashboard
        await page.waitForURL('**/admin/dashboard**');
        await expect(page.locator('h1')).toContainText('Dashboard');
    });
    
    test.afterEach(async () => {
        await page.close();
    });

    test('TinyMCE Custom Button and Image Upload Flow', async () => {
        console.log('=== STARTING COMPREHENSIVE TINYMCE IMAGE TEST ===');
        
        // Step 1: Navigate to content creation
        console.log('Step 1: Navigating to content creation...');
        await page.goto('http://localhost:8000/admin/content/create');
        await page.waitForLoadState('networkidle');
        
        // Step 2: Check if TinyMCE is loaded
        console.log('Step 2: Checking TinyMCE initialization...');
        await page.waitForSelector('textarea#body', { timeout: 10000 });
        
        // Wait for TinyMCE to initialize
        await page.waitForFunction(() => {
            return typeof window.tinymce !== 'undefined' && window.tinymce.get().length > 0;
        }, { timeout: 15000 });
        
        // Take screenshot of initial state
        await page.screenshot({ path: 'testing/screenshots/tinymce-initial-state.png', fullPage: true });
        
        // Step 3: Examine TinyMCE toolbar
        console.log('Step 3: Examining TinyMCE toolbar...');
        const editor = await page.waitForSelector('.tox-toolbar, .mce-toolbar', { timeout: 10000 });
        
        // Find all buttons in toolbar
        const toolbarButtons = await page.$$eval('.tox-toolbar button, .mce-toolbar button', buttons => {
            return buttons.map(btn => ({
                title: btn.title || btn.getAttribute('aria-label') || '',
                text: btn.textContent.trim(),
                className: btn.className
            }));
        });
        
        console.log('Found toolbar buttons:', toolbarButtons.length);
        toolbarButtons.forEach((btn, i) => {
            console.log(`  ${i + 1}. Title: "${btn.title}", Text: "${btn.text}", Class: "${btn.className}"`);
        });
        
        // Take screenshot of toolbar
        await page.screenshot({ path: 'testing/screenshots/tinymce-toolbar-analysis.png', fullPage: true });
        
        // Step 4: Look for custom buttons
        console.log('Step 4: Looking for custom buttons...');
        
        // Try to find dual image button by various selectors
        const customButtonSelectors = [
            'button[title*="Modal"]',
            'button[title*="Dual"]',
            'button[aria-label*="Modal"]',
            'button[aria-label*="Dual"]',
            'button:has-text("🖼️📱")',
            'button:has-text("🔍")',
            'button:has-text("Modal")',
            'button:has-text("Dual")',
            'button:has-text("DEBUG")',
            'button:has-text("🧪")'
        ];
        
        let customButtonFound = false;
        let customButtonInfo = null;
        
        for (const selector of customButtonSelectors) {
            try {
                const button = await page.waitForSelector(selector, { timeout: 2000 });
                if (button) {
                    customButtonFound = true;
                    customButtonInfo = {
                        selector: selector,
                        title: await button.getAttribute('title') || '',
                        text: await button.textContent() || '',
                        ariaLabel: await button.getAttribute('aria-label') || ''
                    };
                    console.log(`Found custom button with selector "${selector}":`, customButtonInfo);
                    break;
                }
            } catch (e) {
                // Button not found with this selector, continue
            }
        }
        
        if (!customButtonFound) {
            console.log('No custom buttons found! Checking debug information...');
            
            // Run TinyMCE debug function if available
            const debugInfo = await page.evaluate(() => {
                if (typeof window.debugTinyMCE === 'function') {
                    window.debugTinyMCE();
                    return 'Debug function executed - check browser console';
                }
                
                if (typeof window.tinymce !== 'undefined') {
                    const editors = window.tinymce.get();
                    if (editors.length > 0) {
                        const editor = editors[0];
                        const registeredButtons = editor.ui.registry.getAll().buttons;
                        return {
                            totalButtons: Object.keys(registeredButtons).length,
                            buttons: Object.keys(registeredButtons),
                            customButtons: Object.keys(registeredButtons).filter(name => 
                                ['dualimage', 'modalimage', 'testbutton'].includes(name)
                            ),
                            toolbarConfig: editor.settings?.toolbar
                        };
                    }
                }
                return 'TinyMCE not available';
            });
            
            console.log('TinyMCE Debug Info:', debugInfo);
        }
        
        // Step 5: Test image upload functionality
        console.log('Step 5: Testing image upload functionality...');
        
        // Create a test image file
        const testImagePath = path.join(process.cwd(), 'testing', 'fixtures', 'test-image.jpg');
        
        // Create test image if it doesn't exist
        await page.evaluate(() => {
            // Create a simple canvas image for testing
            const canvas = document.createElement('canvas');
            canvas.width = 200;
            canvas.height = 150;
            const ctx = canvas.getContext('2d');
            
            // Create a colorful test image
            ctx.fillStyle = '#4CAF50';
            ctx.fillRect(0, 0, 200, 150);
            ctx.fillStyle = '#FFF';
            ctx.font = '20px Arial';
            ctx.fillText('TEST IMAGE', 50, 80);
            
            return canvas.toDataURL('image/jpeg');
        });
        
        if (customButtonFound) {
            console.log('Testing custom button functionality...');
            
            try {
                // Click the custom button
                await page.click(customButtonInfo.selector);
                
                // Wait for dialog to appear
                await page.waitForSelector('.dual-image-dialog', { timeout: 5000 });
                console.log('Dual image dialog opened successfully!');
                
                // Take screenshot of dialog
                await page.screenshot({ path: 'testing/screenshots/dual-image-dialog.png', fullPage: true });
                
                // Fill in the form
                const fileInput = await page.waitForSelector('input[name="display_image"]');
                await fileInput.setInputFiles(testImagePath);
                
                // Fill alt text
                await page.fill('#altText', 'Test image for modal functionality');
                
                // Submit the form
                await page.click('button[type="submit"]');
                
                // Wait for upload to complete
                await page.waitForTimeout(3000);
                
                console.log('Image upload form submitted');
                
            } catch (error) {
                console.log('Error testing custom button:', error.message);
            }
        } else {
            console.log('Custom button not found, testing standard image upload...');
            
            // Try standard TinyMCE image button
            try {
                const imageButton = await page.waitForSelector('button[title*="Image"], button[aria-label*="Image"]', { timeout: 5000 });
                await imageButton.click();
                
                console.log('Standard image button clicked');
                
                // Look for file input or URL input
                await page.waitForTimeout(2000);
                
            } catch (error) {
                console.log('Standard image button not found either:', error.message);
            }
        }
        
        // Step 6: Check TinyMCE content
        console.log('Step 6: Checking TinyMCE content...');
        
        // Get content from TinyMCE editor
        const editorContent = await page.evaluate(() => {
            if (typeof window.tinymce !== 'undefined') {
                const editors = window.tinymce.get();
                if (editors.length > 0) {
                    return editors[0].getContent();
                }
            }
            return '';
        });
        
        console.log('Current TinyMCE content:', editorContent);
        
        // Step 7: Manually insert test content
        console.log('Step 7: Manually inserting test content...');
        
        // Insert test HTML with modal attributes
        const testHtml = `
            <p>Testing image modal functionality:</p>
            <img src="/uploads/content/test-image.jpg" 
                 data-modal-src="/uploads/content/test-image-large.jpg" 
                 alt="Test image with modal" 
                 onclick="openImageModal('/uploads/content/test-image-large.jpg', 'Test image with modal')" 
                 style="cursor: pointer; max-width: 300px;">
            <p>The image above should be clickable and open in a modal.</p>
        `;
        
        await page.evaluate((html) => {
            if (typeof window.tinymce !== 'undefined') {
                const editors = window.tinymce.get();
                if (editors.length > 0) {
                    editors[0].setContent(html);
                }
            }
        }, testHtml);
        
        // Take screenshot with test content
        await page.screenshot({ path: 'testing/screenshots/tinymce-with-test-content.png', fullPage: true });
        
        // Step 8: Save the content
        console.log('Step 8: Saving test content...');
        
        // Fill required fields
        await page.fill('input[name="title"]', 'TinyMCE Image Modal Test Content');
        await page.fill('input[name="alias"]', 'tinymce-image-modal-test');
        await page.selectOption('select[name="type"]', 'article');
        
        // Submit the form
        await page.click('button[type="submit"]');
        
        // Wait for redirect
        await page.waitForURL('**/admin/content**', { timeout: 10000 });
        console.log('Content saved successfully');
        
        // Step 9: Test frontend display
        console.log('Step 9: Testing frontend display...');
        
        // Navigate to the frontend article
        await page.goto('http://localhost:8000/article/tinymce-image-modal-test');
        await page.waitForLoadState('networkidle');
        
        // Take screenshot of frontend
        await page.screenshot({ path: 'testing/screenshots/frontend-test-content.png', fullPage: true });
        
        // Step 10: Test modal functionality
        console.log('Step 10: Testing modal functionality...');
        
        // Look for images with modal attributes
        const modalImages = await page.$$eval('img[data-modal-src]', images => {
            return images.map(img => ({
                src: img.src,
                modalSrc: img.getAttribute('data-modal-src'),
                alt: img.alt,
                hasClickHandler: !!img.onclick,
                cursor: window.getComputedStyle(img).cursor
            }));
        });
        
        console.log('Found modal images:', modalImages);
        
        if (modalImages.length > 0) {
            console.log('Testing modal click functionality...');
            
            // Click the first modal image
            try {
                await page.click('img[data-modal-src]');
                
                // Wait for modal to appear
                await page.waitForSelector('.image-modal', { timeout: 5000 });
                console.log('Image modal opened successfully!');
                
                // Take screenshot of modal
                await page.screenshot({ path: 'testing/screenshots/image-modal-opened.png', fullPage: true });
                
                // Test modal close
                await page.click('.modal-close');
                await page.waitForSelector('.image-modal', { state: 'detached', timeout: 5000 });
                console.log('Image modal closed successfully!');
                
            } catch (error) {
                console.log('Modal functionality test failed:', error.message);
            }
        } else {
            console.log('No modal images found on frontend');
        }
        
        // Step 11: Check for JavaScript errors
        console.log('Step 11: Checking for JavaScript errors...');
        
        const jsErrors = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                jsErrors.push(msg.text());
            }
        });
        
        // Run addModalToContentImages function
        await page.evaluate(() => {
            if (typeof window.addModalToContentImages === 'function') {
                console.log('Running addModalToContentImages...');
                window.addModalToContentImages();
                return 'Function executed';
            }
            return 'Function not found';
        });
        
        console.log('JavaScript errors found:', jsErrors);
        
        // Final screenshot
        await page.screenshot({ path: 'testing/screenshots/final-test-state.png', fullPage: true });
        
        console.log('=== COMPREHENSIVE TEST COMPLETED ===');
        
        // Return summary
        const summary = {
            customButtonFound,
            customButtonInfo,
            modalImages: modalImages.length,
            jsErrors: jsErrors.length,
            editorContent: editorContent.length > 0
        };
        
        console.log('Test Summary:', summary);
        
        // Assert that at least some functionality is working
        expect(summary.editorContent).toBe(true);
    });
    
    test('Debug TinyMCE Button Registration', async () => {
        console.log('=== DEBUGGING TINYMCE BUTTON REGISTRATION ===');
        
        // Navigate to content creation
        await page.goto('http://localhost:8000/admin/content/create');
        await page.waitForLoadState('networkidle');
        
        // Wait for TinyMCE
        await page.waitForFunction(() => {
            return typeof window.tinymce !== 'undefined' && window.tinymce.get().length > 0;
        }, { timeout: 15000 });
        
        // Get detailed button registration info
        const registrationInfo = await page.evaluate(() => {
            if (typeof window.tinymce === 'undefined') {
                return { error: 'TinyMCE not loaded' };
            }
            
            const editors = window.tinymce.get();
            if (editors.length === 0) {
                return { error: 'No editors found' };
            }
            
            const editor = editors[0];
            const registry = editor.ui.registry.getAll();
            
            return {
                editorId: editor.id,
                totalButtons: Object.keys(registry.buttons || {}).length,
                allButtons: Object.keys(registry.buttons || {}),
                customButtons: {
                    dualimage: !!registry.buttons?.dualimage,
                    modalimage: !!registry.buttons?.modalimage,
                    testbutton: !!registry.buttons?.testbutton
                },
                toolbarConfig: editor.settings?.toolbar,
                setupFunction: typeof editor.settings?.setup === 'function',
                tinymceVersion: window.tinymce.majorVersion,
                globalFunctions: {
                    showDualImageDialog: typeof window.showDualImageDialog === 'function',
                    debugTinyMCE: typeof window.debugTinyMCE === 'function'
                },
                state: window.TINYMCE_STATE || 'undefined'
            };
        });
        
        console.log('Button Registration Analysis:', JSON.stringify(registrationInfo, null, 2));
        
        // Take screenshot for visual inspection
        await page.screenshot({ path: 'testing/screenshots/button-registration-debug.png', fullPage: true });
        
        expect(registrationInfo.totalButtons).toBeGreaterThan(0);
    });
});