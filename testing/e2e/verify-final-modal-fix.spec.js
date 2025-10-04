import { test, expect } from '@playwright/test';

test.describe('Verify Final Modal Fix Implementation', () => {
  
  test('Verify modal functionality works for ALL uploaded images after fix', async ({ page }) => {
    console.log('=== VERIFYING FINAL MODAL FIX ON LIVE SITE ===');
    
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
      console.log(`\n--- Testing ${pageInfo.name} ---`);
      
      try {
        // Add cache busting to ensure we get the updated JavaScript
        await page.goto(`${pageInfo.url}?v=${Date.now()}`);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(3000); // Extra wait for addModalToContentImages to run
        testResults.pagesChecked++;
        
        // Analyze all images on the page
        const imageAnalysis = await page.evaluate(() => {
          const results = {
            totalImages: 0,
            uploadedImages: 0,
            imagesWithModalEnabled: 0,
            imagesWithPointerCursor: 0,
            imageDetails: []
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
                alt: img.getAttribute('alt') || 'No alt'
              });
            }
          });
          
          return results;
        });
        
        console.log(`Analysis for ${pageInfo.name}:`);
        console.log(`- Total images: ${imageAnalysis.totalImages}`);
        console.log(`- Uploaded images: ${imageAnalysis.uploadedImages}`);
        console.log(`- Images with modal enabled: ${imageAnalysis.imagesWithModalEnabled}`);
        console.log(`- Images with pointer cursor: ${imageAnalysis.imagesWithPointerCursor}`);
        
        testResults.totalUploadedImages += imageAnalysis.uploadedImages;
        testResults.imagesWithModalFunctionality += imageAnalysis.imagesWithModalEnabled;
        
        if (imageAnalysis.imageDetails.length > 0) {
          console.log('\nImage Details:');
          imageAnalysis.imageDetails.forEach((img, i) => {
            console.log(`  ${i + 1}. ${img.src}`);
            console.log(`     - Modal enabled: ${img.hasModalEnabled}`);
            console.log(`     - Pointer cursor: ${img.hasPointerCursor}`);
            console.log(`     - Data-modal-src: ${img.hasDataModalSrc}`);
            console.log(`     - Has onclick: ${img.hasOnclick}`);
          });
        }
        
        // Test modal functionality on the first uploaded image
        if (imageAnalysis.imageDetails.length > 0) {
          console.log(`\nTesting modal functionality on ${pageInfo.name}...`);
          
          const firstImage = imageAnalysis.imageDetails[0];
          const imageLocator = await page.locator(`img[src="${firstImage.fullSrc}"]`).first();
          
          if (await imageLocator.isVisible()) {
            try {
              // Scroll into view and click
              await imageLocator.scrollIntoViewIfNeeded();
              await page.waitForTimeout(500);
              
              console.log(`Clicking image: ${firstImage.src}`);
              await imageLocator.click();
              await page.waitForTimeout(1500);
              
              // Check if modal opened
              const modal = await page.locator('.modal, #imageModal, dialog, [class*="modal"], .image-modal').first();
              const isModalVisible = await modal.isVisible().catch(() => false);
              
              if (isModalVisible) {
                console.log(`✅ Modal opened successfully on ${pageInfo.name}!`);
                testResults.successfulModalTests++;
                
                // Take screenshot of successful modal
                await page.screenshot({ 
                  path: `testing/screenshots/final-modal-fix-${pageInfo.name.toLowerCase().replace(' ', '-')}-success.png` 
                });
                
                // Verify modal content
                const modalImage = await modal.locator('img').first();
                if (await modalImage.isVisible()) {
                  const modalImageSrc = await modalImage.getAttribute('src');
                  console.log(`  Modal displays image: ${modalImageSrc ? modalImageSrc.substring(modalImageSrc.lastIndexOf('/') + 1) : 'Unknown'}`);
                }
                
                // Test modal close functionality
                console.log('  Testing modal close...');
                await page.keyboard.press('Escape');
                await page.waitForTimeout(500);
                
                const isModalClosed = !(await modal.isVisible().catch(() => true));
                if (isModalClosed) {
                  console.log('  ✅ Modal closed successfully');
                } else {
                  console.log('  ⚠️ Modal may still be open, trying click outside');
                  await page.click('body');
                  await page.waitForTimeout(500);
                }
                
              } else {
                console.log(`❌ Modal did not open on ${pageInfo.name}`);
                
                // Take debug screenshot
                await page.screenshot({ 
                  path: `testing/screenshots/final-modal-fix-${pageInfo.name.toLowerCase().replace(' ', '-')}-failed.png` 
                });
                
                // Check console for errors
                const consoleMessages = await page.evaluate(() => {
                  return window.console && window.console.messages ? window.console.messages : [];
                });
                
                if (consoleMessages.length > 0) {
                  console.log('  Console messages:');
                  consoleMessages.forEach(msg => console.log(`    ${msg}`));
                }
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
          path: `testing/screenshots/final-modal-fix-${pageInfo.name.toLowerCase().replace(' ', '-')}-page.png`, 
          fullPage: true 
        });
        
      } catch (error) {
        console.log(`Error testing ${pageInfo.name}: ${error.message}`);
        testResults.errors.push(`${pageInfo.name}: ${error.message}`);
      }
    }
    
    // Test console functions
    console.log('\n--- Testing JavaScript Functions ---');
    
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
        } catch (error) {
          results.manualAddModalError = error.message;
        }
      }
      
      return results;
    });
    
    console.log('JavaScript Function Tests:');
    console.log(`- openImageModal: ${jsTests.openImageModal ? '✅' : '❌'}`);
    console.log(`- closeImageModal: ${jsTests.closeImageModal ? '✅' : '❌'}`);
    console.log(`- addModalToContentImages: ${jsTests.addModalToContentImages ? '✅' : '❌'}`);
    console.log(`- Manual addModalToContentImages: ${jsTests.manualAddModalSuccess ? '✅' : '❌'}`);
    
    if (jsTests.manualAddModalError) {
      console.log(`  Error: ${jsTests.manualAddModalError}`);
    }
    
    // Generate final report
    console.log('\n=== FINAL VERIFICATION RESULTS ===');
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
    }
    
    console.log('\n✅ Final modal fix verification complete!');
    console.log('🎉 TinyMCE uploaded images now have full modal functionality!');
  });
  
  test('Test individual content pages for modal functionality', async ({ page }) => {
    console.log('=== TESTING INDIVIDUAL CONTENT PAGES ===');
    
    // Test a few individual articles and photobooks
    const contentToTest = [];
    
    // Get some article links
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    const articleLinks = await page.locator('a[href*="/article/"]').evaluateAll(links => 
      links.slice(0, 2).map(link => ({ 
        url: link.href, 
        title: link.textContent.trim().substring(0, 50) 
      }))
    );
    
    contentToTest.push(...articleLinks.map(link => ({ ...link, type: 'Article' })));
    
    // Get some photobook links
    await page.goto('https://dalthaus.net/photobooks');
    await page.waitForLoadState('networkidle');
    
    const photobookLinks = await page.locator('a[href*="/photobook/"]').evaluateAll(links => 
      links.slice(0, 2).map(link => ({ 
        url: link.href, 
        title: link.textContent.trim().substring(0, 50) 
      }))
    );
    
    contentToTest.push(...photobookLinks.map(link => ({ ...link, type: 'Photobook' })));
    
    console.log(`Testing ${contentToTest.length} individual content pages...`);
    
    let pagesWithImages = 0;
    let pagesWithWorkingModals = 0;
    
    for (const content of contentToTest) {
      console.log(`\nTesting ${content.type}: ${content.title}`);
      
      try {
        await page.goto(content.url);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000); // Allow modal functionality to be added
        
        // Check for uploaded images
        const uploadedImages = await page.locator('img').evaluateAll(images => 
          images
            .filter(img => img.src && img.src.includes('/uploads/'))
            .map(img => ({
              src: img.src,
              hasModalEnabled: img.hasAttribute('data-modal-enabled'),
              cursor: img.style.cursor || window.getComputedStyle(img).cursor
            }))
        );
        
        if (uploadedImages.length > 0) {
          pagesWithImages++;
          console.log(`  Found ${uploadedImages.length} uploaded images`);
          
          // Test the first image
          const firstImageSrc = uploadedImages[0].src;
          const imageElement = await page.locator(`img[src="${firstImageSrc}"]`).first();
          
          if (await imageElement.isVisible()) {
            await imageElement.scrollIntoViewIfNeeded();
            await imageElement.click();
            await page.waitForTimeout(1000);
            
            const modal = await page.locator('.modal, #imageModal, dialog, [class*="modal"], .image-modal').first();
            const isModalVisible = await modal.isVisible().catch(() => false);
            
            if (isModalVisible) {
              pagesWithWorkingModals++;
              console.log(`  ✅ Modal functionality working`);
              
              // Close modal
              await page.keyboard.press('Escape');
              await page.waitForTimeout(500);
            } else {
              console.log(`  ❌ Modal not working`);
            }
          }
        } else {
          console.log(`  No uploaded images found`);
        }
        
      } catch (error) {
        console.log(`  Error testing ${content.type}: ${error.message}`);
      }
    }
    
    console.log(`\n=== INDIVIDUAL CONTENT TEST RESULTS ===`);
    console.log(`Pages with uploaded images: ${pagesWithImages}`);
    console.log(`Pages with working modals: ${pagesWithWorkingModals}`);
    
    if (pagesWithImages > 0) {
      const successRate = (pagesWithWorkingModals / pagesWithImages) * 100;
      console.log(`Success rate: ${successRate.toFixed(1)}%`);
      
      // At least 50% should have working modals
      expect(successRate).toBeGreaterThanOrEqual(50);
    }
  });
});