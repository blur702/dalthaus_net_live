import { test, expect } from '@playwright/test';
import { promises as fs } from 'fs';
import path from 'path';

test.describe('Custom Dual Image Button Debug', () => {

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
      0x00, 0x00, 0x00, 0x0D, // IDAT chunk length
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

  test('Debug custom dual image button upload process', async ({ page }) => {
    console.log('🐛 Debugging custom dual image button upload process');

    // Capture console logs and errors
    const consoleLogs = [];
    const errors = [];
    const networkRequests = [];

    page.on('console', msg => {
      consoleLogs.push(`${msg.type()}: ${msg.text()}`);
    });

    page.on('pageerror', error => {
      errors.push(error.message);
    });

    page.on('request', request => {
      if (request.url().includes('upload') || request.method() === 'POST') {
        networkRequests.push({
          url: request.url(),
          method: request.method(),
          headers: request.headers()
        });
      }
    });

    page.on('response', response => {
      if (response.url().includes('upload') || response.status() >= 400) {
        console.log(`Response: ${response.status()} ${response.url()}`);
      }
    });

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

    // Fill in basic title
    const timestamp = Date.now();
    await page.locator('input[name="title"]').clear();
    await page.fill('input[name="title"]', `Debug Test ${timestamp}`);

    // Wait for TinyMCE to load
    await page.waitForSelector('iframe[title="Rich Text Area"]', { timeout: 10000 });
    await page.waitForTimeout(2000);

    console.log('📋 Looking for custom dual image button...');
    
    // Find the custom dual image button
    const customButton = page.locator('button[title="Insert Dual Image (Display + Modal)"]');
    expect(await customButton.count()).toBeGreaterThan(0);
    console.log('✅ Found custom dual image button');

    // Click the custom button
    console.log('🖱️ Clicking custom dual image button...');
    await customButton.click();
    await page.waitForTimeout(1000);

    // Look for and analyze the custom dialog
    console.log('🔍 Analyzing custom dialog...');
    const dialog = page.locator('.modal, [id*="modal"], [class*="modal"]').first();
    
    if (await dialog.count() > 0) {
      console.log('✅ Custom dialog opened');
      
      // Get dialog HTML for analysis
      const dialogHTML = await dialog.innerHTML();
      console.log('Dialog HTML structure:', dialogHTML.substring(0, 500) + '...');
      
      // Look for file input
      const fileInput = dialog.locator('input[type="file"]').first();
      if (await fileInput.count() > 0) {
        console.log('✅ Found file input in dialog');
        
        // Upload file
        console.log('📤 Uploading test image...');
        const testImagePath = path.join(process.cwd(), 'test-assets', 'test-image.png');
        await fileInput.setInputFiles(testImagePath);
        
        // Wait for any upload processing
        await page.waitForTimeout(2000);
        
        // Look for insert/submit button
        const insertBtn = dialog.locator('button:has-text("Insert"), button:has-text("Add"), button:has-text("Upload"), button[type="submit"]').first();
        
        if (await insertBtn.count() > 0) {
          console.log('✅ Found insert button');
          
          // Clear previous network requests
          networkRequests.length = 0;
          
          // Click insert button
          console.log('🎯 Clicking insert button...');
          await insertBtn.click();
          
          // Wait for processing
          await page.waitForTimeout(3000);
          
          // Check for any network requests
          console.log(`📡 Network requests during upload: ${networkRequests.length}`);
          networkRequests.forEach((req, i) => {
            console.log(`  ${i + 1}. ${req.method} ${req.url}`);
          });
          
          // Check for JavaScript errors
          console.log(`❌ JavaScript errors: ${errors.length}`);
          errors.forEach((error, i) => {
            console.log(`  ${i + 1}. ${error}`);
          });
          
          // Check console logs for clues
          console.log(`📝 Console logs (last 10):`);
          consoleLogs.slice(-10).forEach((log, i) => {
            console.log(`  ${i + 1}. ${log}`);
          });
          
          // Check TinyMCE content
          const tinyMCEFrame = page.frameLocator('iframe[title="Rich Text Area"]');
          const editorContent = await tinyMCEFrame.locator('body').innerHTML();
          console.log('📝 TinyMCE content after upload:', editorContent);
          
          // Check if dialog closed
          const dialogStillOpen = await dialog.isVisible();
          console.log(`🚪 Dialog still open: ${dialogStillOpen}`);
          
          // Look for any success/error messages
          const messages = await page.locator('.alert, .notification, .message, .error, .success').allTextContents();
          if (messages.length > 0) {
            console.log('💬 Page messages:', messages);
          }
          
          // Check if image was inserted
          if (editorContent.includes('<img')) {
            console.log('🎉 SUCCESS: Image HTML found in editor!');
            
            // Extract image details
            const imgMatch = editorContent.match(/<img[^>]*src="([^"]*)"[^>]*>/);
            if (imgMatch) {
              console.log(`📷 Image src: ${imgMatch[1]}`);
              
              // Check for modal attributes
              if (editorContent.includes('data-modal-src') || editorContent.includes('onclick')) {
                console.log('🎭 Modal functionality detected in image HTML');
              } else {
                console.log('⚠️ No modal functionality in image HTML');
              }
            }
          } else {
            console.log('❌ FAILED: No image HTML in editor');
            console.log('🔧 Debugging suggestions:');
            console.log('  1. Check if upload endpoint is working');
            console.log('  2. Verify JavaScript insertion logic');
            console.log('  3. Check for CSRF token issues');
            console.log('  4. Verify file permissions for uploads');
          }
          
        } else {
          console.log('❌ No insert button found in dialog');
        }
      } else {
        console.log('❌ No file input found in dialog');
      }
    } else {
      console.log('❌ Custom dialog did not open');
    }

    // Summary
    console.log('\n📊 DEBUG SUMMARY:');
    console.log(`✅ Custom button found: YES`);
    console.log(`✅ Dialog opened: ${await dialog.count() > 0 ? 'YES' : 'NO'}`);
    console.log(`✅ File uploaded: ${networkRequests.length > 0 ? 'YES' : 'NO'}`);
    console.log(`❌ JavaScript errors: ${errors.length}`);
    console.log(`📝 Network requests: ${networkRequests.length}`);
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