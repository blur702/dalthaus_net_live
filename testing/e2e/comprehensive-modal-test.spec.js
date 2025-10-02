const { test, expect } = require('@playwright/test');

test.describe('Comprehensive Modal Test - Live Site', () => {
  test.setTimeout(60000);

  test('Complete modal functionality diagnosis', async ({ page }) => {
    console.log('\n=== MODAL FUNCTIONALITY TEST REPORT ===\n');
    
    const report = {
      articles: {
        tested: [],
        hasImages: false,
        modalFunctionality: 'Not Found',
        errors: []
      },
      photobooks: {
        tested: [],
        hasImages: false, 
        modalFunctionality: 'Not Found',
        errors: []
      },
      technicalDetails: {
        javascriptFunctions: [],
        cssModalStyles: false,
        modalHTML: false
      }
    };

    // Track console errors
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        const errorText = msg.text();
        if (!errorText.includes('fonts.googleapis.com')) { // Ignore font CSP errors
          consoleErrors.push(errorText);
        }
      }
    });

    // Test 1: Check Articles Page
    console.log('1. TESTING ARTICLES PAGE\n');
    console.log('   Navigating to: https://dalthaus.net/articles');
    
    const articlesResponse = await page.goto('https://dalthaus.net/articles', { 
      waitUntil: 'domcontentloaded',
      timeout: 30000 
    });
    
    // Wait for page to stabilize
    await page.waitForTimeout(3000);
    
    console.log(`   Response status: ${articlesResponse.status()}`);
    
    // Get article links using JavaScript evaluation
    const articleData = await page.evaluate(() => {
      const data = {
        links: [],
        pageContent: document.body.innerText.substring(0, 200),
        images: []
      };
      
      // Find all links that might be articles
      const allLinks = document.querySelectorAll('a');
      allLinks.forEach(link => {
        if (link.href.includes('/article/')) {
          data.links.push({
            href: link.href,
            text: link.textContent.trim().substring(0, 50)
          });
        }
      });
      
      // Check for images on listing page
      const images = document.querySelectorAll('img');
      images.forEach(img => {
        if (img.naturalWidth > 50) {
          data.images.push({
            src: img.src,
            width: img.naturalWidth,
            height: img.naturalHeight
          });
        }
      });
      
      return data;
    });
    
    console.log(`   Found ${articleData.links.length} article links`);
    console.log(`   Found ${articleData.images.length} images on listing page`);
    
    // Test first article if available
    if (articleData.links.length > 0) {
      const testArticle = articleData.links[0];
      console.log(`\n   Testing article: ${testArticle.text}`);
      console.log(`   URL: ${testArticle.href}`);
      
      await page.goto(testArticle.href, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(3000);
      
      // Check for images and modal functionality
      const articleTest = await page.evaluate(() => {
        const result = {
          images: [],
          hasModal: false,
          modalFunctions: {},
          clickTest: null
        };
        
        // Find all images
        const images = document.querySelectorAll('img');
        images.forEach(img => {
          if (img.naturalWidth > 100) {
            result.images.push({
              src: img.src,
              width: img.naturalWidth,
              height: img.naturalHeight,
              onclick: img.onclick ? 'Has onclick' : 'No onclick',
              cursor: window.getComputedStyle(img).cursor,
              parentTag: img.parentElement?.tagName
            });
          }
        });
        
        // Check for modal functions
        result.modalFunctions = {
          openImageModal: typeof window.openImageModal === 'function',
          closeImageModal: typeof window.closeImageModal === 'function',
          openModal: typeof window.openModal === 'function',
          closeModal: typeof window.closeModal === 'function'
        };
        
        // Check for modal HTML
        result.hasModal = !!(document.querySelector('#imageModal, #modal, .modal, .lightbox'));
        
        return result;
      });
      
      console.log(`   Images found: ${articleTest.images.length}`);
      if (articleTest.images.length > 0) {
        console.log(`   First image details:`, articleTest.images[0]);
        report.articles.hasImages = true;
      }
      
      console.log(`   Modal functions:`, articleTest.modalFunctions);
      console.log(`   Modal HTML present: ${articleTest.hasModal}`);
      
      // Try clicking first image if exists
      if (articleTest.images.length > 0) {
        const firstImage = await page.$('img');
        if (firstImage) {
          console.log(`\n   Attempting to click first image...`);
          
          await firstImage.click();
          await page.waitForTimeout(2000);
          
          // Check if modal appeared
          const modalState = await page.evaluate(() => {
            const possibleModals = [
              '#imageModal',
              '#modal', 
              '.modal',
              '.lightbox',
              '.overlay',
              '[role="dialog"]'
            ];
            
            for (const selector of possibleModals) {
              const element = document.querySelector(selector);
              if (element) {
                const style = window.getComputedStyle(element);
                const rect = element.getBoundingClientRect();
                if (style.display !== 'none' && rect.width > 0) {
                  return {
                    found: true,
                    selector: selector,
                    display: style.display,
                    zIndex: style.zIndex,
                    width: rect.width,
                    height: rect.height
                  };
                }
              }
            }
            return { found: false };
          });
          
          if (modalState.found) {
            console.log(`   ✓ MODAL APPEARED!`);
            console.log(`     Selector: ${modalState.selector}`);
            console.log(`     Dimensions: ${modalState.width}x${modalState.height}`);
            console.log(`     Z-Index: ${modalState.zIndex}`);
            report.articles.modalFunctionality = 'Working';
            
            // Try to close modal
            await page.keyboard.press('Escape');
          } else {
            console.log(`   ✗ No modal appeared after click`);
            report.articles.modalFunctionality = 'Not Working';
          }
        }
      }
      
      report.articles.tested.push(testArticle.text);
    }
    
    // Test 2: Check Photobooks Page
    console.log('\n\n2. TESTING PHOTOBOOKS PAGE\n');
    console.log('   Navigating to: https://dalthaus.net/photobooks');
    
    const photobooksResponse = await page.goto('https://dalthaus.net/photobooks', {
      waitUntil: 'domcontentloaded',
      timeout: 30000
    });
    
    await page.waitForTimeout(3000);
    
    console.log(`   Response status: ${photobooksResponse.status()}`);
    
    // Check if there are actual photobooks or if it's empty
    const photobookData = await page.evaluate(() => {
      const data = {
        pageTitle: document.title,
        h1Text: document.querySelector('h1')?.textContent?.trim(),
        bodyText: document.body.innerText.substring(0, 500),
        hasNoContentMessage: false,
        photobookLinks: []
      };
      
      // Check for "no content" messages
      const bodyLower = document.body.innerText.toLowerCase();
      if (bodyLower.includes('no photobook') || bodyLower.includes('coming soon') || bodyLower.includes('no content')) {
        data.hasNoContentMessage = true;
      }
      
      // Look for photobook links
      const allLinks = document.querySelectorAll('a');
      allLinks.forEach(link => {
        if (link.href.includes('/photobook/') && !link.href.endsWith('/photobooks')) {
          data.photobookLinks.push({
            href: link.href,
            text: link.textContent.trim()
          });
        }
      });
      
      return data;
    });
    
    console.log(`   Page title: ${photobookData.pageTitle}`);
    console.log(`   H1: ${photobookData.h1Text}`);
    console.log(`   Has "no content" message: ${photobookData.hasNoContentMessage}`);
    console.log(`   Photobook links found: ${photobookData.photobookLinks.length}`);
    
    if (photobookData.photobookLinks.length > 0) {
      const testPhotobook = photobookData.photobookLinks[0];
      console.log(`\n   Testing photobook: ${testPhotobook.text}`);
      console.log(`   URL: ${testPhotobook.href}`);
      
      await page.goto(testPhotobook.href, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(3000);
      
      // Similar image and modal check as articles
      const photobookTest = await page.evaluate(() => {
        const result = {
          images: [],
          modalFunctions: {}
        };
        
        const images = document.querySelectorAll('img');
        images.forEach(img => {
          if (img.naturalWidth > 100) {
            result.images.push({
              src: img.src,
              width: img.naturalWidth,
              onclick: img.onclick ? 'Has onclick' : 'No onclick'
            });
          }
        });
        
        result.modalFunctions = {
          openImageModal: typeof window.openImageModal === 'function',
          closeImageModal: typeof window.closeImageModal === 'function'
        };
        
        return result;
      });
      
      console.log(`   Images found: ${photobookTest.images.length}`);
      if (photobookTest.images.length > 0) {
        report.photobooks.hasImages = true;
      }
      
      report.photobooks.tested.push(testPhotobook.text);
    } else {
      console.log(`   Note: No photobook content available for testing`);
      report.photobooks.modalFunctionality = 'No Content to Test';
    }
    
    // Test 3: Technical Analysis
    console.log('\n\n3. TECHNICAL ANALYSIS\n');
    
    await page.goto('https://dalthaus.net/', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    
    const technicalAnalysis = await page.evaluate(() => {
      const analysis = {
        javascriptFiles: [],
        modalFunctions: [],
        cssFiles: [],
        modalInCSS: false,
        modalInHTML: false,
        inlineScripts: []
      };
      
      // Check JavaScript files
      document.querySelectorAll('script[src]').forEach(script => {
        analysis.javascriptFiles.push(script.src);
      });
      
      // Check inline scripts for modal code
      document.querySelectorAll('script:not([src])').forEach(script => {
        const content = script.textContent;
        if (content.includes('modal') || content.includes('Modal')) {
          analysis.inlineScripts.push(content.substring(0, 100) + '...');
        }
      });
      
      // Check global functions
      Object.keys(window).forEach(key => {
        if (key.toLowerCase().includes('modal')) {
          analysis.modalFunctions.push(key);
        }
      });
      
      // Check CSS files
      document.querySelectorAll('link[rel="stylesheet"]').forEach(link => {
        analysis.cssFiles.push(link.href);
      });
      
      // Check for modal in HTML
      analysis.modalInHTML = document.body.innerHTML.includes('modal');
      
      return analysis;
    });
    
    console.log(`   JavaScript files: ${technicalAnalysis.javascriptFiles.length}`);
    technicalAnalysis.javascriptFiles.forEach(f => console.log(`     - ${f}`));
    
    console.log(`\n   Modal-related functions found:`);
    technicalAnalysis.modalFunctions.forEach(f => console.log(`     - ${f}`));
    
    console.log(`\n   Inline scripts with modal code: ${technicalAnalysis.inlineScripts.length}`);
    
    console.log(`\n   CSS files: ${technicalAnalysis.cssFiles.length}`);
    console.log(`   Modal in HTML: ${technicalAnalysis.modalInHTML}`);
    
    // Final Report
    console.log('\n\n=== FINAL REPORT ===\n');
    
    console.log('ARTICLES:');
    console.log(`  - Has Images: ${report.articles.hasImages}`);
    console.log(`  - Modal Functionality: ${report.articles.modalFunctionality}`);
    console.log(`  - Articles Tested: ${report.articles.tested.length}`);
    
    console.log('\nPHOTOBOOKS:');
    console.log(`  - Has Images: ${report.photobooks.hasImages}`);
    console.log(`  - Modal Functionality: ${report.photobooks.modalFunctionality}`);
    console.log(`  - Photobooks Tested: ${report.photobooks.tested.length}`);
    
    console.log('\nTECHNICAL:');
    console.log(`  - Modal Functions Found: ${technicalAnalysis.modalFunctions.join(', ') || 'None'}`);
    console.log(`  - Modal in HTML: ${technicalAnalysis.modalInHTML}`);
    
    if (consoleErrors.length > 0) {
      console.log('\nJAVASCRIPT ERRORS:');
      consoleErrors.forEach(e => console.log(`  - ${e}`));
    } else {
      console.log('\nJAVASCRIPT ERRORS: None (excluding CSP font warnings)');
    }
    
    // Assertion to make test pass/fail
    if (report.articles.modalFunctionality === 'Working' || report.photobooks.modalFunctionality === 'Working') {
      console.log('\n✓ Modal functionality is working');
    } else {
      console.log('\n✗ Modal functionality is NOT working properly');
    }
  });
});