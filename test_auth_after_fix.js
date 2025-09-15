const { chromium } = require('playwright');

async function testAuthAfterFix() {
    console.log('Testing authentication after fix...\n');
    
    const browser = await chromium.launch({
        headless: false,
        devtools: false
    });
    
    const context = await browser.newContext({
        ignoreHTTPSErrors: true
    });
    
    const page = await context.newPage();
    
    // Monitor for error messages and responses
    page.on('response', response => {
        if (response.url().includes('admin')) {
            console.log(`[${response.status()}] ${response.url()}`);
        }
    });
    
    try {
        // Clear state
        await context.clearCookies();
        
        // Go to login
        console.log('=== Testing Login After Fix ===');
        await page.goto('https://dalthaus.net/admin/login');
        console.log('✓ Loaded login page');
        
        // Submit login
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        
        console.log('Submitting login form...');
        const [response] = await Promise.all([
            page.waitForResponse(response => response.url().includes('login'), { timeout: 15000 }),
            page.click('button[type="submit"], input[type="submit"]')
        ]);
        
        console.log(`Login response: ${response.status()}`);
        
        // Wait for redirect/error
        await page.waitForTimeout(3000);
        
        const finalUrl = page.url();
        console.log(`Final URL: ${finalUrl}`);
        
        // Check for error messages
        const errorSelectors = [
            '.alert',
            '.error', 
            '.flash',
            '.message',
            '[class*="error"]',
            '[class*="alert"]'
        ];
        
        let foundError = false;
        for (const selector of errorSelectors) {
            try {
                const elements = await page.locator(selector).all();
                for (const element of elements) {
                    const text = await element.textContent();
                    if (text && text.trim()) {
                        console.log(`ERROR MESSAGE: ${text.trim()}`);
                        foundError = true;
                    }
                }
            } catch (e) {
                // Selector not found, continue
            }
        }
        
        if (!foundError) {
            console.log('No error messages found');
        }
        
        // Check what page we're on
        if (finalUrl.includes('/admin/dashboard')) {
            console.log('🎉 SUCCESS: Redirected to dashboard!');
        } else if (finalUrl.includes('/admin/login')) {
            console.log('⚠️ Still on login page');
            
            // Check page content for any clues
            const pageText = await page.evaluate(() => document.body.textContent);
            console.log('Page contains:', pageText.substring(0, 200) + '...');
        }
        
        // Test dashboard access directly
        console.log('\n=== Testing Direct Dashboard Access ===');
        const dashResponse = await page.goto('https://dalthaus.net/admin/dashboard');
        console.log(`Dashboard response: ${dashResponse.status()}`);
        console.log(`Dashboard URL: ${page.url()}`);
        
        if (page.url().includes('/admin/dashboard')) {
            console.log('🎉 Dashboard access successful!');
        } else {
            console.log('❌ Dashboard access failed - redirected to login');
        }
        
    } catch (error) {
        console.error('Test error:', error.message);
    } finally {
        console.log('\n=== Test Complete ===');
        await page.waitForTimeout(3000);
        await browser.close();
    }
}

testAuthAfterFix().catch(console.error);