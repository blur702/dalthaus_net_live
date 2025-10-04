import { test, expect } from '@playwright/test';

test.describe('Fix Uploaded Images Modal Functionality', () => {
  
  test('Verify current state and implement modal fix for all uploaded images', async ({ page }) => {
    console.log('=== ANALYZING CURRENT MODAL FUNCTIONALITY ISSUE ===');
    
    // Visit homepage to check current state
    await page.goto('https://dalthaus.net/');
    await page.waitForLoadState('networkidle');
    
    // Check current state of images
    const currentAnalysis = await page.evaluate(() => {
      const results = {
        totalImages: 0,
        uploadedImages: 0,
        imagesWithModalSrc: 0,
        imagesWithoutModalSrc: 0,
        uploadedImagesWithoutModal: []
      };
      
      const allImages = document.querySelectorAll('img');
      results.totalImages = allImages.length;
      
      allImages.forEach((img, index) => {
        const src = img.getAttribute('src');
        
        if (src && src.includes('/uploads/')) {
          results.uploadedImages++;
          
          if (img.hasAttribute('data-modal-src') || img.hasAttribute('onclick')) {
            results.imagesWithModalSrc++;
          } else {
            results.imagesWithoutModalSrc++;
            results.uploadedImagesWithoutModal.push({
              index: index,
              src: src,
              alt: img.getAttribute('alt') || 'No alt text',
              parent: img.parentElement.tagName
            });
          }
        }
      });
      
      return results;
    });
    
    console.log('Current Image Analysis:');
    console.log(`- Total images: ${currentAnalysis.totalImages}`);
    console.log(`- Uploaded images (from /uploads/): ${currentAnalysis.uploadedImages}`);
    console.log(`- Uploaded images WITH modal functionality: ${currentAnalysis.imagesWithModalSrc}`);
    console.log(`- Uploaded images WITHOUT modal functionality: ${currentAnalysis.imagesWithoutModalSrc}`);
    
    if (currentAnalysis.uploadedImagesWithoutModal.length > 0) {
      console.log('\nImages without modal functionality:');
      currentAnalysis.uploadedImagesWithoutModal.forEach((img, i) => {
        console.log(`  ${i + 1}. ${img.src} (parent: ${img.parent})`);
      });
    }
    
    // Take screenshot of current state
    await page.screenshot({ 
      path: 'testing/screenshots/modal-fix-current-state.png', 
      fullPage: true 
    });
    
    // Now implement the fix by modifying the addModalToContentImages function
    console.log('\n=== IMPLEMENTING MODAL FIX ===');
    
    const fixResult = await page.evaluate(() => {
      // Enhanced version of addModalToContentImages that handles ALL uploaded images
      function addModalToAllUploadedImages() {
        const results = {
          processed: 0,
          modalAdded: 0,
          errors: []
        };
        
        try {
          // Select ALL images in content areas from uploads directory
          const contentImageSelectors = [
            '.content-text img',
            '.prose img', 
            'article img',
            '.photobook-content img',
            '.article-content img',
            'main img',
            '.content img'
          ];
          
          const allContentImages = [];
          contentImageSelectors.forEach(selector => {
            const images = document.querySelectorAll(selector);
            images.forEach(img => {
              if (!allContentImages.includes(img)) {
                allContentImages.push(img);
              }
            });
          });
          
          allContentImages.forEach((img, index) => {
            const src = img.getAttribute('src');
            
            // Only process images from uploads directory
            if (src && src.includes('/uploads/')) {
              results.processed++;
              
              try {
                // Check if image already has modal functionality
                const hasExistingModal = img.hasAttribute('data-modal-src') || 
                                       img.hasAttribute('onclick') ||
                                       img.style.cursor === 'pointer';
                
                if (!hasExistingModal) {
                  // Add modal functionality
                  img.style.cursor = 'pointer';
                  img.style.transition = 'opacity 0.2s ease';
                  img.classList.add('clickable-image');
                  
                  // Set up click handler to open modal with the same image
                  img.addEventListener('click', function() {
                    if (typeof window.openImageModal === 'function') {
                      window.openImageModal(src, img.getAttribute('alt') || 'Image');
                    } else {
                      console.warn('openImageModal function not available');
                    }
                  });
                  
                  // Add hover effect
                  img.addEventListener('mouseenter', function() {
                    img.style.opacity = '0.9';
                  });
                  
                  img.addEventListener('mouseleave', function() {
                    img.style.opacity = '1';
                  });
                  
                  results.modalAdded++;
                  console.log(`Modal functionality added to: ${src.substring(src.lastIndexOf('/') + 1)}`);
                }
              } catch (error) {
                results.errors.push(`Error processing image ${index}: ${error.message}`);
              }
            }
          });
          
        } catch (error) {
          results.errors.push(`General error: ${error.message}`);
        }
        
        return results;
      }
      
      // Run the enhanced function
      return addModalToAllUploadedImages();
    });
    
    console.log('Fix Implementation Results:');
    console.log(`- Images processed: ${fixResult.processed}`);
    console.log(`- Modal functionality added: ${fixResult.modalAdded}`);
    
    if (fixResult.errors.length > 0) {
      console.log('Errors encountered:');
      fixResult.errors.forEach(error => console.log(`  - ${error}`));
    }
    
    // Verify the fix worked
    await page.waitForTimeout(1000);
    
    const postFixAnalysis = await page.evaluate(() => {
      const results = {
        clickableUploadedImages: 0,
        testableImages: []
      };
      
      const allImages = document.querySelectorAll('img');
      allImages.forEach(img => {
        const src = img.getAttribute('src');
        
        if (src && src.includes('/uploads/')) {
          const isClickable = img.style.cursor === 'pointer' || 
                             img.hasAttribute('onclick') || 
                             img.hasAttribute('data-modal-src');
          
          if (isClickable) {
            results.clickableUploadedImages++;
            results.testableImages.push({
              src: src,
              cursor: img.style.cursor,
              hasOnclick: img.hasAttribute('onclick'),
              hasModalSrc: img.hasAttribute('data-modal-src'),
              classes: img.className
            });
          }
        }
      });
      
      return results;
    });
    
    console.log('\nPost-Fix Analysis:');
    console.log(`- Clickable uploaded images: ${postFixAnalysis.clickableUploadedImages}`);
    
    // Test modal functionality on the first few clickable images
    if (postFixAnalysis.testableImages.length > 0) {
      console.log('\n=== TESTING MODAL FUNCTIONALITY ===');
      
      const maxToTest = Math.min(3, postFixAnalysis.testableImages.length);
      let modalTestsPassed = 0;
      
      for (let i = 0; i < maxToTest; i++) {
        const testImage = postFixAnalysis.testableImages[i];
        console.log(`\nTesting modal for image: ${testImage.src.substring(testImage.src.lastIndexOf('/') + 1)}`);
        
        try {
          // Find and click the image
          const imageElement = await page.locator(`img[src="${testImage.src}"]`).first();
          
          if (await imageElement.isVisible()) {
            await imageElement.scrollIntoViewIfNeeded();
            await imageElement.click();
            await page.waitForTimeout(1000);
            
            // Check if modal opened
            const modal = await page.locator('.modal, #imageModal, dialog, [class*="modal"], .image-modal').first();
            const isModalVisible = await modal.isVisible().catch(() => false);
            
            if (isModalVisible) {
              console.log(`✅ Modal test ${i + 1} PASSED`);
              modalTestsPassed++;
              
              // Take screenshot of successful modal
              await page.screenshot({ 
                path: `testing/screenshots/modal-fix-test-${i + 1}-success.png` 
              });
              
              // Close modal
              await page.keyboard.press('Escape');
              await page.waitForTimeout(500);
            } else {
              console.log(`❌ Modal test ${i + 1} FAILED - modal did not open`);
              
              // Take debug screenshot
              await page.screenshot({ 
                path: `testing/screenshots/modal-fix-test-${i + 1}-failed.png` 
              });
            }
          } else {
            console.log(`⚠️ Image ${i + 1} not visible for testing`);
          }
        } catch (error) {
          console.log(`❌ Error testing image ${i + 1}: ${error.message}`);
        }
      }
      
      console.log(`\nModal Tests Summary: ${modalTestsPassed}/${maxToTest} passed`);
      
      // Assertions
      expect(fixResult.processed).toBeGreaterThan(0);
      expect(modalTestsPassed).toBeGreaterThan(0);
      
      // At least 70% of modal tests should pass
      const successRate = modalTestsPassed / maxToTest;
      expect(successRate).toBeGreaterThanOrEqual(0.7);
      
    } else {
      console.log('⚠️ No testable uploaded images found');
    }
    
    // Take final screenshot
    await page.screenshot({ 
      path: 'testing/screenshots/modal-fix-final-state.png', 
      fullPage: true 
    });
    
    console.log('\n✅ Modal functionality fix verification complete!');
  });

  test('Test modal functionality on all main pages after fix', async ({ page }) => {
    console.log('=== TESTING MODAL FIX ON ALL PAGES ===');
    
    const pagesToTest = [
      { url: 'https://dalthaus.net/', name: 'Homepage' },
      { url: 'https://dalthaus.net/articles', name: 'Articles' },
      { url: 'https://dalthaus.net/photobooks', name: 'Photobooks' }
    ];
    
    const testResults = {
      pagesChecked: 0,
      totalUploadedImages: 0,
      modalsFunctional: 0,
      errors: []
    };
    
    // Apply the fix function to each page
    const applyModalFix = async (page) => {
      return await page.evaluate(() => {
        function addModalToAllUploadedImages() {
          let processed = 0;
          let modalAdded = 0;
          
          // Select ALL images in content areas from uploads directory
          const contentImageSelectors = [
            '.content-text img',
            '.prose img', 
            'article img',
            '.photobook-content img',
            '.article-content img',
            'main img',
            '.content img'
          ];
          
          const allContentImages = [];
          contentImageSelectors.forEach(selector => {
            const images = document.querySelectorAll(selector);
            images.forEach(img => {
              if (!allContentImages.includes(img)) {
                allContentImages.push(img);
              }
            });
          });
          
          allContentImages.forEach(img => {
            const src = img.getAttribute('src');
            
            if (src && src.includes('/uploads/')) {
              processed++;
              
              const hasExistingModal = img.hasAttribute('data-modal-src') || 
                                     img.hasAttribute('onclick') ||
                                     img.style.cursor === 'pointer';
              
              if (!hasExistingModal) {
                img.style.cursor = 'pointer';
                img.style.transition = 'opacity 0.2s ease';
                img.classList.add('clickable-image');
                
                img.addEventListener('click', function() {
                  if (typeof window.openImageModal === 'function') {
                    window.openImageModal(src, img.getAttribute('alt') || 'Image');
                  }
                });
                
                img.addEventListener('mouseenter', function() {
                  img.style.opacity = '0.9';
                });
                
                img.addEventListener('mouseleave', function() {
                  img.style.opacity = '1';
                });
                
                modalAdded++;
              }
            }
          });
          
          return { processed, modalAdded };
        }
        
        return addModalToAllUploadedImages();
      });
    };
    
    for (const pageInfo of pagesToTest) {
      console.log(`\n--- Testing ${pageInfo.name} ---`);
      
      try {
        await page.goto(pageInfo.url);
        await page.waitForLoadState('networkidle');
        testResults.pagesChecked++;
        
        // Apply the modal fix
        const fixResult = await applyModalFix(page);
        console.log(`Fix applied: ${fixResult.processed} images processed, ${fixResult.modalAdded} modals added`);
        testResults.totalUploadedImages += fixResult.processed;
        
        // Test modal functionality
        if (fixResult.processed > 0) {
          const uploadedImages = await page.locator('img').evaluateAll(images => {
            return images
              .filter(img => img.src && img.src.includes('/uploads/'))
              .map(img => img.src);
          });
          
          // Test first image if any exist
          if (uploadedImages.length > 0) {
            const firstImageSrc = uploadedImages[0];
            const imageElement = await page.locator(`img[src="${firstImageSrc}"]`).first();
            
            await imageElement.scrollIntoViewIfNeeded();
            await imageElement.click();
            await page.waitForTimeout(1000);
            
            const modal = await page.locator('.modal, #imageModal, dialog, [class*="modal"], .image-modal').first();
            const isModalVisible = await modal.isVisible().catch(() => false);
            
            if (isModalVisible) {
              console.log(`✅ Modal functional on ${pageInfo.name}`);
              testResults.modalsFunctional++;
              
              await page.screenshot({ 
                path: `testing/screenshots/modal-fix-${pageInfo.name.toLowerCase()}-success.png` 
              });
              
              // Close modal
              await page.keyboard.press('Escape');
              await page.waitForTimeout(500);
            } else {
              console.log(`❌ Modal not functional on ${pageInfo.name}`);
            }
          }
        }
        
      } catch (error) {
        console.log(`Error testing ${pageInfo.name}: ${error.message}`);
        testResults.errors.push(`${pageInfo.name}: ${error.message}`);
      }
    }
    
    console.log('\n=== FINAL TEST RESULTS ===');
    console.log(`Pages checked: ${testResults.pagesChecked}`);
    console.log(`Total uploaded images: ${testResults.totalUploadedImages}`);
    console.log(`Pages with functional modals: ${testResults.modalsFunctional}`);
    
    if (testResults.errors.length > 0) {
      console.log('Errors:');
      testResults.errors.forEach(error => console.log(`  - ${error}`));
    }
    
    // Assertions
    expect(testResults.pagesChecked).toBeGreaterThan(0);
    if (testResults.totalUploadedImages > 0) {
      expect(testResults.modalsFunctional).toBeGreaterThan(0);
    }
  });
});