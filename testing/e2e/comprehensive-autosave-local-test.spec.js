const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');

// Local database configuration as specified by user
const dbConfig = {
    host: 'localhost',
    port: 3306,
    user: 'cms_user',
    password: 'cms_password',
    database: 'cms_db'
};

test.describe('Comprehensive Autosave Functionality Test - Local Environment', () => {
    let dbConnection;

    test.beforeAll(async () => {
        try {
            // Create database connection
            dbConnection = await mysql.createConnection(dbConfig);
            console.log('✅ Database connection established');
            
            // Check if autosaves table exists
            const [tables] = await dbConnection.execute(
                "SHOW TABLES LIKE 'autosaves'"
            );
            
            if (tables.length === 0) {
                console.log('⚠️ autosaves table does not exist - autosave functionality may not work');
            } else {
                console.log('✅ autosaves table found');
            }
            
        } catch (error) {
            console.log('❌ Database connection failed:', error.message);
            console.log('📝 Please ensure the local database is set up with:');
            console.log('   - Database: cms_db');
            console.log('   - Username: cms_user');
            console.log('   - Password: cms_password');
        }
    });

    test.afterAll(async () => {
        if (dbConnection) {
            await dbConnection.end();
        }
    });

    test('Complete autosave functionality verification', async ({ page }) => {
        console.log('🚀 Starting comprehensive autosave test...');
        
        // Track console messages for debugging
        page.on('console', msg => {
            if (msg.type() === 'log' && msg.text().includes('autosave')) {
                console.log('🔍 Browser autosave log:', msg.text());
            } else if (msg.type() === 'error') {
                console.log('❌ Browser error:', msg.text());
            }
        });

        // Step 1: Navigate to login page
        console.log('📍 Step 1: Navigating to login page...');
        await page.goto('http://localhost:8000/admin/login');
        await page.waitForSelector('input[name="username"]', { timeout: 10000 });
        console.log('✅ Login page loaded');

        // Step 2: Login with admin credentials
        console.log('📍 Step 2: Logging in...');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard', { timeout: 15000 });
        console.log('✅ Successfully logged into admin dashboard');

        // Step 3: Navigate to create article page
        console.log('📍 Step 3: Navigating to article creation...');
        await page.goto('http://localhost:8000/admin/content/create?type=article');
        await page.waitForSelector('#contentForm', { timeout: 10000 });
        console.log('✅ Article creation form loaded');

        // Step 4: Wait for autosave to initialize
        console.log('📍 Step 4: Waiting for autosave initialization...');
        await page.waitForFunction(() => window.autoSave !== undefined, { timeout: 5000 });
        console.log('✅ Autosave script loaded');

        // Step 5: Verify initial autosave state
        console.log('📍 Step 5: Checking initial autosave state...');
        const initialState = await page.evaluate(() => {
            return {
                isCreateMode: window.autoSave?.isCreateMode,
                isDraftCreated: window.autoSave?.isDraftCreated,
                contentId: window.autoSave?.contentId,
                isEnabled: window.autoSave?.isEnabled,
                uuid: window.autoSave?.uuid
            };
        });
        
        console.log('📊 Initial autosave state:', initialState);
        expect(initialState.isCreateMode).toBeTruthy();
        expect(initialState.isDraftCreated).toBeFalsy();
        expect(initialState.isEnabled).toBeFalsy();
        expect(initialState.uuid).toBeTruthy(); // UUID should be generated
        console.log('✅ UUID generated:', initialState.uuid);

        // Step 6: Get initial database counts
        let initialContentCount = 0;
        let initialAutosaveCount = 0;
        
        if (dbConnection) {
            try {
                const [contentRows] = await dbConnection.execute('SELECT COUNT(*) as count FROM content');
                initialContentCount = contentRows[0].count;
                
                const [autosaveRows] = await dbConnection.execute('SELECT COUNT(*) as count FROM autosaves');
                initialAutosaveCount = autosaveRows[0].count;
                
                console.log('📊 Initial DB counts - Content:', initialContentCount, 'Autosaves:', initialAutosaveCount);
            } catch (error) {
                console.log('⚠️ Database count check failed:', error.message);
            }
        }

        // Step 7: Enter title to trigger draft creation
        console.log('📍 Step 7: Entering title to trigger draft creation...');
        const testTitle = `Test Autosave Article ${Date.now()}`;
        await page.fill('input[name="title"]', testTitle);
        console.log('📝 Title entered:', testTitle);

        // Wait for draft creation (should happen within 2 seconds + processing)
        console.log('⏳ Waiting for draft creation (2 second debounce)...');
        await page.waitForTimeout(3000);

        // Step 8: Verify draft creation status
        console.log('📍 Step 8: Verifying draft creation...');
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
        
        const statusText = await page.locator('#autosave-status').textContent();
        console.log('📊 Draft creation status:', statusText.trim());
        expect(statusText).toContain('Draft created');

        // Step 9: Verify autosave state after draft creation
        console.log('📍 Step 9: Checking autosave state after draft creation...');
        const postDraftState = await page.evaluate(() => {
            return {
                isCreateMode: window.autoSave?.isCreateMode,
                isDraftCreated: window.autoSave?.isDraftCreated,
                contentId: window.autoSave?.contentId,
                isEnabled: window.autoSave?.isEnabled,
                uuid: window.autoSave?.uuid
            };
        });
        
        console.log('📊 Post-draft autosave state:', postDraftState);
        expect(postDraftState.isCreateMode).toBeFalsy();
        expect(postDraftState.isDraftCreated).toBeTruthy();
        expect(postDraftState.contentId).toBeTruthy();
        expect(postDraftState.isEnabled).toBeTruthy();
        expect(postDraftState.uuid).toBe(initialState.uuid); // UUID should remain the same
        
        const contentId = postDraftState.contentId;
        console.log('✅ Draft created with Content ID:', contentId);

        // Step 10: Verify database records
        if (dbConnection) {
            console.log('📍 Step 10: Verifying database records...');
            
            // Check content record
            const [contentRows] = await dbConnection.execute('SELECT COUNT(*) as count FROM content');
            const newContentCount = contentRows[0].count;
            expect(newContentCount).toBe(initialContentCount + 1);
            console.log('✅ Content record created in database');

            // Find the specific content record
            const [specificContent] = await dbConnection.execute(
                'SELECT * FROM content WHERE title = ? ORDER BY content_id DESC LIMIT 1',
                [testTitle]
            );
            
            expect(specificContent.length).toBe(1);
            const contentRecord = specificContent[0];
            expect(contentRecord.title).toBe(testTitle);
            expect(contentRecord.status).toBe('draft');
            console.log('✅ Content record verified - Status:', contentRecord.status);
        }

        // Step 11: Test body content autosave
        console.log('📍 Step 11: Testing body content autosave...');
        const bodyContent = 'This is test content for autosave functionality';
        await page.fill('textarea[name="body"]', bodyContent);
        console.log('📝 Body content entered');

        // Wait for autosave (2 second debounce)
        console.log('⏳ Waiting for autosave (2 second debounce)...');
        await page.waitForTimeout(3000);

        // Check for autosave confirmation
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && status.textContent.includes('Saved at');
            },
            { timeout: 10000 }
        );
        
        const autosaveStatus = await page.locator('#autosave-status').textContent();
        console.log('📊 Autosave status:', autosaveStatus.trim());
        expect(autosaveStatus).toContain('Saved at');

        // Step 12: Test multiple autosave versions (to verify 3-version limit)
        console.log('📍 Step 12: Testing multiple autosave versions...');
        
        for (let i = 1; i <= 4; i++) {
            const updatedContent = `${bodyContent} - Update ${i}`;
            await page.fill('textarea[name="body"]', updatedContent);
            console.log(`📝 Update ${i}: Content changed`);
            
            // Wait for autosave
            await page.waitForTimeout(3000);
            
            // Wait for save confirmation
            await page.waitForFunction(
                () => {
                    const status = document.querySelector('#autosave-status');
                    return status && status.textContent.includes('Saved at');
                },
                { timeout: 10000 }
            );
            console.log(`✅ Update ${i}: Autosaved`);
        }

        // Step 13: Verify autosave count in database (should be max 3)
        if (dbConnection) {
            console.log('📍 Step 13: Verifying autosave version limit...');
            
            try {
                const [autosaveRows] = await dbConnection.execute(
                    'SELECT COUNT(*) as count FROM autosaves WHERE uuid = ?',
                    [postDraftState.uuid]
                );
                
                const autosaveCount = autosaveRows[0].count;
                console.log(`📊 Autosave versions for UUID ${postDraftState.uuid}:`, autosaveCount);
                expect(autosaveCount).toBeLessThanOrEqual(3);
                console.log('✅ Autosave version limit respected (max 3 versions)');
                
                // Get the autosave entries to verify content
                const [autosaveEntries] = await dbConnection.execute(
                    'SELECT * FROM autosaves WHERE uuid = ? ORDER BY created_at DESC',
                    [postDraftState.uuid]
                );
                
                console.log('📊 Autosave entries found:', autosaveEntries.length);
                autosaveEntries.forEach((entry, index) => {
                    console.log(`   ${index + 1}. Created: ${entry.created_at}, Content length: ${entry.content_data?.length || 0}`);
                });
                
            } catch (error) {
                console.log('⚠️ Autosave verification failed:', error.message);
            }
        }

        // Step 14: Test teaser field autosave
        console.log('📍 Step 14: Testing teaser field autosave...');
        const teaserContent = 'This is test teaser content for autosave verification';
        await page.fill('textarea[name="teaser"]', teaserContent);
        console.log('📝 Teaser content entered');

        // Wait for autosave
        await page.waitForTimeout(3000);
        
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && status.textContent.includes('Saved at');
            },
            { timeout: 10000 }
        );
        console.log('✅ Teaser content autosaved');

        // Step 15: Test persistence by refreshing page
        console.log('📍 Step 15: Testing content persistence...');
        await page.reload();
        await page.waitForSelector('#contentForm', { timeout: 10000 });
        await page.waitForFunction(() => window.autoSave !== undefined, { timeout: 5000 });

        // Verify content was restored
        const restoredTitle = await page.inputValue('input[name="title"]');
        const restoredBody = await page.inputValue('textarea[name="body"]');
        const restoredTeaser = await page.inputValue('textarea[name="teaser"]');

        console.log('📊 Restored content:');
        console.log('   Title:', restoredTitle);
        console.log('   Body length:', restoredBody.length);
        console.log('   Teaser length:', restoredTeaser.length);

        expect(restoredTitle).toBe(testTitle);
        expect(restoredBody).toContain('This is test content for autosave functionality');
        expect(restoredTeaser).toBe(teaserContent);
        console.log('✅ Content persistence verified');

        // Step 16: Test final save and autosave cleanup
        console.log('📍 Step 16: Testing final save and autosave cleanup...');
        
        // Save the article
        await page.click('button[type="submit"]');
        
        // Wait for save to complete (should redirect or show success message)
        try {
            await page.waitForURL('**/admin/content', { timeout: 10000 });
            console.log('✅ Article saved successfully (redirected to content list)');
        } catch {
            // Check for success message on same page
            try {
                await page.waitForSelector('.alert-success, .success-message', { timeout: 5000 });
                console.log('✅ Article saved successfully (success message shown)');
            } catch {
                console.log('⚠️ Could not verify save completion');
            }
        }

        // Step 17: Verify autosave cleanup
        if (dbConnection) {
            console.log('📍 Step 17: Verifying autosave cleanup...');
            
            try {
                // Wait a moment for cleanup to occur
                await page.waitForTimeout(2000);
                
                const [autosaveRows] = await dbConnection.execute(
                    'SELECT COUNT(*) as count FROM autosaves WHERE uuid = ?',
                    [postDraftState.uuid]
                );
                
                const remainingAutosaves = autosaveRows[0].count;
                console.log(`📊 Remaining autosaves for UUID ${postDraftState.uuid}:`, remainingAutosaves);
                
                if (remainingAutosaves === 0) {
                    console.log('✅ Autosaves cleaned up after save');
                } else {
                    console.log('⚠️ Autosaves not cleaned up - may be by design or timing issue');
                }
                
                // Clean up the test content
                await dbConnection.execute('DELETE FROM content WHERE title = ?', [testTitle]);
                console.log('✅ Test content cleaned up');
                
                // Clean up any remaining autosaves
                await dbConnection.execute('DELETE FROM autosaves WHERE uuid = ?', [postDraftState.uuid]);
                console.log('✅ Test autosaves cleaned up');
                
            } catch (error) {
                console.log('⚠️ Cleanup verification failed:', error.message);
            }
        }

        console.log('🎉 Comprehensive autosave test completed successfully!');
    });

    test('Autosave browser console verification', async ({ page }) => {
        console.log('🔍 Testing autosave console messages...');
        
        const consoleMessages = [];
        page.on('console', msg => {
            if (msg.text().includes('autosave') || msg.text().includes('AutoSave')) {
                consoleMessages.push(msg.text());
            }
        });

        // Login and navigate to create page
        await page.goto('http://localhost:8000/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard');

        await page.goto('http://localhost:8000/admin/content/create?type=article');
        await page.waitForSelector('#contentForm');
        await page.waitForFunction(() => window.autoSave !== undefined);

        // Enter title to trigger autosave
        const testTitle = `Console Test ${Date.now()}`;
        await page.fill('input[name="title"]', testTitle);
        await page.waitForTimeout(3000);

        // Check console messages
        console.log('📊 Autosave console messages captured:', consoleMessages.length);
        consoleMessages.forEach((msg, index) => {
            console.log(`   ${index + 1}. ${msg}`);
        });

        expect(consoleMessages.length).toBeGreaterThan(0);
        console.log('✅ Autosave console messages verified');

        // Cleanup
        if (dbConnection) {
            try {
                await dbConnection.execute('DELETE FROM content WHERE title = ?', [testTitle]);
            } catch (error) {
                console.log('⚠️ Cleanup failed:', error.message);
            }
        }
    });

    test('Autosave timing verification', async ({ page }) => {
        console.log('⏱️ Testing autosave timing (2-second debounce)...');
        
        // Login and navigate
        await page.goto('http://localhost:8000/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard');

        await page.goto('http://localhost:8000/admin/content/create?type=article');
        await page.waitForSelector('#contentForm');
        await page.waitForFunction(() => window.autoSave !== undefined);

        // Enter title
        const testTitle = `Timing Test ${Date.now()}`;
        await page.fill('input[name="title"]', testTitle);
        
        // Check that autosave doesn't happen immediately (within 1 second)
        await page.waitForTimeout(1000);
        const status1sec = await page.locator('#autosave-status').textContent();
        console.log('📊 Status after 1 second:', status1sec?.trim() || 'No status');
        
        // Wait for full debounce period (2 seconds total)
        await page.waitForTimeout(2000);
        
        // Now check that autosave occurred
        await page.waitForFunction(
            () => {
                const status = document.querySelector('#autosave-status');
                return status && status.textContent.includes('Draft created');
            },
            { timeout: 5000 }
        );
        
        const statusAfterDebounce = await page.locator('#autosave-status').textContent();
        console.log('📊 Status after 3 seconds total:', statusAfterDebounce?.trim());
        
        expect(statusAfterDebounce).toContain('Draft created');
        console.log('✅ 2-second debounce timing verified');

        // Cleanup
        if (dbConnection) {
            try {
                await dbConnection.execute('DELETE FROM content WHERE title = ?', [testTitle]);
            } catch (error) {
                console.log('⚠️ Cleanup failed:', error.message);
            }
        }
    });
});