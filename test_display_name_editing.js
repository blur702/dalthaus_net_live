const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({
        headless: false // Show browser for testing
    });
    
    const context = await browser.newContext();
    const page = await context.newPage();
    
    console.log('Testing display_name editing functionality...');
    console.log('==========================================');
    
    try {
        // 1. Login to admin
        console.log('\n1. Logging into admin dashboard');
        await page.goto('http://localhost:8000/admin/login', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        
        // Fill login form
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        
        // Wait for dashboard
        await page.waitForLoadState('networkidle');
        
        if (page.url().includes('/admin/dashboard')) {
            console.log('   ✓ Login successful');
        } else {
            console.log('   ✗ Login failed');
            return;
        }
        
        // 2. Navigate to users page
        console.log('\n2. Navigating to users management');
        await page.goto('http://localhost:8000/admin/users', {
            waitUntil: 'networkidle'
        });
        
        // Find the kevin user and click edit
        const editLinks = await page.locator('a:has-text("Edit")').all();
        if (editLinks.length > 0) {
            await editLinks[0].click();
            await page.waitForLoadState('networkidle');
            console.log('   ✓ Opened user edit form');
        } else {
            console.log('   ✗ No edit links found');
            return;
        }
        
        // 3. Check current display_name value
        console.log('\n3. Checking current display_name field');
        const displayNameInput = await page.locator('input[name="display_name"]');
        const currentValue = await displayNameInput.inputValue();
        console.log(`   Current display_name: "${currentValue}"`);
        
        // 4. Update display_name
        console.log('\n4. Updating display_name to "Kevin R. Althaus"');
        await displayNameInput.fill('Kevin R. Althaus');
        
        // Submit the form
        await page.click('button[type="submit"]:has-text("Update User")');
        await page.waitForLoadState('networkidle');
        
        // Check for success message
        const successMessage = await page.locator('.flash, .alert-success, text=/success/i').first();
        if (await successMessage.count() > 0) {
            const message = await successMessage.textContent();
            console.log(`   ✓ Success: ${message}`);
        } else {
            console.log('   ? No success message found');
        }
        
        // 5. Verify the change was saved
        console.log('\n5. Verifying display_name was saved');
        const updatedValue = await page.locator('input[name="display_name"]').inputValue();
        console.log(`   Updated display_name: "${updatedValue}"`);
        
        if (updatedValue === 'Kevin R. Althaus') {
            console.log('   ✓ Display name successfully updated!');
        } else {
            console.log(`   ✗ Display name not updated correctly. Expected "Kevin R. Althaus", got "${updatedValue}"`);
        }
        
        // 6. Test creating a new user with display_name
        console.log('\n6. Testing user creation with display_name');
        await page.goto('http://localhost:8000/admin/users/create', {
            waitUntil: 'networkidle'
        });
        
        // Fill new user form
        await page.fill('input[name="username"]', 'testuser');
        await page.fill('input[name="display_name"]', 'Test User Display');
        await page.fill('input[name="email"]', 'test@example.com');
        await page.fill('input[name="password"]', 'TestPassword123');
        await page.fill('input[name="confirm_password"]', 'TestPassword123');
        
        await page.click('button[type="submit"]:has-text("Create User")');
        await page.waitForLoadState('networkidle');
        
        // Check if we were redirected to edit page (successful creation)
        if (page.url().includes('/admin/users/') && page.url().includes('/edit')) {
            console.log('   ✓ New user created successfully');
            
            // Check display_name is populated
            const newUserDisplayName = await page.locator('input[name="display_name"]').inputValue();
            console.log(`   New user display_name: "${newUserDisplayName}"`);
            
            if (newUserDisplayName === 'Test User Display') {
                console.log('   ✓ Display name correctly set for new user!');
            } else {
                console.log(`   ✗ Display name incorrect. Expected "Test User Display", got "${newUserDisplayName}"`);
            }
        } else {
            console.log('   ✗ User creation failed or validation errors');
            
            // Check for any error messages
            const errorMessages = await page.locator('.error, .alert-danger, .text-red-600').all();
            for (let error of errorMessages) {
                const errorText = await error.textContent();
                if (errorText.trim()) {
                    console.log(`   Error: ${errorText.trim()}`);
                }
            }
        }
        
        console.log('\n==========================================');
        console.log('Display name editing test completed!');
        
    } catch (error) {
        console.error('\nError during test:', error.message);
    }
    
    // Keep browser open for inspection
    console.log('\nBrowser will stay open for 10 seconds for inspection...');
    await page.waitForTimeout(10000);
    
    await browser.close();
})();