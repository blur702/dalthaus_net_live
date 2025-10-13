/**
 * Verify teaser image links on production site (dalthaus.net)
 */
const { test, expect } = require('@playwright/test');

test.describe('Production Teaser Image Verification', () => {
    test('production homepage teaser images should link to content pages', async ({ page }) => {
        // Navigate to production homepage
        await page.goto('https://dalthaus.net/');

        // Wait for page to load
        await page.waitForLoadState('networkidle');

        console.log('✓ Loaded production homepage');

        // Find all teaser images on the page
        const teaserImages = await page.locator('.teaser-image').all();

        if (teaserImages.length === 0) {
            console.log('⚠️  No teaser images found on production homepage');
            return;
        }

        console.log(`✓ Found ${teaserImages.length} teaser image(s) on production homepage`);

        // Check that each teaser image is wrapped in an anchor tag
        for (let i = 0; i < teaserImages.length; i++) {
            const teaserImage = teaserImages[i];

            // Get the parent element
            const parent = await teaserImage.locator('..').first();
            const tagName = await parent.evaluate(el => el.tagName);

            console.log(`\nTeaser image ${i + 1}:`);
            console.log(`  Parent tag: <${tagName.toLowerCase()}>`);

            // Parent should be an anchor tag
            expect(tagName.toLowerCase()).toBe('a');

            // Get the href attribute
            const href = await parent.getAttribute('href');
            expect(href).toBeTruthy();
            expect(href).not.toBe('#');

            console.log(`  ✓ Links to: ${href}`);

            // Verify link is valid (not just a hash or javascript:void)
            expect(href).toMatch(/^\/[\w-]+/);
        }

        console.log('\n✅ All teaser images have proper links');
    });

    test('production teaser images should NOT open modals', async ({ page }) => {
        // Navigate to production homepage
        await page.goto('https://dalthaus.net/');

        // Wait for page to load
        await page.waitForLoadState('networkidle');

        // Find all teaser images
        const teaserImages = await page.locator('.teaser-image').all();

        if (teaserImages.length === 0) {
            console.log('⚠️  No teaser images found on production homepage');
            return;
        }

        console.log(`Checking ${teaserImages.length} teaser images for modal functionality...`);

        // Check that teaser images don't have modal-related attributes
        for (let i = 0; i < teaserImages.length; i++) {
            const teaserImage = teaserImages[i];

            const hasModalEnabled = await teaserImage.getAttribute('data-modal-enabled');
            const hasModalSrc = await teaserImage.getAttribute('data-modal-src');

            expect(hasModalEnabled).toBeNull();
            expect(hasModalSrc).toBeNull();

            console.log(`  ✓ Teaser image ${i + 1}: No modal attributes`);
        }

        console.log('✅ No teaser images have modal functionality');
    });

    test('clicking production teaser image navigates to content page', async ({ page }) => {
        // Navigate to production homepage
        await page.goto('https://dalthaus.net/');

        // Wait for page to load
        await page.waitForLoadState('networkidle');

        // Find first teaser image
        const firstTeaser = await page.locator('.teaser-image').first();

        if (await firstTeaser.count() === 0) {
            console.log('⚠️  No teaser images found on production homepage - skipping test');
            return;
        }

        // Get the parent link
        const parentLink = await firstTeaser.locator('..').first();
        const targetUrl = await parentLink.getAttribute('href');

        console.log(`Clicking teaser image to navigate to: ${targetUrl}`);

        // Click the teaser image
        await firstTeaser.click();

        // Wait for navigation
        await page.waitForLoadState('networkidle');

        // Check that we navigated to the correct page
        const currentUrl = page.url();
        console.log(`Current URL after click: ${currentUrl}`);

        // Verify we're on a content page (photobook or article)
        expect(currentUrl).toMatch(/\/(photobook|article)\/[\w-]+/);

        console.log('✅ Successfully navigated to content page');

        // Verify no modal appeared
        const modal = await page.locator('.image-modal').count();
        expect(modal).toBe(0);

        console.log('✅ No modal appeared on click');
    });
});
