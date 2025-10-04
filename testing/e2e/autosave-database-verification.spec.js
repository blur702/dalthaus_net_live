const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');

// Database configuration (production)
const dbConfig = {
    host: 'mi3-cl9-its2.a2hosting.com',
    port: 3306,
    user: 'dalthaus_maincms',
    password: 'f4!,Wpds=w6*=~+1',
    database: 'dalthaus_maincms'
};

test.describe('Auto-save Database Verification', () => {
    let dbConnection;

    test.beforeAll(async () => {
        // Create database connection
        try {
            dbConnection = await mysql.createConnection(dbConfig);
            console.log('✓ Database connection established');
        } catch (error) {
            console.log('✗ Database connection failed:', error.message);
            // Continue without DB connection for basic functionality testing
        }
    });

    test.afterAll(async () => {
        if (dbConnection) {
            await dbConnection.end();
        }
    });

    test('should create draft in database after title entry', async ({ page }) => {
        // Track console messages for debugging
        page.on('console', msg => {
            if (msg.type() === 'log') {
                console.log('Browser:', msg.text());
            } else if (msg.type() === 'error') {
                console.log('Browser Error:', msg.text());
            }
        });

        console.log('🔄 Starting admin login test...');

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

        // Step 6: Get initial record count if database is available
        let initialCount = 0;
        if (dbConnection) {
            const [rows] = await dbConnection.execute('SELECT COUNT(*) as count FROM content');
            initialCount = rows[0].count;
            console.log('✓ Initial content count:', initialCount);
        }

        // Step 7: Enter a unique title
        const testTitle = `Auto-save Test Article ${Date.now()}`;
        const titleField = page.locator('#title');
        await titleField.fill(testTitle);
        console.log('✓ Title entered:', testTitle);

        // Step 8: Wait for draft creation (2 second debounce + processing time)
        console.log('⏳ Waiting for draft creation...');
        await page.waitForTimeout(3000);

        // Step 9: Check for draft creation success message
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

        // Step 10: Verify auto-save state changed
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

        // Step 11: Verify form action was updated
        const formAction = await page.locator('#contentForm').getAttribute('action');
        expect(formAction).toMatch(/\/admin\/content\/\d+\/update/);
        console.log('✓ Form action updated to edit mode:', formAction);

        // Step 12: Verify URL alias was generated
        const urlAliasField = page.locator('#url_alias');
        const urlAlias = await urlAliasField.inputValue();
        expect(urlAlias).toBeTruthy();
        expect(urlAlias).toMatch(/^[a-z0-9\-]+$/);
        console.log('✓ URL alias generated:', urlAlias);

        // Step 13: Database verification
        if (dbConnection) {
            console.log('🔍 Verifying database record...');
            
            // Check that a new record was created
            const [countRows] = await dbConnection.execute('SELECT COUNT(*) as count FROM content');
            const newCount = countRows[0].count;
            expect(newCount).toBe(initialCount + 1);
            console.log('✓ New content record created in database');

            // Find the specific record
            const [contentRows] = await dbConnection.execute(
                'SELECT * FROM content WHERE title = ? ORDER BY content_id DESC LIMIT 1',
                [testTitle]
            );

            expect(contentRows.length).toBe(1);
            const contentRecord = contentRows[0];

            // Verify record details
            expect(contentRecord.title).toBe(testTitle);
            expect(contentRecord.url_alias).toBe(urlAlias);
            expect(contentRecord.content_type).toBe('article');
            expect(contentRecord.status).toBe('draft');
            expect(contentRecord.body).toBe('');
            expect(contentRecord.content_id).toBe(updatedAutoSaveState.contentId);

            console.log('✓ Database record verified:');
            console.log('  - ID:', contentRecord.content_id);
            console.log('  - Title:', contentRecord.title);
            console.log('  - URL Alias:', contentRecord.url_alias);
            console.log('  - Type:', contentRecord.content_type);
            console.log('  - Status:', contentRecord.status);
            console.log('  - Created:', contentRecord.created_at);

            // Clean up - delete the test record
            await dbConnection.execute('DELETE FROM content WHERE content_id = ?', [contentRecord.content_id]);
            console.log('✓ Test record cleaned up');
        } else {
            console.log('⚠️ Skipping database verification (no connection)');
        }

        // Step 14: Test continued auto-save functionality
        console.log('🔄 Testing continued auto-save...');
        
        const bodyField = page.locator('#body');
        await bodyField.fill('This is test body content for auto-save verification.');
        
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

        // Step 15: Verify persistence by refreshing
        console.log('🔄 Testing persistence after page refresh...');
        await page.reload();
        await page.waitForSelector('#title', { timeout: 10000 });
        
        const savedTitle = await titleField.inputValue();
        const savedBody = await bodyField.inputValue();
        
        expect(savedTitle).toBe(testTitle);
        expect(savedBody).toBe('This is test body content for auto-save verification.');
        console.log('✓ Content persisted after page reload');

        console.log('🎉 All auto-save tests passed successfully!');
    });

    test('should handle photobook creation with auto-save', async ({ page }) => {
        console.log('🔄 Testing photobook auto-save...');

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

        // Get initial count
        let initialCount = 0;
        if (dbConnection) {
            const [rows] = await dbConnection.execute('SELECT COUNT(*) as count FROM content WHERE content_type = "photobook"');
            initialCount = rows[0].count;
        }

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

        // Database verification
        if (dbConnection) {
            const [countRows] = await dbConnection.execute('SELECT COUNT(*) as count FROM content WHERE content_type = "photobook"');
            const newCount = countRows[0].count;
            expect(newCount).toBe(initialCount + 1);

            // Find and clean up the record
            const [rows] = await dbConnection.execute(
                'SELECT content_id FROM content WHERE title = ? AND content_type = "photobook"',
                [testTitle]
            );
            if (rows.length > 0) {
                await dbConnection.execute('DELETE FROM content WHERE content_id = ?', [rows[0].content_id]);
                console.log('✓ Photobook test record cleaned up');
            }
        }

        console.log('✓ Photobook auto-save test completed');
    });

    test('should not create draft without title', async ({ page }) => {
        console.log('🔄 Testing no draft creation without title...');

        // Login
        await page.goto('https://dalthaus.net/admin/login');
        await page.waitForSelector('input[name="username"]', { timeout: 10000 });
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard', { timeout: 15000 });

        // Navigate to create form
        await page.goto('https://dalthaus.net/admin/content/create?type=article');
        await page.waitForSelector('#contentForm', { timeout: 10000 });
        await page.waitForFunction(() => window.autoSave !== undefined, { timeout: 5000 });

        // Get initial count
        let initialCount = 0;
        if (dbConnection) {
            const [rows] = await dbConnection.execute('SELECT COUNT(*) as count FROM content');
            initialCount = rows[0].count;
        }

        // Enter body content without title
        await page.locator('#body').fill('This is body content without a title');
        await page.waitForTimeout(3000);

        // Verify no draft was created
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

        // Verify database count unchanged
        if (dbConnection) {
            const [countRows] = await dbConnection.execute('SELECT COUNT(*) as count FROM content');
            const newCount = countRows[0].count;
            expect(newCount).toBe(initialCount);
            console.log('✓ No database record created without title');
        }

        console.log('✓ Correctly prevented draft creation without title');
    });
});