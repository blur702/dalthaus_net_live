const { test, expect } = require('@playwright/test');

test.describe('Test Live Modal Fix', () => {
  
  test('Test if modal fix is working on live site', async ({ page }) => {
    await page.goto('https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible');
    await page.waitForLoadState('networkidle');
    
    // Check if modal functions exist and are global now
    const modalFunctionsCheck = await page.evaluate(() => {
      return {
        openImageModal: typeof window.openImageModal === 'function',
        closeImageModal: typeof window.closeImageModal === 'function',
        addModalToContentImages: typeof window.addModalToContentImages === 'function'
      };
    });
    
    console.log('Live site modal functions:', modalFunctionsCheck);
    
    if (modalFunctionsCheck.addModalToContentImages) {
      console.log('✅ Modal initialization function is now globally available!');
    } else {
      console.log('❌ Modal initialization function is still not available');
    }
    
    // Inject a test image with proper content structure
    await page.evaluate(() => {
      const contentArea = document.querySelector('.content-text');
      if (contentArea) {
        const testImage = document.createElement('img');
        testImage.src = 'https://via.placeholder.com/800x600/ff6600/ffffff?text=Live+Site+Test+Image';
        testImage.alt = 'Live Site Test Image';
        testImage.style.maxWidth = '100%';
        testImage.style.display = 'block';
        testImage.style.margin = '20px auto';
        
        contentArea.appendChild(testImage);
      }
    });
    
    // Wait for automatic modal processing to run
    await page.waitForTimeout(3000);
    
    // Check if the image automatically got modal functionality
    const automaticProcessing = await page.evaluate(() => {
      const testImage = document.querySelector('img[src*="Live+Site+Test+Image"]');
      if (testImage) {
        return {
          found: true,
          width: testImage.width,
          height: testImage.height,
          naturalWidth: testImage.naturalWidth,
          naturalHeight: testImage.naturalHeight,
          hasModalEnabled: testImage.hasAttribute('data-modal-enabled'),
          cursor: window.getComputedStyle(testImage).cursor,
          classList: Array.from(testImage.classList)
        };
      }
      return { found: false };
    });
    
    console.log('Live site automatic processing result:', automaticProcessing);
    
    if (automaticProcessing.found && automaticProcessing.hasModalEnabled) {
      console.log('✅ Automatic modal processing is working on live site!');
      
      // Test clicking the image
      const testImage = page.locator('img[src*="Live+Site+Test+Image"]').first();
      await testImage.click();
      await page.waitForTimeout(1000);
      
      const modalVisible = await page.locator('.image-modal').isVisible();
      console.log('Modal opened on live site:', modalVisible);
      
      if (modalVisible) {
        console.log('✅ Complete modal functionality is working on live site!');
        
        await page.screenshot({ 
          path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/live-modal-success.png',
          fullPage: true 
        });
        
        // Test closing with Escape
        await page.keyboard.press('Escape');
        await page.waitForTimeout(500);
        
        const modalClosed = !(await page.locator('.image-modal').isVisible());
        console.log('Modal closed with Escape on live site:', modalClosed);
        
        // Test closing with close button
        await testImage.click();
        await page.waitForTimeout(500);
        
        const closeButton = page.locator('.modal-close');
        if (await closeButton.isVisible()) {
          await closeButton.click();
          await page.waitForTimeout(500);
          
          const modalClosedByButton = !(await page.locator('.image-modal').isVisible());
          console.log('Modal closed with close button on live site:', modalClosedByButton);
        }
        
        expect(modalVisible).toBe(true);
      } else {
        console.log('❌ Modal did not open even with automatic processing on live site');
        
        // Debug the click
        const debugClick = await page.evaluate(() => {
          const img = document.querySelector('img[src*="Live+Site+Test+Image"]');
          if (img) {
            return {
              hasClickHandler: !!img.onclick,
              eventListeners: img.getEventListeners ? img.getEventListeners() : 'getEventListeners not available',
              hasModalAttribute: img.hasAttribute('data-modal-enabled')
            };
          }
          return { error: 'Image not found' };
        });
        
        console.log('Debug click info:', debugClick);
      }
    } else {
      console.log('❌ Automatic processing did not add modal functionality on live site');
      
      // Try manual function call
      if (modalFunctionsCheck.addModalToContentImages) {
        const manualCall = await page.evaluate(() => {
          try {
            window.addModalToContentImages();
            return { success: true };
          } catch (error) {
            return { success: false, error: error.message };
          }
        });
        
        console.log('Manual function call result on live site:', manualCall);
        
        // Check again after manual call
        await page.waitForTimeout(1000);
        
        const afterManualCall = await page.evaluate(() => {
          const testImage = document.querySelector('img[src*="Live+Site+Test+Image"]');
          if (testImage) {
            return {
              hasModalEnabled: testImage.hasAttribute('data-modal-enabled'),
              cursor: window.getComputedStyle(testImage).cursor
            };
          }
          return { notFound: true };
        });
        
        console.log('After manual call on live site:', afterManualCall);
      }
    }
  });
  
  test('Check for console output and debugging info on live site', async ({ page }) => {
    const consoleMessages = [];
    
    page.on('console', msg => {
      consoleMessages.push({
        type: msg.type(),
        text: msg.text()
      });
    });
    
    await page.goto('https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible');
    await page.waitForLoadState('networkidle');
    
    // Add test image and wait for processing
    await page.evaluate(() => {
      const contentArea = document.querySelector('.content-text');
      if (contentArea) {
        const testImage = document.createElement('img');
        testImage.src = 'https://via.placeholder.com/700x500/00cc00/ffffff?text=Console+Debug+Test';
        testImage.alt = 'Console Debug Test Image';
        testImage.style.maxWidth = '100%';
        testImage.style.display = 'block';
        
        contentArea.appendChild(testImage);
      }
    });
    
    await page.waitForTimeout(4000);
    
    console.log('Console messages from live site:');
    consoleMessages.forEach(msg => {
      console.log(`[${msg.type}] ${msg.text}`);
    });
    
    const modalMessages = consoleMessages.filter(msg => 
      msg.text.includes('Modal functionality added') || 
      msg.text.toLowerCase().includes('modal')
    );
    
    if (modalMessages.length > 0) {
      console.log('✅ Modal processing console messages found on live site:');
      modalMessages.forEach(msg => {
        console.log(`  [${msg.type}] ${msg.text}`);
      });
    } else {
      console.log('ℹ No modal processing console messages found on live site');
    }
    
    // Check for any errors
    const errorMessages = consoleMessages.filter(msg => msg.type === 'error');
    if (errorMessages.length > 0) {
      console.log('⚠️ Console errors found:');
      errorMessages.forEach(msg => {
        console.log(`  [ERROR] ${msg.text}`);
      });
    }
  });
});