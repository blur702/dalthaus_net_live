const { chromium } = require('playwright');
const fs = require('fs').promises;
const path = require('path');

async function testLiveLogin() {
    console.log('Starting live login test...\n');
    
    // Create screenshots directory
    const screenshotsDir = path.join(__dirname, 'login_test_screenshots');
    try {
        await fs.mkdir(screenshotsDir, { recursive: true });
    } catch (e) {
        // Directory might already exist
    }
    
    const browser = await chromium.launch({ 
        headless: false,
        slowMo: 500 // Slow down actions so we can see what's happening
    });
    
    const context = await browser.newContext({
        viewport: { width: 1280, height: 720 },
        ignoreHTTPSErrors: true // In case there are cert issues
    });
    
    const page = await context.newPage();
    
    // Enable console logging
    page.on('console', msg => {
        if (msg.type() === 'error') {
            console.log('Browser console error:', msg.text());
        }
    });
    
    page.on('response', response => {
        if (response.status() >= 400) {
            console.log(`HTTP ${response.status()} error: ${response.url()}`);
        }
    });
    
    try {
        // Step 1: Check initial session status
        console.log('Step 1: Checking initial session status at /test_session.php');
        await page.goto('https://dalthaus.net/test_session.php', { 
            waitUntil: 'networkidle',
            timeout: 30000 
        });
        await page.waitForTimeout(2000);
        
        const initialSessionContent = await page.content();
        console.log('Initial session page content captured');
        await page.screenshot({ 
            path: path.join(screenshotsDir, '01_initial_session.png'),
            fullPage: true 
        });
        
        // Extract session info if visible
        const sessionText = await page.textContent('body').catch(() => 'Could not extract text');
        console.log('Session status:', sessionText.substring(0, 200));
        
        // Step 2: Navigate to login page
        console.log('\nStep 2: Navigating to /admin/login');
        await page.goto('https://dalthaus.net/admin/login', { 
            waitUntil: 'networkidle',
            timeout: 30000 
        });
        await page.waitForTimeout(2000);
        
        const loginUrl = page.url();
        console.log('Current URL:', loginUrl);
        await page.screenshot({ 
            path: path.join(screenshotsDir, '02_login_page.png'),
            fullPage: true 
        });
        
        // Check if login form exists
        const hasLoginForm = await page.locator('form').count() > 0;
        console.log('Login form present:', hasLoginForm);
        
        if (hasLoginForm) {
            // Look for username and password fields
            const usernameField = await page.locator('input[name="username"], input[type="text"], input#username').first();
            const passwordField = await page.locator('input[name="password"], input[type="password"], input#password').first();
            
            if (await usernameField.count() > 0 && await passwordField.count() > 0) {
                // Step 3: Fill in login credentials
                console.log('\nStep 3: Filling in login credentials');
                await usernameField.fill('kevin');
                await passwordField.fill('(130Bpm)');
                
                await page.screenshot({ 
                    path: path.join(screenshotsDir, '03_credentials_filled.png'),
                    fullPage: true 
                });
                
                // Look for submit button
                const submitButton = await page.locator('button[type="submit"], input[type="submit"], button:has-text("Login"), button:has-text("Sign In")').first();
                
                if (await submitButton.count() > 0) {
                    // Step 4: Submit the form
                    console.log('\nStep 4: Submitting login form');
                    
                    // Set up navigation promise before clicking
                    const navigationPromise = page.waitForNavigation({ 
                        waitUntil: 'networkidle',
                        timeout: 30000 
                    }).catch(() => {
                        console.log('No navigation occurred after form submission');
                        return null;
                    });
                    
                    await submitButton.click();
                    
                    // Wait for navigation or timeout
                    await navigationPromise;
                    await page.waitForTimeout(3000);
                    
                    const afterLoginUrl = page.url();
                    console.log('URL after login attempt:', afterLoginUrl);
                    
                    await page.screenshot({ 
                        path: path.join(screenshotsDir, '04_after_login_attempt.png'),
                        fullPage: true 
                    });
                    
                    // Check for flash messages or error messages
                    const flashMessages = await page.locator('.flash-message, .alert, .error, .message, .notice').allTextContents();
                    if (flashMessages.length > 0) {
                        console.log('Flash/Error messages found:', flashMessages);
                    }
                    
                    // Check if we're still on login page
                    if (afterLoginUrl.includes('/admin/login')) {
                        console.log('Still on login page - login likely failed');
                        
                        // Look for any error messages
                        const pageText = await page.textContent('body');
                        if (pageText.includes('Invalid') || pageText.includes('incorrect') || pageText.includes('error')) {
                            console.log('Error indicators found in page content');
                        }
                    } else if (afterLoginUrl.includes('/admin/dashboard')) {
                        console.log('Successfully redirected to dashboard!');
                    } else {
                        console.log('Redirected to unexpected page:', afterLoginUrl);
                    }
                } else {
                    console.log('Could not find submit button');
                }
            } else {
                console.log('Could not find username or password fields');
            }
        } else {
            console.log('No form found on login page');
            const pageContent = await page.textContent('body');
            console.log('Page content preview:', pageContent.substring(0, 500));
        }
        
        // Step 5: Try to access dashboard directly
        console.log('\nStep 5: Attempting to access /admin/dashboard directly');
        await page.goto('https://dalthaus.net/admin/dashboard', { 
            waitUntil: 'networkidle',
            timeout: 30000 
        });
        await page.waitForTimeout(2000);
        
        const dashboardUrl = page.url();
        console.log('Dashboard access URL:', dashboardUrl);
        await page.screenshot({ 
            path: path.join(screenshotsDir, '05_dashboard_attempt.png'),
            fullPage: true 
        });
        
        if (dashboardUrl.includes('/admin/login')) {
            console.log('Redirected to login - not authenticated');
        } else if (dashboardUrl.includes('/admin/dashboard')) {
            console.log('Successfully accessed dashboard - authenticated!');
        }
        
        // Step 6: Check final session status
        console.log('\nStep 6: Checking final session status');
        await page.goto('https://dalthaus.net/test_session.php', { 
            waitUntil: 'networkidle',
            timeout: 30000 
        });
        await page.waitForTimeout(2000);
        
        const finalSessionContent = await page.content();
        console.log('Final session page content captured');
        await page.screenshot({ 
            path: path.join(screenshotsDir, '06_final_session.png'),
            fullPage: true 
        });
        
        const finalSessionText = await page.textContent('body').catch(() => 'Could not extract text');
        console.log('Final session status:', finalSessionText.substring(0, 200));
        
        // Summary
        console.log('\n=== TEST SUMMARY ===');
        console.log('Screenshots saved in:', screenshotsDir);
        console.log('Final URL after login:', dashboardUrl);
        console.log('Authentication successful:', !dashboardUrl.includes('/admin/login'));
        
    } catch (error) {
        console.error('Test error:', error);
        await page.screenshot({ 
            path: path.join(screenshotsDir, 'error_state.png'),
            fullPage: true 
        });
    } finally {
        // Keep browser open for 10 seconds to observe
        console.log('\nKeeping browser open for observation...');
        await page.waitForTimeout(10000);
        
        await browser.close();
        console.log('Test completed');
    }
}

// Run the test
testLiveLogin().catch(console.error);