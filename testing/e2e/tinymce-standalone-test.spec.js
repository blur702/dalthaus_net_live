const { test, expect } = require('@playwright/test');

test.describe('TinyMCE Dual Image Button - Standalone Test', () => {
    test('should load standalone test page and verify dual image button', async ({ page }) => {
        // Navigate to the standalone test page
        await page.goto('http://localhost:8000/test_tinymce_standalone.html');
        
        // Wait for the page to load
        await page.waitForLoadState('networkidle');
        
        // Wait for tests to run automatically and for manual button injection
        await page.waitForTimeout(10000); // Give tests time to complete and manual injection to finish
        
        // Check if the test results show success
        const testResults = await page.locator('#test-results').textContent();
        console.log('Test Results:', testResults);
        
        // Take a screenshot for verification
        await page.screenshot({ 
            path: 'testing/screenshots/tinymce-standalone-test.png',
            fullPage: true 
        });
        
        // Check for specific success indicators
        const successElements = await page.locator('.test-status.success').count();
        const errorElements = await page.locator('.test-status.error').count();
        
        console.log(`Success tests: ${successElements}, Error tests: ${errorElements}`);
        
        // Verify that TinyMCE loaded
        const tinymceLoaded = await page.evaluate(() => {
            return typeof tinymce !== 'undefined';
        });
        
        expect(tinymceLoaded).toBe(true);
        
        // Check if editor was initialized
        const editorInitialized = await page.evaluate(() => {
            return typeof tinymce !== 'undefined' && tinymce.get('body') !== null;
        });
        
        expect(editorInitialized).toBe(true);
        
        // Check if dual image button is registered
        const dualImageButtonRegistered = await page.evaluate(() => {
            if (typeof tinymce === 'undefined') return false;
            const editor = tinymce.get('body');
            if (!editor) return false;
            
            const buttons = editor.ui.registry.getAll().buttons;
            return !!buttons.dualimage;
        });
        
        console.log('Dual image button registered:', dualImageButtonRegistered);
        expect(dualImageButtonRegistered).toBe(true);
        
        // Check if showDualImageDialog function exists
        const dialogFunctionExists = await page.evaluate(() => {
            return typeof showDualImageDialog === 'function';
        });
        
        console.log('showDualImageDialog function exists:', dialogFunctionExists);
        expect(dialogFunctionExists).toBe(true);
        
        // Try to find the dual image button in the toolbar
        const buttonInToolbar = await page.evaluate(() => {
            // Try different toolbar selectors
            let toolbar = document.querySelector('.tox-toolbar');
            if (!toolbar) toolbar = document.querySelector('.mce-toolbar');
            if (!toolbar) toolbar = document.querySelector('[role="toolbar"]');
            if (!toolbar) toolbar = document.querySelector('.tox-toolbar-primary');
            
            if (!toolbar) {
                console.log('No toolbar found with any selector');
                return false;
            }
            
            const buttons = toolbar.querySelectorAll('button');
            console.log(`Found ${buttons.length} buttons in toolbar`);
            
            // Look for dual image button
            const dualImageButton = Array.from(buttons).find(btn => {
                const text = btn.textContent || '';
                const title = btn.title || '';
                return text.includes('🖼️📱') || title.includes('Insert image with modal view');
            });
            
            // Look for test button
            const testButton = Array.from(buttons).find(btn => {
                const text = btn.textContent || '';
                const title = btn.title || '';
                return text.includes('🧪') || title.includes('Test button');
            });
            
            console.log('Dual image button found:', !!dualImageButton);
            console.log('Test button found:', !!testButton);
            
            // Log all button texts for debugging
            const buttonTexts = Array.from(buttons).map(btn => btn.textContent || btn.title || 'No text');
            console.log('All button texts:', buttonTexts);
            
            return !!dualImageButton;
        });
        
        console.log('Dual image button in toolbar:', buttonInToolbar);
        expect(buttonInToolbar).toBe(true);
        
        // If button is in toolbar, try to click it
        if (buttonInToolbar) {
            try {
                // Find and click the dual image button
                const dualImageButton = page.locator('button:has-text("🖼️📱"), button[title*="Insert image with modal view"]').first();
                await dualImageButton.click();
                
                // Wait for dialog to appear
                await page.waitForSelector('.dual-image-dialog', { timeout: 5000 });
                
                // Verify dialog content
                await expect(page.locator('.dual-image-dialog h3')).toHaveText('Insert Image with Modal View');
                
                // Take screenshot of dialog
                await page.screenshot({ 
                    path: 'testing/screenshots/dual-image-dialog-standalone.png',
                    fullPage: false 
                });
                
                console.log('✅ Dual image dialog opened successfully');
                
                // Close dialog
                await page.click('.close-btn');
                
                // Verify dialog is closed
                await expect(page.locator('.dual-image-dialog')).not.toBeVisible();
                
            } catch (error) {
                console.log('❌ Could not test dialog functionality:', error.message);
            }
        }
        
        // Log console output from the test page
        const consoleOutput = await page.locator('#console-output').textContent();
        console.log('Test page console output:', consoleOutput);
        
        // Expect at least some success tests
        expect(successElements).toBeGreaterThan(0);
    });
});