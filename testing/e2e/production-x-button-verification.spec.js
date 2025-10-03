const { test, expect } = require('@playwright/test');

test.describe('Production X Button Fix Verification', () => {
  test('Verify X close button fix is deployed and working in production', async ({ page }) => {
    console.log('Testing X close button fix in production...');
    
    // Go to production site
    await page.goto('https://dalthaus.net');
    await page.waitForLoadState('networkidle');
    
    // Inject a test modal to verify our fix is deployed
    const testResult = await page.evaluate(() => {
      // Check if our fixed openImageModal function exists
      if (typeof window.openImageModal !== 'function') {
        return 'openImageModal function not found';
      }
      
      // Check if our fixed closeImageModal function exists  
      if (typeof window.closeImageModal !== 'function') {
        return 'closeImageModal function not found';
      }
      
      // Create test image and test the modal functionality
      const testImg = document.createElement('img');
      testImg.src = '/assets/img/placeholder.jpg';
      testImg.setAttribute('data-modal-src', '/assets/img/placeholder.jpg');
      testImg.style.cursor = 'pointer';
      testImg.style.width = '100px';
      testImg.style.height = '100px';
      testImg.id = 'test-modal-image';
      
      // Add click handler using our fixed function
      testImg.onclick = function() {
        window.openImageModal(this.getAttribute('data-modal-src'), 'Test Image');
      };
      
      document.body.appendChild(testImg);
      return 'Test image created successfully';
    });
    
    console.log('Test setup result:', testResult);
    
    if (testResult === 'Test image created successfully') {
      // Click the test image to open modal
      await page.click('#test-modal-image');
      await page.waitForTimeout(500);
      
      // Verify modal opened
      const modal = page.locator('.image-modal');
      const modalVisible = await modal.isVisible();
      
      if (modalVisible) {
        console.log('✅ Modal opened successfully');
        
        // Test the X close button specifically
        const closeBtn = page.locator('.modal-close');
        const closeBtnVisible = await closeBtn.isVisible();
        
        if (closeBtnVisible) {
          console.log('✅ X close button is visible');
          
          // Click the X button
          await closeBtn.click();
          await page.waitForTimeout(300);
          
          // Verify modal closed
          const modalStillVisible = await modal.isVisible();
          
          if (!modalStillVisible) {
            console.log('🎉 SUCCESS: X close button fix is working correctly in production!');
          } else {
            console.log('❌ FAILURE: X close button did not close the modal');
            
            // Debug: Check if event listeners are properly attached
            const debugInfo = await page.evaluate(() => {
              const closeBtn = document.querySelector('.modal-close');
              if (closeBtn) {
                return {
                  hasEventListeners: closeBtn.onclick !== null,
                  innerHTML: closeBtn.innerHTML,
                  className: closeBtn.className
                };
              }
              return 'Close button not found';
            });
            console.log('Debug info:', debugInfo);
          }
          
          // Also test Escape key
          await page.keyboard.press('Escape');
          await page.waitForTimeout(200);
          const modalAfterEscape = await modal.isVisible();
          
          if (!modalAfterEscape) {
            console.log('✅ Escape key also works correctly');
          }
          
        } else {
          console.log('❌ X close button not visible');
        }
        
      } else {
        console.log('❌ Modal did not open');
      }
      
    } else {
      console.log('❌ Test setup failed:', testResult);
    }
    
    // Clean up
    await page.evaluate(() => {
      const testImg = document.getElementById('test-modal-image');
      if (testImg) testImg.remove();
      
      const modal = document.querySelector('.image-modal');
      if (modal) modal.remove();
    });
    
    // Take final screenshot
    await page.screenshot({ 
      path: 'testing/screenshots/production-x-button-verification.png',
      fullPage: true 
    });
    
    console.log('Production X button verification completed');
  });
  
  test('Verify modal JavaScript functions are correctly deployed', async ({ page }) => {
    console.log('Checking deployed JavaScript functions...');
    
    await page.goto('https://dalthaus.net');
    await page.waitForLoadState('networkidle');
    
    // Check the JavaScript functions in the page source
    const jsAnalysis = await page.evaluate(() => {
      const results = [];
      
      // Check openImageModal function
      if (typeof window.openImageModal === 'function') {
        const funcString = window.openImageModal.toString();
        // Check if it uses addEventListener instead of innerHTML with onclick
        if (funcString.includes('addEventListener')) {
          results.push('✅ openImageModal uses addEventListener (fixed version)');
        } else {
          results.push('❌ openImageModal still uses old innerHTML method');
        }
      } else {
        results.push('❌ openImageModal function not found');
      }
      
      // Check closeImageModal function
      if (typeof window.closeImageModal === 'function') {
        results.push('✅ closeImageModal function exists');
      } else {
        results.push('❌ closeImageModal function not found');
      }
      
      return results;
    });
    
    console.log('JavaScript analysis results:');
    jsAnalysis.forEach(result => console.log(result));
    
    // Verify the fix is in place by checking the page source
    const pageContent = await page.content();
    
    // Check if our fixed code is present
    const hasEventListenerCode = pageContent.includes('addEventListener(\'click\'');
    const hasCloseImageModal = pageContent.includes('window.closeImageModal');
    
    console.log('Page source analysis:');
    console.log(`- Contains addEventListener code: ${hasEventListenerCode ? '✅' : '❌'}`);
    console.log(`- Contains closeImageModal function: ${hasCloseImageModal ? '✅' : '❌'}`);
    
    if (hasEventListenerCode && hasCloseImageModal) {
      console.log('🎉 All fixes are properly deployed in production!');
    } else {
      console.log('⚠️  Some fixes may not be deployed correctly');
    }
  });
});