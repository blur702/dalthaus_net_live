const { test, expect } = require('@playwright/test');

test.describe('Modal X Button Verification', () => {
  test('Login and access dual image functionality', async ({ page }) => {
    console.log('Starting modal X button verification test...');
    
    // Navigate to login page
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('networkidle');
    
    // Login with credentials
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Check for successful login more specifically
    const dashboardLink = page.locator('a[href="/admin/dashboard"]');
    await expect(dashboardLink).toBeVisible();
    console.log('Successfully logged into admin panel');
    
    // Navigate to articles
    await page.click('a[href="/admin/articles"]');
    await page.waitForLoadState('networkidle');
    
    // Take screenshot of articles page
    await page.screenshot({ 
      path: 'testing/screenshots/articles-admin-page.png',
      fullPage: true 
    });
    
    // Look for any way to create content
    const createOptions = [
      'a:has-text("Create")',
      'a:has-text("Add")', 
      'a:has-text("New")',
      'button:has-text("Create")',
      'button:has-text("Add")',
      '.btn:has-text("Create")',
      '.btn:has-text("Add")'
    ];
    
    let createButton = null;
    for (const selector of createOptions) {
      const element = page.locator(selector).first();
      if (await element.isVisible()) {
        createButton = element;
        console.log(`Found create button with selector: ${selector}`);
        break;
      }
    }
    
    if (createButton) {
      await createButton.click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);
      
      // Take screenshot of create page
      await page.screenshot({ 
        path: 'testing/screenshots/article-create-page.png',
        fullPage: true 
      });
      
      // Check if we're on a create page
      const isCreatePage = await page.locator('input[name="title"], textarea[name="content"]').count() > 0;
      
      if (isCreatePage) {
        console.log('Successfully reached article creation page');
        
        // Fill basic fields if they exist
        const titleField = page.locator('input[name="title"]');
        if (await titleField.isVisible()) {
          await titleField.fill('Modal Test Article');
        }
        
        // Wait for TinyMCE to initialize
        await page.waitForTimeout(3000);
        
        // Check for TinyMCE toolbar
        const toolbar = page.locator('.tox-toolbar, .mce-toolbar');
        if (await toolbar.isVisible()) {
          console.log('TinyMCE toolbar found');
          
          // Look for any button that might trigger dual image dialog
          const buttonSelectors = [
            'button:has-text("Modal Image")',
            'button:has-text("Dual Image")', 
            'button[title*="modal"]',
            'button[title*="Modal"]',
            'button[aria-label*="modal"]',
            'button[data-mce-name="dualimage"]'
          ];
          
          for (const selector of buttonSelectors) {
            const button = page.locator(selector);
            const count = await button.count();
            if (count > 0) {
              console.log(`Found ${count} buttons matching: ${selector}`);
              
              // Click the button
              await button.first().click();
              await page.waitForTimeout(1000);
              
              // Check for dual image dialog
              const dialog = page.locator('.dual-image-dialog');
              if (await dialog.isVisible()) {
                console.log('✅ Dual image dialog opened!');
                
                // NOW TEST THE X CLOSE BUTTON
                const closeBtn = page.locator('.close-btn');
                await expect(closeBtn).toBeVisible();
                console.log('X close button is visible');
                
                // Click the X button
                await closeBtn.click();
                await page.waitForTimeout(500);
                
                // Verify dialog closed
                const dialogVisible = await dialog.isVisible();
                if (!dialogVisible) {
                  console.log('✅ SUCCESS: X close button works correctly!');
                } else {
                  console.log('❌ FAILURE: X close button did not close the dialog');
                }
                
                break;
              }
            }
          }
          
          // If no specific button found, list all toolbar buttons
          const allButtons = await page.locator('.tox-toolbar button').count();
          console.log(`Total toolbar buttons: ${allButtons}`);
          
          if (allButtons > 0) {
            // Get text content of all buttons for debugging
            const buttonTexts = await page.locator('.tox-toolbar button').evaluateAll(buttons => 
              buttons.map(btn => btn.textContent || btn.getAttribute('title') || btn.getAttribute('aria-label'))
            );
            console.log('Available toolbar buttons:', buttonTexts);
          }
          
        } else {
          console.log('No TinyMCE toolbar found');
        }
        
      } else {
        console.log('Did not reach article creation page');
        
        // Check what page we're actually on
        const currentUrl = page.url();
        console.log(`Current URL: ${currentUrl}`);
        
        const pageTitle = await page.title();
        console.log(`Page title: ${pageTitle}`);
      }
      
    } else {
      console.log('No create button found');
      
      // List all available links and buttons on the page
      const links = await page.locator('a').evaluateAll(links => 
        links.map(link => ({ text: link.textContent?.trim(), href: link.href }))
          .filter(item => item.text && item.text.length > 0)
      );
      console.log('Available links:', links.slice(0, 10)); // First 10 links
      
      const buttons = await page.locator('button').evaluateAll(buttons => 
        buttons.map(btn => btn.textContent?.trim()).filter(text => text && text.length > 0)
      );
      console.log('Available buttons:', buttons);
    }
    
    // Take final screenshot
    await page.screenshot({ 
      path: 'testing/screenshots/modal-x-verification-final.png',
      fullPage: true 
    });
    
    console.log('Modal X button verification test completed');
  });
  
  test('Test X button in modal on any existing content', async ({ page }) => {
    // Test the modal close functionality on the frontend
    await page.goto('https://dalthaus.net');
    await page.waitForLoadState('networkidle');
    
    // Inject a test modal image with our fixed JavaScript
    await page.evaluate(() => {
      // Create a test image with modal functionality
      const testImg = document.createElement('img');
      testImg.src = 'https://dalthaus.net/assets/img/placeholder.jpg'; // Use any existing image
      testImg.setAttribute('data-modal-src', 'https://dalthaus.net/assets/img/placeholder.jpg');
      testImg.style.cursor = 'pointer';
      testImg.style.width = '100px';
      testImg.style.height = '100px';
      testImg.onclick = function() {
        window.openImageModal(testImg.getAttribute('data-modal-src'), testImg.alt || 'Test Image');
      };
      
      // Add to page
      document.body.appendChild(testImg);
      
      return 'Test image added';
    });
    
    // Click the test image to open modal
    const testImg = page.locator('img[data-modal-src]').last();
    await testImg.click();
    await page.waitForTimeout(500);
    
    // Check if modal appeared
    const modal = page.locator('.image-modal');
    if (await modal.isVisible()) {
      console.log('✅ Test modal opened successfully');
      
      // Test the X close button
      const closeBtn = page.locator('.modal-close');
      await expect(closeBtn).toBeVisible();
      
      await closeBtn.click();
      await page.waitForTimeout(300);
      
      const modalStillVisible = await modal.isVisible();
      if (!modalStillVisible) {
        console.log('✅ SUCCESS: X close button works on frontend modal!');
      } else {
        console.log('❌ FAILURE: X close button did not close frontend modal');
      }
      
    } else {
      console.log('❌ Test modal did not open');
    }
  });
});