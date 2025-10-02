const { test, expect } = require('@playwright/test');

test.describe('Test Photobooks Modal Functionality', () => {
  
  test('Test modal functionality on photobooks page', async ({ page }) => {
    const timestamp = Date.now();
    await page.goto(`https://dalthaus.net/photobooks?v=${timestamp}`);
    await page.waitForLoadState('networkidle');
    
    // Check what's on the photobooks page
    const pageContent = await page.evaluate(() => {
      return {
        title: document.title,
        hasContent: document.querySelector('.content, .prose, article, main')?.textContent?.trim() || 'No content found',
        photobookLinks: document.querySelectorAll('a[href*="/photobook/"]').length,
        images: document.querySelectorAll('img').length
      };
    });
    
    console.log('Photobooks page content:', pageContent);
    
    if (pageContent.photobookLinks > 0) {
      console.log('✅ Found photobook links, testing individual photobook');
      
      const firstPhotobookLink = page.locator('a[href*="/photobook/"]').first();
      await firstPhotobookLink.click();
      await page.waitForLoadState('networkidle');
      
      // Check modal functions on photobook page
      const modalFunctions = await page.evaluate(() => {
        return {
          openImageModal: typeof window.openImageModal === 'function',
          closeImageModal: typeof window.closeImageModal === 'function',
          addModalToContentImages: typeof window.addModalToContentImages === 'function'
        };
      });
      
      console.log('Photobook page modal functions:', modalFunctions);
      
      if (modalFunctions.addModalToContentImages) {
        console.log('✅ Modal functions available on photobook page');
        
        // Add test image to photobook content
        await page.evaluate(() => {
          const contentArea = document.querySelector('.content-text, .prose, article, main');
          if (contentArea) {
            const testImage = document.createElement('img');
            testImage.src = 'https://picsum.photos/800/600?random=photobook';
            testImage.alt = 'Photobook Test Image';
            testImage.style.maxWidth = '100%';
            testImage.style.display = 'block';
            testImage.style.margin = '20px auto';
            
            contentArea.appendChild(testImage);
          }
        });
        
        await page.waitForTimeout(3000);
        
        const imageCheck = await page.evaluate(() => {
          const testImage = document.querySelector('img[src*="photobook"]');
          return testImage ? {
            found: true,
            hasModalEnabled: testImage.hasAttribute('data-modal-enabled'),
            cursor: window.getComputedStyle(testImage).cursor,
            width: testImage.width,
            height: testImage.height
          } : { found: false };
        });
        
        console.log('Photobook image modal check:', imageCheck);
        
        if (imageCheck.found && imageCheck.hasModalEnabled) {
          console.log('✅ Modal functionality working on photobook page!');
          
          const testImage = page.locator('img[src*="photobook"]').first();
          await testImage.click();
          await page.waitForTimeout(1000);
          
          const modalVisible = await page.locator('.image-modal').isVisible();
          console.log('Photobook modal opened:', modalVisible);
          
          if (modalVisible) {
            console.log('🎉 PHOTOBOOK MODAL FUNCTIONALITY CONFIRMED!');
            
            await page.screenshot({ 
              path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/photobook-modal-success.png',
              fullPage: true 
            });
            
            await page.keyboard.press('Escape');
          }
        }
      }
    } else {
      console.log('ℹ No photobook links found, testing modal on photobooks listing page');
      
      // Check modal functions on listing page
      const modalFunctions = await page.evaluate(() => {
        return {
          openImageModal: typeof window.openImageModal === 'function',
          closeImageModal: typeof window.closeImageModal === 'function',
          addModalToContentImages: typeof window.addModalToContentImages === 'function'
        };
      });
      
      console.log('Photobooks listing modal functions:', modalFunctions);
      
      if (modalFunctions.addModalToContentImages) {
        console.log('✅ Modal functions available on photobooks listing');
        
        // Add test image to the page
        await page.evaluate(() => {
          const contentArea = document.querySelector('main, .content, body');
          if (contentArea) {
            const testImage = document.createElement('img');
            testImage.src = 'https://picsum.photos/700/500?random=listing';
            testImage.alt = 'Photobooks Listing Test Image';
            testImage.style.maxWidth = '100%';
            testImage.style.display = 'block';
            testImage.style.margin = '20px auto';
            
            contentArea.appendChild(testImage);
          }
        });
        
        await page.waitForTimeout(3000);
        
        const imageCheck = await page.evaluate(() => {
          const testImage = document.querySelector('img[src*="listing"]');
          return testImage ? {
            found: true,
            hasModalEnabled: testImage.hasAttribute('data-modal-enabled'),
            cursor: window.getComputedStyle(testImage).cursor
          } : { found: false };
        });
        
        console.log('Photobooks listing image check:', imageCheck);
        
        if (imageCheck.found && imageCheck.hasModalEnabled) {
          console.log('✅ Modal works on photobooks listing page too!');
          
          const testImage = page.locator('img[src*="listing"]').first();
          await testImage.click();
          await page.waitForTimeout(1000);
          
          const modalVisible = await page.locator('.image-modal').isVisible();
          console.log('Listing page modal opened:', modalVisible);
          
          if (modalVisible) {
            console.log('🎉 MODAL WORKS ON PHOTOBOOKS LISTING PAGE!');
            await page.keyboard.press('Escape');
          }
        }
      }
    }
  });
});