const { test, expect } = require('@playwright/test');

test.describe('Quick Dual Image Diagnostic', () => {
    test('should diagnose dual image implementation', async ({ page }) => {
        console.log('🔍 Starting diagnostic test...');

        // Step 1: Login
        console.log('1. Testing login...');
        await page.goto('http://localhost:8000/admin/login');
        await page.fill('#username', 'kevin');
        await page.fill('#password', '(130Bpm)');
        await page.click('button[type="submit"]');
        await expect(page).toHaveURL(/.*\/admin\/dashboard/);
        console.log('✅ Login successful');

        // Step 2: Navigate to content creation
        console.log('2. Testing content creation page...');
        await page.goto('http://localhost:8000/admin/content/create');
        await page.waitForSelector('.tox-tinymce', { timeout: 10000 });
        console.log('✅ Content creation page loaded');

        // Step 3: Wait for TinyMCE to fully initialize
        console.log('3. Waiting for TinyMCE initialization...');
        await page.waitForTimeout(5000);

        // Step 4: Check all toolbar buttons
        console.log('4. Checking toolbar buttons...');
        const toolbarButtons = await page.locator('.tox-toolbar button').all();
        console.log(`Found ${toolbarButtons.length} toolbar buttons`);

        for (let i = 0; i < toolbarButtons.length; i++) {
            const button = toolbarButtons[i];
            const title = await button.getAttribute('title');
            const text = await button.textContent();
            const ariaLabel = await button.getAttribute('aria-label');

            console.log(`Button ${i}: title="${title}", text="${text}", aria-label="${ariaLabel}"`);

            // Check if this is our dual image button
            if (title && title.includes('modal')) {
                console.log('🎯 Found potential dual image button!');
            }
            if (text && text.includes('🖼️')) {
                console.log('🎯 Found button with image emoji!');
            }
        }

        // Step 5: Check if our custom button exists anywhere
        console.log('5. Searching for dual image button...');

        const buttonSelectors = [
            'button[title="Insert image with modal view"]',
            'button:has-text("🖼️📱")',
            'button[aria-label*="modal"]',
            'button[title*="modal"]'
        ];

        for (const selector of buttonSelectors) {
            const count = await page.locator(selector).count();
            console.log(`Selector "${selector}": ${count} matches`);
        }

        // Step 6: Check TinyMCE configuration
        console.log('6. Checking TinyMCE configuration...');
        const tinymceInfo = await page.evaluate(() => {
            if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                const editor = tinymce.activeEditor;
                return {
                    plugins: editor.settings.plugins,
                    toolbar: editor.settings.toolbar,
                    hasCustomButton: !!editor.ui.registry.getAll().buttons.dualimage
                };
            }
            return { error: 'TinyMCE not available' };
        });

        console.log('TinyMCE info:', tinymceInfo);

        // Step 7: Test direct API endpoint
        console.log('7. Testing API endpoint...');
        const response = await page.request.post('/admin/upload/dual-image');
        console.log(`API endpoint status: ${response.status()}`);

        if (response.status() !== 404) {
            const responseData = await response.json();
            console.log('API response:', responseData);
        }

        // Step 8: Check if JavaScript functions exist
        console.log('8. Checking JavaScript functions...');
        const jsInfo = await page.evaluate(() => {
            return {
                showDualImageDialog: typeof showDualImageDialog !== 'undefined',
                openImageModal: typeof openImageModal !== 'undefined',
                closeDualImageDialog: typeof closeDualImageDialog !== 'undefined'
            };
        });
        console.log('JavaScript functions:', jsInfo);

        // Step 9: Check console for errors
        console.log('9. Checking for JavaScript errors...');
        const consoleLogs = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                consoleLogs.push(`ERROR: ${msg.text()}`);
            }
        });

        // Wait a bit to catch any delayed errors
        await page.waitForTimeout(2000);

        if (consoleLogs.length > 0) {
            console.log('JavaScript errors found:');
            consoleLogs.forEach(log => console.log(log));
        } else {
            console.log('✅ No JavaScript errors detected');
        }

        console.log('🏁 Diagnostic complete!');
    });
});