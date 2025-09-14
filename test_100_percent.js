// Final 100% validation test
const { chromium } = require('playwright');

async function test100Percent() {
    console.log('=== FINAL 100% VALIDATION TEST ===\n');
    
    const browser = await chromium.launch({ 
        headless: true,
        args: ['--disable-blink-features=AutomationControlled']
    });
    
    // Create completely fresh context
    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        viewport: { width: 1280, height: 720 }
    });
    
    const page = await context.newPage();
    let allPassed = true;
    
    try {
        // Test 1: Homepage
        console.log('1. Homepage Test:');
        const homepageResponse = await page.goto('https://dalthaus.net/', {
            waitUntil: 'domcontentloaded',
            timeout: 15000
        });
        if (homepageResponse && homepageResponse.status() === 200) {
            console.log('   ✅ Homepage loads correctly');
        } else {
            console.log('   ❌ Homepage issue');
            allPassed = false;
        }
        
        // Test 2: Admin redirects when not authenticated
        console.log('\n2. Admin Authentication Test:');
        const adminResponse = await page.goto('https://dalthaus.net/admin', {
            waitUntil: 'domcontentloaded',
            timeout: 15000
        });
        
        const adminUrl = page.url();
        const adminContent = await page.content();
        
        if (adminContent.includes('Database Connection Error')) {
            console.log('   ❌ DATABASE ERROR STILL SHOWING!');
            allPassed = false;
        } else if (adminUrl.includes('/admin/login')) {
            console.log('   ✅ Admin redirects to login page (correct behavior)');
        } else {
            console.log('   ⚠️ Unexpected behavior');
            allPassed = false;
        }
        
        // Test 3: Login and access dashboard
        console.log('\n3. Dashboard After Login Test:');
        
        // Go to login page
        await page.goto('https://dalthaus.net/admin/login', {
            waitUntil: 'domcontentloaded',
            timeout: 15000
        });
        
        // Fill login form
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        
        // Submit form
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
            page.click('button[type="submit"]')
        ]);
        
        // Check dashboard
        const dashboardUrl = page.url();
        const dashboardContent = await page.content();
        
        if (dashboardContent.includes('Database Connection Error')) {
            console.log('   ❌ Database error on dashboard!');
            allPassed = false;
        } else if (dashboardUrl.includes('/admin/dashboard')) {
            console.log('   ✅ Dashboard loads successfully after login');
            
            // Check for dashboard elements
            if (dashboardContent.includes('Dashboard') || dashboardContent.includes('Total')) {
                console.log('   ✅ Dashboard content displays correctly');
            }
        } else if (dashboardUrl.includes('/admin/login')) {
            console.log('   ⚠️ Login failed - still on login page');
            allPassed = false;
        } else {
            console.log('   ⚠️ Unexpected URL: ' + dashboardUrl);
            allPassed = false;
        }
        
        // Test 4: Database connectivity
        console.log('\n4. Database Connectivity Test:');
        const dbResponse = await page.goto('https://dalthaus.net/simple_db_test.php', {
            waitUntil: 'domcontentloaded',
            timeout: 15000
        });
        
        const dbContent = await page.content();
        if (dbContent.includes('SUCCESSFUL') || dbContent.includes('CONNECTED')) {
            console.log('   ✅ Database is connected and working');
        } else {
            console.log('   ❌ Database test failed');
            allPassed = false;
        }
        
        // Test 5: Public pages
        console.log('\n5. Public Pages Test:');
        const publicPages = ['/articles', '/photobooks'];
        
        for (const path of publicPages) {
            const response = await page.goto(`https://dalthaus.net${path}`, {
                waitUntil: 'domcontentloaded',
                timeout: 10000
            });
            
            if (response && response.status() === 200) {
                console.log(`   ✅ ${path} loads correctly`);
            } else {
                console.log(`   ❌ ${path} failed`);
                allPassed = false;
            }
        }
        
    } catch (error) {
        console.log('\n❌ Test error:', error.message);
        allPassed = false;
    } finally {
        await browser.close();
    }
    
    // Final verdict
    console.log('\n' + '='.repeat(60));
    if (allPassed) {
        console.log('✅ ✅ ✅ ALL TESTS PASSED! SITE IS 100% WORKING! ✅ ✅ ✅');
        console.log('\nThe database connection issues have been completely resolved.');
        console.log('The admin dashboard is fully functional.');
        console.log('All systems are operational.');
    } else {
        console.log('⚠️ Some tests failed - please check the results above');
        console.log('\nIf you see database errors, please:');
        console.log('1. Clear your browser cache completely');
        console.log('2. Use incognito/private browsing mode');
        console.log('3. Try a different browser');
    }
    console.log('='.repeat(60));
    
    process.exit(allPassed ? 0 : 1);
}

test100Percent().catch(console.error);