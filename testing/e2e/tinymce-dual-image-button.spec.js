const { test, expect } = require('@playwright/test');

test.describe('TinyMCE Dual Image Button', () => {
    test.beforeEach(async ({ page }) => {
        // Navigate to admin login
        await page.goto('http://localhost:8000/admin/login');
        
        // Wait for page to load
        await page.waitForLoadState('networkidle');
        
        // Fill login form with better selectors
        await page.fill('#username', 'kevin');
        await page.fill('#password', '(130Bpm)');
        await page.click('button[type="submit"]');
        
        // Wait for redirect to dashboard
        await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
    });

    test('should display dual image button in TinyMCE toolbar', async ({ page }) => {
        // Navigate to content creation page
        await page.goto('http://localhost:8000/admin/content/create?type=article');
        
        // Wait for TinyMCE to initialize
        await page.waitForSelector('.tox-tinymce', { timeout: 10000 });
        
        // Wait a bit more for the editor to fully load
        await page.waitForTimeout(2000);
        
        // Check if TinyMCE is loaded
        const editorExists = await page.evaluate(() => {
            return typeof tinymce !== 'undefined' && tinymce.get('body') !== null;
        });
        
        console.log('TinyMCE loaded:', editorExists);
        
        // Check if dual image button is registered
        const buttonRegistered = await page.evaluate(() => {
            if (typeof tinymce === 'undefined') return false;
            const editor = tinymce.get('body');
            if (!editor) return false;
            
            const buttons = editor.ui.registry.getAll().buttons;
            return !!buttons.dualimage;
        });
        
        console.log('Dual image button registered:', buttonRegistered);
        expect(buttonRegistered).toBe(true);
        
        // Check if button appears in toolbar by looking for the emoji text
        const buttonInToolbar = await page.evaluate(() => {
            const toolbar = document.querySelector('.tox-toolbar');
            if (!toolbar) return false;
            
            // Look for button with dual image emoji text
            const buttons = toolbar.querySelectorAll('button');
            return Array.from(buttons).some(btn => {
                const text = btn.textContent || '';
                return text.includes('🖼️📱') || 
                       (btn.title && btn.title.includes('Insert image with modal view'));
            });
        });
        
        console.log('Dual image button in toolbar:', buttonInToolbar);
        expect(buttonInToolbar).toBe(true);
        
        // Try to find and click the dual image button
        const dualImageButtonSelector = 'button[title*="Insert image with modal view"], button:has-text("🖼️📱")';
        
        try {
            await page.waitForSelector(dualImageButtonSelector, { timeout: 5000 });
            console.log('✅ Dual image button found in DOM');
            
            // Take a screenshot for verification
            await page.screenshot({ 
                path: 'testing/screenshots/tinymce-dual-image-button.png',
                fullPage: false 
            });
            
        } catch (error) {
            console.log('❌ Dual image button not found in DOM');
            
            // Take a screenshot for debugging
            await page.screenshot({ 
                path: 'testing/screenshots/tinymce-toolbar-debug.png',
                fullPage: false 
            });
            
            // Log available buttons for debugging
            const availableButtons = await page.evaluate(() => {
                const toolbar = document.querySelector('.tox-toolbar');
                if (!toolbar) return [];
                
                const buttons = toolbar.querySelectorAll('button');
                return Array.from(buttons).map(btn => ({
                    text: btn.textContent,
                    title: btn.title,
                    classes: btn.className
                }));
            });
            
            console.log('Available toolbar buttons:', availableButtons);
            throw error;
        }
    });

    test('should open dual image dialog when button is clicked', async ({ page }) => {
        // Navigate to content creation page
        await page.goto('http://localhost:8000/admin/content/create?type=article');
        
        // Wait for TinyMCE to initialize
        await page.waitForSelector('.tox-tinymce', { timeout: 10000 });
        await page.waitForTimeout(2000);
        
        // Look for the dual image button and click it
        const dualImageButtonSelector = 'button[title*="Insert image with modal view"], button:has-text("🖼️📱")';
        
        await page.waitForSelector(dualImageButtonSelector, { timeout: 5000 });
        await page.click(dualImageButtonSelector);
        
        // Wait for the dual image dialog to appear
        await page.waitForSelector('.dual-image-dialog', { timeout: 5000 });
        
        // Verify dialog content
        await expect(page.locator('.dual-image-dialog h3')).toHaveText('Insert Image with Modal View');
        await expect(page.locator('input[name="display_image"]')).toBeVisible();
        await expect(page.locator('input[name="modal_image"]')).toBeVisible();
        
        // Take a screenshot of the dialog
        await page.screenshot({ 
            path: 'testing/screenshots/dual-image-dialog.png',
            fullPage: false 
        });
        
        // Close the dialog
        await page.click('.close-btn');
        
        // Verify dialog is closed
        await expect(page.locator('.dual-image-dialog')).not.toBeVisible();
    });

    test('should check if showDualImageDialog function exists', async ({ page }) => {
        // Navigate to content creation page
        await page.goto('http://localhost:8000/admin/content/create?type=article');
        
        // Wait for TinyMCE to initialize
        await page.waitForSelector('.tox-tinymce', { timeout: 10000 });
        await page.waitForTimeout(2000);
        
        // Check if the showDualImageDialog function exists
        const functionExists = await page.evaluate(() => {
            return typeof showDualImageDialog === 'function';
        });
        
        console.log('showDualImageDialog function exists:', functionExists);
        expect(functionExists).toBe(true);
    });
});