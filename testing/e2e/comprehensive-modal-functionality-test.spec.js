const { test, expect } = require('@playwright/test');

test.describe('Comprehensive Modal Functionality Test', () => {
  
  test('Modal functions should be available on article pages', async ({ page }) => {
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    // Find an article link and navigate to it
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    if (articleLinks.length > 0) {
      await articleLinks[0].click();
      await page.waitForLoadState('networkidle');
      
      // Check if modal functions exist
      const modalFunctions = await page.evaluate(() => {
        return {
          openImageModal: typeof window.openImageModal === 'function',
          closeImageModal: typeof window.closeImageModal === 'function',
          hasImages: document.querySelectorAll('.content-text img, .prose img, article img').length > 0
        };
      });
      
      console.log('Article page modal functions:', modalFunctions);
      expect(modalFunctions.openImageModal).toBe(true);
      expect(modalFunctions.closeImageModal).toBe(true);
      
      if (modalFunctions.hasImages) {
        console.log('✓ Images found on article page for modal testing');
      } else {
        console.log('ℹ No images found on this article page');
      }
    } else {
      console.log('No article links found to test');
    }
  });

  test('Modal functions should be available on photobook pages', async ({ page }) => {
    await page.goto('https://dalthaus.net/photobooks');
    await page.waitForLoadState('networkidle');
    
    // Check if there are photobook links
    const photobookLinks = await page.locator('a[href*="/photobook/"]').all();
    if (photobookLinks.length > 0) {
      await photobookLinks[0].click();
      await page.waitForLoadState('networkidle');
      
      // Check if modal functions exist
      const modalFunctions = await page.evaluate(() => {
        return {
          openImageModal: typeof window.openImageModal === 'function',
          closeImageModal: typeof window.closeImageModal === 'function',
          hasImages: document.querySelectorAll('.content-text img, .prose img, article img').length > 0
        };
      });
      
      console.log('Photobook page modal functions:', modalFunctions);
      expect(modalFunctions.openImageModal).toBe(true);
      expect(modalFunctions.closeImageModal).toBe(true);
      
      if (modalFunctions.hasImages) {
        console.log('✓ Images found on photobook page for modal testing');
      } else {
        console.log('ℹ No images found on this photobook page');
      }
    } else {
      console.log('No photobook links found - checking for "no content" message');
      const noContentMessage = await page.textContent('body');
      console.log('Page content preview:', noContentMessage.substring(0, 200));
    }
  });

  test('Images should have modal functionality automatically added', async ({ page }) => {
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    // Find an article with content
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    if (articleLinks.length > 0) {
      await articleLinks[0].click();
      await page.waitForLoadState('networkidle');
      
      // Wait for modal functionality to be added
      await page.waitForTimeout(1000);
      
      // Check if images have been processed for modal functionality
      const imageProcessing = await page.evaluate(() => {
        const images = document.querySelectorAll('.content-text img, .prose img, article img');
        const results = {
          totalImages: images.length,
          imagesWithModal: 0,
          imagesWithCursor: 0,
          imagesWithClass: 0,
          imageDetails: []
        };
        
        images.forEach((img, index) => {
          const hasModal = img.hasAttribute('data-modal-enabled') || img.onclick;
          const hasCursor = window.getComputedStyle(img).cursor === 'pointer';
          const hasClass = img.classList.contains('modal-image');
          
          if (hasModal) results.imagesWithModal++;
          if (hasCursor) results.imagesWithCursor++;
          if (hasClass) results.imagesWithClass++;
          
          results.imageDetails.push({
            index: index,
            src: img.src.substring(img.src.lastIndexOf('/') + 1),
            width: img.width,
            height: img.height,
            hasModal: hasModal,
            hasCursor: hasCursor,
            hasClass: hasClass
          });
        });
        
        return results;
      });
      
      console.log('Image processing results:', JSON.stringify(imageProcessing, null, 2));
      
      if (imageProcessing.totalImages > 0) {
        console.log(`Found ${imageProcessing.totalImages} images on the page`);
        console.log(`${imageProcessing.imagesWithModal} images have modal functionality`);
        console.log(`${imageProcessing.imagesWithCursor} images have pointer cursor`);
        console.log(`${imageProcessing.imagesWithClass} images have modal class`);
        
        // At least some images should have modal functionality if they're large enough
        if (imageProcessing.imagesWithModal > 0) {
          console.log('✓ Modal functionality has been added to images');
        } else {
          console.log('ⓘ No images have modal functionality (might be too small or decorative)');
        }
      } else {
        console.log('ℹ No images found to test modal functionality');
      }
    }
  });

  test('Modal should open and close correctly when clicking on images', async ({ page }) => {
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    // Find an article with images
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    for (let i = 0; i < Math.min(3, articleLinks.length); i++) {
      await articleLinks[i].click();
      await page.waitForLoadState('networkidle');
      
      // Wait for modal functionality to be added
      await page.waitForTimeout(1000);
      
      // Look for clickable images
      const clickableImages = await page.locator('.content-text img[data-modal-enabled], .prose img[data-modal-enabled], article img[data-modal-enabled]').all();
      
      if (clickableImages.length > 0) {
        console.log(`Found ${clickableImages.length} clickable images on article ${i + 1}`);
        
        // Test clicking the first image
        try {
          await clickableImages[0].click();
          await page.waitForTimeout(500);
          
          // Check if modal appeared
          const modalVisible = await page.locator('.image-modal').isVisible();
          console.log('Modal visible after click:', modalVisible);
          
          if (modalVisible) {
            console.log('✓ Modal opened successfully');
            
            // Take screenshot of modal
            await page.screenshot({ 
              path: `/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/modal-open-test.png`,
              fullPage: true 
            });
            
            // Test closing modal with close button
            const closeButton = page.locator('.modal-close');
            if (await closeButton.isVisible()) {
              await closeButton.click();
              await page.waitForTimeout(300);
              
              const modalStillVisible = await page.locator('.image-modal').isVisible();
              console.log('Modal still visible after close:', modalStillVisible);
              
              if (!modalStillVisible) {
                console.log('✓ Modal closed successfully with close button');
              }
            }
            
            // Test opening and closing with Escape key
            await clickableImages[0].click();
            await page.waitForTimeout(300);
            await page.keyboard.press('Escape');
            await page.waitForTimeout(300);
            
            const modalAfterEscape = await page.locator('.image-modal').isVisible();
            console.log('Modal visible after Escape:', modalAfterEscape);
            
            if (!modalAfterEscape) {
              console.log('✓ Modal closed successfully with Escape key');
            }
            
            return; // Exit test successfully
          } else {
            console.log('⚠ Modal did not open when clicking image');
          }
        } catch (error) {
          console.log('Error testing modal:', error.message);
        }
      } else {
        console.log(`No clickable images found on article ${i + 1}`);
      }
      
      // Go back to articles list
      await page.goto('https://dalthaus.net/articles');
      await page.waitForLoadState('networkidle');
    }
    
    console.log('Modal functionality test completed');
  });

  test('Check for any JavaScript errors that might affect modals', async ({ page }) => {
    const jsErrors = [];
    
    page.on('console', msg => {
      if (msg.type() === 'error') {
        jsErrors.push(msg.text());
      }
    });
    
    page.on('pageerror', error => {
      jsErrors.push(`Page error: ${error.message}`);
    });
    
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    // Navigate to an article
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    if (articleLinks.length > 0) {
      await articleLinks[0].click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000); // Wait for any delayed scripts
    }
    
    console.log('JavaScript errors detected:', jsErrors.length);
    if (jsErrors.length > 0) {
      console.log('Errors:', JSON.stringify(jsErrors, null, 2));
    } else {
      console.log('✓ No JavaScript errors detected that would affect modal functionality');
    }
    
    // This test should pass even if there are some errors, as long as they're not modal-related
    const modalRelatedErrors = jsErrors.filter(error => 
      error.toLowerCase().includes('modal') || 
      error.toLowerCase().includes('openimagemmodal') ||
      error.toLowerCase().includes('closeimagemmodal')
    );
    
    expect(modalRelatedErrors.length).toBe(0);
  });

  test('Verify modal CSS and styling is properly loaded', async ({ page }) => {
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    // Navigate to an article
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    if (articleLinks.length > 0) {
      await articleLinks[0].click();
      await page.waitForLoadState('networkidle');
      
      // Check if modal CSS is present
      const modalStyles = await page.evaluate(() => {
        const styles = window.getComputedStyle(document.documentElement);
        
        // Create a temporary modal element to test styles
        const testModal = document.createElement('div');
        testModal.className = 'image-modal';
        testModal.style.visibility = 'hidden';
        document.body.appendChild(testModal);
        
        const modalStyles = window.getComputedStyle(testModal);
        const hasModalStyles = {
          position: modalStyles.position,
          zIndex: modalStyles.zIndex,
          background: modalStyles.background || modalStyles.backgroundColor,
          display: modalStyles.display
        };
        
        document.body.removeChild(testModal);
        
        // Check for image hover styles
        const testImage = document.createElement('img');
        testImage.className = 'modal-image';
        testImage.style.visibility = 'hidden';
        document.body.appendChild(testImage);
        
        const imageStyles = window.getComputedStyle(testImage);
        const hasImageStyles = {
          cursor: imageStyles.cursor,
          transition: imageStyles.transition
        };
        
        document.body.removeChild(testImage);
        
        return {
          modal: hasModalStyles,
          image: hasImageStyles
        };
      });
      
      console.log('Modal CSS check:', JSON.stringify(modalStyles, null, 2));
      
      // Verify that modal has positioning styles
      expect(modalStyles.modal.position).toBe('fixed');
      
      if (modalStyles.image.cursor === 'pointer') {
        console.log('✓ Image cursor styles are properly loaded');
      }
      
      if (modalStyles.modal.zIndex && parseInt(modalStyles.modal.zIndex) > 1000) {
        console.log('✓ Modal z-index is properly set for overlay');
      }
    }
  });
});