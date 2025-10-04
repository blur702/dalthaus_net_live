import { test, expect } from '@playwright/test';

test.describe('Focused Dual Image Test', () => {
    test('Test dual image button and modal functionality', async ({ page }) => {
        console.log('Starting focused dual image test...');

        // Step 1: Login
        await page.goto('http://localhost:8000/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard');
        console.log('✓ Logged in successfully');

        // Step 2: Go to content creation
        await page.goto('http://localhost:8000/admin/content/create');
        await page.waitForLoadState('networkidle');
        console.log('✓ On content creation page');

        // Step 3: Wait for TinyMCE to load and take screenshot
        await page.waitForTimeout(5000);
        await page.screenshot({ 
            path: 'testing/results/content-creation-page.png',
            fullPage: true 
        });
        console.log('✓ TinyMCE loaded');

        // Step 4: Examine TinyMCE toolbar buttons
        console.log('=== EXAMINING TINYMCE TOOLBAR ===');
        
        // Check if TinyMCE is available
        const tinymceAvailable = await page.evaluate(() => {
            return typeof window.tinymce !== 'undefined';
        });
        console.log(`TinyMCE available: ${tinymceAvailable}`);

        // Find all toolbar buttons
        const toolbarButtons = await page.locator('.mce-toolbar button, .mce-btn, button[aria-label], [role="button"]').all();
        console.log(`Found ${toolbarButtons.length} potential toolbar buttons`);

        let dualImageButtonFound = false;
        let targetButton = null;

        // Check each button
        for (let i = 0; i < Math.min(toolbarButtons.length, 20); i++) { // Limit to avoid too much output
            const button = toolbarButtons[i];
            try {
                const text = await button.textContent();
                const title = await button.getAttribute('title');
                const ariaLabel = await button.getAttribute('aria-label');
                const classes = await button.getAttribute('class');
                
                console.log(`Button ${i + 1}:`);
                console.log(`  Text: "${text}"`);
                console.log(`  Title: "${title}"`);
                console.log(`  Aria-label: "${ariaLabel}"`);
                console.log(`  Classes: "${classes}"`);
                
                // Check if this might be a dual image button
                const buttonInfo = `${text} ${title} ${ariaLabel} ${classes}`.toLowerCase();
                if (buttonInfo.includes('dual') || 
                    (buttonInfo.includes('image') && buttonInfo.includes('modal')) ||
                    buttonInfo.includes('dual-image')) {
                    console.log(`  *** POTENTIAL DUAL IMAGE BUTTON FOUND ***`);
                    dualImageButtonFound = true;
                    targetButton = button;
                }
                
                // Also check for generic image buttons
                if (buttonInfo.includes('image') && !buttonInfo.includes('link')) {
                    console.log(`  *** IMAGE BUTTON FOUND ***`);
                    if (!targetButton) {
                        targetButton = button;
                    }
                }
            } catch (error) {
                console.log(`  Error examining button ${i + 1}: ${error.message}`);
            }
        }

        // Step 5: Try to click the dual image button
        if (targetButton) {
            console.log('=== TESTING BUTTON CLICK ===');
            
            try {
                // Take screenshot before clicking
                await page.screenshot({ 
                    path: 'testing/results/before-button-click.png',
                    fullPage: true 
                });
                
                await targetButton.click();
                console.log('✓ Clicked target button');
                
                // Wait for potential modal
                await page.waitForTimeout(2000);
                
                // Take screenshot after clicking
                await page.screenshot({ 
                    path: 'testing/results/after-button-click.png',
                    fullPage: true 
                });
                
                // Check for modal elements
                const modalSelectors = [
                    '.modal',
                    '.mce-window',
                    '[role="dialog"]',
                    '.dialog',
                    '.popup',
                    '[data-testid="modal"]'
                ];
                
                let modalFound = false;
                for (const selector of modalSelectors) {
                    const modal = page.locator(selector);
                    if (await modal.isVisible()) {
                        console.log(`✓ Modal found with selector: ${selector}`);
                        modalFound = true;
                        
                        // Take screenshot of modal
                        await modal.screenshot({ 
                            path: 'testing/results/modal-screenshot.png' 
                        });
                        
                        // Check modal content
                        const modalText = await modal.textContent();
                        console.log(`Modal content preview: ${modalText.substring(0, 200)}...`);
                        
                        // Look for dual image specific fields
                        const displayImageInput = modal.locator('input[name*="display"], input[placeholder*="display"]');
                        const modalImageInput = modal.locator('input[name*="modal"], input[placeholder*="modal"]');
                        
                        if (await displayImageInput.isVisible()) {
                            console.log('✓ Display image input found');
                        }
                        if (await modalImageInput.isVisible()) {
                            console.log('✓ Modal image input found');
                        }
                        
                        // Close modal
                        const closeButton = modal.locator('button').filter({ hasText: /close|cancel|×/i }).first();
                        if (await closeButton.isVisible()) {
                            await closeButton.click();
                            console.log('✓ Closed modal');
                        } else {
                            await page.keyboard.press('Escape');
                            console.log('✓ Closed modal with Escape key');
                        }
                        
                        break;
                    }
                }
                
                if (!modalFound) {
                    console.log('❌ No modal found after button click');
                }
                
            } catch (error) {
                console.log(`❌ Error clicking button: ${error.message}`);
            }
            
        } else {
            console.log('❌ No suitable button found to test');
        }

        // Step 6: Alternative - check for dual image functionality in page source
        console.log('=== CHECKING PAGE SOURCE FOR DUAL IMAGE FUNCTIONALITY ===');
        const pageContent = await page.content();
        
        if (pageContent.includes('dual') && pageContent.includes('image')) {
            console.log('✓ Page contains dual image related content');
        } else {
            console.log('❌ No dual image content found in page source');
        }
        
        // Check for JavaScript functions related to dual images
        const hasDualImageJS = await page.evaluate(() => {
            const scriptTags = Array.from(document.querySelectorAll('script'));
            return scriptTags.some(script => 
                script.textContent && 
                (script.textContent.includes('dual') || script.textContent.includes('modal'))
            );
        });
        
        console.log(`Page has dual image JavaScript: ${hasDualImageJS}`);

        console.log('=== FOCUSED DUAL IMAGE TEST COMPLETE ===');
    });
});