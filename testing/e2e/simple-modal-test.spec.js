const { test, expect } = require('@playwright/test');

test.describe('Simple Modal Test - Live Site', () => {
  test.setTimeout(60000);

  test('Test modal on specific article', async ({ page }) => {
    // Set up console error tracking
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleErrors.push({
          text: msg.text(),
          location: msg.location()
        });
        console.log(`Console Error: ${msg.text()}`);
      }
    });

    // Navigate directly to a known article
    const testUrl = 'https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible';
    console.log(`\nNavigating to: ${testUrl}`);
    await page.goto(testUrl, { waitUntil: 'networkidle' });

    // Take initial screenshot
    await page.screenshot({ 
      path: 'testing/screenshots/article-before-click.png',
      fullPage: false 
    });

    // Wait for any content container
    await page.waitForSelector('body', { timeout: 5000 });
    
    // Get all images on the page
    const allImages = await page.$$('img');
    console.log(`Found ${allImages.length} images on the page`);

    // Find clickable images
    const clickableImages = [];
    for (const img of allImages) {
      const imgInfo = await img.evaluate(el => {
        const rect = el.getBoundingClientRect();
        return {
          src: el.src,
          alt: el.alt,
          width: rect.width,
          height: rect.height,
          visible: rect.width > 0 && rect.height > 0,
          onclick: el.onclick ? 'has onclick' : 'no onclick',
          cursor: window.getComputedStyle(el).cursor,
          parentTag: el.parentElement?.tagName,
          parentOnclick: el.parentElement?.onclick ? 'parent has onclick' : 'no parent onclick',
          classes: el.className
        };
      });
      
      if (imgInfo.visible && imgInfo.width > 100) {
        clickableImages.push({ element: img, info: imgInfo });
        console.log(`Image ${clickableImages.length}:`, JSON.stringify(imgInfo, null, 2));
      }
    }

    console.log(`\nFound ${clickableImages.length} visible, sizeable images`);

    // Test clicking the first sizeable image
    if (clickableImages.length > 0) {
      const testImage = clickableImages[0];
      console.log('\nTesting click on first image...');
      
      // Click the image
      await testImage.element.click();
      console.log('Clicked image');
      
      // Wait a moment for modal to potentially appear
      await page.waitForTimeout(2000);
      
      // Take screenshot after click
      await page.screenshot({ 
        path: 'testing/screenshots/article-after-click.png',
        fullPage: false 
      });

      // Check for any modal/overlay/lightbox elements
      const modalChecks = await page.evaluate(() => {
        const checks = {
          modalById: document.getElementById('modal') !== null,
          imageModalById: document.getElementById('imageModal') !== null,
          modalByClass: document.querySelector('.modal') !== null,
          overlayByClass: document.querySelector('.overlay') !== null,
          lightboxByClass: document.querySelector('.lightbox') !== null,
          dialogByRole: document.querySelector('[role="dialog"]') !== null,
          visibleModals: [],
          zIndexElements: []
        };

        // Check for visible modal-like elements
        const allElements = document.querySelectorAll('*');
        allElements.forEach(el => {
          const style = window.getComputedStyle(el);
          const rect = el.getBoundingClientRect();
          
          // Check if element is modal-like (high z-index, large, visible)
          if (style.zIndex && parseInt(style.zIndex) > 100) {
            checks.zIndexElements.push({
              tag: el.tagName,
              id: el.id,
              class: el.className,
              zIndex: style.zIndex,
              display: style.display,
              visible: rect.width > 0 && rect.height > 0
            });
          }
          
          // Check for visible overlays
          if (style.position === 'fixed' && rect.width > window.innerWidth * 0.8) {
            checks.visibleModals.push({
              tag: el.tagName,
              id: el.id,
              class: el.className
            });
          }
        });

        return checks;
      });

      console.log('\nModal checks:', JSON.stringify(modalChecks, null, 2));

      // Try to find and close any modal
      const closeSelectors = ['.close', '.modal-close', '[data-dismiss]', 'button[aria-label*="close"]'];
      let closed = false;
      for (const selector of closeSelectors) {
        const closeBtn = await page.$(selector);
        if (closeBtn && await closeBtn.isVisible()) {
          await closeBtn.click();
          console.log(`Closed modal using: ${selector}`);
          closed = true;
          break;
        }
      }

      if (!closed) {
        // Try ESC key
        await page.keyboard.press('Escape');
        console.log('Pressed ESC key');
      }
    }

    // Check for JavaScript modal functions
    const jsCheck = await page.evaluate(() => {
      const globalFunctions = Object.keys(window).filter(key => 
        key.toLowerCase().includes('modal') || 
        key.toLowerCase().includes('lightbox') ||
        key.toLowerCase().includes('gallery')
      );
      
      return {
        globalFunctions,
        hasOpenModal: typeof window.openModal === 'function',
        hasCloseModal: typeof window.closeModal === 'function',
        hasJQuery: typeof window.$ !== 'undefined',
        hasBootstrap: typeof window.bootstrap !== 'undefined'
      };
    });

    console.log('\nJavaScript environment:', JSON.stringify(jsCheck, null, 2));

    // Report console errors
    if (consoleErrors.length > 0) {
      console.log('\n=== Console Errors Found ===');
      consoleErrors.forEach((error, i) => {
        console.log(`Error ${i + 1}:`, error.text);
        if (error.location) {
          console.log(`  Location: ${error.location.url}:${error.location.lineNumber}`);
        }
      });
    } else {
      console.log('\n✓ No console errors detected');
    }
  });

  test('Test photobooks page', async ({ page }) => {
    // Set up console error tracking
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
        console.log(`Console Error: ${msg.text()}`);
      }
    });

    // Navigate to photobooks
    console.log('\nNavigating to photobooks page...');
    await page.goto('https://dalthaus.net/photobooks', { waitUntil: 'networkidle' });

    // Take screenshot
    await page.screenshot({ 
      path: 'testing/screenshots/photobooks-page.png',
      fullPage: true 
    });

    // Check what content is on the page
    const pageAnalysis = await page.evaluate(() => {
      const analysis = {
        title: document.title,
        h1: document.querySelector('h1')?.textContent,
        links: [],
        images: [],
        contentFound: false
      };

      // Get all links
      const links = document.querySelectorAll('a');
      links.forEach(link => {
        if (link.href.includes('photobook')) {
          analysis.links.push({
            href: link.href,
            text: link.textContent.trim().substring(0, 50)
          });
        }
      });

      // Get all images
      const images = document.querySelectorAll('img');
      images.forEach(img => {
        if (img.src && img.width > 50) {
          analysis.images.push({
            src: img.src,
            alt: img.alt
          });
        }
      });

      // Check for content
      analysis.contentFound = analysis.links.length > 0 || analysis.images.length > 0;

      return analysis;
    });

    console.log('\nPhotobooks page analysis:', JSON.stringify(pageAnalysis, null, 2));

    // If we found photobook links, test one
    if (pageAnalysis.links.length > 0) {
      const firstPhotobook = pageAnalysis.links[0].href;
      console.log(`\nNavigating to first photobook: ${firstPhotobook}`);
      
      await page.goto(firstPhotobook, { waitUntil: 'networkidle' });
      
      // Check for images in the photobook
      const photobookImages = await page.$$eval('img', imgs => 
        imgs.map(img => ({
          src: img.src,
          width: img.width,
          onclick: img.onclick ? 'has onclick' : 'no onclick'
        })).filter(img => img.width > 100)
      );

      console.log(`Found ${photobookImages.length} images in photobook`);
      photobookImages.forEach((img, i) => {
        console.log(`  Image ${i + 1}: ${img.onclick}`);
      });

      // Try clicking first image if exists
      const firstImg = await page.$('img');
      if (firstImg) {
        const imgBounds = await firstImg.boundingBox();
        if (imgBounds && imgBounds.width > 100) {
          await firstImg.click();
          console.log('Clicked first photobook image');
          await page.waitForTimeout(1000);
          
          // Check for modal
          const hasModal = await page.evaluate(() => {
            const modal = document.querySelector('.modal, #modal, #imageModal, .lightbox, .overlay');
            return modal && window.getComputedStyle(modal).display !== 'none';
          });
          
          console.log(`Modal appeared: ${hasModal}`);
        }
      }
    }

    // Report errors
    if (consoleErrors.length > 0) {
      console.log('\n=== Console Errors ===');
      consoleErrors.forEach(error => console.log(error));
    }
  });
});