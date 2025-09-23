const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({
        headless: false
    });
    
    const context = await browser.newContext();
    const page = await context.newPage();
    
    console.log('Testing display_name validation fixes...');
    console.log('==========================================');
    
    try {
        // 1. Login
        console.log('\n1. Logging into admin');
        await page.goto('https://dalthaus.net/admin/login', {
            waitUntil: 'networkidle'
        });
        
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        
        // 2. Go to user edit
        console.log('2. Opening user edit form');
        await page.goto('https://dalthaus.net/admin/users', {
            waitUntil: 'networkidle'
        });
        
        const editLinks = await page.locator('a:has-text("Edit")').all();
        await editLinks[0].click();
        await page.waitForLoadState('networkidle');
        
        // 3. Test validation - clear display name and try to submit
        console.log('3. Testing empty display_name validation');
        await page.locator('input[name="display_name"]').fill('');
        
        // Listen for alert
        let alertMessage = '';
        page.on('dialog', async dialog => {
            alertMessage = dialog.message();
            await dialog.accept();
        });
        
        await page.click('button[type="submit"]:has-text("Update User")');
        
        // Wait a moment for the alert
        await page.waitForTimeout(1000);
        
        if (alertMessage.includes('Display name must be at least 2 characters')) {
            console.log('   ✓ Empty display name validation working!');
            console.log(`   Alert: "${alertMessage}"`);
        } else if (alertMessage) {
            console.log(`   ? Different alert: "${alertMessage}"`);
        } else {
            console.log('   ✗ No validation alert triggered');
        }
        
        // 4. Test too long display name
        console.log('4. Testing long display_name validation');
        const longName = 'A'.repeat(150); // 150 characters, over the 100 limit
        await page.locator('input[name="display_name"]').fill(longName);
        
        alertMessage = '';
        await page.click('button[type="submit"]:has-text("Update User")');
        await page.waitForTimeout(1000);
        
        if (alertMessage.includes('Display name must be less than 100 characters')) {
            console.log('   ✓ Long display name validation working!');
            console.log(`   Alert: "${alertMessage}"`);
        } else if (alertMessage) {
            console.log(`   ? Different alert: "${alertMessage}"`);
        } else {
            console.log('   ✗ No validation alert triggered');
        }
        
        // 5. Test valid display name
        console.log('5. Testing valid display_name');
        await page.locator('input[name="display_name"]').fill('Valid Display Name');
        
        alertMessage = '';
        await page.click('button[type="submit"]:has-text("Update User")');
        await page.waitForLoadState('networkidle');
        
        if (!alertMessage) {
            // Check for success message instead
            const successText = await page.textContent('body');
            if (successText.includes('success')) {
                console.log('   ✓ Valid display name accepted - form submitted successfully!');
            } else {
                console.log('   ? Form submitted but no clear success message found');
            }
        } else {
            console.log(`   ✗ Unexpected alert: "${alertMessage}"`);
        }
        
        console.log('\n==========================================');
        console.log('Validation testing completed!');
        
    } catch (error) {
        console.error('Error during test:', error.message);
    }
    
    console.log('\nKeeping browser open for 10 seconds...');
    await page.waitForTimeout(10000);
    
    await browser.close();
})();