const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

(async () => {
    const browser = await chromium.launch({
        headless: false
    });
    
    const context = await browser.newContext();
    const page = await context.newPage();
    
    console.log('Testing TinyMCE image upload and display...');
    console.log('==========================================');
    
    try {
        // Create a simple test image
        const testImagePath = path.join(process.cwd(), 'test_tinymce_image.png');
        const pngData = Buffer.from([
            0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A, 0x00, 0x00, 0x00, 0x0D,
            0x49, 0x48, 0x44, 0x52, 0x00, 0x00, 0x00, 0x64, 0x00, 0x00, 0x00, 0x64,
            0x08, 0x06, 0x00, 0x00, 0x00, 0x70, 0xE2, 0x95, 0x25, 0x00, 0x00, 0x00,
            0x13, 0x49, 0x44, 0x41, 0x54, 0x78, 0x9C, 0x63, 0xF8, 0x0F, 0x00, 0x01,
            0x01, 0x01, 0x00, 0x18, 0xDD, 0x8D, 0xB4, 0x00, 0x00, 0x00, 0x00, 0x49,
            0x45, 0x4E, 0x44, 0xAE, 0x42, 0x60, 0x82
        ]);
        fs.writeFileSync(testImagePath, pngData);
        
        // 1. Login
        console.log('\\n1. Logging into admin');
        await page.goto('https://dalthaus.net/admin/login', {
            waitUntil: 'networkidle'
        });
        
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        
        // 2. Create new content
        console.log('2. Creating new content to test image upload');
        await page.goto('https://dalthaus.net/admin/content/create', {
            waitUntil: 'networkidle'
        });
        
        // Fill basic details
        await page.fill('input[name="title"]', 'TinyMCE Image Upload Test');
        await page.fill('input[name="url_alias"]', 'tinymce-image-upload-test');
        
        // Wait for TinyMCE to load
        console.log('3. Waiting for TinyMCE to load...');
        await page.waitForSelector('.tox-tinymce', { timeout: 15000 });
        await page.waitForTimeout(3000); // Give extra time for full initialization
        
        // 4. Test image button and upload
        console.log('4. Testing image upload functionality');
        
        // Try to find and click the image button
        const imageButtonSelectors = [
            'button[title*="Insert/edit image"]',
            'button[aria-label*="Image"]',
            '.tox-tbtn[title*="image"]',
            'button[data-mce-name="image"]'
        ];
        
        let imageButtonFound = false;
        for (const selector of imageButtonSelectors) {
            const buttonCount = await page.locator(selector).count();
            if (buttonCount > 0) {
                console.log(`   Found image button: ${selector}`);
                await page.click(selector);
                imageButtonFound = true;
                break;
            }
        }
        
        if (!imageButtonFound) {
            console.log('   ✗ Image button not found in TinyMCE toolbar');
            
            // List all available buttons for debugging
            const availableButtons = await page.evaluate(() => {
                const buttons = document.querySelectorAll('.tox-tbtn');
                return Array.from(buttons).map(btn => ({
                    title: btn.title,
                    ariaLabel: btn.getAttribute('aria-label'),
                    text: btn.textContent.trim()
                }));
            });
            
            console.log('   Available toolbar buttons:');
            availableButtons.forEach(btn => {
                console.log(`     - ${btn.title || btn.ariaLabel || btn.text}`);
            });
        } else {
            // Wait for dialog to open
            await page.waitForTimeout(1000);
            
            // Check if image dialog opened
            const dialogExists = await page.locator('.tox-dialog').count();
            if (dialogExists > 0) {
                console.log('   ✓ Image dialog opened');
                
                // Look for upload options
                const uploadOptions = await page.evaluate(() => {
                    const uploadTab = document.querySelector('[role="tab"]:has-text("Upload")');
                    const browseButton = document.querySelector('input[type="file"]');
                    const urlInput = document.querySelector('input[placeholder*="Source"]');
                    
                    return {
                        uploadTab: !!uploadTab,
                        browseButton: !!browseButton,
                        urlInput: !!urlInput
                    };
                });
                
                console.log(`   Upload tab: ${uploadOptions.uploadTab ? '✓' : '✗'}`);
                console.log(`   Browse button: ${uploadOptions.browseButton ? '✓' : '✗'}`);
                console.log(`   URL input: ${uploadOptions.urlInput ? '✓' : '✗'}`);
                
                if (uploadOptions.uploadTab) {
                    // Click upload tab if available
                    await page.click('[role="tab"]:has-text("Upload")');
                    await page.waitForTimeout(500);
                }
                
                if (uploadOptions.browseButton) {
                    // Try to upload the test image
                    console.log('   Attempting to upload test image...');
                    try {
                        await page.setInputFiles('input[type="file"]', testImagePath);
                        await page.waitForTimeout(2000); // Wait for upload
                        
                        // Check if upload was successful
                        const uploadSuccess = await page.evaluate(() => {
                            const urlInput = document.querySelector('input[placeholder*="Source"]');
                            return urlInput ? urlInput.value : '';
                        });
                        
                        if (uploadSuccess) {
                            console.log(`   ✓ Image uploaded successfully: ${uploadSuccess}`);
                            
                            // Insert the image
                            await page.click('button:has-text("Save")');
                            await page.waitForTimeout(1000);
                            
                            console.log('   ✓ Image inserted into editor');
                        } else {
                            console.log('   ✗ Image upload may have failed');
                        }
                    } catch (uploadError) {
                        console.log(`   ✗ Upload error: ${uploadError.message}`);
                    }
                } else {
                    // Test with manual URL
                    if (uploadOptions.urlInput) {
                        console.log('   Testing with manual image URL...');
                        await page.fill('input[placeholder*="Source"]', '/uploads/content/test-image.jpg');
                        await page.click('button:has-text("Save")');
                        await page.waitForTimeout(1000);
                        console.log('   ✓ Manual image URL inserted');
                    }
                }
                
                // Close dialog if still open
                const dialogStillExists = await page.locator('.tox-dialog').count();
                if (dialogStillExists > 0) {
                    await page.press('body', 'Escape');
                }
            } else {
                console.log('   ✗ Image dialog did not open');
            }
        }
        
        // 5. Check if images are visible in the editor
        console.log('5. Checking image display in editor');
        await page.waitForTimeout(2000);
        
        const editorImages = await page.evaluate(() => {
            try {
                const iframe = document.querySelector('.tox-edit-area iframe');
                if (!iframe) return { error: 'No editor iframe found' };
                
                const editorDoc = iframe.contentDocument || iframe.contentWindow.document;
                const images = editorDoc.querySelectorAll('img');
                
                return {
                    success: true,
                    count: images.length,
                    images: Array.from(images).map(img => ({
                        src: img.src,
                        alt: img.alt,
                        width: img.width,
                        height: img.height,
                        loaded: img.complete && img.naturalHeight !== 0
                    }))
                };
            } catch (e) {
                return { error: e.message };
            }
        });
        
        if (editorImages.success) {
            console.log(`   Images in editor: ${editorImages.count}`);
            editorImages.images.forEach((img, index) => {
                console.log(`   Image ${index + 1}:`);
                console.log(`     Src: ${img.src}`);
                console.log(`     Loaded: ${img.loaded ? '✓' : '✗'}`);
                console.log(`     Size: ${img.width}x${img.height}`);
            });
        } else {
            console.log(`   ✗ Error checking editor images: ${editorImages.error}`);
        }
        
        // Clean up test image
        if (fs.existsSync(testImagePath)) {
            fs.unlinkSync(testImagePath);
        }
        
    } catch (error) {
        console.error('Error during test:', error.message);
    }
    
    console.log('\\n==========================================');
    console.log('TinyMCE image upload test completed!');
    console.log('Browser will stay open for 20 seconds for manual inspection...');
    
    await page.waitForTimeout(20000);
    await browser.close();
})();