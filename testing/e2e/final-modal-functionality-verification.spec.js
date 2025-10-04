import { test, expect } from '@playwright/test';

/**
 * FINAL MODAL FUNCTIONALITY VERIFICATION TEST
 * 
 * After discovering that the modal system creates HTML dynamically,
 * this test properly verifies the complete modal functionality.
 * 
 * KEY INSIGHT: The modal is created dynamically by openImageModal()
 * function, not as a static #imageModal element.
 */

test.describe('Final Modal Functionality Verification', () => {
  
  test('Complete Modal System Verification - Dynamic Modal Creation', async ({ page }) => {
    console.log('🎯 FINAL VERIFICATION: Testing complete modal functionality...');
    
    // Go to homepage
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    
    // 1. Verify JavaScript functions exist
    const functionsCheck = await page.evaluate(() => {
      return {
        openImageModal: typeof window.openImageModal === 'function',
        closeImageModal: typeof window.closeImageModal === 'function', 
        addModalToContentImages: typeof window.addModalToContentImages === 'function'
      };
    });
    
    console.log('JavaScript Functions Check:');
    console.log(`✅ openImageModal: ${functionsCheck.openImageModal}`);
    console.log(`✅ closeImageModal: ${functionsCheck.closeImageModal}`);
    console.log(`✅ addModalToContentImages: ${functionsCheck.addModalToContentImages}`);
    
    expect(functionsCheck.openImageModal).toBe(true);
    expect(functionsCheck.closeImageModal).toBe(true);
    expect(functionsCheck.addModalToContentImages).toBe(true);
    
    // 2. Test dynamic modal creation
    console.log('🖼️ Testing dynamic modal creation...');
    
    const modalCreationTest = await page.evaluate(() => {
      // Test creating a modal dynamically
      if (typeof window.openImageModal === 'function') {
        // Call the function to create modal
        window.openImageModal('https://via.placeholder.com/800x600/0066cc/ffffff?text=Test+Modal', 'Test Image');
        
        // Check if modal was created
        const modal = document.querySelector('.image-modal');
        const hasModal = !!modal;
        const modalVisible = hasModal && getComputedStyle(modal).display !== 'none';
        const hasCloseButton = hasModal && !!modal.querySelector('.modal-close');
        const hasImage = hasModal && !!modal.querySelector('img');
        
        return {
          modalCreated: hasModal,
          modalVisible: modalVisible,
          hasCloseButton: hasCloseButton,
          hasImage: hasImage,
          modalHTML: hasModal ? modal.outerHTML.substring(0, 200) + '...' : 'No modal found'
        };
      }
      return { error: 'openImageModal function not available' };
    });
    
    console.log('Modal Creation Test Results:');
    console.log(`✅ Modal created: ${modalCreationTest.modalCreated}`);
    console.log(`✅ Modal visible: ${modalCreationTest.modalVisible}`);
    console.log(`✅ Has close button: ${modalCreationTest.hasCloseButton}`);
    console.log(`✅ Has image: ${modalCreationTest.hasImage}`);
    
    if (modalCreationTest.modalCreated) {
      console.log('Modal HTML preview:', modalCreationTest.modalHTML);
    }
    
    // Take screenshot with modal open
    await page.screenshot({ 
      path: 'testing/screenshots/final-verification-modal-open.png',
      fullPage: true 
    });
    
    // 3. Test modal close functionality
    console.log('❌ Testing modal close functionality...');
    
    const modalCloseTest = await page.evaluate(() => {
      if (typeof window.closeImageModal === 'function') {
        window.closeImageModal();
        
        const modal = document.querySelector('.image-modal');
        return {
          modalClosed: !modal,
          bodyOverflowRestored: document.body.style.overflow === ''
        };
      }
      return { error: 'closeImageModal function not available' };
    });
    
    console.log('Modal Close Test Results:');
    console.log(`✅ Modal closed: ${modalCloseTest.modalClosed}`);
    console.log(`✅ Body overflow restored: ${modalCloseTest.bodyOverflowRestored}`);
    
    // Take screenshot with modal closed
    await page.screenshot({ 
      path: 'testing/screenshots/final-verification-modal-closed.png',
      fullPage: true 
    });
    
    // Assert all functionality works
    expect(modalCreationTest.modalCreated).toBe(true);
    expect(modalCreationTest.modalVisible).toBe(true);
    expect(modalCreationTest.hasCloseButton).toBe(true);
    expect(modalCreationTest.hasImage).toBe(true);
    expect(modalCloseTest.modalClosed).toBe(true);
    
    console.log('🎉 MODAL FUNCTIONALITY VERIFICATION: COMPLETE SUCCESS!');
  });

  test('Test addModalToContentImages Function with Injected Content', async ({ page }) => {
    console.log('🧪 Testing addModalToContentImages with real content...');
    
    // Go to articles page
    await page.goto('/articles');
    await page.waitForLoadState('networkidle');
    
    // Inject test image with modal attributes
    const injectionResult = await page.evaluate(() => {
      // Create a test image with proper modal attributes
      const testImg = document.createElement('img');
      testImg.src = 'https://via.placeholder.com/400x300/ff6b6b/ffffff?text=Test+Display+Image';
      testImg.setAttribute('data-modal-src', 'https://via.placeholder.com/800x600/ff6b6b/ffffff?text=Test+Modal+Image');
      testImg.alt = 'Test Modal Image';
      testImg.style.border = '3px solid red';
      testImg.style.margin = '20px';
      
      // Add to main content area
      const main = document.querySelector('main');
      if (main) {
        const testDiv = document.createElement('div');
        testDiv.innerHTML = '<h3 style="color: red; font-family: Arial;">🧪 TEST IMAGE WITH MODAL (injected by test):</h3>';
        testDiv.appendChild(testImg);
        testDiv.setAttribute('class', 'content-text'); // Add content class
        main.appendChild(testDiv);
        
        // Run the addModalToContentImages function
        if (typeof window.addModalToContentImages === 'function') {
          window.addModalToContentImages();
          
          // Check if modal functionality was added
          const hasModalEnabled = testImg.hasAttribute('data-modal-enabled');
          const hasCursorPointer = testImg.style.cursor === 'pointer';
          const hasModalClass = testImg.classList.contains('modal-image');
          
          return {
            imageAdded: true,
            modalEnabled: hasModalEnabled,
            cursorPointer: hasCursorPointer,
            modalClass: hasModalClass,
            imageSrc: testImg.src,
            modalSrc: testImg.getAttribute('data-modal-src')
          };
        }
        return { error: 'addModalToContentImages function not available' };
      }
      return { error: 'Main element not found' };
    });
    
    console.log('Image Injection Results:');
    console.log(`✅ Image added: ${injectionResult.imageAdded}`);
    console.log(`✅ Modal enabled: ${injectionResult.modalEnabled}`);
    console.log(`✅ Cursor pointer: ${injectionResult.cursorPointer}`);
    console.log(`✅ Modal class added: ${injectionResult.modalClass}`);
    
    // Take screenshot showing injected image
    await page.screenshot({ 
      path: 'testing/screenshots/final-verification-injected-image.png',
      fullPage: true 
    });
    
    // Test clicking on the injected image
    const testImage = page.locator('img[alt="Test Modal Image"]');
    
    if (await testImage.count() > 0) {
      console.log('🖱️ Clicking on test image to trigger modal...');
      
      await testImage.click();
      await page.waitForTimeout(500);
      
      // Check if modal appeared
      const modalCheck = await page.evaluate(() => {
        const modal = document.querySelector('.image-modal');
        if (modal) {
          const img = modal.querySelector('img');
          return {
            modalExists: true,
            modalVisible: getComputedStyle(modal).display !== 'none',
            imageSrc: img ? img.src : 'no image',
            hasCloseButton: !!modal.querySelector('.modal-close')
          };
        }
        return { modalExists: false };
      });
      
      console.log('Click Test Results:');
      console.log(`✅ Modal created on click: ${modalCheck.modalExists}`);
      console.log(`✅ Modal visible: ${modalCheck.modalVisible}`);
      console.log(`✅ Has close button: ${modalCheck.hasCloseButton}`);
      
      if (modalCheck.modalExists) {
        console.log(`✅ Modal image src: ${modalCheck.imageSrc}`);
        
        // Take screenshot with modal open from click
        await page.screenshot({ 
          path: 'testing/screenshots/final-verification-click-modal.png',
          fullPage: true 
        });
        
        // Close modal by calling function
        await page.evaluate(() => {
          if (typeof window.closeImageModal === 'function') {
            window.closeImageModal();
          }
        });
      }
      
      expect(modalCheck.modalExists).toBe(true);
      expect(modalCheck.modalVisible).toBe(true);
    }
    
    // Assert injection worked
    expect(injectionResult.imageAdded).toBe(true);
    expect(injectionResult.modalEnabled).toBe(true);
    expect(injectionResult.cursorPointer).toBe(true);
    
    console.log('🎉 CONTENT IMAGE MODAL FUNCTIONALITY: COMPLETE SUCCESS!');
  });

  test('Test Real Content Pages for Modal Images', async ({ page }) => {
    console.log('📄 Testing real content pages for existing modal images...');
    
    const pagesWithResults = [];
    
    // Test each content type
    const contentPages = [
      { url: '/', name: 'Homepage' },
      { url: '/articles', name: 'Articles List' },
      { url: '/photobooks', name: 'Photobooks List' }
    ];
    
    for (const contentPage of contentPages) {
      console.log(`Testing ${contentPage.name}...`);
      
      await page.goto(contentPage.url);
      await page.waitForLoadState('networkidle');
      
      const pageAnalysis = await page.evaluate(() => {
        const allImages = document.querySelectorAll('img');
        const modalImages = document.querySelectorAll('img[data-modal-src]');
        const clickableImages = document.querySelectorAll('img[onclick*="openImageModal"]');
        
        return {
          totalImages: allImages.length,
          modalImages: modalImages.length,
          clickableImages: clickableImages.length,
          hasModalFunction: typeof window.openImageModal === 'function',
          hasAddFunction: typeof window.addModalToContentImages === 'function'
        };
      });
      
      console.log(`${contentPage.name} Analysis:`);
      console.log(`- Total images: ${pageAnalysis.totalImages}`);
      console.log(`- Images with data-modal-src: ${pageAnalysis.modalImages}`);
      console.log(`- Images with onclick modal: ${pageAnalysis.clickableImages}`);
      console.log(`- Has modal functions: ${pageAnalysis.hasModalFunction}`);
      
      pagesWithResults.push({
        ...contentPage,
        ...pageAnalysis
      });
      
      // If this is the articles list, click on first article
      if (contentPage.url === '/articles') {
        const firstArticle = page.locator('article a, .article-link').first();
        if (await firstArticle.count() > 0) {
          console.log('Clicking on first article...');
          await firstArticle.click();
          await page.waitForLoadState('networkidle');
          
          const articleAnalysis = await page.evaluate(() => {
            const allImages = document.querySelectorAll('img');
            const modalImages = document.querySelectorAll('img[data-modal-src]');
            const clickableImages = document.querySelectorAll('img[onclick*="openImageModal"]');
            
            return {
              totalImages: allImages.length,
              modalImages: modalImages.length,
              clickableImages: clickableImages.length
            };
          });
          
          console.log('Individual Article Analysis:');
          console.log(`- Total images: ${articleAnalysis.totalImages}`);
          console.log(`- Images with data-modal-src: ${articleAnalysis.modalImages}`);
          console.log(`- Images with onclick modal: ${articleAnalysis.clickableImages}`);
          
          // Take screenshot of individual article
          await page.screenshot({ 
            path: 'testing/screenshots/final-verification-individual-article.png',
            fullPage: true 
          });
        }
      }
    }
    
    // Summary
    const totalModalImages = pagesWithResults.reduce((sum, page) => sum + page.modalImages, 0);
    const allPagesHaveFunctions = pagesWithResults.every(page => page.hasModalFunction && page.hasAddFunction);
    
    console.log('\n📊 FINAL CONTENT ANALYSIS SUMMARY:');
    console.log(`Total pages tested: ${pagesWithResults.length}`);
    console.log(`Total images with modal functionality: ${totalModalImages}`);
    console.log(`All pages have modal functions: ${allPagesHaveFunctions}`);
    
    expect(allPagesHaveFunctions).toBe(true);
    
    console.log('🎉 CONTENT PAGES MODAL VERIFICATION: COMPLETE!');
  });
});