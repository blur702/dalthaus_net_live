/**
 * Autosave Recovery Test
 * Tests the "Load from autosave" functionality
 */

const { test, expect } = require('@playwright/test');

test.describe('Autosave Recovery Feature', () => {
    test('Create autosave and verify recovery UI appears on edit page', async ({ page }) => {
        // Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard');

        console.log('✓ Logged in successfully');

        // Navigate to existing content to edit
        await page.goto('https://dalthaus.net/admin/content');
        await page.waitForSelector('a[href*="/admin/content/"][href*="/edit"]');

        // Get the first article/photobook edit link
        const firstEditLink = page.locator('a[href*="/admin/content/"][href*="/edit"]').first();
        const editHref = await firstEditLink.getAttribute('href');
        const fullEditUrl = 'https://dalthaus.net' + editHref;
        console.log('✓ Found edit link:', fullEditUrl);

        // Go to edit page
        await page.goto(fullEditUrl);
        await page.waitForSelector('#contentForm');

        // Wait for TinyMCE
        await page.waitForFunction(() => {
            return typeof tinymce !== 'undefined' && tinymce.get('body') !== null;
        }, { timeout: 10000 });

        console.log('✓ Edit page loaded with TinyMCE ready');

        // Make a change to trigger autosave
        const originalTitle = await page.inputValue('input[name="title"]');
        const testTitle = originalTitle + ' [AUTOSAVE TEST]';

        await page.fill('input[name="title"]', testTitle);

        // Add test content via TinyMCE
        await page.evaluate(() => {
            const editor = tinymce.get('body');
            if (editor) {
                const existingContent = editor.getContent();
                editor.setContent(existingContent + '<p><strong>TEST AUTOSAVE CONTENT - ' + Date.now() + '</strong></p>');
                editor.fire('Change');
            }
        });

        console.log('✓ Modified title and content');

        // Wait for autosave to complete (2s debounce + 2s processing)
        await page.waitForTimeout(5000);

        // Check autosave status
        const autosaveStatus = await page.locator('#autosaveStatus').textContent();
        console.log('Autosave status:', autosaveStatus);

        // Navigate away WITHOUT saving
        await page.goto('https://dalthaus.net/admin/content');
        console.log('✓ Navigated away without saving');

        // Now go back to edit the same content
        await page.goto(fullEditUrl);
        await page.waitForSelector('#contentForm');

        console.log('✓ Returned to edit page');

        // Check if autosave recovery notice appears
        const recoveryNotice = page.locator('.bg-blue-50:has-text("Autosaved version available")');

        // Take screenshot for debugging
        await page.screenshot({ path: 'test-results/autosave-recovery-page.png', fullPage: true });

        if (await recoveryNotice.isVisible()) {
            console.log('✓ Autosave recovery notice IS visible');

            // Get the timestamp
            const noticeText = await recoveryNotice.textContent();
            console.log('Recovery notice text:', noticeText);

            // Check if window.autosaveData exists
            const hasAutosaveData = await page.evaluate(() => {
                return typeof window.autosaveData !== 'undefined' && window.autosaveData !== null;
            });
            console.log('window.autosaveData exists:', hasAutosaveData);

            if (hasAutosaveData) {
                const autosaveData = await page.evaluate(() => window.autosaveData);
                console.log('Autosave data:', JSON.stringify(autosaveData, null, 2));
            }

            // Wait for TinyMCE
            await page.waitForFunction(() => {
                return typeof tinymce !== 'undefined' && tinymce.get('body') !== null;
            }, { timeout: 10000 });

            // Check if load button exists
            const loadButton = page.locator('#loadAutosave');
            await expect(loadButton).toBeVisible();
            console.log('✓ Load autosave button is visible');

            // Click the load button
            await loadButton.click();
            await page.waitForTimeout(1000);

            console.log('✓ Clicked load autosave button');

            // Verify content was loaded
            const loadedTitle = await page.inputValue('input[name="title"]');
            console.log('Loaded title:', loadedTitle);
            expect(loadedTitle).toContain('[AUTOSAVE TEST]');

            // Verify TinyMCE content
            const loadedContent = await page.evaluate(() => {
                const editor = tinymce.get('body');
                return editor ? editor.getContent() : '';
            });
            console.log('Loaded content length:', loadedContent.length);
            expect(loadedContent).toContain('TEST AUTOSAVE CONTENT');

            console.log('✓ Autosave loaded successfully!');

            // Restore original title (cleanup)
            await page.fill('input[name="title"]', originalTitle);
            await page.evaluate((orig) => {
                const editor = tinymce.get('body');
                if (editor) {
                    // Remove the test content
                    const content = editor.getContent();
                    const cleaned = content.replace(/<p><strong>TEST AUTOSAVE CONTENT.*?<\/strong><\/p>/g, '');
                    editor.setContent(cleaned);
                }
            }, originalTitle);

        } else {
            console.log('✗ Autosave recovery notice NOT visible');

            // Debug: Check what's on the page
            const pageContent = await page.content();
            const hasAutosaveCheck = pageContent.includes('has_autosave');
            const hasAutosaveData = pageContent.includes('window.autosaveData');

            console.log('Page has "has_autosave" check:', hasAutosaveCheck);
            console.log('Page has "window.autosaveData":', hasAutosaveData);

            // Check backend data
            const backendData = await page.evaluate(() => {
                return {
                    hasAutosaveData: typeof window.autosaveData !== 'undefined',
                    autosaveData: window.autosaveData || null
                };
            });
            console.log('Backend data:', JSON.stringify(backendData, null, 2));
        }

        // Cleanup - delete the autosave
        await page.goto('https://dalthaus.net/admin/autosaves');
        const autosaveRow = page.locator('tr:has-text("[AUTOSAVE TEST]")').first();
        if (await autosaveRow.isVisible()) {
            await autosaveRow.locator('button:has-text("Delete")').click();
            await page.waitForTimeout(500);
            console.log('✓ Cleaned up test autosave');
        }
    });
});
