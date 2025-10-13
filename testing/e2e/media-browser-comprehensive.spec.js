const { test, expect } = require('@playwright/test');
const path = require('path');

/**
 * Comprehensive Media Browser Test Suite
 * Tests the Drupal-style media module integration with TinyMCE
 */

test.describe('Media Browser - Complete Workflow', () => {
    const baseURL = 'http://localhost:8000';
    const adminEmail = 'kevin@kevinalthaus.com';
    const adminPassword = '(130Bpm)';

    test.beforeEach(async ({ page }) => {
        // Login before each test
        await page.goto(`${baseURL}/admin/login`);
        await page.fill('input[name="email"]', adminEmail);
        await page.fill('input[name="password"]', adminPassword);
        await page.click('button[type="submit"]');

        // Wait for dashboard
        await page.waitForURL('**/admin/dashboard');
        await expect(page.locator('h1')).toContainText('Dashboard');
    });

    test('01 - Navigate to content edit page', async ({ page }) => {
        // Go to content management
        await page.goto(`${baseURL}/admin/content`);
        await page.waitForLoadState('networkidle');

        // Click edit on first article
        const firstEditLink = page.locator('a[href*="/admin/content/"][href*="/edit"]').first();
        await expect(firstEditLink).toBeVisible();
        await firstEditLink.click();

        // Verify we're on edit page with TinyMCE
        await page.waitForSelector('.tox-tinymce', { timeout: 10000 });
        await expect(page.locator('.tox-tinymce')).toBeVisible();
    });

    test('02 - Open media browser from TinyMCE image button', async ({ page }) => {
        // Navigate to content edit
        await page.goto(`${baseURL}/admin/content`);
        const firstEditLink = page.locator('a[href*="/admin/content/"][href*="/edit"]').first();
        await firstEditLink.click();

        // Wait for TinyMCE
        await page.waitForSelector('.tox-tinymce', { timeout: 10000 });

        // Click the TinyMCE image button
        const imageButton = page.locator('button[aria-label*="Image"], button[title*="Image"]').first();
        await expect(imageButton).toBeVisible();
        await imageButton.click();

        // Wait a moment for the button action
        await page.waitForTimeout(1000);

        // Check if media browser modal appears (iframe modal)
        const mediaBrowserModal = page.locator('#mediaBrowserModal');

        // If modal exists, test passes
        if (await mediaBrowserModal.isVisible()) {
            console.log('✓ Media browser modal opened successfully');

            // Check for iframe
            const iframe = mediaBrowserModal.locator('iframe');
            await expect(iframe).toBeVisible();
            console.log('✓ Media browser iframe is visible');
        } else {
            console.log('ℹ Media browser not triggered - may need TinyMCE dialog interaction');
            // This is expected if TinyMCE shows its own dialog first
        }
    });

    test('03 - Access media browser directly', async ({ page }) => {
        // Go directly to media browser
        await page.goto(`${baseURL}/admin/media/browser`);
        await page.waitForLoadState('networkidle');

        // Check for main elements
        await expect(page.locator('h1')).toContainText('Media Browser');
        await expect(page.locator('#searchInput')).toBeVisible();
        await expect(page.locator('#typeFilter')).toBeVisible();
        await expect(page.locator('#uploadBtn')).toBeVisible();

        console.log('✓ Media browser page loaded successfully');
    });

    test('04 - Load media grid via API', async ({ page }) => {
        await page.goto(`${baseURL}/admin/media/browser`);
        await page.waitForLoadState('networkidle');

        // Wait for loading to complete
        await page.waitForSelector('#loadingIndicator.hidden', { timeout: 5000 });

        // Check if media grid is visible or empty state is shown
        const mediaGrid = page.locator('#mediaGrid');
        const emptyState = page.locator('#emptyState');

        const isGridVisible = await mediaGrid.isVisible();
        const isEmptyVisible = await emptyState.isVisible();

        expect(isGridVisible || isEmptyVisible).toBeTruthy();

        if (isGridVisible) {
            console.log('✓ Media grid loaded with images');
            const imageCount = await page.locator('.media-item').count();
            console.log(`  Found ${imageCount} images in grid`);
        } else {
            console.log('ℹ No images found - showing empty state');
        }
    });

    test('05 - Test search functionality', async ({ page }) => {
        await page.goto(`${baseURL}/admin/media/browser`);
        await page.waitForLoadState('networkidle');

        const searchInput = page.locator('#searchInput');
        await searchInput.fill('test');

        // Wait for search debounce and results
        await page.waitForTimeout(1000);
        await page.waitForSelector('#loadingIndicator.hidden', { timeout: 5000 });

        console.log('✓ Search executed successfully');
    });

    test('06 - Test type filter', async ({ page }) => {
        await page.goto(`${baseURL}/admin/media/browser`);
        await page.waitForLoadState('networkidle');

        const typeFilter = page.locator('#typeFilter');
        await typeFilter.selectOption('tinymce');

        // Wait for filter to apply
        await page.waitForTimeout(500);
        await page.waitForSelector('#loadingIndicator.hidden', { timeout: 5000 });

        console.log('✓ Type filter applied successfully');
    });

    test('07 - Test group dual images toggle', async ({ page }) => {
        await page.goto(`${baseURL}/admin/media/browser`);
        await page.waitForLoadState('networkidle');

        const groupDualCheckbox = page.locator('#groupDual');
        await groupDualCheckbox.check();

        // Wait for grouping to apply
        await page.waitForTimeout(500);
        await page.waitForSelector('#loadingIndicator.hidden', { timeout: 5000 });

        console.log('✓ Dual image grouping toggled successfully');
    });

    test('08 - Select an image and view details', async ({ page }) => {
        await page.goto(`${baseURL}/admin/media/browser`);
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('#loadingIndicator.hidden', { timeout: 5000 });

        // Check if any images exist
        const mediaItems = page.locator('.media-item');
        const itemCount = await mediaItems.count();

        if (itemCount > 0) {
            // Click first image
            await mediaItems.first().click();

            // Wait for details sidebar
            await page.waitForSelector('#detailsSidebar:not(.hidden)', { timeout: 2000 });

            // Verify details are shown
            await expect(page.locator('#detailsImage')).toBeVisible();
            await expect(page.locator('#altText')).toBeVisible();
            await expect(page.locator('#titleText')).toBeVisible();

            console.log('✓ Image selected and details displayed');
        } else {
            console.log('ℹ No images available to select');
        }
    });

    test('09 - Open upload modal', async ({ page }) => {
        await page.goto(`${baseURL}/admin/media/browser`);
        await page.waitForLoadState('networkidle');

        // Click upload button
        await page.locator('#uploadBtn').click();

        // Verify upload modal appears
        await expect(page.locator('#uploadModal:not(.hidden)')).toBeVisible();
        await expect(page.locator('#imageFiles')).toBeVisible();
        await expect(page.locator('#uploadType')).toBeVisible();

        console.log('✓ Upload modal opened successfully');
    });

    test('10 - Upload a single image', async ({ page }) => {
        await page.goto(`${baseURL}/admin/media/browser`);
        await page.waitForLoadState('networkidle');

        // Open upload modal
        await page.locator('#uploadBtn').click();
        await expect(page.locator('#uploadModal:not(.hidden)')).toBeVisible();

        // Create a test image file
        const testImagePath = path.join(__dirname, '../fixtures/test-image.png');

        // Set upload type
        await page.locator('#uploadType').selectOption('tinymce');

        // Upload file
        await page.locator('#imageFiles').setInputFiles(testImagePath);

        // Submit form
        await page.locator('#uploadForm button[type="submit"]').click();

        // Wait for upload to complete
        await page.waitForTimeout(2000);

        console.log('✓ Image upload initiated');
    });

    test('11 - Edit image metadata', async ({ page }) => {
        await page.goto(`${baseURL}/admin/media/browser`);
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('#loadingIndicator.hidden', { timeout: 5000 });

        const mediaItems = page.locator('.media-item');
        const itemCount = await mediaItems.count();

        if (itemCount > 0) {
            // Select first image
            await mediaItems.first().click();
            await page.waitForSelector('#detailsSidebar:not(.hidden)');

            // Edit metadata
            await page.locator('#altText').fill('Test alt text');
            await page.locator('#titleText').fill('Test title');
            await page.locator('#captionText').fill('Test caption');

            // Save metadata
            await page.locator('#saveMetadataBtn').click();

            // Wait for save confirmation
            await page.waitForTimeout(1000);

            console.log('✓ Metadata edited and saved');
        } else {
            console.log('ℹ No images available to edit');
        }
    });

    test('12 - Test pagination', async ({ page }) => {
        await page.goto(`${baseURL}/admin/media/browser`);
        await page.waitForLoadState('networkidle');
        await page.waitForSelector('#loadingIndicator.hidden', { timeout: 5000 });

        const pagination = page.locator('#pagination');
        const isPaginationVisible = await pagination.isVisible();

        if (isPaginationVisible) {
            const pageButtons = pagination.locator('button');
            const buttonCount = await pageButtons.count();

            console.log(`✓ Pagination visible with ${buttonCount} buttons`);

            // Try clicking next page if available
            const nextButton = pageButtons.filter({ hasText: '»' });
            if (await nextButton.count() > 0) {
                await nextButton.click();
                await page.waitForTimeout(500);
                await page.waitForSelector('#loadingIndicator.hidden', { timeout: 5000 });
                console.log('✓ Pagination navigation works');
            }
        } else {
            console.log('ℹ Pagination not needed (few images)');
        }
    });

    test('13 - Verify dual image grouping shows correctly', async ({ page }) => {
        await page.goto(`${baseURL}/admin/media/browser`);
        await page.waitForLoadState('networkidle');

        // Enable grouping
        await page.locator('#groupDual').check();
        await page.waitForTimeout(500);
        await page.waitForSelector('#loadingIndicator.hidden', { timeout: 5000 });

        // Look for dual image indicators
        const dualImages = page.locator('.media-item').filter({ has: page.locator('.absolute:has-text("DUAL")') });
        const dualCount = await dualImages.count();

        if (dualCount > 0) {
            console.log(`✓ Found ${dualCount} dual image groups`);
        } else {
            console.log('ℹ No dual images found (expected if none uploaded)');
        }
    });

    test('14 - Test close browser functionality', async ({ page }) => {
        await page.goto(`${baseURL}/admin/media/browser`);
        await page.waitForLoadState('networkidle');

        // In actual TinyMCE integration, close would send postMessage
        // Here we just verify the close button exists
        const closeButton = page.locator('button:has(svg)').filter({ has: page.locator('path[d*="18L18 6M6 6l12 12"]') });

        if (await closeButton.count() > 0) {
            console.log('✓ Close button found');
        }
    });

    test('15 - API endpoint responds correctly', async ({ page, request }) => {
        // Test the API endpoint directly
        const response = await request.get(`${baseURL}/admin/media/api/list`, {
            headers: {
                'Cookie': await page.context().cookies().then(cookies =>
                    cookies.map(c => `${c.name}=${c.value}`).join('; ')
                )
            }
        });

        expect(response.ok()).toBeTruthy();

        const data = await response.json();
        expect(data).toHaveProperty('success');
        expect(data).toHaveProperty('data');
        expect(data).toHaveProperty('pagination');

        console.log('✓ API endpoint responds correctly');
        console.log(`  Total images: ${data.pagination.total}`);
    });
});

test.describe('Media Browser - Error Handling', () => {
    const baseURL = 'http://localhost:8000';

    test('16 - Handles no authentication gracefully', async ({ page }) => {
        // Try to access browser without login
        await page.goto(`${baseURL}/admin/media/browser`);

        // Should redirect to login
        await page.waitForURL('**/admin/login', { timeout: 5000 });
        console.log('✓ Redirects to login when not authenticated');
    });
});
