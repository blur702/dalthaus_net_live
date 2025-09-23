const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({
        headless: false
    });
    
    const context = await browser.newContext();
    const page = await context.newPage();
    
    console.log('Testing TinyMCE image display in content editor...');
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
        
        // 2. Create test content with image
        console.log('2. Creating new content with image in body');
        await page.goto('https://dalthaus.net/admin/content/create', {
            waitUntil: 'networkidle'
        });
        
        // Fill basic details
        await page.fill('input[name="title"]', 'TinyMCE Image Test Article');
        await page.fill('input[name="url_alias"]', 'tinymce-image-test-article');
        
        // Wait for TinyMCE to initialize
        console.log('   Waiting for TinyMCE to initialize...');
        await page.waitForSelector('.tox-tinymce', { timeout: 10000 });
        await page.waitForTimeout(2000); // Give TinyMCE time to fully load
        
        // Check if TinyMCE is loaded
        const tinymceLoaded = await page.evaluate(() => {
            return typeof tinymce !== 'undefined' && tinymce.get('body') !== null;
        });
        
        if (!tinymceLoaded) {
            console.log('   ✗ TinyMCE not loaded properly');
            return;
        }
        
        console.log('   ✓ TinyMCE loaded successfully');
        
        // Insert some content with an image
        console.log('3. Adding content with image to TinyMCE');
        const testContent = `
            <p>This is a test article with an image:</p>
            <p><img src="/uploads/content/test-image.jpg" alt="Test Image" width="300" /></p>
            <p>The image above should display properly in the editor.</p>
        `;
        
        // Set content using TinyMCE API
        await page.evaluate((content) => {
            if (typeof tinymce !== 'undefined' && tinymce.get('body')) {
                tinymce.get('body').setContent(content);
            }
        }, testContent);
        
        // Save the content
        console.log('   Saving content...');
        await page.click('button[name="action"][value="save"]');
        await page.waitForLoadState('networkidle');
        
        // Check if we're on the edit page
        const currentUrl = page.url();
        console.log(`   Current URL: ${currentUrl}`);
        
        if (currentUrl.includes('/admin/content/') && currentUrl.includes('/edit')) {
            console.log('   ✓ Article created and redirected to edit page');
            
            // 4. Check image display in TinyMCE editor
            console.log('4. Checking image display in TinyMCE editor');
            
            // Wait for TinyMCE to reload
            await page.waitForSelector('.tox-tinymce', { timeout: 10000 });
            await page.waitForTimeout(2000);
            
            // Check if images are displayed in the editor
            const imagesInEditor = await page.evaluate(() => {
                const iframe = document.querySelector('.tox-edit-area iframe');
                if (!iframe) return { found: false, error: 'No TinyMCE iframe found' };
                
                try {
                    const doc = iframe.contentDocument || iframe.contentWindow.document;
                    const images = doc.querySelectorAll('img');
                    
                    const imageData = Array.from(images).map(img => ({
                        src: img.src,
                        alt: img.alt,
                        loaded: img.complete && img.naturalHeight !== 0,
                        naturalWidth: img.naturalWidth,
                        naturalHeight: img.naturalHeight
                    }));
                    
                    return {
                        found: true,
                        count: images.length,
                        images: imageData
                    };
                } catch (e) {
                    return { found: false, error: e.message };
                }
            });
            
            console.log('   TinyMCE Image Analysis:');
            if (imagesInEditor.found) {
                console.log(`   Images found in editor: ${imagesInEditor.count}`);
                imagesInEditor.images.forEach((img, index) => {
                    console.log(`   Image ${index + 1}:`);
                    console.log(`     Src: ${img.src}`);
                    console.log(`     Alt: ${img.alt}`);
                    console.log(`     Loaded: ${img.loaded ? '✓' : '✗'}`);
                    console.log(`     Dimensions: ${img.naturalWidth}x${img.naturalHeight}`);
                });
                
                // Check if any images failed to load
                const failedImages = imagesInEditor.images.filter(img => !img.loaded);
                if (failedImages.length > 0) {
                    console.log(`   ! ${failedImages.length} images failed to load`);
                    
                    // Test if the image URLs are accessible
                    for (const img of failedImages) {
                        try {
                            const response = await page.goto(img.src, { waitUntil: 'load' });
                            console.log(`     Testing ${img.src}: ${response.status()}`);
                        } catch (e) {
                            console.log(`     Testing ${img.src}: Error - ${e.message}`);
                        }
                    }
                    
                    // Go back to edit page
                    await page.goto(currentUrl, { waitUntil: 'networkidle' });
                } else {
                    console.log('   ✓ All images loaded successfully in TinyMCE');
                }
            } else {
                console.log(`   ✗ Error accessing TinyMCE content: ${imagesInEditor.error}`);
            }
            
            // 5. Test image upload functionality
            console.log('5. Testing image upload in TinyMCE');
            
            // Try to click the image button in TinyMCE toolbar
            const imageButtonExists = await page.locator('button[title="Insert/edit image"]').count();
            console.log(`   Image button found: ${imageButtonExists > 0 ? '✓' : '✗'}`);
            
            if (imageButtonExists > 0) {
                // Click the image button
                await page.click('button[title="Insert/edit image"]');
                await page.waitForTimeout(1000);
                
                // Check if the image dialog opened
                const dialogExists = await page.locator('.tox-dialog').count();
                console.log(`   Image dialog opened: ${dialogExists > 0 ? '✓' : '✗'}`);
                
                if (dialogExists > 0) {
                    // Check for upload tab
                    const uploadTabExists = await page.locator('text=Upload').count();
                    console.log(`   Upload tab available: ${uploadTabExists > 0 ? '✓' : '✗'}`);
                    
                    // Close the dialog
                    await page.press('body', 'Escape');
                }
            }
            
        } else {
            console.log('   ✗ Article creation may have failed or unexpected redirect');
        }
        
    } catch (error) {
        console.error('Error during test:', error.message);
    }
    
    console.log('\n==========================================');
    console.log('TinyMCE image test completed!');
    console.log('Keeping browser open for 15 seconds...');
    
    await page.waitForTimeout(15000);
    await browser.close();
})();