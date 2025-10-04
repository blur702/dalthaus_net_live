import { test, expect } from '@playwright/test';

test.describe('Frontend Modal Functionality Tests', () => {
  
  test('Test modal functionality on homepage', async ({ page }) => {
    console.log('Starting homepage modal test...');
    
    // Visit homepage
    await page.goto('https://dalthaus.net/');
    await page.waitForLoadState('networkidle');
    
    // Take screenshot of homepage
    await page.screenshot({ path: 'testing/screenshots/homepage-modal-test.png', fullPage: true });
    
    // Check for images with modal attributes
    const modalImages = await page.locator('img[data-modal-src], img[onclick*="openImageModal"]').all();
    console.log(`Found ${modalImages.length} images with modal attributes on homepage`);
    
    if (modalImages.length > 0) {
      for (let i = 0; i < modalImages.length; i++) {
        const image = modalImages[i];
        const src = await image.getAttribute('src');
        const modalSrc = await image.getAttribute('data-modal-src');
        const onclick = await image.getAttribute('onclick');
        
        console.log(`Image ${i + 1}: src="${src}", data-modal-src="${modalSrc}", onclick="${onclick}"`);
        
        // Check if image has pointer cursor
        const cursor = await image.evaluate(el => window.getComputedStyle(el).cursor);
        console.log(`Image ${i + 1} cursor style: ${cursor}`);
        
        // Try clicking the image
        await image.click();
        await page.waitForTimeout(1000);
        
        // Check if modal opened
        const modal = await page.locator('.modal, #imageModal, [class*="modal"]').first();
        const isModalVisible = await modal.isVisible().catch(() => false);
        
        if (isModalVisible) {
          console.log(`Modal opened successfully for image ${i + 1}`);
          await page.screenshot({ path: `testing/screenshots/homepage-modal-${i + 1}-open.png` });
          
          // Test modal close functionality
          const closeButton = await page.locator('.modal-close, .close, [onclick*="closeModal"]').first();
          if (await closeButton.isVisible().catch(() => false)) {
            await closeButton.click();
            await page.waitForTimeout(500);
            console.log('Modal closed using close button');
          } else {
            // Try clicking outside modal
            await page.click('body');
            await page.waitForTimeout(500);
            console.log('Attempted to close modal by clicking outside');
          }
        } else {
          console.log(`No modal opened for image ${i + 1}`);
        }
      }
    } else {
      console.log('No images with modal functionality found on homepage');
    }
  });

  test('Test modal functionality on articles page', async ({ page }) => {
    console.log('Starting articles page modal test...');
    
    // Visit articles page
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    // Take screenshot of articles page
    await page.screenshot({ path: 'testing/screenshots/articles-modal-test.png', fullPage: true });
    
    // Check for images with modal attributes
    const modalImages = await page.locator('img[data-modal-src], img[onclick*="openImageModal"]').all();
    console.log(`Found ${modalImages.length} images with modal attributes on articles page`);
    
    if (modalImages.length > 0) {
      for (let i = 0; i < modalImages.length; i++) {
        const image = modalImages[i];
        const src = await image.getAttribute('src');
        const modalSrc = await image.getAttribute('data-modal-src');
        const onclick = await image.getAttribute('onclick');
        
        console.log(`Image ${i + 1}: src="${src}", data-modal-src="${modalSrc}", onclick="${onclick}"`);
        
        // Try clicking the image
        await image.click();
        await page.waitForTimeout(1000);
        
        // Check if modal opened
        const modal = await page.locator('.modal, #imageModal, [class*="modal"]').first();
        const isModalVisible = await modal.isVisible().catch(() => false);
        
        if (isModalVisible) {
          console.log(`Modal opened successfully for image ${i + 1}`);
          await page.screenshot({ path: `testing/screenshots/articles-modal-${i + 1}-open.png` });
          
          // Test close functionality
          await page.keyboard.press('Escape');
          await page.waitForTimeout(500);
          console.log('Attempted to close modal with Escape key');
        }
      }
    }
    
    // Also check individual article pages
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    if (articleLinks.length > 0) {
      // Test the first article
      await articleLinks[0].click();
      await page.waitForLoadState('networkidle');
      
      await page.screenshot({ path: 'testing/screenshots/individual-article-modal-test.png', fullPage: true });
      
      const articleModalImages = await page.locator('img[data-modal-src], img[onclick*="openImageModal"]').all();
      console.log(`Found ${articleModalImages.length} images with modal attributes in individual article`);
      
      if (articleModalImages.length > 0) {
        const image = articleModalImages[0];
        await image.click();
        await page.waitForTimeout(1000);
        
        const modal = await page.locator('.modal, #imageModal, [class*="modal"]').first();
        const isModalVisible = await modal.isVisible().catch(() => false);
        
        if (isModalVisible) {
          console.log('Modal opened successfully in individual article');
          await page.screenshot({ path: 'testing/screenshots/individual-article-modal-open.png' });
        }
      }
    }
  });

  test('Test modal functionality on photobooks page', async ({ page }) => {
    console.log('Starting photobooks page modal test...');
    
    // Visit photobooks page
    await page.goto('https://dalthaus.net/photobooks');
    await page.waitForLoadState('networkidle');
    
    // Take screenshot of photobooks page
    await page.screenshot({ path: 'testing/screenshots/photobooks-modal-test.png', fullPage: true });
    
    // Check for images with modal attributes
    const modalImages = await page.locator('img[data-modal-src], img[onclick*="openImageModal"]').all();
    console.log(`Found ${modalImages.length} images with modal attributes on photobooks page`);
    
    if (modalImages.length > 0) {
      for (let i = 0; i < modalImages.length; i++) {
        const image = modalImages[i];
        const src = await image.getAttribute('src');
        const modalSrc = await image.getAttribute('data-modal-src');
        
        console.log(`Image ${i + 1}: src="${src}", data-modal-src="${modalSrc}"`);
        
        // Try clicking the image
        await image.click();
        await page.waitForTimeout(1000);
        
        // Check if modal opened
        const modal = await page.locator('.modal, #imageModal, [class*="modal"]').first();
        const isModalVisible = await modal.isVisible().catch(() => false);
        
        if (isModalVisible) {
          console.log(`Modal opened successfully for image ${i + 1}`);
          await page.screenshot({ path: `testing/screenshots/photobooks-modal-${i + 1}-open.png` });
          
          // Test close functionality
          await page.click('body');
          await page.waitForTimeout(500);
        }
      }
    }
    
    // Also check individual photobook pages
    const photobookLinks = await page.locator('a[href*="/photobook/"]').all();
    if (photobookLinks.length > 0) {
      // Test the first photobook
      await photobookLinks[0].click();
      await page.waitForLoadState('networkidle');
      
      await page.screenshot({ path: 'testing/screenshots/individual-photobook-modal-test.png', fullPage: true });
      
      const photobookModalImages = await page.locator('img[data-modal-src], img[onclick*="openImageModal"]').all();
      console.log(`Found ${photobookModalImages.length} images with modal attributes in individual photobook`);
      
      if (photobookModalImages.length > 0) {
        const image = photobookModalImages[0];
        await image.click();
        await page.waitForTimeout(1000);
        
        const modal = await page.locator('.modal, #imageModal, [class*="modal"]').first();
        const isModalVisible = await modal.isVisible().catch(() => false);
        
        if (isModalVisible) {
          console.log('Modal opened successfully in individual photobook');
          await page.screenshot({ path: 'testing/screenshots/individual-photobook-modal-open.png' });
        }
      }
    }
  });

  test('Test console modal functionality', async ({ page }) => {
    console.log('Starting console modal test...');
    
    // Visit homepage
    await page.goto('https://dalthaus.net/');
    await page.waitForLoadState('networkidle');
    
    // Test if openImageModal function exists and works
    const consoleResult = await page.evaluate(() => {
      try {
        // Check if function exists
        if (typeof openImageModal === 'function') {
          // Try to call it with test parameters
          openImageModal('https://dalthaus.net/uploads/content/featured/2025/09/1e625471330e48845cec8e17dc85bd14.jpg', 'Test Modal Image');
          return { success: true, message: 'openImageModal function called successfully' };
        } else {
          return { success: false, message: 'openImageModal function not found' };
        }
      } catch (error) {
        return { success: false, message: `Error: ${error.message}` };
      }
    });
    
    console.log('Console test result:', consoleResult);
    
    if (consoleResult.success) {
      await page.waitForTimeout(1000);
      
      // Check if modal opened
      const modal = await page.locator('.modal, #imageModal, [class*="modal"]').first();
      const isModalVisible = await modal.isVisible().catch(() => false);
      
      if (isModalVisible) {
        console.log('Modal opened successfully via console command');
        await page.screenshot({ path: 'testing/screenshots/console-modal-success.png' });
        
        // Test close functionality
        const closeButton = await page.locator('.modal-close, .close, [onclick*="closeModal"]').first();
        if (await closeButton.isVisible().catch(() => false)) {
          await closeButton.click();
          console.log('Modal closed successfully');
        }
      } else {
        console.log('Modal did not open via console command');
      }
    }
    
    // Also test if closeModal function exists
    const closeResult = await page.evaluate(() => {
      try {
        if (typeof closeModal === 'function') {
          return { exists: true, message: 'closeModal function found' };
        } else {
          return { exists: false, message: 'closeModal function not found' };
        }
      } catch (error) {
        return { exists: false, message: `Error: ${error.message}` };
      }
    });
    
    console.log('Close function test result:', closeResult);
  });

  test('Comprehensive modal functionality analysis', async ({ page }) => {
    console.log('Starting comprehensive modal analysis...');
    
    const results = {
      homepage: { images: 0, working: 0 },
      articles: { images: 0, working: 0 },
      photobooks: { images: 0, working: 0 },
      functions: { openImageModal: false, closeModal: false }
    };
    
    // Test homepage
    await page.goto('https://dalthaus.net/');
    await page.waitForLoadState('networkidle');
    
    let modalImages = await page.locator('img[data-modal-src], img[onclick*="openImageModal"]').all();
    results.homepage.images = modalImages.length;
    
    for (const image of modalImages) {
      await image.click();
      await page.waitForTimeout(500);
      
      const modal = await page.locator('.modal, #imageModal, [class*="modal"]').first();
      if (await modal.isVisible().catch(() => false)) {
        results.homepage.working++;
        await page.keyboard.press('Escape');
        await page.waitForTimeout(500);
      }
    }
    
    // Test articles page
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    modalImages = await page.locator('img[data-modal-src], img[onclick*="openImageModal"]').all();
    results.articles.images = modalImages.length;
    
    for (const image of modalImages) {
      await image.click();
      await page.waitForTimeout(500);
      
      const modal = await page.locator('.modal, #imageModal, [class*="modal"]').first();
      if (await modal.isVisible().catch(() => false)) {
        results.articles.working++;
        await page.keyboard.press('Escape');
        await page.waitForTimeout(500);
      }
    }
    
    // Test photobooks page
    await page.goto('https://dalthaus.net/photobooks');
    await page.waitForLoadState('networkidle');
    
    modalImages = await page.locator('img[data-modal-src], img[onclick*="openImageModal"]').all();
    results.photobooks.images = modalImages.length;
    
    for (const image of modalImages) {
      await image.click();
      await page.waitForTimeout(500);
      
      const modal = await page.locator('.modal, #imageModal, [class*="modal"]').first();
      if (await modal.isVisible().catch(() => false)) {
        results.photobooks.working++;
        await page.keyboard.press('Escape');
        await page.waitForTimeout(500);
      }
    }
    
    // Test console functions
    const functionTests = await page.evaluate(() => {
      return {
        openImageModal: typeof openImageModal === 'function',
        closeModal: typeof closeModal === 'function'
      };
    });
    
    results.functions = functionTests;
    
    console.log('=== FINAL MODAL FUNCTIONALITY RESULTS ===');
    console.log('Homepage:', results.homepage);
    console.log('Articles:', results.articles);
    console.log('Photobooks:', results.photobooks);
    console.log('Functions:', results.functions);
    
    // Take final screenshot
    await page.screenshot({ path: 'testing/screenshots/modal-analysis-final.png', fullPage: true });
    
    // Assert that at least some modal functionality works
    const totalImages = results.homepage.images + results.articles.images + results.photobooks.images;
    const totalWorking = results.homepage.working + results.articles.working + results.photobooks.working;
    
    console.log(`Total images with modal attributes: ${totalImages}`);
    console.log(`Total working modals: ${totalWorking}`);
    
    if (totalImages > 0) {
      expect(totalWorking).toBeGreaterThan(0);
    }
    
    // Assert that modal functions exist
    expect(results.functions.openImageModal).toBe(true);
  });

  test('Debug modal implementation details', async ({ page }) => {
    console.log('Starting modal implementation debug...');
    
    // Visit homepage
    await page.goto('https://dalthaus.net/');
    await page.waitForLoadState('networkidle');
    
    // Check for modal-related JavaScript and CSS
    const modalImplementation = await page.evaluate(() => {
      const results = {
        scripts: [],
        styles: [],
        modalElements: [],
        globalFunctions: {}
      };
      
      // Check for scripts that might contain modal functionality
      const scripts = document.querySelectorAll('script');
      scripts.forEach(script => {
        if (script.src && (script.src.includes('modal') || script.src.includes('image'))) {
          results.scripts.push(script.src);
        }
        if (script.textContent && script.textContent.includes('openImageModal')) {
          results.scripts.push('inline script with openImageModal');
        }
      });
      
      // Check for modal-related CSS
      const styles = document.querySelectorAll('style, link[rel="stylesheet"]');
      styles.forEach(style => {
        if (style.href && style.href.includes('modal')) {
          results.styles.push(style.href);
        }
        if (style.textContent && style.textContent.includes('.modal')) {
          results.styles.push('inline style with .modal');
        }
      });
      
      // Check for existing modal elements
      const modalElements = document.querySelectorAll('[id*="modal"], [class*="modal"]');
      modalElements.forEach(el => {
        results.modalElements.push({
          tag: el.tagName,
          id: el.id,
          className: el.className,
          display: window.getComputedStyle(el).display
        });
      });
      
      // Check for global functions
      results.globalFunctions.openImageModal = typeof window.openImageModal === 'function';
      results.globalFunctions.closeModal = typeof window.closeModal === 'function';
      
      return results;
    });
    
    console.log('Modal Implementation Details:');
    console.log('Scripts:', modalImplementation.scripts);
    console.log('Styles:', modalImplementation.styles);
    console.log('Modal Elements:', modalImplementation.modalElements);
    console.log('Global Functions:', modalImplementation.globalFunctions);
    
    // Save detailed debug information
    await page.screenshot({ path: 'testing/screenshots/modal-debug-homepage.png', fullPage: true });
  });
});