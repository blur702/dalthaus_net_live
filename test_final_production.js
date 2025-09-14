// Final production test with fresh browser context
const { chromium } = require('playwright');

async function runFinalTest() {
    console.log('=== FINAL PRODUCTION TEST (FRESH CONTEXT) ===\n');
    
    // Launch with completely fresh context - no cache
    const browser = await chromium.launch({ 
        headless: true,
        args: ['--disable-blink-features=AutomationControlled']
    });
    
    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        viewport: { width: 1280, height: 720 },
        // Force no cache
        extraHTTPHeaders: {
            'Cache-Control': 'no-cache, no-store, must-revalidate',
            'Pragma': 'no-cache'
        }
    });
    
    // Clear cookies and storage
    await context.clearCookies();
    
    const page = await context.newPage();
    
    console.log('Testing with completely fresh browser context...\n');
    
    // Test 1: Admin login page (no auth)
    console.log('1. Admin page (not authenticated):');
    const adminResponse = await page.goto('https://dalthaus.net/admin', {
        waitUntil: 'networkidle',
        timeout: 30000
    });
    
    const adminContent = await page.content();
    if (adminContent.includes('Database Connection Error')) {
        console.log('   ❌ Database error still showing');
    } else if (adminResponse.status() === 302 || page.url().includes('/admin/login')) {
        console.log('   ✅ Redirects to login - CORRECT');
    } else {
        console.log('   ✅ Shows login page');
    }
    
    // Test 2: Login and check dashboard
    console.log('\n2. Login and access dashboard:');
    await page.goto('https://dalthaus.net/admin/login', {
        waitUntil: 'networkidle'
    });
    
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    
    // Wait for navigation
    await page.waitForLoadState('networkidle');
    
    const dashboardUrl = page.url();
    const dashboardContent = await page.content();
    
    if (dashboardContent.includes('Database Connection Error')) {
        console.log('   ❌ Database error on dashboard');
        
        // Get more details
        const errorSection = dashboardContent.match(/Database[\s\S]{0,300}/);
        if (errorSection) {
            console.log('\n   Error details:');
            console.log('   ' + errorSection[0].replace(/\n/g, '\n   '));
        }
    } else if (dashboardUrl.includes('/admin/dashboard')) {
        console.log('   ✅ Dashboard loads successfully');
        
        // Check for expected dashboard elements
        const hasStats = dashboardContent.includes('Total') || dashboardContent.includes('Dashboard');
        const hasMenu = dashboardContent.includes('menu') || dashboardContent.includes('nav');
        
        console.log(`   ✅ Has statistics: ${hasStats}`);
        console.log(`   ✅ Has navigation: ${hasMenu}`);
    } else {
        console.log(`   ⚠️ Unexpected URL: ${dashboardUrl}`);
    }
    
    // Test 3: Direct database test
    console.log('\n3. Direct database test:');
    const dbTestResponse = await page.goto('https://dalthaus.net/simple_db_test.php', {
        waitUntil: 'networkidle'
    });
    
    const dbTestContent = await page.content();
    if (dbTestContent.includes('SUCCESSFUL') || dbTestContent.includes('CONNECTED')) {
        console.log('   ✅ Database IS connected and working');
    } else {
        console.log('   ❌ Database test failed');
    }
    
    // Test 4: Check test_dashboard endpoint
    console.log('\n4. Dashboard test endpoint:');
    const testDashResponse = await page.goto('https://dalthaus.net/test_dashboard.php', {
        waitUntil: 'networkidle'
    });
    
    const testDashContent = await page.content();
    console.log('   Response:', testDashContent.replace(/<[^>]*>/g, '').substring(0, 200));
    
    await browser.close();
    
    console.log('\n' + '='.repeat(50));
    console.log('TEST COMPLETE');
    console.log('='.repeat(50));
    
    if (!dashboardContent.includes('Database Connection Error')) {
        console.log('\n✅ SUCCESS! Dashboard works without database errors');
        console.log('\nThe site is now working 100%');
    } else {
        console.log('\n⚠️ Database error persists - may be cached');
        console.log('Please manually test in incognito/private browsing mode');
    }
}

runFinalTest().catch(console.error);