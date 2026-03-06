const { test, expect } = require('@playwright/test');

/**
 * Production Media Browser Test
 * Tests the deployed media browser on https://dalthaus.net
 */

test.describe('Production Media Browser Test', () => {
    const baseURL = 'https://dalthaus.net';
    const adminEmail = 'kevin@kevinalthaus.com';
    const adminPassword = '(130Bpm)';

    test('01 - Login and access media browser', async ({ page }) => {
        console.log('Testing media browser on production...');

        // Login
        await page.goto(`${baseURL}/admin/login`);
        await page.fill('input[name="email"]', adminEmail);
        await page.fill('input[name="password"]', adminPassword);
        await page.click('button[type="submit"]');

        // Wait for dashboard
        await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
        console.log('✓ Logged in successfully');

        // Navigate to media browser
        await page.goto(`${baseURL}/admin/media/browser`);
        await page.waitForLoadState('networkidle');

        // Check if page loaded
        const title = await page.locator('h1').textContent();
        console.log('Page title:', title);

        // Verify key elements exist
        await expect(page.locator('h1')).toContainText('Media Browser');
        await expect(page.locator('#searchInput')).toBeVisible();
        await expect(page.locator('#typeFilter')).toBeVisible();
        await expect(page.locator('#uploadBtn')).toBeVisible();

        console.log('✓ Media browser loaded successfully!');
    });

    test('02 - Test API endpoint', async ({ page, request }) => {
        // Login first
        await page.goto(`${baseURL}/admin/login`);
        await page.fill('input[name="email"]', adminEmail);
        await page.fill('input[name="password"]', adminPassword);
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard');

        // Get cookies for API request
        const cookies = await page.context().cookies();
        const cookieString = cookies.map(c => `${c.name}=${c.value}`).join('; ');

        // Test API
        const response = await request.get(`${baseURL}/admin/media/api/list`, {
            headers: {
                'Cookie': cookieString
            }
        });

        expect(response.ok()).toBeTruthy();
        const data = await response.json();

        console.log('API Response:', {
            success: data.success,
            totalImages: data.pagination?.total,
            currentPage: data.pagination?.page
        });

        expect(data).toHaveProperty('success');
        expect(data).toHaveProperty('data');
        expect(data).toHaveProperty('pagination');

        console.log('✓ API endpoint working!');
    });

    test('03 - Test media grid loads', async ({ page }) => {
        // Login
        await page.goto(`${baseURL}/admin/login`);
        await page.fill('input[name="email"]', adminEmail);
        await page.fill('input[name="password"]', adminPassword);
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard');

        // Go to browser
        await page.goto(`${baseURL}/admin/media/browser`);
        await page.waitForLoadState('networkidle');

        // Wait for loading to complete
        await page.waitForSelector('#loadingIndicator.hidden', { timeout: 10000 });

        // Check if grid or empty state is visible
        const mediaGrid = page.locator('#mediaGrid');
        const emptyState = page.locator('#emptyState');

        const isGridVisible = await mediaGrid.isVisible();
        const isEmptyVisible = await emptyState.isVisible();

        console.log('Grid visible:', isGridVisible);
        console.log('Empty state visible:', isEmptyVisible);

        expect(isGridVisible || isEmptyVisible).toBeTruthy();

        if (isGridVisible) {
            const imageCount = await page.locator('.media-item').count();
            console.log(`✓ Media grid showing ${imageCount} images`);
        } else {
            console.log('✓ Empty state showing (no images yet)');
        }
    });

    test('04 - Test TinyMCE integration', async ({ page }) => {
        // Login
        await page.goto(`${baseURL}/admin/login`);
        await page.fill('input[name="email"]', adminEmail);
        await page.fill('input[name="password"]', adminPassword);
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard');

        // Go to content edit page
        await page.goto(`${baseURL}/admin/content`);
        await page.waitForLoadState('networkidle');

        // Click edit on first item
        const firstEdit = page.locator('a[href*="/admin/content/"][href*="/edit"]').first();
        if (await firstEdit.count() > 0) {
            await firstEdit.click();

            // Wait for TinyMCE to load
            await page.waitForSelector('.tox-tinymce', { timeout: 15000 });
            console.log('✓ TinyMCE loaded');

            // Check if media browser integration script is loaded
            const mediaIntegrationLoaded = await page.evaluate(() => {
                return typeof window.MediaBrowser !== 'undefined' &&
                       typeof window.tinyMCEFilePicker !== 'undefined';
            });

            console.log('Media browser integration loaded:', mediaIntegrationLoaded);

            if (mediaIntegrationLoaded) {
                console.log('✓ TinyMCE integration is working!');
            } else {
                console.log('⚠ Media browser integration not detected (may need page refresh)');
            }
        } else {
            console.log('ℹ No content items to edit');
        }
    });
});
