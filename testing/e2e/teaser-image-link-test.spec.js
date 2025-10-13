/**
 * Test that teaser images on the homepage link to content pages
 * instead of opening modal windows
 */
const { test, expect } = require('@playwright/test');

test.describe('Homepage Teaser Image Links', () => {
    test('teaser images should link to photobook pages, not open modals', async ({ page }) => {
        // Navigate to homepage
        await page.goto('http://localhost:8000/');

        // Wait for page to load
        await page.waitForLoadState('networkidle');

        // Find all teaser images on the page
        const teaserImages = await page.locator('.teaser-image').all();

        if (teaserImages.length === 0) {
            console.log('⚠️  No teaser images found on homepage - skipping test');
            return;
        }

        console.log(`✓ Found ${teaserImages.length} teaser image(s) on homepage`);

        // Check that each teaser image is wrapped in an anchor tag
        for (let i = 0; i < teaserImages.length; i++) {
            const teaserImage = teaserImages[i];

            // Get the parent element
            const parent = await teaserImage.locator('..').first();
            const tagName = await parent.evaluate(el => el.tagName);

            console.log(`Teaser image ${i + 1}: parent tag is <${tagName.toLowerCase()}>`);

            // Parent should be an anchor tag
            expect(tagName.toLowerCase()).toBe('a');

            // Get the href attribute
            const href = await parent.getAttribute('href');
            expect(href).toBeTruthy();
            expect(href).not.toBe('#');

            console.log(`  ✓ Links to: ${href}`);
        }

        // Test clicking on the first teaser image
        if (teaserImages.length > 0) {
            const firstTeaser = teaserImages[0];
            const parentLink = await firstTeaser.locator('..').first();
            const targetUrl = await parentLink.getAttribute('href');

            console.log(`\nClicking first teaser image to navigate to: ${targetUrl}`);

            // Click the teaser image
            await firstTeaser.click();

            // Wait for navigation
            await page.waitForLoadState('networkidle');

            // Check that we navigated to the correct page
            const currentUrl = page.url();
            console.log(`Current URL after click: ${currentUrl}`);

            expect(currentUrl).toContain(targetUrl.replace(/^\//, ''));

            console.log('✓ Successfully navigated to content page');
        }
    });

    test('teaser images should NOT trigger modal functionality', async ({ page }) => {
        // Navigate to homepage
        await page.goto('http://localhost:8000/');

        // Wait for page to load
        await page.waitForLoadState('networkidle');

        // Find all teaser images
        const teaserImages = await page.locator('.teaser-image').all();

        if (teaserImages.length === 0) {
            console.log('⚠️  No teaser images found on homepage - skipping test');
            return;
        }

        // Check that teaser images don't have modal-related attributes
        for (let i = 0; i < teaserImages.length; i++) {
            const teaserImage = teaserImages[i];

            const hasModalEnabled = await teaserImage.getAttribute('data-modal-enabled');
            const hasModalSrc = await teaserImage.getAttribute('data-modal-src');
            const hasModalClass = await teaserImage.evaluate(el => el.classList.contains('modal-image'));

            expect(hasModalEnabled).toBeNull();
            expect(hasModalSrc).toBeNull();
            expect(hasModalClass).toBe(false);

            console.log(`✓ Teaser image ${i + 1} does not have modal functionality`);
        }
    });

    test('teaser images should have hover effect', async ({ page }) => {
        // Navigate to homepage
        await page.goto('http://localhost:8000/');

        // Wait for page to load
        await page.waitForLoadState('networkidle');

        // Find first teaser image
        const firstTeaser = await page.locator('.teaser-image').first();

        if (await firstTeaser.count() === 0) {
            console.log('⚠️  No teaser images found on homepage - skipping test');
            return;
        }

        // Check that the image has transition-opacity class for hover effect
        const hasTransition = await firstTeaser.evaluate(el =>
            el.classList.contains('transition-opacity') ||
            getComputedStyle(el).transition.includes('opacity')
        );

        expect(hasTransition).toBe(true);
        console.log('✓ Teaser images have hover transition effect');
    });
});
