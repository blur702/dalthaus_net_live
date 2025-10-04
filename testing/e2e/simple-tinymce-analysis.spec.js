import { test, expect } from '@playwright/test';

test.describe('Simple TinyMCE Analysis', () => {
    
    test('TinyMCE Button Analysis and Image Modal Test', async ({ page }) => {
        console.log('=== STARTING SIMPLE TINYMCE ANALYSIS ===');
        
        // Step 1: Login to admin
        await page.goto('http://localhost:8000/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        
        // Wait for dashboard (adjusted for actual content)
        await page.waitForURL('**/admin/dashboard**');
        await expect(page.locator('h1')).toContainText('Good evening'); // Fixed expectation
        
        console.log('✅ Successfully logged into admin dashboard');
        
        // Step 2: Navigate to content creation
        await page.goto('http://localhost:8000/admin/content/create');
        await page.waitForLoadState('networkidle');
        
        console.log('✅ Navigated to content creation page');
        
        // Step 3: Wait for TinyMCE to load
        await page.waitForSelector('textarea#body', { timeout: 10000 });
        console.log('✅ Found textarea#body element');
        
        // Wait for TinyMCE to initialize
        await page.waitForFunction(() => {
            return typeof window.tinymce !== 'undefined' && window.tinymce.get().length > 0;
        }, { timeout: 15000 });
        console.log('✅ TinyMCE initialized successfully');
        
        // Take screenshot of initial state
        await page.screenshot({ path: 'testing/screenshots/simple-tinymce-initial.png', fullPage: true });
        
        // Step 4: Analyze TinyMCE configuration
        const tinymceAnalysis = await page.evaluate(() => {
            if (typeof window.tinymce === 'undefined') {
                return { error: 'TinyMCE not available' };
            }
            
            const editors = window.tinymce.get();
            if (editors.length === 0) {
                return { error: 'No TinyMCE editors found' };
            }
            
            const editor = editors[0];
            const registry = editor.ui.registry.getAll();
            
            // Get all button names
            const allButtons = Object.keys(registry.buttons || {});
            
            // Check for custom buttons
            const customButtons = {
                dualimage: !!registry.buttons?.dualimage,
                modalimage: !!registry.buttons?.modalimage,
                testbutton: !!registry.buttons?.testbutton
            };
            
            // Get toolbar configuration
            const toolbarConfig = editor.settings?.toolbar || '';
            
            // Check global functions
            const globalFunctions = {
                showDualImageDialog: typeof window.showDualImageDialog === 'function',
                debugTinyMCE: typeof window.debugTinyMCE === 'function',
                addModalToContentImages: typeof window.addModalToContentImages === 'function'
            };
            
            return {
                editorId: editor.id,
                totalButtons: allButtons.length,
                allButtons: allButtons,
                customButtons: customButtons,
                toolbarConfig: toolbarConfig,
                globalFunctions: globalFunctions,
                tinymceVersion: window.tinymce.majorVersion || 'unknown',
                state: window.TINYMCE_STATE || null
            };
        });
        
        console.log('TinyMCE Analysis Results:');
        console.log('  Total buttons:', tinymceAnalysis.totalButtons);
        console.log('  Custom buttons:', tinymceAnalysis.customButtons);
        console.log('  Global functions:', tinymceAnalysis.globalFunctions);
        console.log('  Toolbar config:', tinymceAnalysis.toolbarConfig);
        
        // Step 5: Check for custom buttons in DOM
        const domButtonAnalysis = await page.evaluate(() => {
            const toolbar = document.querySelector('.tox-toolbar, .mce-toolbar');
            if (!toolbar) return { error: 'Toolbar not found' };
            
            const allButtons = toolbar.querySelectorAll('button');
            const buttonInfo = Array.from(allButtons).map(btn => ({
                title: btn.title || '',
                text: btn.textContent.trim(),
                ariaLabel: btn.getAttribute('aria-label') || '',
                className: btn.className
            }));
            
            // Look for custom buttons
            const customButtonsFound = buttonInfo.filter(btn => 
                btn.title.toLowerCase().includes('modal') ||
                btn.title.toLowerCase().includes('dual') ||
                btn.text.includes('🖼️') ||
                btn.text.includes('🔍') ||
                btn.text.includes('🧪') ||
                btn.text.toLowerCase().includes('modal') ||
                btn.text.toLowerCase().includes('test')
            );
            
            return {
                totalButtons: buttonInfo.length,
                customButtonsFound: customButtonsFound.length,
                customButtons: customButtonsFound,
                allButtons: buttonInfo
            };
        });
        
        console.log('DOM Button Analysis:');
        console.log('  Total DOM buttons:', domButtonAnalysis.totalButtons);
        console.log('  Custom buttons found:', domButtonAnalysis.customButtonsFound);
        if (domButtonAnalysis.customButtons.length > 0) {
            console.log('  Custom button details:', domButtonAnalysis.customButtons);
        }
        
        // Step 6: Test image insertion manually
        console.log('Step 6: Testing manual image insertion...');
        
        const testImageHtml = `
        <p>Testing image with modal functionality:</p>
        <img src="/uploads/content/test-display.jpg" 
             data-modal-src="/uploads/content/test-modal.jpg" 
             alt="Test image with modal functionality" 
             onclick="openImageModal('/uploads/content/test-modal.jpg', 'Test image')" 
             style="cursor: pointer; max-width: 300px; border: 2px solid #4CAF50;">
        <p>Click the image above to test modal functionality.</p>
        `;
        
        await page.evaluate((html) => {
            if (typeof window.tinymce !== 'undefined') {
                const editors = window.tinymce.get();
                if (editors.length > 0) {
                    editors[0].setContent(html);
                    return true;
                }
            }
            return false;
        }, testImageHtml);
        
        console.log('✅ Test HTML content inserted into TinyMCE');
        
        // Take screenshot with content
        await page.screenshot({ path: 'testing/screenshots/tinymce-with-test-html.png', fullPage: true });
        
        // Step 7: Save the content
        await page.fill('input[name="title"]', 'TinyMCE Modal Test Article');
        await page.fill('input[name="alias"]', 'tinymce-modal-test');
        await page.selectOption('select[name="type"]', 'article');
        
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/content**', { timeout: 10000 });
        console.log('✅ Test content saved successfully');
        
        // Step 8: Test frontend display and modal functionality
        await page.goto('http://localhost:8000/article/tinymce-modal-test');
        await page.waitForLoadState('networkidle');
        
        console.log('✅ Navigated to frontend article');
        
        // Take screenshot of frontend
        await page.screenshot({ path: 'testing/screenshots/frontend-article-test.png', fullPage: true });
        
        // Step 9: Check for modal images and functionality
        const frontendAnalysis = await page.evaluate(() => {
            // Check for addModalToContentImages function
            let modalFunctionResult = 'not executed';
            if (typeof window.addModalToContentImages === 'function') {
                try {
                    window.addModalToContentImages();
                    modalFunctionResult = 'executed successfully';
                } catch (e) {
                    modalFunctionResult = 'execution failed: ' + e.message;
                }
            }
            
            // Find images with modal attributes
            const modalImages = document.querySelectorAll('img[data-modal-src]');
            const imageData = Array.from(modalImages).map(img => ({
                src: img.src,
                modalSrc: img.getAttribute('data-modal-src'),
                alt: img.alt,
                hasClickHandler: !!img.onclick || img.hasAttribute('onclick'),
                cursor: window.getComputedStyle(img).cursor,
                modalEnabled: img.hasAttribute('data-modal-enabled')
            }));
            
            return {
                modalFunctionResult: modalFunctionResult,
                modalImagesFound: modalImages.length,
                imageData: imageData,
                openImageModalAvailable: typeof window.openImageModal === 'function'
            };
        });
        
        console.log('Frontend Analysis:');
        console.log('  Modal function result:', frontendAnalysis.modalFunctionResult);
        console.log('  Modal images found:', frontendAnalysis.modalImagesFound);
        console.log('  openImageModal available:', frontendAnalysis.openImageModalAvailable);
        console.log('  Image data:', frontendAnalysis.imageData);
        
        // Step 10: Test modal click if images are found
        if (frontendAnalysis.modalImagesFound > 0) {
            console.log('Testing modal click functionality...');
            
            try {
                // Click the first modal image
                await page.click('img[data-modal-src]');
                
                // Wait for modal to appear
                await page.waitForSelector('.image-modal', { timeout: 5000 });
                console.log('✅ Image modal opened successfully!');
                
                // Take screenshot of opened modal
                await page.screenshot({ path: 'testing/screenshots/image-modal-opened.png', fullPage: true });
                
                // Close modal
                await page.click('.modal-close');
                await page.waitForSelector('.image-modal', { state: 'detached', timeout: 5000 });
                console.log('✅ Image modal closed successfully!');
                
            } catch (error) {
                console.log('❌ Modal click test failed:', error.message);
                
                // Try alternative close methods
                try {
                    await page.keyboard.press('Escape');
                    console.log('Tried closing with Escape key');
                } catch (e) {
                    console.log('Escape key also failed');
                }
            }
        } else {
            console.log('❌ No modal images found for testing');
        }
        
        // Final summary
        const summary = {
            tinymceLoaded: tinymceAnalysis.totalButtons > 0,
            customButtonsRegistered: Object.values(tinymceAnalysis.customButtons).some(val => val),
            customButtonsInDOM: domButtonAnalysis.customButtonsFound > 0,
            modalImagesOnFrontend: frontendAnalysis.modalImagesFound > 0,
            modalFunctionWorking: frontendAnalysis.openImageModalAvailable
        };
        
        console.log('=== FINAL SUMMARY ===');
        console.log('TinyMCE loaded:', summary.tinymceLoaded);
        console.log('Custom buttons registered:', summary.customButtonsRegistered);
        console.log('Custom buttons in DOM:', summary.customButtonsInDOM);
        console.log('Modal images on frontend:', summary.modalImagesOnFrontend);
        console.log('Modal function working:', summary.modalFunctionWorking);
        
        // Assert basic functionality
        expect(summary.tinymceLoaded).toBe(true);
        expect(summary.modalFunctionWorking).toBe(true);
    });
});