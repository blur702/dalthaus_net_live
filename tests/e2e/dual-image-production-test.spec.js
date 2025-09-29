const { test, expect } = require('@playwright/test');

test.describe('Dual Image Production Test', () => {
    test('should test dual image functionality on production', async ({ page }) => {
        console.log('🔍 Testing dual image on production...');

        // Test against production server
        await page.goto('https://dalthaus.net/admin/login');

        // Login
        await page.fill('#username', 'kevin');
        await page.fill('#password', '(130Bpm)');
        await page.click('button[type="submit"]');
        await expect(page).toHaveURL(/.*\/admin\/dashboard/);
        console.log('✅ Production login successful');

        // Navigate to content creation
        await page.goto('https://dalthaus.net/admin/content/create');
        await page.waitForSelector('.tox-tinymce');
        await page.waitForTimeout(5000);
        console.log('✅ Content creation page loaded');

        // Check for dual image button
        console.log('🔍 Checking for dual image button...');

        const toolbarButtons = await page.locator('.tox-toolbar button').all();
        console.log(`Found ${toolbarButtons.length} toolbar buttons`);

        let foundDualImageButton = false;
        for (let i = 0; i < toolbarButtons.length; i++) {
            const button = toolbarButtons[i];
            const title = await button.getAttribute('title');
            const text = await button.textContent();

            console.log(`Button ${i}: title="${title}", text="${text}"`);

            if (title && title.includes('modal')) {
                foundDualImageButton = true;
                console.log('🎯 Found dual image button!');
                break;
            }
            if (text && text.includes('🖼️')) {
                foundDualImageButton = true;
                console.log('🎯 Found dual image button by emoji!');
                break;
            }
        }

        if (!foundDualImageButton) {
            console.log('❌ Dual image button not found in toolbar');

            // Check if the button exists but isn't visible
            const hiddenButton = await page.locator('button[title="Insert image with modal view"]').count();
            console.log(`Hidden dual image button count: ${hiddenButton}`);

            // Check TinyMCE configuration
            const tinymceConfig = await page.evaluate(() => {
                if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                    return {
                        toolbar: tinymce.activeEditor.settings.toolbar,
                        plugins: tinymce.activeEditor.settings.plugins
                    };
                }
                return null;
            });
            console.log('TinyMCE config:', tinymceConfig);
        }

        // Test API endpoint
        console.log('🔍 Testing dual image API endpoint...');
        try {
            const response = await page.request.post('https://dalthaus.net/admin/upload/dual-image');
            console.log(`API endpoint status: ${response.status()}`);

            if (response.status() === 400) {
                const responseData = await response.json();
                console.log('✅ API endpoint exists and validates correctly');
                console.log('Response:', responseData);
            } else if (response.status() === 404) {
                console.log('❌ API endpoint not found');
            } else {
                console.log(`API endpoint returned status: ${response.status()}`);
            }
        } catch (error) {
            console.log('❌ Error testing API endpoint:', error.message);
        }

        // Test JavaScript functions
        console.log('🔍 Testing JavaScript functions...');
        const jsInfo = await page.evaluate(() => {
            return {
                showDualImageDialog: typeof showDualImageDialog !== 'undefined',
                openImageModal: typeof openImageModal !== 'undefined'
            };
        });
        console.log('JavaScript functions:', jsInfo);

        console.log('🏁 Production test complete!');
    });
});