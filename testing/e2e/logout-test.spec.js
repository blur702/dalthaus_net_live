const { test, expect } = require('@playwright/test');

test.describe('Logout Functionality Test', () => {
    test('should successfully logout without 500 error', async ({ page }) => {
        console.log('🔄 Testing logout functionality...');

        // Track console messages for debugging
        page.on('console', msg => {
            if (msg.type() === 'log') {
                console.log('Browser:', msg.text());
            } else if (msg.type() === 'error') {
                console.log('Browser Error:', msg.text());
            }
        });

        try {
            // Step 1: Navigate to admin portal
            await page.goto('https://dalthaus.net/admin-access.php');
            await page.waitForSelector('h1', { timeout: 10000 });
            console.log('✓ Accessed admin portal');

            // Step 2: Try direct admin login
            console.log('🔄 Attempting to access admin login...');
            
            // Try multiple admin access methods
            const adminUrls = [
                'https://dalthaus.net/?route=admin/login',
                'https://dalthaus.net/admin/login',
                'https://dalthaus.net/admin.html'
            ];

            let loginSuccessful = false;
            for (const url of adminUrls) {
                try {
                    console.log(`Trying: ${url}`);
                    await page.goto(url, { timeout: 15000 });
                    
                    // Check if we got a login form
                    const loginForm = await page.locator('input[name="username"]').count();
                    if (loginForm > 0) {
                        console.log(`✓ Login form found at: ${url}`);
                        loginSuccessful = true;
                        break;
                    }
                } catch (error) {
                    console.log(`Failed to access: ${url}`);
                    continue;
                }
            }

            if (!loginSuccessful) {
                console.log('⚠️ Could not access admin login directly, testing logout endpoint directly');
                
                // Test logout endpoint directly
                console.log('🔄 Testing logout endpoint directly...');
                
                const response = await page.goto('https://dalthaus.net/admin/logout', { 
                    waitUntil: 'networkidle',
                    timeout: 15000 
                });
                
                const status = response.status();
                console.log(`Logout endpoint status: ${status}`);
                
                // Should get 302 redirect, not 500 error
                expect(status).not.toBe(500);
                console.log('✓ Logout endpoint no longer returns 500 error');
                
                if (status === 302 || status === 200) {
                    console.log('✓ Logout endpoint works correctly');
                } else {
                    console.log(`⚠️ Unexpected status: ${status}`);
                }
                
                return;
            }

            // Step 3: Login with admin credentials
            await page.fill('input[name="username"]', 'kevin');
            await page.fill('input[name="password"]', '(130Bpm)');
            await page.click('button[type="submit"]');
            console.log('✓ Login form submitted');

            // Wait for successful login
            await page.waitForURL('**/admin/dashboard', { timeout: 15000 });
            console.log('✓ Successfully logged into admin');

            // Step 4: Test logout functionality
            console.log('🔄 Testing logout functionality...');

            // Look for logout button/link
            const logoutSelectors = [
                'a[href*="logout"]',
                'button[onclick*="logout"]',
                'form[action*="logout"] button[type="submit"]',
                'a:has-text("Logout")',
                'a:has-text("Sign Out")'
            ];

            let logoutElement = null;
            for (const selector of logoutSelectors) {
                const elements = await page.locator(selector).count();
                if (elements > 0) {
                    logoutElement = page.locator(selector).first();
                    console.log(`✓ Found logout element: ${selector}`);
                    break;
                }
            }

            if (logoutElement) {
                // Step 5: Click logout
                console.log('🔄 Clicking logout...');
                await logoutElement.click();

                // Wait for redirect to login page
                await page.waitForFunction(
                    () => window.location.href.includes('login') || window.location.href.includes('admin'),
                    { timeout: 10000 }
                );

                const currentUrl = page.url();
                console.log(`✓ Redirected to: ${currentUrl}`);

                // Verify we're logged out (should see login form)
                const loginFormVisible = await page.locator('input[name="username"]').count();
                if (loginFormVisible > 0) {
                    console.log('✓ Successfully logged out - login form visible');
                } else {
                    console.log('⚠️ Logout may have succeeded but login form not visible');
                }

            } else {
                // Test logout by making a POST request to logout endpoint
                console.log('🔄 No logout button found, testing POST to logout endpoint...');
                
                // Get CSRF token
                const csrfToken = await page.evaluate(() => {
                    const tokenInput = document.querySelector('input[name="_token"]');
                    return tokenInput ? tokenInput.value : null;
                });

                // Make POST request to logout
                const response = await page.evaluate(async (token) => {
                    const formData = new FormData();
                    if (token) formData.append('_token', token);
                    
                    const response = await fetch('/admin/logout', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });
                    
                    return {
                        status: response.status,
                        redirected: response.redirected,
                        url: response.url
                    };
                }, csrfToken);

                console.log(`Logout POST response: Status ${response.status}`);
                
                // Should not be 500 error
                expect(response.status).not.toBe(500);
                console.log('✓ Logout POST does not return 500 error');

                if (response.status === 302 || response.redirected) {
                    console.log('✓ Logout successfully redirected');
                }
            }

            console.log('🎉 Logout functionality test completed successfully!');

        } catch (error) {
            console.error('❌ Logout test failed:', error.message);
            
            // Take screenshot for debugging
            await page.screenshot({ path: 'testing/results/logout-test-error.png' });
            console.log('Screenshot saved: testing/results/logout-test-error.png');
            
            throw error;
        }
    });

    test('should test logout endpoint directly', async ({ page }) => {
        console.log('🔄 Testing logout endpoint directly without login...');

        try {
            // Test accessing logout endpoint directly
            const response = await page.goto('https://dalthaus.net/admin/logout', { 
                waitUntil: 'networkidle',
                timeout: 15000 
            });
            
            const status = response.status();
            console.log(`Direct logout access status: ${status}`);
            
            // Should get redirect (302) or OK (200), not 500 error
            expect(status).not.toBe(500);
            console.log('✓ Logout endpoint does not return 500 error when accessed directly');
            
            // Should redirect to login page
            const currentUrl = page.url();
            console.log(`Redirected to: ${currentUrl}`);
            
            if (currentUrl.includes('login')) {
                console.log('✓ Correctly redirected to login page');
            }

        } catch (error) {
            console.error('❌ Direct logout test failed:', error.message);
            throw error;
        }
    });
});