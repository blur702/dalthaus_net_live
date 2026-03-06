const { test, expect } = require('@playwright/test');

/**
 * Test publish/draft status functionality
 */

test.describe('Publish Status', () => {
    test('Published content should stay published when clicking Save Changes', async ({ page }) => {
        console.log('\n[TEST] Testing published status persistence...');

        // Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard');
        console.log('[STEP 1] ✅ Logged in');

        // Go to content list
        await page.goto('https://dalthaus.net/admin/content');
        await page.waitForLoadState('networkidle');
        console.log('[STEP 2] On content list page');

        // Find a published article
        const publishedBadges = page.locator('.bg-green-100.text-green-800');
        const count = await publishedBadges.count();
        console.log(`[STEP 3] Found ${count} published items`);

        if (count === 0) {
            console.log('[TEST] ⚠️  No published content found, skipping test');
            test.skip();
            return;
        }

        // Get the first published item's edit link
        const firstPublishedRow = publishedBadges.first().locator('xpath=ancestor::tr');
        const editLink = firstPublishedRow.locator('a:has-text("Edit")').first();
        const contentTitle = await firstPublishedRow.locator('td').nth(1).textContent();
        console.log(`[STEP 3] Selected published content: "${contentTitle.trim()}"`);

        await editLink.click();
        await page.waitForLoadState('networkidle');
        console.log('[STEP 4] On edit page');

        // Check which buttons are visible
        const hasSaveChanges = await page.locator('button[value="save"]:has-text("Save Changes")').count() > 0;
        const hasPublishButton = await page.locator('button[value="publish"]:has-text("Publish")').count() > 0;
        const hasUnpublishButton = await page.locator('button[value="draft"]:has-text("Unpublish")').count() > 0;

        console.log(`[STEP 4] Buttons visible:`);
        console.log(`  - Save Changes: ${hasSaveChanges}`);
        console.log(`  - Save & Publish: ${hasPublishButton}`);
        console.log(`  - Unpublish (Save as Draft): ${hasUnpublishButton}`);

        // Make a small change (add a space to title to trigger save)
        await page.locator('input[name="title"]').fill(contentTitle.trim() + ' ');
        await page.locator('input[name="title"]').fill(contentTitle.trim()); // Remove the space

        // Click "Save Changes" button
        await page.locator('button[value="save"]:has-text("Save Changes")').click();
        await page.waitForLoadState('networkidle');
        console.log('[STEP 5] Clicked "Save Changes"');

        // Wait for redirect to content view or edit page
        await page.waitForTimeout(1000);
        const currentUrl = page.url();
        console.log(`[STEP 5] Current URL: ${currentUrl}`);

        // Go back to content list to check status
        await page.goto('https://dalthaus.net/admin/content');
        await page.waitForLoadState('networkidle');

        // Find the same content item by title
        const contentRow = page.locator(`tr:has-text("${contentTitle.trim()}")`).first();

        // Get the status badge specifically (should be in the "Status" column, not content type)
        const statusCell = contentRow.locator('td').nth(3); // Status is usually 4th column (0-indexed = 3)
        const statusBadge = statusCell.locator('.bg-green-100.text-green-800, .bg-yellow-100.text-yellow-800');
        const statusText = await statusBadge.textContent();
        console.log(`[STEP 6] Status after save: "${statusText.trim()}"`);

        // Check if it's still published
        const isStillPublished = statusText.trim().toLowerCase() === 'published';
        console.log(`[STEP 6] Is still published: ${isStillPublished}`);

        if (!isStillPublished) {
            console.log('[STEP 6] ❌ ISSUE FOUND: Published content became draft after clicking "Save Changes"!');
            console.log(`[STEP 6] Expected: "Published", Got: "${statusText.trim()}"`);
            throw new Error('Published content should stay published when clicking "Save Changes"');
        }

        console.log('[STEP 6] ✅ Content remained published after Save Changes');
    });

    test('Draft content with Publish button should become published', async ({ page }) => {
        console.log('\n[TEST] Testing draft to published transition...');

        // Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard');
        console.log('[STEP 1] ✅ Logged in');

        // Go to content list with draft filter
        await page.goto('https://dalthaus.net/admin/content?status=draft');
        await page.waitForLoadState('networkidle');
        console.log('[STEP 2] On content list page (draft filter)');

        // Find a draft article
        const draftBadges = page.locator('.bg-yellow-100.text-yellow-800');
        const count = await draftBadges.count();
        console.log(`[STEP 3] Found ${count} draft items`);

        if (count === 0) {
            console.log('[TEST] ⚠️  No draft content found, skipping test');
            test.skip();
            return;
        }

        // Get the first draft item's edit link
        const firstDraftRow = draftBadges.first().locator('xpath=ancestor::tr');
        const editLink = firstDraftRow.locator('a:has-text("Edit")').first();
        const contentTitle = await firstDraftRow.locator('td').nth(1).textContent();
        console.log(`[STEP 3] Selected draft content: "${contentTitle.trim()}"`);

        await editLink.click();
        await page.waitForLoadState('networkidle');
        console.log('[STEP 4] On edit page');

        // Check which buttons are visible
        const hasSaveChanges = await page.locator('button[value="save"]:has-text("Save Changes")').count() > 0;
        const hasPublishButton = await page.locator('button[value="publish"]:has-text("Publish")').count() > 0;
        const hasUnpublishButton = await page.locator('button[value="draft"]:has-text("Unpublish")').count() > 0;

        console.log(`[STEP 4] Buttons visible:`);
        console.log(`  - Save Changes: ${hasSaveChanges}`);
        console.log(`  - Save & Publish: ${hasPublishButton}`);
        console.log(`  - Unpublish (Save as Draft): ${hasUnpublishButton}`);

        if (!hasPublishButton) {
            console.log('[TEST] ❌ Expected "Save & Publish" button for draft content but it was not found!');
            throw new Error('Draft content should show "Save & Publish" button');
        }

        // Click "Save & Publish" button
        await page.locator('button[value="publish"]:has-text("Publish")').click();
        await page.waitForLoadState('networkidle');
        console.log('[STEP 5] Clicked "Save & Publish"');

        // Wait for redirect
        await page.waitForTimeout(1000);

        // Go back to content list to check status
        await page.goto('https://dalthaus.net/admin/content');
        await page.waitForLoadState('networkidle');

        // Find the same content item by title
        const contentRow = page.locator(`tr:has-text("${contentTitle.trim()}")`).first();

        // Get the status badge specifically
        const statusCell = contentRow.locator('td').nth(3);
        const statusBadge = statusCell.locator('.bg-green-100.text-green-800, .bg-yellow-100.text-yellow-800');
        const statusText = await statusBadge.textContent();
        console.log(`[STEP 6] Status after publish: "${statusText.trim()}"`);

        // Check if it's now published
        const isNowPublished = statusText.trim().toLowerCase() === 'published';
        console.log(`[STEP 6] Is now published: ${isNowPublished}`);

        if (!isNowPublished) {
            console.log('[STEP 6] ❌ ISSUE: Draft content did not become published after clicking "Save & Publish"!');
            console.log(`[STEP 6] Expected: "Published", Got: "${statusText.trim()}"`);
            throw new Error('Draft content should become published when clicking "Save & Publish"');
        }

        console.log('[STEP 6] ✅ Draft content successfully published');
    });
});
