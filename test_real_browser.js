// Test with real browser to see actual behavior
const { chromium } = require('playwright');

async function testRealBrowser() {
    console.log('=== TESTING WITH REAL BROWSER (NO COOKIES) ===\n');
    
    const browser = await chromium.launch({ 
        headless: true
    });
    
    // Create completely fresh context - no cookies, no cache
    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        viewport: { width: 1280, height: 720 },
        // Clear everything
        storageState: {
            cookies: [],
            origins: []
        }
    });
    
    const page = await context.newPage();
    
    console.log('1. Testing /admin/login with fresh browser...\n');
    
    try {
        const response = await page.goto('https://dalthaus.net/admin/login', {
            waitUntil: 'domcontentloaded',
            timeout: 10000
        });
        
        console.log(`   Status: ${response.status()}`);
        console.log(`   URL: ${page.url()}`);
        
        // Check page content
        const title = await page.title();
        console.log(`   Title: ${title}`);
        
        // Check for login form
        const hasLoginForm = await page.locator('input[name="username"]').count() > 0;
        console.log(`   Has login form: ${hasLoginForm}`);
        
        // Check for error messages
        const content = await page.content();
        if (content.includes('ERR_TOO_MANY_REDIRECTS')) {
            console.log('   ❌ Browser shows redirect error');
        } else if (content.includes('Login') || hasLoginForm) {
            console.log('   ✅ Login page loads successfully');
        }
        
        // Try to fill and submit the form
        if (hasLoginForm) {
            console.log('\n2. Testing login process...\n');
            
            await page.fill('input[name="username"]', 'kevin');
            await page.fill('input[name="password"]', '(130Bpm)');
            
            // Submit
            await Promise.all([
                page.waitForNavigation({ timeout: 5000 }).catch(() => null),
                page.click('button[type="submit"]')
            ]);
            
            console.log(`   After login URL: ${page.url()}`);
            
            if (page.url().includes('/admin/dashboard')) {
                console.log('   ✅ Successfully logged in and redirected to dashboard');
            } else if (page.url().includes('/admin/login')) {
                console.log('   ⚠️ Still on login page (credentials may be wrong)');
            }
        }
        
    } catch (error) {
        console.log(`   ❌ Error: ${error.message}`);
    }
    
    await browser.close();
    
    console.log('\n✅ Browser test complete');
}

testRealBrowser().catch(console.error);