const { test, expect } = require('@playwright/test');

test.describe('Auto-save functionality', () => {
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

    test('should show auto-save status indicator on edit pages', async ({ page }) => {
        // Navigate to content management
        await page.goto('http://localhost:9000/admin/content');
        
        // Wait for content to load
        await page.waitForSelector('.content-item', { timeout: 10000 });
        
        // Find and click first edit button
        const editButton = page.locator('a[href*="/edit"]').first();
        await editButton.click();
        
        // Wait for edit page to load
        await page.waitForSelector('#contentForm', { timeout: 10000 });
        
        // Check that auto-save JavaScript is loaded
        await page.waitForFunction(() => window.autoSave !== undefined, { timeout: 5000 });
        
        // Check that status indicator exists (might be hidden initially)
        const statusIndicator = page.locator('#autosave-status');
        await expect(statusIndicator).toBeAttached();
        
        console.log('✓ Auto-save status indicator is present');
    });

    test('should auto-save title changes', async ({ page }) => {
        // Navigate to content management and edit first item
        await page.goto('http://localhost:9000/admin/content');
        await page.waitForSelector('.content-item', { timeout: 10000 });
        
        const editButton = page.locator('a[href*="/edit"]').first();
        await editButton.click();
        
        // Wait for edit page to load
        await page.waitForSelector('#contentForm', { timeout: 10000 });
        await page.waitForFunction(() => window.autoSave !== undefined, { timeout: 5000 });
        
        // Get original title
        const titleField = page.locator('#title');
        const originalTitle = await titleField.inputValue();
        
        // Modify title
        const testSuffix = ' - Auto-save Test';
        await titleField.fill(originalTitle + testSuffix);
        
        // Wait for auto-save to trigger (2 seconds debounce)
        await page.waitForTimeout(2500);
        
        // Check if status indicator shows saving
        const statusIndicator = page.locator('#autosave-status');
        
        // Wait for save completion status
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && status.textContent.includes('Saved at');
            },
            { timeout: 10000 }
        );
        
        console.log('✓ Auto-save triggered for title change');
        
        // Verify the save by refreshing page and checking if title persists
        await page.reload();
        await page.waitForSelector('#title', { timeout: 10000 });
        
        const savedTitle = await titleField.inputValue();
        expect(savedTitle).toBe(originalTitle + testSuffix);
        
        console.log('✓ Title change was persisted after auto-save');
        
        // Restore original title
        await titleField.fill(originalTitle);
        await page.waitForTimeout(2500);
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && status.textContent.includes('Saved at');
            },
            { timeout: 10000 }
        );
    });

    test('should auto-save body content changes', async ({ page }) => {
        // Navigate to content management and edit first item
        await page.goto('http://localhost:9000/admin/content');
        await page.waitForSelector('.content-item', { timeout: 10000 });
        
        const editButton = page.locator('a[href*="/edit"]').first();
        await editButton.click();
        
        // Wait for edit page to load
        await page.waitForSelector('#contentForm', { timeout: 10000 });
        await page.waitForFunction(() => window.autoSave !== undefined, { timeout: 5000 });
        
        // Get original body content
        const bodyField = page.locator('#body');
        const originalBody = await bodyField.inputValue();
        
        // Modify body content
        const testContent = '\n\n--- Auto-save test content ---';
        await bodyField.fill(originalBody + testContent);
        
        // Wait for auto-save to trigger
        await page.waitForTimeout(2500);
        
        // Wait for save completion
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && status.textContent.includes('Saved at');
            },
            { timeout: 10000 }
        );
        
        console.log('✓ Auto-save triggered for body content change');
        
        // Verify persistence by refreshing
        await page.reload();
        await page.waitForSelector('#body', { timeout: 10000 });
        
        const savedBody = await bodyField.inputValue();
        expect(savedBody).toBe(originalBody + testContent);
        
        console.log('✓ Body content change was persisted after auto-save');
        
        // Restore original content
        await bodyField.fill(originalBody);
        await page.waitForTimeout(2500);
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && status.textContent.includes('Saved at');
            },
            { timeout: 10000 }
        );
    });

    test('should auto-save teaser changes', async ({ page }) => {
        // Navigate to content management and edit first item
        await page.goto('http://localhost:9000/admin/content');
        await page.waitForSelector('.content-item', { timeout: 10000 });
        
        const editButton = page.locator('a[href*="/edit"]').first();
        await editButton.click();
        
        // Wait for edit page to load
        await page.waitForSelector('#contentForm', { timeout: 10000 });
        await page.waitForFunction(() => window.autoSave !== undefined, { timeout: 5000 });
        
        // Get original teaser
        const teaserField = page.locator('#teaser');
        const originalTeaser = await teaserField.inputValue();
        
        // Modify teaser
        const testTeaser = 'Auto-save test teaser content.';
        await teaserField.fill(originalTeaser + ' ' + testTeaser);
        
        // Wait for auto-save to trigger
        await page.waitForTimeout(2500);
        
        // Wait for save completion
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && status.textContent.includes('Saved at');
            },
            { timeout: 10000 }
        );
        
        console.log('✓ Auto-save triggered for teaser change');
        
        // Verify persistence
        await page.reload();
        await page.waitForSelector('#teaser', { timeout: 10000 });
        
        const savedTeaser = await teaserField.inputValue();
        expect(savedTeaser).toBe(originalTeaser + ' ' + testTeaser);
        
        console.log('✓ Teaser change was persisted after auto-save');
        
        // Restore original teaser
        await teaserField.fill(originalTeaser);
        await page.waitForTimeout(2500);
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && status.textContent.includes('Saved at');
            },
            { timeout: 10000 }
        );
    });

    test('should work for both article and photobook content types', async ({ page }) => {
        // Test articles
        await page.goto('http://localhost:9000/admin/content?type=article');
        await page.waitForSelector('.content-item', { timeout: 10000 });
        
        let editButton = page.locator('a[href*="/edit"]').first();
        if (await editButton.count() > 0) {
            await editButton.click();
            await page.waitForSelector('#contentForm', { timeout: 10000 });
            await page.waitForFunction(() => window.autoSave !== undefined, { timeout: 5000 });
            console.log('✓ Auto-save loaded for article edit page');
        }
        
        // Test photobooks
        await page.goto('http://localhost:9000/admin/content?type=photobook');
        await page.waitForSelector('.content-item', { timeout: 10000 });
        
        editButton = page.locator('a[href*="/edit"]').first();
        if (await editButton.count() > 0) {
            await editButton.click();
            await page.waitForSelector('#contentForm', { timeout: 10000 });
            await page.waitForFunction(() => window.autoSave !== undefined, { timeout: 5000 });
            console.log('✓ Auto-save loaded for photobook edit page');
        }
    });

    test('should not enable auto-save on create pages', async ({ page }) => {
        // Navigate to create article page
        await page.goto('http://localhost:9000/admin/content/create?type=article');
        await page.waitForSelector('#contentForm', { timeout: 10000 });
        
        // Wait a moment for auto-save to potentially initialize
        await page.waitForTimeout(1000);
        
        // Check that auto-save is not enabled (no content ID)
        const autoSaveEnabled = await page.evaluate(() => {
            return window.autoSave && window.autoSave.isEnabled;
        });
        
        expect(autoSaveEnabled).toBeFalsy();
        console.log('✓ Auto-save correctly disabled on create pages');
    });

    test('should handle save errors gracefully', async ({ page }) => {
        // Navigate to edit page
        await page.goto('http://localhost:9000/admin/content');
        await page.waitForSelector('.content-item', { timeout: 10000 });
        
        const editButton = page.locator('a[href*="/edit"]').first();
        await editButton.click();
        
        await page.waitForSelector('#contentForm', { timeout: 10000 });
        await page.waitForFunction(() => window.autoSave !== undefined, { timeout: 5000 });
        
        // Mock a failed auto-save request
        await page.route('**/content/autosave', route => {
            route.fulfill({
                status: 500,
                contentType: 'application/json',
                body: JSON.stringify({ success: false, message: 'Mock error' })
            });
        });
        
        // Trigger a change
        const titleField = page.locator('#title');
        await titleField.fill(await titleField.inputValue() + ' - Error Test');
        
        // Wait for auto-save attempt
        await page.waitForTimeout(2500);
        
        // Check that error status is shown
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && (
                    status.textContent.includes('failed') || 
                    status.classList.contains('error')
                );
            },
            { timeout: 10000 }
        );
        
        console.log('✓ Auto-save error handling works correctly');
    });
});