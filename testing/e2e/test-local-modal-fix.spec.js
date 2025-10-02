const { test, expect } = require('@playwright/test');

test.describe('Test Local Modal Fix', () => {
  
  test('Test if modal fix works on local development server', async ({ page }) => {
    // Test on local server first
    await page.goto('http://localhost:8000/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible');
    await page.waitForLoadState('networkidle');
    
    // Check if modal functions exist and are global
    const modalFunctionsCheck = await page.evaluate(() => {
      return {
        openImageModal: typeof window.openImageModal === 'function',
        closeImageModal: typeof window.closeImageModal === 'function',
        addModalToContentImages: typeof window.addModalToContentImages === 'function'
      };
    });
    
    console.log('Local server modal functions:', modalFunctionsCheck);
    expect(modalFunctionsCheck.addModalToContentImages).toBe(true);
    
    // Inject a test image to verify the automatic processing
    await page.evaluate(() => {
      const contentArea = document.querySelector('.content-text');
      if (contentArea) {
        const testImage = document.createElement('img');
        testImage.src = 'https://via.placeholder.com/800x600/009900/ffffff?text=Local+Test+Image';
        testImage.alt = 'Local Test Image';
        testImage.style.maxWidth = '100%';
        testImage.style.display = 'block';
        testImage.style.margin = '20px auto';
        
        contentArea.appendChild(testImage);
      }
    });
    
    // Wait for the automatic modal processing
    await page.waitForTimeout(3000);
    
    // Check if the image automatically got modal functionality
    const automaticProcessing = await page.evaluate(() => {
      const testImage = document.querySelector('img[src*="Local+Test+Image"]');
      if (testImage) {
        return {
          found: true,
          width: testImage.width,
          height: testImage.height,
          naturalWidth: testImage.naturalWidth,
          naturalHeight: testImage.naturalHeight,
          hasModalEnabled: testImage.hasAttribute('data-modal-enabled'),
          cursor: window.getComputedStyle(testImage).cursor,
          hasClickHandler: !!testImage.onclick
        };
      }
      return { found: false };
    });
    
    console.log('Automatic processing result:', automaticProcessing);
    
    if (automaticProcessing.found && automaticProcessing.hasModalEnabled) {
      console.log('✅ Automatic modal processing is working!');
      
      // Test clicking the image
      const testImage = page.locator('img[src*="Local+Test+Image"]').first();
      await testImage.click();
      await page.waitForTimeout(1000);
      
      const modalVisible = await page.locator('.image-modal').isVisible();
      console.log('Modal opened automatically:', modalVisible);
      
      if (modalVisible) {
        console.log('✅ Complete modal functionality is working on local server!');
        
        await page.screenshot({ 
          path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/local-modal-success.png',
          fullPage: true 
        });
        
        // Test closing with Escape
        await page.keyboard.press('Escape');
        await page.waitForTimeout(500);
        
        const modalClosed = !(await page.locator('.image-modal').isVisible());
        console.log('Modal closed with Escape:', modalClosed);
      } else {
        console.log('❌ Modal did not open even with automatic processing');
      }
    } else {
      console.log('❌ Automatic processing did not add modal functionality');
      
      // Try manual function call
      const manualCall = await page.evaluate(() => {
        try {
          window.addModalToContentImages();
          return { success: true };
        } catch (error) {
          return { success: false, error: error.message };
        }
      });
      
      console.log('Manual function call result:', manualCall);
    }
  });
  
  test('Check console output for modal processing', async ({ page }) => {
    const consoleMessages = [];
    
    page.on('console', msg => {
      consoleMessages.push({
        type: msg.type(),
        text: msg.text()
      });
    });
    
    await page.goto('http://localhost:8000/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible');
    await page.waitForLoadState('networkidle');
    
    // Add test image
    await page.evaluate(() => {
      const contentArea = document.querySelector('.content-text');
      if (contentArea) {
        const testImage = document.createElement('img');
        testImage.src = 'https://via.placeholder.com/600x400/cc0000/ffffff?text=Console+Test';
        testImage.alt = 'Console Test Image';
        testImage.style.maxWidth = '100%';
        testImage.style.display = 'block';
        
        contentArea.appendChild(testImage);
      }
    });
    
    await page.waitForTimeout(4000); // Wait longer to see console output
    
    console.log('Console messages during modal processing:');
    consoleMessages.forEach(msg => {
      console.log(`[${msg.type}] ${msg.text}`);
    });
    
    const modalMessages = consoleMessages.filter(msg => 
      msg.text.includes('Modal functionality added') || 
      msg.text.includes('modal') ||
      msg.text.includes('image')
    );
    
    if (modalMessages.length > 0) {
      console.log('✅ Modal processing messages found in console');
    } else {
      console.log('ℹ No specific modal processing messages found');
    }
  });
});