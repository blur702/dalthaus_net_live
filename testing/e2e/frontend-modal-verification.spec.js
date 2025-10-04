const { test, expect } = require('@playwright/test');

test.describe('Frontend Modal Verification', () => {
  test('Verify frontend image modal functionality', async ({ page }) => {
    console.log('🌐 Starting frontend modal verification...');
    
    // Track console messages
    page.on('console', msg => {
      console.log(`FRONTEND CONSOLE ${msg.type().toUpperCase()}: ${msg.text()}`);
    });
    
    // Step 1: Navigate to homepage
    await page.goto('https://dalthaus.net');
    await page.waitForLoadState('networkidle');
    
    console.log('✅ Loaded homepage');
    
    // Step 2: Find articles/photobooks with images
    const contentLinks = await page.locator('a[href*="/article/"], a[href*="/photobook/"]').all();
    
    console.log(`Found ${contentLinks.length} content links`);
    
    if (contentLinks.length > 0) {
      // Click on first content item
      await contentLinks[0].click();
      await page.waitForLoadState('networkidle');
      
      console.log('✅ Navigated to content page');
      
      // Step 3: Look for images with modal functionality
      const modalImages = await page.evaluate(() => {
        const images = Array.from(document.querySelectorAll('img'));
        return images.map(img => ({
          src: img.src,
          dataModalSrc: img.getAttribute('data-modal-src'),
          onclick: img.onclick?.toString() || 'none',
          hasModalSrc: !!img.getAttribute('data-modal-src'),
          hasOnclick: !!img.onclick,
          style: img.style.cssText,
          cursor: getComputedStyle(img).cursor
        })).filter(imgData => imgData.hasModalSrc || imgData.hasOnclick);
      });
      
      console.log(`Found ${modalImages.length} images with modal functionality`);
      console.log('Modal images:', JSON.stringify(modalImages, null, 2));
      
      if (modalImages.length > 0) {
        // Step 4: Test clicking on a modal image
        const modalImage = page.locator('img[data-modal-src], img[onclick*="openImageModal"]').first();
        
        if (await modalImage.count() > 0) {
          console.log('🖱️ Clicking on modal image...');
          await modalImage.click();
          await page.waitForTimeout(1000);
          
          // Check for modal
          const modalElements = await page.evaluate(() => {
            const modalSelectors = [
              '#imageModal',
              '.modal',
              '.image-modal',
              '[id*="modal"]',
              '.overlay'
            ];
            
            const results = {};
            modalSelectors.forEach(selector => {
              const element = document.querySelector(selector);
              if (element) {
                results[selector] = {
                  visible: element.offsetWidth > 0 && element.offsetHeight > 0,
                  display: getComputedStyle(element).display,
                  opacity: getComputedStyle(element).opacity,
                  zIndex: getComputedStyle(element).zIndex
                };
              }
            });
            
            return results;
          });
          
          console.log('Modal elements check:', JSON.stringify(modalElements, null, 2));
          
          const anyModalVisible = Object.values(modalElements).some(modal => modal.visible);
          
          if (anyModalVisible) {
            console.log('✅ Frontend modal opened successfully');
            
            // Try to close modal with escape key
            await page.keyboard.press('Escape');
            await page.waitForTimeout(500);
            console.log('✅ Attempted to close modal with Escape');
          } else {
            console.log('❌ Frontend modal did not open visibly');
          }
          
          await page.screenshot({ 
            path: 'testing/results/frontend-modal-verification.png',
            fullPage: true 
          });
        }
      } else {
        console.log('ℹ️ No images with modal functionality found on this page');
      }
    }
    
    // Step 5: Check for modal-related JavaScript functions
    const frontendJSCheck = await page.evaluate(() => {
      return {
        openImageModal: typeof openImageModal !== 'undefined',
        closeImageModal: typeof closeImageModal !== 'undefined',
        modalFunctions: Object.keys(window).filter(key => 
          key.toLowerCase().includes('modal') || key.toLowerCase().includes('image')
        ),
        jqueryLoaded: typeof $ !== 'undefined'
      };
    });
    
    console.log('Frontend JavaScript environment:', JSON.stringify(frontendJSCheck, null, 2));
    
    // Final report
    console.log('\n=== FRONTEND MODAL VERIFICATION REPORT ===');
    console.log(`📄 Content pages found: ${contentLinks.length}`);
    console.log(`🖼️ Modal images found: ${modalImages.length}`);
    console.log(`🔧 Modal functions available: ${frontendJSCheck.openImageModal ? '✅' : '❌'}`);
    console.log(`📱 jQuery loaded: ${frontendJSCheck.jqueryLoaded ? '✅' : '❌'}`);
    
    if (modalImages.length === 0) {
      console.log('\n💡 Suggestion: Test image upload through admin to create content with modal images');
    }
  });
});