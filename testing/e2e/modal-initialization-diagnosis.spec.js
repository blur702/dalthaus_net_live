const { test, expect } = require('@playwright/test');

test.describe('Modal Initialization Diagnosis', () => {
  
  test('Diagnose why modal initialization is not working', async ({ page }) => {
    await page.goto('https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible');
    await page.waitForLoadState('networkidle');
    
    // Inject a test image with proper dimensions
    await page.evaluate(() => {
      const contentArea = document.querySelector('.content-text, .prose, article, main');
      if (contentArea) {
        const testImage = document.createElement('img');
        testImage.src = 'https://via.placeholder.com/600x400/0066cc/ffffff?text=Test+Image';
        testImage.alt = 'Test Image';
        testImage.width = 600;
        testImage.height = 400;
        testImage.style.maxWidth = '100%';
        testImage.style.height = 'auto';
        testImage.style.margin = '10px';
        testImage.style.display = 'block';
        
        contentArea.appendChild(testImage);
      }
    });
    
    // Wait for image to load
    await page.waitForTimeout(2000);
    
    // Check if addModalToContentImages function exists and can be called
    const functionCheck = await page.evaluate(() => {
      return {
        addModalToContentImagesExists: typeof window.addModalToContentImages === 'function',
        functionIsGlobal: typeof addModalToContentImages === 'function'
      };
    });
    
    console.log('Function availability:', functionCheck);
    
    // Manually call the function to see if it works
    const manualCall = await page.evaluate(() => {
      try {
        if (typeof addModalToContentImages === 'function') {
          addModalToContentImages();
          return { success: true, error: null };
        } else {
          return { success: false, error: 'Function not found' };
        }
      } catch (error) {
        return { success: false, error: error.message };
      }
    });
    
    console.log('Manual function call result:', manualCall);
    
    // Wait a moment for the function to process
    await page.waitForTimeout(1000);
    
    // Check if images now have modal functionality
    const afterManualCall = await page.evaluate(() => {
      const images = document.querySelectorAll('img');
      const results = [];
      
      images.forEach((img, index) => {
        const isTestImage = img.src.includes('placeholder');
        results.push({
          index: index,
          isTestImage: isTestImage,
          width: img.width,
          height: img.height,
          naturalWidth: img.naturalWidth,
          naturalHeight: img.naturalHeight,
          complete: img.complete,
          cursor: window.getComputedStyle(img).cursor,
          hasModalEnabled: img.hasAttribute('data-modal-enabled'),
          hasClickHandler: !!img.onclick || !!img.addEventListener,
          classList: Array.from(img.classList)
        });
      });
      
      return results;
    });
    
    console.log('After manual call - Image states:', JSON.stringify(afterManualCall, null, 2));
    
    // Try to click the test image if it has modal functionality
    const testImageWithModal = afterManualCall.find(img => img.isTestImage && img.hasModalEnabled);
    
    if (testImageWithModal) {
      console.log('✅ Found test image with modal functionality, testing click...');
      
      const testImage = page.locator('img[src*="placeholder"]').first();
      await testImage.click();
      await page.waitForTimeout(1000);
      
      const modalVisible = await page.locator('.image-modal').isVisible();
      console.log('Modal opened after click:', modalVisible);
      
      if (modalVisible) {
        console.log('✅ Modal functionality is working correctly!');
        
        await page.screenshot({ 
          path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/modal-diagnosis-success.png',
          fullPage: true 
        });
        
        // Close modal
        await page.keyboard.press('Escape');
        await page.waitForTimeout(500);
        
        const modalClosed = !(await page.locator('.image-modal').isVisible());
        console.log('Modal closed with Escape:', modalClosed);
      } else {
        console.log('❌ Modal did not open even after manual initialization');
        
        // Debug the click event
        const debugClick = await page.evaluate(() => {
          const img = document.querySelector('img[src*="placeholder"]');
          if (img) {
            const event = new MouseEvent('click', { bubbles: true });
            img.dispatchEvent(event);
            return {
              hasClickListeners: img.onclick ? 'onclick handler' : 'no onclick',
              eventDispatched: true
            };
          }
          return { error: 'Image not found' };
        });
        
        console.log('Debug click result:', debugClick);
      }
    } else {
      console.log('❌ Test image did not get modal functionality even after manual call');
      
      // Debug why the function didn't work
      const debugInfo = await page.evaluate(() => {
        const selectors = [
          '.content-text img',
          '.prose img', 
          'article img',
          '.photobook-content img',
          '.article-content img'
        ];
        
        const debug = {
          selectorsChecked: [],
          imagesFound: 0,
          testImageFound: false,
          testImageParent: null
        };
        
        selectors.forEach(selector => {
          const images = document.querySelectorAll(selector);
          debug.selectorsChecked.push({
            selector: selector,
            count: images.length,
            imageDetails: Array.from(images).map(img => ({
              src: img.src.includes('placeholder') ? 'test-image' : 'other',
              width: img.width,
              height: img.height,
              complete: img.complete
            }))
          });
          
          images.forEach(img => {
            if (img.src.includes('placeholder')) {
              debug.testImageFound = true;
              debug.testImageParent = img.parentElement.tagName + '.' + img.parentElement.className;
            }
          });
          
          debug.imagesFound += images.length;
        });
        
        return debug;
      });
      
      console.log('Debug selector info:', JSON.stringify(debugInfo, null, 2));
    }
  });
  
  test('Check if DOMContentLoaded event properly triggers modal initialization', async ({ page }) => {
    await page.goto('https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible');
    
    // Check if DOMContentLoaded has fired and what happened
    const domContentLoadedCheck = await page.evaluate(() => {
      return {
        readyState: document.readyState,
        domContentLoadedFired: document.readyState === 'complete' || document.readyState === 'interactive'
      };
    });
    
    console.log('DOM state:', domContentLoadedCheck);
    
    // Add a test image and then trigger DOMContentLoaded simulation
    await page.evaluate(() => {
      const contentArea = document.querySelector('.content-text, .prose, article, main');
      if (contentArea) {
        const testImage = document.createElement('img');
        testImage.src = 'https://via.placeholder.com/800x600/cc6600/ffffff?text=DOMContentLoaded+Test';
        testImage.alt = 'DOMContentLoaded Test Image';
        testImage.onload = function() {
          console.log('Test image loaded, dimensions:', this.width, 'x', this.height);
        };
        
        contentArea.appendChild(testImage);
      }
    });
    
    await page.waitForTimeout(3000);
    
    // Manually trigger the addModalToContentImages function
    const triggerResult = await page.evaluate(() => {
      try {
        if (typeof addModalToContentImages === 'function') {
          addModalToContentImages();
          return { success: true };
        }
        return { success: false, reason: 'Function not available' };
      } catch (error) {
        return { success: false, reason: error.message };
      }
    });
    
    console.log('Manual trigger result:', triggerResult);
    
    await page.waitForTimeout(1000);
    
    // Check the final state
    const finalCheck = await page.evaluate(() => {
      const testImage = document.querySelector('img[src*="DOMContentLoaded"]');
      if (testImage) {
        return {
          found: true,
          width: testImage.width,
          height: testImage.height,
          naturalWidth: testImage.naturalWidth,
          naturalHeight: testImage.naturalHeight,
          complete: testImage.complete,
          hasModalEnabled: testImage.hasAttribute('data-modal-enabled'),
          cursor: window.getComputedStyle(testImage).cursor,
          hasClickHandler: !!testImage.onclick
        };
      }
      return { found: false };
    });
    
    console.log('Final image state:', finalCheck);
    
    if (finalCheck.found && finalCheck.hasModalEnabled) {
      console.log('✅ DOMContentLoaded trigger worked correctly');
    } else if (finalCheck.found) {
      console.log('⚠️ Image was found but modal functionality was not added');
      console.log('Image dimensions when function ran:', finalCheck.width, 'x', finalCheck.height);
      console.log('Natural dimensions:', finalCheck.naturalWidth, 'x', finalCheck.naturalHeight);
    } else {
      console.log('❌ Test image was not found');
    }
  });
});