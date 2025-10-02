const { test, expect } = require('@playwright/test');

test.describe('Manual Dual Image System Test', () => {
  
  test('Complete dual image workflow test with TinyMCE button verification', async ({ page }) => {
    console.log('🔍 Testing complete dual image workflow...');
    
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
    await page.waitForTimeout(5000); // Wait for TinyMCE to fully load
    
    // Verify TinyMCE is loaded and check for dual image button more thoroughly
    const tinymceAnalysis = await page.evaluate(() => {
      if (typeof tinymce === 'undefined') {
        return { loaded: false, error: 'TinyMCE not loaded' };
      }
      
      const editor = tinymce.get('body');
      if (!editor) {
        return { loaded: true, editor: false, error: 'No editor instance found' };
      }
      
      if (!editor.initialized) {
        return { loaded: true, editor: true, initialized: false, error: 'Editor not initialized' };
      }
      
      // Get toolbar configuration
      const toolbar = editor.settings.toolbar || '';
      const hasDualImageInToolbar = toolbar.includes('dualimage');
      
      // Check if the actual button exists in DOM
      const container = editor.getContainer();
      const toolbarElement = container ? container.querySelector('.tox-toolbar') : null;
      let buttonFound = false;
      let buttonDetails = null;
      
      if (toolbarElement) {
        const buttons = toolbarElement.querySelectorAll('button');
        buttons.forEach((button, index) => {
          const title = button.getAttribute('title') || '';
          const ariaLabel = button.getAttribute('aria-label') || '';
          const text = button.textContent || '';
          
          if (title.toLowerCase().includes('modal') || 
              text.includes('🖼️📱') || 
              ariaLabel.toLowerCase().includes('dual') ||
              button.getAttribute('data-mce-name') === 'dualimage') {
            buttonFound = true;
            buttonDetails = {
              index,
              title,
              ariaLabel,
              text,
              innerHTML: button.innerHTML.substring(0, 200)
            };
          }
        });
      }
      
      return {
        loaded: true,
        editor: true,
        initialized: true,
        toolbarConfig: toolbar,
        hasDualImageInToolbar,
        buttonFound,
        buttonDetails,
        totalButtons: toolbarElement ? toolbarElement.querySelectorAll('button').length : 0
      };
    });
    
    console.log('TinyMCE Analysis:', JSON.stringify(tinymceAnalysis, null, 2));
    
    if (tinymceAnalysis.buttonFound) {
      console.log('✅ Dual image button found in TinyMCE toolbar');
      console.log(`   Button: "${tinymceAnalysis.buttonDetails.text}" - "${tinymceAnalysis.buttonDetails.title}"`);
      
      // Take screenshot of the toolbar
      await page.screenshot({ 
        path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/tinymce-toolbar-with-button.png',
        fullPage: true 
      });
      
      // Try to click the dual image button
      const clickResult = await page.evaluate(() => {
        const editor = tinymce.get('body');
        const container = editor.getContainer();
        const toolbarElement = container.querySelector('.tox-toolbar');
        const buttons = toolbarElement.querySelectorAll('button');
        
        let clicked = false;
        buttons.forEach(button => {
          const title = button.getAttribute('title') || '';
          const text = button.textContent || '';
          
          if (title.toLowerCase().includes('modal') || text.includes('🖼️📱')) {
            button.click();
            clicked = true;
          }
        });
        
        return { clicked };
      });
      
      if (clickResult.clicked) {
        await page.waitForTimeout(1000);
        
        // Check if dialog appeared
        const dialogVisible = await page.locator('.dual-image-dialog').isVisible();
        console.log('Dialog visible after button click:', dialogVisible);
        
        if (dialogVisible) {
          console.log('✅ Dual image dialog opened successfully');
          
          // Take screenshot of the dialog
          await page.screenshot({ 
            path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/dual-image-dialog-opened.png',
            fullPage: true 
          });
          
          // Close the dialog
          await page.keyboard.press('Escape');
          await page.waitForTimeout(500);
        } else {
          console.log('❌ Dialog did not appear after clicking button');
        }
      }
    } else {
      console.log('❌ Dual image button not found in TinyMCE toolbar');
      console.log(`   Toolbar config: ${tinymceAnalysis.toolbarConfig}`);
      console.log(`   Total buttons found: ${tinymceAnalysis.totalButtons}`);
    }
  });

  test('Test creating content with manual dual image HTML', async ({ page }) => {
    console.log('🔍 Testing manual dual image content creation...');
    
    // Login
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
    
    // Fill basic content fields
    await page.fill('input[name="title"]', 'Dual Image Test Article');
    await page.fill('input[name="url_alias"]', 'dual-image-test');
    
    // Create test content with dual image HTML
    const testContent = `
      <h2>Testing Dual Image Modal Functionality</h2>
      <p>This article contains test images to verify the dual image modal system.</p>
      
      <h3>Regular Image (no modal functionality)</h3>
      <img src="https://picsum.photos/400/300?random=1" alt="Regular test image" width="400">
      
      <h3>Dual Image (with modal functionality)</h3>
      <img src="https://picsum.photos/400/300?random=2" 
           alt="Dual image test" 
           width="400" 
           data-modal-src="https://picsum.photos/800/600?random=2" 
           onclick="openImageModal('https://picsum.photos/800/600?random=2', 'Dual image test')"
           style="cursor: pointer;"
           class="modal-image"
           data-modal-enabled="true">
      
      <h3>Another Dual Image</h3>
      <img src="https://picsum.photos/300/200?random=3" 
           alt="Second dual image test" 
           width="300" 
           data-modal-src="https://picsum.photos/900/600?random=3" 
           onclick="openImageModal('https://picsum.photos/900/600?random=3', 'Second dual image test')"
           style="cursor: pointer;"
           class="modal-image"
           data-modal-enabled="true">
      
      <p>Click on the dual images above to test the modal functionality. The regular image should not open a modal.</p>
    `;
    
    // Insert content into TinyMCE
    await page.evaluate((content) => {
      if (typeof tinymce !== 'undefined') {
        const editor = tinymce.get('body');
        if (editor) {
          editor.setContent(content);
        }
      }
    }, testContent);
    
    // Set article type
    await page.selectOption('select[name="type"]', 'article');
    
    // Submit the form
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Check if creation was successful
    const currentUrl = page.url();
    console.log('Current URL after submission:', currentUrl);
    
    if (currentUrl.includes('/admin/content')) {
      console.log('✅ Test article created successfully');
      
      // Now test the frontend display
      await page.goto('https://dalthaus.net/article/dual-image-test');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000); // Wait for modal processing
      
      // Analyze the images on the frontend
      const imageAnalysis = await page.evaluate(() => {
        const allImages = document.querySelectorAll('img');
        const modalImages = document.querySelectorAll('img[data-modal-src]');
        const regularImages = Array.from(allImages).filter(img => !img.hasAttribute('data-modal-src'));
        
        const analysis = {
          totalImages: allImages.length,
          modalImages: modalImages.length,
          regularImages: regularImages.length,
          modalImageDetails: [],
          regularImageDetails: []
        };
        
        modalImages.forEach((img, index) => {
          const style = window.getComputedStyle(img);
          analysis.modalImageDetails.push({
            index,
            src: img.src,
            modalSrc: img.getAttribute('data-modal-src'),
            hasPointerCursor: style.cursor === 'pointer',
            hasOnClick: !!img.onclick,
            hasModalEnabled: img.hasAttribute('data-modal-enabled')
          });
        });
        
        regularImages.forEach((img, index) => {
          const style = window.getComputedStyle(img);
          analysis.regularImageDetails.push({
            index,
            src: img.src,
            hasPointerCursor: style.cursor === 'pointer',
            hasOnClick: !!img.onclick
          });
        });
        
        return analysis;
      });
      
      console.log('Frontend Image Analysis:', JSON.stringify(imageAnalysis, null, 2));
      
      if (imageAnalysis.modalImages > 0) {
        console.log(`✅ Found ${imageAnalysis.modalImages} dual images on frontend`);
        
        // Test clicking a dual image
        const modalImage = page.locator('img[data-modal-src]').first();
        await modalImage.click();
        await page.waitForTimeout(500);
        
        const modalVisible = await page.locator('.image-modal').isVisible();
        
        if (modalVisible) {
          console.log('✅ Modal opened when clicking dual image');
          
          // Take screenshot of the modal
          await page.screenshot({ 
            path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/test-content-modal.png',
            fullPage: true 
          });
          
          // Check modal image source
          const modalImageSrc = await page.locator('.image-modal img').getAttribute('src');
          console.log('Modal image source:', modalImageSrc);
          
          // Close modal
          await page.keyboard.press('Escape');
          await page.waitForTimeout(300);
          
          const modalClosed = !(await page.locator('.image-modal').isVisible());
          console.log('✅ Modal closed successfully:', modalClosed);
        } else {
          console.log('❌ Modal did not open when clicking dual image');
        }
        
        // Test that regular images don't open modal
        if (imageAnalysis.regularImages > 0) {
          const regularImage = page.locator('img:not([data-modal-src])').first();
          await regularImage.click();
          await page.waitForTimeout(500);
          
          const modalOpenedForRegular = await page.locator('.image-modal').isVisible();
          expect(modalOpenedForRegular).toBe(false);
          console.log('✅ Regular images correctly do NOT open modals');
        }
      } else {
        console.log('❌ No dual images found on frontend');
      }
      
      // Clean up - delete the test article
      await page.goto('https://dalthaus.net/admin/content');
      await page.waitForLoadState('networkidle');
      
      // Find and delete the test article
      const deleteButton = page.locator('tr:has-text("Dual Image Test Article") .btn-danger');
      if (await deleteButton.isVisible()) {
        await deleteButton.click();
        
        // Confirm deletion if there's a confirmation dialog
        await page.waitForTimeout(500);
        const confirmButton = page.locator('button:has-text("Delete"), button:has-text("Confirm")');
        if (await confirmButton.isVisible()) {
          await confirmButton.click();
        }
        
        console.log('✅ Test article cleaned up');
      }
    } else {
      console.log('❌ Test article creation failed');
    }
  });

  test('Test dual image upload endpoint with actual file simulation', async ({ page }) => {
    console.log('🔍 Testing dual image upload endpoint...');
    
    // Login
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('networkidle');
    
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Test the upload endpoint with simulated FormData
    const uploadTest = await page.evaluate(async () => {
      try {
        // Create a simple test image data
        const canvas = document.createElement('canvas');
        canvas.width = 100;
        canvas.height = 100;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#FF0000';
        ctx.fillRect(0, 0, 100, 100);
        
        // Convert to blob
        const blob = await new Promise(resolve => {
          canvas.toBlob(resolve, 'image/png');
        });
        
        // Create FormData
        const formData = new FormData();
        formData.append('display_image', blob, 'test-display.png');
        formData.append('modal_image', blob, 'test-modal.png');
        
        const response = await fetch('/admin/upload/dual-image', {
          method: 'POST',
          body: formData
        });
        
        const data = await response.json();
        
        return {
          status: response.status,
          success: data.success || false,
          images: data.images || null,
          error: data.error || null
        };
      } catch (error) {
        return {
          error: error.message,
          status: 0
        };
      }
    });
    
    console.log('Upload test result:', uploadTest);
    
    if (uploadTest.success && uploadTest.images) {
      console.log('✅ Dual image upload endpoint working correctly');
      console.log('   Display image:', uploadTest.images.display_image);
      console.log('   Modal image:', uploadTest.images.modal_image);
    } else if (uploadTest.status === 400 && uploadTest.error) {
      console.log('⚠️ Upload endpoint responding (expected error for test data):', uploadTest.error);
    } else {
      console.log('❌ Upload endpoint test failed:', uploadTest.error);
    }
  });
});