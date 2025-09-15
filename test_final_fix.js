const { chromium } = require('playwright');

async function testFinalFix() {
    console.log('🧪 Testing final authentication fix...\n');
    
    const browser = await chromium.launch({
        headless: false,
        devtools: false
    });
    
    const context = await browser.newContext({
        ignoreHTTPSErrors: true
    });
    
    const page = await context.newPage();
    
    // Monitor all responses
    page.on('response', response => {
        console.log(`[${response.status()}] ${response.url()}`);
        if (response.status() === 302) {
            console.log(`  └─ Redirect to: ${response.headers()['location'] || 'Unknown'}`);
        }
    });
    
    try {
        // Clear any existing session
        await context.clearCookies();
        console.log('✓ Cleared browser state\n');
        
        // Test the login flow
        console.log('=== 🔐 Testing Login Flow ===');
        await page.goto('https://dalthaus.net/admin/login');
        console.log('✓ Navigated to login page');
        
        // Fill credentials
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        console.log('✓ Filled credentials: kevin / (130Bpm)');
        
        // Submit and track the flow
        console.log('\n🚀 Submitting login form...');
        
        const [response] = await Promise.all([
            page.waitForResponse(response => 
                response.url().includes('admin'), 
                { timeout: 15000 }
            ),
            page.click('button[type="submit"], input[type="submit"]')
        ]);
        
        console.log(`Initial response: ${response.status()}`);
        
        // Wait for any redirects to complete
        await page.waitForTimeout(3000);
        
        const finalUrl = page.url();
        console.log(`\n📍 Final URL: ${finalUrl}`);
        
        // Determine success or failure
        if (finalUrl.includes('/admin/dashboard')) {
            console.log('🎉 SUCCESS! Login successful - redirected to dashboard');
            
            // Verify dashboard content
            const pageTitle = await page.title();
            console.log(`📄 Page title: ${pageTitle}`);
            
            // Check for dashboard elements
            const dashboardElements = await page.locator('h1, h2, .dashboard, [class*="dashboard"]').allTextContents();
            if (dashboardElements.length > 0) {
                console.log(`🎯 Dashboard content: ${dashboardElements[0]}`);
            }
            
            // Test session persistence
            console.log('\n=== 🔒 Testing Session Persistence ===');
            await page.reload();
            await page.waitForTimeout(2000);
            
            if (page.url().includes('/admin/dashboard')) {
                console.log('✅ Session persists after page reload');
            } else {
                console.log('❌ Session lost after page reload');
            }
            
        } else if (finalUrl.includes('/admin/login')) {
            console.log('❌ FAILED! Still on login page');
            
            // Check for error messages
            const errors = await page.locator('.alert, .error, .flash, .message').allTextContents();
            if (errors.length > 0) {
                console.log(`🚨 Error messages: ${errors.join(', ')}`);
            } else {
                console.log('⚠️ No error messages displayed');
            }
            
            // Check session debug
            console.log('\n=== 🔍 Checking Session Debug ===');
            await page.goto('https://dalthaus.net/debug_dashboard.php');
            const debugText = await page.evaluate(() => document.body.textContent);
            
            if (debugText.includes('logged_in')) {
                console.log('Session debug shows authentication status');
            } else {
                console.log('Session debug not accessible');
            }
            
        } else {
            console.log(`🤔 UNEXPECTED! Redirected to: ${finalUrl}`);
        }
        
        // Final test: Direct dashboard access
        console.log('\n=== 🎯 Testing Direct Dashboard Access ===');
        const dashResponse = await page.goto('https://dalthaus.net/admin/dashboard');
        console.log(`Dashboard access: ${dashResponse.status()}`);
        
        const dashUrl = page.url();
        if (dashUrl.includes('/admin/dashboard')) {
            console.log('✅ Direct dashboard access successful');
        } else {
            console.log('❌ Direct dashboard access failed - redirected to login');
        }
        
    } catch (error) {
        console.error('💥 Test error:', error.message);
    } finally {
        console.log('\n' + '='.repeat(50));
        console.log('🏁 Test Complete');
        console.log('='.repeat(50));
        
        await page.waitForTimeout(5000);
        await browser.close();
    }
}

testFinalFix().catch(console.error);