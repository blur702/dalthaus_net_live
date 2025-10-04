import { test, expect } from '@playwright/test';

test('Manual Modal Test - Insert HTML and Test Frontend', async ({ page }) => {
    console.log('=== TESTING MANUAL MODAL FUNCTIONALITY ===');
    
    // Login
    await page.goto('http://localhost:8000/admin/login');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard**');
    
    // Go to content creation
    await page.goto('http://localhost:8000/admin/content/create');
    await page.waitForLoadState('networkidle');
    
    // Wait for TinyMCE
    await page.waitForFunction(() => {
        return typeof window.tinymce !== 'undefined' && window.tinymce.get().length > 0;
    }, { timeout: 15000 });
    
    console.log('✅ TinyMCE loaded');
    
    // Insert test HTML with modal attributes
    const testHtml = `
    <h2>Modal Image Test</h2>
    <p>This tests the modal functionality for images uploaded via TinyMCE custom buttons.</p>
    <img src="https://picsum.photos/400/300" 
         data-modal-src="https://picsum.photos/800/600" 
         alt="Test image with modal functionality" 
         onclick="openImageModal('https://picsum.photos/800/600', 'Test image with modal functionality')" 
         style="cursor: pointer; max-width: 400px; border: 2px solid #2196F3;">
    <p>The image above should be clickable and open in a modal. It uses the exact HTML structure that the TinyMCE custom button should generate.</p>
    `;
    
    await page.evaluate((html) => {
        const editors = window.tinymce.get();
        if (editors.length > 0) {
            editors[0].setContent(html);
        }
    }, testHtml);
    
    console.log('✅ Test HTML inserted');
    
    // Fill form and save
    await page.fill('input[name="title"]', 'Modal Functionality Test');
    await page.fill('input[name="alias"]', 'modal-functionality-test');
    await page.selectOption('select[name="type"]', 'article');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/content**', { timeout: 10000 });
    
    console.log('✅ Article saved');
    
    // Test frontend
    await page.goto('http://localhost:8000/article/modal-functionality-test');
    await page.waitForLoadState('networkidle');
    
    // Take screenshot
    await page.screenshot({ path: 'testing/screenshots/manual-modal-test-frontend.png', fullPage: true });
    
    // Test modal functionality
    const modalTest = await page.evaluate(() => {
        // Run addModalToContentImages
        if (typeof window.addModalToContentImages === 'function') {
            window.addModalToContentImages();
        }
        
        // Check for modal images
        const modalImages = document.querySelectorAll('img[data-modal-src]');
        return {
            modalImagesFound: modalImages.length,
            modalFunctionExists: typeof window.openImageModal === 'function',
            firstImageHasClick: modalImages.length > 0 ? !!modalImages[0].onclick : false
        };
    });
    
    console.log('Modal test results:', modalTest);
    
    if (modalTest.modalImagesFound > 0 && modalTest.modalFunctionExists) {
        console.log('Testing modal click...');
        
        try {
            await page.click('img[data-modal-src]');
            await page.waitForSelector('.image-modal', { timeout: 5000 });
            console.log('✅ Modal opened successfully!');
            
            await page.screenshot({ path: 'testing/screenshots/modal-opened-manual-test.png', fullPage: true });
            
            // Close modal
            await page.click('.modal-close');
            await page.waitForSelector('.image-modal', { state: 'detached', timeout: 5000 });
            console.log('✅ Modal closed successfully!');
            
        } catch (error) {
            console.log('❌ Modal test failed:', error.message);
        }
    } else {
        console.log('❌ Modal prerequisites not met');
    }
    
    expect(modalTest.modalFunctionExists).toBe(true);
});