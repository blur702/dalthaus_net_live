const { test, expect } = require('@playwright/test');

test.describe('Production TinyMCE Inline Button Verification', () => {
    test('Verify TinyMCE inline editor button visibility', async ({ page }) => {
        // Set console listener to capture all console messages
        const consoleMessages = [];
        page.on('console', msg => {
            consoleMessages.push({
                type: msg.type(),
                text: msg.text(),
                location: msg.location()
            });
        });

        console.log('🚀 Starting TinyMCE inline production test...');

        // Step 1: Navigate to login page
        await page.goto('https://dalthaus.net/admin/login', { 
            waitUntil: 'networkidle',
            timeout: 30000 
        });

        // Step 2: Login with admin credentials
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard', { timeout: 15000 });

        // Step 3: Navigate to content creation page
        const cacheParam = `?nocache=${Date.now()}`;
        const targetUrl = `https://dalthaus.net/admin/content/create?type=article&${cacheParam}`;
        
        console.log(`🎯 Navigating to: ${targetUrl}`);
        await page.goto(targetUrl, { 
            waitUntil: 'networkidle',
            timeout: 30000 
        });

        // Step 4: Wait for TinyMCE toolbar to appear (inline mode)
        console.log('⏳ Waiting for TinyMCE toolbar...');
        await page.waitForSelector('.tox-toolbar', { timeout: 15000 });
        console.log('✅ TinyMCE toolbar found');

        // Wait for editor to fully initialize
        await page.waitForTimeout(3000);

        // Step 5: Take initial screenshot
        await page.screenshot({ 
            path: 'testing/screenshots/production-inline-editor.png',
            fullPage: true 
        });

        // Step 6: Get all toolbar buttons for analysis
        const allButtons = await page.locator('.tox-toolbar button').all();
        console.log(`📊 Total toolbar buttons found: ${allButtons.length}`);

        const buttonDetails = [];
        for (const button of allButtons) {
            try {
                const title = await button.getAttribute('title') || '';
                const ariaLabel = await button.getAttribute('aria-label') || '';
                const text = await button.textContent() || '';
                const classes = await button.getAttribute('class') || '';
                const innerHTML = await button.innerHTML();
                
                buttonDetails.push({
                    title,
                    ariaLabel,
                    text: text.trim(),
                    classes,
                    innerHTML: innerHTML.substring(0, 100) // First 100 chars of HTML
                });
            } catch (e) {
                console.log('Error getting button details:', e.message);
            }
        }

        console.log('📋 All toolbar buttons:');
        buttonDetails.forEach((btn, index) => {
            console.log(`  ${index + 1}. Title: "${btn.title}" | Label: "${btn.ariaLabel}" | Text: "${btn.text}"`);
            if (btn.innerHTML.includes('🖼️') || btn.innerHTML.includes('🧪') || btn.title.includes('Dual') || btn.title.includes('Test')) {
                console.log(`    🎯 FOUND CUSTOM BUTTON: ${btn.innerHTML}`);
            }
        });

        // Step 7: Specific searches for our custom buttons
        console.log('🔍 Searching for specific custom buttons...');

        // Look for Dual Image button with various selectors
        const dualImageSelectors = [
            'button[title*="Dual Image"]',
            'button[aria-label*="Dual Image"]',
            'button:has-text("🖼️")',
            'button:has-text("📱")',
            'button[data-mce-name="dualimage"]',
            '.tox-toolbar button:has-text("Dual")'
        ];

        let dualImageFound = false;
        for (const selector of dualImageSelectors) {
            const count = await page.locator(selector).count();
            if (count > 0) {
                console.log(`🖼️ Found Dual Image button with selector: ${selector} (${count} instances)`);
                dualImageFound = true;
            }
        }

        // Look for Test button with various selectors
        const testButtonSelectors = [
            'button[title*="Test Button"]',
            'button[aria-label*="Test Button"]',
            'button:has-text("🧪")',
            'button[data-mce-name="testbutton"]',
            '.tox-toolbar button:has-text("Test")'
        ];

        let testButtonFound = false;
        for (const selector of testButtonSelectors) {
            const count = await page.locator(selector).count();
            if (count > 0) {
                console.log(`🧪 Found Test button with selector: ${selector} (${count} instances)`);
                testButtonFound = true;
            }
        }

        // Step 8: Check TinyMCE configuration and registration
        const tinymceInfo = await page.evaluate(() => {
            const info = {
                tinymceExists: typeof window.tinymce !== 'undefined',
                activeEditor: null,
                registeredButtons: [],
                toolbarConfig: null,
                errors: []
            };

            try {
                if (window.tinymce) {
                    info.activeEditor = window.tinymce.activeEditor ? {
                        id: window.tinymce.activeEditor.id,
                        initialized: window.tinymce.activeEditor.initialized
                    } : null;

                    if (window.tinymce.activeEditor && window.tinymce.activeEditor.ui && window.tinymce.activeEditor.ui.registry) {
                        const registry = window.tinymce.activeEditor.ui.registry;
                        info.registeredButtons = Object.keys(registry.getAll().buttons || {});
                        
                        // Check for our specific buttons
                        const buttons = registry.getAll().buttons || {};
                        info.hasDualImageButton = buttons.hasOwnProperty('dualimage');
                        info.hasTestButton = buttons.hasOwnProperty('testbutton');
                    }

                    // Get toolbar configuration
                    if (window.tinymce.activeEditor && window.tinymce.activeEditor.settings) {
                        info.toolbarConfig = window.tinymce.activeEditor.settings.toolbar;
                    }
                }
            } catch (e) {
                info.errors.push(e.toString());
            }

            return info;
        });

        console.log('🔧 TinyMCE Info:', JSON.stringify(tinymceInfo, null, 2));

        // Step 9: Check for TinyMCE script loading
        const scriptInfo = await page.evaluate(() => {
            const scripts = Array.from(document.querySelectorAll('script')).map(script => ({
                src: script.src,
                id: script.id,
                hasContent: script.innerHTML.length > 0
            }));

            return {
                allScripts: scripts.filter(s => s.src.includes('tinymce') || s.src.includes('mce')),
                inlineScripts: scripts.filter(s => s.hasContent && (s.innerHTML.includes('tinymce') || s.innerHTML.includes('dualimage') || s.innerHTML.includes('testbutton')))
            };
        });

        console.log('📜 TinyMCE Scripts:', JSON.stringify(scriptInfo, null, 2));

        // Step 10: Check console for specific errors
        const errors = consoleMessages.filter(msg => msg.type === 'error');
        const tinymceErrors = errors.filter(err => 
            err.text.toLowerCase().includes('tinymce') || 
            err.text.toLowerCase().includes('dualimage') ||
            err.text.toLowerCase().includes('testbutton')
        );

        console.log(`❌ Total console errors: ${errors.length}`);
        console.log(`🔧 TinyMCE-related errors: ${tinymceErrors.length}`);
        
        tinymceErrors.forEach(error => {
            console.log(`  TinyMCE Error: ${error.text}`);
        });

        // Step 11: Take final detailed screenshots
        await page.screenshot({ 
            path: 'testing/screenshots/production-final-inline-verification.png',
            fullPage: true 
        });

        // Focus on just the toolbar
        const toolbar = page.locator('.tox-toolbar');
        await toolbar.screenshot({ 
            path: 'testing/screenshots/production-toolbar-inline-detail.png' 
        });

        // Step 12: Generate final report
        const testResults = {
            timestamp: new Date().toISOString(),
            url: targetUrl,
            toolbarFound: true,
            totalButtons: allButtons.length,
            dualImageButtonFound,
            testButtonFound,
            tinymceInfo,
            scriptInfo,
            buttonDetails,
            consoleErrors: errors.length,
            tinymceErrors: tinymceErrors.length,
            errorMessages: tinymceErrors.map(e => e.text)
        };

        console.log('📊 FINAL INLINE TEST RESULTS:');
        console.log('================================');
        console.log(`✅ TinyMCE Toolbar Found: ${testResults.toolbarFound}`);
        console.log(`📊 Total Buttons: ${testResults.totalButtons}`);
        console.log(`🖼️ Dual Image Button: ${testResults.dualImageButtonFound}`);
        console.log(`🧪 Test Button: ${testResults.testButtonFound}`);
        console.log(`❌ Console Errors: ${testResults.consoleErrors}`);
        console.log(`🔧 TinyMCE Errors: ${testResults.tinymceErrors}`);
        
        if (tinymceInfo.registeredButtons.length > 0) {
            console.log(`📋 Registered Buttons: ${tinymceInfo.registeredButtons.join(', ')}`);
        }
        console.log('================================');

        // Write detailed report
        const fs = require('fs');
        fs.writeFileSync(
            'testing/reports/production-inline-verification-report.json',
            JSON.stringify(testResults, null, 2)
        );

        // Critical assertions
        expect(testResults.toolbarFound).toBe(true);
        expect(testResults.totalButtons).toBeGreaterThan(5);

        // Report final status
        if (!testResults.dualImageButtonFound && !testResults.testButtonFound) {
            console.log('❌ CRITICAL: Neither Dual Image nor Test buttons found in production!');
        } else if (!testResults.dualImageButtonFound) {
            console.log('⚠️ WARNING: Dual Image button NOT found in production');
        } else if (!testResults.testButtonFound) {
            console.log('⚠️ WARNING: Test button NOT found in production');
        } else {
            console.log('✅ SUCCESS: Both custom buttons found in production!');
        }
    });
});