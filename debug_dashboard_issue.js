// Debug the dashboard database error
const { chromium } = require('playwright');

async function debugDashboard() {
    const browser = await chromium.launch({ headless: false }); // Run with UI
    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        viewport: { width: 1280, height: 720 }
    });
    
    const page = await context.newPage();
    
    // Enable console logging
    page.on('console', msg => console.log('Browser console:', msg.text()));
    page.on('pageerror', err => console.log('Page error:', err.message));
    
    console.log('=== DEBUGGING DASHBOARD DATABASE ERROR ===\n');
    
    // Login first
    console.log('1. Logging in...');
    await page.goto('https://dalthaus.net/admin/login');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    
    // Wait for navigation
    await page.waitForLoadState('networkidle');
    
    console.log('2. Current URL:', page.url());
    
    // Get the page content
    const content = await page.content();
    
    // Look for error messages
    if (content.includes('Database Connection Error')) {
        console.log('\n❌ FOUND DATABASE ERROR ON PAGE');
        
        // Extract the error section
        const errorMatch = content.match(/Database Connection Error[\s\S]{0,500}/);
        if (errorMatch) {
            console.log('\nError context:');
            console.log(errorMatch[0]);
        }
        
        // Check page source for clues
        const bodyText = await page.locator('body').innerText();
        console.log('\nFull page text (first 1000 chars):');
        console.log(bodyText.substring(0, 1000));
    } else {
        console.log('\n✅ No database error found on dashboard');
        
        // Check what IS on the page
        const title = await page.title();
        console.log('Page title:', title);
        
        const h1 = await page.locator('h1').first().innerText().catch(() => 'No H1');
        console.log('Page H1:', h1);
    }
    
    // Check network requests
    console.log('\n3. Checking for failed requests...');
    page.on('response', response => {
        if (response.status() >= 400) {
            console.log(`Failed request: ${response.url()} - ${response.status()}`);
        }
    });
    
    // Try refreshing
    console.log('\n4. Refreshing page...');
    await page.reload();
    await page.waitForLoadState('networkidle');
    
    const refreshedContent = await page.content();
    if (refreshedContent.includes('Database Connection Error')) {
        console.log('❌ Error persists after refresh');
    } else {
        console.log('✅ No error after refresh');
    }
    
    // Take a screenshot
    await page.screenshot({ path: 'dashboard_debug.png', fullPage: true });
    console.log('\n5. Screenshot saved as dashboard_debug.png');
    
    console.log('\nPress Ctrl+C to close browser...');
    // Keep browser open for manual inspection
    await new Promise(() => {});
}

debugDashboard().catch(console.error);