const { chromium } = require('playwright');

async function testSessionRegenFix() {
    console.log('🔄 Testing without session regeneration...\n');
    
    const browser = await chromium.launch({
        headless: false,
        devtools: false
    });
    
    const context = await browser.newContext({
        ignoreHTTPSErrors: true
    });
    
    const page = await context.newPage();
    
    try {
        await context.clearCookies();
        
        console.log('=== 🧪 Session Regeneration Test ===');
        
        // Login
        console.log('Attempting login...');
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        
        await page.waitForTimeout(3000);
        console.log(`After login: ${page.url()}`);
        
        // Check session
        console.log('\nChecking session...');
        await page.goto('https://dalthaus.net/debug_dashboard.php');
        await page.waitForTimeout(1000);
        
        const sessionText = await page.evaluate(() => document.body.textContent);
        const hasSession = sessionText.includes('logged_in set: YES') || sessionText.includes('[logged_in] => 1');
        console.log(`Session has login data: ${hasSession ? 'YES' : 'NO'}`);
        
        if (hasSession) {
            console.log('✅ Session data found in debug');
            
            // Extract session details
            if (sessionText.includes('user_id')) {
                console.log('Session contains user_id');
            }
            if (sessionText.includes('logged_in')) {
                console.log('Session contains logged_in flag');
            }
        } else {
            console.log('❌ No session data found');
            console.log('Session snippet:', sessionText.substring(0, 200));
        }
        
        // Try dashboard
        console.log('\nTesting dashboard access...');
        await page.goto('https://dalthaus.net/admin/dashboard');
        await page.waitForTimeout(3000);
        
        const finalUrl = page.url();
        console.log(`Dashboard URL: ${finalUrl}`);
        
        if (finalUrl.includes('/admin/dashboard')) {
            console.log('🎉 SUCCESS! Dashboard loaded without session regeneration');
            
            const title = await page.title();
            console.log(`Page title: ${title}`);
            
        } else {
            console.log('❌ Still redirected to login');
            
            // Debug what changed
            console.log('\nDebugging continued failure...');
            await page.goto('https://dalthaus.net/admin/dashboard?debug_auth');
            await page.waitForTimeout(1000);
            
            const debugText = await page.evaluate(() => document.body.textContent);
            if (debugText.includes('Authentication passed')) {
                console.log('Debug endpoint still shows authentication success');
                console.log('Issue is not with session regeneration');
            } else {
                console.log('Debug endpoint now shows authentication failure');
                console.log('Session regeneration was part of the issue');
            }
        }
        
    } catch (error) {
        console.error('Test error:', error.message);
    } finally {
        console.log('\n' + '='.repeat(50));
        console.log('🏁 Session Regeneration Test Complete');
        console.log('='.repeat(50));
        
        await page.waitForTimeout(3000);
        await browser.close();
    }
}

testSessionRegenFix().catch(console.error);