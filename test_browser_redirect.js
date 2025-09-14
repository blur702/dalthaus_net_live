// Test actual browser redirect behavior
const { chromium } = require('playwright');

async function testRedirectBehavior() {
    console.log('=== TESTING BROWSER REDIRECT BEHAVIOR ===\n');
    
    const browser = await chromium.launch({ 
        headless: false,  // Show browser to see what's happening
        slowMo: 500       // Slow down to observe
    });
    
    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        viewport: { width: 1280, height: 720 }
    });
    
    const page = await context.newPage();
    
    // Log all requests and responses
    const redirectChain = [];
    
    page.on('request', request => {
        if (request.url().includes('/admin')) {
            console.log(`→ Request: ${request.method()} ${request.url()}`);
        }
    });
    
    page.on('response', response => {
        if (response.url().includes('/admin')) {
            console.log(`← Response: ${response.status()} ${response.url()}`);
            if (response.status() >= 300 && response.status() < 400) {
                const location = response.headers()['location'];
                if (location) {
                    console.log(`  ↳ Redirecting to: ${location}`);
                    redirectChain.push({
                        from: response.url(),
                        to: location,
                        status: response.status()
                    });
                }
            }
        }
    });
    
    console.log('1. Attempting to navigate to /admin/login...\n');
    
    try {
        // Try to go to login page
        const response = await page.goto('https://dalthaus.net/admin/login', {
            waitUntil: 'networkidle',
            timeout: 10000
        }).catch(err => {
            console.log('Navigation failed:', err.message);
            return null;
        });
        
        if (response) {
            console.log(`\nFinal URL: ${page.url()}`);
            console.log(`Final status: ${response.status()}`);
            
            // Check page content
            const content = await page.content();
            if (content.includes('ERR_TOO_MANY_REDIRECTS')) {
                console.log('\n❌ Browser shows ERR_TOO_MANY_REDIRECTS');
            } else if (content.includes('Login')) {
                console.log('\n✅ Login page loaded successfully');
            } else if (content.includes('Dashboard')) {
                console.log('\n⚠️ Redirected to dashboard (already logged in?)');
            }
        }
        
        if (redirectChain.length > 0) {
            console.log('\n2. Redirect chain detected:');
            redirectChain.forEach((r, i) => {
                console.log(`   ${i + 1}. ${r.from} → ${r.to} (${r.status})`);
            });
            
            // Check for loops
            const urls = redirectChain.map(r => r.from);
            const uniqueUrls = [...new Set(urls)];
            if (urls.length !== uniqueUrls.length) {
                console.log('\n❌ REDIRECT LOOP DETECTED!');
            }
        } else {
            console.log('\n2. No redirects detected');
        }
        
        // Check cookies
        const cookies = await context.cookies();
        console.log('\n3. Cookies set:');
        cookies.forEach(cookie => {
            if (cookie.name.includes('session') || cookie.name.includes('PHPSESS')) {
                console.log(`   ${cookie.name}: ${cookie.value.substring(0, 20)}...`);
            }
        });
        
    } catch (error) {
        console.log('\nError during navigation:', error.message);
    }
    
    console.log('\nPress Ctrl+C to close browser...');
    // Keep browser open for manual inspection
    await new Promise(() => {});
}

testRedirectBehavior().catch(console.error);