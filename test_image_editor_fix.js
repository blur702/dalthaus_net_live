const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({
        headless: false // Show browser for verification
    });
    
    const context = await browser.newContext();
    const page = await context.newPage();
    
    console.log('Testing image display fix in content editor...');
    console.log('==========================================');
    
    try {
        // 1. Login to admin
        console.log('\n1. Logging into admin');
        await page.goto('https://dalthaus.net/admin/login', {
            waitUntil: 'networkidle'
        });
        
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        
        // 2. Go to content list to see existing content
        console.log('2. Checking existing content');
        await page.goto('https://dalthaus.net/admin/content', {
            waitUntil: 'networkidle'
        });
        
        // Look for edit links
        const editLinks = await page.locator('a:has-text("Edit")').all();
        console.log(`   Found ${editLinks.length} content items to edit`);
        
        if (editLinks.length > 0) {
            // 3. Test editing the first piece of content
            console.log('3. Testing image display in editor');
            await editLinks[0].click();
            await page.waitForLoadState('networkidle');
            
            const currentUrl = page.url();
            console.log(`   Opened: ${currentUrl}`);
            
            // Check for existing images
            const featuredImages = await page.locator('img[alt="Current featured image"]').count();
            const teaserImages = await page.locator('img[alt="Current teaser image"]').count();
            
            console.log(`   Featured images found: ${featuredImages}`);
            console.log(`   Teaser images found: ${teaserImages}`);
            
            // Check for error messages (our new feature)
            const imageErrors = await page.locator('div:has-text("Image not found")').count();
            console.log(`   Image error messages: ${imageErrors}`);
            
            if (featuredImages > 0) {
                console.log('   ✓ Featured image is displaying in editor');
                
                // Check if the image loads successfully
                const imageLoaded = await page.locator('img[alt="Current featured image"]').evaluate(img => {
                    return img.complete && img.naturalHeight !== 0;
                });
                
                if (imageLoaded) {
                    console.log('   ✓ Featured image loads successfully');
                } else {
                    console.log('   ! Featured image element present but not loading');
                }
                
                // Get the image src to check the path
                const imageSrc = await page.locator('img[alt="Current featured image"]').getAttribute('src');
                console.log(`   Image path: ${imageSrc}`);
            } else if (imageErrors > 0) {
                console.log('   ! Image error messages are showing (paths may be incorrect)');
                
                // Get the error message text
                const errorText = await page.locator('div:has-text("Image not found")').first().textContent();
                console.log(`   Error: ${errorText}`);
            } else {
                console.log('   - No images found in this content item');
            }
            
            // Check teaser images for photobooks
            if (teaserImages > 0) {
                console.log('   ✓ Teaser image is displaying in editor');
                
                const teaserImageLoaded = await page.locator('img[alt="Current teaser image"]').evaluate(img => {
                    return img.complete && img.naturalHeight !== 0;
                });
                
                if (teaserImageLoaded) {
                    console.log('   ✓ Teaser image loads successfully');
                } else {
                    console.log('   ! Teaser image element present but not loading');
                }
            }
            
        } else {
            console.log('   No content found to test with');
        }
        
        // 4. If no images found, let's check if we can create test content
        if (editLinks.length === 0 || await page.locator('img[alt*="Current"]').count() === 0) {
            console.log('4. Testing by creating new content');
            await page.goto('https://dalthaus.net/admin/content/create', {
                waitUntil: 'networkidle'
            });
            
            console.log('   Opened content creation form');
            console.log('   (This would require manual image upload to fully test)');
        }
        
        console.log('\n==========================================');
        console.log('Image editor test completed!');
        
    } catch (error) {
        console.error('Error during test:', error.message);
    }
    
    console.log('\nKeeping browser open for 15 seconds for manual inspection...');
    await page.waitForTimeout(15000);
    
    await browser.close();
})();