const { test, expect } = require('@playwright/test');

test.describe('Content Structure Analysis', () => {
  
  test('Analyze the actual content structure to understand selector issues', async ({ page }) => {
    await page.goto('https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible');
    await page.waitForLoadState('networkidle');
    
    // Analyze the page structure to understand where content is placed
    const contentStructure = await page.evaluate(() => {
      const structure = {
        bodyClasses: document.body.className,
        contentAreas: [],
        allElements: []
      };
      
      // Check for various content container selectors
      const selectors = [
        'main',
        '.content',
        '.content-text', 
        '.prose',
        'article',
        '.article-content',
        '.photobook-content',
        '.post-content',
        '.entry-content',
        '[role="main"]'
      ];
      
      selectors.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        if (elements.length > 0) {
          elements.forEach((el, index) => {
            structure.contentAreas.push({
              selector: selector,
              index: index,
              tagName: el.tagName,
              className: el.className,
              id: el.id,
              textPreview: el.textContent?.substring(0, 100) || '',
              childElementCount: el.children.length
            });
          });
        }
      });
      
      // Get a broader view of the page structure
      const main = document.querySelector('main');
      if (main) {
        structure.mainContent = {
          tagName: main.tagName,
          className: main.className,
          children: Array.from(main.children).map(child => ({
            tagName: child.tagName,
            className: child.className,
            id: child.id,
            textPreview: child.textContent?.substring(0, 50) || ''
          }))
        };
      }
      
      return structure;
    });
    
    console.log('Content Structure Analysis:', JSON.stringify(contentStructure, null, 2));
    
    // Now inject a test image into the main content area and see what happens
    const testResult = await page.evaluate(() => {
      const main = document.querySelector('main');
      if (main) {
        // Create a proper content container
        const contentDiv = document.createElement('div');
        contentDiv.className = 'content-text';
        
        const testImage = document.createElement('img');
        testImage.src = 'https://via.placeholder.com/800x600/ff6600/ffffff?text=Structure+Test';
        testImage.alt = 'Structure Test Image';
        testImage.style.width = '800px';
        testImage.style.height = '600px';
        testImage.style.maxWidth = '100%';
        testImage.style.display = 'block';
        testImage.style.margin = '20px auto';
        
        contentDiv.appendChild(testImage);
        main.appendChild(contentDiv);
        
        return {
          success: true,
          imageAdded: true,
          parentSelector: 'main > .content-text'
        };
      }
      return { success: false, reason: 'No main element found' };
    });
    
    console.log('Test image injection result:', testResult);
    
    await page.waitForTimeout(2000);
    
    // Now check if the image can be found by the selectors
    const selectorTest = await page.evaluate(() => {
      const selectors = [
        '.content-text img',
        '.prose img',
        'article img',
        'main img',
        'img'
      ];
      
      const results = {};
      
      selectors.forEach(selector => {
        const images = document.querySelectorAll(selector);
        results[selector] = {
          count: images.length,
          images: Array.from(images).map(img => ({
            src: img.src.includes('placeholder') ? 'test-image' : 'other',
            width: img.width,
            height: img.height,
            naturalWidth: img.naturalWidth,
            naturalHeight: img.naturalHeight,
            complete: img.complete,
            parentTag: img.parentElement.tagName,
            parentClass: img.parentElement.className
          }))
        };
      });
      
      return results;
    });
    
    console.log('Selector test results:', JSON.stringify(selectorTest, null, 2));
    
    // Test if we can manually add modal functionality to the test image
    const manualModalTest = await page.evaluate(() => {
      const testImage = document.querySelector('img[src*="Structure+Test"]');
      if (testImage && testImage.width > 100 && testImage.height > 50) {
        // Manually add modal functionality
        testImage.setAttribute('data-modal-enabled', 'true');
        testImage.style.cursor = 'pointer';
        testImage.classList.add('modal-image');
        
        testImage.addEventListener('click', function(e) {
          e.preventDefault();
          if (typeof window.openImageModal === 'function') {
            window.openImageModal(this.src, this.alt);
          }
        });
        
        return {
          success: true,
          imageWidth: testImage.width,
          imageHeight: testImage.height,
          hasModalEnabled: testImage.hasAttribute('data-modal-enabled'),
          cursor: window.getComputedStyle(testImage).cursor
        };
      }
      return { success: false, reason: 'Image not found or too small' };
    });
    
    console.log('Manual modal test result:', manualModalTest);
    
    if (manualModalTest.success) {
      // Try clicking the image
      const clickTest = page.locator('img[src*="Structure+Test"]').first();
      await clickTest.click();
      await page.waitForTimeout(1000);
      
      const modalVisible = await page.locator('.image-modal').isVisible();
      console.log('Modal opened after manual setup and click:', modalVisible);
      
      if (modalVisible) {
        console.log('✅ Modal functionality works when properly set up!');
        
        await page.screenshot({ 
          path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/manual-modal-success.png',
          fullPage: true 
        });
        
        // Close the modal
        await page.keyboard.press('Escape');
        await page.waitForTimeout(500);
        console.log('Modal closed successfully');
      } else {
        console.log('❌ Modal still did not open even with manual setup');
      }
    }
  });
});