import { test, expect } from '@playwright/test';

test.describe('Debug TinyMCE Button Registration', () => {
  const ADMIN_URL = 'https://dalthaus.net/admin';
  
  test.use({
    viewport: { width: 1920, height: 1080 },
    ignoreHTTPSErrors: true,
  });

  test('Debug TinyMCE button registration process', async ({ page }) => {
    console.log('🔍 Debugging TinyMCE button registration...');
    
    // Monitor console logs
    const logs = [];
    page.on('console', msg => {
      logs.push(`${msg.type()}: ${msg.text()}`);
    });
    
    // Login
    await page.goto(`${ADMIN_URL}/login`);
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForURL(`${ADMIN_URL}/dashboard`);
    
    // Go to content creation
    await page.goto(`${ADMIN_URL}/content/create`);
    await page.waitForTimeout(10000); // Wait longer for full initialization
    
    // Check button registration process
    const registrationStatus = await page.evaluate(() => {
      if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
        const editor = tinymce.activeEditor;
        
        // Check if button is registered
        const buttonRegistered = editor.ui.registry.getAll().buttons.dualimage;
        
        // Get current toolbar
        const toolbar = editor.getContainer().querySelector('.tox-toolbar');
        const allButtons = Array.from(toolbar.querySelectorAll('button'));
        const buttonTexts = allButtons.map(btn => btn.textContent.trim());
        
        // Check manual button injection
        const manualButton = toolbar.querySelector('button[title*="modal" i]') || 
                            toolbar.querySelector('button:has-text("🖼️📱")');
        
        return {
          editorExists: !!editor,
          editorId: editor.id,
          buttonRegistered: !!buttonRegistered,
          buttonRegistration: buttonRegistered || null,
          totalButtons: allButtons.length,
          buttonTexts: buttonTexts,
          manualButtonFound: !!manualButton,
          manualButtonHTML: manualButton ? manualButton.outerHTML : null,
          toolbarHTML: toolbar.outerHTML.substring(0, 1000)
        };
      }
      
      return { error: 'TinyMCE not available' };
    });
    
    console.log('📊 Registration Status:', JSON.stringify(registrationStatus, null, 2));
    
    // Check console logs for dual image related messages
    const dualImageLogs = logs.filter(log => 
      log.toLowerCase().includes('dual') || 
      log.toLowerCase().includes('button') ||
      log.toLowerCase().includes('register')
    );
    
    console.log('📝 Dual Image Related Logs:');
    dualImageLogs.forEach(log => console.log(`  ${log}`));
    
    // Try to manually trigger button registration
    const manualRegistration = await page.evaluate(() => {
      if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
        const editor = tinymce.activeEditor;
        
        try {
          // Try to register the button manually
          editor.ui.registry.addButton('dualimage_manual', {
            text: '🖼️📱',
            tooltip: 'Dual Image (Manual)',
            onAction: () => {
              alert('Manual dual image button clicked!');
            }
          });
          
          return { success: true, message: 'Manual registration successful' };
        } catch (error) {
          return { success: false, error: error.message };
        }
      }
      
      return { success: false, error: 'TinyMCE not available' };
    });
    
    console.log('🔧 Manual Registration Result:', manualRegistration);
    
    // Try to add toolbar button manually
    const manualButtonAdd = await page.evaluate(() => {
      if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
        const editor = tinymce.activeEditor;
        const toolbar = editor.getContainer().querySelector('.tox-toolbar');
        
        if (toolbar) {
          // Find a good place to insert button (after image button)
          const existingImageBtn = toolbar.querySelector('[aria-label*="Image" i], [title*="Image" i]');
          
          if (existingImageBtn && existingImageBtn.parentNode) {
            const dualBtn = document.createElement('button');
            dualBtn.type = 'button';
            dualBtn.innerHTML = '🖼️📱';
            dualBtn.title = 'Dual Image Upload';
            dualBtn.setAttribute('aria-label', 'Dual Image Upload');
            dualBtn.className = existingImageBtn.className;
            dualBtn.style.marginLeft = '4px';
            
            dualBtn.addEventListener('click', () => {
              if (typeof window.showDualImageDialog === 'function') {
                window.showDualImageDialog(editor);
              } else {
                alert('Dual image dialog function not available');
              }
            });
            
            existingImageBtn.parentNode.insertBefore(dualBtn, existingImageBtn.nextSibling);
            
            return { 
              success: true, 
              message: 'Manual button added to toolbar',
              buttonHTML: dualBtn.outerHTML 
            };
          } else {
            return { success: false, error: 'Could not find image button reference' };
          }
        } else {
          return { success: false, error: 'Toolbar not found' };
        }
      }
      
      return { success: false, error: 'TinyMCE not available' };
    });
    
    console.log('🎯 Manual Button Add Result:', manualButtonAdd);
    
    if (manualButtonAdd.success) {
      // Try clicking the manually added button
      await page.waitForTimeout(1000);
      
      const manualButton = page.locator('button[title="Dual Image Upload"]');
      const buttonExists = await manualButton.count() > 0;
      
      if (buttonExists) {
        console.log('✅ Manual button found, attempting to click...');
        await manualButton.click();
        
        // Check for dialog
        await page.waitForTimeout(1000);
        const dialog = page.locator('.dual-image-dialog, [role="dialog"]');
        const dialogVisible = await dialog.isVisible();
        
        if (dialogVisible) {
          console.log('🎉 SUCCESS: Dual image dialog opened from manual button!');
          await page.keyboard.press('Escape');
        } else {
          console.log('❌ Dialog did not open from manual button');
        }
      } else {
        console.log('❌ Manual button not found in DOM');
      }
    }
    
    // Final screenshot
    await page.screenshot({ path: 'testing/screenshots/tinymce-debug-final.png', fullPage: true });
    
    console.log('📸 Debug screenshot saved');
    console.log('📝 All console logs:');
    logs.forEach(log => console.log(`  ${log}`));
  });
});