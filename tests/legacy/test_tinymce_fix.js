const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({
        headless: false
    });
    
    const context = await browser.newContext();
    const page = await context.newPage();
    
    console.log('Testing TinyMCE image display fix...');
    console.log('==========================================');
    
    try {
        // 1. Login
        console.log('\n1. Logging into admin');
        await page.goto('https://dalthaus.net/admin/login', {
            waitUntil: 'networkidle'
        });
        
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        
        // 2. Go to content list and edit existing content
        console.log('2. Opening existing content for editing');
        await page.goto('https://dalthaus.net/admin/content', {
            waitUntil: 'networkidle'
        });
        
        // Find and click the first edit link
        const editLinks = await page.locator('a:has-text("Edit")').all();
        if (editLinks.length > 0) {
            await editLinks[0].click();
            await page.waitForLoadState('networkidle');
            
            const currentUrl = page.url();
            console.log(`   Editing content at: ${currentUrl}`);
            
            // 3. Check TinyMCE configuration
            console.log('3. Checking TinyMCE configuration');
            
            // Wait for TinyMCE to load
            await page.waitForSelector('.tox-tinymce', { timeout: 10000 });
            await page.waitForTimeout(2000);
            
            // Check TinyMCE configuration
            const configCheck = await page.evaluate(() => {
                if (typeof tinymce === 'undefined') {
                    return { loaded: false, error: 'TinyMCE not loaded' };
                }
                
                const editor = tinymce.get('body');
                if (!editor) {
                    return { loaded: false, error: 'No body editor found' };
                }
                
                const settings = editor.settings;
                return {
                    loaded: true,
                    relative_urls: settings.relative_urls,
                    remove_script_host: settings.remove_script_host,
                    document_base_url: settings.document_base_url,
                    images_upload_url: settings.images_upload_url,
                    verify_html: settings.verify_html,
                    image_advtab: settings.image_advtab
                };
            });
            
            console.log('   TinyMCE Configuration:');
            if (configCheck.loaded) {
                console.log(`     relative_urls: ${configCheck.relative_urls}`);
                console.log(`     remove_script_host: ${configCheck.remove_script_host}`);
                console.log(`     document_base_url: ${configCheck.document_base_url}`);
                console.log(`     images_upload_url: ${configCheck.images_upload_url}`);
                console.log(`     verify_html: ${configCheck.verify_html}`);
                console.log(`     image_advtab: ${configCheck.image_advtab}`);
                
                // Check for our fixes
                if (configCheck.relative_urls === false) {
                    console.log('     ✓ relative_urls set to false (images use absolute URLs)');
                } else {
                    console.log('     ! relative_urls not properly configured');
                }
                
                if (configCheck.document_base_url) {
                    console.log('     ✓ document_base_url is set');
                } else {
                    console.log('     ! document_base_url not set');
                }
                
                if (configCheck.verify_html === false) {
                    console.log('     ✓ verify_html disabled (prevents image filtering)');
                } else {
                    console.log('     ! verify_html not properly configured');
                }
            } else {
                console.log(`     ✗ Error: ${configCheck.error}`);
            }
            
            // 4. Test image insertion
            console.log('4. Testing image insertion and display');
            
            // Add some test content with an image
            const testImageContent = `
                <p>Testing image display in TinyMCE:</p>
                <p><img src="/uploads/content/test-image.jpg" alt="Test Image" width="300" style="max-width: 100%; height: auto;" /></p>
                <p>This image should display properly with our new configuration.</p>
            `;
            
            // Set content using TinyMCE API
            await page.evaluate((content) => {
                if (typeof tinymce !== 'undefined' && tinymce.get('body')) {
                    tinymce.get('body').setContent(content);
                }
            }, testImageContent);
            
            // Give TinyMCE time to process the content
            await page.waitForTimeout(2000);
            
            // Check if images are properly displayed in editor
            const imageDisplayCheck = await page.evaluate(() => {
                if (typeof tinymce === 'undefined') return { success: false, error: 'TinyMCE not available' };
                
                const editor = tinymce.get('body');
                if (!editor) return { success: false, error: 'Editor not found' };
                
                try {
                    const editorDocument = editor.getDoc();
                    const images = editorDocument.querySelectorAll('img');
                    
                    const imageData = Array.from(images).map(img => ({
                        src: img.src,
                        alt: img.alt,
                        naturalWidth: img.naturalWidth,
                        naturalHeight: img.naturalHeight,
                        complete: img.complete,
                        displayed: img.style.display !== 'none' && img.offsetWidth > 0
                    }));
                    
                    return {
                        success: true,
                        imageCount: images.length,
                        images: imageData
                    };
                } catch (e) {
                    return { success: false, error: e.message };
                }
            });
            
            if (imageDisplayCheck.success) {
                console.log(`   Found ${imageDisplayCheck.imageCount} images in editor`);
                imageDisplayCheck.images.forEach((img, index) => {
                    console.log(`   Image ${index + 1}:`);
                    console.log(`     Src: ${img.src}`);
                    console.log(`     Alt: ${img.alt}`);
                    console.log(`     Complete: ${img.complete ? '✓' : '✗'}`);
                    console.log(`     Displayed: ${img.displayed ? '✓' : '✗'}`);
                    console.log(`     Dimensions: ${img.naturalWidth}x${img.naturalHeight}`);
                });
                
                if (imageDisplayCheck.images.some(img => img.displayed)) {
                    console.log('   ✓ Images are displaying in TinyMCE editor');
                } else {
                    console.log('   ! Images are present but may not be visible');
                }
            } else {
                console.log(`   ✗ Error checking images: ${imageDisplayCheck.error}`);
            }
            
            // 5. Test image upload functionality
            console.log('5. Testing image upload button');
            
            const imageButtonCheck = await page.evaluate(() => {
                const imageButton = document.querySelector('button[title="Insert/edit image"], button[aria-label*="Image"], .tox-tbtn[title*="image"]');
                return {
                    found: !!imageButton,
                    enabled: imageButton ? !imageButton.disabled : false,
                    title: imageButton ? imageButton.title || imageButton.getAttribute('aria-label') : null
                };
            });
            
            if (imageButtonCheck.found) {
                console.log(`   ✓ Image button found: "${imageButtonCheck.title}"`);
                console.log(`   Button enabled: ${imageButtonCheck.enabled ? '✓' : '✗'}`);
            } else {
                console.log('   ✗ Image button not found in toolbar');
            }
            
        } else {
            console.log('   No content available to edit');
        }
        
    } catch (error) {
        console.error('Error during test:', error.message);
    }
    
    console.log('\n==========================================');
    console.log('TinyMCE fix test completed!');
    console.log('Keeping browser open for 15 seconds for manual inspection...');
    
    await page.waitForTimeout(15000);
    await browser.close();
})();