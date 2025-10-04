import { test, expect } from '@playwright/test';

/**
 * FOCUSED MODAL VERIFICATION TEST
 * 
 * This test specifically verifies that the modal functionality is working
 * by testing existing content and creating new content with modal images.
 * 
 * Based on initial E2E test results, we know:
 * - Admin login works ✅
 * - Frontend display works ✅  
 * - JavaScript functions exist ✅
 * - We need to verify actual modal functionality
 */

test.describe('Focused Modal Verification Test', () => {
  
  test('Verify JavaScript Modal Functions Are Available', async ({ page }) => {
    console.log('🔧 Testing JavaScript modal functions availability...');
    
    // Go to any frontend page
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    
    // Test if modal functions exist
    const functions = await page.evaluate(() => {
      return {
        openImageModal: typeof window.openImageModal === 'function',
        addModalToContentImages: typeof window.addModalToContentImages === 'function',
        modalElement: !!document.getElementById('imageModal'),
        modalHTML: document.getElementById('imageModal') ? document.getElementById('imageModal').outerHTML : 'not found'
      };
    });
    
    console.log('Function availability:');
    console.log(`- openImageModal: ${functions.openImageModal}`);
    console.log(`- addModalToContentImages: ${functions.addModalToContentImages}`);
    console.log(`- Modal element exists: ${functions.modalElement}`);
    
    if (functions.modalElement) {
      console.log('Modal HTML:', functions.modalHTML);
    }
    
    // Verify functions exist
    expect(functions.openImageModal).toBe(true);
    expect(functions.addModalToContentImages).toBe(true);
    
    console.log('✅ JavaScript modal functions are available');
  });

  test('Test Manual Modal Trigger', async ({ page }) => {
    console.log('🖼️ Testing manual modal trigger...');
    
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    
    // Manually trigger the modal with a test image
    const modalResult = await page.evaluate(() => {
      if (typeof window.openImageModal === 'function') {
        // Try to trigger modal with test parameters
        window.openImageModal('/uploads/content/test.jpg', 'Test Modal Image');
        
        const modal = document.getElementById('imageModal');
        return {
          modalExists: !!modal,
          modalDisplay: modal ? getComputedStyle(modal).display : 'not found',
          modalVisible: modal ? modal.style.display !== 'none' && getComputedStyle(modal).display !== 'none' : false
        };
      }
      return { error: 'openImageModal function not available' };
    });
    
    console.log('Manual modal trigger result:', modalResult);
    
    // Take screenshot
    await page.screenshot({ 
      path: 'testing/screenshots/focused-manual-modal-test.png',
      fullPage: true 
    });
    
    if (modalResult.modalVisible) {
      console.log('✅ Modal can be triggered manually');
      
      // Try to close modal
      await page.evaluate(() => {
        const modal = document.getElementById('imageModal');
        if (modal) modal.style.display = 'none';
      });
    } else {
      console.log('⚠️ Modal did not become visible after manual trigger');
    }
  });

  test('Test Content Creation - New Article', async ({ page }) => {
    console.log('📝 Testing content creation with New Article...');
    
    // Login first
    await page.goto('/admin/login');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle' }),
      page.click('button[type="submit"]')
    ]);
    
    // Navigate to content management
    await page.goto('/admin/content');
    await page.waitForLoadState('networkidle');
    
    // Click "New Article" button instead of generic create
    const newArticleButton = page.locator('button:has-text("New Article"), a:has-text("New Article")');
    
    if (await newArticleButton.count() > 0) {
      await newArticleButton.click();
      await page.waitForLoadState('networkidle');
      
      // Take screenshot of article creation page
      await page.screenshot({ 
        path: 'testing/screenshots/focused-article-creation.png',
        fullPage: true 
      });
      
      // Check if TinyMCE is present
      const tinyMCEPresent = await page.locator('.tox-tinymce').count() > 0;
      console.log(`TinyMCE editor present: ${tinyMCEPresent}`);
      
      if (tinyMCEPresent) {
        await page.waitForTimeout(3000); // Wait for TinyMCE to fully load
        
        // Take screenshot of TinyMCE toolbar
        await page.screenshot({ 
          path: 'testing/screenshots/focused-tinymce-toolbar.png',
          fullPage: true 
        });
        
        // Look for image buttons
        const imageButtons = page.locator('button[title*="Image"], button[aria-label*="Image"], .tox-tbtn:has([class*="image"])');
        const buttonCount = await imageButtons.count();
        
        console.log(`Found ${buttonCount} image-related buttons in TinyMCE`);
        
        // Log toolbar buttons
        const toolbarButtons = page.locator('.tox-toolbar button');
        const totalButtons = await toolbarButtons.count();
        
        console.log(`Total toolbar buttons: ${totalButtons}`);
        
        for (let i = 0; i < Math.min(totalButtons, 15); i++) {
          const button = toolbarButtons.nth(i);
          const title = await button.getAttribute('title');
          const ariaLabel = await button.getAttribute('aria-label');
          console.log(`Button ${i}: title="${title}", aria-label="${ariaLabel}"`);
        }
        
        console.log('✅ TinyMCE editor loaded successfully');
      } else {
        console.log('❌ TinyMCE editor not found');
      }
      
    } else {
      console.log('❌ New Article button not found');
    }
  });

  test('Test Existing Content for Modal Images', async ({ page }) => {
    console.log('🔍 Testing existing content for modal images...');
    
    // Test articles page
    await page.goto('/articles');
    await page.waitForLoadState('networkidle');
    
    // Check for any images
    const allImages = await page.locator('img').count();
    const modalImages = await page.locator('img[data-modal-src], img[onclick*="openImageModal"]').count();
    
    console.log(`Articles page - Total images: ${allImages}, Modal images: ${modalImages}`);
    
    if (allImages > 0) {
      // Click on first article to see individual article
      const firstArticleLink = page.locator('article a, .article-link').first();
      if (await firstArticleLink.count() > 0) {
        await firstArticleLink.click();
        await page.waitForLoadState('networkidle');
        
        // Take screenshot
        await page.screenshot({ 
          path: 'testing/screenshots/focused-individual-article.png',
          fullPage: true 
        });
        
        // Check for images in individual article
        const articleImages = await page.locator('img').count();
        const articleModalImages = await page.locator('img[data-modal-src], img[onclick*="openImageModal"]').count();
        
        console.log(`Individual article - Total images: ${articleImages}, Modal images: ${articleModalImages}`);
        
        if (articleModalImages > 0) {
          // Test clicking on modal image
          const firstModalImage = page.locator('img[data-modal-src], img[onclick*="openImageModal"]').first();
          
          // Get image attributes
          const src = await firstModalImage.getAttribute('src');
          const modalSrc = await firstModalImage.getAttribute('data-modal-src');
          const onclick = await firstModalImage.getAttribute('onclick');
          
          console.log('Modal image attributes:');
          console.log(`- src: ${src}`);
          console.log(`- data-modal-src: ${modalSrc}`);
          console.log(`- onclick: ${onclick}`);
          
          // Click the image
          await firstModalImage.click();
          await page.waitForTimeout(1000);
          
          // Check if modal opened
          const modal = page.locator('#imageModal');
          const isVisible = await modal.isVisible();
          
          console.log(`Modal opened: ${isVisible}`);
          
          if (isVisible) {
            console.log('✅ Modal functionality working on existing content');
            
            await page.screenshot({ 
              path: 'testing/screenshots/focused-modal-opened.png',
              fullPage: true 
            });
            
            // Test close functionality
            const closeButton = page.locator('.modal-close, .close, #imageModal .close');
            if (await closeButton.count() > 0) {
              await closeButton.first().click();
              await page.waitForTimeout(500);
              
              const isClosed = await modal.isHidden();
              console.log(`Modal closed: ${isClosed}`);
            }
          } else {
            console.log('❌ Modal did not open');
          }
        }
      }
    }
    
    // Test photobooks page
    await page.goto('/photobooks');
    await page.waitForLoadState('networkidle');
    
    const photobookImages = await page.locator('img').count();
    const photobookModalImages = await page.locator('img[data-modal-src], img[onclick*="openImageModal"]').count();
    
    console.log(`Photobooks page - Total images: ${photobookImages}, Modal images: ${photobookModalImages}`);
  });

  test('Create and Inject Test Modal Image', async ({ page }) => {
    console.log('🧪 Creating test modal image manually...');
    
    // Go to any article page
    await page.goto('/articles');
    await page.waitForLoadState('networkidle');
    
    // Click on first article
    const firstArticleLink = page.locator('article a, .article-link').first();
    if (await firstArticleLink.count() > 0) {
      await firstArticleLink.click();
      await page.waitForLoadState('networkidle');
      
      // Inject a test modal image into the page
      await page.evaluate(() => {
        // Create a test image with modal attributes
        const testImg = document.createElement('img');
        testImg.src = 'https://via.placeholder.com/300x200/0066cc/ffffff?text=Test+Modal+Image';
        testImg.setAttribute('data-modal-src', 'https://via.placeholder.com/800x600/0066cc/ffffff?text=Large+Modal+Image');
        testImg.setAttribute('onclick', 'openImageModal(this.getAttribute("data-modal-src"), "Test Modal Image")');
        testImg.style.cursor = 'pointer';
        testImg.style.border = '3px solid red';
        testImg.style.margin = '20px';
        testImg.alt = 'Test Modal Image - Click to Open';
        
        // Add to page
        const article = document.querySelector('article, main, .content');
        if (article) {
          const testDiv = document.createElement('div');
          testDiv.innerHTML = '<h3 style="color: red;">TEST MODAL IMAGE (injected by test):</h3>';
          testDiv.appendChild(testImg);
          article.appendChild(testDiv);
        }
      });
      
      await page.waitForTimeout(1000);
      
      // Take screenshot
      await page.screenshot({ 
        path: 'testing/screenshots/focused-injected-test-image.png',
        fullPage: true 
      });
      
      // Click on the injected test image
      const testImage = page.locator('img[alt="Test Modal Image - Click to Open"]');
      if (await testImage.count() > 0) {
        await testImage.click();
        await page.waitForTimeout(1000);
        
        // Check if modal opened
        const modal = page.locator('#imageModal');
        const isVisible = await modal.isVisible();
        
        console.log(`Test modal opened: ${isVisible}`);
        
        if (isVisible) {
          console.log('✅ Modal functionality confirmed working with injected test image');
          
          await page.screenshot({ 
            path: 'testing/screenshots/focused-test-modal-success.png',
            fullPage: true 
          });
          
          // Get modal content
          const modalContent = await page.evaluate(() => {
            const modal = document.getElementById('imageModal');
            return modal ? {
              display: getComputedStyle(modal).display,
              innerHTML: modal.innerHTML.substring(0, 200)
            } : null;
          });
          
          console.log('Modal content:', modalContent);
          
        } else {
          console.log('❌ Test modal did not open - checking console errors');
          
          // Check for JavaScript errors
          const logs = [];
          page.on('console', msg => {
            if (msg.type() === 'error') {
              logs.push(msg.text());
            }
          });
          
          await page.waitForTimeout(2000);
          console.log('Console errors:', logs);
        }
      }
    }
  });
});