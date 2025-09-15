const { chromium } = require('playwright');

async function testCompleteFix() {
    console.log('🎯 Testing complete authentication fix...\n');
    
    const browser = await chromium.launch({
        headless: false,
        devtools: false
    });
    
    const context = await browser.newContext({
        ignoreHTTPSErrors: true
    });
    
    const page = await context.newPage();
    
    // Monitor responses
    page.on('response', response => {
        if (response.url().includes('admin')) {
            console.log(`[${response.status()}] ${response.url()}`);
            if (response.status() === 302) {
                console.log(`  └─ Redirect to: ${response.headers()['location'] || 'Unknown'}`);
            }
        }
    });
    
    try {
        // Clear everything
        await context.clearCookies();
        console.log('✓ Cleared browser state\n');
        
        // Test complete flow
        console.log('=== 🔐 Complete Authentication Test ===');
        
        // Step 1: Login
        console.log('Step 1: Login');
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        
        console.log('Submitting login form...');
        await page.click('button[type="submit"]');
        
        // Wait for login to complete
        await page.waitForTimeout(3000);
        console.log(`After login: ${page.url()}`);
        
        // Step 2: Check session immediately  
        console.log('\nStep 2: Session check');
        await page.goto('https://dalthaus.net/debug_dashboard.php');
        await page.waitForTimeout(1000);
        
        const sessionText = await page.evaluate(() => document.body.textContent);
        const hasSession = sessionText.includes('logged_in set: YES') || sessionText.includes('[logged_in] => 1');
        console.log(`Session contains login data: ${hasSession ? 'YES ✅' : 'NO ❌'}`);
        
        // Step 3: Dashboard access
        console.log('\nStep 3: Dashboard access');
        await page.goto('https://dalthaus.net/admin/dashboard');
        await page.waitForTimeout(3000);
        
        const dashUrl = page.url();
        console.log(`Dashboard URL: ${dashUrl}`);
        
        if (dashUrl.includes('/admin/dashboard')) {
            console.log('🎉 SUCCESS! Dashboard loaded successfully');
            
            // Verify dashboard content
            const title = await page.title();
            console.log(`Page title: ${title}`);
            
            // Test dashboard functionality
            const hasContent = await page.locator('h1, h2, .dashboard').count() > 0;
            console.log(`Dashboard content loaded: ${hasContent ? 'YES' : 'NO'}`);
            
            // Test navigation persistence
            console.log('\nStep 4: Testing navigation persistence');
            await page.reload();
            await page.waitForTimeout(2000);
            
            if (page.url().includes('/admin/dashboard')) {
                console.log('✅ Session persists after page reload');
            } else {
                console.log('❌ Session lost after page reload');
            }
            
            // Test other admin pages
            console.log('\nStep 5: Testing other admin pages');
            const testPages = [
                '/admin/content',
                '/admin/pages', 
                '/admin/users'
            ];
            
            for (const testPage of testPages) {
                console.log(`Testing ${testPage}...`);
                await page.goto(`https://dalthaus.net${testPage}`);
                await page.waitForTimeout(1500);
                
                if (page.url().includes(testPage)) {
                    console.log(`  ✅ ${testPage} accessible`);
                } else {
                    console.log(`  ❌ ${testPage} redirected to login`);
                }
            }
            
        } else {
            console.log('❌ FAILED! Dashboard access redirected to login');
            
            // Debug the failure
            console.log('\nDebugging the failure...');
            
            // Check what the dashboard debug shows
            await page.goto('https://dalthaus.net/admin/dashboard?debug_auth');
            await page.waitForTimeout(1000);
            
            const debugText = await page.evaluate(() => document.body.textContent);
            if (debugText.includes('isAuthenticated(): YES')) {
                console.log('Dashboard debug shows authentication SUCCESS');
                console.log('This indicates a timing or redirect issue');
            } else {
                console.log('Dashboard debug shows authentication FAILURE');
                console.log('Session data not properly set');
            }
        }
        
    } catch (error) {
        console.error('💥 Test error:', error.message);
    } finally {
        console.log('\n' + '='.repeat(60));
        console.log('🏁 Complete Authentication Test Finished');
        console.log('='.repeat(60));
        
        await page.waitForTimeout(5000);
        await browser.close();
    }
}

testCompleteFix().catch(console.error);