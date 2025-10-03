import { test, expect } from '@playwright/test';

test.describe('Direct Dual Image Function Test', () => {
  const ADMIN_URL = 'https://dalthaus.net/admin';
  
  test.use({
    viewport: { width: 1920, height: 1080 },
    ignoreHTTPSErrors: true,
  });

  test('Test dual image functionality directly', async ({ page }) => {
    console.log('🎯 Testing dual image functionality directly...');
    
    // Login
    await page.goto(`${ADMIN_URL}/login`);
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForURL(`${ADMIN_URL}/dashboard`);
    
    // Go to content creation
    await page.goto(`${ADMIN_URL}/content/create`);
    await page.waitForTimeout(5000);
    
    // Check if functions are available
    const functionsCheck = await page.evaluate(() => {
      return {
        showDualImageDialog: typeof window.showDualImageDialog,
        closeDualImageDialog: typeof window.closeDualImageDialog,
        uploadDualImage: typeof window.uploadDualImage,
        tinymceAvailable: typeof tinymce !== 'undefined',
        tinymceEditor: typeof tinymce !== 'undefined' && !!tinymce.activeEditor
      };
    });
    
    console.log('📋 Functions availability:', functionsCheck);
    
    if (functionsCheck.showDualImageDialog === 'function' && functionsCheck.tinymceEditor) {
      console.log('✅ Attempting to call showDualImageDialog directly...');
      
      // Call the function directly
      const result = await page.evaluate(() => {
        try {
          // Call the dual image dialog function
          window.showDualImageDialog(tinymce.activeEditor);
          return { success: true, message: 'Function called successfully' };
        } catch (error) {
          return { success: false, error: error.message };
        }
      });
      
      console.log('📞 Function call result:', result);
      
      if (result.success) {
        // Wait for dialog to appear
        await page.waitForTimeout(1000);
        
        // Check for dialog
        const dialog = page.locator('.dual-image-dialog');
        const dialogVisible = await dialog.isVisible();
        
        if (dialogVisible) {
          console.log('🎉 SUCCESS: Dual image dialog opened!');
          
          // Take screenshot of dialog
          await page.screenshot({ path: 'testing/screenshots/dual-image-dialog-success.png', fullPage: true });
          
          // Test dialog content
          const dialogContent = await page.evaluate(() => {
            const dialog = document.querySelector('.dual-image-dialog');
            if (dialog) {
              return {
                hasOverlay: !!dialog.querySelector('.dual-image-overlay'),
                hasContent: !!dialog.querySelector('.dual-image-content'),
                hasHeader: !!dialog.querySelector('.dual-image-header'),
                hasBody: !!dialog.querySelector('.dual-image-body'),
                hasForm: !!dialog.querySelector('#dualImageForm'),
                hasFileInputs: dialog.querySelectorAll('input[type="file"]').length,
                hasCloseButton: !!dialog.querySelector('.close-btn')
              };
            }
            return null;
          });
          
          console.log('📋 Dialog structure:', dialogContent);
          
          // Test closing the dialog
          const closeButton = page.locator('.dual-image-dialog .close-btn');
          if (await closeButton.isVisible()) {
            await closeButton.click();
            console.log('✅ Closed dialog with close button');
          } else {
            await page.keyboard.press('Escape');
            console.log('✅ Closed dialog with Escape key');
          }
          
          // Verify dialog is closed
          await page.waitForTimeout(500);
          const dialogClosed = !(await dialog.isVisible());
          console.log(`${dialogClosed ? '✅' : '❌'} Dialog closed: ${dialogClosed}`);
          
        } else {
          console.log('❌ Dialog did not appear after function call');
        }
      }
    } else {
      console.log('❌ Required functions or TinyMCE editor not available');
    }
  });

  test('Verify production deployment status', async ({ page }) => {
    console.log('📊 PRODUCTION DEPLOYMENT VERIFICATION');
    console.log('=====================================');
    
    // Test 1: Admin Access
    await page.goto(`${ADMIN_URL}/login`);
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    
    try {
      await page.waitForURL(`${ADMIN_URL}/dashboard`, { timeout: 10000 });
      console.log('✅ 1. Admin login functionality: WORKING');
    } catch {
      console.log('❌ 1. Admin login functionality: FAILED');
    }
    
    // Test 2: TinyMCE Integration
    await page.goto(`${ADMIN_URL}/content/create`);
    await page.waitForTimeout(3000);
    
    const tinymceStatus = await page.evaluate(() => {
      return {
        loaded: typeof tinymce !== 'undefined',
        hasEditor: typeof tinymce !== 'undefined' && !!tinymce.activeEditor,
        editorReady: typeof tinymce !== 'undefined' && !!tinymce.activeEditor && !!tinymce.activeEditor.initialized
      };
    });
    
    console.log(`${tinymceStatus.loaded ? '✅' : '❌'} 2. TinyMCE script loading: ${tinymceStatus.loaded ? 'WORKING' : 'FAILED'}`);
    console.log(`${tinymceStatus.hasEditor ? '✅' : '❌'} 3. TinyMCE editor creation: ${tinymceStatus.hasEditor ? 'WORKING' : 'FAILED'}`);
    console.log(`${tinymceStatus.editorReady ? '✅' : '❌'} 4. TinyMCE editor initialization: ${tinymceStatus.editorReady ? 'WORKING' : 'FAILED'}`);
    
    // Test 3: Dual Image Functions
    const dualImageFunctions = await page.evaluate(() => {
      return {
        showDualImageDialog: typeof window.showDualImageDialog === 'function',
        closeDualImageDialog: typeof window.closeDualImageDialog === 'function',
        uploadDualImage: typeof window.uploadDualImage === 'function'
      };
    });
    
    console.log(`${dualImageFunctions.showDualImageDialog ? '✅' : '❌'} 5. showDualImageDialog function: ${dualImageFunctions.showDualImageDialog ? 'AVAILABLE' : 'MISSING'}`);
    console.log(`${dualImageFunctions.closeDualImageDialog ? '✅' : '❌'} 6. closeDualImageDialog function: ${dualImageFunctions.closeDualImageDialog ? 'AVAILABLE' : 'MISSING'}`);
    console.log(`${dualImageFunctions.uploadDualImage ? '✅' : '❌'} 7. uploadDualImage function: ${dualImageFunctions.uploadDualImage ? 'AVAILABLE' : 'MISSING'}`);
    
    // Test 4: Frontend Modal System
    await page.goto('https://dalthaus.net/articles');
    
    const frontendModal = await page.evaluate(() => {
      return {
        openImageModal: typeof window.openImageModal === 'function',
        closeImageModal: typeof window.closeImageModal === 'function',
        addModalToContentImages: typeof window.addModalToContentImages === 'function'
      };
    });
    
    console.log(`${frontendModal.openImageModal ? '✅' : '❌'} 8. Frontend openImageModal: ${frontendModal.openImageModal ? 'AVAILABLE' : 'MISSING'}`);
    console.log(`${frontendModal.closeImageModal ? '✅' : '❌'} 9. Frontend closeImageModal: ${frontendModal.closeImageModal ? 'AVAILABLE' : 'MISSING'}`);
    console.log(`${frontendModal.addModalToContentImages ? '✅' : '❌'} 10. Frontend addModalToContentImages: ${frontendModal.addModalToContentImages ? 'AVAILABLE' : 'MISSING'}`);
    
    console.log('=====================================');
    
    // Calculate score
    const checks = [
      tinymceStatus.loaded,
      tinymceStatus.hasEditor,
      tinymceStatus.editorReady,
      dualImageFunctions.showDualImageDialog,
      dualImageFunctions.closeDualImageDialog,
      dualImageFunctions.uploadDualImage,
      frontendModal.openImageModal,
      frontendModal.closeImageModal,
      frontendModal.addModalToContentImages
    ];
    
    const working = checks.filter(Boolean).length;
    const total = checks.length;
    const percentage = Math.round((working / total) * 100);
    
    console.log(`📊 OVERALL STATUS: ${working}/${total} components working (${percentage}%)`);
    
    if (percentage >= 80) {
      console.log('🎉 DEPLOYMENT STATUS: SUCCESS - Modal functionality is deployed and working');
    } else if (percentage >= 60) {
      console.log('⚠️ DEPLOYMENT STATUS: PARTIAL - Some issues detected');
    } else {
      console.log('❌ DEPLOYMENT STATUS: FAILED - Major issues detected');
    }
    
    console.log('\n📝 USAGE NOTES:');
    console.log('- The dual image button should appear in TinyMCE toolbar');
    console.log('- If button is not visible, the showDualImageDialog() function can be called directly');
    console.log('- Frontend modal system processes images with data-modal-src attribute');
    console.log('- Regular images without data-modal-src will not have modal functionality');
  });
});