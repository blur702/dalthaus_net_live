const { test, expect } = require('@playwright/test');

test.describe('Specific Article Modal Test', () => {
  
  test('Test modal functionality on specific article', async ({ page }) => {
    // Test a specific article that might have images
    await page.goto('https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible');
    await page.waitForLoadState('networkidle');
    
    // Wait for any dynamic content to load
    await page.waitForTimeout(2000);
    
    // Check if modal functions exist
    const modalFunctions = await page.evaluate(() => {
      return {
        openImageModal: typeof window.openImageModal === 'function',
        closeImageModal: typeof window.closeImageModal === 'function',
        hasImages: document.querySelectorAll('img').length > 0,
        imageSelectors: {
          contentImages: document.querySelectorAll('.content-text img').length,
          proseImages: document.querySelectorAll('.prose img').length,
          articleImages: document.querySelectorAll('article img').length,
          allImages: document.querySelectorAll('img').length
        }
      };
    });
    
    console.log('Storytelling article modal check:', JSON.stringify(modalFunctions, null, 2));
    
    // Check for all images on the page and their properties
    const imageDetails = await page.evaluate(() => {
      const images = document.querySelectorAll('img');
      const details = [];
      
      images.forEach((img, index) => {
        details.push({
          index: index,
          src: img.src,
          alt: img.alt,
          width: img.width,
          height: img.height,
          className: img.className,
          parentElement: img.parentElement.tagName,
          hasClickHandler: !!img.onclick,
          dataModalEnabled: img.hasAttribute('data-modal-enabled'),
          cursor: window.getComputedStyle(img).cursor
        });
      });
      
      return details;
    });
    
    console.log('Image details:', JSON.stringify(imageDetails, null, 2));
    
    if (imageDetails.length > 0) {
      console.log(`Found ${imageDetails.length} images on the page`);
      
      // Try to click on the first content image if it exists
      const contentImages = imageDetails.filter(img => 
        img.parentElement === 'DIV' || 
        img.className.includes('content') ||
        img.width > 100 // Likely content images are larger
      );
      
      if (contentImages.length > 0) {
        console.log('Testing modal functionality on content image...');
        
        // Click the first content image
        const firstImage = page.locator('img').first();
        await firstImage.click();
        await page.waitForTimeout(1000);
        
        // Check if modal opened
        const modalVisible = await page.locator('.image-modal').isVisible();
        console.log('Modal visible after click:', modalVisible);
        
        if (modalVisible) {
          await page.screenshot({ 
            path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/specific-article-modal-test.png',
            fullPage: true 
          });
          
          // Test closing with Escape
          await page.keyboard.press('Escape');
          await page.waitForTimeout(500);
          
          const modalAfterEscape = await page.locator('.image-modal').isVisible();
          console.log('Modal closed with Escape:', !modalAfterEscape);
        }
      }
    } else {
      console.log('No images found on this article page');
    }
    
    expect(modalFunctions.openImageModal).toBe(true);
    expect(modalFunctions.closeImageModal).toBe(true);
  });
  
  test('Test modal on photography article', async ({ page }) => {
    await page.goto('https://dalthaus.net/article/the-joy-of-getting-it-right-in-the-camera');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    
    const pageContent = await page.evaluate(() => {
      return {
        title: document.title,
        hasImages: document.querySelectorAll('img').length > 0,
        imageCount: document.querySelectorAll('img').length,
        contentText: document.querySelector('.content-text, .prose, article')?.textContent?.substring(0, 200) || 'No content found'
      };
    });
    
    console.log('Photography article content:', JSON.stringify(pageContent, null, 2));
    
    if (pageContent.hasImages) {
      console.log(`Photography article has ${pageContent.imageCount} images`);
      
      // Test modal functionality
      const firstImage = page.locator('img').first();
      if (await firstImage.isVisible()) {
        await firstImage.click();
        await page.waitForTimeout(1000);
        
        const modalVisible = await page.locator('.image-modal').isVisible();
        console.log('Modal opened on photography article:', modalVisible);
        
        if (modalVisible) {
          await page.screenshot({ 
            path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/photography-article-modal-test.png',
            fullPage: true 
          });
          
          // Close modal
          await page.keyboard.press('Escape');
        }
      }
    }
  });
});