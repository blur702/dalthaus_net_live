import { test, expect } from '@playwright/test';

test.describe('Simple TinyMCE Debug on Live Site', () => {
  const baseURL = 'https://dalthaus.net';
  const adminCredentials = {
    username: 'kevin',
    password: '(130Bpm)'
  };

  test('Check TinyMCE setup and scripts', async ({ page }) => {
    // Login first
    await page.goto(`${baseURL}/admin/login`);
    await page.fill('input[name="username"]', adminCredentials.username);
    await page.fill('input[name="password"]', adminCredentials.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(`${baseURL}/admin/dashboard`);
    
    // Go to content creation
    await page.goto(`${baseURL}/admin/content/create`);
    await page.waitForTimeout(5000); // Wait for TinyMCE to fully load
    
    // Check what TinyMCE exists
    const basicInfo = await page.evaluate(() => {
      return {
        tinyMCEExists: typeof window.tinymce !== 'undefined',
        tinyMCEVersion: window.tinymce ? window.tinymce.majorVersion : null,
        activeEditor: window.tinymce ? !!window.tinymce.activeEditor : false,
        editorCount: window.tinymce ? window.tinymce.editors.length : 0
      };
    });
    
    console.log('=== Basic TinyMCE Info ===');
    console.log(JSON.stringify(basicInfo, null, 2));
    
    // Check the page source for TinyMCE initialization
    const pageSource = await page.content();
    
    // Look for tinymce-single.js
    const hasTinyMCESingle = pageSource.includes('tinymce-single.js');
    console.log(`tinymce-single.js referenced: ${hasTinyMCESingle}`);
    
    // Look for TinyMCE initialization
    const hasInit = pageSource.includes('tinymce.init');
    console.log(`TinyMCE initialization found: ${hasInit}`);
    
    // Check for dual image related code
    const hasDualImage = pageSource.includes('dualimage') || pageSource.includes('modalimage');
    console.log(`Dual image code found: ${hasDualImage}`);
    
    // Get all script tags
    const scripts = await page.evaluate(() => {
      return Array.from(document.querySelectorAll('script')).map(script => ({
        src: script.src || 'inline',
        hasContent: !!script.textContent,
        contentSnippet: script.textContent ? script.textContent.substring(0, 100) : null
      }));
    });
    
    console.log('=== Script Tags ===');
    scripts.forEach((script, index) => {
      if (script.src.includes('tinymce') || script.contentSnippet?.includes('tinymce')) {
        console.log(`${index}: ${script.src} - ${script.contentSnippet || 'external'}`);
      }
    });
    
    // Check specifically for our custom button code
    const customButtonCheck = await page.evaluate(() => {
      const allText = document.documentElement.textContent || document.documentElement.innerText || '';
      return {
        hasDualImageText: allText.includes('dualimage'),
        hasModalImageText: allText.includes('modalimage'),
        hasTestButtonText: allText.includes('testbutton')
      };
    });
    
    console.log('=== Custom Button Text Check ===');
    console.log(JSON.stringify(customButtonCheck, null, 2));
    
    // Try to access TinyMCE editor settings if available
    if (basicInfo.activeEditor) {
      const editorSettings = await page.evaluate(() => {
        const editor = window.tinymce.activeEditor;
        return {
          id: editor.id,
          plugins: editor.settings.plugins,
          toolbar: editor.settings.toolbar,
          buttons: Object.keys(editor.ui.registry.getAll().buttons || {})
        };
      });
      
      console.log('=== Editor Settings ===');
      console.log(JSON.stringify(editorSettings, null, 2));
    }
    
    // Take screenshot
    await page.screenshot({ 
      path: 'testing/screenshots/live-tinymce-simple-debug.png',
      fullPage: true 
    });
  });
});