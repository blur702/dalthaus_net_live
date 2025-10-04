import { test, expect } from '@playwright/test';

test.describe('TinyMCE Uploaded Images Modal Verification', () => {
  
  test('Verify modal functionality on TinyMCE uploaded images - Complete End-to-End Test', async ({ page }) => {
    console.log('=== STARTING COMPLETE TINYMCE MODAL VERIFICATION ===');
    
    const testResults = {
      pagesChecked: 0,
      imagesFound: 0,
      modalsFunctional: 0,
      nonFunctional: 0,
      errors: []
    };
    
    // Pages to check for TinyMCE uploaded images
    const pagesToCheck = [
      { url: 'https://dalthaus.net/', name: 'Homepage' },
      { url: 'https://dalthaus.net/articles', name: 'Articles Page' },
      { url: 'https://dalthaus.net/photobooks', name: 'Photobooks Page' }
    ];
    
    for (const pageInfo of pagesToCheck) {
      console.log(`\n--- Testing ${pageInfo.name} ---`);
      
      try {
        await page.goto(pageInfo.url);
        await page.waitForLoadState('networkidle');
        testResults.pagesChecked++;
        
        // Take screenshot of initial page state
        await page.screenshot({ 
          path: `testing/screenshots/tinymce-modal-${pageInfo.name.toLowerCase().replace(' ', '-')}-initial.png`, 
          fullPage: true 
        });
        
        // Look for images that should have been uploaded via TinyMCE
        // These will have data-modal-src attributes or onclick handlers
        const tinymceImages = await page.locator('img[data-modal-src], img[onclick*="openImageModal"]').all();
        console.log(`Found ${tinymceImages.length} TinyMCE images with modal attributes on ${pageInfo.name}`);
        testResults.imagesFound += tinymceImages.length;
        
        if (tinymceImages.length > 0) {
          for (let i = 0; i < tinymceImages.length; i++) {
            const image = tinymceImages[i];
            
            try {
              // Get image details
              const src = await image.getAttribute('src');
              const modalSrc = await image.getAttribute('data-modal-src');
              const onclick = await image.getAttribute('onclick');
              const alt = await image.getAttribute('alt');
              
              console.log(`\nTesting Image ${i + 1}:`);
              console.log(`  - src: ${src}`);
              console.log(`  - data-modal-src: ${modalSrc}`);
              console.log(`  - onclick: ${onclick}`);
              console.log(`  - alt: ${alt}`);
              
              // Verify image is in uploads directory (indicates TinyMCE upload)
              const isTinyMCEImage = src && (src.includes('/uploads/') || src.includes('dalthaus.net/uploads/'));
              if (!isTinyMCEImage) {
                console.log(`  - Skipping: Not a TinyMCE uploaded image`);
                continue;
              }
              
              // Check if image has cursor pointer (indicates clickable)
              const cursor = await image.evaluate(el => window.getComputedStyle(el).cursor);
              console.log(`  - cursor style: ${cursor}`);
              
              // Verify image is visible and clickable
              const isVisible = await image.isVisible();
              const isEnabled = await image.isEnabled();
              console.log(`  - visible: ${isVisible}, enabled: ${isEnabled}`);
              
              if (!isVisible || !isEnabled) {
                console.log(`  - Skipping: Image not visible or enabled`);
                testResults.nonFunctional++;
                continue;
              }
              
              // Click the image to test modal functionality
              console.log(`  - Clicking image to test modal...`);
              await image.click();
              await page.waitForTimeout(1000); // Give modal time to open
              
              // Check if modal opened
              const modal = await page.locator('.modal, #imageModal, [class*="modal"], dialog').first();
              const isModalVisible = await modal.isVisible().catch(() => false);
              
              if (isModalVisible) {
                console.log(`  ✅ Modal opened successfully!`);
                testResults.modalsFunctional++;
                
                // Take screenshot of opened modal
                await page.screenshot({ 
                  path: `testing/screenshots/tinymce-modal-${pageInfo.name.toLowerCase().replace(' ', '-')}-image-${i + 1}-open.png` 
                });
                
                // Verify modal content
                const modalImage = await modal.locator('img').first();
                const modalImageSrc = await modalImage.getAttribute('src').catch(() => null);
                console.log(`  - Modal image src: ${modalImageSrc}`);
                
                // Test close functionality
                console.log(`  - Testing modal close functionality...`);
                
                // Try close button first
                const closeButton = await page.locator('.modal-close, .close, [onclick*="closeModal"], button[aria-label="Close"]').first();
                if (await closeButton.isVisible().catch(() => false)) {
                  await closeButton.click();
                  await page.waitForTimeout(500);
                  console.log(`  - Closed with close button`);
                } else {
                  // Try Escape key
                  await page.keyboard.press('Escape');
                  await page.waitForTimeout(500);
                  console.log(`  - Attempted close with Escape key`);
                }
                
                // Verify modal is closed
                const isModalClosed = !(await modal.isVisible().catch(() => true));
                if (isModalClosed) {
                  console.log(`  ✅ Modal closed successfully`);
                } else {
                  console.log(`  ⚠️ Modal may still be open`);
                  // Force close by clicking outside
                  await page.click('body');
                  await page.waitForTimeout(500);
                }
                
              } else {
                console.log(`  ❌ Modal did not open`);
                testResults.nonFunctional++;
                
                // Take screenshot for debugging
                await page.screenshot({ 
                  path: `testing/screenshots/tinymce-modal-${pageInfo.name.toLowerCase().replace(' ', '-')}-image-${i + 1}-failed.png` 
                });
              }
              
            } catch (error) {
              console.log(`  ❌ Error testing image ${i + 1}: ${error.message}`);
              testResults.errors.push(`${pageInfo.name} Image ${i + 1}: ${error.message}`);
              testResults.nonFunctional++;
            }
          }
        } else {
          console.log(`No TinyMCE images with modal attributes found on ${pageInfo.name}`);
        }
        
        // Check individual content pages (articles/photobooks)
        if (pageInfo.name.includes('Articles') || pageInfo.name.includes('Photobooks')) {
          const contentLinks = await page.locator('a[href*="/article/"], a[href*="/photobook/"]').all();
          
          if (contentLinks.length > 0) {
            console.log(`\nChecking individual ${pageInfo.name.toLowerCase()} for TinyMCE images...`);
            
            // Test first few individual pages
            const maxToTest = Math.min(3, contentLinks.length);
            for (let j = 0; j < maxToTest; j++) {
              try {
                const link = contentLinks[j];
                const href = await link.getAttribute('href');
                console.log(`\nTesting individual content: ${href}`);
                
                await link.click();
                await page.waitForLoadState('networkidle');
                
                // Take screenshot
                await page.screenshot({ 
                  path: `testing/screenshots/tinymce-modal-individual-${j + 1}.png`, 
                  fullPage: true 
                });
                
                const individualImages = await page.locator('img[data-modal-src], img[onclick*="openImageModal"]').all();
                console.log(`Found ${individualImages.length} TinyMCE images in individual content`);
                testResults.imagesFound += individualImages.length;
                
                if (individualImages.length > 0) {
                  // Test first image in individual content
                  const firstImage = individualImages[0];
                  const src = await firstImage.getAttribute('src');
                  
                  if (src && src.includes('/uploads/')) {
                    console.log(`Testing individual content image: ${src}`);
                    
                    await firstImage.click();
                    await page.waitForTimeout(1000);
                    
                    const modal = await page.locator('.modal, #imageModal, [class*="modal"], dialog').first();
                    const isModalVisible = await modal.isVisible().catch(() => false);
                    
                    if (isModalVisible) {
                      console.log(`✅ Individual content modal working!`);
                      testResults.modalsFunctional++;
                      
                      await page.screenshot({ 
                        path: `testing/screenshots/tinymce-modal-individual-${j + 1}-open.png` 
                      });
                      
                      // Close modal
                      await page.keyboard.press('Escape');
                      await page.waitForTimeout(500);
                    } else {
                      console.log(`❌ Individual content modal failed`);
                      testResults.nonFunctional++;
                    }
                  }
                }
                
                // Go back to listing page
                await page.goBack();
                await page.waitForLoadState('networkidle');
                
              } catch (error) {
                console.log(`Error testing individual content ${j + 1}: ${error.message}`);
                testResults.errors.push(`Individual content ${j + 1}: ${error.message}`);
                // Try to go back
                await page.goBack().catch(() => {});
              }
            }
          }
        }
        
      } catch (error) {
        console.log(`Error testing ${pageInfo.name}: ${error.message}`);
        testResults.errors.push(`${pageInfo.name}: ${error.message}`);
      }
    }
    
    // Test JavaScript functions directly
    console.log('\n--- Testing Modal JavaScript Functions ---');
    
    await page.goto('https://dalthaus.net/');
    await page.waitForLoadState('networkidle');
    
    const jsTestResults = await page.evaluate(() => {
      const results = {
        openImageModalExists: typeof window.openImageModal === 'function',
        closeModalExists: typeof window.closeModal === 'function',
        addModalToContentImagesExists: typeof window.addModalToContentImages === 'function',
        modalTestWorking: false
      };
      
      // Test if we can call openImageModal manually
      if (results.openImageModalExists) {
        try {
          window.openImageModal('https://dalthaus.net/uploads/test-image.jpg', 'Test Image');
          results.modalTestWorking = true;
        } catch (error) {
          results.modalTestError = error.message;
        }
      }
      
      return results;
    });
    
    console.log('JavaScript Function Tests:', jsTestResults);
    
    if (jsTestResults.modalTestWorking) {
      await page.waitForTimeout(1000);
      
      // Check if test modal opened
      const testModal = await page.locator('.modal, #imageModal, [class*="modal"], dialog').first();
      const isTestModalVisible = await testModal.isVisible().catch(() => false);
      
      if (isTestModalVisible) {
        console.log('✅ Manual JavaScript modal test successful!');
        
        await page.screenshot({ 
          path: 'testing/screenshots/tinymce-modal-js-test-success.png' 
        });
        
        // Close test modal
        await page.keyboard.press('Escape');
        await page.waitForTimeout(500);
      }
    }
    
    // Generate final report
    console.log('\n=== FINAL TINYMCE MODAL VERIFICATION RESULTS ===');
    console.log(`Pages Checked: ${testResults.pagesChecked}`);
    console.log(`Total TinyMCE Images Found: ${testResults.imagesFound}`);
    console.log(`Modals Working: ${testResults.modalsFunctional}`);
    console.log(`Modals Not Working: ${testResults.nonFunctional}`);
    console.log(`JavaScript Functions Available:`, jsTestResults);
    
    if (testResults.errors.length > 0) {
      console.log('\nErrors Encountered:');
      testResults.errors.forEach(error => console.log(`  - ${error}`));
    }
    
    // Take final comprehensive screenshot
    await page.screenshot({ 
      path: 'testing/screenshots/tinymce-modal-verification-final.png', 
      fullPage: true 
    });
    
    // Assertions for test validation
    expect(testResults.pagesChecked).toBeGreaterThan(0);
    
    if (testResults.imagesFound > 0) {
      // If we found TinyMCE images, at least some should have working modals
      expect(testResults.modalsFunctional).toBeGreaterThan(0);
      
      // Success rate should be reasonable (at least 50% working)
      const successRate = testResults.modalsFunctional / testResults.imagesFound;
      expect(successRate).toBeGreaterThanOrEqual(0.5);
    }
    
    // Essential JavaScript functions should exist
    expect(jsTestResults.openImageModalExists).toBe(true);
    
    console.log('\n✅ TinyMCE Modal Verification Complete!');
  });
  
  test('Test specific TinyMCE image modal scenarios', async ({ page }) => {
    console.log('=== TESTING SPECIFIC TINYMCE MODAL SCENARIOS ===');
    
    // Test with cache busting to ensure fresh content
    await page.goto(`https://dalthaus.net/?nocache=${Date.now()}`);
    await page.waitForLoadState('networkidle');
    
    // Check for images in content areas specifically
    const contentImages = await page.locator('.content img, .article-content img, .photobook-content img, main img').all();
    console.log(`Found ${contentImages.length} images in content areas`);
    
    let modalTests = 0;
    let modalSuccesses = 0;
    
    for (const image of contentImages) {
      const src = await image.getAttribute('src');
      
      // Only test images that are clearly from TinyMCE uploads
      if (src && src.includes('/uploads/content/')) {
        modalTests++;
        console.log(`Testing TinyMCE content image: ${src}`);
        
        try {
          // Scroll image into view
          await image.scrollIntoViewIfNeeded();
          await page.waitForTimeout(500);
          
          // Click the image
          await image.click();
          await page.waitForTimeout(1500); // Wait longer for modal
          
          // Check for any type of modal or dialog
          const modals = await page.locator('.modal, #imageModal, dialog, [role="dialog"], .image-modal').all();
          let modalOpened = false;
          
          for (const modal of modals) {
            if (await modal.isVisible().catch(() => false)) {
              modalOpened = true;
              modalSuccesses++;
              console.log(`✅ Modal opened for image: ${src}`);
              
              // Take screenshot
              await page.screenshot({
                path: `testing/screenshots/tinymce-specific-modal-${modalTests}-success.png`
              });
              
              // Close modal
              await page.keyboard.press('Escape');
              await page.waitForTimeout(500);
              break;
            }
          }
          
          if (!modalOpened) {
            console.log(`❌ No modal opened for image: ${src}`);
            
            // Take debug screenshot
            await page.screenshot({
              path: `testing/screenshots/tinymce-specific-modal-${modalTests}-failed.png`
            });
          }
          
        } catch (error) {
          console.log(`Error testing image ${src}: ${error.message}`);
        }
        
        // Limit to first 5 tests to avoid excessive runtime
        if (modalTests >= 5) break;
      }
    }
    
    console.log(`\nSpecific TinyMCE Modal Tests: ${modalTests}`);
    console.log(`Successful Modals: ${modalSuccesses}`);
    
    if (modalTests > 0) {
      expect(modalSuccesses).toBeGreaterThan(0);
    }
  });
});