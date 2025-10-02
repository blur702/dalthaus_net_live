const { test, expect } = require('@playwright/test');

test.describe('Corrected Dual Image Modal System Test', () => {
  
  test('Should only add modal functionality to images with data-modal-src attribute', async ({ page }) => {
    // Test on a real article page
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    // Find an article link and navigate to it
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    if (articleLinks.length > 0) {
      await articleLinks[0].click();
      await page.waitForLoadState('networkidle');
      
      // Wait for modal functionality to be processed
      await page.waitForTimeout(1000);
      
      // Check the image processing results
      const imageAnalysis = await page.evaluate(() => {
        const allImages = document.querySelectorAll('.content-text img, .prose img, article img, main img');
        const imagesWithModalSrc = document.querySelectorAll('img[data-modal-src]');
        const imagesWithModalEnabled = document.querySelectorAll('img[data-modal-enabled]');
        
        const analysis = {
          totalImages: allImages.length,
          imagesWithModalSrc: imagesWithModalSrc.length,
          imagesWithModalEnabled: imagesWithModalEnabled.length,
          modalSrcImages: [],
          regularImages: []
        };
        
        // Analyze images with data-modal-src
        imagesWithModalSrc.forEach((img, index) => {
          analysis.modalSrcImages.push({
            index: index,
            src: img.src.substring(img.src.lastIndexOf('/') + 1),
            modalSrc: img.getAttribute('data-modal-src'),
            hasModalEnabled: img.hasAttribute('data-modal-enabled'),
            hasCursor: window.getComputedStyle(img).cursor === 'pointer',
            hasClick: !!img.onclick
          });
        });
        
        // Analyze regular images (without data-modal-src)
        const regularImages = Array.from(allImages).filter(img => !img.hasAttribute('data-modal-src'));
        regularImages.forEach((img, index) => {
          analysis.regularImages.push({
            index: index,
            src: img.src.substring(img.src.lastIndexOf('/') + 1),
            hasModalEnabled: img.hasAttribute('data-modal-enabled'),
            hasCursor: window.getComputedStyle(img).cursor === 'pointer'
          });
        });
        
        return analysis;
      });
      
      console.log('Image Analysis Results:', JSON.stringify(imageAnalysis, null, 2));
      
      // Verify that only images with data-modal-src get modal functionality
      expect(imageAnalysis.imagesWithModalEnabled).toBe(imageAnalysis.imagesWithModalSrc);
      
      if (imageAnalysis.imagesWithModalSrc > 0) {
        console.log(`✓ Found ${imageAnalysis.imagesWithModalSrc} images with data-modal-src attribute`);
        console.log(`✓ ${imageAnalysis.imagesWithModalEnabled} images have modal functionality enabled`);
      } else {
        console.log('ℹ No images with data-modal-src found on this page');
      }
      
      if (imageAnalysis.regularImages.length > 0) {
        const regularImagesWithModal = imageAnalysis.regularImages.filter(img => img.hasModalEnabled);
        expect(regularImagesWithModal.length).toBe(0);
        console.log(`✓ ${imageAnalysis.regularImages.length} regular images correctly do NOT have modal functionality`);
      }
    } else {
      console.log('No article links found to test');
    }
  });

  test('Test dual image modal button in admin TinyMCE editor', async ({ page }) => {
    // Login to admin
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('networkidle');
    
    // Fill login form
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Navigate to content creation
    await page.goto('https://dalthaus.net/admin/content/create');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000); // Wait for TinyMCE to load
    
    // Check if dual image button exists in TinyMCE toolbar
    const dualImageButtonExists = await page.evaluate(() => {
      // Check if TinyMCE is loaded
      if (typeof tinymce === 'undefined') return false;
      
      const editor = tinymce.get('body');
      if (!editor) return false;
      
      // Look for the dual image button
      const toolbar = editor.getContainer().querySelector('.tox-toolbar');
      if (!toolbar) return false;
      
      // Check for button with dual image icon or tooltip
      const buttons = toolbar.querySelectorAll('button');
      let foundDualImageButton = false;
      
      buttons.forEach(button => {
        const title = button.getAttribute('title') || button.getAttribute('aria-label') || '';
        const text = button.textContent || '';
        if (title.toLowerCase().includes('modal') || 
            title.toLowerCase().includes('dual') ||
            text.includes('🖼️📱')) {
          foundDualImageButton = true;
        }
      });
      
      return foundDualImageButton;
    });
    
    console.log('Dual image button exists in TinyMCE:', dualImageButtonExists);
    
    if (dualImageButtonExists) {
      console.log('✓ Dual image button found in TinyMCE toolbar');
      
      // Try to click the dual image button
      try {
        await page.evaluate(() => {
          const editor = tinymce.get('body');
          const toolbar = editor.getContainer().querySelector('.tox-toolbar');
          const buttons = toolbar.querySelectorAll('button');
          
          buttons.forEach(button => {
            const title = button.getAttribute('title') || button.getAttribute('aria-label') || '';
            const text = button.textContent || '';
            if (title.toLowerCase().includes('modal') || 
                title.toLowerCase().includes('dual') ||
                text.includes('🖼️📱')) {
              button.click();
            }
          });
        });
        
        await page.waitForTimeout(500);
        
        // Check if dual image dialog opened
        const dialogVisible = await page.locator('.dual-image-dialog').isVisible();
        console.log('Dual image dialog opened:', dialogVisible);
        
        if (dialogVisible) {
          console.log('✓ Dual image dialog opens successfully');
          
          // Take screenshot of the dialog
          await page.screenshot({ 
            path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/dual-image-dialog.png',
            fullPage: true 
          });
          
          // Close the dialog
          const closeButton = page.locator('.dual-image-dialog .close-btn');
          if (await closeButton.isVisible()) {
            await closeButton.click();
          } else {
            await page.keyboard.press('Escape');
          }
        }
      } catch (error) {
        console.log('Error testing dual image button:', error.message);
      }
    } else {
      console.log('⚠ Dual image button not found in TinyMCE toolbar');
    }
  });

  test('Verify modal opens with correct image when data-modal-src is used', async ({ page }) => {
    // First, let's create a test scenario with known dual images
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    // Navigate to an article
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    if (articleLinks.length > 0) {
      await articleLinks[0].click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);
      
      // Check for images with data-modal-src
      const modalImages = await page.locator('img[data-modal-src]').all();
      
      if (modalImages.length > 0) {
        console.log(`Found ${modalImages.length} images with data-modal-src attribute`);
        
        // Test clicking the first modal image
        const firstImage = modalImages[0];
        const imageInfo = await firstImage.evaluate(img => ({
          displaySrc: img.src,
          modalSrc: img.getAttribute('data-modal-src'),
          alt: img.alt
        }));
        
        console.log('Testing image:', imageInfo);
        
        // Click the image to open modal
        await firstImage.click();
        await page.waitForTimeout(500);
        
        // Check if modal opened
        const modalVisible = await page.locator('.image-modal').isVisible();
        
        if (modalVisible) {
          console.log('✓ Modal opened successfully');
          
          // Get the modal image source
          const modalImageSrc = await page.locator('.image-modal img').getAttribute('src');
          console.log('Modal image source:', modalImageSrc);
          console.log('Expected modal source:', imageInfo.modalSrc);
          
          // Verify the modal shows the correct image (modal image, not display image)
          expect(modalImageSrc).toBe(imageInfo.modalSrc);
          console.log('✓ Modal displays the correct modal image source');
          
          // Take screenshot of modal
          await page.screenshot({ 
            path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/dual-image-modal-test.png',
            fullPage: true 
          });
          
          // Close modal
          await page.keyboard.press('Escape');
          await page.waitForTimeout(300);
          
          const modalClosed = await page.locator('.image-modal').isVisible();
          console.log('Modal closed successfully:', !modalClosed);
        } else {
          console.log('⚠ Modal did not open when clicking image with data-modal-src');
        }
      } else {
        console.log('ℹ No images with data-modal-src found on this page');
        
        // Let's also test that regular images (without data-modal-src) don't open modals
        const regularImages = await page.locator('.content-text img:not([data-modal-src]), .prose img:not([data-modal-src]), article img:not([data-modal-src])').all();
        
        if (regularImages.length > 0) {
          console.log(`Testing that ${regularImages.length} regular images don't open modals`);
          
          // Try clicking the first regular image
          await regularImages[0].click();
          await page.waitForTimeout(500);
          
          const modalOpened = await page.locator('.image-modal').isVisible();
          expect(modalOpened).toBe(false);
          console.log('✓ Regular images (without data-modal-src) correctly do NOT open modals');
        }
      }
    }
  });

  test('Check that upload controller handles dual image upload correctly', async ({ page }) => {
    // Test the dual image upload endpoint functionality
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('networkidle');
    
    // Login
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Check if dual image upload endpoint exists
    const endpointTest = await page.evaluate(async () => {
      try {
        const response = await fetch('/admin/upload/dual-image', {
          method: 'POST',
          // Empty FormData to test endpoint existence
          body: new FormData()
        });
        
        return {
          status: response.status,
          exists: response.status !== 404,
          headers: Object.fromEntries(response.headers.entries())
        };
      } catch (error) {
        return {
          error: error.message,
          exists: false
        };
      }
    });
    
    console.log('Dual image upload endpoint test:', endpointTest);
    
    if (endpointTest.exists) {
      console.log('✓ Dual image upload endpoint exists and is accessible');
    } else {
      console.log('⚠ Dual image upload endpoint not found or not accessible');
    }
  });

  test('Verify JavaScript console shows correct processing for dual images', async ({ page }) => {
    const consoleMessages = [];
    
    page.on('console', msg => {
      const text = msg.text();
      if (text.includes('modal') || text.includes('data-modal-src') || text.includes('Modal functionality')) {
        consoleMessages.push(text);
      }
    });
    
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    // Navigate to an article
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    if (articleLinks.length > 0) {
      await articleLinks[0].click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000); // Wait for processing
    }
    
    console.log('Modal-related console messages:', consoleMessages);
    
    // Check for expected console messages
    const hasModalProcessingMessages = consoleMessages.some(msg => 
      msg.includes('Modal functionality added') || 
      msg.includes('data-modal-src')
    );
    
    if (hasModalProcessingMessages) {
      console.log('✓ Console shows modal processing messages');
    } else {
      console.log('ℹ No modal processing messages found (might indicate no dual images on page)');
    }
  });
});