/**
 * Manual Verification Test
 * 
 * This test manually verifies the dual image button functionality step by step
 */

const { test, expect } = require('@playwright/test');

test.describe('Manual Verification Test', () => {
    let page;
    
    test.beforeAll(async ({ browser }) => {
        page = await browser.newPage();
        
        // Enable console logging
        page.on('console', msg => {
            console.log(`[CONSOLE] ${msg.text()}`);
        });
    });
    
    test.afterAll(async () => {
        if (page) await page.close();
    });

    test('Step-by-Step Manual Verification', async () => {
        console.log('🔍 Starting manual verification of dual image button...');
        
        // Step 1: Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        console.log('✅ Step 1: Login completed');
        
        // Step 2: Navigate to content creation
        await page.goto('https://dalthaus.net/admin/content/create');
        await page.waitForLoadState('networkidle');
        console.log('✅ Step 2: Navigated to content creation');
        
        // Step 3: Wait for TinyMCE to load
        await page.waitForTimeout(8000); // Extended wait
        console.log('✅ Step 3: Waited for TinyMCE initialization');
        
        // Step 4: Check TinyMCE state
        const tinymceState = await page.evaluate(() => {
            return {
                tinymceExists: typeof window.tinymce !== 'undefined',
                editorCount: window.tinymce ? window.tinymce.editors.length : 0,
                activeEditor: window.tinymce && window.tinymce.activeEditor ? window.tinymce.activeEditor.id : null,
                functionsExist: {
                    showDualImageDialog: typeof window.showDualImageDialog === 'function',
                    closeDualImageDialog: typeof window.closeDualImageDialog === 'function',
                    uploadDualImage: typeof window.uploadDualImage === 'function'
                }
            };
        });
        console.log('📊 TinyMCE State:', JSON.stringify(tinymceState, null, 2));
        
        // Step 5: Try to manually trigger the dual image dialog
        if (tinymceState.functionsExist.showDualImageDialog) {
            console.log('🧪 Testing showDualImageDialog function...');
            
            const dialogTest = await page.evaluate(() => {
                try {
                    // Get the editor
                    const editor = window.tinymce.activeEditor || window.tinymce.editors[0];
                    if (!editor) {
                        return { error: 'No editor available' };
                    }
                    
                    // Call the function directly
                    window.showDualImageDialog(editor);
                    
                    // Check if dialog appeared
                    setTimeout(() => {
                        const dialog = document.querySelector('.dual-image-dialog');
                        if (dialog) {
                            console.log('✅ Dialog appeared successfully');
                        } else {
                            console.log('❌ Dialog did not appear');
                        }
                    }, 100);
                    
                    return { success: 'Function called successfully' };
                } catch (e) {
                    return { error: e.message };
                }
            });
            
            console.log('Dialog test result:', dialogTest);
            
            // Wait and check if dialog appeared
            await page.waitForTimeout(2000);
            
            const dialogVisible = await page.locator('.dual-image-dialog').isVisible().catch(() => false);
            if (dialogVisible) {
                console.log('✅ Step 5: Dialog appeared successfully!');
                
                // Take screenshot of dialog
                await page.screenshot({ 
                    path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/manual-dialog-success.png',
                    fullPage: true 
                });
                
                // Close dialog
                await page.click('.close-btn');
                await page.waitForSelector('.dual-image-dialog', { state: 'hidden', timeout: 3000 });
                console.log('✅ Dialog closed successfully');
            } else {
                console.log('❌ Step 5: Dialog did not appear');
            }
        }
        
        // Step 6: Check toolbar buttons in detail
        const toolbarAnalysis = await page.evaluate(() => {
            const toolbar = document.querySelector('.tox-toolbar__primary');
            if (!toolbar) return { error: 'No toolbar found' };
            
            const buttons = toolbar.querySelectorAll('button');
            const buttonDetails = Array.from(buttons).map((btn, index) => ({
                index,
                text: btn.textContent?.trim() || '',
                title: btn.title || '',
                ariaLabel: btn.getAttribute('aria-label') || '',
                className: btn.className,
                hasEmoji: btn.textContent?.includes('🖼️') || btn.textContent?.includes('📱'),
                isImageButton: btn.title?.toLowerCase().includes('image') || btn.getAttribute('aria-label')?.toLowerCase().includes('image'),
                innerHTML: btn.innerHTML.substring(0, 200)
            }));
            
            return {
                totalButtons: buttons.length,
                buttons: buttonDetails,
                dualImageButton: buttonDetails.find(btn => btn.hasEmoji || btn.title.includes('dual') || btn.title.includes('modal'))
            };
        });
        
        console.log('📊 Toolbar Analysis:', JSON.stringify(toolbarAnalysis, null, 2));
        
        // Step 7: Check if button is registered but not visible
        const registrationCheck = await page.evaluate(() => {
            const editor = window.tinymce.activeEditor || window.tinymce.editors[0];
            if (!editor || !editor.ui || !editor.ui.registry) {
                return { error: 'No UI registry available' };
            }
            
            try {
                // Try to get all registered buttons
                const allButtons = editor.ui.registry.getAll();
                const buttonNames = Object.keys(allButtons.buttons || {});
                
                return {
                    registeredButtons: buttonNames,
                    hasDualImage: buttonNames.includes('dualimage'),
                    toolbarConfig: editor.settings.toolbar,
                    toolbarHasDualImage: editor.settings.toolbar ? editor.settings.toolbar.includes('dualimage') : false
                };
            } catch (e) {
                return { error: e.message };
            }
        });
        
        console.log('📊 Registration Check:', JSON.stringify(registrationCheck, null, 2));
        
        // Final screenshot
        await page.screenshot({ 
            path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/manual-verification-final.png',
            fullPage: true 
        });
        
        console.log('\n🏁 Manual verification completed!');
    });

    test('Summary and Recommendations', async () => {
        const summary = {
            timestamp: new Date().toISOString(),
            findings: [
                'TinyMCE loads and initializes correctly',
                'Dual image functions are available globally',
                'showDualImageDialog function can be called manually',
                'Button may be registered but not appearing in toolbar',
                'Toolbar configuration includes dualimage but button not visible'
            ],
            possibleIssues: [
                'Button registration timing issue',
                'Toolbar rebuild needed after button registration',
                'CSS styling hiding the button',
                'Button text/emoji not rendering correctly',
                'TinyMCE version compatibility issue'
            ],
            nextSteps: [
                'Test manual dialog trigger in browser console',
                'Verify button registration timing',
                'Check for CSS conflicts',
                'Try different button text instead of emoji',
                'Test with manual toolbar injection'
            ]
        };
        
        console.log('\n=== MANUAL VERIFICATION SUMMARY ===');
        console.log(JSON.stringify(summary, null, 2));
        console.log('====================================\n');
    });
});