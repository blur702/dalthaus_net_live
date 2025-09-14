// Final browser test to verify fix
const { chromium } = require('playwright');

async function finalBrowserTest() {
    console.log('=== FINAL BROWSER TEST ===\n');
    
    const browser = await chromium.launch({ 
        headless: true,
        args: ['--disable-http2']  // Disable HTTP/2 to avoid some redirect issues
    });
    
    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        viewport: { width: 1280, height: 720 },
        // No cookies, no cache
        storageState: { cookies: [], origins: [] }
    });
    
    const page = await context.newPage();
    
    console.log('Testing login page...\n');
    
    try {
        // Set shorter timeout
        page.setDefaultTimeout(5000);
        
        const response = await page.goto('https://dalthaus.net/admin/login', {
            waitUntil: 'domcontentloaded',
            timeout: 5000
        });
        
        if (response) {
            console.log(`✅ Page loaded!`);
            console.log(`   Status: ${response.status()}`);
            console.log(`   URL: ${page.url()}`);
            
            // Check for login form
            const hasUsername = await page.locator('input[name="username"]').count() > 0;
            const hasPassword = await page.locator('input[name="password"]').count() > 0;
            const hasSubmit = await page.locator('button[type="submit"]').count() > 0;
            
            if (hasUsername && hasPassword && hasSubmit) {
                console.log(`   ✅ Login form is present`);
                console.log('\n=== LOGIN PAGE IS WORKING! ===\n');
                console.log('The redirect loop has been fixed.');
                console.log('Users can now access the login page.');
            } else {
                console.log(`   ⚠️ Login form not found`);
                
                // Check what's on the page
                const text = await page.innerText('body');
                console.log('\nPage content:');
                console.log(text.substring(0, 500));
            }
        }
        
    } catch (error) {
        if (error.message.includes('Timeout')) {
            console.log('❌ Page timeout - likely redirect loop');
            console.log('\nThe issue persists. Checking further...');
            
            // Try to get the actual error
            try {
                await page.goto('chrome://net-internals/#events');
                // Can't access chrome internals in headless
            } catch (e) {
                // Expected
            }
        } else {
            console.log(`Error: ${error.message}`);
        }
    }
    
    await browser.close();
    
    console.log('\nTest complete.');
}

finalBrowserTest().catch(console.error);