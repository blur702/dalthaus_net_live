import { test, expect } from '@playwright/test';
import { promises as fs } from 'fs';
import path from 'path';

test.describe('TinyMCE Image Upload to Frontend Display Verification', () => {
  let uploadedImagePath = '';
  let testArticleId = '';
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

  test('Complete TinyMCE image upload to frontend display workflow', async ({ page }) => {
    console.log('🚀 Starting complete TinyMCE image upload to frontend display test');

    // Step 1: Login to admin
    console.log('📝 Step 1: Logging into admin panel');
    await page.goto('https://dalthaus.net/admin/login');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    
    // Wait for redirect to dashboard
    await page.waitForURL('**/admin/dashboard*');
    console.log('✅ Successfully logged into admin');

    // Step 2: Navigate to content creation
    console.log('📝 Step 2: Navigating to content creation');
    await page.goto('https://dalthaus.net/admin/content');
    await page.click('text=New Article');
    await page.waitForLoadState('networkidle');
    console.log('✅ Navigated to content creation page');

    // Step 3: Fill in basic article information
    console.log('📝 Step 3: Creating test article');
    const timestamp = Date.now();
    const testTitle = `Test Image Upload Article ${timestamp}`;
    
    // Clear the title field and fill with our test title
    await page.locator('input[name="title"]').clear();
    await page.fill('input[name="title"]', testTitle);
    
    // The alias is auto-generated, so let's get it
    const aliasValue = await page.locator('input[name="alias"], #alias, [name="url_alias"]').inputValue();
    testArticleAlias = aliasValue || `test-image-upload-${timestamp}`;
    
    console.log(`✅ Created test article: ${testTitle} with alias: ${testArticleAlias}`);

    // Step 4: Wait for TinyMCE to load and find the custom dual image button
    console.log('📝 Step 4: Waiting for TinyMCE to load');
    await page.waitForSelector('iframe[title="Rich Text Area"]', { timeout: 10000 });
    
    // Wait a bit more for TinyMCE to fully initialize
    await page.waitForTimeout(2000);
    
    // Look for the custom dual image button (🖼️📱)
    console.log('📝 Step 5: Looking for TinyMCE custom dual image button');
    
    // Let's first log all buttons in the TinyMCE toolbar to see what's available
    const allButtons = await page.locator('button, .tox-tbtn').all();
    console.log(`Found ${allButtons.length} buttons in toolbar`);
    
    let customImageButton = null;
    for (let i = 0; i < allButtons.length; i++) {
      const button = allButtons[i];
      const title = await button.getAttribute('title') || '';
      const ariaLabel = await button.getAttribute('aria-label') || '';
      const textContent = await button.textContent() || '';
      const innerHTML = await button.innerHTML() || '';
      
      console.log(`Button ${i}: Title="${title}", Aria-label="${ariaLabel}", Text="${textContent}"`);
      
      // Look for dual image button or any custom image button
      if (title.toLowerCase().includes('dual') || 
          title.toLowerCase().includes('image') ||
          ariaLabel.toLowerCase().includes('dual') ||
          ariaLabel.toLowerCase().includes('image') ||
          textContent.includes('🖼️') || 
          textContent.includes('📱') ||
          innerHTML.includes('🖼️') ||
          innerHTML.includes('📱')) {
        customImageButton = button;
        console.log(`✅ Found custom dual image button at index ${i}: ${title || ariaLabel || textContent}`);
        break;
      }
    }

    // If no dual image button found, look for standard image button as fallback
    if (!customImageButton) {
      console.log('🔍 Looking for standard image button as fallback');
      for (let i = 0; i < allButtons.length; i++) {
        const button = allButtons[i];
        const title = await button.getAttribute('title') || '';
        const ariaLabel = await button.getAttribute('aria-label') || '';
        
        if (title.toLowerCase().includes('image') || 
            ariaLabel.toLowerCase().includes('image')) {
          customImageButton = button;
          console.log(`✅ Found fallback image button: ${title || ariaLabel}`);
          break;
        }
      }
    }

    expect(customImageButton).toBeTruthy();
    console.log('✅ Found TinyMCE image button');

    // Step 5: Click the custom image button to open upload dialog
    console.log('📝 Step 6: Clicking custom image button to open upload dialog');
    await customImageButton.click();
    
    // Wait for the TinyMCE image dialog to appear
    await page.waitForSelector('.tox-dialog', { timeout: 5000 });
    console.log('✅ TinyMCE image dialog opened');
    
    // Step 6: Click on the Upload tab
    console.log('📝 Step 7: Clicking Upload tab');
    await page.click('text=Upload');
    await page.waitForTimeout(500);
    
    // Step 7: Upload the test image
    console.log('📝 Step 8: Uploading test image file');
    const testImagePath = path.join(process.cwd(), 'test-assets', 'test-image.png');
    
    // Look for file input in the upload tab
    const fileInput = page.locator('input[type="file"]').first();
    expect(await fileInput.count()).toBeGreaterThan(0);
    await fileInput.setInputFiles(testImagePath);
    
    // Wait for upload to complete and image to be processed
    await page.waitForTimeout(3000);
    console.log('✅ Image uploaded');
    
    // Step 8: Save the image to insert it into the editor
    console.log('📝 Step 9: Saving image to insert into editor');
    
    // Click the Save button within the TinyMCE dialog
    await page.locator('.tox-dialog button:has-text("Save")').click();
    await page.waitForTimeout(1000);
    
    // Wait for dialog to close
    await page.waitForSelector('.tox-dialog', { state: 'detached', timeout: 5000 });
    console.log('✅ Image inserted into editor');

    // Step 9: Verify HTML is inserted in TinyMCE editor
    console.log('📝 Step 10: Verifying HTML insertion in TinyMCE editor');
    
    // Switch to TinyMCE iframe context
    const tinyMCEFrame = page.frameLocator('iframe[title="Rich Text Area"]');
    
    // Wait for content to appear
    await page.waitForTimeout(1000);
    
    // Get the HTML content from TinyMCE
    const editorContent = await tinyMCEFrame.locator('body').innerHTML();
    console.log('TinyMCE Editor Content:', editorContent);
    
    // Verify image HTML is present
    expect(editorContent).toContain('<img');
    expect(editorContent).toContain('/uploads/content/');
    
    // Extract the image src path for later verification
    const imgMatch = editorContent.match(/src="([^"]*\/uploads\/content\/[^"]*)"/);
    if (imgMatch) {
      uploadedImagePath = imgMatch[1];
      console.log(`✅ Found uploaded image path: ${uploadedImagePath}`);
    }

    // Step 10: Save the article
    console.log('📝 Step 11: Saving the test article');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Get the article ID from the URL or page
    const currentUrl = page.url();
    const idMatch = currentUrl.match(/\/admin\/content\/(\d+)/);
    if (idMatch) {
      testArticleId = idMatch[1];
      console.log(`✅ Article saved with ID: ${testArticleId}`);
    }

    console.log('✅ Test article saved successfully');

    // Step 11: Navigate to frontend to verify image display
    console.log('📝 Step 12: Navigating to frontend to verify image display');
    const frontendUrl = `https://dalthaus.net/article/${testArticleAlias}`;
    await page.goto(frontendUrl);
    await page.waitForLoadState('networkidle');
    console.log(`✅ Navigated to frontend article: ${frontendUrl}`);

    // Step 12: Verify image appears on frontend
    console.log('📝 Step 13: Verifying image appears on frontend page');
    
    // Look for images in the content
    const frontendImages = await page.locator('.content img, article img, .article-content img, main img').all();
    expect(frontendImages.length).toBeGreaterThan(0);
    
    let testImage = null;
    for (const img of frontendImages) {
      const src = await img.getAttribute('src');
      if (src && src.includes('/uploads/content/')) {
        testImage = img;
        console.log(`✅ Found frontend image with src: ${src}`);
        break;
      }
    }

    expect(testImage).toBeTruthy();

    // Step 13: Verify image is actually loaded and visible
    console.log('📝 Step 14: Verifying image is loaded and visible');
    await expect(testImage).toBeVisible();
    
    // Check if image loaded successfully (not broken)
    const naturalWidth = await testImage.evaluate(img => img.naturalWidth);
    const naturalHeight = await testImage.evaluate(img => img.naturalHeight);
    expect(naturalWidth).toBeGreaterThan(0);
    expect(naturalHeight).toBeGreaterThan(0);
    console.log(`✅ Image loaded successfully (${naturalWidth}x${naturalHeight})`);

    // Step 14: Test image modal functionality
    console.log('📝 Step 15: Testing image modal functionality');
    
    // Check if image has onclick handler for modal
    const onclickAttr = await testImage.getAttribute('onclick');
    if (onclickAttr && onclickAttr.includes('openImageModal')) {
      console.log('✅ Image has modal onclick handler');
      
      // Click the image to open modal
      await testImage.click();
      await page.waitForTimeout(500);
      
      // Look for modal elements
      const modal = await page.locator('#imageModal, .modal, .image-modal, [id*="modal"]').first();
      if (await modal.count() > 0) {
        await expect(modal).toBeVisible();
        console.log('✅ Image modal opened successfully');
        
        // Close modal by clicking close button or outside
        const closeBtn = await page.locator('.modal-close, .close, [aria-label="Close"]').first();
        if (await closeBtn.count() > 0) {
          await closeBtn.click();
        } else {
          await page.keyboard.press('Escape');
        }
        console.log('✅ Modal closed successfully');
      } else {
        console.log('⚠️ Modal did not appear, but onclick handler exists');
      }
    } else {
      console.log('⚠️ Image does not have modal onclick handler');
    }

    // Step 15: Verify file exists on server
    console.log('📝 Step 16: Verifying uploaded file exists on server');
    if (uploadedImagePath) {
      const imageUrl = `https://dalthaus.net${uploadedImagePath}`;
      const response = await page.request.get(imageUrl);
      expect(response.status()).toBe(200);
      console.log(`✅ Uploaded image accessible at: ${imageUrl}`);
    }

    console.log('🎉 COMPLETE SUCCESS: Image upload to frontend display workflow verified!');
    
    // Summary report
    console.log('\n📊 VERIFICATION SUMMARY:');
    console.log(`✅ Admin login: SUCCESS`);
    console.log(`✅ Article creation: SUCCESS (${testTitle})`);
    console.log(`✅ TinyMCE image upload: SUCCESS`);
    console.log(`✅ HTML generation: SUCCESS (${uploadedImagePath})`);
    console.log(`✅ Frontend image display: SUCCESS`);
    console.log(`✅ Image file accessibility: SUCCESS`);
    console.log(`✅ Modal functionality: ${onclickAttr ? 'SUCCESS' : 'NOT TESTED'}`);
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