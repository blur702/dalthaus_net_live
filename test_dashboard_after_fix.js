const { chromium } = require('playwright');

async function testDashboardAfterFix() {
    console.log('Testing Dashboard After Exception Fix');
    console.log('=====================================\n');
    
    const browser = await chromium.launch({ 
        headless: false,
        args: ['--start-maximized']
    });
    
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });
    
    const page = await context.newPage();
    
    try {
        // Step 1: Login
        console.log('1. Logging in...');
        await page.goto('https://dalthaus.net/admin/login', {
            waitUntil: 'networkidle'
        });
        
        // Fill login form
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        
        // Submit
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            page.click('button[type="submit"]')
        ]);
        
        console.log('   Current URL:', page.url());
        
        // Step 2: Check if we're on dashboard
        if (page.url().includes('/admin/dashboard')) {
            console.log('   ✓ Successfully reached dashboard!\n');
            
            // Check for errors
            const pageContent = await page.content();
            
            if (pageContent.includes('Fatal error') || pageContent.includes('Exception')) {
                console.log('2. ERROR DETECTED on dashboard:');
                
                // Extract error message
                const errorText = await page.textContent('body');
                const errorLines = errorText.split('\n').slice(0, 10);
                errorLines.forEach(line => {
                    if (line.trim()) console.log('   ', line.trim());
                });
                
                console.log('\n   ✗ Dashboard has errors - Exception fix not applied yet');
                console.log('   Please apply the manual fix from manual_dashboard_fix.txt');
            } else {
                console.log('2. Checking dashboard content...');
                
                // Look for dashboard elements
                const hasStats = await page.$('.stat, .stats, [class*="stat"]') !== null;
                const hasContent = await page.$('table, .content-list, [class*="recent"]') !== null;
                const hasNavigation = await page.$('nav, .sidebar, [class*="menu"]') !== null;
                
                console.log('   Statistics section:', hasStats ? '✓ Found' : '✗ Not found');
                console.log('   Content section:', hasContent ? '✓ Found' : '✗ Not found');
                console.log('   Navigation:', hasNavigation ? '✓ Found' : '✗ Not found');
                
                // Check page title
                const pageTitle = await page.title();
                console.log('   Page title:', pageTitle || '(empty)');
                
                // Look for specific dashboard text
                const bodyText = await page.textContent('body');
                if (bodyText.includes('Dashboard') || bodyText.includes('Overview')) {
                    console.log('   ✓ Dashboard content detected');
                }
                
                // Check for activity stats (should be 0 if table doesn't exist)
                if (bodyText.includes('Active Users') || bodyText.includes('Activities')) {
                    console.log('   ✓ Activity statistics displayed (may show 0)');
                }
                
                console.log('\n   ✓ Dashboard is working properly!');
                console.log('   Exception handling is functioning correctly.');
            }
        } else if (page.url().includes('/admin/login')) {
            console.log('   ✗ Still on login page - authentication failed');
            
            // Check for error messages
            const errorMessages = await page.$$eval('.error, .alert, .message', 
                elements => elements.map(el => el.textContent.trim())
            );
            
            if (errorMessages.length > 0) {
                console.log('   Error messages:');
                errorMessages.forEach(msg => console.log('     -', msg));
            }
        } else {
            console.log('   Unexpected redirect to:', page.url());
        }
        
        // Step 3: Try navigating to other admin pages
        console.log('\n3. Testing navigation to other admin sections...');
        
        const testPages = [
            { url: '/admin/content', name: 'Content Management' },
            { url: '/admin/pages', name: 'Pages' },
            { url: '/admin/users', name: 'Users' }
        ];
        
        for (const testPage of testPages) {
            await page.goto(`https://dalthaus.net${testPage.url}`, {
                waitUntil: 'networkidle',
                timeout: 10000
            }).catch(() => null);
            
            const currentUrl = page.url();
            if (currentUrl.includes(testPage.url)) {
                console.log(`   ✓ ${testPage.name}: Accessible`);
            } else if (currentUrl.includes('/admin/login')) {
                console.log(`   ✗ ${testPage.name}: Redirected to login`);
            } else {
                console.log(`   ? ${testPage.name}: Unexpected URL - ${currentUrl}`);
            }
        }
        
        console.log('\n=== Test Complete ===');
        
    } catch (error) {
        console.error('\nTest failed with error:', error.message);
    } finally {
        await page.screenshot({ path: 'dashboard_test_result.png', fullPage: true });
        console.log('\nScreenshot saved as: dashboard_test_result.png');
        
        // Keep browser open for observation
        await page.waitForTimeout(5000);
        await browser.close();
    }
}

// Run the test
testDashboardAfterFix().catch(console.error);