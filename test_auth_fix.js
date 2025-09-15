const { chromium } = require('playwright');
const fs = require('fs').promises;
const path = require('path');

async function testAuthFix() {
    console.log('Testing authentication fix...\n');
    
    const screenshotsDir = path.join(__dirname, 'auth-fix-screenshots');
    try {
        await fs.mkdir(screenshotsDir, { recursive: true });
    } catch (e) {}
    
    const browser = await chromium.launch({
        headless: false,
        devtools: false
    });
    
    const context = await browser.newContext({
        ignoreHTTPSErrors: true
    });
    
    const page = await context.newPage();
    
    // Set up response logging
    page.on('response', response => {
        if (response.url().includes('admin')) {
            console.log(`[RESPONSE] ${response.status()} ${response.url()}`);
            if (response.status() === 302 || response.status() === 301) {
                console.log(`[REDIRECT] Location: ${response.headers()['location']}`);
            }
        }
    });
    
    try {
        // Clear browser state
        await context.clearCookies();
        
        // Go to login page
        console.log('=== Step 1: Navigate to login page ===');
        await page.goto('https://dalthaus.net/admin/login', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        console.log(`Current URL: ${page.url()}`);
        await page.screenshot({ 
            path: path.join(screenshotsDir, '01-login-page.png'),
            fullPage: true 
        });
        
        // Fill and submit login form
        console.log('\n=== Step 2: Submit login form ===');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        
        // Submit and wait for response
        const [response] = await Promise.all([
            page.waitForResponse(response => 
                response.url().includes('login'),
                { timeout: 30000 }
            ),
            page.click('button[type="submit"], input[type="submit"]')
        ]);
        
        console.log(`Login submit response: ${response.status()}`);
        
        // Wait for any redirects to complete
        await page.waitForTimeout(3000);
        
        console.log(`Final URL after login: ${page.url()}`);
        await page.screenshot({ 
            path: path.join(screenshotsDir, '02-after-login.png'),
            fullPage: true 
        });
        
        // Check for error messages
        const errorMessages = await page.locator('.alert, .error, .message, .flash').allTextContents();
        if (errorMessages.length > 0) {
            console.log('\n=== Error Messages Found ===');
            errorMessages.forEach((msg, index) => {
                console.log(`${index + 1}. ${msg.trim()}`);
            });
        } else {
            console.log('\n=== No Error Messages Found ===');
        }
        
        // Check if we're on dashboard or login page
        const pageTitle = await page.title();
        console.log(`\nPage title: ${pageTitle}`);
        
        if (page.url().includes('/admin/dashboard')) {
            console.log('✓ SUCCESS: Redirected to dashboard');
        } else if (page.url().includes('/admin/login')) {
            console.log('⚠️ STAYED: Still on login page (expected if credentials are wrong)');
            
            // Check session debug
            console.log('\n=== Step 3: Check session debug ===');
            await page.goto('https://dalthaus.net/debug_dashboard.php');
            await page.screenshot({ 
                path: path.join(screenshotsDir, '03-session-debug.png'),
                fullPage: true 
            });
            
            const sessionInfo = await page.evaluate(() => {
                return document.body.textContent.substring(0, 500);
            });
            console.log('Session debug output:');
            console.log(sessionInfo);
        }
        
    } catch (error) {
        console.error('Test error:', error);
        await page.screenshot({ 
            path: path.join(screenshotsDir, 'error.png'),
            fullPage: true 
        });
    } finally {
        console.log('\n=== Test Complete ===');
        console.log(`Screenshots saved to: ${screenshotsDir}`);
        
        await page.waitForTimeout(5000);
        await browser.close();
    }
}

testAuthFix().catch(console.error);