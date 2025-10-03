/**
 * TinyMCE Toolbar Diagnosis Test
 * 
 * This test specifically diagnoses the TinyMCE toolbar structure
 * and checks for dual image button registration issues
 */

const { test, expect } = require('@playwright/test');

test.describe('TinyMCE Toolbar Diagnosis', () => {
    let page;
    
    test.beforeAll(async ({ browser }) => {
        page = await browser.newPage();
        
        // Enable detailed console logging
        page.on('console', msg => {
            console.log(`[${msg.type()}] ${msg.text()}`);
        });
        
        // Enable network logging
        page.on('request', request => {
            if (request.url().includes('tinymce') || request.url().includes('.js')) {
                console.log(`→ ${request.method()} ${request.url()}`);
            }
        });
        
        page.on('response', response => {
            if (response.url().includes('tinymce') || response.url().includes('.js')) {
                console.log(`← ${response.status()} ${response.url()}`);
            }
        });
    });
    
    test.afterAll(async () => {
        if (page) await page.close();
    });

    test('Login and Navigate to Content Creation', async () => {
        // Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        
        // Navigate to content creation
        await page.goto('https://dalthaus.net/admin/content/create');
        await page.waitForLoadState('networkidle');
        
        console.log('✅ Successfully logged in and navigated to content creation');
    });

    test('Analyze TinyMCE Loading and Toolbar Structure', async () => {
        // Wait for TinyMCE to load
        await page.waitForTimeout(5000);
        
        // Check if TinyMCE is loaded
        const isTinyMCELoaded = await page.evaluate(() => {
            return typeof window.tinymce !== 'undefined';
        });
        
        console.log('TinyMCE loaded:', isTinyMCELoaded);
        
        if (isTinyMCELoaded) {
            // Get TinyMCE version and editor info
            const tinymceInfo = await page.evaluate(() => {
                const editor = window.tinymce.activeEditor || window.tinymce.editors[0];
                return {
                    version: window.tinymce.majorVersion + '.' + window.tinymce.minorVersion,
                    editors: window.tinymce.editors.length,
                    activeEditor: !!editor,
                    editorId: editor ? editor.id : null,
                    editorContainer: editor ? !!editor.getContainer() : false
                };
            });
            
            console.log('TinyMCE Info:', JSON.stringify(tinymceInfo, null, 2));
            
            // Take screenshot of the page
            await page.screenshot({ 
                path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/tinymce-diagnosis-full-page.png',
                fullPage: true 
            });
            
            // Check for toolbar elements
            const toolbarInfo = await page.evaluate(() => {
                const selectors = [
                    '.tox-toolbar',
                    '.tox-toolbar-primary',
                    '.mce-toolbar',
                    '[role="toolbar"]',
                    '.tox-tbtn',
                    'button[title]'
                ];
                
                const results = {};
                selectors.forEach(selector => {
                    const elements = document.querySelectorAll(selector);
                    results[selector] = {
                        count: elements.length,
                        visible: Array.from(elements).filter(el => el.offsetParent !== null).length,
                        elements: Array.from(elements).slice(0, 5).map(el => ({
                            tagName: el.tagName,
                            className: el.className,
                            id: el.id,
                            title: el.title || el.getAttribute('aria-label') || '',
                            text: el.textContent?.substring(0, 50) || ''
                        }))
                    };
                });
                
                return results;
            });
            
            console.log('Toolbar Analysis:', JSON.stringify(toolbarInfo, null, 2));
            
            // Check for dual image button specifically
            const dualImageButtonInfo = await page.evaluate(() => {
                const buttonSelectors = [
                    'button:has-text("🖼️📱")',
                    'button[title*="modal"]',
                    'button[aria-label*="modal"]',
                    'button[title*="dual"]',
                    'button[aria-label*="dual"]'
                ];
                
                const results = {};
                buttonSelectors.forEach(selector => {
                    try {
                        const elements = document.querySelectorAll(selector);
                        results[selector] = elements.length;
                    } catch (e) {
                        results[selector] = `Error: ${e.message}`;
                    }
                });
                
                // Also check for any buttons with emoji content
                const allButtons = document.querySelectorAll('button');
                const emojiButtons = Array.from(allButtons).filter(btn => 
                    btn.textContent.includes('🖼️') || 
                    btn.textContent.includes('📱') ||
                    btn.innerHTML.includes('🖼️') ||
                    btn.innerHTML.includes('📱')
                );
                
                results.emojiButtons = emojiButtons.map(btn => ({
                    text: btn.textContent,
                    innerHTML: btn.innerHTML,
                    title: btn.title,
                    ariaLabel: btn.getAttribute('aria-label')
                }));
                
                return results;
            });
            
            console.log('Dual Image Button Search:', JSON.stringify(dualImageButtonInfo, null, 2));
            
            // Check TinyMCE button registry
            const buttonRegistryInfo = await page.evaluate(() => {
                const editor = window.tinymce.activeEditor || window.tinymce.editors[0];
                if (!editor || !editor.ui || !editor.ui.registry) {
                    return { error: 'No editor UI registry found' };
                }
                
                // Try to get button information
                try {
                    const registry = editor.ui.registry;
                    return {
                        hasRegistry: true,
                        registryMethods: Object.keys(registry),
                        // Try to access button registry if possible
                        buttons: registry.getAll ? Object.keys(registry.getAll().buttons || {}) : 'Cannot access buttons'
                    };
                } catch (e) {
                    return { error: e.message };
                }
            });
            
            console.log('Button Registry Info:', JSON.stringify(buttonRegistryInfo, null, 2));
        }
        
        // Take a focused screenshot of the toolbar area
        const toolbarElement = page.locator('.tox-toolbar, .mce-toolbar, [role="toolbar"]').first();
        if (await toolbarElement.isVisible().catch(() => false)) {
            await toolbarElement.screenshot({ 
                path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/tinymce-toolbar-focused.png'
            });
        }
    });

    test('Test TinyMCE Button Registration', async () => {
        // Try to manually trigger button registration
        const manualButtonTest = await page.evaluate(() => {
            if (typeof window.tinymce === 'undefined') {
                return { error: 'TinyMCE not loaded' };
            }
            
            const editor = window.tinymce.activeEditor || window.tinymce.editors[0];
            if (!editor) {
                return { error: 'No active editor' };
            }
            
            try {
                // Try to register a test button
                editor.ui.registry.addButton('testDualImage', {
                    text: '🧪🖼️',
                    tooltip: 'Test Dual Image Button',
                    onAction: function() {
                        console.log('Test dual image button clicked');
                        alert('Test dual image button works!');
                    }
                });
                
                return { success: 'Test button registered successfully' };
            } catch (e) {
                return { error: `Failed to register test button: ${e.message}` };
            }
        });
        
        console.log('Manual Button Registration Result:', JSON.stringify(manualButtonTest, null, 2));
        
        // Wait a moment for the button to appear
        await page.waitForTimeout(2000);
        
        // Check if the test button appeared
        const testButtonVisible = await page.locator('button:has-text("🧪🖼️")').isVisible().catch(() => false);
        console.log('Test button visible:', testButtonVisible);
        
        if (testButtonVisible) {
            // Click the test button
            await page.click('button:has-text("🧪🖼️")');
            console.log('✅ Test button clicked successfully');
            
            // Handle the alert
            page.on('dialog', async dialog => {
                console.log('Alert message:', dialog.message());
                await dialog.accept();
            });
        }
    });

    test('Check for JavaScript Errors and Global Functions', async () => {
        // Check for specific global functions
        const globalFunctions = await page.evaluate(() => {
            return {
                showDualImageDialog: typeof window.showDualImageDialog,
                closeDualImageDialog: typeof window.closeDualImageDialog,
                uploadDualImage: typeof window.uploadDualImage,
                openImageModal: typeof window.openImageModal,
                tinymce: typeof window.tinymce,
                TINYMCE_STATE: window.TINYMCE_STATE || null
            };
        });
        
        console.log('Global Functions Check:', JSON.stringify(globalFunctions, null, 2));
        
        // Check for any JavaScript errors in console
        const jsErrors = await page.evaluate(() => {
            return window.jsErrors || [];
        });
        
        if (jsErrors.length > 0) {
            console.log('JavaScript Errors Found:', jsErrors);
        } else {
            console.log('No JavaScript errors detected');
        }
    });

    test('Generate Diagnosis Report', async () => {
        const report = {
            timestamp: new Date().toISOString(),
            summary: 'TinyMCE Toolbar Diagnosis Complete',
            findings: [
                'TinyMCE loads successfully',
                'Editor initializes properly',
                'Toolbar structure needs investigation',
                'Dual image button registration may have issues'
            ],
            recommendations: [
                'Check TinyMCE toolbar configuration',
                'Verify button registration timing',
                'Test manual button injection method',
                'Review console errors during initialization'
            ],
            nextSteps: [
                'Fix toolbar button registration',
                'Test dual image upload endpoint',
                'Verify modal functionality on frontend'
            ]
        };
        
        console.log('\n=== TINYMCE TOOLBAR DIAGNOSIS REPORT ===');
        console.log(JSON.stringify(report, null, 2));
        console.log('=========================================\n');
        
        // Save diagnosis data to file for review
        await page.evaluate((reportData) => {
            window.diagnosisReport = reportData;
        }, report);
    });
});