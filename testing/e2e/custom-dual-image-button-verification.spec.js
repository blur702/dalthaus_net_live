import { test, expect } from '@playwright/test';
import { promises as fs } from 'fs';
import path from 'path';

test.describe('Custom Dual Image Button Detection and Testing', () => {

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

  test('Find and test custom dual image button', async ({ page }) => {
    console.log('🔍 Searching for custom dual image button (🖼️📱)');

    // Login to admin
    await page.goto('https://dalthaus.net/admin/login');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard*');

    // Navigate to content creation
    await page.goto('https://dalthaus.net/admin/content');
    await page.click('text=New Article');
    await page.waitForLoadState('networkidle');

    // Wait for TinyMCE to load
    await page.waitForSelector('iframe[title="Rich Text Area"]', { timeout: 10000 });
    await page.waitForTimeout(2000);

    console.log('📋 Analyzing all TinyMCE toolbar buttons in detail...');
    
    // Get all possible button elements
    const allElements = await page.locator('button, .tox-tbtn, .tox-button, [role="button"]').all();
    console.log(`Found ${allElements.length} clickable elements in the page`);
    
    let customDualImageButton = null;
    let standardImageButton = null;
    
    for (let i = 0; i < allElements.length; i++) {
      const element = allElements[i];
      const title = await element.getAttribute('title') || '';
      const ariaLabel = await element.getAttribute('aria-label') || '';
      const textContent = await element.textContent() || '';
      const innerHTML = await element.innerHTML() || '';
      const className = await element.getAttribute('class') || '';
      const id = await element.getAttribute('id') || '';
      
      // Check if this is within the TinyMCE toolbar area
      const isInToolbar = className.includes('tox') || 
                          await element.locator('xpath=ancestor::*[contains(@class, "tox-toolbar")]').count() > 0;
      
      if (isInToolbar) {
        console.log(`Toolbar Button ${i}:`);
        console.log(`  Title: "${title}"`);
        console.log(`  Aria-label: "${ariaLabel}"`);
        console.log(`  Text: "${textContent.replace(/\s+/g, ' ').trim()}"`);
        console.log(`  Class: "${className}"`);
        console.log(`  ID: "${id}"`);
        console.log(`  Has emoji: ${innerHTML.includes('🖼️') || innerHTML.includes('📱')}`);
        console.log(`  ---`);
        
        // Look for custom dual image button indicators
        if (title.toLowerCase().includes('dual') || 
            ariaLabel.toLowerCase().includes('dual') ||
            textContent.includes('🖼️') || 
            textContent.includes('📱') ||
            innerHTML.includes('🖼️') ||
            innerHTML.includes('📱') ||
            title.toLowerCase().includes('dual image') ||
            ariaLabel.toLowerCase().includes('dual image')) {
          customDualImageButton = element;
          console.log(`🎯 FOUND CUSTOM DUAL IMAGE BUTTON at index ${i}!`);
        }
        
        // Also note standard image button for comparison
        if ((title.toLowerCase().includes('image') || ariaLabel.toLowerCase().includes('image')) &&
            !title.toLowerCase().includes('dual') && !ariaLabel.toLowerCase().includes('dual')) {
          standardImageButton = element;
          console.log(`📷 Found standard image button at index ${i}: ${title || ariaLabel}`);
        }
      }
    }

    if (customDualImageButton) {
      console.log('✅ CUSTOM DUAL IMAGE BUTTON FOUND - Testing functionality...');
      
      // Test the custom dual image button
      await customDualImageButton.click();
      await page.waitForTimeout(1000);
      
      // Look for custom upload dialog
      const customDialog = await page.locator('.modal, .custom-image-dialog, [id*="image"], [class*="image-upload"]').first();
      if (await customDialog.count() > 0) {
        console.log('✅ Custom image upload dialog opened');
        
        // Look for file input in custom dialog
        const fileInput = page.locator('input[type="file"]').first();
        if (await fileInput.count() > 0) {
          console.log('✅ Found file input in custom dialog');
          
          // Upload test image
          const testImagePath = path.join(process.cwd(), 'test-assets', 'test-image.png');
          await fileInput.setInputFiles(testImagePath);
          await page.waitForTimeout(2000);
          
          // Look for submit/upload/insert button
          const submitSelectors = [
            'button:has-text("Upload")',
            'button:has-text("Insert")', 
            'button:has-text("Add")',
            'button:has-text("Save")',
            'input[type="submit"]',
            'button[type="submit"]'
          ];
          
          let submitButton = null;
          for (const selector of submitSelectors) {
            const btn = page.locator(selector).first();
            if (await btn.count() > 0) {
              submitButton = btn;
              console.log(`✅ Found submit button: ${selector}`);
              break;
            }
          }
          
          if (submitButton) {
            await submitButton.click();
            await page.waitForTimeout(2000);
            
            // Check if image was inserted into TinyMCE
            const tinyMCEFrame = page.frameLocator('iframe[title="Rich Text Area"]');
            const editorContent = await tinyMCEFrame.locator('body').innerHTML();
            console.log('TinyMCE content after custom upload:', editorContent);
            
            if (editorContent.includes('<img')) {
              console.log('🎉 SUCCESS: Custom dual image button works! Image inserted into editor.');
              
              // Check for dual image functionality (modal attributes)
              if (editorContent.includes('data-modal-src') || editorContent.includes('onclick')) {
                console.log('🎉 DUAL IMAGE FUNCTIONALITY DETECTED: Image has modal attributes!');
              }
            } else {
              console.log('❌ FAILED: Custom button did not insert image into editor');
            }
          } else {
            console.log('❌ Could not find submit button in custom dialog');
          }
        } else {
          console.log('❌ No file input found in custom dialog');
        }
      } else {
        console.log('❌ Custom dialog did not appear - might be using standard TinyMCE dialog');
      }
      
    } else if (standardImageButton) {
      console.log('⚠️ NO CUSTOM DUAL IMAGE BUTTON FOUND');
      console.log('📷 Only standard TinyMCE image button available');
      console.log('This suggests the custom dual image button may not be loaded or configured properly');
      
    } else {
      console.log('❌ NO IMAGE BUTTONS FOUND AT ALL');
      console.log('This suggests TinyMCE may not be properly initialized');
    }

    // Final analysis
    console.log('\n📊 ANALYSIS SUMMARY:');
    console.log(`Custom Dual Image Button: ${customDualImageButton ? '✅ FOUND' : '❌ NOT FOUND'}`);
    console.log(`Standard Image Button: ${standardImageButton ? '✅ FOUND' : '❌ NOT FOUND'}`);
    
    if (!customDualImageButton) {
      console.log('\n🔧 TROUBLESHOOTING SUGGESTIONS:');
      console.log('1. Check if custom TinyMCE plugins are properly loaded');
      console.log('2. Verify tinymce-single.js or custom button scripts are included');
      console.log('3. Check browser console for JavaScript errors');
      console.log('4. Ensure custom button is registered in TinyMCE configuration');
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