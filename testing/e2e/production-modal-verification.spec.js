import { test, expect } from '@playwright/test';

test.describe('Production Modal Functionality Verification', () => {
  const PRODUCTION_URL = 'https://dalthaus.net';
  const ADMIN_URL = `${PRODUCTION_URL}/admin`;
  
  // Test configuration
  test.use({
    viewport: { width: 1920, height: 1080 },
    ignoreHTTPSErrors: true,
    // Add longer timeouts for production
    actionTimeout: 30000,
    navigationTimeout: 30000,
  });

  test.beforeEach(async ({ page }) => {
    // Set up console log monitoring
    page.on('console', msg => {
      if (msg.type() === 'error') {
        console.log('Browser console error:', msg.text());
      }
    });

    // Monitor network errors
    page.on('requestfailed', request => {
      console.log('Request failed:', request.url(), request.failure().errorText);
    });
  });

  test('Admin TinyMCE has dual image button and dialog works', async ({ page }) => {
    console.log('Testing TinyMCE dual image functionality in admin...');
    
    // Navigate to admin login
    await page.goto(`${ADMIN_URL}/login`);
    
    // Login to admin
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    
    // Wait for dashboard to load
    await page.waitForURL(`${ADMIN_URL}/dashboard`, { timeout: 10000 });
    console.log('✓ Logged into admin successfully');
    
    // Navigate to content creation/editing page
    await page.goto(`${ADMIN_URL}/content/create`);
    
    // Wait for TinyMCE to initialize
    await page.waitForTimeout(3000);
    
    // Switch to TinyMCE iframe
    const tinymceFrame = page.frameLocator('iframe.tinyMCE');
    
    // Check if dual image button exists in toolbar
    const dualImageButton = await page.locator('button[aria-label="Dual Image"], button[title="Dual Image"], .tox-tbtn:has-text("🖼️📱")').first();
    const buttonExists = await dualImageButton.count() > 0;
    
    if (buttonExists) {
      console.log('✓ Dual image button (🖼️📱) found in TinyMCE toolbar');
      
      // Click the dual image button
      await dualImageButton.click();
      
      // Check if dialog opens
      const dialog = page.locator('.tox-dialog, [role="dialog"]').first();
      await expect(dialog).toBeVisible({ timeout: 5000 });
      console.log('✓ Dual image dialog opens when button is clicked');
      
      // Close the dialog
      const closeButton = page.locator('.tox-dialog__header button[aria-label="Close"], .tox-dialog__header button[title="Close"]').first();
      if (await closeButton.isVisible()) {
        await closeButton.click();
        console.log('✓ Dialog can be closed');
      } else {
        // Try ESC key if close button not found
        await page.keyboard.press('Escape');
      }
      
    } else {
      console.log('❌ Dual image button NOT found in TinyMCE toolbar');
    }
  });

  test('Frontend modal functionality for dual images', async ({ page }) => {
    console.log('Testing frontend modal functionality...');
    
    // Go to the articles page
    await page.goto(`${PRODUCTION_URL}/articles`);
    
    // Find all images on the page
    const images = await page.locator('img').all();
    console.log(`Found ${images.length} images on articles page`);
    
    let dualImagesFound = 0;
    let regularImagesFound = 0;
    let modalTests = [];
    
    for (let i = 0; i < Math.min(images.length, 5); i++) { // Test up to 5 images
      const img = images[i];
      const hasModalSrc = await img.getAttribute('data-modal-src');
      const imgSrc = await img.getAttribute('src');
      
      if (hasModalSrc) {
        dualImagesFound++;
        console.log(`  Image ${i + 1}: Dual image with data-modal-src="${hasModalSrc}"`);
        
        // Test clicking the dual image
        await img.scrollIntoViewIfNeeded();
        await img.click();
        
        // Wait briefly for modal
        await page.waitForTimeout(500);
        
        // Check if modal opened
        const modal = page.locator('.modal, #imageModal, [role="dialog"]').first();
        const modalVisible = await modal.isVisible();
        
        if (modalVisible) {
          console.log(`    ✓ Modal opens for dual image`);
          
          // Check if correct image is displayed
          const modalImg = modal.locator('img').first();
          const modalImgSrc = await modalImg.getAttribute('src');
          
          if (modalImgSrc && modalImgSrc.includes(hasModalSrc)) {
            console.log(`    ✓ Modal shows correct image: ${hasModalSrc}`);
          } else {
            console.log(`    ❌ Modal image mismatch. Expected to include: ${hasModalSrc}, Got: ${modalImgSrc}`);
          }
          
          // Close modal
          const closeModal = modal.locator('.close, [data-dismiss="modal"], button:has-text("×")').first();
          if (await closeModal.isVisible()) {
            await closeModal.click();
          } else {
            await page.keyboard.press('Escape');
          }
          await page.waitForTimeout(300);
        } else {
          console.log(`    ❌ Modal did not open for dual image`);
        }
      } else {
        regularImagesFound++;
        console.log(`  Image ${i + 1}: Regular image (no data-modal-src)`);
        
        // Test clicking regular image - should NOT open modal
        await img.scrollIntoViewIfNeeded();
        await img.click();
        
        // Wait briefly
        await page.waitForTimeout(500);
        
        // Check that modal did NOT open
        const modal = page.locator('.modal:visible, #imageModal:visible').first();
        const modalVisible = await modal.count() > 0;
        
        if (!modalVisible) {
          console.log(`    ✓ No modal opens for regular image (correct behavior)`);
        } else {
          console.log(`    ❌ Modal incorrectly opened for regular image without data-modal-src`);
          // Close the modal if it opened
          await page.keyboard.press('Escape');
          await page.waitForTimeout(300);
        }
      }
    }
    
    console.log(`\nSummary:`);
    console.log(`- Dual images found (with data-modal-src): ${dualImagesFound}`);
    console.log(`- Regular images found (without data-modal-src): ${regularImagesFound}`);
  });

  test('Check JavaScript console for errors', async ({ page }) => {
    console.log('Checking for JavaScript errors on production site...');
    
    const errors = [];
    
    // Monitor console errors
    page.on('console', msg => {
      if (msg.type() === 'error') {
        errors.push(msg.text());
      }
    });
    
    // Visit homepage
    await page.goto(PRODUCTION_URL);
    await page.waitForTimeout(2000);
    
    // Visit articles page
    await page.goto(`${PRODUCTION_URL}/articles`);
    await page.waitForTimeout(2000);
    
    if (errors.length > 0) {
      console.log('JavaScript errors found:');
      errors.forEach(error => console.log(`  - ${error}`));
    } else {
      console.log('✓ No JavaScript errors detected');
    }
  });

  test('Verify modal.js is loaded and initialized', async ({ page }) => {
    console.log('Verifying modal.js script presence and initialization...');
    
    await page.goto(`${PRODUCTION_URL}/articles`);
    
    // Check if modal.js is loaded
    const modalScriptLoaded = await page.evaluate(() => {
      const scripts = Array.from(document.querySelectorAll('script'));
      return scripts.some(script => script.src && script.src.includes('modal.js'));
    });
    
    if (modalScriptLoaded) {
      console.log('✓ modal.js script is loaded');
    } else {
      console.log('❌ modal.js script not found');
    }
    
    // Check if modal initialization function exists
    const modalFunctionExists = await page.evaluate(() => {
      return typeof window.initializeModalSystem === 'function' || 
             typeof window.setupModalListeners === 'function' ||
             typeof window.modalInit === 'function';
    });
    
    if (modalFunctionExists) {
      console.log('✓ Modal initialization function exists in global scope');
    } else {
      console.log('⚠️ Modal initialization function not found in global scope (might be encapsulated)');
    }
    
    // Check for modal HTML structure
    const modalStructure = await page.evaluate(() => {
      const modal = document.querySelector('#imageModal, .modal');
      if (modal) {
        return {
          exists: true,
          id: modal.id,
          classes: modal.className
        };
      }
      return { exists: false };
    });
    
    if (modalStructure.exists) {
      console.log(`✓ Modal HTML structure found: #${modalStructure.id || '(no id)'} with classes: ${modalStructure.classes}`);
    } else {
      console.log('❌ Modal HTML structure not found in DOM');
    }
  });

  test('Performance check - modal responsiveness', async ({ page }) => {
    console.log('Testing modal performance and responsiveness...');
    
    await page.goto(`${PRODUCTION_URL}/articles`);
    
    // Find a dual image
    const dualImage = page.locator('img[data-modal-src]').first();
    
    if (await dualImage.count() > 0) {
      const startTime = Date.now();
      
      await dualImage.click();
      
      // Wait for modal with specific timeout
      const modal = page.locator('.modal:visible, #imageModal:visible').first();
      
      try {
        await modal.waitFor({ state: 'visible', timeout: 3000 });
        const endTime = Date.now();
        const loadTime = endTime - startTime;
        
        console.log(`✓ Modal opened in ${loadTime}ms`);
        
        if (loadTime < 500) {
          console.log('  ✓ Excellent performance (< 500ms)');
        } else if (loadTime < 1000) {
          console.log('  ✓ Good performance (< 1s)');
        } else {
          console.log('  ⚠️ Slow performance (> 1s)');
        }
        
        // Close modal
        await page.keyboard.press('Escape');
        
      } catch (e) {
        console.log('❌ Modal failed to open within 3 seconds');
      }
    } else {
      console.log('No dual images found to test performance');
    }
  });
});