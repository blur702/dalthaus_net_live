const { test, expect } = require('@playwright/test');
const path = require('path');

/**
 * Verify Uploaded Images Are Accessible (Debug 404 Issue)
 */

test.describe('Image Upload 404 Debug', () => {
    test.beforeEach(async ({ page }) => {
        // Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
    });

    test('Check if uploaded teaser image is accessible', async ({ page }) => {
        console.log('\n[DEBUG] Starting image upload and accessibility test...');

        // Navigate to first photobook
        await page.goto('https://dalthaus.net/admin/content?type=photobook');
        await page.waitForLoadState('networkidle');

        const editButtons = page.locator('a[href*="/admin/content/"][href*="/edit"]');
        const editButtonCount = await editButtons.count();

        if (editButtonCount === 0) {
            console.log('[DEBUG] ⚠️  SKIPPED - No photobooks found');
            test.skip();
            return;
        }

        // Go to edit page
        await editButtons.first().click();
        await page.waitForLoadState('networkidle');

        const currentUrl = page.url();
        console.log(`[DEBUG] Editing photobook at: ${currentUrl}`);

        // Upload test image
        const testImagePath = path.join(__dirname, '../test-images/test-upload.jpg');
        const fileInput = page.locator('input[name="teaser_image"]');
        await fileInput.setInputFiles(testImagePath);
        console.log('[DEBUG] File selected for upload');

        // Submit form and wait for response
        const responsePromise = page.waitForResponse(
            response => response.url().includes('/update'),
            { timeout: 10000 }
        );

        await page.click('button[type="submit"][name="action"][value="save"]');
        const response = await responsePromise;

        console.log(`[DEBUG] Form submission response: ${response.status()}`);

        // Wait for redirect back to edit page
        await page.waitForURL('**/admin/content/**/edit', { timeout: 10000 });
        await page.waitForLoadState('networkidle');

        // Get the teaser image URL from the page
        const teaserImageSection = page.locator('label:has-text("Teaser Image")').locator('..');
        const teaserImage = teaserImageSection.locator('img').first();

        if (await teaserImage.count() === 0) {
            console.log('[DEBUG] ❌ No teaser image element found on page');
            throw new Error('Teaser image element not found after upload');
        }

        const imageSrc = await teaserImage.getAttribute('src');
        console.log(`[DEBUG] Teaser image src attribute: ${imageSrc}`);

        // Try to load the image directly
        const imageUrl = imageSrc.startsWith('http') ? imageSrc : `https://dalthaus.net${imageSrc}`;
        console.log(`[DEBUG] Full image URL: ${imageUrl}`);

        // Navigate to the image URL directly
        const imageResponse = await page.goto(imageUrl);
        const imageStatus = imageResponse.status();

        console.log(`[DEBUG] Direct image access status: ${imageStatus}`);

        if (imageStatus === 404) {
            console.log('[DEBUG] ❌ Image returns 404 - file does not exist on server');
            console.log(`[DEBUG] Image path that failed: ${imageSrc}`);

            // Check what the server logs might say
            console.log('[DEBUG] This means either:');
            console.log('[DEBUG]   1. Directory was not created');
            console.log('[DEBUG]   2. File was not moved successfully');
            console.log('[DEBUG]   3. Permissions prevent access');
            console.log('[DEBUG]   4. Path in database is wrong');

            throw new Error(`Uploaded image returns 404: ${imageSrc}`);
        }

        if (imageStatus === 200) {
            console.log('[DEBUG] ✅ Image is accessible - upload worked correctly');

            // Check image dimensions to verify it's a real image
            const imageElement = page.locator('img').first();
            const box = await imageElement.boundingBox();

            if (box) {
                console.log(`[DEBUG] Image dimensions: ${box.width}x${box.height}px`);
            }
        } else {
            console.log(`[DEBUG] ⚠️  Unexpected status code: ${imageStatus}`);
        }

        expect(imageStatus).toBe(200);
    });

    test('Check server error logs', async ({ page }) => {
        console.log('\n[DEBUG] Checking if we can access server logs...');

        // This test is informational - helps debug permission issues
        const testDirs = [
            '/uploads',
            '/uploads/content',
            '/uploads/content/teasers',
            '/uploads/content/featureds'
        ];

        console.log('[DEBUG] Expected directory structure:');
        testDirs.forEach(dir => {
            console.log(`[DEBUG]   - ${dir}`);
        });

        console.log('[DEBUG] Server should create: /uploads/content/teasers/YYYY/MM/');
        console.log('[DEBUG] Server should create: /uploads/content/featureds/YYYY/MM/');
        console.log('[DEBUG] Check production logs/error.log for directory creation messages');
    });
});
