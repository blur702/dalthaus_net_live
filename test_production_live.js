// Comprehensive Production E2E Tests for dalthaus.net
const { chromium } = require('playwright');

async function runProductionTests() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        // Clear cookies and cache for fresh test
        viewport: { width: 1280, height: 720 }
    });
    
    const page = await context.newPage();
    let testsPassed = 0;
    let testsFailed = 0;
    
    console.log('=== RUNNING PRODUCTION E2E TESTS FOR DALTHAUS.NET ===\n');
    
    // Test 1: Homepage loads
    console.log('Test 1: Homepage');
    try {
        const response = await page.goto('https://dalthaus.net/', { 
            waitUntil: 'networkidle',
            timeout: 30000 
        });
        if (response.status() === 200) {
            console.log('  ✅ Homepage loads (200 OK)');
            testsPassed++;
        } else {
            console.log(`  ❌ Homepage returned ${response.status()}`);
            testsFailed++;
        }
    } catch (error) {
        console.log('  ❌ Homepage error:', error.message);
        testsFailed++;
    }
    
    // Test 2: Admin login page
    console.log('\nTest 2: Admin Login Page');
    try {
        const response = await page.goto('https://dalthaus.net/admin', { 
            waitUntil: 'networkidle',
            timeout: 30000 
        });
        
        // Check for database error text
        const pageContent = await page.content();
        if (pageContent.includes('Database Connection Error')) {
            console.log('  ❌ Database Connection Error still showing!');
            console.log('     Page contains:', pageContent.substring(0, 200));
            testsFailed++;
        } else if (response.status() === 302 || response.url().includes('/admin/login')) {
            console.log('  ✅ Admin redirects to login (302) - CORRECT');
            testsPassed++;
        } else if (response.status() === 200 && pageContent.includes('Login')) {
            console.log('  ✅ Admin login page loads (200 OK)');
            testsPassed++;
        } else {
            console.log(`  ⚠️ Unexpected response: ${response.status()}`);
            testsFailed++;
        }
    } catch (error) {
        console.log('  ❌ Admin page error:', error.message);
        testsFailed++;
    }
    
    // Test 3: Admin dashboard (should redirect to login)
    console.log('\nTest 3: Admin Dashboard');
    try {
        const response = await page.goto('https://dalthaus.net/admin/dashboard', { 
            waitUntil: 'networkidle',
            timeout: 30000 
        });
        
        const pageContent = await page.content();
        if (pageContent.includes('Database Connection Error')) {
            console.log('  ❌ Database Connection Error on dashboard!');
            testsFailed++;
        } else if (response.status() === 302 || response.url().includes('/admin/login')) {
            console.log('  ✅ Dashboard redirects to login - CORRECT');
            testsPassed++;
        } else {
            console.log(`  ⚠️ Dashboard status: ${response.status()}`);
            testsFailed++;
        }
    } catch (error) {
        console.log('  ❌ Dashboard error:', error.message);
        testsFailed++;
    }
    
    // Test 4: Login functionality
    console.log('\nTest 4: Admin Login Process');
    try {
        await page.goto('https://dalthaus.net/admin/login', { 
            waitUntil: 'networkidle',
            timeout: 30000 
        });
        
        // Check if login form exists
        const hasUsernameField = await page.locator('input[name="username"]').count() > 0;
        const hasPasswordField = await page.locator('input[name="password"]').count() > 0;
        
        if (hasUsernameField && hasPasswordField) {
            console.log('  ✅ Login form fields present');
            
            // Try to login
            await page.fill('input[name="username"]', 'kevin');
            await page.fill('input[name="password"]', '(130Bpm)');
            
            // Look for CSRF token
            const csrfToken = await page.locator('input[name="_token"]').getAttribute('value');
            if (csrfToken) {
                console.log('  ✅ CSRF token present');
            }
            
            await page.click('button[type="submit"]');
            await page.waitForLoadState('networkidle');
            
            // Check if we're on dashboard now
            if (page.url().includes('/admin/dashboard')) {
                console.log('  ✅ Login successful - redirected to dashboard');
                testsPassed++;
                
                // Check dashboard content
                const dashboardContent = await page.content();
                if (!dashboardContent.includes('Database Connection Error')) {
                    console.log('  ✅ Dashboard loads without database errors');
                    testsPassed++;
                } else {
                    console.log('  ❌ Dashboard shows database error after login');
                    testsFailed++;
                }
            } else if (page.url().includes('/admin/login')) {
                console.log('  ⚠️ Still on login page - credentials may be incorrect');
                testsFailed++;
            }
        } else {
            console.log('  ❌ Login form not found');
            testsFailed++;
        }
    } catch (error) {
        console.log('  ❌ Login test error:', error.message);
        testsFailed++;
    }
    
    // Test 5: Database test endpoint
    console.log('\nTest 5: Database Test Endpoint');
    try {
        const response = await page.goto('https://dalthaus.net/simple_db_test.php', { 
            waitUntil: 'networkidle',
            timeout: 30000 
        });
        
        const content = await page.content();
        if (content.includes('SUCCESSFUL') || content.includes('CONNECTED')) {
            console.log('  ✅ Database test shows connection successful');
            testsPassed++;
        } else {
            console.log('  ❌ Database test failed');
            console.log('     Response:', content.substring(0, 200));
            testsFailed++;
        }
    } catch (error) {
        console.log('  ❌ Database test error:', error.message);
        testsFailed++;
    }
    
    // Test 6: Full stack test
    console.log('\nTest 6: Full Stack Test');
    try {
        const response = await page.goto('https://dalthaus.net/test_full_stack.php', { 
            waitUntil: 'networkidle',
            timeout: 30000 
        });
        
        const content = await page.content();
        if (content.includes('CONNECTED') && content.includes('WORKING')) {
            console.log('  ✅ Full stack test successful');
            testsPassed++;
        } else {
            console.log('  ❌ Full stack test failed');
            console.log('     Response:', content.substring(0, 200));
            testsFailed++;
        }
    } catch (error) {
        console.log('  ❌ Full stack test error:', error.message);
        testsFailed++;
    }
    
    // Test 7: Check for any 500/503 errors
    console.log('\nTest 7: Error Status Codes');
    const pagesToCheck = [
        '/articles',
        '/photobooks',
        '/admin/content',
        '/admin/pages',
        '/admin/users',
        '/admin/settings'
    ];
    
    for (const path of pagesToCheck) {
        try {
            const response = await page.goto(`https://dalthaus.net${path}`, { 
                waitUntil: 'networkidle',
                timeout: 15000 
            });
            
            const status = response.status();
            if (status === 500 || status === 503) {
                console.log(`  ❌ ${path}: ${status} error`);
                testsFailed++;
            } else if (status === 200 || status === 302) {
                console.log(`  ✅ ${path}: ${status} OK`);
                testsPassed++;
            } else {
                console.log(`  ⚠️ ${path}: ${status}`);
            }
        } catch (error) {
            console.log(`  ❌ ${path}: ${error.message}`);
            testsFailed++;
        }
    }
    
    await browser.close();
    
    // Summary
    console.log('\n' + '='.repeat(50));
    console.log('TEST SUMMARY:');
    console.log(`  Passed: ${testsPassed}`);
    console.log(`  Failed: ${testsFailed}`);
    console.log(`  Total:  ${testsPassed + testsFailed}`);
    
    if (testsFailed === 0) {
        console.log('\n✅ ALL TESTS PASSED! Site is working 100%');
    } else {
        console.log(`\n❌ ${testsFailed} tests failed - needs attention`);
    }
    
    console.log('='.repeat(50));
    
    process.exit(testsFailed === 0 ? 0 : 1);
}

// Run the tests
runProductionTests().catch(console.error);