const { test, expect } = require('@playwright/test');

test.describe('Production Auto-save Verification', () => {
    test('should demonstrate auto-save functionality on production site', async ({ page }) => {
        // Track console messages for debugging
        page.on('console', msg => {
            if (msg.type() === 'log') {
                console.log('Browser:', msg.text());
            } else if (msg.type() === 'error') {
                console.log('Browser Error:', msg.text());
            }
        });

        console.log('🔄 Starting production auto-save test...');

        // Step 1: Navigate to homepage first
        await page.goto('https://dalthaus.net/');
        console.log('✓ Homepage loaded');

        // Step 2: Navigate to admin login
        await page.goto('https://dalthaus.net/admin/login');
        console.log('✓ Admin login page loaded');

        // Wait for login form
        await page.waitForSelector('input[name="username"]', { timeout: 10000 });
        console.log('✓ Login form found');

        // Step 3: Login with admin credentials
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        console.log('✓ Login form submitted');

        // Wait for dashboard redirect
        await page.waitForURL('**/admin/dashboard', { timeout: 15000 });
        console.log('✓ Successfully logged into admin dashboard');

        // Step 4: Navigate to create article page
        await page.goto('https://dalthaus.net/admin/content/create?type=article');
        await page.waitForSelector('#contentForm', { timeout: 10000 });
        console.log('✓ Create article form loaded');

        // Step 5: Wait for auto-save to initialize
        await page.waitForFunction(() => window.autoSave !== undefined, { timeout: 5000 });
        console.log('✓ Auto-save script loaded');

        // Verify we're in create mode
        const autoSaveState = await page.evaluate(() => {
            return {
                isCreateMode: window.autoSave.isCreateMode,
                isDraftCreated: window.autoSave.isDraftCreated,
                contentId: window.autoSave.contentId,
                isEnabled: window.autoSave.isEnabled
            };
        });

        expect(autoSaveState.isCreateMode).toBeTruthy();
        expect(autoSaveState.isDraftCreated).toBeFalsy();
        expect(autoSaveState.contentId).toBeFalsy();
        expect(autoSaveState.isEnabled).toBeFalsy();
        console.log('✓ Auto-save in create mode, not enabled yet');

        // Step 6: Enter a unique title
        const testTitle = `Auto-save Test Article ${Date.now()}`;
        const titleField = page.locator('#title');
        await titleField.fill(testTitle);
        console.log('✓ Title entered:', testTitle);

        // Step 7: Wait for draft creation (2 second debounce + processing time)
        console.log('⏳ Waiting for draft creation...');
        await page.waitForTimeout(3000);

        // Step 8: Check for draft creation success message
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && (
                    status.textContent.includes('Draft created') || 
                    status.textContent.includes('auto-save enabled')
                );
            },
            { timeout: 15000 }
        );
        console.log('✓ Draft creation status message appeared');

        // Step 9: Verify auto-save state changed
        const updatedAutoSaveState = await page.evaluate(() => {
            return {
                isCreateMode: window.autoSave.isCreateMode,
                isDraftCreated: window.autoSave.isDraftCreated,
                contentId: window.autoSave.contentId,
                isEnabled: window.autoSave.isEnabled
            };
        });

        expect(updatedAutoSaveState.isCreateMode).toBeFalsy();
        expect(updatedAutoSaveState.isDraftCreated).toBeTruthy();
        expect(updatedAutoSaveState.contentId).toBeTruthy();
        expect(updatedAutoSaveState.isEnabled).toBeTruthy();
        console.log('✓ Auto-save state updated - Content ID:', updatedAutoSaveState.contentId);

        // Step 10: Verify form action was updated
        const formAction = await page.locator('#contentForm').getAttribute('action');
        expect(formAction).toMatch(/\/admin\/content\/\d+\/update/);
        console.log('✓ Form action updated to edit mode:', formAction);

        // Step 11: Verify URL alias was generated
        const urlAliasField = page.locator('#url_alias');
        const urlAlias = await urlAliasField.inputValue();
        expect(urlAlias).toBeTruthy();
        expect(urlAlias).toMatch(/^[a-z0-9\-]+$/);
        console.log('✓ URL alias generated:', urlAlias);

        // Step 12: Test continued auto-save functionality
        console.log('🔄 Testing continued auto-save...');
        
        const bodyField = page.locator('#body');
        await bodyField.fill('This is test body content for auto-save verification on production.');
        
        // Wait for auto-save
        await page.waitForTimeout(2500);
        
        // Check for save confirmation
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && status.textContent.includes('Saved at');
            },
            { timeout: 10000 }
        );
        console.log('✓ Body content auto-saved successfully');

        // Step 13: Verify persistence by refreshing
        console.log('🔄 Testing persistence after page refresh...');
        await page.reload();
        await page.waitForSelector('#title', { timeout: 10000 });
        
        const savedTitle = await titleField.inputValue();
        const savedBody = await bodyField.inputValue();
        
        expect(savedTitle).toBe(testTitle);
        expect(savedBody).toBe('This is test body content for auto-save verification on production.');
        console.log('✓ Content persisted after page reload');

        // Step 14: Test teaser auto-save
        console.log('🔄 Testing teaser auto-save...');
        const teaserField = page.locator('#teaser');
        await teaserField.fill('This is a test teaser for the auto-save functionality.');
        
        // Wait for auto-save
        await page.waitForTimeout(2500);
        
        // Check for save confirmation
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && status.textContent.includes('Saved at');
            },
            { timeout: 10000 }
        );
        console.log('✓ Teaser content auto-saved successfully');

        // Step 15: Navigate to content management to verify the record exists
        console.log('🔄 Verifying record in content management...');
        await page.goto('https://dalthaus.net/admin/content');
        await page.waitForSelector('.content-item', { timeout: 10000 });
        
        // Look for our test article in the list
        const articleExists = await page.evaluate((title) => {
            const rows = document.querySelectorAll('.content-item');
            for (let row of rows) {
                if (row.textContent.includes(title)) {
                    return true;
                }
            }
            return false;
        }, testTitle);
        
        expect(articleExists).toBeTruthy();
        console.log('✓ Article found in content management list');

        // Step 16: Clean up - delete the test article
        console.log('🔄 Cleaning up test data...');
        
        // Find and click the delete button for our test article
        const deleteSuccess = await page.evaluate((title) => {
            const rows = document.querySelectorAll('.content-item');
            for (let row of rows) {
                if (row.textContent.includes(title)) {
                    const deleteButton = row.querySelector('button[onclick*="delete"]');
                    if (deleteButton) {
                        deleteButton.click();
                        return true;
                    }
                }
            }
            return false;
        }, testTitle);

        if (deleteSuccess) {
            // Confirm deletion if a confirmation dialog appears
            try {
                await page.waitForFunction(() => window.confirm, { timeout: 2000 });
                await page.evaluate(() => window.confirm = () => true);
            } catch (e) {
                // No confirmation dialog, that's fine
            }
            
            await page.waitForTimeout(1000);
            console.log('✓ Test article deleted');
        } else {
            console.log('⚠️ Could not delete test article automatically');
        }

        console.log('🎉 All production auto-save tests passed successfully!');
    });

    test('should handle photobook auto-save on production', async ({ page }) => {
        console.log('🔄 Testing photobook auto-save on production...');

        // Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.waitForSelector('input[name="username"]', { timeout: 10000 });
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard', { timeout: 15000 });

        // Navigate to create photobook
        await page.goto('https://dalthaus.net/admin/content/create?type=photobook');
        await page.waitForSelector('#contentForm', { timeout: 10000 });
        await page.waitForFunction(() => window.autoSave !== undefined, { timeout: 5000 });

        // Enter title
        const testTitle = `Auto-save Test Photobook ${Date.now()}`;
        await page.locator('#title').fill(testTitle);

        // Wait for draft creation
        await page.waitForTimeout(3000);
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && status.textContent.includes('Draft created');
            },
            { timeout: 15000 }
        );

        // Verify content type is preserved
        const contentTypeField = page.locator('input[name="content_type"]');
        const contentType = await contentTypeField.inputValue();
        expect(contentType).toBe('photobook');
        console.log('✓ Photobook content type preserved');

        // Verify auto-save enabled
        const autoSaveEnabled = await page.evaluate(() => {
            return window.autoSave && window.autoSave.isEnabled && window.autoSave.contentId;
        });
        expect(autoSaveEnabled).toBeTruthy();
        console.log('✓ Photobook auto-save enabled after draft creation');

        console.log('✓ Photobook auto-save test completed');
    });
});