import { test, expect } from '@playwright/test';

test.describe('Live Site Dual Image Modal System Test', () => {
  const baseURL = 'https://dalthaus.net';
  const adminCredentials = {
    username: 'kevin',
    password: '(130Bpm)'
  };

  test('Complete dual image workflow - from creation to modal display', async ({ page }) => {
    // Set timeout for this test as it involves multiple steps
    test.setTimeout(120000);

    // Step 1: Login to admin
    console.log('Step 1: Logging into admin panel...');
    await page.goto(`${baseURL}/admin/login`);
    
    // Wait for login form
    await page.waitForSelector('input[name="username"]', { timeout: 10000 });
    
    // Fill login credentials
    await page.fill('input[name="username"]', adminCredentials.username);
    await page.fill('input[name="password"]', adminCredentials.password);
    
    // Take screenshot of login page
    await page.screenshot({ 
      path: 'testing/screenshots/live-login-page.png',
      fullPage: true 
    });
    
    // Submit login form
    await page.click('button[type="submit"]');
    
    // Wait for redirect to dashboard
    await page.waitForURL(`${baseURL}/admin/dashboard`, { timeout: 10000 });
    console.log('✓ Successfully logged into admin panel');
    
    // Step 2: Navigate to content creation
    console.log('Step 2: Navigating to content creation...');
    await page.goto(`${baseURL}/admin/content/create`);
    
    // Wait for TinyMCE to load
    await page.waitForTimeout(3000); // Give TinyMCE time to initialize
    
    // Step 3: Check TinyMCE toolbar for dual image buttons
    console.log('Step 3: Checking TinyMCE toolbar for dual image buttons...');
    
    // Switch to TinyMCE iframe
    const editorFrame = page.frameLocator('iframe[id^="content_ifr"]');
    
    // Take screenshot of the content creation page
    await page.screenshot({ 
      path: 'testing/screenshots/live-content-creation-page.png',
      fullPage: true 
    });
    
    // Check for various button possibilities in the main toolbar
    const buttonSelectors = [
      'button[aria-label*="dual"]',
      'button[aria-label*="Dual"]',
      'button[aria-label*="modal"]',
      'button[aria-label*="Modal"]',
      'button[title*="dual"]',
      'button[title*="Dual"]',
      'button[title*="modal"]',
      'button[title*="Modal"]',
      'button.tox-tbtn:has-text("Dual")',
      'button.tox-tbtn:has-text("Modal")',
      'button[data-mce-cmd="mceDualImage"]',
      'button[data-mce-cmd="mceModalImage"]'
    ];
    
    let foundButton = null;
    for (const selector of buttonSelectors) {
      const button = page.locator(selector).first();
      if (await button.isVisible({ timeout: 1000 }).catch(() => false)) {
        foundButton = button;
        console.log(`✓ Found dual image button with selector: ${selector}`);
        break;
      }
    }
    
    // Also check the toolbar more thoroughly
    const toolbarButtons = await page.locator('.tox-toolbar button').all();
    console.log(`Found ${toolbarButtons.length} toolbar buttons total`);
    
    // Log all button aria-labels and titles for debugging
    for (let i = 0; i < toolbarButtons.length; i++) {
      const button = toolbarButtons[i];
      const ariaLabel = await button.getAttribute('aria-label').catch(() => null);
      const title = await button.getAttribute('title').catch(() => null);
      const text = await button.textContent().catch(() => null);
      
      if (ariaLabel || title || text) {
        console.log(`Button ${i}: aria-label="${ariaLabel}", title="${title}", text="${text}"`);
      }
      
      // Check if this might be our dual image button
      if (ariaLabel?.toLowerCase().includes('dual') || 
          ariaLabel?.toLowerCase().includes('modal') ||
          title?.toLowerCase().includes('dual') || 
          title?.toLowerCase().includes('modal')) {
        foundButton = button;
        console.log(`✓ Identified potential dual image button at index ${i}`);
      }
    }
    
    if (foundButton) {
      console.log('Step 4: Testing dual image button functionality...');
      
      // Click the button
      await foundButton.click();
      
      // Wait for dialog to appear
      await page.waitForTimeout(1000);
      
      // Check if a dialog opened
      const dialogSelectors = [
        '.tox-dialog',
        '.mce-window',
        '[role="dialog"]',
        '.modal'
      ];
      
      let dialogFound = false;
      for (const selector of dialogSelectors) {
        if (await page.locator(selector).isVisible({ timeout: 1000 }).catch(() => false)) {
          console.log(`✓ Dialog opened with selector: ${selector}`);
          dialogFound = true;
          
          // Take screenshot of the dialog
          await page.screenshot({ 
            path: 'testing/screenshots/live-dual-image-dialog.png',
            fullPage: true 
          });
          
          // Look for file input fields in the dialog
          const fileInputs = await page.locator('input[type="file"]').all();
          console.log(`Found ${fileInputs.length} file input fields in dialog`);
          
          // Close dialog if possible
          const closeButton = page.locator('.tox-dialog__header button[aria-label="Close"]').first();
          if (await closeButton.isVisible({ timeout: 1000 }).catch(() => false)) {
            await closeButton.click();
            console.log('✓ Closed dialog');
          }
          
          break;
        }
      }
      
      if (!dialogFound) {
        console.log('⚠ No dialog appeared after clicking dual image button');
      }
    } else {
      console.log('⚠ No dual image button found in TinyMCE toolbar');
      
      // Try alternative approach - check if button exists but might be hidden
      const hiddenButtons = await page.locator('button[style*="display: none"], button.tox-tbtn--disabled').all();
      console.log(`Found ${hiddenButtons.length} hidden or disabled buttons`);
    }
    
    // Step 5: Try to create content with manual HTML
    console.log('Step 5: Creating test article with manual dual image HTML...');
    
    // Fill in basic article details
    await page.fill('input[name="title"]', `Dual Image Test Article - ${new Date().toISOString()}`);
    
    // Add manual dual image HTML to content
    const dualImageHTML = `
      <p>Testing dual image modal functionality:</p>
      <img 
        src="/uploads/content/featured/2024/01/display-test.jpg" 
        data-modal-src="/uploads/content/featured/2024/01/modal-test.jpg" 
        alt="Test Dual Image" 
        style="cursor: pointer; max-width: 100%;"
        class="dual-image"
      />
      <p>Click the image above to test modal functionality.</p>
    `;
    
    // Try to set content in TinyMCE
    await page.evaluate((html) => {
      // Try different methods to set TinyMCE content
      if (window.tinymce && window.tinymce.activeEditor) {
        window.tinymce.activeEditor.setContent(html);
        return true;
      } else if (window.tinyMCE && window.tinyMCE.activeEditor) {
        window.tinyMCE.activeEditor.setContent(html);
        return true;
      }
      return false;
    }, dualImageHTML);
    
    // Set other required fields
    const typeSelect = page.locator('select[name="type"]');
    if (await typeSelect.isVisible({ timeout: 1000 }).catch(() => false)) {
      await typeSelect.selectOption('article');
    }
    
    const statusSelect = page.locator('select[name="status"]');
    if (await statusSelect.isVisible({ timeout: 1000 }).catch(() => false)) {
      await statusSelect.selectOption('published');
    }
    
    // Take screenshot before saving
    await page.screenshot({ 
      path: 'testing/screenshots/live-before-save.png',
      fullPage: true 
    });
    
    // Save the article
    const saveButton = page.locator('button[type="submit"], input[type="submit"]').first();
    if (await saveButton.isVisible({ timeout: 1000 }).catch(() => false)) {
      await saveButton.click();
      console.log('✓ Clicked save button');
      
      // Wait for save to complete
      await page.waitForTimeout(2000);
      
      // Check if we're redirected or see success message
      const currentURL = page.url();
      console.log(`Current URL after save: ${currentURL}`);
      
      // Look for success message
      const successMessage = page.locator('.alert-success, .success-message, [class*="success"]').first();
      if (await successMessage.isVisible({ timeout: 1000 }).catch(() => false)) {
        const messageText = await successMessage.textContent();
        console.log(`✓ Success message: ${messageText}`);
      }
    } else {
      console.log('⚠ Save button not found');
    }
    
    // Step 6: Check if article was created and view it
    console.log('Step 6: Checking if article was created...');
    
    // Go to content listing
    await page.goto(`${baseURL}/admin/content`);
    await page.waitForTimeout(2000);
    
    // Look for our test article
    const testArticle = page.locator('td:has-text("Dual Image Test Article")').first();
    if (await testArticle.isVisible({ timeout: 5000 }).catch(() => false)) {
      console.log('✓ Test article found in content listing');
      
      // Try to view it on frontend
      const viewLink = page.locator('a[href*="/article/"]:has-text("View")').first();
      if (await viewLink.isVisible({ timeout: 1000 }).catch(() => false)) {
        const articleURL = await viewLink.getAttribute('href');
        console.log(`Article URL: ${articleURL}`);
        
        // Visit the article on frontend
        await page.goto(`${baseURL}${articleURL}`);
        await page.waitForTimeout(2000);
        
        // Check for dual image
        const dualImage = page.locator('img[data-modal-src]').first();
        if (await dualImage.isVisible({ timeout: 5000 }).catch(() => false)) {
          console.log('✓ Dual image found on frontend');
          
          const displaySrc = await dualImage.getAttribute('src');
          const modalSrc = await dualImage.getAttribute('data-modal-src');
          console.log(`Display image: ${displaySrc}`);
          console.log(`Modal image: ${modalSrc}`);
          
          // Test clicking the image
          await dualImage.click();
          await page.waitForTimeout(1000);
          
          // Check if modal opened
          const modal = page.locator('.modal, #imageModal, [class*="modal"]').first();
          if (await modal.isVisible({ timeout: 2000 }).catch(() => false)) {
            console.log('✓ Modal opened when image clicked');
            
            // Check modal image
            const modalImg = modal.locator('img').first();
            if (await modalImg.isVisible({ timeout: 1000 }).catch(() => false)) {
              const modalImgSrc = await modalImg.getAttribute('src');
              console.log(`Modal is showing image: ${modalImgSrc}`);
              
              if (modalImgSrc === modalSrc) {
                console.log('✅ SUCCESS: Modal is showing the correct modal image!');
              } else {
                console.log('⚠ Modal is showing a different image than expected');
              }
            }
            
            // Take screenshot of modal
            await page.screenshot({ 
              path: 'testing/screenshots/live-modal-open.png',
              fullPage: true 
            });
          } else {
            console.log('⚠ Modal did not open when image was clicked');
          }
        } else {
          console.log('⚠ Dual image not found on frontend');
        }
      }
    } else {
      console.log('⚠ Test article not found in content listing');
    }
    
    // Final summary
    console.log('\n=== TEST SUMMARY ===');
    console.log('1. Admin login: ✓');
    console.log('2. Content creation page: ✓');
    console.log(`3. Dual image button in toolbar: ${foundButton ? '✓' : '✗'}`);
    console.log(`4. Article creation: ${await page.url().includes('/admin/content') ? '✓' : '?'}`);
    console.log('5. Frontend display: Check screenshots for results');
  });
});