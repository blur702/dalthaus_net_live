const { chromium } = require('playwright');

async function testUpdatedMaintenance() {
    console.log('Testing updated maintenance page...\n');
    
    const browser = await chromium.launch({
        headless: false
    });
    
    try {
        const context = await browser.newContext();
        const page = await context.newPage();
        
        // Visit homepage
        console.log('Visiting homepage to check updated design...');
        await page.goto('https://dalthaus.net/', { waitUntil: 'load' });
        
        const title = await page.title();
        const bodyText = await page.textContent('body');
        
        console.log(`Title: ${title}`);
        console.log(`Body preview: ${bodyText.substring(0, 300)}...`);
        
        // Check for admin button
        const hasAdminButton = bodyText.toLowerCase().includes('admin login');
        console.log(`Has admin button: ${hasAdminButton ? '❌ YES (still there)' : '✅ NO (removed)'}`);
        
        // Check for maintenance keywords
        const showsMaintenance = bodyText.toLowerCase().includes('maintenance');
        console.log(`Shows maintenance: ${showsMaintenance ? '✅ YES' : '❌ NO'}`);
        
        // Take screenshot to see colors
        await page.screenshot({ path: 'updated-maintenance.png', fullPage: true });
        console.log('✅ Screenshot saved: updated-maintenance.png');
        
        // Keep browser open briefly for visual inspection
        console.log('\nBrowser will stay open for 5 seconds for visual inspection...');
        await page.waitForTimeout(5000);
        
    } catch (error) {
        console.error('Error:', error);
    } finally {
        await browser.close();
    }
}

testUpdatedMaintenance();