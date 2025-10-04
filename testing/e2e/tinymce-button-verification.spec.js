const { test, expect } = require('@playwright/test');

test.describe('TinyMCE Button Verification', () => {
    test.beforeEach(async ({ page }) => {
        // Enable console logging to debug
        page.on('console', msg => console.log('PAGE LOG:', msg.text()));
        page.on('pageerror', error => console.log('PAGE ERROR:', error.message));
    });

    test('should load TinyMCE editor with custom buttons on content creation page', async ({ page }) => {
        console.log('🧪 Testing TinyMCE button visibility...');
        
        // Navigate to admin content creation page
        console.log('Navigating to admin content creation page...');
        await page.goto('https://dalthaus.net/admin/content/create?type=article');
        
        // Wait for page to load
        await page.waitForLoadState('networkidle');
        
        // Check if we're redirected to login (not authenticated)
        const currentUrl = page.url();
        if (currentUrl.includes('/admin/login')) {
            console.log('🔑 Login required - attempting to authenticate...');
            
            // Try to login with admin credentials
            try {
                await page.fill('input[name="username"]', 'kevin');
                await page.fill('input[name="password"]', '(130Bpm)');
                await page.click('button[type="submit"]');
                
                // Wait for redirect after login
                await page.waitForLoadState('networkidle');
                
                // Check if login was successful
                if (page.url().includes('/admin/login')) {
                    console.log('❌ Login failed - authentication required');
                    await page.screenshot({ path: 'testing/screenshots/login-failed.png' });
                    test.skip('Test requires valid admin authentication');
                    return;
                } else {
                    console.log('✅ Login successful, proceeding with test...');
                    // Navigate back to content creation page if we're not there
                    if (!page.url().includes('/admin/content/create')) {
                        await page.goto('https://dalthaus.net/admin/content/create?type=article');
                        await page.waitForLoadState('networkidle');
                    }
                }
            } catch (error) {
                console.log('❌ Login attempt failed:', error.message);
                await page.screenshot({ path: 'testing/screenshots/login-error.png' });
                test.skip('Unable to authenticate');
                return;
            }
        }
        
        console.log('✅ Successfully accessed admin content creation page');
        console.log('Current URL:', currentUrl);
        
        // Wait for TinyMCE to load
        console.log('Waiting for TinyMCE to initialize...');
        
        // Wait for the textarea to exist
        await page.waitForSelector('textarea#body', { timeout: 10000 });
        console.log('✅ Found textarea#body element');
        
        // Wait for TinyMCE to initialize (look for the TinyMCE iframe or editor)
        try {
            // Wait for TinyMCE iframe or editor container
            await page.waitForSelector('.tox-tinymce', { timeout: 15000 });
            console.log('✅ TinyMCE container found');
        } catch (error) {
            console.log('❌ TinyMCE container not found within timeout');
            await page.screenshot({ path: 'testing/screenshots/tinymce-timeout.png' });
            throw error;
        }
        
        // Wait for toolbar to be visible
        try {
            await page.waitForSelector('.tox-toolbar', { timeout: 10000 });
            console.log('✅ TinyMCE toolbar found');
        } catch (error) {
            console.log('❌ TinyMCE toolbar not found');
            await page.screenshot({ path: 'testing/screenshots/toolbar-missing.png' });
            throw error;
        }
        
        // Take screenshot of the current state
        await page.screenshot({ path: 'testing/screenshots/tinymce-loaded.png' });
        
        // Check for specific custom buttons
        console.log('Checking for custom buttons...');
        
        // Look for dual image button (🖼️📱)
        const dualImageButton = await page.locator('button[title*="Dual Image"], button:has-text("🖼️📱")').first();
        const dualImageExists = await dualImageButton.count() > 0;
        console.log('Dual Image Button (🖼️📱):', dualImageExists ? '✅ FOUND' : '❌ NOT FOUND');
        
        // Look for test button (🧪)
        const testButton = await page.locator('button[title*="Test Button"], button:has-text("🧪")').first();
        const testButtonExists = await testButton.count() > 0;
        console.log('Test Button (🧪):', testButtonExists ? '✅ FOUND' : '❌ NOT FOUND');
        
        // Get all toolbar buttons for debugging
        const allButtons = await page.locator('.tox-toolbar button').all();
        console.log(`Total toolbar buttons found: ${allButtons.length}`);
        
        // Log button texts for debugging
        for (let i = 0; i < Math.min(allButtons.length, 20); i++) {
            const buttonText = await allButtons[i].textContent();
            const buttonTitle = await allButtons[i].getAttribute('title');
            console.log(`Button ${i + 1}: Text="${buttonText}" Title="${buttonTitle}"`);
        }
        
        // Test button functionality if found
        if (testButtonExists) {
            console.log('Testing button functionality...');
            
            // Handle alert dialog that will appear when clicking test button
            page.once('dialog', async dialog => {
                console.log('✅ Test button triggered alert:', dialog.message());
                await dialog.accept();
            });
            
            await testButton.click();
            console.log('✅ Test button clicked successfully');
        }
        
        // Take final screenshot
        await page.screenshot({ path: 'testing/screenshots/tinymce-final-verification.png' });
        
        // Assertions
        expect(dualImageExists || testButtonExists, 'At least one custom button should be visible').toBeTruthy();
        
        if (dualImageExists) {
            console.log('✅ SUCCESS: Dual Image button is visible');
        }
        if (testButtonExists) {
            console.log('✅ SUCCESS: Test button is visible');
        }
        
        console.log('🎉 TinyMCE button verification completed');
    });
    
    test('should verify TinyMCE loads without infinite loading dots', async ({ page }) => {
        console.log('🧪 Testing TinyMCE loading state...');
        
        await page.goto('https://dalthaus.net/admin/content/create?type=article');
        await page.waitForLoadState('networkidle');
        
        // Handle login if needed
        if (page.url().includes('/admin/login')) {
            console.log('🔑 Login required for loading test...');
            try {
                await page.fill('input[name="username"]', 'kevin');
                await page.fill('input[name="password"]', '(130Bpm)');
                await page.click('button[type="submit"]');
                await page.waitForLoadState('networkidle');
                
                if (page.url().includes('/admin/login')) {
                    test.skip('Test requires valid admin authentication');
                    return;
                }
                
                // Navigate to content creation page
                if (!page.url().includes('/admin/content/create')) {
                    await page.goto('https://dalthaus.net/admin/content/create?type=article');
                    await page.waitForLoadState('networkidle');
                }
            } catch (error) {
                test.skip('Unable to authenticate for loading test');
                return;
            }
        }
        
        // Wait for textarea
        await page.waitForSelector('textarea#body', { timeout: 10000 });
        
        // Check that TinyMCE initializes within reasonable time
        const startTime = Date.now();
        await page.waitForSelector('.tox-tinymce', { timeout: 15000 });
        const loadTime = Date.now() - startTime;
        
        console.log(`✅ TinyMCE loaded in ${loadTime}ms`);
        
        // Verify no loading indicators are stuck
        const loadingIndicators = await page.locator('.tox-throbber, .mce-loading').count();
        console.log('Loading indicators still present:', loadingIndicators);
        
        expect(loadTime).toBeLessThan(15000);
        expect(loadingIndicators).toBe(0);
        
        await page.screenshot({ path: 'testing/screenshots/tinymce-load-verification.png' });
    });
});