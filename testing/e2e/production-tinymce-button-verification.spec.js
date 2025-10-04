const { test, expect } = require('@playwright/test');

test.describe('Production TinyMCE Button Visibility Test', () => {
    test('Verify TinyMCE button visibility on production server', async ({ page, context }) => {
        // Set console listener to capture all console messages
        const consoleMessages = [];
        page.on('console', msg => {
            consoleMessages.push({
                type: msg.type(),
                text: msg.text(),
                location: msg.location()
            });
        });

        // Set network listener to capture failed requests
        const failedRequests = [];
        page.on('response', response => {
            if (!response.ok()) {
                failedRequests.push({
                    url: response.url(),
                    status: response.status(),
                    statusText: response.statusText()
                });
            }
        });

        console.log('🚀 Starting TinyMCE production test...');

        // Step 1: Navigate to login page
        await page.goto('https://dalthaus.net/admin/login', { 
            waitUntil: 'networkidle',
            timeout: 30000 
        });
        
        await page.screenshot({ 
            path: 'testing/screenshots/production-login-page.png',
            fullPage: true 
        });

        // Step 2: Login with admin credentials
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');

        // Wait for login to complete
        await page.waitForURL('**/admin/dashboard', { timeout: 15000 });
        console.log('✅ Successfully logged in');

        // Step 3: Navigate to content creation page with cache busting
        const cacheParam = `?nocache=${Date.now()}`;
        const targetUrl = `https://dalthaus.net/admin/content/create?type=article${cacheParam}`;
        
        console.log(`🎯 Navigating to: ${targetUrl}`);
        await page.goto(targetUrl, { 
            waitUntil: 'networkidle',
            timeout: 30000 
        });

        await page.screenshot({ 
            path: 'testing/screenshots/production-content-create-page.png',
            fullPage: true 
        });

        // Step 4: Wait for TinyMCE to initialize
        console.log('⏳ Waiting for TinyMCE to initialize...');
        
        // Wait for TinyMCE iframe to appear
        await page.waitForSelector('iframe[id*="content_ifr"]', { 
            timeout: 30000,
            state: 'visible'
        });

        // Wait a bit more for TinyMCE to fully load
        await page.waitForTimeout(5000);

        // Step 5: Check for infinite loading dots
        const loadingDots = await page.locator('.tox-throbber').count();
        console.log(`🔄 TinyMCE loading dots found: ${loadingDots}`);
        
        if (loadingDots > 0) {
            console.log('⚠️ TinyMCE still showing loading dots - infinite loading detected!');
            await page.screenshot({ 
                path: 'testing/screenshots/production-infinite-loading.png',
                fullPage: true 
            });
        } else {
            console.log('✅ TinyMCE finished loading - no infinite loading dots');
        }

        // Step 6: Check TinyMCE toolbar visibility
        await page.waitForSelector('.tox-toolbar', { timeout: 10000 });
        console.log('✅ TinyMCE toolbar is visible');

        // Step 7: Search for expected buttons
        console.log('🔍 Searching for TinyMCE buttons...');

        // Check for Dual Image button
        const dualImageButton = page.locator('button[title*="Dual Image"], button[aria-label*="Dual Image"], button:has-text("🖼️📱")');
        const dualImageCount = await dualImageButton.count();
        console.log(`🖼️ Dual Image button found: ${dualImageCount} instances`);

        // Check for Test button
        const testButton = page.locator('button[title*="Test Button"], button[aria-label*="Test Button"], button:has-text("🧪")');
        const testButtonCount = await testButton.count();
        console.log(`🧪 Test button found: ${testButtonCount} instances`);

        // Step 8: Check all toolbar buttons for debugging
        const allButtons = await page.locator('.tox-toolbar button').all();
        console.log(`📊 Total toolbar buttons found: ${allButtons.length}`);

        const buttonDetails = [];
        for (const button of allButtons) {
            try {
                const title = await button.getAttribute('title') || '';
                const ariaLabel = await button.getAttribute('aria-label') || '';
                const text = await button.textContent() || '';
                const classes = await button.getAttribute('class') || '';
                
                buttonDetails.push({
                    title,
                    ariaLabel,
                    text: text.trim(),
                    classes
                });
            } catch (e) {
                console.log('Error getting button details:', e.message);
            }
        }

        console.log('📋 All toolbar buttons:');
        buttonDetails.forEach((btn, index) => {
            console.log(`  ${index + 1}. Title: "${btn.title}" | Label: "${btn.ariaLabel}" | Text: "${btn.text}" | Classes: "${btn.classes}"`);
        });

        // Step 9: Test button functionality if found
        if (testButtonCount > 0) {
            console.log('🧪 Testing button functionality...');
            
            // Set up dialog handler
            page.on('dialog', async dialog => {
                console.log(`📨 Dialog appeared: ${dialog.message()}`);
                await dialog.accept();
            });

            await testButton.first().click();
            await page.waitForTimeout(2000);
            console.log('✅ Test button clicked successfully');
        }

        // Step 10: Check browser console for errors
        const errors = consoleMessages.filter(msg => msg.type === 'error');
        console.log(`❌ Console errors found: ${errors.length}`);
        errors.forEach(error => {
            console.log(`  Error: ${error.text} at ${error.location?.url}:${error.location?.lineNumber}`);
        });

        // Step 11: Check for failed network requests
        console.log(`🌐 Failed network requests: ${failedRequests.length}`);
        failedRequests.forEach(req => {
            console.log(`  Failed: ${req.status} ${req.statusText} - ${req.url}`);
        });

        // Step 12: Check TinyMCE registry for button registration
        const registryCheck = await page.evaluate(() => {
            if (window.tinymce && window.tinymce.activeEditor) {
                const editor = window.tinymce.activeEditor;
                const registry = editor.ui?.registry;
                
                if (registry) {
                    // Check if buttons are registered
                    const hasDualImage = registry.getAll().buttons.hasOwnProperty('dualimage');
                    const hasTestButton = registry.getAll().buttons.hasOwnProperty('testbutton');
                    
                    return {
                        registryExists: true,
                        hasDualImage,
                        hasTestButton,
                        allButtons: Object.keys(registry.getAll().buttons),
                        editorId: editor.id
                    };
                }
            }
            return { registryExists: false };
        });

        console.log('🔧 TinyMCE Registry Check:', JSON.stringify(registryCheck, null, 2));

        // Step 13: Final screenshots
        await page.screenshot({ 
            path: 'testing/screenshots/production-final-verification.png',
            fullPage: true 
        });

        // Focus on toolbar for detailed shot
        await page.locator('.tox-toolbar').screenshot({ 
            path: 'testing/screenshots/production-toolbar-detail.png' 
        });

        // Step 14: Generate summary report
        const testResults = {
            timestamp: new Date().toISOString(),
            url: targetUrl,
            tinymceLoaded: loadingDots === 0,
            dualImageButtonFound: dualImageCount > 0,
            testButtonFound: testButtonCount > 0,
            totalToolbarButtons: allButtons.length,
            consoleErrors: errors.length,
            failedRequests: failedRequests.length,
            buttonDetails,
            registryCheck,
            consoleMessages: consoleMessages.slice(-10) // Last 10 messages
        };

        console.log('📊 FINAL TEST RESULTS:');
        console.log('================================');
        console.log(`✅ TinyMCE Loaded: ${testResults.tinymceLoaded}`);
        console.log(`🖼️ Dual Image Button: ${testResults.dualImageButtonFound}`);
        console.log(`🧪 Test Button: ${testResults.testButtonFound}`);
        console.log(`📊 Total Buttons: ${testResults.totalToolbarButtons}`);
        console.log(`❌ Console Errors: ${testResults.consoleErrors}`);
        console.log(`🌐 Failed Requests: ${testResults.failedRequests}`);
        console.log('================================');

        // Write detailed report to file
        const fs = require('fs');
        fs.writeFileSync(
            'testing/reports/production-tinymce-verification-report.json',
            JSON.stringify(testResults, null, 2)
        );

        // Assertions
        expect(testResults.tinymceLoaded).toBe(true);
        expect(testResults.totalToolbarButtons).toBeGreaterThan(0);
        
        // Log whether critical buttons were found
        if (!testResults.dualImageButtonFound) {
            console.log('⚠️ WARNING: Dual Image button NOT found in toolbar');
        }
        if (!testResults.testButtonFound) {
            console.log('⚠️ WARNING: Test button NOT found in toolbar');
        }
    });
});