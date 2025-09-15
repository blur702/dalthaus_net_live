const { chromium } = require('playwright');

async function testErrorHandling() {
    console.log('Testing error handling with intentionally bad credentials...\n');
    
    const browser = await chromium.launch({
        headless: false,
        devtools: false
    });
    
    const context = await browser.newContext({
        ignoreHTTPSErrors: true
    });
    
    const page = await context.newPage();
    
    // Monitor responses
    page.on('response', response => {
        if (response.url().includes('admin')) {
            console.log(`[${response.status()}] ${response.url()}`);
        }
    });
    
    try {
        await context.clearCookies();
        
        // Test 1: Completely wrong username
        console.log('=== Test 1: Wrong Username ===');
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'nonexistent_user');
        await page.fill('input[name="password"]', 'wrong_password');
        
        const [response1] = await Promise.all([
            page.waitForResponse(response => response.url().includes('login'), { timeout: 10000 }),
            page.click('button[type="submit"]')
        ]);
        
        await page.waitForTimeout(2000);
        console.log(`Response: ${response1.status()}, URL: ${page.url()}`);
        
        // Check for error messages
        const errors1 = await page.locator('.alert, .error, .flash, .message').allTextContents();
        console.log(`Error messages: ${errors1.length > 0 ? errors1.join(', ') : 'None'}`);
        
        // Test 2: Correct username, wrong password  
        console.log('\n=== Test 2: Wrong Password ===');
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', 'definitely_wrong_password');
        
        const [response2] = await Promise.all([
            page.waitForResponse(response => response.url().includes('login'), { timeout: 10000 }),
            page.click('button[type="submit"]')
        ]);
        
        await page.waitForTimeout(2000);
        console.log(`Response: ${response2.status()}, URL: ${page.url()}`);
        
        const errors2 = await page.locator('.alert, .error, .flash, .message').allTextContents();
        console.log(`Error messages: ${errors2.length > 0 ? errors2.join(', ') : 'None'}`);
        
        // Test 3: The actual credentials
        console.log('\n=== Test 3: Actual Credentials ===');
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        
        const [response3] = await Promise.all([
            page.waitForResponse(response => response.url().includes('login'), { timeout: 10000 }),
            page.click('button[type="submit"]')
        ]);
        
        await page.waitForTimeout(2000);
        console.log(`Response: ${response3.status()}, URL: ${page.url()}`);
        
        const errors3 = await page.locator('.alert, .error, .flash, .message').allTextContents();
        console.log(`Error messages: ${errors3.length > 0 ? errors3.join(', ') : 'None'}`);
        
        // Test 4: Try with empty fields
        console.log('\n=== Test 4: Empty Fields ===');
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', '');
        await page.fill('input[name="password"]', '');
        
        const [response4] = await Promise.all([
            page.waitForResponse(response => response.url().includes('login'), { timeout: 10000 }),
            page.click('button[type="submit"]')
        ]);
        
        await page.waitForTimeout(2000);
        console.log(`Response: ${response4.status()}, URL: ${page.url()}`);
        
        const errors4 = await page.locator('.alert, .error, .flash, .message').allTextContents();
        console.log(`Error messages: ${errors4.length > 0 ? errors4.join(', ') : 'None'}`);
        
        console.log('\n=== Analysis ===');
        console.log('If ALL tests show no error messages, then:');
        console.log('1. The exception handling is not being triggered');
        console.log('2. The Auth::attempt() method is failing silently'); 
        console.log('3. There might be a database connection issue');
        console.log('');
        console.log('If SOME tests show error messages, then:');
        console.log('1. Error handling works for some cases');
        console.log('2. The specific credentials are causing a different issue');
        
    } catch (error) {
        console.error('Test error:', error.message);
    } finally {
        await page.waitForTimeout(3000);
        await browser.close();
    }
}

testErrorHandling().catch(console.error);