const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

async function testLoginWithDebug() {
    console.log('Starting login test with debug mode enabled...');
    
    // Create screenshots directory
    const screenshotsDir = path.join(__dirname, 'debug_screenshots');
    if (!fs.existsSync(screenshotsDir)) {
        fs.mkdirSync(screenshotsDir);
    }
    
    const browser = await chromium.launch({ 
        headless: false,
        args: ['--start-maximized']
    });
    
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });
    
    const page = await context.newPage();
    
    // Enable console logging
    page.on('console', msg => {
        console.log(`Browser console [${msg.type()}]:`, msg.text());
    });
    
    page.on('pageerror', error => {
        console.log('Page error:', error.message);
    });
    
    try {
        // Step 1: Clear cookies and storage
        console.log('\n1. Clearing browser state...');
        await context.clearCookies();
        
        // Navigate to site first before clearing storage
        await page.goto('https://dalthaus.net/', { waitUntil: 'domcontentloaded' });
        
        // Now clear storage
        await page.evaluate(() => {
            try {
                localStorage.clear();
                sessionStorage.clear();
            } catch (e) {
                console.log('Could not clear storage:', e.message);
            }
        });
        
        // Step 2: Navigate to login page
        console.log('\n2. Navigating to login page...');
        const loginResponse = await page.goto('https://dalthaus.net/admin/login', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        
        console.log(`   Login page status: ${loginResponse.status()}`);
        console.log(`   Final URL: ${page.url()}`);
        
        // Check for any visible error messages
        const errorMessages = await page.$$eval('.error, .alert, .message, [class*="error"], [class*="debug"]', 
            elements => elements.map(el => ({
                class: el.className,
                text: el.textContent.trim()
            }))
        );
        
        if (errorMessages.length > 0) {
            console.log('   Error/Debug messages found on login page:');
            errorMessages.forEach(msg => {
                console.log(`     - [${msg.class}]: ${msg.text}`);
            });
        }
        
        await page.screenshot({ 
            path: path.join(screenshotsDir, '01_login_page.png'),
            fullPage: true 
        });
        
        // Get page content for debugging
        const pageContent = await page.content();
        if (pageContent.includes('Fatal error') || pageContent.includes('Warning') || pageContent.includes('Notice')) {
            console.log('   PHP errors detected on page!');
            const errors = pageContent.match(/(Fatal error|Warning|Notice):.*?(?=<|$)/g);
            if (errors) {
                errors.forEach(error => console.log(`     ${error}`));
            }
        }
        
        // Step 3: Check if login form exists
        console.log('\n3. Checking for login form...');
        const formExists = await page.$('form') !== null;
        const usernameField = await page.$('input[name="username"]');
        const passwordField = await page.$('input[name="password"]');
        
        console.log(`   Form exists: ${formExists}`);
        console.log(`   Username field exists: ${usernameField !== null}`);
        console.log(`   Password field exists: ${passwordField !== null}`);
        
        if (!usernameField || !passwordField) {
            console.log('   ERROR: Login form fields not found!');
            console.log('   Page title:', await page.title());
            console.log('   Page text preview:', (await page.textContent('body')).substring(0, 500));
            await page.screenshot({ 
                path: path.join(screenshotsDir, '02_missing_form.png'),
                fullPage: true 
            });
            return;
        }
        
        // Step 4: Fill login form
        console.log('\n4. Filling login form...');
        await usernameField.fill('kevin');
        await passwordField.fill('(130Bpm)');
        
        await page.screenshot({ 
            path: path.join(screenshotsDir, '03_form_filled.png'),
            fullPage: true 
        });
        
        // Step 5: Submit form
        console.log('\n5. Submitting login form...');
        
        // Look for submit button
        const submitButton = await page.$('button[type="submit"], input[type="submit"]');
        if (!submitButton) {
            console.log('   ERROR: Submit button not found!');
            return;
        }
        
        // Click and wait for navigation or error
        const [response] = await Promise.all([
            page.waitForResponse(response => response.url().includes('/admin/'), { timeout: 10000 }).catch(() => null),
            submitButton.click()
        ]);
        
        // Wait a moment for any redirects or errors
        await page.waitForTimeout(2000);
        
        console.log(`   Current URL after submit: ${page.url()}`);
        if (response) {
            console.log(`   Response status: ${response.status()}`);
        }
        
        // Check for any error messages after submission
        const postSubmitErrors = await page.$$eval('.error, .alert, .message, [class*="error"], [class*="debug"], pre', 
            elements => elements.map(el => ({
                tag: el.tagName,
                class: el.className,
                text: el.textContent.trim().substring(0, 500)
            }))
        );
        
        if (postSubmitErrors.length > 0) {
            console.log('\n   Messages/Errors after submission:');
            postSubmitErrors.forEach(msg => {
                console.log(`     [${msg.tag}.${msg.class}]: ${msg.text}`);
            });
        }
        
        await page.screenshot({ 
            path: path.join(screenshotsDir, '04_after_submit.png'),
            fullPage: true 
        });
        
        // Check page content for PHP errors
        const afterSubmitContent = await page.content();
        if (afterSubmitContent.includes('Fatal error') || afterSubmitContent.includes('Exception')) {
            console.log('\n   PHP ERROR DETECTED!');
            // Extract error details
            const errorMatch = afterSubmitContent.match(/(Fatal error|Exception):.*?(?=in\s|$)/);
            if (errorMatch) {
                console.log(`   Error: ${errorMatch[0]}`);
            }
        }
        
        // Step 6: Check session status
        console.log('\n6. Checking session status...');
        const cookies = await context.cookies();
        const sessionCookie = cookies.find(c => c.name === 'cms_session' || c.name === 'PHPSESSID');
        
        if (sessionCookie) {
            console.log(`   Session cookie found: ${sessionCookie.name}`);
            console.log(`   Cookie value: ${sessionCookie.value.substring(0, 20)}...`);
        } else {
            console.log('   WARNING: No session cookie found!');
        }
        
        // Step 7: Try accessing dashboard
        console.log('\n7. Attempting to access dashboard...');
        const dashboardResponse = await page.goto('https://dalthaus.net/admin/dashboard', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        
        console.log(`   Dashboard status: ${dashboardResponse.status()}`);
        console.log(`   Current URL: ${page.url()}`);
        
        await page.screenshot({ 
            path: path.join(screenshotsDir, '05_dashboard_attempt.png'),
            fullPage: true 
        });
        
        // Check if we're on dashboard or redirected
        if (page.url().includes('/admin/dashboard')) {
            console.log('   SUCCESS: On dashboard page!');
            const pageTitle = await page.title();
            console.log(`   Page title: ${pageTitle}`);
        } else if (page.url().includes('/admin/login')) {
            console.log('   FAILED: Redirected back to login');
            
            // Check for error messages on login page
            const loginErrors = await page.$$eval('.error, .alert, .message', 
                elements => elements.map(el => el.textContent.trim())
            );
            if (loginErrors.length > 0) {
                console.log('   Error messages:');
                loginErrors.forEach(err => console.log(`     - ${err}`));
            }
        } else {
            console.log('   UNEXPECTED: On different page');
        }
        
        // Step 8: Check debug dashboard
        console.log('\n8. Checking debug dashboard...');
        await page.goto('https://dalthaus.net/debug_dashboard.php', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        
        await page.screenshot({ 
            path: path.join(screenshotsDir, '06_debug_dashboard.png'),
            fullPage: true 
        });
        
        // Extract debug info
        const debugInfo = await page.evaluate(() => {
            const text = document.body.innerText;
            const info = {};
            
            // Extract session info
            if (text.includes('Session Status:')) {
                const sessionMatch = text.match(/Session Status: (.*?)(?:\n|$)/);
                info.sessionStatus = sessionMatch ? sessionMatch[1] : 'unknown';
            }
            
            if (text.includes('Session ID:')) {
                const idMatch = text.match(/Session ID: (.*?)(?:\n|$)/);
                info.sessionId = idMatch ? idMatch[1] : 'none';
            }
            
            if (text.includes('User ID:')) {
                const userMatch = text.match(/User ID: (.*?)(?:\n|$)/);
                info.userId = userMatch ? userMatch[1] : 'not set';
            }
            
            return info;
        });
        
        console.log('\n   Debug Dashboard Info:');
        console.log(`     Session Status: ${debugInfo.sessionStatus}`);
        console.log(`     Session ID: ${debugInfo.sessionId}`);
        console.log(`     User ID: ${debugInfo.userId}`);
        
        console.log('\n=== Test Complete ===');
        console.log(`Screenshots saved in: ${screenshotsDir}`);
        
    } catch (error) {
        console.error('\nERROR during test:', error.message);
        await page.screenshot({ 
            path: path.join(screenshotsDir, 'error_state.png'),
            fullPage: true 
        });
    } finally {
        // Keep browser open for 5 seconds to observe
        await page.waitForTimeout(5000);
        await browser.close();
    }
}

// Run the test
testLoginWithDebug().catch(console.error);