const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({
        headless: false // Show browser to see what's happening
    });
    
    const context = await browser.newContext();
    const page = await context.newPage();
    
    console.log('Debugging user display name validation errors...');
    console.log('==========================================');
    
    try {
        // 1. Login to admin
        console.log('\n1. Logging into admin');
        await page.goto('https://dalthaus.net/admin/login', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        
        console.log('   ✓ Login successful');
        
        // 2. Navigate to users page and find a user to edit
        console.log('\n2. Navigating to user edit');
        await page.goto('https://dalthaus.net/admin/users', {
            waitUntil: 'networkidle'
        });
        
        // Find the first edit link
        const editLinks = await page.locator('a:has-text("Edit")').all();
        if (editLinks.length > 0) {
            await editLinks[0].click();
            await page.waitForLoadState('networkidle');
            console.log('   ✓ Opened user edit form');
        } else {
            console.log('   ✗ No edit links found');
            return;
        }
        
        // 3. Get current form values
        console.log('\n3. Current form values');
        const currentUsername = await page.locator('input[name="username"]').inputValue();
        const currentDisplayName = await page.locator('input[name="display_name"]').inputValue();
        const currentEmail = await page.locator('input[name="email"]').inputValue();
        
        console.log(`   Username: "${currentUsername}"`);
        console.log(`   Display Name: "${currentDisplayName}"`);
        console.log(`   Email: "${currentEmail}"`);
        
        // 4. Test 1: Try updating display name only
        console.log('\n4. TEST 1: Update display name only');
        await page.locator('input[name="display_name"]').fill('Test Display Name Update');
        
        console.log('   Submitting form...');
        await page.click('button[type="submit"]:has-text("Update User")');
        await page.waitForLoadState('networkidle');
        
        // Check for error messages
        const errorElements = await page.locator('.error, .alert-danger, .text-red-600, text=/error/i').all();
        if (errorElements.length > 0) {
            console.log('   ✗ Validation errors found:');
            for (let error of errorElements) {
                const errorText = await error.textContent();
                if (errorText.trim()) {
                    console.log(`     - ${errorText.trim()}`);
                }
            }
        } else {
            console.log('   ✓ No validation errors found');
        }
        
        // Check for success message
        const successElements = await page.locator('text=/success/i, .alert-success, .text-green-600').all();
        if (successElements.length > 0) {
            console.log('   ✓ Success message found:');
            for (let success of successElements) {
                const successText = await success.textContent();
                if (successText.trim()) {
                    console.log(`     - ${successText.trim()}`);
                }
            }
        }
        
        // 5. Check current URL to see if we stayed on edit page or were redirected
        const currentUrl = page.url();
        console.log(`   Current URL: ${currentUrl}`);
        
        // 6. Test 2: Check form validation by submitting empty display name
        console.log('\n5. TEST 2: Try empty display name (should trigger validation)');
        await page.locator('input[name="display_name"]').fill('');
        
        console.log('   Submitting form with empty display name...');
        await page.click('button[type="submit"]:has-text("Update User")');
        await page.waitForLoadState('networkidle');
        
        // Check for validation errors
        const validationErrors = await page.locator('.error, .alert-danger, .text-red-600').all();
        if (validationErrors.length > 0) {
            console.log('   ✓ Expected validation errors found:');
            for (let error of validationErrors) {
                const errorText = await error.textContent();
                if (errorText.trim()) {
                    console.log(`     - ${errorText.trim()}`);
                }
            }
        } else {
            console.log('   ✗ No validation errors found (unexpected)');
        }
        
        // 7. Test 3: Check client-side JavaScript validation
        console.log('\n6. TEST 3: Check JavaScript validation');
        
        // Restore original display name
        await page.locator('input[name="display_name"]').fill(currentDisplayName);
        
        // Check if there are any JavaScript errors in console
        const consoleMessages = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                consoleMessages.push(msg.text());
            }
        });
        
        // Try submission again
        await page.click('button[type="submit"]:has-text("Update User")');
        await page.waitForLoadState('networkidle');
        
        if (consoleMessages.length > 0) {
            console.log('   JavaScript errors found:');
            consoleMessages.forEach(msg => console.log(`     - ${msg}`));
        } else {
            console.log('   ✓ No JavaScript errors found');
        }
        
        console.log('\n==========================================');
        console.log('Validation debugging completed!');
        
    } catch (error) {
        console.error('\nError during debug:', error.message);
    }
    
    // Keep browser open for inspection
    console.log('\nKeeping browser open for 15 seconds for manual inspection...');
    await page.waitForTimeout(15000);
    
    await browser.close();
})();