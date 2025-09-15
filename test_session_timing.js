const { chromium } = require('playwright');

async function testSessionTiming() {
    console.log('🕒 Testing session timing and authentication flow...\n');
    
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
        
        // Step 1: Login
        console.log('=== 🔐 Step 1: Login Process ===');
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        
        console.log('Submitting login...');
        await page.click('button[type="submit"]');
        
        // Wait for login response
        await page.waitForTimeout(3000);
        console.log(`After login: ${page.url()}`);
        
        // Step 2: Check session immediately after login
        console.log('\n=== 📊 Step 2: Session Status After Login ===');
        await page.goto('https://dalthaus.net/debug_dashboard.php');
        await page.waitForTimeout(1000);
        
        const sessionText = await page.evaluate(() => document.body.textContent);
        const isLoggedIn = sessionText.includes('logged_in set: YES') || sessionText.includes('[logged_in] => 1');
        console.log(`Session shows logged in: ${isLoggedIn ? 'YES' : 'NO'}`);
        
        if (isLoggedIn) {
            console.log('✅ Session properly set after login');
        } else {
            console.log('❌ Session not set after login');
            console.log('Session excerpt:', sessionText.substring(0, 300));
        }
        
        // Step 3: Test dashboard debug endpoints
        console.log('\n=== 🎯 Step 3: Dashboard Debug Endpoints ===');
        
        console.log('Testing ?debug_auth...');
        await page.goto('https://dalthaus.net/admin/dashboard?debug_auth');
        await page.waitForTimeout(1000);
        
        const debugAuthText = await page.evaluate(() => document.body.textContent);
        console.log('Dashboard debug_auth result:');
        if (debugAuthText.includes('isAuthenticated()')) {
            const authResult = debugAuthText.includes('isAuthenticated(): YES') ? 'YES' : 'NO';
            console.log(`  - isAuthenticated(): ${authResult}`);
        }
        
        if (debugAuthText.includes('Session data:')) {
            console.log('  - Session data found in debug output');
        }
        
        if (debugAuthText.includes('Authentication passed')) {
            console.log('  - ✅ Dashboard authentication passed');
        } else if (debugAuthText.includes('would redirect to login')) {
            console.log('  - ❌ Dashboard would redirect to login');
        }
        
        // Step 4: Test minimal dashboard
        console.log('\n=== 🎯 Step 4: Minimal Dashboard Test ===');
        
        console.log('Testing ?debug_minimal...');
        await page.goto('https://dalthaus.net/admin/dashboard?debug_minimal');
        await page.waitForTimeout(2000);
        
        const finalUrl = page.url();
        console.log(`Final URL: ${finalUrl}`);
        
        if (finalUrl.includes('/admin/dashboard')) {
            console.log('✅ Minimal dashboard loaded successfully');
        } else {
            console.log('❌ Minimal dashboard redirected to login');
        }
        
        // Step 5: Check cookies
        console.log('\n=== 🍪 Step 5: Cookie Analysis ===');
        const cookies = await context.cookies();
        const sessionCookie = cookies.find(c => c.name.includes('session') || c.name === 'PHPSESSID');
        
        if (sessionCookie) {
            console.log(`Session cookie: ${sessionCookie.name} = ${sessionCookie.value.substring(0, 10)}...`);
            console.log(`Cookie domain: ${sessionCookie.domain}`);
            console.log(`Cookie path: ${sessionCookie.path}`);
        } else {
            console.log('❌ No session cookie found');
        }
        
        // Step 6: Retry normal dashboard
        console.log('\n=== 🚀 Step 6: Final Dashboard Test ===');
        await page.goto('https://dalthaus.net/admin/dashboard');
        await page.waitForTimeout(3000);
        
        const endUrl = page.url();
        console.log(`Final dashboard URL: ${endUrl}`);
        
        if (endUrl.includes('/admin/dashboard')) {
            console.log('🎉 SUCCESS! Dashboard works after debug tests');
        } else {
            console.log('❌ Dashboard still redirects to login');
        }
        
    } catch (error) {
        console.error('💥 Test error:', error.message);
    } finally {
        console.log('\n' + '='.repeat(50));
        console.log('🏁 Session Timing Test Complete');
        console.log('='.repeat(50));
        
        await page.waitForTimeout(3000);
        await browser.close();
    }
}

testSessionTiming().catch(console.error);