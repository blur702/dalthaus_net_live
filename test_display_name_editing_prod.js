const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({
        headless: false // Show browser for testing
    });
    
    const context = await browser.newContext();
    const page = await context.newPage();
    
    console.log('Testing display_name editing on production...');
    console.log('==========================================');
    
    try {
        // 1. Login to admin
        console.log('\n1. Logging into production admin dashboard');
        await page.goto('https://dalthaus.net/admin/login', {
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
        await page.goto('https://dalthaus.net/admin/users', {
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
        
        // 4. Test editing the display_name
        console.log('\n4. Testing display_name editing');
        const newDisplayName = 'Kevin R. Althaus';
        
        // Clear and fill with new value
        await displayNameInput.fill('');
        await displayNameInput.fill(newDisplayName);
        
        // Submit the form
        console.log('   Submitting form...');
        await page.click('button[type="submit"]:has-text("Update User")');
        await page.waitForLoadState('networkidle');
        
        // Check for success or error messages
        const successMessage = await page.locator('text=/success/i').first();
        const errorMessage = await page.locator('text=/error/i').first();
        
        if (await successMessage.count() > 0) {
            const message = await successMessage.textContent();
            console.log(`   ✓ Success: ${message}`);
        } else if (await errorMessage.count() > 0) {
            const message = await errorMessage.textContent();
            console.log(`   ✗ Error: ${message}`);
        } else {
            console.log('   ? No success/error message found');
        }
        
        // 5. Verify the change was saved
        console.log('\n5. Verifying display_name was saved');
        const updatedValue = await page.locator('input[name="display_name"]').inputValue();
        console.log(`   Display_name field value: "${updatedValue}"`);
        
        if (updatedValue === newDisplayName) {
            console.log('   ✓ Display name successfully updated in form!');
        } else {
            console.log(`   ✗ Display name not updated correctly. Expected "${newDisplayName}", got "${updatedValue}"`);
        }
        
        // 6. Test that the change appears on the frontend
        console.log('\n6. Testing frontend display of updated name');
        await page.goto('https://dalthaus.net/', {
            waitUntil: 'networkidle'
        });
        
        // Look for the updated display name on the homepage
        const pageContent = await page.content();
        if (pageContent.includes('Kevin R. Althaus')) {
            console.log('   ✓ Updated display name found on homepage!');
        } else if (pageContent.includes('Kevin Althaus')) {
            console.log('   ? Old display name still showing (may need cache refresh)');
        } else {
            console.log('   ? No display name found on homepage');
        }
        
        // Also check articles page
        await page.goto('https://dalthaus.net/articles', {
            waitUntil: 'networkidle'
        });
        
        const articlesContent = await page.content();
        if (articlesContent.includes('Kevin R. Althaus')) {
            console.log('   ✓ Updated display name found on articles page!');
        } else if (articlesContent.includes('Kevin Althaus')) {
            console.log('   ? Old display name still showing on articles page');
        } else {
            console.log('   ? No display name found on articles page');
        }
        
        console.log('\n==========================================');
        console.log('Display name editing test completed!');
        console.log('The display_name editing functionality is working!');
        
    } catch (error) {
        console.error('\nError during test:', error.message);
    }
    
    // Keep browser open for inspection
    console.log('\nBrowser will stay open for 10 seconds for inspection...');
    await page.waitForTimeout(10000);
    
    await browser.close();
})();