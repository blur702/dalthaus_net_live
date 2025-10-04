const { test, expect } = require('@playwright/test');

test.describe('Auto-Save Feedback System Testing', () => {
    test('should demonstrate enhanced auto-save feedback and draft management', async ({ page }) => {
        console.log('🔄 Testing enhanced auto-save feedback system...');

        // Track console messages for debugging
        page.on('console', msg => {
            if (msg.type() === 'log') {
                console.log('Browser:', msg.text());
            } else if (msg.type() === 'error') {
                console.log('Browser Error:', msg.text());
            }
        });

        try {
            // Step 1: Navigate to admin portal
            await page.goto('https://dalthaus.net/admin-access.php');
            await page.waitForSelector('h1', { timeout: 10000 });
            console.log('✓ Accessed admin portal');

            // Step 2: Login using direct method
            await page.goto('https://dalthaus.net/?route=admin/login');
            
            // Wait for login form
            await page.waitForSelector('input[name="username"]', { timeout: 10000 });
            console.log('✓ Login form found');

            // Login
            await page.fill('input[name="username"]', 'kevin');
            await page.fill('input[name="password"]', '(130Bpm)');
            await page.click('button[type="submit"]');
            console.log('✓ Login submitted');

            // Wait for redirect
            await page.waitForURL('**/admin/dashboard', { timeout: 15000 });
            console.log('✓ Successfully logged into admin');

            // Step 3: Navigate to content management
            await page.goto('https://dalthaus.net/admin/content');
            await page.waitForSelector('h2', { timeout: 10000 });
            console.log('✓ Content management loaded');

            // Step 4: Check for View Drafts button
            const draftsButton = page.locator('a[href="/admin/content/drafts"]');
            const draftsButtonExists = await draftsButton.count();
            expect(draftsButtonExists).toBeGreaterThan(0);
            console.log('✓ View Drafts button found in navigation');

            // Step 5: Test draft management page
            await draftsButton.first().click();
            await page.waitForSelector('h2', { timeout: 10000 });
            
            const pageTitle = await page.locator('h2').first().textContent();
            expect(pageTitle).toContain('Draft Content');
            console.log('✓ Draft management page loaded');

            // Step 6: Navigate to create new article
            await page.goto('https://dalthaus.net/admin/content/create?type=article');
            await page.waitForSelector('#contentForm', { timeout: 10000 });
            console.log('✓ Create article form loaded');

            // Step 7: Check for auto-save script and status indicator
            const autoSaveStatus = await page.locator('#autosave-status').count();
            console.log('Auto-save status element count:', autoSaveStatus);

            // Step 8: Test enhanced auto-save feedback
            const testTitle = `Enhanced Auto-save Feedback Test ${Date.now()}`;
            const titleField = page.locator('#title');
            
            console.log('🔄 Entering title to trigger enhanced auto-save...');
            await titleField.fill(testTitle);

            // Step 9: Wait for enhanced status feedback
            console.log('⏳ Waiting for enhanced auto-save feedback...');
            
            // Wait for draft creation status
            await page.waitForFunction(
                () => {
                    const status = document.querySelector('#autosave-status');
                    return status && (
                        status.classList.contains('saving') ||
                        status.classList.contains('draft-created') ||
                        status.textContent.includes('Creating draft') ||
                        status.textContent.includes('Draft created')
                    );
                },
                { timeout: 15000 }
            );
            console.log('✓ Enhanced auto-save status feedback detected');

            // Step 10: Check for visual feedback elements
            const statusElement = page.locator('#autosave-status');
            const statusVisible = await statusElement.isVisible();
            expect(statusVisible).toBeTruthy();
            console.log('✓ Auto-save status indicator is visible');

            // Check for spinner or success state
            await page.waitForFunction(
                () => {
                    const status = document.querySelector('#autosave-status');
                    return status && (
                        status.querySelector('.spinner') || 
                        status.classList.contains('draft-created') ||
                        status.classList.contains('success')
                    );
                },
                { timeout: 10000 }
            );
            console.log('✓ Enhanced visual feedback (spinner or success state) confirmed');

            // Step 11: Test continued auto-save with feedback
            const bodyField = page.locator('#body');
            await bodyField.fill('Testing enhanced auto-save feedback with visual indicators.');
            
            console.log('⏳ Testing continued auto-save feedback...');
            
            // Wait for saving feedback
            await page.waitForFunction(
                () => {
                    const status = document.querySelector('#autosave-status');
                    return status && (
                        status.classList.contains('saving') ||
                        status.querySelector('.spinner') ||
                        status.textContent.includes('Saving')
                    );
                },
                { timeout: 15000 }
            );
            console.log('✓ Saving feedback with visual indicators confirmed');

            // Wait for save completion
            await page.waitForFunction(
                () => {
                    const status = document.querySelector('#autosave-status');
                    return status && (
                        status.classList.contains('success') ||
                        status.textContent.includes('Saved at')
                    );
                },
                { timeout: 10000 }
            );
            console.log('✓ Save completion feedback confirmed');

            // Step 12: Verify draft appears in draft management
            console.log('🔄 Verifying draft appears in draft management...');
            await page.goto('https://dalthaus.net/admin/content/drafts');
            await page.waitForSelector('h2', { timeout: 10000 });

            // Look for our test title in the drafts list
            const draftExists = await page.evaluate((title) => {
                const rows = document.querySelectorAll('.content-item');
                for (let row of rows) {
                    if (row.textContent.includes(title)) {
                        return true;
                    }
                }
                return false;
            }, testTitle);

            expect(draftExists).toBeTruthy();
            console.log('✓ Draft appears in draft management list');

            // Step 13: Test continue editing functionality
            console.log('🔄 Testing continue editing functionality...');
            
            const continueEditingLink = page.locator('a:has-text("Continue Editing")').first();
            await continueEditingLink.click();
            
            await page.waitForSelector('#contentForm', { timeout: 10000 });
            const editFormTitle = await page.locator('#title').inputValue();
            expect(editFormTitle).toBe(testTitle);
            console.log('✓ Continue editing functionality works - draft content preserved');

            // Step 14: Clean up test data
            console.log('🔄 Cleaning up test data...');
            await page.goto('https://dalthaus.net/admin/content/drafts');
            
            const deleteSuccess = await page.evaluate((title) => {
                const rows = document.querySelectorAll('.content-item');
                for (let row of rows) {
                    if (row.textContent.includes(title)) {
                        const deleteButton = row.querySelector('button[type="submit"]');
                        if (deleteButton) {
                            deleteButton.click();
                            return true;
                        }
                    }
                }
                return false;
            }, testTitle);

            if (deleteSuccess) {
                // Handle confirmation dialog
                page.on('dialog', dialog => dialog.accept());
                await page.waitForTimeout(1000);
                console.log('✓ Test draft deleted');
            }

            console.log('🎉 Enhanced auto-save feedback system test completed successfully!');

        } catch (error) {
            console.error('❌ Auto-save feedback test failed:', error.message);
            
            // Take screenshot for debugging
            await page.screenshot({ path: 'testing/results/autosave-feedback-error.png' });
            console.log('Screenshot saved: testing/results/autosave-feedback-error.png');
            
            throw error;
        }
    });

    test('should test photobook auto-save feedback', async ({ page }) => {
        console.log('🔄 Testing photobook auto-save feedback...');

        try {
            // Login
            await page.goto('https://dalthaus.net/?route=admin/login');
            await page.waitForSelector('input[name="username"]', { timeout: 10000 });
            await page.fill('input[name="username"]', 'kevin');
            await page.fill('input[name="password"]', '(130Bpm)');
            await page.click('button[type="submit"]');
            await page.waitForURL('**/admin/dashboard', { timeout: 15000 });

            // Navigate to create photobook
            await page.goto('https://dalthaus.net/admin/content/create?type=photobook');
            await page.waitForSelector('#contentForm', { timeout: 10000 });

            // Test photobook auto-save feedback
            const testTitle = `Photobook Feedback Test ${Date.now()}`;
            await page.locator('#title').fill(testTitle);

            // Wait for draft creation feedback
            await page.waitForFunction(
                () => {
                    const status = document.querySelector('#autosave-status');
                    return status && status.textContent.includes('Draft created');
                },
                { timeout: 15000 }
            );
            console.log('✓ Photobook auto-save feedback working');

            // Verify content type preservation
            const contentType = await page.locator('input[name="content_type"]').inputValue();
            expect(contentType).toBe('photobook');
            console.log('✓ Photobook content type preserved with auto-save');

        } catch (error) {
            console.error('❌ Photobook feedback test failed:', error.message);
            throw error;
        }
    });
});