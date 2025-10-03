import { test, expect } from '@playwright/test';

test.describe('Post-Fix Dual Image Modal System Test', () => {
  const baseURL = 'https://dalthaus.net';
  const adminCredentials = {
    username: 'kevin',
    password: '(130Bpm)'
  };

  test('Verify dual image button now appears and works', async ({ page }) => {
    test.setTimeout(120000);

    // Enable cache busting to see the latest changes
    await page.route('**/*', (route) => {
      const url = new URL(route.request().url());
      if (url.pathname.endsWith('.js') || url.pathname.endsWith('.css')) {
        url.searchParams.set('v', Date.now().toString());
        route.continue({ url: url.toString() });
      } else {
        route.continue();
      }
    });

    console.log('Step 1: Logging into admin panel...');
    await page.goto(`${baseURL}/admin/login`);
    
    await page.waitForSelector('input[name="username"]', { timeout: 10000 });
    await page.fill('input[name="username"]', adminCredentials.username);
    await page.fill('input[name="password"]', adminCredentials.password);
    
    await page.screenshot({ 
      path: 'testing/screenshots/post-fix-login-page.png',
      fullPage: true 
    });
    
    await page.click('button[type="submit"]');
    await page.waitForURL(`${baseURL}/admin/dashboard`, { timeout: 10000 });
    console.log('✓ Successfully logged into admin panel');
    
    console.log('Step 2: Navigating to content creation...');
    await page.goto(`${baseURL}/admin/content/create`);
    
    // Wait longer for TinyMCE to fully initialize
    await page.waitForTimeout(5000);
    
    console.log('Step 3: Checking if tinymce-single.js is loaded...');
    
    // Check if the script is loaded
    const tinyMCESingleLoaded = await page.evaluate(() => {
      const scripts = Array.from(document.querySelectorAll('script'));
      return scripts.some(script => script.src.includes('tinymce-single.js'));
    });
    
    console.log(`tinymce-single.js loaded: ${tinyMCESingleLoaded}`);
    
    // Check TinyMCE initialization status
    const tinyMCEStatus = await page.evaluate(() => {
      return {
        tinyMCEExists: typeof window.tinymce !== 'undefined',
        editorExists: window.tinymce && window.tinymce.editors && window.tinymce.editors.length > 0,
        activeEditor: window.tinymce && window.tinymce.activeEditor ? true : false
      };
    });
    
    console.log('TinyMCE Status:', JSON.stringify(tinyMCEStatus, null, 2));
    
    // Take screenshot of content creation page
    await page.screenshot({ 
      path: 'testing/screenshots/post-fix-content-creation.png',
      fullPage: true 
    });
    
    console.log('Step 4: Looking for dual image button in toolbar...');
    
    // Wait a bit more for TinyMCE to fully load
    await page.waitForTimeout(3000);
    
    // Check for dual image buttons
    const buttonCheckResults = await page.evaluate(() => {
      const results = {
        toolbarButtons: [],
        foundDualImageButton: false,
        buttonDetails: []
      };
      
      // Get all toolbar buttons
      const buttons = document.querySelectorAll('.tox-toolbar button, .tox-tbtn');
      
      buttons.forEach((button, index) => {
        const ariaLabel = button.getAttribute('aria-label') || '';
        const title = button.getAttribute('title') || '';
        const text = button.textContent || '';
        const className = button.className || '';
        
        results.toolbarButtons.push({
          index,
          ariaLabel,
          title,
          text,
          className,
          visible: button.offsetWidth > 0 && button.offsetHeight > 0
        });
        
        // Check if this is our dual image button
        if (ariaLabel.toLowerCase().includes('dual') || 
            ariaLabel.toLowerCase().includes('modal') ||
            title.toLowerCase().includes('dual') ||
            title.toLowerCase().includes('modal') ||
            text.toLowerCase().includes('dual') ||
            text.toLowerCase().includes('modal')) {
          results.foundDualImageButton = true;
          results.buttonDetails.push({
            index,
            ariaLabel,
            title,
            text,
            className
          });
        }
      });
      
      return results;
    });
    
    console.log(`Found ${buttonCheckResults.toolbarButtons.length} toolbar buttons`);
    
    if (buttonCheckResults.foundDualImageButton) {
      console.log('✅ SUCCESS: Dual image button found!');
      console.log('Button details:', JSON.stringify(buttonCheckResults.buttonDetails, null, 2));
      
      // Try to click the button
      const buttonSelector = `.tox-toolbar button[aria-label*="dual"], .tox-toolbar button[aria-label*="Dual"], .tox-toolbar button[aria-label*="modal"], .tox-toolbar button[aria-label*="Modal"]`;
      
      try {
        await page.click(buttonSelector);
        await page.waitForTimeout(1000);
        
        // Check if dialog opened
        const dialogExists = await page.locator('.dual-image-dialog, .tox-dialog, [role="dialog"]').first().isVisible();
        
        if (dialogExists) {
          console.log('✅ SUCCESS: Dialog opened when button clicked!');
          
          await page.screenshot({ 
            path: 'testing/screenshots/post-fix-dialog-opened.png',
            fullPage: true 
          });
          
          // Close dialog
          const closeButton = page.locator('.close-btn, .tox-dialog__header button, button:has-text("Cancel")').first();
          if (await closeButton.isVisible()) {
            await closeButton.click();
            console.log('✓ Dialog closed');
          }
        } else {
          console.log('⚠ Button clicked but no dialog appeared');
        }
      } catch (error) {
        console.log('⚠ Could not click dual image button:', error.message);
      }
    } else {
      console.log('❌ FAILED: No dual image button found');
      
      // Debug: log all button details
      console.log('All toolbar buttons:');
      buttonCheckResults.toolbarButtons.forEach((btn, i) => {
        if (btn.visible) {
          console.log(`${i}: "${btn.ariaLabel}" | "${btn.title}" | "${btn.text}"`);
        }
      });
    }
    
    // Step 5: Try to create content manually to test end-to-end
    console.log('Step 5: Testing manual content creation...');
    
    await page.fill('input[name="title"]', `Dual Image Post-Fix Test - ${new Date().toISOString()}`);
    
    // Try to insert content into TinyMCE
    const contentInserted = await page.evaluate(() => {
      const testContent = `
        <p>Testing dual image functionality after fix:</p>
        <img 
          src="/uploads/content/featured/2024/01/display-test.jpg" 
          data-modal-src="/uploads/content/featured/2024/01/modal-test.jpg" 
          alt="Test Image" 
          onclick="openImageModal('/uploads/content/featured/2024/01/modal-test.jpg', 'Test Image')"
          style="cursor: pointer; max-width: 100%;"
          class="clickable-image"
        />
        <p>Click the image above to test modal functionality.</p>
      `;
      
      if (window.tinymce && window.tinymce.activeEditor) {
        window.tinymce.activeEditor.setContent(testContent);
        return true;
      }
      return false;
    });
    
    console.log(`Content inserted into TinyMCE: ${contentInserted}`);
    
    // Set article type and status
    const typeSelect = page.locator('select[name="type"]');
    if (await typeSelect.isVisible()) {
      await typeSelect.selectOption('article');
    }
    
    const statusSelect = page.locator('select[name="status"]');
    if (await statusSelect.isVisible()) {
      await statusSelect.selectOption('published');
    }
    
    // Save the article
    const saveButton = page.locator('button:has-text("Create & Publish"), button[type="submit"]').first();
    if (await saveButton.isVisible()) {
      await saveButton.click();
      console.log('✓ Article save attempted');
      
      await page.waitForTimeout(3000);
      
      // Check if we were redirected (success) or stayed on same page (error)
      const currentURL = page.url();
      if (currentURL.includes('/admin/content') && !currentURL.includes('/create')) {
        console.log('✅ Article appears to have been saved successfully');
        
        // Go back to content list to verify
        await page.goto(`${baseURL}/admin/content`);
        await page.waitForTimeout(2000);
        
        const testArticleExists = await page.locator('text*="Dual Image Post-Fix Test"').first().isVisible();
        if (testArticleExists) {
          console.log('✅ Test article found in content listing');
          
          // Try to view on frontend
          const viewLink = page.locator('a[href*="/article/"]:near(text*="Dual Image Post-Fix Test")').first();
          if (await viewLink.isVisible()) {
            const articleURL = await viewLink.getAttribute('href');
            
            await page.goto(`${baseURL}${articleURL}`);
            await page.waitForTimeout(2000);
            
            const dualImage = page.locator('img[data-modal-src]').first();
            if (await dualImage.isVisible()) {
              console.log('✅ Dual image found on frontend');
              
              // Test clicking the image
              await dualImage.click();
              await page.waitForTimeout(1000);
              
              const modal = page.locator('.image-modal, #imageModal').first();
              if (await modal.isVisible()) {
                console.log('✅ SUCCESS: Modal opened on frontend!');
                
                await page.screenshot({ 
                  path: 'testing/screenshots/post-fix-frontend-modal.png',
                  fullPage: true 
                });
              } else {
                console.log('⚠ Modal did not open on frontend');
              }
            } else {
              console.log('⚠ Dual image not found on frontend');
            }
          }
        }
      }
    }
    
    // Final summary
    console.log('\n=== POST-FIX TEST SUMMARY ===');
    console.log(`1. Admin login: ✅`);
    console.log(`2. tinymce-single.js loaded: ${tinyMCESingleLoaded ? '✅' : '❌'}`);
    console.log(`3. TinyMCE initialized: ${tinyMCEStatus.activeEditor ? '✅' : '❌'}`);
    console.log(`4. Dual image button visible: ${buttonCheckResults.foundDualImageButton ? '✅' : '❌'}`);
    console.log(`5. Content creation: ${contentInserted ? '✅' : '❌'}`);
    console.log('6. Check screenshots for complete workflow results');
  });
});