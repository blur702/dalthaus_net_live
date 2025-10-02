const { test, expect } = require('@playwright/test');

test.describe('Modal Functionality Test - Live Site', () => {
  test.setTimeout(60000); // Increase timeout for live site testing

  test('Articles page - test image modal functionality', async ({ page }) => {
    console.log('Testing Articles Page Modal Functionality...');
    
    // Navigate to articles page
    await page.goto('https://dalthaus.net/articles', { waitUntil: 'networkidle' });
    
    // Wait for articles to load - use more flexible selector
    await page.waitForSelector('main, .container, .content', { timeout: 10000 });
    
    // Get all article links - try multiple selectors
    const articleLinks = await page.$$eval('a[href*="/article/"], .article-link, .content-card a, article a', links => 
      links.map(link => link.href).filter(href => href.includes('/article/'))
    );
    
    console.log(`Found ${articleLinks.length} articles to test`);
    
    // Test first few articles (limit to 3 for efficiency)
    const articlesToTest = articleLinks.slice(0, 3);
    
    for (let i = 0; i < articlesToTest.length; i++) {
      const articleUrl = articlesToTest[i];
      console.log(`\nTesting article ${i + 1}: ${articleUrl}`);
      
      // Navigate to article
      await page.goto(articleUrl, { waitUntil: 'networkidle' });
      
      // Check for console errors
      const consoleErrors = [];
      page.on('console', msg => {
        if (msg.type() === 'error') {
          consoleErrors.push(msg.text());
        }
      });
      
      // Wait for content to load
      await page.waitForSelector('.article-content, .content-wrapper', { timeout: 10000 });
      
      // Check if there's a featured image
      const featuredImage = await page.$('.featured-image img, .article-image img, img[onclick*="modal"], img[data-modal]');
      
      if (featuredImage) {
        console.log('  - Found featured image');
        
        // Get image details
        const imageDetails = await featuredImage.evaluate(img => ({
          src: img.src,
          onclick: img.onclick ? img.onclick.toString() : null,
          dataModal: img.dataset.modal,
          className: img.className,
          parentClassName: img.parentElement?.className
        }));
        
        console.log('  - Image details:', JSON.stringify(imageDetails, null, 2));
        
        // Try clicking the image
        try {
          await featuredImage.click();
          console.log('  - Clicked on image');
          
          // Wait a moment for modal to potentially appear
          await page.waitForTimeout(1000);
          
          // Check for modal elements
          const modalSelectors = [
            '#imageModal',
            '.modal',
            '.image-modal',
            '[role="dialog"]',
            '.lightbox',
            '.overlay'
          ];
          
          let modalFound = false;
          for (const selector of modalSelectors) {
            const modal = await page.$(selector);
            if (modal) {
              const isVisible = await modal.isVisible();
              if (isVisible) {
                console.log(`  ✓ Modal found and visible: ${selector}`);
                modalFound = true;
                
                // Try to close modal
                const closeButton = await page.$('.modal-close, .close, [data-dismiss="modal"]');
                if (closeButton) {
                  await closeButton.click();
                  await page.waitForTimeout(500);
                  console.log('  - Modal closed');
                } else {
                  // Try ESC key
                  await page.keyboard.press('Escape');
                  await page.waitForTimeout(500);
                  console.log('  - Attempted to close modal with ESC');
                }
                break;
              }
            }
          }
          
          if (!modalFound) {
            console.log('  ✗ No modal appeared after clicking image');
          }
          
        } catch (error) {
          console.log(`  ✗ Error clicking image: ${error.message}`);
        }
        
        // Report console errors
        if (consoleErrors.length > 0) {
          console.log('  ✗ Console errors found:');
          consoleErrors.forEach(error => console.log(`    - ${error}`));
        } else {
          console.log('  ✓ No console errors');
        }
        
      } else {
        console.log('  - No featured image found');
      }
      
      // Take screenshot for documentation
      await page.screenshot({ 
        path: `testing/screenshots/article-modal-test-${i + 1}.png`,
        fullPage: false 
      });
    }
  });

  test('Photobooks page - test image modal functionality', async ({ page }) => {
    console.log('Testing Photobooks Page Modal Functionality...');
    
    // Navigate to photobooks page
    await page.goto('https://dalthaus.net/photobooks', { waitUntil: 'networkidle' });
    
    // Wait for photobooks to load - use more flexible selector
    await page.waitForSelector('main, .container, .content', { timeout: 10000 });
    
    // Get all photobook links - try multiple selectors
    const photobookLinks = await page.$$eval('a[href*="/photobook/"], .photobook-link, .content-card a, article a', links => 
      links.map(link => link.href).filter(href => href.includes('/photobook/'))
    );
    
    console.log(`Found ${photobookLinks.length} photobooks to test`);
    
    // Test first few photobooks (limit to 3 for efficiency)
    const photobooksToTest = photobookLinks.slice(0, 3);
    
    for (let i = 0; i < photobooksToTest.length; i++) {
      const photobookUrl = photobooksToTest[i];
      console.log(`\nTesting photobook ${i + 1}: ${photobookUrl}`);
      
      // Navigate to photobook
      await page.goto(photobookUrl, { waitUntil: 'networkidle' });
      
      // Check for console errors
      const consoleErrors = [];
      page.on('console', msg => {
        if (msg.type() === 'error') {
          consoleErrors.push(msg.text());
        }
      });
      
      // Wait for content to load
      await page.waitForSelector('.photobook-content, .content-wrapper, .gallery', { timeout: 10000 });
      
      // Check for gallery images
      const galleryImages = await page.$$('.gallery img, .photobook-images img, .image-grid img, img[onclick*="modal"], img[data-modal]');
      
      if (galleryImages.length > 0) {
        console.log(`  - Found ${galleryImages.length} gallery images`);
        
        // Test first image
        const firstImage = galleryImages[0];
        
        // Get image details
        const imageDetails = await firstImage.evaluate(img => ({
          src: img.src,
          onclick: img.onclick ? img.onclick.toString() : null,
          dataModal: img.dataset.modal,
          className: img.className,
          parentClassName: img.parentElement?.className
        }));
        
        console.log('  - First image details:', JSON.stringify(imageDetails, null, 2));
        
        // Try clicking the first image
        try {
          await firstImage.click();
          console.log('  - Clicked on first gallery image');
          
          // Wait a moment for modal to potentially appear
          await page.waitForTimeout(1000);
          
          // Check for modal elements
          const modalSelectors = [
            '#imageModal',
            '.modal',
            '.image-modal',
            '[role="dialog"]',
            '.lightbox',
            '.overlay'
          ];
          
          let modalFound = false;
          for (const selector of modalSelectors) {
            const modal = await page.$(selector);
            if (modal) {
              const isVisible = await modal.isVisible();
              if (isVisible) {
                console.log(`  ✓ Modal found and visible: ${selector}`);
                modalFound = true;
                
                // Check if modal contains an image
                const modalImage = await modal.$('img');
                if (modalImage) {
                  const modalImageSrc = await modalImage.getAttribute('src');
                  console.log(`  - Modal contains image: ${modalImageSrc}`);
                }
                
                // Try to close modal
                const closeButton = await page.$('.modal-close, .close, [data-dismiss="modal"]');
                if (closeButton) {
                  await closeButton.click();
                  await page.waitForTimeout(500);
                  console.log('  - Modal closed');
                } else {
                  // Try ESC key
                  await page.keyboard.press('Escape');
                  await page.waitForTimeout(500);
                  console.log('  - Attempted to close modal with ESC');
                }
                break;
              }
            }
          }
          
          if (!modalFound) {
            console.log('  ✗ No modal appeared after clicking image');
          }
          
        } catch (error) {
          console.log(`  ✗ Error clicking image: ${error.message}`);
        }
        
        // Report console errors
        if (consoleErrors.length > 0) {
          console.log('  ✗ Console errors found:');
          consoleErrors.forEach(error => console.log(`    - ${error}`));
        } else {
          console.log('  ✓ No console errors');
        }
        
      } else {
        console.log('  - No gallery images found');
      }
      
      // Take screenshot for documentation
      await page.screenshot({ 
        path: `testing/screenshots/photobook-modal-test-${i + 1}.png`,
        fullPage: false 
      });
    }
  });

  test('Check for modal-related JavaScript and CSS', async ({ page }) => {
    console.log('Checking for modal-related resources...');
    
    // Go to homepage to check loaded resources
    await page.goto('https://dalthaus.net/', { waitUntil: 'networkidle' });
    
    // Check for modal-related JavaScript
    const hasModalJS = await page.evaluate(() => {
      // Check for common modal functions
      const modalFunctions = [
        typeof openModal !== 'undefined',
        typeof closeModal !== 'undefined',
        typeof showModal !== 'undefined',
        typeof window.openModal !== 'undefined',
        typeof window.closeModal !== 'undefined',
        typeof $ !== 'undefined' && typeof $.modal !== 'undefined',
        document.querySelector('script[src*="modal"]') !== null
      ];
      
      return {
        hasModalFunctions: modalFunctions.some(f => f),
        jQuery: typeof $ !== 'undefined',
        bootstrap: typeof bootstrap !== 'undefined',
        details: modalFunctions
      };
    });
    
    console.log('JavaScript check:', JSON.stringify(hasModalJS, null, 2));
    
    // Check for modal-related CSS
    const hasModalCSS = await page.evaluate(() => {
      const stylesheets = Array.from(document.styleSheets);
      let hasModalStyles = false;
      
      for (const sheet of stylesheets) {
        try {
          const rules = Array.from(sheet.cssRules || sheet.rules || []);
          const modalRule = rules.some(rule => 
            rule.selectorText && rule.selectorText.includes('modal')
          );
          if (modalRule) {
            hasModalStyles = true;
            break;
          }
        } catch (e) {
          // Cross-origin stylesheets will throw
        }
      }
      
      // Also check for inline styles
      const inlineModalStyles = document.querySelector('style')?.textContent?.includes('modal');
      
      return {
        hasModalStyles,
        hasInlineModalStyles: inlineModalStyles || false,
        modalElements: document.querySelectorAll('[class*="modal"], [id*="modal"]').length
      };
    });
    
    console.log('CSS check:', JSON.stringify(hasModalCSS, null, 2));
    
    // Check page source for modal HTML
    const pageSource = await page.content();
    const modalHTMLPresent = {
      hasModalDiv: pageSource.includes('id="modal') || pageSource.includes('class="modal'),
      hasImageModal: pageSource.includes('imageModal'),
      hasLightbox: pageSource.includes('lightbox'),
      hasOverlay: pageSource.includes('overlay')
    };
    
    console.log('HTML check:', JSON.stringify(modalHTMLPresent, null, 2));
  });
});