const { chromium } = require('playwright');
const fs = require('fs').promises;
const path = require('path');

async function testLoginFlow() {
    console.log('Starting comprehensive login flow test after activity_logs fix...\n');
    
    const browser = await chromium.launch({ 
        headless: false,
        slowMo: 500 // Slow down for observation
    });
    
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true,
        recordVideo: {
            dir: './test-videos',
            size: { width: 1920, height: 1080 }
        }
    });
    
    // Enable request/response logging
    const page = await context.newPage();
    
    const networkLog = [];
    
    // Log all network requests and responses
    page.on('request', request => {
        const log = `[REQUEST] ${request.method()} ${request.url()}`;
        console.log(log);
        networkLog.push(log);
    });
    
    page.on('response', response => {
        const log = `[RESPONSE] ${response.status()} ${response.url()}`;
        console.log(log);
        networkLog.push(log);
        
        // Log redirects
        if (response.status() >= 300 && response.status() < 400) {
            const location = response.headers()['location'];
            if (location) {
                const redirectLog = `  → Redirecting to: ${location}`;
                console.log(redirectLog);
                networkLog.push(redirectLog);
            }
        }
    });
    
    // Monitor console errors
    page.on('console', msg => {
        if (msg.type() === 'error') {
            const errorLog = `[CONSOLE ERROR] ${msg.text()}`;
            console.log(errorLog);
            networkLog.push(errorLog);
        }
    });
    
    try {
        // Step 1: Clear all data and start fresh
        console.log('\n=== STEP 1: Starting with clean browser state ===');
        await context.clearCookies();
        console.log('✓ Cleared all cookies');
        
        // Step 2: Navigate to login page
        console.log('\n=== STEP 2: Navigating to login page ===');
        await page.goto('https://dalthaus.net/admin/login', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        
        const loginUrl = page.url();
        console.log(`Current URL: ${loginUrl}`);
        await page.screenshot({ 
            path: 'screenshots/01_login_page.png',
            fullPage: true 
        });
        console.log('✓ Screenshot saved: 01_login_page.png');
        
        // Check if we're actually on the login page
        const loginForm = await page.$('form');
        if (loginForm) {
            console.log('✓ Login form found on page');
        } else {
            console.log('⚠ No login form found on page');
        }
        
        // Step 3: Fill and submit login form
        console.log('\n=== STEP 3: Filling login credentials ===');
        
        // Try multiple selectors for username field
        const usernameSelectors = [
            'input[name="username"]',
            'input#username',
            'input[type="text"][placeholder*="username" i]',
            'input[type="text"]'
        ];
        
        let usernameField = null;
        for (const selector of usernameSelectors) {
            usernameField = await page.$(selector);
            if (usernameField) {
                console.log(`✓ Found username field with selector: ${selector}`);
                break;
            }
        }
        
        if (usernameField) {
            await usernameField.fill('kevin');
            console.log('✓ Filled username: kevin');
        } else {
            console.log('✗ Could not find username field');
        }
        
        // Try multiple selectors for password field
        const passwordSelectors = [
            'input[name="password"]',
            'input#password',
            'input[type="password"]'
        ];
        
        let passwordField = null;
        for (const selector of passwordSelectors) {
            passwordField = await page.$(selector);
            if (passwordField) {
                console.log(`✓ Found password field with selector: ${selector}`);
                break;
            }
        }
        
        if (passwordField) {
            await passwordField.fill('(130Bpm)');
            console.log('✓ Filled password: (130Bpm)');
        } else {
            console.log('✗ Could not find password field');
        }
        
        await page.screenshot({ 
            path: 'screenshots/02_credentials_filled.png',
            fullPage: true 
        });
        console.log('✓ Screenshot saved: 02_credentials_filled.png');
        
        // Step 4: Submit the form
        console.log('\n=== STEP 4: Submitting login form ===');
        
        // Find submit button
        const submitSelectors = [
            'button[type="submit"]',
            'input[type="submit"]',
            'button:has-text("Login")',
            'button:has-text("Sign In")',
            'input[value="Login"]'
        ];
        
        let submitButton = null;
        for (const selector of submitSelectors) {
            try {
                submitButton = await page.$(selector);
                if (submitButton) {
                    console.log(`✓ Found submit button with selector: ${selector}`);
                    break;
                }
            } catch (e) {
                // Continue trying other selectors
            }
        }
        
        if (submitButton) {
            console.log('Clicking submit button...');
            
            // Wait for navigation after clicking
            const [response] = await Promise.all([
                page.waitForNavigation({ 
                    waitUntil: 'networkidle',
                    timeout: 30000 
                }).catch(e => {
                    console.log('Navigation timeout or error:', e.message);
                    return null;
                }),
                submitButton.click()
            ]);
            
            console.log('✓ Form submitted');
            
            // Wait a bit for any redirects to complete
            await page.waitForTimeout(3000);
            
            const afterSubmitUrl = page.url();
            console.log(`\nURL after submission: ${afterSubmitUrl}`);
            
            await page.screenshot({ 
                path: 'screenshots/03_after_submission.png',
                fullPage: true 
            });
            console.log('✓ Screenshot saved: 03_after_submission.png');
            
            // Check if we're on the dashboard
            if (afterSubmitUrl.includes('/admin/dashboard')) {
                console.log('✅ Successfully redirected to dashboard!');
            } else if (afterSubmitUrl.includes('/admin/login')) {
                console.log('⚠ Still on login page after submission');
                
                // Check for error messages
                const errorMessages = await page.$$eval('.error, .alert-danger, .message', 
                    elements => elements.map(el => el.textContent.trim())
                );
                if (errorMessages.length > 0) {
                    console.log('Error messages found:', errorMessages);
                }
            } else {
                console.log(`⚠ Redirected to unexpected page: ${afterSubmitUrl}`);
            }
        } else {
            console.log('✗ Could not find submit button');
        }
        
        // Step 5: Try direct dashboard access
        console.log('\n=== STEP 5: Testing direct dashboard access ===');
        await page.goto('https://dalthaus.net/admin/dashboard', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        
        const dashboardUrl = page.url();
        console.log(`Current URL: ${dashboardUrl}`);
        
        await page.screenshot({ 
            path: 'screenshots/04_dashboard_attempt.png',
            fullPage: true 
        });
        console.log('✓ Screenshot saved: 04_dashboard_attempt.png');
        
        if (dashboardUrl.includes('/admin/dashboard')) {
            console.log('✅ Dashboard loaded successfully!');
            
            // Check for dashboard content
            const dashboardTitle = await page.$('h1, h2, .dashboard-title');
            if (dashboardTitle) {
                const titleText = await dashboardTitle.textContent();
                console.log(`Dashboard title: ${titleText}`);
            }
        } else if (dashboardUrl.includes('/admin/login')) {
            console.log('⚠ Redirected back to login page');
        }
        
        // Step 6: Check session status via debug page
        console.log('\n=== STEP 6: Checking session status ===');
        await page.goto('https://dalthaus.net/debug_dashboard.php', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        
        await page.screenshot({ 
            path: 'screenshots/05_session_debug.png',
            fullPage: true 
        });
        console.log('✓ Screenshot saved: 05_session_debug.png');
        
        // Get session info from the page
        const pageContent = await page.content();
        console.log('\nDebug page content preview:');
        const bodyText = await page.$eval('body', el => el.textContent);
        console.log(bodyText.substring(0, 500));
        
        // Step 7: Check for any errors in console or network
        console.log('\n=== STEP 7: Error Analysis ===');
        
        // Check for 404s or 500s in network log
        const errors = networkLog.filter(log => 
            log.includes('[RESPONSE] 404') || 
            log.includes('[RESPONSE] 500') ||
            log.includes('[CONSOLE ERROR]')
        );
        
        if (errors.length > 0) {
            console.log('Errors detected:');
            errors.forEach(error => console.log(`  - ${error}`));
        } else {
            console.log('✓ No network or console errors detected');
        }
        
        // Save network log
        await fs.writeFile('network_log.txt', networkLog.join('\n'));
        console.log('\n✓ Full network log saved to network_log.txt');
        
        // Final summary
        console.log('\n=== TEST SUMMARY ===');
        console.log(`Login page URL: ${loginUrl}`);
        console.log(`URL after login: ${page.url()}`);
        console.log(`Dashboard accessible: ${dashboardUrl.includes('/admin/dashboard') ? 'YES' : 'NO'}`);
        console.log(`Session authenticated: Check screenshot 05_session_debug.png`);
        
    } catch (error) {
        console.error('\n❌ Test failed with error:', error);
        await page.screenshot({ 
            path: 'screenshots/error_state.png',
            fullPage: true 
        });
    } finally {
        // Keep browser open for manual inspection
        console.log('\n📋 Browser will remain open for manual inspection.');
        console.log('Press Ctrl+C to close when done.');
        
        // Wait indefinitely
        await new Promise(() => {});
    }
}

// Create screenshots directory
fs.mkdir('screenshots', { recursive: true }).then(() => {
    testLoginFlow().catch(console.error);
});