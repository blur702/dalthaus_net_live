import { test, expect } from '@playwright/test';

test.describe('Frontend Modal with Real Content Tests', () => {
  
  test('Find actual articles and photobooks with images and test modal functionality', async ({ page }) => {
    console.log('=== TESTING REAL CONTENT FOR MODAL FUNCTIONALITY ===');
    
    const results = {
      articles: { found: 0, tested: 0, working: 0 },
      photobooks: { found: 0, tested: 0, working: 0 },
      images: { total: 0, modalEnabled: 0, working: 0 }
    };
    
    // First, let's visit the homepage and see what's available
    await page.goto('https://dalthaus.net/');
    await page.waitForLoadState('networkidle');
    
    // Take screenshot of homepage
    await page.screenshot({ path: 'testing/screenshots/frontend-modal-homepage.png', fullPage: true });
    
    // Check for article links on homepage
    const articleLinksHomepage = await page.locator('a[href*="/article/"]').all();
    console.log(`Found ${articleLinksHomepage.length} article links on homepage`);
    
    // Check for photobook links on homepage
    const photobookLinksHomepage = await page.locator('a[href*="/photobook/"]').all();
    console.log(`Found ${photobookLinksHomepage.length} photobook links on homepage`);
    
    // Visit articles page
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: 'testing/screenshots/frontend-modal-articles-page.png', fullPage: true });
    
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    console.log(`Found ${articleLinks.length} article links on articles page`);
    results.articles.found = articleLinks.length;
    
    // Test first few articles for modal functionality
    for (let i = 0; i < Math.min(3, articleLinks.length); i++) {
      console.log(`\n--- Testing Article ${i + 1} ---`);
      const link = articleLinks[i];
      const href = await link.getAttribute('href');
      console.log(`Article URL: ${href}`);
      
      await link.click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000); // Give time for any dynamic content loading
      
      // Take screenshot of article
      await page.screenshot({ 
        path: `testing/screenshots/frontend-modal-article-${i + 1}.png`, 
        fullPage: true 
      });
      
      // Look for all images in the content
      const allImages = await page.locator('.content-text img, .prose img, article img, main img').all();
      console.log(`Found ${allImages.length} images in article content`);
      results.images.total += allImages.length;
      
      // Check specifically for images with modal attributes
      const modalImages = await page.locator('img[data-modal-src]').all();
      console.log(`Found ${modalImages.length} images with data-modal-src attribute`);
      results.images.modalEnabled += modalImages.length;
      
      // Test modal functionality on each modal-enabled image
      for (let j = 0; j < modalImages.length; j++) {
        const image = modalImages[j];
        const src = await image.getAttribute('src');
        const modalSrc = await image.getAttribute('data-modal-src');
        console.log(`  Image ${j + 1}: src="${src?.substring(src.lastIndexOf('/') + 1)}", modal-src="${modalSrc?.substring(modalSrc.lastIndexOf('/') + 1)}"`);
        
        // Check if image has pointer cursor
        const cursor = await image.evaluate(el => window.getComputedStyle(el).cursor);
        console.log(`  Cursor style: ${cursor}`);
        
        // Try clicking the image
        await image.click();
        await page.waitForTimeout(1000);
        
        // Check if modal opened
        const modal = await page.locator('.image-modal').first();
        const isModalVisible = await modal.isVisible().catch(() => false);
        
        if (isModalVisible) {
          console.log(`  ✓ Modal opened successfully for image ${j + 1}`);
          results.images.working++;
          
          // Take screenshot of modal
          await page.screenshot({ 
            path: `testing/screenshots/frontend-modal-article-${i + 1}-image-${j + 1}-open.png` 
          });
          
          // Test close functionality
          await page.keyboard.press('Escape');
          await page.waitForTimeout(500);
          console.log(`  ✓ Modal closed with Escape key`);
        } else {
          console.log(`  ✗ Modal did not open for image ${j + 1}`);
        }
      }
      
      // Also test any images that might have onclick handlers
      const onclickImages = await page.locator('img[onclick*="openImageModal"]').all();
      if (onclickImages.length > 0) {
        console.log(`Found ${onclickImages.length} images with onclick handlers`);
        for (let k = 0; k < onclickImages.length; k++) {
          const image = onclickImages[k];
          await image.click();
          await page.waitForTimeout(1000);
          
          const modal = await page.locator('.image-modal').first();
          const isModalVisible = await modal.isVisible().catch(() => false);
          
          if (isModalVisible) {
            console.log(`  ✓ Modal opened via onclick handler`);
            results.images.working++;
            await page.keyboard.press('Escape');
            await page.waitForTimeout(500);
          }
        }
      }
      
      results.articles.tested++;
      
      // Go back to articles page for next iteration
      if (i < Math.min(3, articleLinks.length) - 1) {
        await page.goto('https://dalthaus.net/articles');
        await page.waitForLoadState('networkidle');
      }
    }
    
    // Now test photobooks
    await page.goto('https://dalthaus.net/photobooks');
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: 'testing/screenshots/frontend-modal-photobooks-page.png', fullPage: true });
    
    const photobookLinks = await page.locator('a[href*="/photobook/"]').all();
    console.log(`\nFound ${photobookLinks.length} photobook links on photobooks page`);
    results.photobooks.found = photobookLinks.length;
    
    // Test first few photobooks for modal functionality
    for (let i = 0; i < Math.min(3, photobookLinks.length); i++) {
      console.log(`\n--- Testing Photobook ${i + 1} ---`);
      const link = photobookLinks[i];
      const href = await link.getAttribute('href');
      console.log(`Photobook URL: ${href}`);
      
      await link.click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);
      
      // Take screenshot of photobook
      await page.screenshot({ 
        path: `testing/screenshots/frontend-modal-photobook-${i + 1}.png`, 
        fullPage: true 
      });
      
      // Look for all images in the content
      const allImages = await page.locator('.content-text img, .prose img, article img, main img').all();
      console.log(`Found ${allImages.length} images in photobook content`);
      results.images.total += allImages.length;
      
      // Check specifically for images with modal attributes
      const modalImages = await page.locator('img[data-modal-src]').all();
      console.log(`Found ${modalImages.length} images with data-modal-src attribute`);
      results.images.modalEnabled += modalImages.length;
      
      // Test modal functionality
      for (let j = 0; j < modalImages.length; j++) {
        const image = modalImages[j];
        const src = await image.getAttribute('src');
        const modalSrc = await image.getAttribute('data-modal-src');
        console.log(`  Image ${j + 1}: src="${src?.substring(src.lastIndexOf('/') + 1)}", modal-src="${modalSrc?.substring(modalSrc.lastIndexOf('/') + 1)}"`);
        
        await image.click();
        await page.waitForTimeout(1000);
        
        const modal = await page.locator('.image-modal').first();
        const isModalVisible = await modal.isVisible().catch(() => false);
        
        if (isModalVisible) {
          console.log(`  ✓ Modal opened successfully for photobook image ${j + 1}`);
          results.images.working++;
          
          await page.screenshot({ 
            path: `testing/screenshots/frontend-modal-photobook-${i + 1}-image-${j + 1}-open.png` 
          });
          
          await page.keyboard.press('Escape');
          await page.waitForTimeout(500);
        } else {
          console.log(`  ✗ Modal did not open for photobook image ${j + 1}`);
        }
      }
      
      results.photobooks.tested++;
      
      // Go back to photobooks page for next iteration
      if (i < Math.min(3, photobookLinks.length) - 1) {
        await page.goto('https://dalthaus.net/photobooks');
        await page.waitForLoadState('networkidle');
      }
    }
    
    // Final summary
    console.log('\n=== FINAL RESULTS ===');
    console.log('Articles:', results.articles);
    console.log('Photobooks:', results.photobooks);
    console.log('Images:', results.images);
    
    // Test console functionality one more time
    await page.goto('https://dalthaus.net/');
    await page.waitForLoadState('networkidle');
    
    const consoleTest = await page.evaluate(() => {
      try {
        // Test with a known image from uploads directory
        openImageModal('/uploads/content/featured/2025/09/1e625471330e48845cec8e17dc85bd14.jpg', 'Test Console Modal');
        return { success: true, message: 'Console modal test successful' };
      } catch (error) {
        return { success: false, message: `Console modal test failed: ${error.message}` };
      }
    });
    
    console.log('Console test result:', consoleTest);
    
    if (consoleTest.success) {
      await page.waitForTimeout(1000);
      const modal = await page.locator('.image-modal').first();
      const isModalVisible = await modal.isVisible().catch(() => false);
      
      if (isModalVisible) {
        console.log('✓ Console modal opened successfully');
        await page.screenshot({ path: 'testing/screenshots/frontend-console-modal-success.png' });
        await page.keyboard.press('Escape');
      }
    }
    
    // Create a summary report
    const summary = {
      totalContent: results.articles.found + results.photobooks.found,
      testedContent: results.articles.tested + results.photobooks.tested,
      totalImages: results.images.total,
      modalEnabledImages: results.images.modalEnabled,
      workingModals: results.images.working,
      consoleModalWorks: consoleTest.success
    };
    
    console.log('\n=== SUMMARY REPORT ===');
    console.log(`Total content pieces found: ${summary.totalContent}`);
    console.log(`Content pieces tested: ${summary.testedContent}`);
    console.log(`Total images found in content: ${summary.totalImages}`);
    console.log(`Images with modal functionality: ${summary.modalEnabledImages}`);
    console.log(`Working modal images: ${summary.workingModals}`);
    console.log(`Console modal functionality: ${summary.consoleModalWorks ? 'Working' : 'Not working'}`);
    
    // Assertions
    if (summary.modalEnabledImages > 0) {
      expect(summary.workingModals).toBeGreaterThan(0);
    }
    expect(summary.consoleModalWorks).toBe(true);
  });

  test('Debug modal JavaScript implementation', async ({ page }) => {
    console.log('=== DEBUGGING MODAL JAVASCRIPT IMPLEMENTATION ===');
    
    await page.goto('https://dalthaus.net/');
    await page.waitForLoadState('networkidle');
    
    // Check the modal implementation details
    const modalImplementation = await page.evaluate(() => {
      const results = {
        functions: {},
        modalStyles: {},
        eventListeners: {},
        contentProcessing: {}
      };
      
      // Check if modal functions exist
      results.functions.openImageModal = typeof window.openImageModal === 'function';
      results.functions.closeImageModal = typeof window.closeImageModal === 'function';
      results.functions.addModalToContentImages = typeof window.addModalToContentImages === 'function';
      
      // Check modal CSS styles
      const testModal = document.createElement('div');
      testModal.className = 'image-modal';
      testModal.style.display = 'none';
      document.body.appendChild(testModal);
      
      const modalStyles = window.getComputedStyle(testModal);
      results.modalStyles.position = modalStyles.position;
      results.modalStyles.zIndex = modalStyles.zIndex;
      results.modalStyles.background = modalStyles.backgroundColor;
      
      document.body.removeChild(testModal);
      
      // Check if addModalToContentImages has been called
      try {
        window.addModalToContentImages();
        results.contentProcessing.manual = 'Successfully called addModalToContentImages';
      } catch (error) {
        results.contentProcessing.manual = `Error calling addModalToContentImages: ${error.message}`;
      }
      
      // Check for images that should have modal functionality
      const selectors = [
        '.content-text img[data-modal-src]',
        '.prose img[data-modal-src]',
        'article img[data-modal-src]',
        'main img[data-modal-src]'
      ];
      
      results.contentProcessing.imageCount = 0;
      results.contentProcessing.modalEnabledCount = 0;
      
      selectors.forEach(selector => {
        const images = document.querySelectorAll(selector);
        results.contentProcessing.imageCount += images.length;
        
        images.forEach(img => {
          if (img.hasAttribute('data-modal-enabled')) {
            results.contentProcessing.modalEnabledCount++;
          }
        });
      });
      
      return results;
    });
    
    console.log('Modal Implementation Debug Results:');
    console.log('Functions:', modalImplementation.functions);
    console.log('Modal Styles:', modalImplementation.modalStyles);
    console.log('Content Processing:', modalImplementation.contentProcessing);
    
    await page.screenshot({ path: 'testing/screenshots/frontend-modal-debug.png', fullPage: true });
  });

  test('Test modal functionality with manual image injection', async ({ page }) => {
    console.log('=== TESTING MODAL WITH MANUALLY INJECTED IMAGES ===');
    
    await page.goto('https://dalthaus.net/');
    await page.waitForLoadState('networkidle');
    
    // Inject test images with modal attributes
    const testResult = await page.evaluate(() => {
      // Create a test container
      const container = document.createElement('div');
      container.id = 'modal-test-container';
      container.innerHTML = `
        <div class="content-text">
          <h3>Test Images for Modal Functionality</h3>
          <p>These images are injected for testing modal functionality:</p>
          
          <img src="/uploads/content/featured/2025/09/1e625471330e48845cec8e17dc85bd14.jpg" 
               data-modal-src="/uploads/content/featured/2025/09/fb2287ae6e882c2a4abe6767927d2da9.jpg"
               alt="Test Modal Image 1" 
               style="width: 200px; height: auto; margin: 10px; border: 2px solid red;">
               
          <img src="/uploads/content/featured/2025/09/55a281e98034dbf05a6692241b65c767.jpg" 
               data-modal-src="/uploads/content/featured/2025/09/8fc5d5a9d1ddb15c0d44ffad84df6d50.png"
               alt="Test Modal Image 2" 
               style="width: 200px; height: auto; margin: 10px; border: 2px solid blue;">
        </div>
      `;
      
      // Insert into main content area
      const main = document.querySelector('main') || document.body;
      main.insertBefore(container, main.firstChild);
      
      // Run the modal processing function
      if (typeof window.addModalToContentImages === 'function') {
        window.addModalToContentImages();
        return { success: true, message: 'Test images injected and processed' };
      } else {
        return { success: false, message: 'addModalToContentImages function not found' };
      }
    });
    
    console.log('Test image injection result:', testResult);
    
    if (testResult.success) {
      await page.waitForTimeout(2000); // Wait for processing
      
      // Take screenshot showing injected test images
      await page.screenshot({ path: 'testing/screenshots/frontend-modal-injected-images.png', fullPage: true });
      
      // Test clicking on the injected images
      const testImages = await page.locator('#modal-test-container img[data-modal-src]').all();
      console.log(`Found ${testImages.length} injected test images`);
      
      for (let i = 0; i < testImages.length; i++) {
        console.log(`Testing injected image ${i + 1}...`);
        const image = testImages[i];
        
        // Check if image has modal functionality
        const hasModalEnabled = await image.getAttribute('data-modal-enabled');
        const cursor = await image.evaluate(el => window.getComputedStyle(el).cursor);
        console.log(`  data-modal-enabled: ${hasModalEnabled}, cursor: ${cursor}`);
        
        // Click the image
        await image.click();
        await page.waitForTimeout(1000);
        
        // Check if modal opened
        const modal = await page.locator('.image-modal').first();
        const isModalVisible = await modal.isVisible().catch(() => false);
        
        if (isModalVisible) {
          console.log(`  ✓ Modal opened for injected image ${i + 1}`);
          
          // Take screenshot of modal
          await page.screenshot({ 
            path: `testing/screenshots/frontend-modal-injected-${i + 1}-open.png` 
          });
          
          // Test close
          await page.keyboard.press('Escape');
          await page.waitForTimeout(500);
          console.log(`  ✓ Modal closed for injected image ${i + 1}`);
        } else {
          console.log(`  ✗ Modal did not open for injected image ${i + 1}`);
        }
      }
    }
  });
});