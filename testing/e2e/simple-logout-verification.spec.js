const { test, expect } = require('@playwright/test');

test('Logout endpoint should not return 500 error', async ({ page }) => {
    console.log('🔄 Testing logout endpoint for 500 error fix...');

    try {
        // Test 1: Direct GET request to logout (should not be 500)
        console.log('📡 Testing GET request to logout endpoint...');
        
        const getResponse = await page.goto('https://dalthaus.net/admin/logout', { 
            waitUntil: 'domcontentloaded',
            timeout: 10000 
        });
        
        const getStatus = getResponse.status();
        console.log(`GET /admin/logout status: ${getStatus}`);
        
        // Should not be 500 (even if GET is not the right method)
        expect(getStatus).not.toBe(500);
        console.log('✅ GET request does not return 500 error');

        // Test 2: POST request to logout (proper method)
        console.log('📡 Testing POST request to logout endpoint...');
        
        const postResponse = await page.evaluate(async () => {
            try {
                const response = await fetch('/admin/logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    credentials: 'same-origin'
                });
                
                return {
                    status: response.status,
                    statusText: response.statusText,
                    redirected: response.redirected,
                    url: response.url
                };
            } catch (error) {
                return {
                    error: error.message
                };
            }
        });
        
        console.log(`POST /admin/logout response:`, postResponse);
        
        if (postResponse.error) {
            console.log(`⚠️ POST request had network error: ${postResponse.error}`);
        } else {
            // Should not be 500
            expect(postResponse.status).not.toBe(500);
            console.log('✅ POST request does not return 500 error');
            
            if (postResponse.status === 302 || postResponse.redirected) {
                console.log('✅ Logout correctly redirects (302 or redirected)');
            }
            
            if (postResponse.url && postResponse.url.includes('login')) {
                console.log('✅ Redirects to login page as expected');
            }
        }

        // Test 3: Check that we can access the login page after logout
        console.log('📡 Testing redirect to login page...');
        
        await page.goto('https://dalthaus.net/admin/login', { timeout: 10000 });
        const loginPageStatus = await page.evaluate(() => document.readyState);
        console.log(`Login page loaded: ${loginPageStatus}`);
        
        // Look for login form elements
        const usernameField = await page.locator('input[name="username"]').count();
        if (usernameField > 0) {
            console.log('✅ Login page accessible with username field');
        }

        console.log('🎉 Logout endpoint verification completed successfully!');
        console.log('✅ CONFIRMED: Logout no longer returns 500 error');

    } catch (error) {
        console.error('❌ Logout verification failed:', error.message);
        
        // Still take screenshot for debugging
        try {
            await page.screenshot({ path: 'testing/results/logout-verification-error.png' });
            console.log('Screenshot saved: testing/results/logout-verification-error.png');
        } catch (screenshotError) {
            console.log('Could not save screenshot:', screenshotError.message);
        }
        
        throw error;
    }
});