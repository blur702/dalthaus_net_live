import { test, expect } from '@playwright/test';

test.describe('Admin TinyMCE Dual Image Verification', () => {
  const PRODUCTION_URL = 'https://dalthaus.net';
  const ADMIN_URL = `${PRODUCTION_URL}/admin`;
  
  test.use({
    viewport: { width: 1920, height: 1080 },
    ignoreHTTPSErrors: true,
    actionTimeout: 30000,
    navigationTimeout: 30000,
  });

  test('Verify TinyMCE dual image button in admin editor', async ({ page }) => {
    console.log('Testing admin TinyMCE dual image functionality...');
    
    // Navigate to admin login
    await page.goto(`${ADMIN_URL}/login`);
    console.log('Navigated to admin login page');
    
    // Wait for page to load
    await page.waitForLoadState('networkidle');
    
    // Login to admin
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    
    // Wait for dashboard
    await page.waitForURL(`${ADMIN_URL}/dashboard`, { timeout: 15000 });
    console.log('✓ Successfully logged into admin');
    
    // Navigate to content creation page
    await page.goto(`${ADMIN_URL}/content/create`);
    console.log('Navigated to content creation page');
    
    // Wait for page to fully load
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(5000); // Extra time for TinyMCE to initialize
    
    // Take screenshot for debugging
    await page.screenshot({ path: 'testing/screenshots/admin-content-create.png', fullPage: true });
    console.log('Screenshot taken of content creation page');
    
    // Check if TinyMCE is loaded
    const tinymceExists = await page.evaluate(() => {
      return typeof window.tinymce !== 'undefined';
    });
    
    if (tinymceExists) {
      console.log('✓ TinyMCE is loaded');
      
      // Get TinyMCE editor info
      const editorInfo = await page.evaluate(() => {
        if (window.tinymce && window.tinymce.activeEditor) {
          const editor = window.tinymce.activeEditor;
          const toolbar = editor.getContainer().querySelector('.tox-toolbar');
          const buttons = Array.from(toolbar.querySelectorAll('button'));
          
          return {
            editorId: editor.id,
            buttons: buttons.map(btn => ({
              title: btn.title || btn.getAttribute('aria-label'),
              text: btn.textContent.trim(),
              dataset: Object.assign({}, btn.dataset)
            }))
          };
        }
        return null;
      });
      
      if (editorInfo) {
        console.log(`TinyMCE Editor ID: ${editorInfo.editorId}`);
        console.log('Available buttons:');
        editorInfo.buttons.forEach((btn, index) => {
          console.log(`  ${index + 1}. ${btn.title || btn.text} | Text: "${btn.text}"`);
        });
        
        // Look for dual image button
        const dualImageButton = editorInfo.buttons.find(btn => 
          btn.title && (
            btn.title.toLowerCase().includes('dual') ||
            btn.title.toLowerCase().includes('image') ||
            btn.text.includes('🖼️') ||
            btn.text.includes('📱')
          )
        );
        
        if (dualImageButton) {
          console.log('✓ Found potential dual image button:', dualImageButton);
        } else {
          console.log('❌ No dual image button found in TinyMCE toolbar');
        }
      }
      
      // Check if custom plugin is registered
      const pluginInfo = await page.evaluate(() => {
        if (window.tinymce && window.tinymce.PluginManager) {
          const plugins = Object.keys(window.tinymce.PluginManager.plugins || {});
          return plugins;
        }
        return [];
      });
      
      console.log('Registered TinyMCE plugins:', pluginInfo);
      
      // Look specifically for dual image button in DOM
      const dualImageButtonElement = await page.locator('button[title*="dual" i], button[aria-label*="dual" i], button:has-text("🖼️"), button:has-text("📱")').first();
      const buttonExists = await dualImageButtonElement.count() > 0;
      
      if (buttonExists) {
        console.log('✓ Dual image button found in DOM');
        
        // Try to click it
        try {
          await dualImageButtonElement.click();
          console.log('✓ Clicked dual image button');
          
          // Look for dialog
          await page.waitForTimeout(1000);
          const dialog = await page.locator('.tox-dialog, [role="dialog"]').first();
          const dialogVisible = await dialog.isVisible();
          
          if (dialogVisible) {
            console.log('✓ Dual image dialog opened');
            
            // Close dialog
            await page.keyboard.press('Escape');
          } else {
            console.log('❌ Dual image dialog did not open');
          }
        } catch (e) {
          console.log('❌ Could not click dual image button:', e.message);
        }
      } else {
        console.log('❌ Dual image button not found in DOM');
      }
    } else {
      console.log('❌ TinyMCE is not loaded');
    }
    
    // Check for tinymce-single.js file
    const scripts = await page.evaluate(() => {
      return Array.from(document.querySelectorAll('script[src]')).map(script => script.src);
    });
    
    const tinymceScriptLoaded = scripts.some(src => src.includes('tinymce-single.js'));
    if (tinymceScriptLoaded) {
      console.log('✓ tinymce-single.js script is loaded');
    } else {
      console.log('❌ tinymce-single.js script not found');
      console.log('Loaded scripts:', scripts.filter(src => src.includes('tinymce') || src.includes('tiny')));
    }
  });
  
  test('Check TinyMCE integration file content', async ({ page }) => {
    console.log('Checking TinyMCE integration file...');
    
    // Try to access the tinymce-single.js file directly
    try {
      const response = await page.goto(`${PRODUCTION_URL}/assets/js/tinymce-single.js`);
      const content = await response.text();
      
      if (content.includes('dual') || content.includes('Dual')) {
        console.log('✓ tinymce-single.js contains dual image functionality');
        
        // Check for specific functions
        const hasDualImagePlugin = content.includes('dualimage') || content.includes('dual_image');
        const hasButtonDef = content.includes('🖼️') && content.includes('📱');
        
        console.log(`  - Has dual image plugin definition: ${hasDualImagePlugin}`);
        console.log(`  - Has button icons: ${hasButtonDef}`);
      } else {
        console.log('❌ tinymce-single.js does not contain dual image functionality');
      }
    } catch (e) {
      console.log('❌ Could not access tinymce-single.js file:', e.message);
    }
  });
});