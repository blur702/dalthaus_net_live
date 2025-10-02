const { test, expect } = require('@playwright/test');

test.describe('Diagnose Image Loading for Modal', () => {
  
  test('Diagnose why images are not getting proper dimensions', async ({ page }) => {
    const timestamp = Date.now();
    await page.goto(`https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible?v=${timestamp}`);
    await page.waitForLoadState('networkidle');
    
    // Check if the function is available
    const functionCheck = await page.evaluate(() => {
      return typeof window.addModalToContentImages === 'function';
    });
    
    console.log('Function available:', functionCheck);
    
    if (functionCheck) {
      // Create a proper test image that will load correctly
      await page.evaluate(() => {
        const contentArea = document.querySelector('.content-text');
        if (contentArea) {
          const testImage = document.createElement('img');
          testImage.src = 'https://picsum.photos/600/400';
          testImage.alt = 'Picsum Test Image';
          testImage.style.maxWidth = '100%';
          testImage.style.display = 'block';
          testImage.style.margin = '20px auto';
          
          // Add onload handler to debug
          testImage.onload = function() {
            console.log('Test image loaded:', this.naturalWidth, 'x', this.naturalHeight);
          };
          
          contentArea.appendChild(testImage);
        }
      });
      
      // Wait for image to load
      await page.waitForTimeout(5000);
      
      // Check image properties after loading
      const imageProperties = await page.evaluate(() => {
        const testImage = document.querySelector('img[src*="picsum"]');
        if (testImage) {
          return {
            found: true,
            src: testImage.src,
            width: testImage.width,
            height: testImage.height,
            naturalWidth: testImage.naturalWidth,
            naturalHeight: testImage.naturalHeight,
            complete: testImage.complete,
            hasModalEnabled: testImage.hasAttribute('data-modal-enabled'),
            cursor: window.getComputedStyle(testImage).cursor
          };
        }
        return { found: false };
      });
      
      console.log('Image properties after loading:', imageProperties);
      
      if (imageProperties.found && imageProperties.naturalWidth > 100) {
        console.log('✅ Image loaded properly with correct dimensions');
        
        if (imageProperties.hasModalEnabled) {
          console.log('✅ Modal functionality was automatically added!');
          
          // Test clicking the image
          const testImage = page.locator('img[src*="picsum"]').first();
          await testImage.click();
          await page.waitForTimeout(1000);
          
          const modalVisible = await page.locator('.image-modal').isVisible();
          console.log('Modal opened from properly loaded image:', modalVisible);
          
          if (modalVisible) {
            console.log('🎉 MODAL FUNCTIONALITY IS FULLY WORKING!');
            
            await page.screenshot({ 
              path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/final-modal-success.png',
              fullPage: true 
            });
            
            // Test closing with Escape
            await page.keyboard.press('Escape');
            await page.waitForTimeout(500);
            
            console.log('Modal closed successfully');
          }
        } else {
          console.log('❌ Modal functionality was not automatically added to loaded image');
          
          // Try manual function call
          const manualResult = await page.evaluate(() => {
            try {
              window.addModalToContentImages();
              return { success: true };
            } catch (error) {
              return { success: false, error: error.message };
            }
          });
          
          console.log('Manual function call result:', manualResult);
          
          // Check again after manual call
          await page.waitForTimeout(1000);
          
          const afterManual = await page.evaluate(() => {
            const testImage = document.querySelector('img[src*="picsum"]');
            return testImage ? {
              hasModalEnabled: testImage.hasAttribute('data-modal-enabled'),
              cursor: window.getComputedStyle(testImage).cursor
            } : { notFound: true };
          });
          
          console.log('After manual call:', afterManual);
          
          if (afterManual.hasModalEnabled) {
            console.log('✅ Manual call worked! Testing click...');
            
            const testImage = page.locator('img[src*="picsum"]').first();
            await testImage.click();
            await page.waitForTimeout(1000);
            
            const modalVisible = await page.locator('.image-modal').isVisible();
            console.log('Modal opened after manual call:', modalVisible);
            
            if (modalVisible) {
              console.log('🎉 MODAL WORKS WITH MANUAL INITIALIZATION!');
              await page.keyboard.press('Escape');
            }
          }
        }
      } else {
        console.log('❌ Image did not load with proper dimensions');
      }
    }
  });
  
  test('Test with different image sources and check console output', async ({ page }) => {
    const consoleMessages = [];
    
    page.on('console', msg => {
      consoleMessages.push({
        type: msg.type(),
        text: msg.text()
      });
    });
    
    const timestamp = Date.now();
    await page.goto(`https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible?v=${timestamp}`);
    await page.waitForLoadState('networkidle');
    
    // Add multiple test images with different sources
    await page.evaluate(() => {
      const contentArea = document.querySelector('.content-text');
      if (contentArea) {
        // Image 1: Picsum
        const img1 = document.createElement('img');
        img1.src = 'https://picsum.photos/600/400?random=1';
        img1.alt = 'Picsum Image 1';
        img1.style.maxWidth = '100%';
        img1.style.display = 'block';
        img1.style.margin = '10px auto';
        
        // Image 2: Different source
        const img2 = document.createElement('img');
        img2.src = 'https://via.placeholder.com/700x500/ff6600/ffffff?text=Test+Image+2';
        img2.alt = 'Placeholder Image 2';
        img2.style.maxWidth = '100%';
        img2.style.display = 'block';
        img2.style.margin = '10px auto';
        
        contentArea.appendChild(img1);
        contentArea.appendChild(img2);
      }
    });
    
    // Wait for images to load and processing to happen
    await page.waitForTimeout(6000);
    
    console.log('Console messages during processing:');
    consoleMessages.forEach(msg => {
      console.log(`[${msg.type}] ${msg.text}`);
    });
    
    // Check final state of images
    const finalCheck = await page.evaluate(() => {
      const images = document.querySelectorAll('img[src*="picsum"], img[src*="placeholder"]');
      const results = [];
      
      images.forEach((img, index) => {
        results.push({
          index: index,
          src: img.src.includes('picsum') ? 'picsum' : 'placeholder',
          width: img.width,
          height: img.height,
          naturalWidth: img.naturalWidth,
          naturalHeight: img.naturalHeight,
          complete: img.complete,
          hasModalEnabled: img.hasAttribute('data-modal-enabled'),
          cursor: window.getComputedStyle(img).cursor
        });
      });
      
      return results;
    });
    
    console.log('Final image states:', JSON.stringify(finalCheck, null, 2));
    
    const modalEnabledImages = finalCheck.filter(img => img.hasModalEnabled);
    console.log(`${modalEnabledImages.length} images have modal functionality enabled`);
    
    if (modalEnabledImages.length > 0) {
      console.log('✅ Some images have modal functionality!');
      
      // Test clicking the first modal-enabled image
      const firstModalImage = modalEnabledImages[0];
      const selector = firstModalImage.src === 'picsum' ? 'img[src*="picsum"]' : 'img[src*="placeholder"]';
      
      const testImage = page.locator(selector).first();
      await testImage.click();
      await page.waitForTimeout(1000);
      
      const modalVisible = await page.locator('.image-modal').isVisible();
      console.log('Modal test click result:', modalVisible);
      
      if (modalVisible) {
        console.log('🎉 FINAL CONFIRMATION: MODAL FUNCTIONALITY IS WORKING!');
        await page.screenshot({ 
          path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/final-working-modal.png',
          fullPage: true 
        });
        await page.keyboard.press('Escape');
      }
    } else {
      console.log('❌ No images have modal functionality enabled');
    }
  });
});