const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({
        headless: true // Set to false to see the browser
    });
    
    const context = await browser.newContext();
    const page = await context.newPage();
    
    console.log('Testing login on production server...');
    console.log('==========================================');
    
    try {
        // Navigate to the production admin login page
        console.log('1. Navigating to https://dalthaus.net/admin/login');
        await page.goto('https://dalthaus.net/admin/login', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        
        // Check if we reached the login page
        const pageTitle = await page.title();
        console.log(`   Page title: ${pageTitle}`);
        
        // Check for login form
        const loginForm = await page.locator('form').first();
        const formExists = await loginForm.count() > 0;
        console.log(`   Login form found: ${formExists}`);
        
        if (!formExists) {
            console.log('   ERROR: Login form not found!');
            await browser.close();
            return;
        }
        
        // Fill in the login credentials
        console.log('\n2. Entering credentials');
        console.log('   Username: kevin');
        await page.fill('input[name="username"]', 'kevin');
        
        console.log('   Password: (130Bpm)');
        await page.fill('input[name="password"]', '(130Bpm)');
        
        // Take a screenshot before login
        await page.screenshot({ 
            path: 'before_login.png',
            fullPage: true 
        });
        console.log('   Screenshot saved: before_login.png');
        
        // Submit the form
        console.log('\n3. Submitting login form...');
        await Promise.all([
            page.waitForNavigation({
                waitUntil: 'networkidle',
                timeout: 30000
            }),
            page.click('button[type="submit"]')
        ]);
        
        // Check if login was successful
        const currentUrl = page.url();
        console.log(`\n4. After login URL: ${currentUrl}`);
        
        if (currentUrl.includes('/admin/dashboard')) {
            console.log('   ✓ SUCCESS: Redirected to dashboard - Login successful!');
            
            // Check for user info on dashboard
            const pageContent = await page.content();
            
            // Look for welcome message or username display
            const welcomeText = await page.locator('text=/Welcome|Dashboard|kevin/i').first();
            if (await welcomeText.count() > 0) {
                const text = await welcomeText.textContent();
                console.log(`   ✓ User info found: ${text}`);
            }
            
            // Check for display_name if it exists
            const displayNameElements = await page.locator('text=/Kevin|display_name/i').all();
            if (displayNameElements.length > 0) {
                console.log('   ✓ Display name elements found on page');
            }
            
            // Take a screenshot of the dashboard
            await page.screenshot({ 
                path: 'dashboard_after_login.png',
                fullPage: true 
            });
            console.log('   Screenshot saved: dashboard_after_login.png');
            
        } else if (currentUrl.includes('/admin/login')) {
            console.log('   ✗ FAILED: Still on login page');
            
            // Check for error messages
            const errorMessage = await page.locator('.error, .alert-danger, [role="alert"], text=/error|invalid|incorrect/i').first();
            if (await errorMessage.count() > 0) {
                const errorText = await errorMessage.textContent();
                console.log(`   Error message: ${errorText}`);
            }
            
            // Take a screenshot of the error
            await page.screenshot({ 
                path: 'login_error.png',
                fullPage: true 
            });
            console.log('   Screenshot saved: login_error.png');
        } else {
            console.log(`   ? UNKNOWN: Redirected to unexpected page: ${currentUrl}`);
        }
        
        // Test logout if login was successful
        if (currentUrl.includes('/admin/dashboard')) {
            console.log('\n5. Testing logout...');
            
            // Look for logout link
            const logoutLink = await page.locator('a[href*="logout"], button:has-text("Logout")').first();
            if (await logoutLink.count() > 0) {
                await logoutLink.click();
                await page.waitForLoadState('networkidle');
                
                const afterLogoutUrl = page.url();
                if (afterLogoutUrl.includes('/admin/login')) {
                    console.log('   ✓ Successfully logged out');
                } else {
                    console.log(`   ? Logout redirected to: ${afterLogoutUrl}`);
                }
            } else {
                console.log('   Logout link not found');
            }
        }
        
    } catch (error) {
        console.error('\nError during test:', error.message);
        
        // Take error screenshot
        try {
            await page.screenshot({ 
                path: 'error_screenshot.png',
                fullPage: true 
            });
            console.log('Error screenshot saved: error_screenshot.png');
        } catch (e) {
            console.log('Could not take error screenshot');
        }
    }
    
    console.log('\n==========================================');
    console.log('Test completed');
    
    await browser.close();
})();