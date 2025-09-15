const { chromium } = require('playwright');

async function testFinalSolution() {
    console.log('🎯 Testing FINAL SOLUTION: HTTPS Session Cookie Fix\n');
    
    const browser = await chromium.launch({
        headless: false,
        devtools: false
    });
    
    const context = await browser.newContext({
        ignoreHTTPSErrors: true
    });
    
    const page = await context.newPage();
    
    try {
        // Clear everything for clean test
        await context.clearCookies();
        console.log('✓ Browser state cleared\n');
        
        console.log('=== 🔐 FINAL AUTHENTICATION TEST ===');
        console.log('Testing secure cookie fix for HTTPS site\n');
        
        // Step 1: Login
        console.log('Step 1: Login Process');
        await page.goto('https://dalthaus.net/admin/login');
        console.log('✓ Loaded login page');
        
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        console.log('✓ Filled credentials');
        
        console.log('🚀 Submitting login...');
        await page.click('button[type="submit"]');
        
        // Wait for authentication to complete
        await page.waitForTimeout(4000);
        
        const postLoginUrl = page.url();
        console.log(`Result URL: ${postLoginUrl}`);
        
        // Step 2: Verify the outcome
        if (postLoginUrl.includes('/admin/dashboard')) {
            console.log('🎉 SUCCESS! Login redirected to dashboard');
            
            // Verify dashboard content loads
            console.log('\nStep 2: Dashboard Verification');
            const pageTitle = await page.title();
            console.log(`Dashboard title: "${pageTitle}"`);
            
            // Check for dashboard content
            const dashboardContent = await page.locator('h1, h2, .dashboard').first().textContent().catch(() => null);
            if (dashboardContent) {
                console.log(`Dashboard content: "${dashboardContent}"`);
            }
            
            // Step 3: Test session persistence
            console.log('\nStep 3: Session Persistence Test');
            await page.reload();
            await page.waitForTimeout(2000);
            
            if (page.url().includes('/admin/dashboard')) {
                console.log('✅ Session persists after page reload');
            } else {
                console.log('❌ Session lost after reload');
            }
            
            // Step 4: Test other admin pages
            console.log('\nStep 4: Admin Navigation Test');
            const adminPages = [
                { url: '/admin/content', name: 'Content' },
                { url: '/admin/pages', name: 'Pages' },
                { url: '/admin/users', name: 'Users' }
            ];
            
            for (const adminPage of adminPages) {
                console.log(`Testing ${adminPage.name}...`);
                await page.goto(`https://dalthaus.net${adminPage.url}`);
                await page.waitForTimeout(1500);
                
                if (page.url().includes(adminPage.url)) {
                    console.log(`  ✅ ${adminPage.name} page accessible`);
                } else {
                    console.log(`  ❌ ${adminPage.name} redirected to login`);
                }
            }
            
            // Step 5: Test logout
            console.log('\nStep 5: Logout Test');
            await page.goto('https://dalthaus.net/admin/dashboard');
            
            // Look for logout link/button
            const logoutLink = page.locator('a[href*="logout"], button[onclick*="logout"]').first();
            if (await logoutLink.count() > 0) {
                console.log('Found logout link, testing...');
                await logoutLink.click();
                await page.waitForTimeout(2000);
                
                if (page.url().includes('/admin/login')) {
                    console.log('✅ Logout successful - redirected to login');
                } else {
                    console.log('❌ Logout failed - still on dashboard');
                }
            } else {
                console.log('No logout link found, testing manual logout...');
                await page.goto('https://dalthaus.net/admin/logout');
                await page.waitForTimeout(2000);
                
                if (page.url().includes('/admin/login')) {
                    console.log('✅ Manual logout successful');
                } else {
                    console.log('❌ Manual logout failed');
                }
            }
            
        } else if (postLoginUrl.includes('/admin/login')) {
            console.log('❌ FAILED: Still on login page after submission');
            
            // Check for error messages
            const errorMessages = await page.locator('.alert, .error, .flash, .message').allTextContents();
            if (errorMessages.length > 0) {
                console.log(`Error messages: ${errorMessages.join(', ')}`);
            } else {
                console.log('No error messages found - check session debug');
                
                await page.goto('https://dalthaus.net/debug_dashboard.php');
                await page.waitForTimeout(1000);
                const sessionDebug = await page.evaluate(() => document.body.textContent);
                
                if (sessionDebug.includes('logged_in set: YES')) {
                    console.log('Session debug shows user IS logged in');
                    console.log('Issue may be in dashboard authentication check');
                } else {
                    console.log('Session debug shows user is NOT logged in');
                    console.log('Session cookie fix may need more work');
                }
            }
        } else {
            console.log(`🤔 UNEXPECTED: Redirected to ${postLoginUrl}`);
        }
        
    } catch (error) {
        console.error('💥 Test error:', error.message);
    } finally {
        console.log('\n' + '='.repeat(70));
        console.log('🏁 FINAL SOLUTION TEST COMPLETE');
        console.log('='.repeat(70));
        console.log('\nIf this test shows SUCCESS, the authentication system is fixed!');
        console.log('If it still fails, the issue may require server-side session configuration.');
        
        await page.waitForTimeout(5000);
        await browser.close();
    }
}

testFinalSolution().catch(console.error);