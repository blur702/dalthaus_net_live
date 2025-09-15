const { chromium } = require('playwright');
const fs = require('fs').promises;
const path = require('path');

async function testLoginFlow() {
    console.log('Starting comprehensive login flow test...\n');
    
    // Create screenshots directory
    const screenshotsDir = path.join(__dirname, 'test-screenshots');
    try {
        await fs.mkdir(screenshotsDir, { recursive: true });
    } catch (e) {
        // Directory might already exist
    }
    
    const browser = await chromium.launch({
        headless: false,  // Show browser for debugging
        devtools: true    // Open DevTools
    });
    
    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        recordVideo: {
            dir: screenshotsDir,
            size: { width: 1280, height: 720 }
        }
    });
    
    const page = await context.newPage();
    
    // Set up console logging
    page.on('console', msg => {
        if (msg.type() === 'error') {
            console.log(`[CONSOLE ERROR] ${msg.text()}`);
        }
    });
    
    // Set up request/response logging
    page.on('request', request => {
        if (request.url().includes('admin')) {
            console.log(`[REQUEST] ${request.method()} ${request.url()}`);
        }
    });
    
    page.on('response', response => {
        if (response.url().includes('admin')) {
            console.log(`[RESPONSE] ${response.status()} ${response.url()}`);
            if (response.status() === 302 || response.status() === 301) {
                console.log(`[REDIRECT] Location: ${response.headers()['location']}`);
            }
        }
    });
    
    try {
        // Step 1: Clear all browser state
        console.log('=== Step 1: Clearing browser state ===');
        await context.clearCookies();
        try {
            await page.evaluate(() => {
                try {
                    localStorage.clear();
                    sessionStorage.clear();
                } catch (e) {
                    // Ignore localStorage errors
                }
            });
        } catch (e) {
            console.log('Could not clear storage (this is normal)');
        }
        console.log('Browser state cleared\n');
        
        // Step 2: Navigate to login page
        console.log('=== Step 2: Navigating to login page ===');
        const loginResponse = await page.goto('https://dalthaus.net/admin/login', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        console.log(`Login page status: ${loginResponse.status()}`);
        console.log(`Current URL: ${page.url()}`);
        await page.screenshot({ 
            path: path.join(screenshotsDir, '01-login-page.png'),
            fullPage: true 
        });
        
        // Check if login form exists
        const formExists = await page.locator('form').count() > 0;
        console.log(`Login form exists: ${formExists}`);
        
        if (formExists) {
            // Get form action
            const formAction = await page.locator('form').first().getAttribute('action');
            console.log(`Form action: ${formAction}`);
            
            // Step 3: Fill and submit login form
            console.log('\n=== Step 3: Filling login form ===');
            
            // Fill username
            await page.fill('input[name="username"]', 'kevin');
            console.log('Username filled: kevin');
            
            // Fill password
            await page.fill('input[name="password"]', '(130Bpm)');
            console.log('Password filled: (130Bpm)');
            
            // Take screenshot before submission
            await page.screenshot({ 
                path: path.join(screenshotsDir, '02-login-filled.png'),
                fullPage: true 
            });
            
            // Submit form and wait for navigation
            console.log('\n=== Step 4: Submitting login form ===');
            const [response] = await Promise.all([
                page.waitForResponse(response => 
                    response.url().includes('login') || 
                    response.url().includes('dashboard'),
                    { timeout: 30000 }
                ),
                page.click('button[type="submit"], input[type="submit"]')
            ]);
            
            console.log(`Submit response status: ${response.status()}`);
            console.log(`Submit response URL: ${response.url()}`);
            
            // Wait a bit for any redirects
            await page.waitForTimeout(2000);
            
            console.log(`URL after submission: ${page.url()}`);
            await page.screenshot({ 
                path: path.join(screenshotsDir, '03-after-login-submit.png'),
                fullPage: true 
            });
            
            // Check for error messages
            const errorMessages = await page.locator('.error, .alert-danger, .message').allTextContents();
            if (errorMessages.length > 0) {
                console.log('Error messages found:', errorMessages);
            }
        }
        
        // Step 5: Try to access dashboard directly
        console.log('\n=== Step 5: Attempting direct dashboard access ===');
        const dashboardResponse = await page.goto('https://dalthaus.net/admin/dashboard', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        console.log(`Dashboard response status: ${dashboardResponse.status()}`);
        console.log(`Current URL after dashboard attempt: ${page.url()}`);
        await page.screenshot({ 
            path: path.join(screenshotsDir, '04-dashboard-attempt.png'),
            fullPage: true 
        });
        
        // Step 6: Check debug dashboard
        console.log('\n=== Step 6: Checking debug dashboard ===');
        const debugResponse = await page.goto('https://dalthaus.net/debug_dashboard.php', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        console.log(`Debug dashboard status: ${debugResponse.status()}`);
        
        // Get debug output
        const debugContent = await page.content();
        await page.screenshot({ 
            path: path.join(screenshotsDir, '05-debug-dashboard.png'),
            fullPage: true 
        });
        
        // Extract session info if visible
        const sessionInfo = await page.evaluate(() => {
            const preElements = document.querySelectorAll('pre');
            if (preElements.length > 0) {
                return preElements[0].textContent;
            }
            return document.body.textContent;
        });
        console.log('\nSession info from debug dashboard:');
        console.log(sessionInfo.substring(0, 500) + '...');
        
        // Step 7: Test debug endpoints
        console.log('\n=== Step 7: Testing debug endpoints ===');
        
        // Test debug_auth
        const debugAuthResponse = await page.goto('https://dalthaus.net/admin/dashboard?debug_auth=1', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        console.log(`Debug auth endpoint status: ${debugAuthResponse.status()}`);
        console.log(`URL after debug_auth: ${page.url()}`);
        await page.screenshot({ 
            path: path.join(screenshotsDir, '06-debug-auth.png'),
            fullPage: true 
        });
        
        // Test debug_minimal
        const debugMinimalResponse = await page.goto('https://dalthaus.net/admin/dashboard?debug_minimal=1', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        console.log(`Debug minimal endpoint status: ${debugMinimalResponse.status()}`);
        console.log(`URL after debug_minimal: ${page.url()}`);
        await page.screenshot({ 
            path: path.join(screenshotsDir, '07-debug-minimal.png'),
            fullPage: true 
        });
        
        // Step 8: Check cookies
        console.log('\n=== Step 8: Checking cookies ===');
        const cookies = await context.cookies();
        console.log('Cookies found:', cookies.length);
        cookies.forEach(cookie => {
            console.log(`  - ${cookie.name}: ${cookie.value.substring(0, 20)}... (domain: ${cookie.domain})`);
        });
        
        // Step 9: Try one more dashboard access
        console.log('\n=== Step 9: Final dashboard access attempt ===');
        const finalDashboardResponse = await page.goto('https://dalthaus.net/admin/dashboard', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        console.log(`Final dashboard status: ${finalDashboardResponse.status()}`);
        console.log(`Final URL: ${page.url()}`);
        await page.screenshot({ 
            path: path.join(screenshotsDir, '08-final-dashboard.png'),
            fullPage: true 
        });
        
        // Check page content
        const pageTitle = await page.title();
        const h1Text = await page.locator('h1').first().textContent().catch(() => 'No H1 found');
        console.log(`Page title: ${pageTitle}`);
        console.log(`H1 text: ${h1Text}`);
        
        // Step 10: Network analysis summary
        console.log('\n=== Network Analysis Summary ===');
        const performanceTiming = await page.evaluate(() => {
            const timing = performance.timing;
            return {
                redirectCount: performance.navigation.redirectCount,
                loadTime: timing.loadEventEnd - timing.navigationStart,
                domReadyTime: timing.domContentLoadedEventEnd - timing.navigationStart
            };
        });
        console.log('Performance metrics:', performanceTiming);
        
    } catch (error) {
        console.error('Test error:', error);
        await page.screenshot({ 
            path: path.join(screenshotsDir, 'error-screenshot.png'),
            fullPage: true 
        });
    } finally {
        console.log('\n=== Test Complete ===');
        console.log(`Screenshots saved to: ${screenshotsDir}`);
        console.log('Video saved (if recording was successful)');
        
        // Keep browser open for 10 seconds to inspect
        console.log('\nKeeping browser open for 10 seconds for inspection...');
        await page.waitForTimeout(10000);
        
        await browser.close();
    }
}

// Run the test
testLoginFlow().catch(console.error);