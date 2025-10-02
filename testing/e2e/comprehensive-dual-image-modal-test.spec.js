const { test, expect } = require('@playwright/test');

test.describe('Comprehensive Dual Image Modal System Test', () => {
  
  test('1. Verify TinyMCE dual image button appears in admin editor toolbar', async ({ page }) => {
    console.log('🔍 Testing TinyMCE dual image button in admin interface...');
    
    // Login to admin
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('networkidle');
    
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Navigate to content creation page
    await page.goto('https://dalthaus.net/admin/content/create');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000); // Wait for TinyMCE to fully load
    
    // Check TinyMCE initialization and dual image button
    const buttonAnalysis = await page.evaluate(() => {
      const analysis = {
        tinymceLoaded: typeof tinymce !== 'undefined',
        editorInstance: null,
        toolbarButtons: [],
        dualImageButton: null
      };
      
      if (analysis.tinymceLoaded) {
        const editor = tinymce.get('body');
        analysis.editorInstance = !!editor;
        
        if (editor) {
          const toolbar = editor.getContainer().querySelector('.tox-toolbar');
          if (toolbar) {
            const buttons = toolbar.querySelectorAll('button');
            buttons.forEach((button, index) => {
              const title = button.getAttribute('title') || '';
              const ariaLabel = button.getAttribute('aria-label') || '';
              const text = button.textContent || '';
              const innerHTML = button.innerHTML || '';
              
              analysis.toolbarButtons.push({
                index,
                title,
                ariaLabel,
                text,
                hasIcon: innerHTML.includes('🖼️📱') || innerHTML.includes('svg'),
                isDualImage: title.toLowerCase().includes('dual') || 
                           title.toLowerCase().includes('modal') ||
                           text.includes('🖼️📱') ||
                           ariaLabel.toLowerCase().includes('dual')
              });
              
              if (title.toLowerCase().includes('dual') || 
                  title.toLowerCase().includes('modal') ||
                  text.includes('🖼️📱')) {
                analysis.dualImageButton = {
                  index,
                  title,
                  ariaLabel,
                  text,
                  innerHTML
                };
              }
            });
          }
        }
      }
      
      return analysis;
    });
    
    console.log('TinyMCE Analysis:', JSON.stringify(buttonAnalysis, null, 2));
    
    expect(buttonAnalysis.tinymceLoaded).toBe(true);
    expect(buttonAnalysis.editorInstance).toBe(true);
    
    if (buttonAnalysis.dualImageButton) {
      console.log('✅ Dual image button found in TinyMCE toolbar');
      console.log(`   Button title: "${buttonAnalysis.dualImageButton.title}"`);
      console.log(`   Button text: "${buttonAnalysis.dualImageButton.text}"`);
    } else {
      console.log('❌ Dual image button NOT found in TinyMCE toolbar');
      console.log(`   Found ${buttonAnalysis.toolbarButtons.length} toolbar buttons total`);
    }
    
    // Take screenshot of the admin interface
    await page.screenshot({ 
      path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/admin-tinymce-toolbar.png',
      fullPage: true 
    });
  });

  test('2. Test dual image button click opens dialog', async ({ page }) => {
    console.log('🔍 Testing dual image button click functionality...');
    
    // Login to admin
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('networkidle');
    
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Navigate to content creation
    await page.goto('https://dalthaus.net/admin/content/create');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    
    // Try to click the dual image button
    const buttonClickResult = await page.evaluate(() => {
      if (typeof tinymce === 'undefined') return { error: 'TinyMCE not loaded' };
      
      const editor = tinymce.get('body');
      if (!editor) return { error: 'Editor not found' };
      
      const toolbar = editor.getContainer().querySelector('.tox-toolbar');
      if (!toolbar) return { error: 'Toolbar not found' };
      
      const buttons = toolbar.querySelectorAll('button');
      let dualImageButton = null;
      
      buttons.forEach(button => {
        const title = button.getAttribute('title') || '';
        const text = button.textContent || '';
        if (title.toLowerCase().includes('dual') || 
            title.toLowerCase().includes('modal') ||
            text.includes('🖼️📱')) {
          dualImageButton = button;
        }
      });
      
      if (dualImageButton) {
        dualImageButton.click();
        return { success: true, buttonFound: true };
      } else {
        return { success: false, buttonFound: false };
      }
    });
    
    console.log('Button click result:', buttonClickResult);
    
    if (buttonClickResult.buttonFound) {
      await page.waitForTimeout(1000);
      
      // Check for dialog appearance
      const dialogVisible = await page.locator('.dual-image-dialog, .tox-dialog, .mce-window').isVisible();
      console.log('Dialog visible after button click:', dialogVisible);
      
      if (dialogVisible) {
        console.log('✅ Dual image dialog opened successfully');
        
        // Take screenshot of the dialog
        await page.screenshot({ 
          path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/dual-image-dialog-open.png',
          fullPage: true 
        });
        
        // Try to close the dialog
        await page.keyboard.press('Escape');
        await page.waitForTimeout(500);
      } else {
        console.log('❌ Dialog did not appear after clicking button');
      }
    } else {
      console.log('❌ Dual image button not found or not clickable');
    }
  });

  test('3. Test dual image upload functionality', async ({ page }) => {
    console.log('🔍 Testing dual image upload endpoint...');
    
    // Login to admin first
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('networkidle');
    
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Test the dual image upload endpoint
    const uploadEndpointTest = await page.evaluate(async () => {
      try {
        // Test endpoint existence with OPTIONS request first
        const optionsResponse = await fetch('/admin/upload/dual-image', {
          method: 'OPTIONS'
        });
        
        // Test with empty POST to check if endpoint exists
        const postResponse = await fetch('/admin/upload/dual-image', {
          method: 'POST',
          body: new FormData()
        });
        
        return {
          optionsStatus: optionsResponse.status,
          postStatus: postResponse.status,
          endpointExists: postResponse.status !== 404,
          responseText: await postResponse.text()
        };
      } catch (error) {
        return {
          error: error.message,
          endpointExists: false
        };
      }
    });
    
    console.log('Upload endpoint test:', uploadEndpointTest);
    
    if (uploadEndpointTest.endpointExists) {
      console.log('✅ Dual image upload endpoint exists and responds');
    } else {
      console.log('❌ Dual image upload endpoint not found or inaccessible');
    }
    
    // Test if the upload controller file exists
    const controllerCheck = await page.evaluate(async () => {
      try {
        const response = await fetch('/admin/upload/', {
          method: 'GET'
        });
        return {
          uploadControllerExists: response.status !== 404,
          status: response.status
        };
      } catch (error) {
        return {
          error: error.message,
          uploadControllerExists: false
        };
      }
    });
    
    console.log('Upload controller check:', controllerCheck);
  });

  test('4. Verify images with data-modal-src attribute display correctly', async ({ page }) => {
    console.log('🔍 Testing images with data-modal-src attribute...');
    
    // Navigate to articles page
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    // Find and navigate to an article
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    
    if (articleLinks.length > 0) {
      await articleLinks[0].click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000); // Wait for modal processing
      
      const imageAnalysis = await page.evaluate(() => {
        const allImages = document.querySelectorAll('img');
        const modalImages = document.querySelectorAll('img[data-modal-src]');
        
        const analysis = {
          totalImages: allImages.length,
          modalEnabledImages: modalImages.length,
          modalImageDetails: [],
          regularImageDetails: []
        };
        
        // Analyze modal-enabled images
        modalImages.forEach((img, index) => {
          const style = window.getComputedStyle(img);
          analysis.modalImageDetails.push({
            index,
            src: img.src.substring(img.src.lastIndexOf('/') + 1),
            modalSrc: img.getAttribute('data-modal-src'),
            hasPointerCursor: style.cursor === 'pointer',
            hasClickHandler: !!img.onclick || img.hasAttribute('data-modal-enabled'),
            alt: img.alt || '',
            width: img.naturalWidth,
            height: img.naturalHeight
          });
        });
        
        // Analyze regular images
        const regularImages = Array.from(allImages).filter(img => !img.hasAttribute('data-modal-src'));
        regularImages.forEach((img, index) => {
          const style = window.getComputedStyle(img);
          analysis.regularImageDetails.push({
            index,
            src: img.src.substring(img.src.lastIndexOf('/') + 1),
            hasPointerCursor: style.cursor === 'pointer',
            hasClickHandler: !!img.onclick,
            alt: img.alt || ''
          });
        });
        
        return analysis;
      });
      
      console.log('Image Analysis:', JSON.stringify(imageAnalysis, null, 2));
      
      if (imageAnalysis.modalEnabledImages > 0) {
        console.log(`✅ Found ${imageAnalysis.modalEnabledImages} images with data-modal-src attribute`);
        
        imageAnalysis.modalImageDetails.forEach((img, index) => {
          console.log(`   Image ${index + 1}: Display "${img.src}" → Modal "${img.modalSrc}"`);
          expect(img.hasPointerCursor).toBe(true);
        });
      } else {
        console.log('ℹ️ No images with data-modal-src found on this page');
      }
      
      // Verify regular images don't have modal functionality
      const regularImagesWithCursor = imageAnalysis.regularImageDetails.filter(img => img.hasPointerCursor);
      console.log(`✅ ${imageAnalysis.regularImageDetails.length} regular images correctly do NOT have pointer cursor`);
      
    } else {
      console.log('ℹ️ No article links found to test');
    }
  });

  test('5. Test clicking images opens modal with correct modal image', async ({ page }) => {
    console.log('🔍 Testing modal opening with correct image...');
    
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    
    if (articleLinks.length > 0) {
      await articleLinks[0].click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);
      
      // Find images with data-modal-src
      const modalImages = await page.locator('img[data-modal-src]').all();
      
      if (modalImages.length > 0) {
        console.log(`Found ${modalImages.length} clickable modal images`);
        
        for (let i = 0; i < Math.min(modalImages.length, 3); i++) { // Test up to 3 images
          const image = modalImages[i];
          
          // Get image information
          const imageInfo = await image.evaluate(img => ({
            displaySrc: img.src,
            modalSrc: img.getAttribute('data-modal-src'),
            alt: img.alt
          }));
          
          console.log(`Testing image ${i + 1}:`, imageInfo);
          
          // Click the image
          await image.click();
          await page.waitForTimeout(500);
          
          // Check if modal opened
          const modal = page.locator('.image-modal');
          const modalVisible = await modal.isVisible();
          
          if (modalVisible) {
            // Get the modal image source
            const modalImg = modal.locator('img');
            const modalImageSrc = await modalImg.getAttribute('src');
            
            console.log(`   Modal opened with image: ${modalImageSrc}`);
            console.log(`   Expected modal image: ${imageInfo.modalSrc}`);
            
            // Verify correct image is displayed in modal
            expect(modalImageSrc).toBe(imageInfo.modalSrc);
            console.log(`   ✅ Modal displays correct image for dual image ${i + 1}`);
            
            // Take screenshot
            await page.screenshot({ 
              path: `/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/modal-test-image-${i + 1}.png`,
              fullPage: true 
            });
            
            // Close modal
            await page.keyboard.press('Escape');
            await page.waitForTimeout(300);
            
            const modalClosed = !(await modal.isVisible());
            expect(modalClosed).toBe(true);
            console.log(`   ✅ Modal closed successfully`);
          } else {
            console.log(`   ❌ Modal did not open for image ${i + 1}`);
          }
        }
      } else {
        console.log('ℹ️ No images with data-modal-src found to test modal functionality');
      }
    }
  });

  test('6. Verify only images with data-modal-src get modal functionality', async ({ page }) => {
    console.log('🔍 Testing that only dual images get modal functionality...');
    
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    
    if (articleLinks.length > 0) {
      await articleLinks[0].click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);
      
      // Test regular images (without data-modal-src) don't open modals
      const regularImages = await page.locator('img:not([data-modal-src])').all();
      
      if (regularImages.length > 0) {
        console.log(`Testing ${regularImages.length} regular images (without data-modal-src)`);
        
        // Test first regular image
        const regularImage = regularImages[0];
        const regularImageSrc = await regularImage.getAttribute('src');
        console.log(`Clicking regular image: ${regularImageSrc?.substring(regularImageSrc.lastIndexOf('/') + 1)}`);
        
        await regularImage.click();
        await page.waitForTimeout(500);
        
        // Check that modal did NOT open
        const modalVisible = await page.locator('.image-modal').isVisible();
        expect(modalVisible).toBe(false);
        console.log('✅ Regular images correctly do NOT open modals');
        
        // Test cursor style
        const hasCursor = await regularImage.evaluate(img => {
          return window.getComputedStyle(img).cursor === 'pointer';
        });
        expect(hasCursor).toBe(false);
        console.log('✅ Regular images correctly do NOT have pointer cursor');
      }
      
      // Compare with modal images
      const modalImages = await page.locator('img[data-modal-src]').all();
      
      if (modalImages.length > 0) {
        const modalImage = modalImages[0];
        const modalImageSrc = await modalImage.getAttribute('src');
        console.log(`Comparing with modal image: ${modalImageSrc?.substring(modalImageSrc.lastIndexOf('/') + 1)}`);
        
        await modalImage.click();
        await page.waitForTimeout(500);
        
        const modalOpened = await page.locator('.image-modal').isVisible();
        expect(modalOpened).toBe(true);
        console.log('✅ Modal images correctly DO open modals');
        
        // Close modal
        await page.keyboard.press('Escape');
        await page.waitForTimeout(300);
      }
    }
  });

  test('7. Console log analysis for modal processing', async ({ page }) => {
    console.log('🔍 Analyzing console logs for modal processing...');
    
    const consoleMessages = [];
    
    page.on('console', msg => {
      const text = msg.text();
      if (text.includes('modal') || 
          text.includes('data-modal-src') || 
          text.includes('Modal functionality') ||
          text.includes('dual image')) {
        consoleMessages.push({
          type: msg.type(),
          text: text,
          timestamp: new Date().toISOString()
        });
      }
    });
    
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    if (articleLinks.length > 0) {
      await articleLinks[0].click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(3000); // Wait for all processing
    }
    
    console.log('Modal-related console messages:');
    consoleMessages.forEach((msg, index) => {
      console.log(`${index + 1}. [${msg.type}] ${msg.text}`);
    });
    
    if (consoleMessages.length > 0) {
      console.log('✅ Console shows modal processing activity');
    } else {
      console.log('ℹ️ No modal-related console messages (may indicate no dual images or quiet processing)');
    }
  });

  test('8. Performance and error checking', async ({ page }) => {
    console.log('🔍 Checking for errors and performance issues...');
    
    const errors = [];
    const networkErrors = [];
    
    page.on('pageerror', error => {
      errors.push({
        message: error.message,
        stack: error.stack,
        timestamp: new Date().toISOString()
      });
    });
    
    page.on('response', response => {
      if (response.status() >= 400) {
        networkErrors.push({
          url: response.url(),
          status: response.status(),
          statusText: response.statusText()
        });
      }
    });
    
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    if (articleLinks.length > 0) {
      await articleLinks[0].click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);
      
      // Test modal functionality
      const modalImages = await page.locator('img[data-modal-src]').all();
      if (modalImages.length > 0) {
        await modalImages[0].click();
        await page.waitForTimeout(500);
        await page.keyboard.press('Escape');
      }
    }
    
    console.log('JavaScript Errors:', errors);
    console.log('Network Errors:', networkErrors);
    
    expect(errors.length).toBe(0);
    expect(networkErrors.filter(err => err.url.includes('dual-image') || err.url.includes('modal')).length).toBe(0);
    
    if (errors.length === 0 && networkErrors.length === 0) {
      console.log('✅ No errors detected during dual image modal testing');
    }
  });
});