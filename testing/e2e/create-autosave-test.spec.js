const { test, expect } = require('@playwright/test');

test.describe('Auto-save on create forms', () => {
    test.beforeEach(async ({ page }) => {
        // Navigate to login page
        await page.goto('http://localhost:9000/admin/login');
        
        // Login with admin credentials
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        
        // Wait for redirect to dashboard
        await page.waitForURL('**/admin/dashboard');
    });

    test('should create draft and enable auto-save after title entry', async ({ page }) => {
        // Navigate to create article page
        await page.goto('http://localhost:9000/admin/content/create?type=article');
        
        // Wait for form to load
        await page.waitForSelector('#contentForm', { timeout: 10000 });
        
        // Check that auto-save is loaded but shows waiting message
        await page.waitForFunction(() => window.autoSave !== undefined, { timeout: 5000 });
        
        // Should show info status initially
        const statusIndicator = page.locator('#autosave-status');
        await expect(statusIndicator).toBeAttached();
        
        // Enter a title
        const testTitle = 'Test Auto-save Article ' + Date.now();
        const titleField = page.locator('#title');
        await titleField.fill(testTitle);
        
        // Wait for draft creation (2 seconds debounce + processing time)
        await page.waitForTimeout(3000);
        
        // Check that status changes to indicate draft creation
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && (
                    status.textContent.includes('Draft created') || 
                    status.textContent.includes('auto-save enabled')
                );
            },
            { timeout: 10000 }
        );
        
        console.log('✓ Draft created after title entry');
        
        // Verify auto-save is now enabled by checking internal state
        const autoSaveEnabled = await page.evaluate(() => {
            return window.autoSave && window.autoSave.isEnabled && window.autoSave.contentId;
        });
        
        expect(autoSaveEnabled).toBeTruthy();
        console.log('✓ Auto-save enabled after draft creation');
        
        // Test that subsequent changes are auto-saved
        const bodyField = page.locator('#body');
        await bodyField.fill('This is test content for auto-save verification.');
        
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
        
        // Verify URL alias was generated
        const urlAliasField = page.locator('#url_alias');
        const urlAlias = await urlAliasField.inputValue();
        expect(urlAlias).toBeTruthy();
        expect(urlAlias).toMatch(/^[a-z0-9\-]+$/);
        
        console.log('✓ URL alias generated:', urlAlias);
        
        // Test persistence by refreshing page
        await page.reload();
        await page.waitForSelector('#title', { timeout: 10000 });
        
        const savedTitle = await titleField.inputValue();
        const savedBody = await bodyField.inputValue();
        
        expect(savedTitle).toBe(testTitle);
        expect(savedBody).toBe('This is test content for auto-save verification.');
        
        console.log('✓ Content persisted after page reload');
    });

    test('should work for photobook creation', async ({ page }) => {
        // Navigate to create photobook page
        await page.goto('http://localhost:9000/admin/content/create?type=photobook');
        
        // Wait for form to load
        await page.waitForSelector('#contentForm', { timeout: 10000 });
        await page.waitForFunction(() => window.autoSave !== undefined, { timeout: 5000 });
        
        // Enter a title
        const testTitle = 'Test Auto-save Photobook ' + Date.now();
        const titleField = page.locator('#title');
        await titleField.fill(testTitle);
        
        // Wait for draft creation
        await page.waitForTimeout(3000);
        
        // Check that draft was created for photobook
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && status.textContent.includes('Draft created');
            },
            { timeout: 10000 }
        );
        
        console.log('✓ Photobook draft created successfully');
        
        // Verify content type is preserved
        const contentTypeField = page.locator('input[name="content_type"]');
        const contentType = await contentTypeField.inputValue();
        expect(contentType).toBe('photobook');
        
        console.log('✓ Content type preserved as photobook');
    });

    test('should not create draft with empty title', async ({ page }) => {
        // Navigate to create page
        await page.goto('http://localhost:9000/admin/content/create?type=article');
        
        await page.waitForSelector('#contentForm', { timeout: 10000 });
        await page.waitForFunction(() => window.autoSave !== undefined, { timeout: 5000 });
        
        // Try to enter body content without title
        const bodyField = page.locator('#body');
        await bodyField.fill('Body content without title');
        
        // Wait to see if anything happens
        await page.waitForTimeout(3000);
        
        // Should not have created a draft
        const autoSaveState = await page.evaluate(() => {
            return {
                isCreateMode: window.autoSave.isCreateMode,
                isDraftCreated: window.autoSave.isDraftCreated,
                contentId: window.autoSave.contentId
            };
        });
        
        expect(autoSaveState.isCreateMode).toBeTruthy();
        expect(autoSaveState.isDraftCreated).toBeFalsy();
        expect(autoSaveState.contentId).toBeFalsy();
        
        console.log('✓ No draft created without title');
    });

    test('should handle draft creation errors gracefully', async ({ page }) => {
        // Navigate to create page
        await page.goto('http://localhost:9000/admin/content/create?type=article');
        
        await page.waitForSelector('#contentForm', { timeout: 10000 });
        await page.waitForFunction(() => window.autoSave !== undefined, { timeout: 5000 });
        
        // Mock the create-draft endpoint to return an error
        await page.route('**/content/create-draft', route => {
            route.fulfill({
                status: 500,
                contentType: 'application/json',
                body: JSON.stringify({ success: false, message: 'Mock error' })
            });
        });
        
        // Enter a title to trigger draft creation
        const titleField = page.locator('#title');
        await titleField.fill('Test Error Handling');
        
        // Wait for the error
        await page.waitForTimeout(3000);
        
        // Should show error status
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && (
                    status.textContent.includes('Failed') || 
                    status.classList.contains('error')
                );
            },
            { timeout: 10000 }
        );
        
        console.log('✓ Draft creation error handled gracefully');
    });

    test('should update form action after draft creation', async ({ page }) => {
        // Navigate to create page
        await page.goto('http://localhost:9000/admin/content/create?type=article');
        
        await page.waitForSelector('#contentForm', { timeout: 10000 });
        await page.waitForFunction(() => window.autoSave !== undefined, { timeout: 5000 });
        
        // Get initial form action
        const initialAction = await page.locator('#contentForm').getAttribute('action');
        expect(initialAction).toContain('/content/store');
        
        // Enter title to create draft
        const titleField = page.locator('#title');
        await titleField.fill('Test Form Action Update');
        
        // Wait for draft creation
        await page.waitForTimeout(3000);
        
        // Wait for success status
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && status.textContent.includes('Draft created');
            },
            { timeout: 10000 }
        );
        
        // Check that form action was updated
        const updatedAction = await page.locator('#contentForm').getAttribute('action');
        expect(updatedAction).toMatch(/\/content\/\d+\/update/);
        expect(updatedAction).not.toBe(initialAction);
        
        console.log('✓ Form action updated to edit mode after draft creation');
    });
});