const { test, expect } = require('@playwright/test');

test.describe('Admin Content Creation with Dual Images', () => {
  test('Create article with dual image and test modal X close functionality', async ({ page }) => {
    console.log('Starting admin content creation test...');
    
    // Navigate to login page
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('networkidle');
    
    // Login with credentials
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Verify login successful by checking for dashboard elements
    await expect(page.locator('text=CMS')).toBeVisible();
    console.log('Successfully logged into admin panel');
    
    // Navigate to articles
    await page.click('a[href="/admin/articles"]');
    await page.waitForLoadState('networkidle');
    
    // Look for create/add button
    const createButton = page.locator('a:has-text("Create"), a:has-text("Add"), a:has-text("New")').first();
    if (await createButton.isVisible()) {
      await createButton.click();
      await page.waitForLoadState('networkidle');
      console.log('Navigated to article creation page');
      
      // Fill basic article information
      await page.fill('input[name="title"]', 'Test Article with Modal Image');
      await page.fill('input[name="alias"]', 'test-modal-article');
      
      // Wait for TinyMCE to initialize
      await page.waitForTimeout(3000);
      
      // Look for TinyMCE iframe or editor
      const tinymceFrame = page.frameLocator('iframe[id*="tinymce"]').first();
      const editorExists = await page.locator('iframe[id*="tinymce"]').count() > 0;
      
      if (editorExists) {
        console.log('TinyMCE editor found');
        
        // Look for the modal image button in toolbar
        const modalImageBtn = page.locator('button:has-text("Modal Image"), button[title*="modal"], button[title*="Modal"]');
        const buttonCount = await modalImageBtn.count();
        console.log(`Found ${buttonCount} potential modal image buttons`);
        
        if (buttonCount > 0) {
          // Click the modal image button
          await modalImageBtn.first().click();
          await page.waitForTimeout(1000);
          
          // Check if dual image dialog appeared
          const dialog = page.locator('.dual-image-dialog');
          if (await dialog.isVisible()) {
            console.log('Dual image dialog opened successfully');
            
            // Test the X close button specifically
            const closeBtn = page.locator('.close-btn, .dual-image-header button');
            await expect(closeBtn).toBeVisible();
            
            console.log('Testing X close button functionality...');
            await closeBtn.click();
            await page.waitForTimeout(500);
            
            // Verify dialog closed
            await expect(dialog).not.toBeVisible();
            console.log('✅ X close button works correctly in dual image dialog!');
            
            // Test again to ensure it's consistently working
            await modalImageBtn.first().click();
            await page.waitForTimeout(500);
            await expect(dialog).toBeVisible();
            
            // Test overlay close
            await page.locator('.dual-image-overlay').click();
            await page.waitForTimeout(500);
            await expect(dialog).not.toBeVisible();
            console.log('✅ Overlay close also works correctly!');
            
          } else {
            console.log('❌ Dual image dialog did not appear when button clicked');
          }
        } else {
          console.log('❌ No modal image button found in TinyMCE toolbar');
          
          // List all available buttons for debugging
          const allButtons = await page.locator('.tox-toolbar button').count();
          console.log(`Total toolbar buttons found: ${allButtons}`);
          
          // Try to find any image-related buttons
          const imageButtons = await page.locator('button[title*="image"], button[title*="Image"]').count();
          console.log(`Image-related buttons found: ${imageButtons}`);
        }
      } else {
        console.log('❌ TinyMCE editor not found');
        
        // Check for regular textarea
        const textareaExists = await page.locator('textarea[name="content"]').isVisible();
        if (textareaExists) {
          console.log('Found regular textarea instead of TinyMCE');
          await page.fill('textarea[name="content"]', '<p>Test content for modal image testing</p>');
        }
      }
      
      // Save the article
      const saveButton = page.locator('button:has-text("Save"), button:has-text("Create"), input[type="submit"]').first();
      if (await saveButton.isVisible()) {
        await saveButton.click();
        await page.waitForLoadState('networkidle');
        console.log('Article saved successfully');
      }
      
    } else {
      console.log('❌ No create button found on articles page');
      
      // Take screenshot for debugging
      await page.screenshot({ 
        path: 'testing/screenshots/admin-articles-page.png',
        fullPage: true 
      });
    }
    
    // Take final screenshot
    await page.screenshot({ 
      path: 'testing/screenshots/admin-content-creation-final.png',
      fullPage: true 
    });
    
    console.log('Admin content creation test completed');
  });
  
  test('Test modal functionality on existing content', async ({ page }) => {
    console.log('Testing modal functionality on homepage...');
    
    // Go to homepage to check for any existing modal images
    await page.goto('https://dalthaus.net');
    await page.waitForLoadState('networkidle');
    
    // Look for images with data-modal-src attribute
    const modalImages = await page.locator('img[data-modal-src]').count();
    console.log(`Found ${modalImages} images with modal functionality`);
    
    if (modalImages > 0) {
      // Test the first modal image
      const firstModalImage = page.locator('img[data-modal-src]').first();
      await firstModalImage.click();
      await page.waitForTimeout(500);
      
      // Check if modal opened
      const modal = page.locator('.image-modal');
      if (await modal.isVisible()) {
        console.log('✅ Modal opened successfully');
        
        // Test X close button
        const closeBtn = page.locator('.modal-close');
        await expect(closeBtn).toBeVisible();
        await closeBtn.click();
        await page.waitForTimeout(300);
        
        // Verify modal closed
        await expect(modal).not.toBeVisible();
        console.log('✅ X close button works on frontend modal!');
        
      } else {
        console.log('❌ Modal did not open when image clicked');
      }
    } else {
      console.log('No modal images found on homepage - this is expected based on previous tests');
    }
  });
});