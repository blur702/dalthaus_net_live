import { test, expect } from '@playwright/test';

test.describe('Debug TinyMCE Initialization on Live Site', () => {
  const baseURL = 'https://dalthaus.net';
  const adminCredentials = {
    username: 'kevin',
    password: '(130Bpm)'
  };

  test('Debug TinyMCE setup and custom buttons', async ({ page }) => {
    // Login first
    await page.goto(`${baseURL}/admin/login`);
    await page.fill('input[name="username"]', adminCredentials.username);
    await page.fill('input[name="password"]', adminCredentials.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(`${baseURL}/admin/dashboard`);
    
    // Go to content creation
    await page.goto(`${baseURL}/admin/content/create`);
    await page.waitForTimeout(5000); // Wait for TinyMCE to fully load
    
    // Debug TinyMCE initialization
    const debugInfo = await page.evaluate(() => {
      const info = {
        tinyMCEExists: typeof window.tinymce !== 'undefined',
        tinyMCEVersion: window.tinymce ? window.tinymce.majorVersion : null,
        activeEditor: window.tinymce ? !!window.tinymce.activeEditor : false,
        editorId: window.tinymce && window.tinymce.activeEditor ? window.tinymce.activeEditor.id : null,
        plugins: window.tinymce && window.tinymce.activeEditor ? window.tinymce.activeEditor.settings.plugins : null,
        toolbar: window.tinymce && window.tinymce.activeEditor ? window.tinymce.activeEditor.settings.toolbar : null,
        customButtons: [],
        errors: []
      };
      
      // Check if our custom buttons are registered
      if (window.tinymce && window.tinymce.PluginManager) {
        const registeredPlugins = Object.keys(window.tinymce.PluginManager.urls || {});
        info.registeredPlugins = registeredPlugins;
      }
      
      // Check for our specific buttons
      if (window.tinymce && window.tinymce.activeEditor) {
        const editor = window.tinymce.activeEditor;
        const ui = editor.ui;
        
        // Try to find our custom buttons
        const buttonNames = ['dualimage', 'modalimage', 'testbutton'];
        buttonNames.forEach(name => {
          try {
            const button = ui.registry.getAll().buttons[name];
            if (button) {
              info.customButtons.push({ name, found: true, button });
            } else {
              info.customButtons.push({ name, found: false });
            }
          } catch (e) {
            info.errors.push(`Error checking button ${name}: ${e.message}`);
          }
        });
      }
      
      // Check if tinymce-single.js was loaded
      const scripts = Array.from(document.querySelectorAll('script')).map(s => s.src);
      info.loadedScripts = scripts.filter(src => src.includes('tinymce'));
      
      return info;
    });
    
    console.log('=== TinyMCE Debug Information ===');
    console.log(JSON.stringify(debugInfo, null, 2));
    
    // Check the actual HTML source for TinyMCE initialization
    const pageSource = await page.content();
    
    // Look for TinyMCE initialization code
    const tinyMCEInitMatch = pageSource.match(/tinymce\.init\s*\(\s*\{[\s\S]*?\}\s*\)/);
    if (tinyMCEInitMatch) {
      console.log('=== TinyMCE Initialization Code ===');
      console.log(tinyMCEInitMatch[0]);
    } else {
      console.log('⚠ No TinyMCE initialization code found in page source');
    }
    
    // Check if tinymce-single.js is referenced
    const tinyMCESingleMatch = pageSource.match(/tinymce-single\.js/);
    if (tinyMCESingleMatch) {
      console.log('✓ tinymce-single.js is referenced in the page');
    } else {
      console.log('⚠ tinymce-single.js is NOT referenced in the page');
    }
    
    // Check network requests for JavaScript files
    const requests = [];
    page.on('request', request => {
      if (request.url().includes('tinymce') || request.url().includes('.js')) {
        requests.push({
          url: request.url(),
          status: 'requested'
        });
      }
    });
    
    page.on('response', response => {
      if (response.url().includes('tinymce') || response.url().includes('.js')) {
        const existingRequest = requests.find(r => r.url === response.url());
        if (existingRequest) {
          existingRequest.status = response.status();
        } else {
          requests.push({
            url: response.url(),
            status: response.status()
          });
        }
      }
    });
    
    // Reload the page to capture network requests
    await page.reload();
    await page.waitForTimeout(5000);
    
    console.log('=== Network Requests for JS/TinyMCE Files ===');
    requests.forEach(req => {
      console.log(`${req.status}: ${req.url}`);
    });
    
    // Try to manually check if the custom button code exists in loaded scripts
    const customButtonCode = await page.evaluate(() => {
      // Check if our custom button registration code is present
      const scripts = Array.from(document.querySelectorAll('script'));
      const results = [];
      
      scripts.forEach((script, index) => {
        const content = script.textContent || script.innerHTML;
        if (content.includes('dualimage') || content.includes('modalimage')) {
          results.push({
            index,
            src: script.src || 'inline',
            snippet: content.substring(0, 500) + '...'
          });
        }
      });
      
      return results;
    });
    
    console.log('=== Custom Button Code Found ===');
    console.log(JSON.stringify(customButtonCode, null, 2));
    
    // Take a final screenshot
    await page.screenshot({ 
      path: 'testing/screenshots/live-tinymce-debug.png',
      fullPage: true 
    });
  });
});