const { chromium } = require('playwright');
const fs = require('fs').promises;
const path = require('path');

async function testDashboardDebug() {
    const browser = await chromium.launch({ 
        headless: false,
        slowMo: 500 // Slow down for observation
    });
    
    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        viewport: { width: 1280, height: 720 }
    });
    
    const page = await context.newPage();
    
    // Create screenshots directory
    const screenshotsDir = path.join(__dirname, 'dashboard_debug_screenshots');
    try {
        await fs.mkdir(screenshotsDir, { recursive: true });
    } catch (e) {
        console.log('Screenshots directory already exists or error:', e.message);
    }
    
    const results = [];
    
    // Listen for console messages
    page.on('console', msg => {
        if (msg.type() === 'error') {
            console.log(`Console error: ${msg.text()}`);
        }
    });
    
    // Listen for page errors
    page.on('pageerror', error => {
        console.log(`Page error: ${error.message}`);
    });
    
    try {
        console.log('\n=== STEP 1: Login to Admin Panel ===');
        
        // Navigate to login page
        await page.goto('https://dalthaus.net/admin/login', { 
            waitUntil: 'networkidle',
            timeout: 30000 
        });
        
        // Take screenshot of login page
        await page.screenshot({ 
            path: path.join(screenshotsDir, '01-login-page.png'),
            fullPage: true 
        });
        console.log('Screenshot saved: 01-login-page.png');
        
        // Check if already logged in (might redirect to dashboard)
        const currentUrl = page.url();
        if (currentUrl.includes('/admin/dashboard')) {
            console.log('Already logged in, redirected to dashboard');
        } else {
            // Perform login
            console.log('Filling login form...');
            await page.fill('input[name="username"]', 'kevin');
            await page.fill('input[name="password"]', '(130Bpm)');
            
            // Take screenshot before submit
            await page.screenshot({ 
                path: path.join(screenshotsDir, '02-login-filled.png'),
                fullPage: true 
            });
            console.log('Screenshot saved: 02-login-filled.png');
            
            // Submit form
            await page.click('button[type="submit"]');
            
            // Wait for navigation
            await page.waitForLoadState('networkidle');
            
            // Check where we ended up
            const afterLoginUrl = page.url();
            console.log(`After login URL: ${afterLoginUrl}`);
            
            await page.screenshot({ 
                path: path.join(screenshotsDir, '03-after-login.png'),
                fullPage: true 
            });
            console.log('Screenshot saved: 03-after-login.png');
            
            if (!afterLoginUrl.includes('/admin/')) {
                console.log('WARNING: Login may have failed, not in admin area');
            }
        }
        
        // Test each debug endpoint
        const debugUrls = [
            { 
                name: 'debug_auth', 
                url: 'https://dalthaus.net/admin/dashboard?debug_auth=1',
                description: 'Testing authentication check'
            },
            { 
                name: 'debug_index', 
                url: 'https://dalthaus.net/admin/dashboard?debug_index=1',
                description: 'Testing index method execution'
            },
            { 
                name: 'debug_render', 
                url: 'https://dalthaus.net/admin/dashboard?debug_render=1',
                description: 'Testing render preparation'
            },
            { 
                name: 'normal_dashboard', 
                url: 'https://dalthaus.net/admin/dashboard',
                description: 'Testing normal dashboard'
            }
        ];
        
        for (let i = 0; i < debugUrls.length; i++) {
            const test = debugUrls[i];
            console.log(`\n=== TESTING: ${test.description} ===`);
            console.log(`URL: ${test.url}`);
            
            const result = {
                name: test.name,
                url: test.url,
                description: test.description,
                success: false,
                finalUrl: '',
                pageContent: '',
                errors: []
            };
            
            try {
                // Clear any previous console errors
                const consoleErrors = [];
                page.on('console', msg => {
                    if (msg.type() === 'error') {
                        consoleErrors.push(msg.text());
                    }
                });
                
                // Navigate to the debug URL
                const response = await page.goto(test.url, { 
                    waitUntil: 'networkidle',
                    timeout: 30000 
                });
                
                // Wait a bit for any redirects
                await page.waitForTimeout(2000);
                
                // Get final URL after any redirects
                result.finalUrl = page.url();
                console.log(`Final URL: ${result.finalUrl}`);
                
                // Check if redirected to login
                if (result.finalUrl.includes('/admin/login')) {
                    console.log('❌ REDIRECTED TO LOGIN!');
                    result.success = false;
                } else if (result.finalUrl === test.url || result.finalUrl.includes('/admin/dashboard')) {
                    console.log('✓ Stayed on dashboard or debug page');
                    result.success = true;
                } else {
                    console.log(`⚠ Unexpected redirect to: ${result.finalUrl}`);
                    result.success = false;
                }
                
                // Get page content (first 500 chars)
                const pageText = await page.evaluate(() => {
                    return document.body ? document.body.innerText.substring(0, 500) : 'No body content';
                });
                result.pageContent = pageText;
                console.log(`Page content preview: ${pageText.substring(0, 200)}...`);
                
                // Check for specific debug output
                if (test.name.startsWith('debug_')) {
                    const hasDebugOutput = await page.evaluate(() => {
                        const text = document.body ? document.body.innerText : '';
                        return text.includes('DEBUG:') || text.includes('User ID:') || text.includes('Is Admin:');
                    });
                    
                    if (hasDebugOutput) {
                        console.log('✓ Debug output found on page');
                    } else {
                        console.log('⚠ No debug output found on page');
                    }
                }
                
                // Take screenshot
                const screenshotName = `${String(i + 4).padStart(2, '0')}-${test.name}.png`;
                await page.screenshot({ 
                    path: path.join(screenshotsDir, screenshotName),
                    fullPage: true 
                });
                console.log(`Screenshot saved: ${screenshotName}`);
                
                // Record console errors
                result.errors = consoleErrors;
                if (consoleErrors.length > 0) {
                    console.log(`Console errors found: ${consoleErrors.join(', ')}`);
                }
                
                // Check response status
                if (response) {
                    console.log(`HTTP Status: ${response.status()}`);
                    result.httpStatus = response.status();
                }
                
            } catch (error) {
                console.log(`ERROR testing ${test.name}: ${error.message}`);
                result.errors.push(error.message);
            }
            
            results.push(result);
        }
        
        // Summary report
        console.log('\n' + '='.repeat(60));
        console.log('SUMMARY REPORT');
        console.log('='.repeat(60));
        
        for (const result of results) {
            console.log(`\n${result.description}:`);
            console.log(`  URL: ${result.url}`);
            console.log(`  Final URL: ${result.finalUrl}`);
            console.log(`  Success: ${result.success ? '✓' : '❌'}`);
            console.log(`  HTTP Status: ${result.httpStatus || 'N/A'}`);
            if (result.errors.length > 0) {
                console.log(`  Errors: ${result.errors.join(', ')}`);
            }
            if (result.pageContent) {
                console.log(`  Content: ${result.pageContent.substring(0, 100)}...`);
            }
        }
        
        // Identify failure point
        console.log('\n' + '='.repeat(60));
        console.log('DIAGNOSIS');
        console.log('='.repeat(60));
        
        const failedTests = results.filter(r => !r.success);
        if (failedTests.length === 0) {
            console.log('✓ All debug endpoints are working!');
        } else {
            console.log(`❌ Failed endpoints: ${failedTests.map(t => t.name).join(', ')}`);
            
            // Determine failure point
            if (results.find(r => r.name === 'debug_auth' && !r.success)) {
                console.log('\n🔍 FAILURE POINT: Authentication check in BaseController');
                console.log('   The session is not being recognized or is expired.');
            } else if (results.find(r => r.name === 'debug_index' && !r.success)) {
                console.log('\n🔍 FAILURE POINT: Dashboard index() method');
                console.log('   Authentication passes but index() method fails.');
            } else if (results.find(r => r.name === 'debug_render' && !r.success)) {
                console.log('\n🔍 FAILURE POINT: Render preparation');
                console.log('   Index method executes but render preparation fails.');
            } else if (results.find(r => r.name === 'normal_dashboard' && !r.success)) {
                console.log('\n🔍 FAILURE POINT: View rendering');
                console.log('   All debug checks pass but final view rendering fails.');
            }
        }
        
        console.log('\n' + '='.repeat(60));
        console.log(`Screenshots saved in: ${screenshotsDir}`);
        console.log('='.repeat(60));
        
    } catch (error) {
        console.error('Test failed:', error);
    } finally {
        // Keep browser open for manual inspection
        console.log('\nBrowser will remain open for 30 seconds for manual inspection...');
        await page.waitForTimeout(30000);
        
        await browser.close();
        console.log('Test completed.');
    }
}

// Run the test
testDashboardDebug().catch(console.error);