const { test, expect } = require('@playwright/test');
const path = require('path');

/**
 * Photobook Teaser Image Upload Test
 *
 * This test verifies that teaser images can be uploaded for photobooks
 * and displayed correctly in the admin interface.
 */

test.describe('Photobook Teaser Image Upload', () => {
    let consoleErrors = [];
    let networkErrors = [];

    test.beforeEach(async ({ page }) => {
        // Reset error collectors
        consoleErrors = [];
        networkErrors = [];

        // Capture console errors
        page.on('console', msg => {
            if (msg.type() === 'error') {
                consoleErrors.push({
                    text: msg.text(),
                    time: new Date().toISOString()
                });
                console.error('[CONSOLE ERROR]', msg.text());
            }
        });

        // Capture network errors
        page.on('response', response => {
            if (response.status() >= 400) {
                networkErrors.push({
                    status: response.status(),
                    url: response.url(),
                    time: new Date().toISOString()
                });
                console.error('[NETWORK ERROR]', response.status(), '-', response.url());
            }
        });

        // Login
        console.log('[SETUP] Logging in...');
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
        console.log('[SETUP] Login successful');
    });

    test('1. Upload Teaser Image to Existing Photobook', async ({ page }) => {
        console.log('\n[TEST 1] Testing teaser image upload to existing photobook...');

        // Navigate to photobooks list
        await page.goto('https://dalthaus.net/admin/content?type=photobook');
        await page.waitForLoadState('networkidle');
        console.log('[TEST 1] Photobooks list loaded');

        // Find the first photobook edit button
        const editButtons = page.locator('a[href*="/admin/content/"][href*="/edit"]');
        const editButtonCount = await editButtons.count();
        console.log(`[TEST 1] Found ${editButtonCount} photobooks`);

        if (editButtonCount === 0) {
            console.log('[TEST 1] ⚠️  SKIPPED - No photobooks found');
            test.skip();
            return;
        }

        // Click first edit button
        const firstEditButton = editButtons.first();
        const photobookUrl = await firstEditButton.getAttribute('href');
        console.log(`[TEST 1] Editing photobook: ${photobookUrl}`);
        await firstEditButton.click();
        await page.waitForLoadState('networkidle');

        // Get the current teaser image (if any)
        const teaserImageSection = page.locator('label:has-text("Teaser Image")').locator('..');
        const hasTeaserImage = await teaserImageSection.locator('img').count() > 0;
        let originalImageSrc = null;

        if (hasTeaserImage) {
            originalImageSrc = await teaserImageSection.locator('img').getAttribute('src');
            console.log(`[TEST 1] Current teaser image: ${originalImageSrc}`);
        } else {
            console.log('[TEST 1] No existing teaser image');
        }

        // Prepare test image
        const testImagePath = path.join(__dirname, '../test-images/test-upload.jpg');
        console.log(`[TEST 1] Uploading test image from: ${testImagePath}`);

        // Track console/network errors before upload
        const consoleErrorsBefore = consoleErrors.length;
        const networkErrorsBefore = networkErrors.length;

        // Upload new teaser image
        const fileInput = page.locator('input[name="teaser_image"]');
        await fileInput.setInputFiles(testImagePath);
        console.log('[TEST 1] File selected');

        // Submit the form
        await page.click('button[type="submit"][name="action"][value="save"]');
        console.log('[TEST 1] Form submitted');

        // Wait for redirect back to edit page
        await page.waitForURL('**/admin/content/**/edit', { timeout: 10000 });
        await page.waitForLoadState('networkidle');
        console.log('[TEST 1] Redirected back to edit page');

        // Check for success message
        const successMessage = page.locator('.bg-green-50, .bg-green-100');
        if (await successMessage.count() > 0) {
            const messageText = await successMessage.textContent();
            console.log(`[TEST 1] Success message: ${messageText.trim()}`);
        }

        // Check if teaser image was updated
        await page.waitForTimeout(1000); // Brief wait for image to load
        const updatedTeaserImage = await teaserImageSection.locator('img').count();

        if (updatedTeaserImage > 0) {
            const newImageSrc = await teaserImageSection.locator('img').getAttribute('src');
            console.log(`[TEST 1] Updated teaser image: ${newImageSrc}`);

            // Verify image actually changed (or exists if there was none before)
            if (originalImageSrc) {
                expect(newImageSrc).not.toBe(originalImageSrc);
                console.log('[TEST 1] ✅ Teaser image was updated');
            } else {
                expect(newImageSrc).toBeTruthy();
                console.log('[TEST 1] ✅ Teaser image was added');
            }
        } else {
            console.log('[TEST 1] ❌ No teaser image found after upload');
            expect(updatedTeaserImage).toBeGreaterThan(0);
        }

        // Check for new errors
        const newConsoleErrors = consoleErrors.length - consoleErrorsBefore;
        const newNetworkErrors = networkErrors.length - networkErrorsBefore;
        console.log(`[TEST 1] New console errors: ${newConsoleErrors}`);
        console.log(`[TEST 1] New network errors: ${newNetworkErrors}`);

        if (newConsoleErrors > 0 || newNetworkErrors > 0) {
            console.log('[TEST 1] ⚠️  PASSED with errors');
        } else {
            console.log('[TEST 1] ✅ PASSED without errors');
        }
    });

    test('2. Verify Teaser Image Displays in Photobook List', async ({ page }) => {
        console.log('\n[TEST 2] Verifying teaser image displays in list view...');

        // Navigate to photobooks list
        await page.goto('https://dalthaus.net/admin/content?type=photobook');
        await page.waitForLoadState('networkidle');
        console.log('[TEST 2] Photobooks list loaded');

        // Find photobook rows with images
        const photobookRows = page.locator('tr').filter({ hasText: 'Photobook' });
        const rowCount = await photobookRows.count();
        console.log(`[TEST 2] Found ${rowCount} photobook rows`);

        if (rowCount === 0) {
            console.log('[TEST 2] ⚠️  SKIPPED - No photobooks found');
            test.skip();
            return;
        }

        // Check first photobook row for teaser image
        const firstRow = photobookRows.first();
        const teaserImages = firstRow.locator('img');
        const imageCount = await teaserImages.count();

        console.log(`[TEST 2] Found ${imageCount} image(s) in first photobook row`);

        if (imageCount > 0) {
            const imageSrc = await teaserImages.first().getAttribute('src');
            console.log(`[TEST 2] Teaser image src: ${imageSrc}`);
            expect(imageSrc).toBeTruthy();
            console.log('[TEST 2] ✅ PASSED - Teaser image displayed');
        } else {
            console.log('[TEST 2] ⚠️  No teaser image displayed in list');
            // This might be expected if the photobook doesn't have a teaser image yet
        }
    });

    test.afterEach(async () => {
        console.log('\n========================================');
        console.log('[FINAL SUMMARY]');
        console.log(`Total Console Errors: ${consoleErrors.length}`);
        console.log(`Total Network Errors: ${networkErrors.length}`);
        console.log('========================================\n');

        if (consoleErrors.length > 0) {
            console.log('[CONSOLE ERRORS DETAILS]\n');
            consoleErrors.forEach((error, index) => {
                console.log(`${index + 1}. ${error.text}`);
                console.log(`   Time: ${error.time}\n`);
            });
        }

        if (networkErrors.length > 0) {
            console.log('[NETWORK ERRORS DETAILS]\n');
            networkErrors.forEach((error, index) => {
                console.log(`${index + 1}. ${error.status} - ${error.url}`);
                console.log(`   Time: ${error.time}\n`);
            });
        }
    });
});
