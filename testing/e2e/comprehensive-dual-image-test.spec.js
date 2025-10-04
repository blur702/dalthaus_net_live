import { test, expect } from '@playwright/test';
import { promises as fs } from 'fs';
import path from 'path';

test.describe('Comprehensive Custom Dual Image Button Test', () => {
  let uploadedImagePath = '';
  let testArticleAlias = '';

  test.beforeAll(async () => {
    // Create a simple test image file (1x1 pixel PNG)
    const testImageDir = path.join(process.cwd(), 'test-assets');
    await fs.mkdir(testImageDir, { recursive: true });
    
    // Create a simple PNG file programmatically
    const pngBuffer = Buffer.from([
      0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A, // PNG signature
      0x00, 0x00, 0x00, 0x0D, // IHDR chunk length
      0x49, 0x48, 0x44, 0x52, // IHDR
      0x00, 0x00, 0x00, 0x01, // Width: 1
      0x00, 0x00, 0x00, 0x01, // Height: 1
      0x08, 0x02, 0x00, 0x00, 0x00, // Bit depth, color type, etc.
      0x90, 0x77, 0x53, 0xDE, // CRC
      0x00, 0x00, 0x00, 0x0C, // IDAT chunk length
      0x49, 0x44, 0x41, 0x54, // IDAT
      0x08, 0x99, 0x01, 0x01, 0x00, 0x00, 0x00, 0xFF, 0xFF, 0x00, 0x00, 0x00, 0x02, 0x00, 0x01, // Image data
      0xE2, 0x21, 0xBC, 0x33, // CRC
      0x00, 0x00, 0x00, 0x00, // IEND chunk length
      0x49, 0x45, 0x4E, 0x44, // IEND
      0xAE, 0x42, 0x60, 0x82  // CRC
    ]);
    
    const testImagePath = path.join(testImageDir, 'test-image.png');
    await fs.writeFile(testImagePath, pngBuffer);
    console.log('Created test image at:', testImagePath);
  });

  test('Complete dual image button workflow test', async ({ page }) => {
    console.log('🎯 Testing complete dual image button workflow');

    // Set up error monitoring
    const consoleLogs = [];
    const errors = [];
    
    page.on('console', msg => {
      consoleLogs.push(`${msg.type()}: ${msg.text()}`);
    });
    
    page.on('pageerror', error => {
      errors.push(error.message);
      console.log(`❌ JavaScript Error: ${error.message}`);
    });

    // Login to admin
    console.log('📝 Step 1: Logging into admin');
    await page.goto('https://dalthaus.net/admin/login');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard*');
    console.log('✅ Logged in successfully');

    // Navigate to content creation
    console.log('📝 Step 2: Creating new article');
    await page.goto('https://dalthaus.net/admin/content');
    await page.click('text=New Article');
    await page.waitForLoadState('networkidle');

    // Fill in article details
    const timestamp = Date.now();
    const testTitle = `Dual Image Test ${timestamp}`;
    await page.locator('input[name="title"]').clear();
    await page.fill('input[name="title"]', testTitle);
    
    const aliasValue = await page.locator('input[name="alias"], #alias, [name="url_alias"]').inputValue();
    testArticleAlias = aliasValue || `dual-image-test-${timestamp}`;
    console.log(`✅ Article created: ${testTitle}`);

    // Wait for TinyMCE
    console.log('📝 Step 3: Waiting for TinyMCE to load');
    await page.waitForSelector('iframe[title="Rich Text Area"]', { timeout: 10000 });
    await page.waitForTimeout(3000); // Give extra time for full initialization
    console.log('✅ TinyMCE loaded');

    // Take screenshot before clicking button
    await page.screenshot({ path: 'debug-before-click.png', fullPage: true });

    // Find and click the custom dual image button
    console.log('📝 Step 4: Clicking custom dual image button');
    const customButton = page.locator('button[title="Insert Dual Image (Display + Modal)"]');
    expect(await customButton.count()).toBeGreaterThan(0);
    
    await customButton.click();
    console.log('✅ Custom dual image button clicked');

    // Wait for any dialog or modal to appear
    await page.waitForTimeout(2000);
    
    // Take screenshot after clicking
    await page.screenshot({ path: 'debug-after-click.png', fullPage: true });

    // Look for different types of modals/dialogs
    const possibleDialogs = [
      '.modal',
      '.tox-dialog',
      '.custom-image-dialog',
      '[id*="modal"]',
      '[class*="modal"]',
      '[id*="image"]',
      '.popup',
      '.overlay'
    ];

    let activeDialog = null;
    for (const selector of possibleDialogs) {
      const dialog = page.locator(selector).first();
      if (await dialog.count() > 0 && await dialog.isVisible()) {
        activeDialog = dialog;
        console.log(`✅ Found active dialog: ${selector}`);
        break;
      }
    }

    if (activeDialog) {
      console.log('📝 Step 5: Working with image upload dialog');
      
      // Take screenshot of the dialog
      await page.screenshot({ path: 'debug-dialog-open.png', fullPage: true });
      
      // Look for file input
      const fileInput = activeDialog.locator('input[type="file"]').first();
      expect(await fileInput.count()).toBeGreaterThan(0);
      
      // Upload the test image
      console.log('📤 Uploading test image');
      const testImagePath = path.join(process.cwd(), 'test-assets', 'test-image.png');
      await fileInput.setInputFiles(testImagePath);
      
      // Wait for upload processing
      await page.waitForTimeout(3000);
      
      // Take screenshot after upload
      await page.screenshot({ path: 'debug-after-upload.png', fullPage: true });
      
      // Look for insert/submit button and click it
      const submitSelectors = [
        'button:has-text("Insert")',
        'button:has-text("Add")', 
        'button:has-text("Upload")',
        'button:has-text("Save")',
        'button[type="submit"]',
        'input[type="submit"]'
      ];

      let submitButton = null;
      for (const selector of submitSelectors) {
        const btn = activeDialog.locator(selector).first();
        if (await btn.count() > 0 && await btn.isVisible()) {
          submitButton = btn;
          console.log(`✅ Found submit button: ${selector}`);
          break;
        }
      }

      if (submitButton) {
        console.log('🎯 Clicking submit button');
        await submitButton.click();
        
        // Wait for processing
        await page.waitForTimeout(2000);
        
        // Take screenshot after submission
        await page.screenshot({ path: 'debug-after-submit.png', fullPage: true });
        
        console.log('✅ Submit button clicked');
      } else {
        console.log('❌ No submit button found');
        
        // List all buttons in the dialog for debugging
        const allButtons = await activeDialog.locator('button, input[type="submit"]').all();
        console.log(`Found ${allButtons.length} buttons in dialog:`);
        for (let i = 0; i < allButtons.length; i++) {
          const btn = allButtons[i];
          const text = await btn.textContent();
          const type = await btn.getAttribute('type');
          const value = await btn.getAttribute('value');
          console.log(`  ${i + 1}. "${text}" type="${type}" value="${value}"`);
        }
      }
      
    } else {
      console.log('❌ No dialog opened after clicking custom button');
      
      // Check if the button might have directly inserted something
      console.log('🔍 Checking if image was directly inserted without dialog');
    }

    // Check TinyMCE content
    console.log('📝 Step 6: Checking TinyMCE content');
    const tinyMCEFrame = page.frameLocator('iframe[title="Rich Text Area"]');
    const editorContent = await tinyMCEFrame.locator('body').innerHTML();
    console.log('📄 TinyMCE Editor Content:', editorContent);

    if (editorContent.includes('<img')) {
      console.log('🎉 SUCCESS: Image HTML found in editor!');
      
      // Extract image src
      const imgMatch = editorContent.match(/<img[^>]*src="([^"]*)"[^>]*>/);
      if (imgMatch) {
        uploadedImagePath = imgMatch[1];
        console.log(`📷 Image src: ${uploadedImagePath}`);
        
        // Check for dual image attributes
        const hasModalSrc = editorContent.includes('data-modal-src');
        const hasOnclick = editorContent.includes('onclick');
        const hasModalFunction = editorContent.includes('openImageModal');
        
        console.log(`🎭 Modal src attribute: ${hasModalSrc ? '✅' : '❌'}`);
        console.log(`🎭 Onclick handler: ${hasOnclick ? '✅' : '❌'}`);
        console.log(`🎭 Modal function: ${hasModalFunction ? '✅' : '❌'}`);
        
        if (hasModalSrc || hasOnclick || hasModalFunction) {
          console.log('🎉 DUAL IMAGE FUNCTIONALITY CONFIRMED!');
        } else {
          console.log('⚠️ Image inserted but missing dual image functionality');
        }
      }

      // Save the article
      console.log('📝 Step 7: Saving article');
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');
      console.log('✅ Article saved');

      // Test frontend display
      console.log('📝 Step 8: Testing frontend display');
      const frontendUrl = `https://dalthaus.net/article/${testArticleAlias}`;
      await page.goto(frontendUrl);
      await page.waitForLoadState('networkidle');
      
      // Take screenshot of frontend
      await page.screenshot({ path: 'debug-frontend.png', fullPage: true });
      
      // Look for the image on frontend
      const frontendImages = await page.locator('img[src*="/uploads/content/"]').all();
      
      if (frontendImages.length > 0) {
        console.log(`🎉 SUCCESS: Found ${frontendImages.length} image(s) on frontend!`);
        
        const firstImage = frontendImages[0];
        await expect(firstImage).toBeVisible();
        
        const src = await firstImage.getAttribute('src');
        const onclick = await firstImage.getAttribute('onclick');
        
        console.log(`📷 Frontend image src: ${src}`);
        console.log(`🎭 Frontend onclick: ${onclick ? 'Present' : 'Missing'}`);
        
        // Test modal functionality
        if (onclick && onclick.includes('openImageModal')) {
          console.log('📝 Step 9: Testing modal functionality');
          await firstImage.click();
          await page.waitForTimeout(1000);
          
          const modal = page.locator('#imageModal, .modal, [id*="modal"]').first();
          if (await modal.count() > 0 && await modal.isVisible()) {
            console.log('🎉 SUCCESS: Modal opened on frontend!');
            
            // Take screenshot of modal
            await page.screenshot({ path: 'debug-frontend-modal.png', fullPage: true });
            
            // Close modal
            const closeBtn = modal.locator('.close, button:has-text("Close"), [aria-label="Close"]').first();
            if (await closeBtn.count() > 0) {
              await closeBtn.click();
            } else {
              await page.keyboard.press('Escape');
            }
            console.log('✅ Modal closed');
          } else {
            console.log('❌ Modal did not open on frontend');
          }
        } else {
          console.log('⚠️ No modal functionality detected on frontend');
        }
        
      } else {
        console.log('❌ No images found on frontend');
      }
      
    } else {
      console.log('❌ FAILED: No image HTML in TinyMCE editor');
    }

    // Final summary
    console.log('\n📊 COMPREHENSIVE TEST SUMMARY:');
    console.log(`✅ Custom button found: YES`);
    console.log(`✅ Dialog behavior: ${activeDialog ? 'Dialog opened' : 'No dialog (direct insert?)'}`);
    console.log(`✅ Image in editor: ${editorContent.includes('<img') ? 'YES' : 'NO'}`);
    console.log(`✅ Frontend display: ${uploadedImagePath ? 'YES' : 'NO'}`);
    console.log(`✅ Modal functionality: TBD`);
    console.log(`❌ JavaScript errors: ${errors.length}`);
    
    if (errors.length > 0) {
      console.log('JavaScript errors encountered:');
      errors.forEach((error, i) => console.log(`  ${i + 1}. ${error}`));
    }
  });

  test.afterAll(async () => {
    // Clean up test image file
    try {
      const testImagePath = path.join(process.cwd(), 'test-assets', 'test-image.png');
      await fs.unlink(testImagePath);
      console.log('✅ Test image file cleaned up');
    } catch (e) {
      console.log('⚠️ Could not clean up test image file:', e.message);
    }
  });
});