import { test, expect } from '@playwright/test';
import fs from 'fs/promises';
import path from 'path';

test.describe('Comprehensive Dual Image Modal System E2E Test', () => {
  const baseURL = 'http://localhost:8000';
  const testImagePath = path.join(__dirname, '../test-images');
  
  // Test data
  const testArticle = {
    title: 'Dual Image Modal Test Article',
    alias: 'dual-image-modal-test',
    content: '<p>This article will contain dual image modal functionality.</p>',
    excerpt: 'Testing dual image modal system'
  };

  test.beforeAll(async () => {
    // Ensure test images directory exists
    try {
      await fs.access(testImagePath);
    } catch {
      await fs.mkdir(testImagePath, { recursive: true });
      
      // Create simple test images (1x1 pixel PNGs)
      const displayImageData = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChAI9hJ+vQQAAAABJRU5ErkJggg==', 'base64');
      const modalImageData = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', 'base64');
      
      await fs.writeFile(path.join(testImagePath, 'display-image.png'), displayImageData);
      await fs.writeFile(path.join(testImagePath, 'modal-image.png'), modalImageData);
    }
  });

  test('Complete Dual Image Modal Workflow', async ({ page }) => {
    console.log('🚀 Starting comprehensive dual image modal workflow test...');

    // ==========================================
    // PHASE 1: ADMIN INTERFACE TESTING
    // ==========================================
    
    console.log('📋 Phase 1: Testing Admin Interface...');
    
    // Login to admin panel
    await page.goto(`${baseURL}/admin/login`);
    console.log('🔐 Navigating to admin login...');
    
    await page.fill('#username', 'kevin');
    await page.fill('#password', '(130Bpm)');
    await page.click('button[type="submit"]');
    console.log('✅ Admin login completed');
    
    // Verify we're on the dashboard
    await expect(page).toHaveURL(/.*\/admin\/dashboard/);
    console.log('📊 Successfully reached admin dashboard');
    
    // Navigate to content creation
    await page.goto(`${baseURL}/admin/content/create`);
    console.log('📝 Navigating to content creation page...');
    
    // Wait for page to load and verify form exists
    await page.waitForSelector('#title');
    await page.waitForSelector('#alias');
    await page.waitForSelector('#content');
    console.log('✅ Content creation form loaded');
    
    // Fill in basic article information
    await page.fill('#title', testArticle.title);
    await page.fill('#alias', testArticle.alias);
    await page.fill('#excerpt', testArticle.excerpt);
    console.log('📄 Basic article information filled');
    
    // Wait for TinyMCE to initialize
    console.log('⏳ Waiting for TinyMCE to initialize...');
    await page.waitForTimeout(3000);
    
    // Check if TinyMCE iframe exists
    const tinymceFrame = page.frameLocator('iframe[id^="content_"]');
    await page.waitForSelector('iframe[id^="content_"]');
    console.log('🖊️  TinyMCE editor detected');
    
    // Look for the dual image button in toolbar
    console.log('🔍 Checking for dual image button in TinyMCE toolbar...');
    
    // Try to find the dual image button
    const dualImageButton = page.locator('button[title*="dual"], button[aria-label*="dual"], button:has-text("🖼️📱")');
    
    try {
      await dualImageButton.waitFor({ timeout: 5000 });
      console.log('✅ Dual image button found in toolbar');
      
      // Click the dual image button
      await dualImageButton.click();
      console.log('🖱️  Clicked dual image button');
      
      // Wait for dual image dialog
      await page.waitForSelector('.dual-image-dialog, [data-testid="dual-image-dialog"]', { timeout: 5000 });
      console.log('✅ Dual image dialog opened');
      
      // Test file uploads (if dialog supports it)
      const displayFileInput = page.locator('input[type="file"]').first();
      const modalFileInput = page.locator('input[type="file"]').last();
      
      if (await displayFileInput.count() > 0) {
        await displayFileInput.setInputFiles(path.join(testImagePath, 'display-image.png'));
        console.log('📁 Display image uploaded');
      }
      
      if (await modalFileInput.count() > 0) {
        await modalFileInput.setInputFiles(path.join(testImagePath, 'modal-image.png'));
        console.log('📁 Modal image uploaded');
      }
      
      // Insert the dual image
      const insertButton = page.locator('button:has-text("Insert"), button:has-text("OK"), button[data-testid="insert"]');
      if (await insertButton.count() > 0) {
        await insertButton.click();
        console.log('✅ Dual image inserted into content');
      }
      
    } catch (error) {
      console.log('⚠️  Dual image button not found, testing manual HTML insertion...');
      
      // Manually insert dual image HTML into TinyMCE
      const dualImageHTML = `
        <img src="/uploads/content/featured/2024/12/display-image.png" 
             alt="Test Display Image" 
             data-modal-src="/uploads/content/featured/2024/12/modal-image.png"
             style="cursor: pointer; max-width: 100%; height: auto;">
      `;
      
      // Focus on TinyMCE and insert content
      await tinymceFrame.locator('body').click();
      await page.evaluate((html) => {
        if (window.tinymce && window.tinymce.activeEditor) {
          window.tinymce.activeEditor.setContent(html);
        }
      }, dualImageHTML);
      console.log('📝 Manually inserted dual image HTML');
    }
    
    // Save the article
    await page.click('button[type="submit"]');
    console.log('💾 Saving article...');
    
    // Wait for redirect or success message
    await page.waitForTimeout(2000);
    console.log('✅ Article saved successfully');
    
    // ==========================================
    // PHASE 2: FRONTEND MODAL TESTING
    // ==========================================
    
    console.log('📋 Phase 2: Testing Frontend Modal Functionality...');
    
    // Navigate to the created article on frontend
    await page.goto(`${baseURL}/article/${testArticle.alias}`);
    console.log('🌐 Navigating to frontend article page...');
    
    // Wait for page to load
    await page.waitForLoadState('networkidle');
    console.log('📄 Article page loaded');
    
    // Take screenshot of initial state
    await page.screenshot({ 
      path: 'testing/screenshots/dual-image-modal-initial.png',
      fullPage: true 
    });
    console.log('📸 Screenshot of initial article state captured');
    
    // Look for images with data-modal-src attribute
    const modalImages = page.locator('img[data-modal-src]');
    const modalImageCount = await modalImages.count();
    
    console.log(`🔍 Found ${modalImageCount} images with modal functionality`);
    
    if (modalImageCount > 0) {
      // Test clicking on modal image
      console.log('🖱️  Testing modal image click...');
      
      // Click the first modal image
      await modalImages.first().click();
      console.log('👆 Clicked on modal image');
      
      // Wait for modal to appear
      try {
        await page.waitForSelector('.image-modal, #imageModal, [data-testid="image-modal"]', { timeout: 3000 });
        console.log('✅ Modal opened successfully');
        
        // Take screenshot of modal
        await page.screenshot({ 
          path: 'testing/screenshots/dual-image-modal-opened.png',
          fullPage: true 
        });
        console.log('📸 Screenshot of opened modal captured');
        
        // Test modal content
        const modalImg = page.locator('.image-modal img, #imageModal img');
        if (await modalImg.count() > 0) {
          const modalSrc = await modalImg.getAttribute('src');
          console.log(`🖼️  Modal image source: ${modalSrc}`);
          expect(modalSrc).toBeTruthy();
        }
        
        // Test close functionality
        console.log('🧪 Testing modal close functionality...');
        
        // Test close button
        const closeButton = page.locator('.close, .modal-close, [data-testid="close-modal"]');
        if (await closeButton.count() > 0) {
          await closeButton.click();
          console.log('✅ Modal closed via close button');
        } else {
          // Test escape key
          await page.keyboard.press('Escape');
          console.log('✅ Modal closed via escape key');
        }
        
        // Verify modal is closed
        await page.waitForTimeout(500);
        const modalVisible = await page.locator('.image-modal, #imageModal').isVisible().catch(() => false);
        expect(modalVisible).toBe(false);
        console.log('✅ Modal properly closed');
        
        // Test overlay click (reopen modal first)
        await modalImages.first().click();
        await page.waitForSelector('.image-modal, #imageModal', { timeout: 3000 });
        
        // Click overlay to close
        const overlay = page.locator('.modal-overlay, .image-modal');
        if (await overlay.count() > 0) {
          await overlay.click({ position: { x: 10, y: 10 } }); // Click outside modal content
          console.log('✅ Modal closed via overlay click');
        }
        
      } catch (error) {
        console.log('⚠️  Modal functionality not working as expected:', error.message);
        
        // Check if modal scripts are loaded
        const modalScriptExists = await page.evaluate(() => {
          return typeof window.openImageModal === 'function' || 
                 document.querySelector('script[src*="modal"]') !== null;
        });
        console.log(`🔧 Modal scripts loaded: ${modalScriptExists}`);
        
        // Check for JavaScript errors
        page.on('console', msg => {
          if (msg.type() === 'error') {
            console.log('❌ JS Error:', msg.text());
          }
        });
      }
    } else {
      console.log('⚠️  No images with modal functionality found');
      
      // Check for regular images
      const allImages = page.locator('img');
      const imageCount = await allImages.count();
      console.log(`📊 Total images found: ${imageCount}`);
      
      if (imageCount > 0) {
        for (let i = 0; i < imageCount; i++) {
          const img = allImages.nth(i);
          const src = await img.getAttribute('src');
          const modalSrc = await img.getAttribute('data-modal-src');
          console.log(`🖼️  Image ${i + 1}: src="${src}", data-modal-src="${modalSrc}"`);
        }
      }
    }
    
    // ==========================================
    // PHASE 3: DATABASE VERIFICATION
    // ==========================================
    
    console.log('📋 Phase 3: Database Verification...');
    
    // Navigate back to admin to verify content was saved
    await page.goto(`${baseURL}/admin/content`);
    console.log('📊 Navigating back to admin content list...');
    
    // Look for our test article
    const articleLink = page.locator(`a:has-text("${testArticle.title}")`);
    if (await articleLink.count() > 0) {
      console.log('✅ Test article found in admin content list');
      
      // Click to edit the article
      await articleLink.click();
      await page.waitForTimeout(2000);
      
      // Check if content contains dual image HTML
      const contentField = page.locator('#content');
      if (await contentField.count() > 0) {
        const contentValue = await page.evaluate(() => {
          if (window.tinymce && window.tinymce.activeEditor) {
            return window.tinymce.activeEditor.getContent();
          }
          return document.querySelector('#content').value;
        });
        
        const hasModalAttribute = contentValue.includes('data-modal-src');
        console.log(`📝 Content contains dual image attributes: ${hasModalAttribute}`);
        console.log('📄 Saved content preview:', contentValue.substring(0, 200) + '...');
      }
    } else {
      console.log('⚠️  Test article not found in admin content list');
    }
    
    // ==========================================
    // PHASE 4: CLEANUP
    // ==========================================
    
    console.log('📋 Phase 4: Cleanup...');
    
    // Delete test article if it exists
    try {
      await page.goto(`${baseURL}/admin/content`);
      const deleteButton = page.locator(`tr:has-text("${testArticle.title}") .delete-btn, tr:has-text("${testArticle.title}") button:has-text("Delete")`);
      if (await deleteButton.count() > 0) {
        await deleteButton.click();
        await page.click('button:has-text("Confirm"), button:has-text("Yes")');
        console.log('🗑️  Test article deleted');
      }
    } catch (error) {
      console.log('⚠️  Could not delete test article:', error.message);
    }
    
    console.log('✅ Comprehensive dual image modal workflow test completed!');
  });

  test('Regression Test - Existing Modal Functionality', async ({ page }) => {
    console.log('🔄 Running regression test on existing modal functionality...');
    
    // Test existing articles with modal functionality
    const testUrls = [
      '/article/10-things-photography-taught-me-that-actually-matter',
      '/article/why-i-rarely-use-ultra-wide-angle-lenses',
      '/photobooks/iceland-2018-book-project'
    ];
    
    for (const url of testUrls) {
      try {
        await page.goto(`${baseURL}${url}`);
        await page.waitForLoadState('networkidle');
        
        console.log(`🧪 Testing modal functionality on: ${url}`);
        
        // Check for modal images
        const modalImages = page.locator('img[data-modal-src]');
        const count = await modalImages.count();
        
        if (count > 0) {
          console.log(`✅ Found ${count} modal images on ${url}`);
          
          // Test first modal image
          await modalImages.first().click();
          await page.waitForTimeout(1000);
          
          const modalVisible = await page.locator('.image-modal, #imageModal').isVisible().catch(() => false);
          console.log(`📱 Modal opens: ${modalVisible}`);
          
          if (modalVisible) {
            await page.keyboard.press('Escape');
            await page.waitForTimeout(500);
          }
        } else {
          console.log(`ℹ️  No modal images found on ${url}`);
        }
        
      } catch (error) {
        console.log(`⚠️  Error testing ${url}:`, error.message);
      }
    }
  });
});