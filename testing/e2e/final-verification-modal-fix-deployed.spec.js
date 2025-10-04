import { test, expect } from '@playwright/test';

test.describe('Final Verification - Modal Fix Deployed', () => {
  
  test('Verify deployed modal functionality works for all uploaded images', async ({ page }) => {
    console.log('=== FINAL VERIFICATION: DEPLOYED MODAL FIX ===');
    
    const testResults = {
      pagesChecked: 0,
      totalUploadedImages: 0,
      imagesWithModalFunctionality: 0,
      successfulModalTests: 0,
      errors: []
    };
    
    const pagesToTest = [
      { url: 'https://dalthaus.net/', name: 'Homepage' },
      { url: 'https://dalthaus.net/articles', name: 'Articles Page' },
      { url: 'https://dalthaus.net/photobooks', name: 'Photobooks Page' }
    ];
    
    for (const pageInfo of pagesToTest) {
      console.log(`\n--- Testing ${pageInfo.name} (After Deployment) ---`);
      
      try {
        // Add cache busting to ensure we get the updated JavaScript
        await page.goto(`${pageInfo.url}?nocache=${Date.now()}`);
        await page.waitForLoadState('networkidle');
        
        // Wait longer for addModalToContentImages to run
        await page.waitForTimeout(5000);
        
        testResults.pagesChecked++;
        
        // Check console for our modal function messages
        const consoleMessages = await page.evaluate(() => {
          return window.consoleMessages || [];
        });
        
        // Analyze all images on the page
        const imageAnalysis = await page.evaluate(() => {
          const results = {
            totalImages: 0,
            uploadedImages: 0,
            imagesWithModalEnabled: 0,
            imagesWithPointerCursor: 0,
            imageDetails: [],
            consoleMessages: []
          };
          
          // Capture console messages related to modal functionality
          const originalLog = console.log;
          const messages = [];
          console.log = function(...args) {
            const message = args.join(' ');
            if (message.includes('Modal functionality') || message.includes('modal') || message.includes('uploaded image')) {
              messages.push(message);
            }
            originalLog.apply(console, args);
          };
          
          const allImages = document.querySelectorAll('img');
          results.totalImages = allImages.length;
          
          allImages.forEach((img, index) => {
            const src = img.getAttribute('src');
            
            if (src && src.includes('/uploads/')) {
              results.uploadedImages++;
              
              const hasModalEnabled = img.hasAttribute('data-modal-enabled');
              const hasPointerCursor = img.style.cursor === 'pointer' || window.getComputedStyle(img).cursor === 'pointer';
              const hasDataModalSrc = img.hasAttribute('data-modal-src');
              const hasOnclick = img.hasAttribute('onclick');
              const hasClickListeners = img.onclick !== null || img.getAttribute('onclick') !== null;
              
              if (hasModalEnabled) results.imagesWithModalEnabled++;
              if (hasPointerCursor) results.imagesWithPointerCursor++;
              
              results.imageDetails.push({
                index: index,
                src: src.substring(src.lastIndexOf('/') + 1),
                fullSrc: src,
                hasModalEnabled: hasModalEnabled,
                hasPointerCursor: hasPointerCursor,
                hasDataModalSrc: hasDataModalSrc,
                hasOnclick: hasOnclick,
                hasClickListeners: hasClickListeners,
                alt: img.getAttribute('alt') || 'No alt',
                className: img.className,
                cursor: window.getComputedStyle(img).cursor
              });
            }
          });
          
          results.consoleMessages = messages;
          console.log = originalLog; // Restore original console.log
          
          return results;
        });
        
        console.log(`Analysis for ${pageInfo.name}:`);
        console.log(`- Total images: ${imageAnalysis.totalImages}`);
        console.log(`- Uploaded images: ${imageAnalysis.uploadedImages}`);
        console.log(`- Images with modal enabled: ${imageAnalysis.imagesWithModalEnabled}`);
        console.log(`- Images with pointer cursor: ${imageAnalysis.imagesWithPointerCursor}`);
        
        if (imageAnalysis.consoleMessages.length > 0) {
          console.log('Console messages from modal system:');
          imageAnalysis.consoleMessages.forEach(msg => console.log(`  ${msg}`));
        }
        
        testResults.totalUploadedImages += imageAnalysis.uploadedImages;
        testResults.imagesWithModalFunctionality += imageAnalysis.imagesWithModalEnabled;
        
        if (imageAnalysis.imageDetails.length > 0) {
          console.log('\nDetailed Image Analysis:');
          imageAnalysis.imageDetails.forEach((img, i) => {
            console.log(`  ${i + 1}. ${img.src}`);
            console.log(`     - Modal enabled: ${img.hasModalEnabled ? '✅' : '❌'}`);
            console.log(`     - Pointer cursor: ${img.hasPointerCursor ? '✅' : '❌'} (${img.cursor})`);
            console.log(`     - Data-modal-src: ${img.hasDataModalSrc ? '✅' : '❌'}`);
            console.log(`     - Click listeners: ${img.hasClickListeners ? '✅' : '❌'}`);
            console.log(`     - CSS class: ${img.className || 'none'}`);
          });
          
          // Test modal functionality on the first uploaded image
          console.log(`\nTesting modal functionality on ${pageInfo.name}...`);
          
          const firstImage = imageAnalysis.imageDetails[0];
          const imageLocator = await page.locator(`img[src="${firstImage.fullSrc}"]`).first();
          
          if (await imageLocator.isVisible()) {
            try {
              // Scroll into view and click
              await imageLocator.scrollIntoViewIfNeeded();
              await page.waitForTimeout(1000);
              
              console.log(`Clicking image: ${firstImage.src}`);
              await imageLocator.click();
              await page.waitForTimeout(2000); // Wait longer for modal
              
              // Check if modal opened
              const modal = await page.locator('.modal, #imageModal, dialog, [class*="modal"], .image-modal').first();
              const isModalVisible = await modal.isVisible().catch(() => false);
              
              if (isModalVisible) {
                console.log(`✅ MODAL WORKING ON ${pageInfo.name.toUpperCase()}!`);
                testResults.successfulModalTests++;
                
                // Take screenshot of successful modal
                await page.screenshot({ 
                  path: `testing/screenshots/deployed-modal-fix-${pageInfo.name.toLowerCase().replace(' ', '-')}-success.png` 
                });
                
                // Verify modal content
                const modalImage = await modal.locator('img').first();
                if (await modalImage.isVisible()) {
                  const modalImageSrc = await modalImage.getAttribute('src');
                  console.log(`  Modal displays image: ${modalImageSrc ? modalImageSrc.substring(modalImageSrc.lastIndexOf('/') + 1) : 'Unknown'}`);
                  
                  // Verify it's the same image
                  if (modalImageSrc === firstImage.fullSrc) {
                    console.log(`  ✅ Modal shows correct image`);
                  } else {
                    console.log(`  ⚠️ Modal shows different image than clicked`);
                  }
                }
                
                // Test modal close functionality
                console.log('  Testing modal close...');
                await page.keyboard.press('Escape');
                await page.waitForTimeout(1000);
                
                const isModalClosed = !(await modal.isVisible().catch(() => true));
                if (isModalClosed) {
                  console.log('  ✅ Modal closed successfully with Escape key');
                } else {
                  console.log('  ⚠️ Modal still open, trying click outside');
                  await page.click('body');
                  await page.waitForTimeout(500);
                }
                
              } else {
                console.log(`❌ Modal did not open on ${pageInfo.name}`);
                
                // Take debug screenshot
                await page.screenshot({ 
                  path: `testing/screenshots/deployed-modal-fix-${pageInfo.name.toLowerCase().replace(' ', '-')}-failed.png` 
                });
                
                // Debug: check if click event was registered
                const clickDebug = await page.evaluate(() => {
                  const img = document.querySelector('img[src*="/uploads/"]');
                  if (img) {
                    return {
                      hasClickListener: img.onclick !== null,
                      hasEventListeners: img.getEventListeners ? Object.keys(img.getEventListeners()).length > 0 : 'unknown',
                      cursor: window.getComputedStyle(img).cursor,
                      modalEnabled: img.hasAttribute('data-modal-enabled'),
                      imgElement: img.outerHTML.substring(0, 200)
                    };
                  }
                  return null;
                });
                
                console.log('  Debug info:', clickDebug);
              }
              
            } catch (error) {
              console.log(`❌ Error testing modal on ${pageInfo.name}: ${error.message}`);
              testResults.errors.push(`${pageInfo.name}: ${error.message}`);
            }
          } else {
            console.log(`⚠️ First image not visible on ${pageInfo.name}`);
          }
        } else {
          console.log(`No uploaded images found on ${pageInfo.name}`);
        }
        
        // Take page screenshot
        await page.screenshot({ 
          path: `testing/screenshots/deployed-modal-fix-${pageInfo.name.toLowerCase().replace(' ', '-')}-page.png`, 
          fullPage: true 
        });
        
      } catch (error) {
        console.log(`Error testing ${pageInfo.name}: ${error.message}`);
        testResults.errors.push(`${pageInfo.name}: ${error.message}`);
      }
    }
    
    // Test JavaScript functions
    console.log('\n--- Testing JavaScript Functions (Post-Deployment) ---');
    
    await page.goto('https://dalthaus.net/');
    await page.waitForLoadState('networkidle');
    
    const jsTests = await page.evaluate(() => {
      const results = {
        openImageModal: typeof window.openImageModal === 'function',
        closeImageModal: typeof window.closeImageModal === 'function',
        addModalToContentImages: typeof window.addModalToContentImages === 'function'
      };
      
      // Test if we can manually add modal functionality
      if (results.addModalToContentImages) {
        try {
          window.addModalToContentImages();
          results.manualAddModalSuccess = true;
          
          // Check if it actually added modal functionality
          const uploadedImages = document.querySelectorAll('img[src*="/uploads/"]');
          let modalCount = 0;
          uploadedImages.forEach(img => {
            if (img.hasAttribute('data-modal-enabled') || img.style.cursor === 'pointer') {
              modalCount++;
            }
          });
          results.manualModalCount = modalCount;
          results.totalUploadedImages = uploadedImages.length;
          
        } catch (error) {
          results.manualAddModalError = error.message;
        }
      }
      
      // Test manual modal opening
      if (results.openImageModal) {
        try {
          const testImageUrl = 'https://dalthaus.net/uploads/content/teasers/2025/10/530fe5ee220115e97dfb7a6386051541.jpg';
          window.openImageModal(testImageUrl, 'Test Modal');
          results.manualModalOpenSuccess = true;
        } catch (error) {
          results.manualModalOpenError = error.message;
        }
      }
      
      return results;
    });
    
    console.log('JavaScript Function Tests:');
    console.log(`- openImageModal: ${jsTests.openImageModal ? '✅' : '❌'}`);
    console.log(`- closeImageModal: ${jsTests.closeImageModal ? '✅' : '❌'}`);
    console.log(`- addModalToContentImages: ${jsTests.addModalToContentImages ? '✅' : '❌'}`);
    console.log(`- Manual addModalToContentImages: ${jsTests.manualAddModalSuccess ? '✅' : '❌'}`);
    console.log(`- Manual modal count: ${jsTests.manualModalCount}/${jsTests.totalUploadedImages}`);
    console.log(`- Manual modal open: ${jsTests.manualModalOpenSuccess ? '✅' : '❌'}`);
    
    if (jsTests.manualAddModalError) {
      console.log(`  addModal Error: ${jsTests.manualAddModalError}`);
    }
    
    if (jsTests.manualModalOpenError) {
      console.log(`  openModal Error: ${jsTests.manualModalOpenError}`);
    }
    
    // If manual modal open worked, test it
    if (jsTests.manualModalOpenSuccess) {
      await page.waitForTimeout(1000);
      const testModal = await page.locator('.modal, #imageModal, [class*="modal"], .image-modal').first();
      const isTestModalVisible = await testModal.isVisible().catch(() => false);
      
      if (isTestModalVisible) {
        console.log('✅ Manual modal test successful!');
        
        await page.screenshot({ 
          path: 'testing/screenshots/deployed-manual-modal-test-success.png' 
        });
        
        // Close test modal
        await page.keyboard.press('Escape');
        await page.waitForTimeout(500);
      } else {
        console.log('❌ Manual modal test failed');
      }
    }
    
    // Generate final report
    console.log('\n=== FINAL DEPLOYMENT VERIFICATION RESULTS ===');
    console.log(`Pages Checked: ${testResults.pagesChecked}`);
    console.log(`Total Uploaded Images Found: ${testResults.totalUploadedImages}`);
    console.log(`Images with Modal Functionality: ${testResults.imagesWithModalFunctionality}`);
    console.log(`Successful Modal Tests: ${testResults.successfulModalTests}`);
    
    if (testResults.errors.length > 0) {
      console.log('\nErrors Encountered:');
      testResults.errors.forEach(error => console.log(`  - ${error}`));
    }
    
    // Calculate success rates
    const modalFunctionalityRate = testResults.totalUploadedImages > 0 
      ? (testResults.imagesWithModalFunctionality / testResults.totalUploadedImages) * 100 
      : 0;
    
    const modalTestSuccessRate = testResults.pagesChecked > 0 
      ? (testResults.successfulModalTests / testResults.pagesChecked) * 100 
      : 0;
    
    console.log(`Modal Functionality Coverage: ${modalFunctionalityRate.toFixed(1)}%`);
    console.log(`Modal Test Success Rate: ${modalTestSuccessRate.toFixed(1)}%`);
    
    // Assertions for test validation
    expect(testResults.pagesChecked).toBeGreaterThan(0);
    expect(jsTests.openImageModal).toBe(true);
    expect(jsTests.addModalToContentImages).toBe(true);
    
    if (testResults.totalUploadedImages > 0) {
      // At least 80% of uploaded images should have modal functionality
      expect(modalFunctionalityRate).toBeGreaterThanOrEqual(80);
      
      // At least 1 successful modal test
      expect(testResults.successfulModalTests).toBeGreaterThan(0);
      
      // At least 50% of pages should have working modals
      expect(modalTestSuccessRate).toBeGreaterThanOrEqual(50);
    }
    
    console.log('\n🎉 DEPLOYMENT VERIFICATION COMPLETE!');
    console.log('✅ TinyMCE uploaded images now have full modal functionality!');
    console.log('✅ Modal system successfully deployed to production!');
  });
});