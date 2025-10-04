import { test, expect } from '@playwright/test';

test.describe('Dual Image Diagnosis', () => {
    test('Diagnose dual image button and modal functionality', async ({ page }) => {
        console.log('Starting dual image diagnosis...');

        // Step 1: Login to admin
        await page.goto('http://localhost:8000/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard');

        // Step 2: Go to content creation page
        await page.goto('http://localhost:8000/admin/content/create');
        await page.waitForLoadState('networkidle');
        
        // Wait for TinyMCE to fully load
        await page.waitForTimeout(5000);

        console.log('=== TinyMCE DIAGNOSTIC ===');
        
        // Check if TinyMCE is loaded
        const tinymceExists = await page.evaluate(() => {
            return typeof window.tinymce !== 'undefined';
        });
        console.log(`TinyMCE loaded: ${tinymceExists}`);

        if (tinymceExists) {
            // Get TinyMCE editor instance info
            const editorInfo = await page.evaluate(() => {
                const editors = window.tinymce.editors;
                return {
                    editorCount: editors.length,
                    editorIds: editors.map(e => e.id),
                    plugins: editors.length > 0 ? editors[0].settings.plugins : 'No editors'
                };
            });
            console.log('TinyMCE Editor Info:', editorInfo);
        }

        // Take screenshot of the editor
        await page.screenshot({ 
            path: 'testing/results/tinymce-editor-diagnosis.png',
            fullPage: true 
        });

        // Find all buttons in TinyMCE toolbar
        console.log('=== TINYMCE TOOLBAR BUTTONS ===');
        const toolbarButtons = await page.locator('.mce-toolbar button, .mce-toolbar .mce-btn').all();
        
        console.log(`Found ${toolbarButtons.length} toolbar buttons:`);
        for (let i = 0; i < toolbarButtons.length; i++) {
            const button = toolbarButtons[i];
            const text = await button.textContent();
            const title = await button.getAttribute('title');
            const classes = await button.getAttribute('class');
            const ariaLabel = await button.getAttribute('aria-label');
            
            console.log(`${i + 1}. Text: "${text}", Title: "${title}", Classes: "${classes}", Aria: "${ariaLabel}"`);
            
            // Check for dual image related buttons
            if (title && (title.toLowerCase().includes('dual') || title.toLowerCase().includes('image'))) {
                console.log(`   *** POTENTIAL DUAL IMAGE BUTTON FOUND ***`);
            }
        }

        // Check for custom scripts loaded
        console.log('=== LOADED SCRIPTS ===');
        const scripts = await page.locator('script[src]').all();
        for (const script of scripts) {
            const src = await script.getAttribute('src');
            if (src && (src.includes('tinymce') || src.includes('dual') || src.includes('image'))) {
                console.log(`Relevant script: ${src}`);
            }
        }

        // Check for JavaScript errors
        const jsErrors = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                jsErrors.push(msg.text());
            }
        });

        // Try to find and click dual image button
        console.log('=== TESTING DUAL IMAGE BUTTON ===');
        
        const buttonSelectors = [
            'button[title*="dual" i]',
            'button[title*="Dual" i]',
            '.mce-i-dual-image',
            'button:has-text("Dual Image")',
            'button[aria-label*="dual" i]'
        ];

        let buttonFound = false;
        for (const selector of buttonSelectors) {
            try {
                const button = page.locator(selector).first();
                if (await button.isVisible()) {
                    console.log(`✓ Found dual image button with selector: ${selector}`);
                    
                    // Click the button and see what happens
                    await button.click();
                    await page.waitForTimeout(2000);
                    
                    // Check if modal opened
                    const modals = await page.locator('.modal, .mce-window, [role="dialog"]').all();
                    console.log(`After click: ${modals.length} modal(s) detected`);
                    
                    if (modals.length > 0) {
                        console.log('✓ Modal opened successfully');
                        await page.screenshot({ 
                            path: 'testing/results/dual-image-modal-diagnosis.png',
                            fullPage: true 
                        });
                        
                        // Close modal for next tests
                        await page.keyboard.press('Escape');
                        await page.waitForTimeout(1000);
                    }
                    
                    buttonFound = true;
                    break;
                }
            } catch (error) {
                console.log(`Button selector ${selector} failed: ${error.message}`);
            }
        }

        if (!buttonFound) {
            console.log('❌ No dual image button found');
            
            // Look for any buttons with "image" in the name
            const imageButtons = await page.locator('button').filter({ hasText: /image/i }).all();
            console.log(`Found ${imageButtons.length} buttons with "image" in text:`);
            for (const button of imageButtons) {
                const text = await button.textContent();
                const title = await button.getAttribute('title');
                console.log(`- "${text}" (title: "${title}")`);
            }
        }

        // Check existing content for dual images
        console.log('=== CHECKING EXISTING CONTENT ===');
        await page.goto('http://localhost:8000/articles');
        await page.waitForLoadState('networkidle');
        
        const images = await page.locator('img').all();
        console.log(`Found ${images.length} images on articles page`);
        
        let dualImageFound = false;
        for (const img of images) {
            const src = await img.getAttribute('src');
            const dataSrc = await img.getAttribute('data-modal-src');
            const onclick = await img.getAttribute('onclick');
            
            if (dataSrc || onclick || (src && src.includes('modal'))) {
                console.log('✓ Found potential dual image:');
                console.log(`  src: ${src}`);
                console.log(`  data-modal-src: ${dataSrc}`);
                console.log(`  onclick: ${onclick}`);
                dualImageFound = true;
                
                // Test clicking this image
                console.log('Testing modal functionality...');
                await img.click();
                await page.waitForTimeout(1000);
                
                // Check for modal
                const modalAfterClick = await page.locator('.modal, .image-modal, [role="dialog"], .overlay, .lightbox').count();
                console.log(`Modal elements after click: ${modalAfterClick}`);
                
                await page.screenshot({ 
                    path: 'testing/results/existing-dual-image-test.png',
                    fullPage: true 
                });
                
                break;
            }
        }

        if (!dualImageFound) {
            console.log('No existing dual images found to test');
        }

        // Report JavaScript errors
        if (jsErrors.length > 0) {
            console.log('=== JAVASCRIPT ERRORS ===');
            jsErrors.forEach((error, index) => {
                console.log(`${index + 1}. ${error}`);
            });
        } else {
            console.log('✓ No JavaScript errors detected');
        }

        console.log('=== DIAGNOSIS COMPLETE ===');
    });
});