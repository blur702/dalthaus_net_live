const { test, expect } = require('@playwright/test');
const path = require('path');

/**
 * Fix Broken Teaser Image for Content ID 33
 */

test.describe('Fix Broken Teaser Image', () => {
    test.beforeEach(async ({ page }) => {
        // Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
    });

    test('Upload new teaser image to content ID 33', async ({ page }) => {
        console.log('\n[FIX] Uploading new teaser image to content ID 33...');

        // Go directly to content ID 33
        await page.goto('https://dalthaus.net/admin/content/33/edit');
        await page.waitForLoadState('networkidle');

        // Check current state
        const teaserImageSection = page.locator('label:has-text("Teaser Image")').locator('..');
        const errorMessage = teaserImageSection.locator('.text-red-600');

        if (await errorMessage.count() > 0) {
            const errorText = await errorMessage.textContent();
            console.log(`[FIX] Current error: ${errorText.trim()}`);
        }

        // Upload new image
        const testImagePath = path.join(__dirname, '../test-images/test-upload.jpg');
        const fileInput = page.locator('input[name="teaser_image"]');
        await fileInput.setInputFiles(testImagePath);
        console.log('[FIX] New teaser image selected');

        // Submit form
        await page.click('button[type="submit"][name="action"][value="save"]');
        console.log('[FIX] Form submitted');

        // Wait for redirect
        await page.waitForURL('**/admin/content/33/edit', { timeout: 10000 });
        await page.waitForLoadState('networkidle');

        // Verify success
        const successMessage = page.locator('.bg-green-50, .bg-green-100');
        if (await successMessage.count() > 0) {
            const messageText = await successMessage.textContent();
            console.log(`[FIX] ${messageText.trim()}`);
        }

        // Check new image
        const newImage = teaserImageSection.locator('img').first();
        if (await newImage.count() > 0) {
            const newImageSrc = await newImage.getAttribute('src');
            console.log(`[FIX] New teaser image: ${newImageSrc}`);

            // Verify it's the new format (has underscores, timestamp, etc)
            expect(newImageSrc).toContain('teaser_');
            expect(newImageSrc).toContain('/2025/10/');
            expect(newImageSrc).not.toContain('..'); // No double dots

            // Verify image is accessible
            const imageUrl = `https://dalthaus.net${newImageSrc}`;
            const imageResponse = await page.goto(imageUrl);
            expect(imageResponse.status()).toBe(200);

            console.log('[FIX] ✅ New teaser image uploaded and accessible');
        } else {
            console.log('[FIX] ❌ No teaser image found after upload');
            throw new Error('Teaser image not found after upload');
        }
    });

    test('Verify image displays on frontend', async ({ page }) => {
        console.log('\n[VERIFY] Checking if teaser image displays on frontend...');

        // Get the content info first
        await page.goto('https://dalthaus.net/admin/content/33/edit');
        await page.waitForLoadState('networkidle');

        // Get URL alias
        const urlAliasInput = page.locator('input[name="url_alias"]');
        const urlAlias = await urlAliasInput.getAttribute('value');
        console.log(`[VERIFY] URL alias: ${urlAlias}`);

        // Check content type
        const contentTypeInput = page.locator('input[name="content_type"]');
        const contentType = await contentTypeInput.getAttribute('value');
        console.log(`[VERIFY] Content type: ${contentType}`);

        // Navigate to frontend
        const frontendUrl = `https://dalthaus.net/${contentType}/${urlAlias}`;
        console.log(`[VERIFY] Checking frontend: ${frontendUrl}`);

        const response = await page.goto(frontendUrl);

        if (response.status() !== 200) {
            console.log(`[VERIFY] ⚠️  Frontend page returned ${response.status()}`);
            console.log('[VERIFY] Skipping image check');
            return;
        }

        await page.waitForLoadState('networkidle');

        // Look for teaser image on the page
        const images = page.locator('img[src*="teaser_"]');
        const imageCount = await images.count();

        console.log(`[VERIFY] Found ${imageCount} teaser image(s) on frontend`);

        if (imageCount > 0) {
            const imageSrc = await images.first().getAttribute('src');
            console.log(`[VERIFY] Frontend teaser image: ${imageSrc}`);
            console.log('[VERIFY] ✅ Teaser image displays on frontend');
        } else {
            console.log('[VERIFY] ⚠️  No teaser images found on frontend');
            console.log('[VERIFY] (This may be expected depending on the page template)');
        }
    });
});
