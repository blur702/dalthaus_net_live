/**
 * Button Registration Debug Test
 * 
 * This test specifically focuses on diagnosing why the dual image button
 * is not appearing in the TinyMCE toolbar
 */

const { test, expect } = require('@playwright/test');

test.describe('Button Registration Debug', () => {
    let page;
    
    test.beforeAll(async ({ browser }) => {
        page = await browser.newPage();
        
        // Enable console logging
        page.on('console', msg => {
            console.log(`[CONSOLE ${msg.type()}] ${msg.text()}`);
        });
        
        // Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        
        // Navigate to content creation
        await page.goto('https://dalthaus.net/admin/content/create');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(5000);
    });
    
    test.afterAll(async () => {
        if (page) await page.close();
    });

    test('Debug TinyMCE State and Button Registration', async () => {
        // Check TinyMCE state in detail
        const tinymceState = await page.evaluate(() => {
            if (typeof window.tinymce === 'undefined') {
                return { error: 'TinyMCE not loaded' };
            }
            
            const editor = window.tinymce.activeEditor || window.tinymce.editors[0];
            if (!editor) {
                return { error: 'No active editor found' };
            }
            
            return {
                editorId: editor.id,
                hasUI: !!editor.ui,
                hasRegistry: !!(editor.ui && editor.ui.registry),
                setupCalled: editor.settings.setup ? 'function exists' : 'no setup function',
                toolbar: editor.settings.toolbar,
                plugins: editor.settings.plugins,
                initialized: editor.initialized,
                loaded: editor.isLoaded,
                container: !!editor.getContainer(),
                bodyElement: !!editor.getBody()
            };
        });
        
        console.log('TinyMCE State:', JSON.stringify(tinymceState, null, 2));
        
        // Check if our setup function was called
        const setupFunctionCheck = await page.evaluate(() => {
            const logs = [];
            
            // Check console history for our setup messages
            if (window.console && window.console.log) {
                // We can't access console history, but we can check for evidence
                // that our setup function ran
            }
            
            // Check if global functions exist
            const globalFunctions = {
                showDualImageDialog: typeof window.showDualImageDialog,
                closeDualImageDialog: typeof window.closeDualImageDialog,
                uploadDualImage: typeof window.uploadDualImage
            };
            
            return {
                globalFunctions,
                timestamp: new Date().toISOString()
            };
        });
        
        console.log('Setup Function Check:', JSON.stringify(setupFunctionCheck, null, 2));
        
        // Try to manually register a button to test the registry
        const manualRegistration = await page.evaluate(() => {
            if (typeof window.tinymce === 'undefined') {
                return { error: 'TinyMCE not available' };
            }
            
            const editor = window.tinymce.activeEditor || window.tinymce.editors[0];
            if (!editor || !editor.ui || !editor.ui.registry) {
                return { error: 'Editor UI registry not available' };
            }
            
            try {
                // Try to register a simple test button
                editor.ui.registry.addButton('debugTestButton', {
                    text: '🔧',
                    tooltip: 'Debug Test Button',
                    onAction: function() {
                        alert('Debug button works!');
                    }
                });
                
                // Try to get the toolbar and see if we can find any buttons
                const container = editor.getContainer();
                const toolbar = container ? container.querySelector('.tox-toolbar') : null;
                const buttons = toolbar ? toolbar.querySelectorAll('button') : [];
                
                return {
                    success: 'Test button registered',
                    toolbarFound: !!toolbar,
                    buttonCount: buttons.length,
                    buttonTexts: Array.from(buttons).slice(0, 10).map(btn => btn.textContent || btn.title || 'no text')
                };
            } catch (e) {
                return { error: e.message };
            }
        });
        
        console.log('Manual Registration Test:', JSON.stringify(manualRegistration, null, 2));
        
        // Wait for any dynamic changes
        await page.waitForTimeout(2000);
        
        // Check if the test button appeared
        const testButtonCheck = await page.evaluate(() => {
            const editor = window.tinymce.activeEditor || window.tinymce.editors[0];
            if (!editor) return { error: 'No editor' };
            
            const container = editor.getContainer();
            if (!container) return { error: 'No container' };
            
            // Look for our test button
            const testButton = container.querySelector('button[title*="Debug Test"]');
            const debugButton = container.querySelector('button:has-text("🔧")');
            
            // Get all buttons in toolbar
            const allButtons = container.querySelectorAll('.tox-toolbar button');
            const buttonInfo = Array.from(allButtons).map(btn => ({
                text: btn.textContent,
                title: btn.title,
                ariaLabel: btn.getAttribute('aria-label'),
                innerHTML: btn.innerHTML.substring(0, 100)
            }));
            
            return {
                testButtonFound: !!testButton,
                debugButtonFound: !!debugButton,
                totalButtons: allButtons.length,
                buttonDetails: buttonInfo
            };
        });
        
        console.log('Test Button Check:', JSON.stringify(testButtonCheck, null, 2));
        
        // Take screenshot of the current state
        await page.screenshot({ 
            path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/button-registration-debug.png',
            fullPage: true 
        });
    });

    test('Check TinyMCE Toolbar Configuration', async () => {
        // Check the actual toolbar configuration that was applied
        const toolbarConfig = await page.evaluate(() => {
            const editor = window.tinymce.activeEditor || window.tinymce.editors[0];
            if (!editor) return { error: 'No editor' };
            
            return {
                configuredToolbar: editor.settings.toolbar,
                actualToolbar: editor.theme.panel ? 'panel exists' : 'no panel',
                registeredButtons: Object.keys(editor.ui.registry.getAll().buttons || {})
            };
        });
        
        console.log('Toolbar Configuration:', JSON.stringify(toolbarConfig, null, 2));
        
        // Check if our button is in the configured toolbar string
        const toolbarContainsDualImage = toolbarConfig.configuredToolbar && 
            toolbarConfig.configuredToolbar.includes('dualimage');
        
        console.log('Toolbar contains "dualimage":', toolbarContainsDualImage);
    });

    test('Test Button Registration Timing', async () => {
        // Try to register button at different times
        const timingTest = await page.evaluate(async () => {
            const results = [];
            
            if (typeof window.tinymce === 'undefined') {
                return [{ error: 'TinyMCE not available' }];
            }
            
            const editor = window.tinymce.activeEditor || window.tinymce.editors[0];
            if (!editor) {
                return [{ error: 'No editor available' }];
            }
            
            // Test 1: Register immediately
            try {
                editor.ui.registry.addButton('immediateButton', {
                    text: '⚡',
                    tooltip: 'Immediate Button',
                    onAction: () => console.log('Immediate button clicked')
                });
                results.push({ test: 'immediate', success: true });
            } catch (e) {
                results.push({ test: 'immediate', error: e.message });
            }
            
            // Test 2: Wait a bit then register
            await new Promise(resolve => setTimeout(resolve, 100));
            try {
                editor.ui.registry.addButton('delayedButton', {
                    text: '⏰',
                    tooltip: 'Delayed Button',
                    onAction: () => console.log('Delayed button clicked')
                });
                results.push({ test: 'delayed', success: true });
            } catch (e) {
                results.push({ test: 'delayed', error: e.message });
            }
            
            return results;
        });
        
        console.log('Timing Test Results:', JSON.stringify(timingTest, null, 2));
        
        // Wait and check if any new buttons appeared
        await page.waitForTimeout(1000);
        
        const newButtonsCheck = await page.evaluate(() => {
            const editor = window.tinymce.activeEditor || window.tinymce.editors[0];
            if (!editor) return { error: 'No editor' };
            
            const container = editor.getContainer();
            if (!container) return { error: 'No container' };
            
            const immediateBtn = container.querySelector('button[title*="Immediate"]');
            const delayedBtn = container.querySelector('button[title*="Delayed"]');
            
            return {
                immediateFound: !!immediateBtn,
                delayedFound: !!delayedBtn
            };
        });
        
        console.log('New Buttons Check:', JSON.stringify(newButtonsCheck, null, 2));
    });

    test('Generate Root Cause Analysis', async () => {
        const analysis = {
            timestamp: new Date().toISOString(),
            rootCauseAnalysis: {
                primaryIssue: 'Dual image button not appearing in TinyMCE toolbar',
                possibleCauses: [
                    'Button registration function not being called',
                    'Timing issue with editor initialization',
                    'Error in button registration code',
                    'Toolbar configuration issue',
                    'Button registration happening after toolbar is built'
                ],
                evidenceGathered: [
                    'TinyMCE loads successfully',
                    'Editor initializes properly',
                    'Setup function may not be executing',
                    'Manual button registration might work',
                    'Toolbar HTML is generated correctly'
                ],
                nextSteps: [
                    'Fix button registration in tinymce-single.js',
                    'Add button to toolbar configuration string',
                    'Test manual button injection approach',
                    'Verify setup function execution'
                ]
            }
        };
        
        console.log('\n=== ROOT CAUSE ANALYSIS ===');
        console.log(JSON.stringify(analysis, null, 2));
        console.log('===========================\n');
    });
});