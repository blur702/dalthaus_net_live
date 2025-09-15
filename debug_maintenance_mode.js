const { chromium } = require('playwright');

async function debugMaintenanceMode() {
    console.log('=== Debugging Maintenance Mode ===\n');
    
    const browser = await chromium.launch({
        headless: false, // Show browser for debugging
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    try {
        const context = await browser.newContext({
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        });
        
        const page = await context.newPage();
        
        // Enable request/response logging
        page.on('request', request => {
            console.log(`[REQUEST] ${request.method()} ${request.url()}`);
        });
        
        page.on('response', response => {
            console.log(`[RESPONSE] ${response.status()} ${response.url()}`);
        });
        
        // Test 1: Homepage without any cookies/session
        console.log('=== TEST 1: Homepage (Fresh Browser) ===');
        await page.goto('https://dalthaus.net/', { 
            waitUntil: 'networkidle',
            timeout: 30000 
        });
        
        const url1 = page.url();
        const title1 = await page.title();
        const html1 = await page.content();
        
        console.log(`URL: ${url1}`);
        console.log(`Title: ${title1}`);
        console.log(`HTML length: ${html1.length} characters`);
        
        // Check for maintenance indicators
        const maintenanceText = await page.textContent('body').catch(() => 'Could not get body text');
        console.log(`Body text preview: ${maintenanceText.substring(0, 200)}...`);
        
        const hasMaintenanceKeywords = [
            'maintenance', 'Maintenance', 'MAINTENANCE',
            'Site Under Maintenance', 'under maintenance',
            'performing maintenance', 'check back soon'
        ].some(keyword => maintenanceText.includes(keyword));
        
        console.log(`Has maintenance keywords: ${hasMaintenanceKeywords}`);
        
        // Check for specific maintenance page elements
        const maintenanceIcon = await page.locator('.maintenance-icon').count();
        const maintenanceTitle = await page.locator('.maintenance-title').count();
        const maintenanceMessage = await page.locator('.maintenance-message').count();
        
        console.log(`Maintenance elements found:`);
        console.log(`  - maintenance-icon: ${maintenanceIcon}`);
        console.log(`  - maintenance-title: ${maintenanceTitle}`);
        console.log(`  - maintenance-message: ${maintenanceMessage}`);
        
        // Take screenshot
        await page.screenshot({ path: 'homepage-test.png', fullPage: true });
        console.log('Screenshot saved: homepage-test.png');
        
        // Test 2: Check specific URL patterns
        console.log('\n=== TEST 2: Testing Different URLs ===');
        
        const testUrls = [
            '/',
            '/articles',
            '/photobooks',
            '/about'
        ];
        
        for (const testUrl of testUrls) {
            console.log(`\nTesting: https://dalthaus.net${testUrl}`);
            
            try {
                await page.goto(`https://dalthaus.net${testUrl}`, { 
                    waitUntil: 'networkidle',
                    timeout: 15000 
                });
                
                const currentUrl = page.url();
                const currentTitle = await page.title();
                const bodyText = await page.textContent('body').catch(() => 'Error getting body text');
                
                console.log(`  Final URL: ${currentUrl}`);
                console.log(`  Title: ${currentTitle}`);
                
                const showsMaintenance = bodyText.toLowerCase().includes('maintenance');
                console.log(`  Shows maintenance: ${showsMaintenance}`);
                
            } catch (error) {
                console.log(`  Error: ${error.message}`);
            }
        }
        
        // Test 3: Check admin area accessibility
        console.log('\n=== TEST 3: Admin Area Access ===');
        
        await page.goto('https://dalthaus.net/admin/login', { 
            waitUntil: 'networkidle',
            timeout: 15000 
        });
        
        const adminUrl = page.url();
        const adminTitle = await page.title();
        const adminText = await page.textContent('body').catch(() => 'Error getting admin body text');
        
        console.log(`Admin URL: ${adminUrl}`);
        console.log(`Admin Title: ${adminTitle}`);
        console.log(`Admin shows login form: ${adminText.toLowerCase().includes('username') || adminText.toLowerCase().includes('login')}`);
        
        await page.screenshot({ path: 'admin-login-test.png', fullPage: true });
        console.log('Screenshot saved: admin-login-test.png');
        
        // Test 4: Check with different user agents (in case there's mobile detection)
        console.log('\n=== TEST 4: Different User Agent ===');
        
        const mobileContext = await browser.newContext({
            userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1'
        });
        
        const mobilePage = await mobileContext.newPage();
        
        await mobilePage.goto('https://dalthaus.net/', { 
            waitUntil: 'networkidle',
            timeout: 15000 
        });
        
        const mobileText = await mobilePage.textContent('body').catch(() => 'Error getting mobile body text');
        const mobileShowsMaintenance = mobileText.toLowerCase().includes('maintenance');
        
        console.log(`Mobile user agent shows maintenance: ${mobileShowsMaintenance}`);
        
        await mobileContext.close();
        
        // Test 5: Check HTTP response codes and headers
        console.log('\n=== TEST 5: HTTP Response Analysis ===');
        
        const response = await page.goto('https://dalthaus.net/', { 
            waitUntil: 'networkidle',
            timeout: 15000 
        });
        
        console.log(`Status Code: ${response.status()}`);
        console.log(`Status Text: ${response.statusText()}`);
        
        const headers = response.headers();
        console.log('Response Headers:');
        Object.keys(headers).forEach(key => {
            console.log(`  ${key}: ${headers[key]}`);
        });
        
        // Final summary
        console.log('\n=== SUMMARY ===');
        console.log(`Maintenance mode appears to be: ${hasMaintenanceKeywords ? 'ACTIVE' : 'INACTIVE'}`);
        
        if (!hasMaintenanceKeywords) {
            console.log('\n❌ ISSUE: Maintenance mode should be active but is not showing');
            console.log('Possible causes:');
            console.log('1. Database setting is not being read correctly');
            console.log('2. BaseController::checkMaintenanceMode() is not being called');
            console.log('3. Settings::getBool() is not working');
            console.log('4. Exception is being caught and maintenance mode is falling back to OFF');
            console.log('5. Session indicates user is logged in as admin (bypassing maintenance)');
        }
        
        // Keep browser open for manual inspection
        console.log('\n📋 Browser will remain open for manual inspection.');
        console.log('Press Enter to close...');
        
        // Wait for user input
        await new Promise(resolve => {
            process.stdin.once('data', () => {
                resolve();
            });
        });
        
    } catch (error) {
        console.error('Error during debugging:', error);
    } finally {
        await browser.close();
    }
}

debugMaintenanceMode().catch(console.error);