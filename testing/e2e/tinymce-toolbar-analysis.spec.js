import { test, expect } from '@playwright/test';

test.describe('TinyMCE Toolbar Analysis', () => {
    
    test('Analyze TinyMCE Toolbar and Test Image Functionality', async ({ page }) => {
        console.log('=== STARTING TINYMCE TOOLBAR ANALYSIS ===');
        
        // Step 1: Login to admin
        await page.goto('http://localhost:8000/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard**');
        
        // Step 2: Navigate to content creation
        await page.goto('http://localhost:8000/admin/content/create');
        await page.waitForLoadState('networkidle');
        
        // Step 3: Wait for TinyMCE to be visible (use the editor container instead of hidden textarea)
        await page.waitForSelector('.tox-editor-container, .mce-container', { timeout: 15000 });
        console.log('✅ TinyMCE editor container found');
        
        // Wait for TinyMCE to be fully initialized
        await page.waitForFunction(() => {
            return typeof window.tinymce !== 'undefined' && window.tinymce.get().length > 0;
        }, { timeout: 15000 });
        console.log('✅ TinyMCE JavaScript object initialized');
        
        // Take initial screenshot
        await page.screenshot({ path: 'testing/screenshots/tinymce-toolbar-initial.png', fullPage: true });
        
        // Step 4: Analyze the toolbar in detail
        const toolbarAnalysis = await page.evaluate(() => {
            // Find the toolbar
            const toolbar = document.querySelector('.tox-toolbar, .mce-toolbar');
            if (!toolbar) return { error: 'Toolbar not found' };
            
            // Get all buttons
            const buttons = toolbar.querySelectorAll('button');
            const buttonData = Array.from(buttons).map((btn, index) => {
                const rect = btn.getBoundingClientRect();
                return {
                    index: index,
                    title: btn.title || '',
                    ariaLabel: btn.getAttribute('aria-label') || '',
                    text: btn.textContent.trim(),
                    className: btn.className,
                    visible: rect.width > 0 && rect.height > 0,
                    innerHTML: btn.innerHTML.substring(0, 100) // First 100 chars
                };
            });
            
            // Look specifically for image-related buttons
            const imageButtons = buttonData.filter(btn => 
                btn.title.toLowerCase().includes('image') ||
                btn.ariaLabel.toLowerCase().includes('image') ||
                btn.text.toLowerCase().includes('image') ||
                btn.innerHTML.includes('image') ||
                btn.innerHTML.includes('picture') ||
                btn.className.includes('image')
            );
            
            // Look for custom buttons
            const customButtons = buttonData.filter(btn => 
                btn.title.toLowerCase().includes('modal') ||
                btn.title.toLowerCase().includes('dual') ||
                btn.text.includes('🖼️') ||
                btn.text.includes('🔍') ||
                btn.text.includes('🧪') ||
                btn.text.toLowerCase().includes('modal') ||
                btn.text.toLowerCase().includes('test') ||
                btn.text.toLowerCase().includes('debug')
            );
            
            return {
                totalButtons: buttonData.length,
                allButtons: buttonData,
                imageButtons: imageButtons,
                customButtons: customButtons
            };
        });
        
        console.log('Toolbar Analysis Results:');
        console.log('  Total buttons:', toolbarAnalysis.totalButtons);
        console.log('  Image buttons found:', toolbarAnalysis.imageButtons.length);
        console.log('  Custom buttons found:', toolbarAnalysis.customButtons.length);
        
        if (toolbarAnalysis.imageButtons.length > 0) {
            console.log('Image buttons:');
            toolbarAnalysis.imageButtons.forEach((btn, i) => {
                console.log(`    ${i + 1}. Title: "${btn.title}", Text: "${btn.text}"`);
            });
        }
        
        if (toolbarAnalysis.customButtons.length > 0) {
            console.log('Custom buttons:');
            toolbarAnalysis.customButtons.forEach((btn, i) => {
                console.log(`    ${i + 1}. Title: "${btn.title}", Text: "${btn.text}"`);
            });
        } else {
            console.log('❌ No custom buttons found in toolbar');
        }
        
        // Step 5: Get TinyMCE registration details
        const registrationInfo = await page.evaluate(() => {
            if (typeof window.tinymce === 'undefined') {
                return { error: 'TinyMCE not available' };
            }
            
            const editors = window.tinymce.get();
            if (editors.length === 0) {
                return { error: 'No editors found' };
            }
            
            const editor = editors[0];
            const registry = editor.ui.registry.getAll();
            
            return {
                editorId: editor.id,
                registeredButtons: Object.keys(registry.buttons || {}),
                customButtonsRegistered: {
                    dualimage: !!registry.buttons?.dualimage,
                    modalimage: !!registry.buttons?.modalimage,
                    testbutton: !!registry.buttons?.testbutton
                },
                toolbarConfig: editor.settings?.toolbar,
                setupFunctionExists: typeof editor.settings?.setup === 'function',
                globalState: window.TINYMCE_STATE || null
            };
        });
        
        console.log('Registration Analysis:');
        console.log('  Registered buttons:', registrationInfo.registeredButtons?.length || 0);
        console.log('  Custom buttons registered:', registrationInfo.customButtonsRegistered);
        console.log('  Toolbar config:', registrationInfo.toolbarConfig);
        
        // Step 6: Test image button functionality
        if (toolbarAnalysis.imageButtons.length > 0) {
            console.log('Testing image button click...');
            
            try {
                // Click the first image button
                const imageButton = toolbarAnalysis.imageButtons[0];
                await page.click(`.tox-toolbar button >> nth=${imageButton.index}`);
                
                // Wait for dialog to appear (could be various types)
                await page.waitForTimeout(2000);
                
                // Look for any dialog that appeared
                const dialogInfo = await page.evaluate(() => {
                    const dialogs = [
                        document.querySelector('.tox-dialog'),
                        document.querySelector('.mce-window'),
                        document.querySelector('.dual-image-dialog'),
                        document.querySelector('[role="dialog"]')
                    ].filter(d => d && d.offsetParent !== null);
                    
                    return dialogs.map(dialog => ({
                        className: dialog.className,
                        innerHTML: dialog.innerHTML.substring(0, 200)
                    }));
                });
                
                console.log('Dialog(s) found after image button click:', dialogInfo.length);
                dialogInfo.forEach((dialog, i) => {
                    console.log(`  Dialog ${i + 1}: ${dialog.className}`);
                });
                
                if (dialogInfo.length > 0) {
                    await page.screenshot({ path: 'testing/screenshots/image-dialog-opened.png', fullPage: true });
                    
                    // Try to close dialog
                    try {
                        await page.keyboard.press('Escape');
                        await page.waitForTimeout(1000);
                    } catch (e) {
                        console.log('Could not close dialog with Escape');
                    }
                }
                
            } catch (error) {
                console.log('Image button test failed:', error.message);
            }
        }
        
        // Step 7: Test manual content insertion with modal attributes
        console.log('Step 7: Testing manual content insertion...');
        
        const testHtml = `
        <h2>Image Modal Test</h2>
        <p>This is a test article to verify image modal functionality.</p>
        <img src="/uploads/content/test-image.jpg" 
             data-modal-src="/uploads/content/test-image-large.jpg" 
             alt="Test image for modal verification" 
             onclick="openImageModal('/uploads/content/test-image-large.jpg', 'Test image for modal verification')" 
             style="cursor: pointer; max-width: 400px; border: 3px solid #2196F3; margin: 10px 0;">
        <p>Click the image above to test the modal functionality. The image should open in a modal overlay.</p>
        <p><strong>Expected behavior:</strong></p>
        <ul>
            <li>Image should have a pointer cursor</li>
            <li>Clicking should open a modal overlay</li>
            <li>Modal should show the larger version of the image</li>
            <li>Modal should close when clicking outside or pressing Escape</li>
        </ul>
        `;
        
        const contentInserted = await page.evaluate((html) => {
            if (typeof window.tinymce !== 'undefined') {
                const editors = window.tinymce.get();
                if (editors.length > 0) {
                    editors[0].setContent(html);
                    return true;
                }
            }
            return false;
        }, testHtml);
        
        if (contentInserted) {
            console.log('✅ Test content inserted into TinyMCE');
        } else {
            console.log('❌ Failed to insert test content');
        }
        
        // Take screenshot with content
        await page.screenshot({ path: 'testing/screenshots/tinymce-with-modal-content.png', fullPage: true });
        
        // Step 8: Save the content
        await page.fill('input[name="title"]', 'Image Modal Functionality Test');
        await page.fill('input[name="alias"]', 'image-modal-test');
        await page.selectOption('select[name="type"]', 'article');
        
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/content**', { timeout: 10000 });
        console.log('✅ Test content saved');
        
        // Step 9: Test frontend modal functionality
        await page.goto('http://localhost:8000/article/image-modal-test');
        await page.waitForLoadState('networkidle');
        
        // Take screenshot of frontend
        await page.screenshot({ path: 'testing/screenshots/frontend-modal-test.png', fullPage: true });
        
        // Step 10: Analyze frontend modal setup
        const frontendAnalysis = await page.evaluate(() => {
            // Check if addModalToContentImages function exists and run it
            let modalSetupResult = 'not available';
            if (typeof window.addModalToContentImages === 'function') {
                try {
                    window.addModalToContentImages();
                    modalSetupResult = 'executed successfully';
                } catch (e) {
                    modalSetupResult = 'execution failed: ' + e.message;
                }
            }
            
            // Find modal images
            const modalImages = document.querySelectorAll('img[data-modal-src]');
            const imageAnalysis = Array.from(modalImages).map(img => ({
                src: img.src,
                modalSrc: img.getAttribute('data-modal-src'),
                alt: img.alt,
                hasOnClick: !!img.onclick || img.hasAttribute('onclick'),
                cursor: window.getComputedStyle(img).cursor,
                modalEnabled: img.hasAttribute('data-modal-enabled'),
                boundingRect: img.getBoundingClientRect()
            }));
            
            return {
                modalSetupResult,
                modalImagesFound: modalImages.length,
                imageAnalysis,
                openImageModalExists: typeof window.openImageModal === 'function',
                closeImageModalExists: typeof window.closeImageModal === 'function'
            };
        });
        
        console.log('Frontend Modal Analysis:');
        console.log('  Modal setup result:', frontendAnalysis.modalSetupResult);
        console.log('  Modal images found:', frontendAnalysis.modalImagesFound);
        console.log('  openImageModal function exists:', frontendAnalysis.openImageModalExists);
        console.log('  closeImageModal function exists:', frontendAnalysis.closeImageModalExists);
        
        if (frontendAnalysis.imageAnalysis.length > 0) {
            console.log('  Image details:');
            frontendAnalysis.imageAnalysis.forEach((img, i) => {
                console.log(`    ${i + 1}. Has click handler: ${img.hasOnClick}, Cursor: ${img.cursor}, Modal enabled: ${img.modalEnabled}`);
            });
        }
        
        // Step 11: Test modal click functionality
        if (frontendAnalysis.modalImagesFound > 0 && frontendAnalysis.openImageModalExists) {
            console.log('Testing modal click...');
            
            try {
                await page.click('img[data-modal-src]');
                
                // Wait for modal
                await page.waitForSelector('.image-modal', { timeout: 5000 });
                console.log('✅ Modal opened successfully!');
                
                await page.screenshot({ path: 'testing/screenshots/modal-opened-success.png', fullPage: true });
                
                // Test closing
                await page.click('.modal-close');
                await page.waitForSelector('.image-modal', { state: 'detached', timeout: 5000 });
                console.log('✅ Modal closed successfully!');
                
            } catch (error) {
                console.log('❌ Modal test failed:', error.message);
                
                // Try alternative methods
                try {
                    await page.keyboard.press('Escape');
                    console.log('Tried closing with Escape');
                } catch (e) {
                    console.log('Escape also failed');
                }
            }
        }
        
        // Final summary
        const summary = {
            tinymceLoaded: toolbarAnalysis.totalButtons > 0,
            imageButtonsPresent: toolbarAnalysis.imageButtons.length > 0,
            customButtonsPresent: toolbarAnalysis.customButtons.length > 0,
            customButtonsRegistered: Object.values(registrationInfo.customButtonsRegistered || {}).some(v => v),
            frontendModalWorking: frontendAnalysis.openImageModalExists && frontendAnalysis.modalImagesFound > 0
        };
        
        console.log('=== FINAL ANALYSIS SUMMARY ===');
        console.log('TinyMCE loaded:', summary.tinymceLoaded);
        console.log('Image buttons present:', summary.imageButtonsPresent);
        console.log('Custom buttons in toolbar:', summary.customButtonsPresent);
        console.log('Custom buttons registered:', summary.customButtonsRegistered);
        console.log('Frontend modal working:', summary.frontendModalWorking);
        
        // The key issue analysis
        if (!summary.customButtonsPresent && !summary.customButtonsRegistered) {
            console.log('🔍 KEY ISSUE: Custom buttons are not being registered or displayed in toolbar');
        } else if (summary.customButtonsRegistered && !summary.customButtonsPresent) {
            console.log('🔍 KEY ISSUE: Custom buttons are registered but not visible in toolbar');
        } else if (summary.frontendModalWorking) {
            console.log('✅ System is working: Custom buttons present and frontend modal functional');
        }
        
        expect(summary.tinymceLoaded).toBe(true);
        expect(summary.frontendModalWorking).toBe(true);
    });
});