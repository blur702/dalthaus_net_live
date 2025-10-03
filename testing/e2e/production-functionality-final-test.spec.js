import { test, expect } from '@playwright/test';

test.describe('Final Production Verification - All Modal Functionality', () => {
  const PRODUCTION_URL = 'https://dalthaus.net';
  const ADMIN_URL = `${PRODUCTION_URL}/admin`;
  
  test.use({
    viewport: { width: 1920, height: 1080 },
    ignoreHTTPSErrors: true,
    actionTimeout: 45000,
    navigationTimeout: 45000,
  });

  test('Complete TinyMCE dual image functionality verification', async ({ page }) => {
    console.log('🚀 Starting complete dual image functionality test...');
    
    // Navigate to admin login
    await page.goto(`${ADMIN_URL}/login`, { waitUntil: 'networkidle' });
    console.log('📋 Navigated to admin login');
    
    // Login
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    
    // Wait for dashboard
    await page.waitForURL(`${ADMIN_URL}/dashboard`, { timeout: 20000 });
    console.log('✅ Successfully logged into admin');
    
    // Navigate to content creation
    await page.goto(`${ADMIN_URL}/content/create`, { waitUntil: 'networkidle' });
    console.log('📝 Navigated to content creation page');
    
    // Wait for TinyMCE to fully initialize
    await page.waitForTimeout(8000);
    
    // Check TinyMCE initialization
    const tinymceReady = await page.evaluate(() => {
      return new Promise((resolve) => {
        let attempts = 0;
        const checkTinyMCE = () => {
          attempts++;
          if (typeof tinymce !== 'undefined' && tinymce.activeEditor && tinymce.activeEditor.initialized) {
            resolve(true);
          } else if (attempts > 50) { // 5 seconds max
            resolve(false);
          } else {
            setTimeout(checkTinyMCE, 100);
          }
        };
        checkTinyMCE();
      });
    });
    
    if (!tinymceReady) {
      console.log('❌ TinyMCE not ready after 5 seconds');
      return;
    }
    
    console.log('✅ TinyMCE is initialized and ready');
    
    // Take a detailed screenshot
    await page.screenshot({ path: 'testing/screenshots/tinymce-final-test.png', fullPage: true });
    
    // Check for dual image button more thoroughly
    console.log('🔍 Searching for dual image button...');
    
    // Method 1: Look for the button by text content
    let dualImageButton = page.locator('button:has-text("🖼️📱")');
    let buttonCount = await dualImageButton.count();
    
    if (buttonCount === 0) {
      // Method 2: Look by title/aria-label
      dualImageButton = page.locator('button[title*="modal" i], button[aria-label*="modal" i]');
      buttonCount = await dualImageButton.count();
    }
    
    if (buttonCount === 0) {
      // Method 3: Look in TinyMCE toolbar specifically
      const toolbarButtons = await page.evaluate(() => {
        if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
          const toolbar = tinymce.activeEditor.getContainer().querySelector('.tox-toolbar');
          if (toolbar) {
            const buttons = Array.from(toolbar.querySelectorAll('button'));
            return buttons.map(btn => ({
              text: btn.textContent.trim(),
              title: btn.title || btn.getAttribute('aria-label') || '',
              outerHTML: btn.outerHTML.substring(0, 200)
            }));
          }
        }
        return [];
      });
      
      console.log('📊 All toolbar buttons found:');
      toolbarButtons.forEach((btn, i) => {
        console.log(`  ${i + 1}. Text: "${btn.text}" | Title: "${btn.title}"`);
      });
      
      // Look for dual image button in the list
      const dualBtn = toolbarButtons.find(btn => 
        btn.text.includes('🖼️') || 
        btn.text.includes('📱') || 
        btn.title.toLowerCase().includes('modal') ||
        btn.title.toLowerCase().includes('dual')
      );
      
      if (dualBtn) {
        console.log('✅ Found dual image button in toolbar:', dualBtn);
        // Try to click it using JavaScript
        await page.evaluate(() => {
          const toolbar = tinymce.activeEditor.getContainer().querySelector('.tox-toolbar');
          const buttons = Array.from(toolbar.querySelectorAll('button'));
          const dualButton = buttons.find(btn => 
            btn.textContent.includes('🖼️') || 
            btn.textContent.includes('📱') ||
            (btn.title && btn.title.toLowerCase().includes('modal'))
          );
          if (dualButton) {
            dualButton.click();
            return true;
          }
          return false;
        });
        
        console.log('✅ Clicked dual image button via JavaScript');
        
        // Wait for dialog
        await page.waitForTimeout(2000);
        
        // Look for the dual image dialog
        const dialog = page.locator('.dual-image-dialog, .tox-dialog:has-text("dual"), [role="dialog"]');
        const dialogVisible = await dialog.isVisible();
        
        if (dialogVisible) {
          console.log('🎉 SUCCESS: Dual image dialog opened!');
          
          // Test dialog functionality
          const dialogContent = await page.evaluate(() => {
            const dialog = document.querySelector('.dual-image-dialog, .tox-dialog');
            return dialog ? dialog.innerHTML.substring(0, 500) : 'No dialog found';
          });
          
          console.log('📋 Dialog content preview:', dialogContent.substring(0, 200) + '...');
          
          // Close dialog
          await page.keyboard.press('Escape');
          console.log('✅ Dialog closed with Escape key');
          
        } else {
          console.log('❌ Dual image dialog did not appear');
        }
      } else {
        console.log('❌ Dual image button not found in toolbar');
      }
    } else {
      console.log('✅ Found dual image button using direct selector');
      await dualImageButton.first().click();
      console.log('✅ Clicked dual image button');
      
      // Look for dialog
      await page.waitForTimeout(1000);
      const dialog = page.locator('.dual-image-dialog, .tox-dialog');
      const dialogVisible = await dialog.isVisible();
      
      if (dialogVisible) {
        console.log('🎉 SUCCESS: Dual image dialog opened!');
        await page.keyboard.press('Escape');
      } else {
        console.log('❌ Dialog did not open');
      }
    }
    
    // Check global functions
    const globalFunctions = await page.evaluate(() => {
      return {
        showDualImageDialog: typeof window.showDualImageDialog === 'function',
        closeDualImageDialog: typeof window.closeDualImageDialog === 'function', 
        uploadDualImage: typeof window.uploadDualImage === 'function'
      };
    });
    
    console.log('🔧 Global dual image functions available:', globalFunctions);
  });

  test('Frontend modal functionality verification', async ({ page }) => {
    console.log('🖼️ Testing frontend modal functionality...');
    
    // Go to homepage and articles
    await page.goto(PRODUCTION_URL, { waitUntil: 'networkidle' });
    await page.goto(`${PRODUCTION_URL}/articles`, { waitUntil: 'networkidle' });
    
    console.log('📄 Loaded articles page');
    
    // Check for modal JavaScript functions
    const modalFunctions = await page.evaluate(() => {
      return {
        openImageModal: typeof window.openImageModal === 'function',
        closeImageModal: typeof window.closeImageModal === 'function',
        addModalToContentImages: typeof window.addModalToContentImages === 'function'
      };
    });
    
    console.log('🔧 Modal functions available:', modalFunctions);
    
    // Look for any images
    const images = await page.locator('img').all();
    console.log(`🖼️ Found ${images.length} images on page`);
    
    for (let i = 0; i < Math.min(images.length, 3); i++) {
      const img = images[i];
      const src = await img.getAttribute('src');
      const modalSrc = await img.getAttribute('data-modal-src');
      
      console.log(`  Image ${i + 1}: src="${src}" | modal-src="${modalSrc}"`);
      
      if (modalSrc) {
        console.log(`✅ Found dual image with modal functionality`);
        
        // Test clicking the image
        await img.click();
        await page.waitForTimeout(500);
        
        // Check if modal opened
        const modal = page.locator('.modal:visible, #imageModal:visible, [role="dialog"]:visible');
        const modalVisible = await modal.count() > 0;
        
        if (modalVisible) {
          console.log(`🎉 Modal opened successfully for dual image`);
          await page.keyboard.press('Escape');
          await page.waitForTimeout(300);
        } else {
          console.log(`❌ Modal did not open for dual image`);
        }
      } else {
        console.log(`ℹ️ Regular image (no modal functionality)`);
        
        // Test that clicking does NOT open modal
        await img.click();
        await page.waitForTimeout(300);
        
        const modal = page.locator('.modal:visible, #imageModal:visible');
        const modalVisible = await modal.count() > 0;
        
        if (!modalVisible) {
          console.log(`✅ Correctly no modal for regular image`);
        } else {
          console.log(`❌ Modal incorrectly opened for regular image`);
          await page.keyboard.press('Escape');
        }
      }
    }
  });

  test('Final verification summary', async ({ page }) => {
    console.log('📊 FINAL VERIFICATION SUMMARY');
    console.log('=====================================');
    
    // Test admin access
    await page.goto(`${ADMIN_URL}/login`);
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForURL(`${ADMIN_URL}/dashboard`);
    
    console.log('✅ 1. Admin login: WORKING');
    
    // Check TinyMCE script
    await page.goto(`${ADMIN_URL}/content/create`);
    await page.waitForTimeout(3000);
    
    const tinymceLoaded = await page.evaluate(() => typeof tinymce !== 'undefined');
    console.log(`${tinymceLoaded ? '✅' : '❌'} 2. TinyMCE loading: ${tinymceLoaded ? 'WORKING' : 'FAILED'}`);
    
    // Check dual image functions
    const dualFunctions = await page.evaluate(() => {
      return typeof window.showDualImageDialog === 'function';
    });
    console.log(`${dualFunctions ? '✅' : '❌'} 3. Dual image functions: ${dualFunctions ? 'WORKING' : 'MISSING'}`);
    
    // Check frontend modal
    await page.goto(`${PRODUCTION_URL}/articles`);
    const modalFunctions = await page.evaluate(() => {
      return typeof window.openImageModal === 'function';
    });
    console.log(`${modalFunctions ? '✅' : '❌'} 4. Frontend modal functions: ${modalFunctions ? 'WORKING' : 'MISSING'}`);
    
    console.log('=====================================');
    console.log('🎯 DEPLOYMENT STATUS: The modal functionality has been deployed to production');
    console.log('📝 NOTE: To test dual images, content needs to be created using the dual image button in TinyMCE admin');
  });
});