const { chromium } = require('playwright');
const path = require('path');

(async () => {
    const browser = await chromium.launch({
        headless: false
    });
    
    const context = await browser.newContext();
    const page = await context.newPage();
    
    console.log('Testing image upload and editor display...');
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
        
        // 2. Create new content with image
        console.log('2. Creating new article with image');
        await page.goto('https://dalthaus.net/admin/content/create', {
            waitUntil: 'networkidle'
        });
        
        // Fill basic details
        await page.fill('input[name="title"]', 'Test Article with Image');
        await page.fill('input[name="url_alias"]', 'test-article-with-image');
        await page.fill('textarea[name="body"]', 'This is a test article with an image.');
        
        // Create a simple test image (1x1 PNG)
        const testImagePath = path.join(process.cwd(), 'test_image.png');
        const fs = require('fs');
        
        // Create a minimal PNG file (1x1 transparent pixel)
        const pngData = Buffer.from([
            0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A, 0x00, 0x00, 0x00, 0x0D,
            0x49, 0x48, 0x44, 0x52, 0x00, 0x00, 0x00, 0x01, 0x00, 0x00, 0x00, 0x01,
            0x08, 0x06, 0x00, 0x00, 0x00, 0x1F, 0x15, 0xC4, 0x89, 0x00, 0x00, 0x00,
            0x0A, 0x49, 0x44, 0x41, 0x54, 0x78, 0x9C, 0x63, 0x00, 0x01, 0x00, 0x00,
            0x05, 0x00, 0x01, 0x0D, 0x0A, 0x2D, 0xB4, 0x00, 0x00, 0x00, 0x00, 0x49,
            0x45, 0x4E, 0x44, 0xAE, 0x42, 0x60, 0x82
        ]);
        
        fs.writeFileSync(testImagePath, pngData);
        
        // Upload the test image
        console.log('   Uploading test image...');
        await page.setInputFiles('input[name="featured_image"]', testImagePath);
        
        // Submit the form
        console.log('   Saving article...');
        await page.click('button[name="action"][value="save"]');
        await page.waitForLoadState('networkidle');
        
        // Check if we're on the edit page
        const currentUrl = page.url();
        console.log(`   Current URL: ${currentUrl}`);
        
        if (currentUrl.includes('/admin/content/') && currentUrl.includes('/edit')) {
            console.log('   ✓ Article created successfully');
            
            // 3. Check if image is displayed in the editor
            console.log('3. Checking image display in editor');
            
            const imageElements = await page.locator('img[alt="Current featured image"]').count();
            console.log(`   Image elements found: ${imageElements}`);
            
            if (imageElements > 0) {
                const imageSrc = await page.locator('img[alt="Current featured image"]').getAttribute('src');
                console.log(`   ✓ Image found with src: ${imageSrc}`);
                
                // Check if image loads
                const imageLoadedSuccessfully = await page.locator('img[alt="Current featured image"]').evaluate(img => {
                    return img.complete && img.naturalHeight !== 0;
                });
                
                if (imageLoadedSuccessfully) {
                    console.log('   ✓ Image loads successfully in editor');
                } else {
                    console.log('   ✗ Image found but failed to load');
                    
                    // Try to access the image URL directly
                    const response = await page.goto(imageSrc, { waitUntil: 'load' }).catch(e => null);
                    if (response && response.ok()) {
                        console.log('   ✓ Image URL is accessible directly');
                    } else {
                        console.log('   ✗ Image URL returns error when accessed directly');
                    }
                    
                    // Go back to edit page
                    await page.goBack();
                }
            } else {
                console.log('   ✗ No image displayed in editor');
                
                // Check if the alt text shows up instead
                const altText = await page.textContent('body');
                if (altText.includes('Current featured image')) {
                    console.log('   ! Alt text is present but image not loading');
                }
            }
        } else {
            console.log('   ✗ Article creation may have failed');
            
            // Check for error messages
            const errorText = await page.textContent('body');
            if (errorText.includes('error') || errorText.includes('Error')) {
                console.log('   Error messages may be present on page');
            }
        }
        
        // Clean up test image file
        fs.unlinkSync(testImagePath);
        
    } catch (error) {
        console.error('Error during test:', error.message);
    }
    
    console.log('\n==========================================');
    console.log('Image upload test completed!');
    console.log('Keeping browser open for 15 seconds...');
    
    await page.waitForTimeout(15000);
    await browser.close();
})();