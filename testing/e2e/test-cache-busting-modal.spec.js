const { test, expect } = require('@playwright/test');

test.describe('Test Modal with Cache Busting', () => {
  
  test('Test modal functionality with cache busting', async ({ page }) => {
    // Add cache busting parameter
    const timestamp = Date.now();
    await page.goto(`https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible?v=${timestamp}`);
    await page.waitForLoadState('networkidle');
    
    // Check if modal functions exist
    const modalFunctionsCheck = await page.evaluate(() => {
      return {
        openImageModal: typeof window.openImageModal === 'function',
        closeImageModal: typeof window.closeImageModal === 'function',
        addModalToContentImages: typeof window.addModalToContentImages === 'function'
      };
    });
    
    console.log('Cache-busted site modal functions:', modalFunctionsCheck);
    
    if (modalFunctionsCheck.addModalToContentImages) {
      console.log('✅ Modal initialization function is now available with cache busting!');
      
      // Test it with a real image
      await page.evaluate(() => {
        const contentArea = document.querySelector('.content-text');
        if (contentArea) {
          const testImage = document.createElement('img');
          testImage.src = 'https://via.placeholder.com/800x600/0099ff/ffffff?text=Cache+Bust+Test';
          testImage.alt = 'Cache Bust Test Image';
          testImage.style.maxWidth = '100%';
          testImage.style.display = 'block';
          testImage.style.margin = '20px auto';
          
          contentArea.appendChild(testImage);
        }
      });
      
      await page.waitForTimeout(3000);
      
      const imageCheck = await page.evaluate(() => {
        const testImage = document.querySelector('img[src*="Cache+Bust+Test"]');
        if (testImage) {
          return {
            found: true,
            hasModalEnabled: testImage.hasAttribute('data-modal-enabled'),
            cursor: window.getComputedStyle(testImage).cursor,
            width: testImage.width,
            height: testImage.height
          };
        }
        return { found: false };
      });
      
      console.log('Image processing with cache busting:', imageCheck);
      
      if (imageCheck.found && imageCheck.hasModalEnabled) {
        console.log('✅ Modal functionality is working with cache busting!');
        
        const testImage = page.locator('img[src*="Cache+Bust+Test"]').first();
        await testImage.click();
        await page.waitForTimeout(1000);
        
        const modalVisible = await page.locator('.image-modal').isVisible();
        console.log('Modal opened with cache busting:', modalVisible);
        
        if (modalVisible) {
          console.log('🎉 COMPLETE MODAL FUNCTIONALITY IS WORKING!');
          
          await page.screenshot({ 
            path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/cache-bust-modal-success.png',
            fullPage: true 
          });
          
          await page.keyboard.press('Escape');
          await page.waitForTimeout(500);
          
          const modalClosed = !(await page.locator('.image-modal').isVisible());
          console.log('Modal closed successfully:', modalClosed);
        }
      }
    } else {
      console.log('❌ Modal function still not available even with cache busting');
      
      // Check if the page content has been updated
      const pageCheck = await page.evaluate(() => {
        return {
          hasUpdatedCode: document.documentElement.innerHTML.includes('window.addModalToContentImages'),
          scriptTags: document.querySelectorAll('script').length,
          lastScriptContent: Array.from(document.querySelectorAll('script')).slice(-2).map(s => s.innerHTML.substring(0, 100))
        };
      });
      
      console.log('Page content check:', pageCheck);
    }
  });
  
  test('Verify cache headers and force reload', async ({ page }) => {
    // Force a hard reload by clearing cache
    await page.goto('https://dalthaus.net/', { waitUntil: 'networkidle' });
    
    // Navigate with a hard reload equivalent
    await page.evaluate(() => {
      window.location.reload(true);
    });
    
    await page.waitForTimeout(2000);
    
    // Now navigate to the article
    await page.goto('https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible', { 
      waitUntil: 'networkidle'
    });
    
    // Check for updated code
    const codeCheck = await page.evaluate(() => {
      return {
        hasNewModalFunction: document.documentElement.innerHTML.includes('window.addModalToContentImages'),
        hasLogMessage: document.documentElement.innerHTML.includes('Modal functionality added to image'),
        fullScriptContent: Array.from(document.querySelectorAll('script')).map(s => s.innerHTML).join('').includes('addModalToContentImages')
      };
    });
    
    console.log('Hard reload code check:', codeCheck);
    
    if (codeCheck.hasNewModalFunction) {
      console.log('✅ Updated code is now loaded after hard reload');
    } else {
      console.log('❌ Updated code still not present after hard reload');
    }
  });
});