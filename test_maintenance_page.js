const puppeteer = require('playwright');

async function testMaintenancePage() {
    console.log('Testing maintenance page functionality...\n');
    
    const browser = await puppeteer.chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    try {
        const page = await browser.newPage();
        
        // Navigate to homepage
        console.log('1. Visiting homepage...');
        await page.goto('https://dalthaus.net/', { waitUntil: 'load' });
        
        const url = page.url();
        const title = await page.title();
        const content = await page.textContent('body');
        
        console.log(`   URL: ${url}`);
        console.log(`   Title: ${title}`);
        console.log(`   Body content preview: ${content.substring(0, 200)}...`);
        
        // Check if maintenance page is shown
        if (content.includes('Site Under Maintenance') || content.includes('maintenance')) {
            console.log('   ✅ MAINTENANCE MODE IS ACTIVE');
        } else {
            console.log('   ❌ MAINTENANCE MODE NOT SHOWING');
        }
        
        // Try admin login page
        console.log('\n2. Checking admin login page...');
        await page.goto('https://dalthaus.net/admin/login', { waitUntil: 'load' });
        
        const adminUrl = page.url();
        const adminTitle = await page.title();
        const adminContent = await page.textContent('body');
        
        console.log(`   URL: ${adminUrl}`);
        console.log(`   Title: ${adminTitle}`);
        
        if (adminContent.includes('Login') || adminContent.includes('Username')) {
            console.log('   ✅ Admin login page accessible');
        } else {
            console.log('   ❌ Admin login page not accessible');
        }
        
    } catch (error) {
        console.error('Error testing maintenance page:', error);
    } finally {
        await browser.close();
    }
}

testMaintenancePage();