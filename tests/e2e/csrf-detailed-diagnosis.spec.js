/**
 * Detailed CSRF Token Diagnosis Test
 *
 * Focuses on capturing the exact server response when saving order changes
 * to determine if CSRF validation is working and identify any remaining issues.
 */

import { test, expect } from '@playwright/test';

const PRODUCTION_URL = 'https://dalthaus.net';
const LOGIN_CREDENTIALS = {
    username: 'kevin',
    password: '(130Bpm)'
};

test.describe('Detailed CSRF Token Diagnosis', () => {
    test('should capture detailed server response when saving order', async ({ page }) => {
        test.setTimeout(60000);

        // Login first
        await page.goto(`${PRODUCTION_URL}/admin/login`);
        await page.waitForLoadState('networkidle');

        await page.fill('input[name="username"]', LOGIN_CREDENTIALS.username);
        await page.fill('input[name="password"]', LOGIN_CREDENTIALS.password);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Navigate to content reorder
        await page.goto(`${PRODUCTION_URL}/admin/content/reorder`);
        await page.waitForLoadState('networkidle');

        const contentItems = await page.locator('.sortable-item').count();
        console.log(`Found ${contentItems} content items`);

        if (contentItems >= 2) {
            // Set up response capture
            const responses = [];
            const requestBodies = [];

            page.on('request', request => {
                if (request.url().includes('/admin/content/update-order')) {
                    console.log('📤 Request URL:', request.url());
                    console.log('📤 Request method:', request.method());
                    console.log('📤 Request headers:', request.headers());

                    // Capture request body if it's a POST
                    if (request.method() === 'POST') {
                        requestBodies.push(request.postData());
                    }
                }
            });

            page.on('response', response => {
                if (response.url().includes('/admin/content/update-order')) {
                    responses.push(response);
                }
            });

            // Perform drag and drop
            const firstItem = page.locator('.sortable-item').first();
            const secondItem = page.locator('.sortable-item').nth(1);

            await page.waitForFunction(() => typeof window.Sortable !== 'undefined');

            await firstItem.hover();
            await page.mouse.down();
            await secondItem.hover();
            await page.mouse.up();

            await page.waitForTimeout(1000);

            // Get CSRF token from the page
            const csrfToken = await page.evaluate(() => {
                const script = document.querySelector('script');
                if (script && script.textContent.includes('_token')) {
                    const match = script.textContent.match(/'([a-f0-9]{64})'/);
                    return match ? match[1] : null;
                }
                return null;
            });

            console.log('🔑 CSRF Token found on page:', csrfToken ? 'Yes' : 'No');
            if (csrfToken) {
                console.log('🔑 CSRF Token:', csrfToken.substring(0, 16) + '...');
            }

            // Click Save New Order button
            const saveButton = page.locator('button', { hasText: 'Save New Order' });
            await saveButton.click();

            // Wait for the request to complete
            await page.waitForTimeout(5000);

            // Analyze the response
            if (responses.length > 0) {
                const response = responses[0];
                console.log('📥 Response Status:', response.status());
                console.log('📥 Response Headers:', await response.allHeaders());

                try {
                    const responseBody = await response.text();
                    console.log('📥 Response Body:', responseBody);

                    // Try to parse as JSON
                    try {
                        const jsonResponse = JSON.parse(responseBody);
                        console.log('📥 Parsed JSON Response:', JSON.stringify(jsonResponse, null, 2));

                        // Check specifically for CSRF-related errors
                        if (jsonResponse.message) {
                            if (jsonResponse.message.includes('token') ||
                                jsonResponse.message.includes('CSRF') ||
                                jsonResponse.message.includes('Security')) {
                                console.log('🚨 CSRF-related error detected:', jsonResponse.message);
                            } else {
                                console.log('ℹ️ Non-CSRF error:', jsonResponse.message);
                            }
                        }

                        if (jsonResponse.success === false) {
                            console.log('❌ Operation failed:', jsonResponse.message || 'Unknown error');
                        } else if (jsonResponse.success === true) {
                            console.log('✅ Operation succeeded:', jsonResponse.message || 'Success');
                        }
                    } catch (parseError) {
                        console.log('📥 Response is not JSON:', responseBody.substring(0, 200));
                    }
                } catch (error) {
                    console.log('❌ Error reading response body:', error.message);
                }
            } else {
                console.log('⚠️ No responses captured for update-order request');
            }

            // Check what's displayed on the page
            const statusMessage = await page.locator('#save-message').textContent();
            console.log('🖥️ UI Status Message:', statusMessage);

            // Check the styling to determine if it's success or error
            const statusElement = page.locator('#save-status');
            const statusClass = await statusElement.getAttribute('class');
            console.log('🖥️ UI Status Class:', statusClass);

            if (statusClass && statusClass.includes('bg-green-100')) {
                console.log('✅ UI shows success styling');
            } else if (statusClass && statusClass.includes('bg-red-100')) {
                console.log('❌ UI shows error styling');
            } else {
                console.log('❓ UI shows neutral styling');
            }

            // Log request body if captured
            if (requestBodies.length > 0) {
                console.log('📤 Request Body:', requestBodies[0]);
            }

        } else {
            console.log('⚠️ Not enough content items for test');
        }
    });

    test('should test with minimal order change to isolate issue', async ({ page }) => {
        test.setTimeout(60000);

        // Login and navigate
        await page.goto(`${PRODUCTION_URL}/admin/login`);
        await page.fill('input[name="username"]', LOGIN_CREDENTIALS.username);
        await page.fill('input[name="password"]', LOGIN_CREDENTIALS.password);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        await page.goto(`${PRODUCTION_URL}/admin/content/reorder`);
        await page.waitForLoadState('networkidle');

        // Try to save without making any changes first
        console.log('🧪 Testing save without any changes...');

        const responses = [];
        page.on('response', response => {
            if (response.url().includes('/admin/content/update-order')) {
                responses.push(response);
            }
        });

        await page.click('button', { hasText: 'Save New Order' });
        await page.waitForTimeout(3000);

        if (responses.length > 0) {
            const response = responses[0];
            const responseBody = await response.text();
            console.log('📥 Save without changes response:', responseBody);

            try {
                const jsonResponse = JSON.parse(responseBody);
                console.log('📥 Parsed response:', JSON.stringify(jsonResponse, null, 2));
            } catch (e) {
                console.log('📥 Non-JSON response:', responseBody.substring(0, 200));
            }
        }

        const statusMessage = await page.locator('#save-message').textContent();
        console.log('🖥️ UI Message after save without changes:', statusMessage);
    });
});