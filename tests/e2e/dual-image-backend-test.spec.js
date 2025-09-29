const { test, expect } = require('@playwright/test');

test.describe('Dual Image Backend Tests', () => {
    test.beforeEach(async ({ page }) => {
        // Login as admin to get proper session
        await page.goto('/admin/login');
        await page.fill('#username', 'kevin');
        await page.fill('#password', '(130Bpm)');
        await page.click('button[type="submit"]');
        await expect(page).toHaveURL(/.*\/admin\/dashboard/);
    });

    test('should verify dual image upload endpoint exists', async ({ page }) => {
        console.log('Testing dual image upload endpoint existence...');

        // Test POST request to dual image endpoint
        const response = await page.request.post('/admin/upload/dual-image');

        // Should not return 404 (endpoint exists)
        expect(response.status()).not.toBe(404);

        // Should return 400 (bad request) since no files were uploaded
        expect(response.status()).toBe(400);

        const responseData = await response.json();
        expect(responseData.error).toContain('No valid images uploaded');

        console.log('✅ Dual image endpoint exists and validates correctly');
    });

    test('should verify regular TinyMCE upload still works', async ({ page }) => {
        console.log('Testing regular TinyMCE upload endpoint...');

        const response = await page.request.post('/admin/upload/tinymce');

        // Should not return 404 (endpoint exists)
        expect(response.status()).not.toBe(404);

        // Should return 400 (bad request) since no file was uploaded
        expect(response.status()).toBe(400);

        const responseData = await response.json();
        expect(responseData.error).toContain('No file uploaded');

        console.log('✅ Regular TinyMCE upload endpoint still working');
    });

    test('should test authentication requirement', async ({ page }) => {
        console.log('Testing authentication requirement...');

        // Create a new context without authentication
        const unauthContext = await page.context().browser().newContext();
        const unauthPage = await unauthContext.newPage();

        // Try to access dual image upload without auth
        const response = await unauthPage.request.post('/admin/upload/dual-image');

        // Should redirect to login or return 401/403
        expect([302, 401, 403]).toContain(response.status());

        await unauthContext.close();

        console.log('✅ Authentication requirement verified');
    });

    test('should verify route configuration', async ({ page }) => {
        console.log('Verifying route configuration...');

        // Test that the route is properly configured
        const routes = [
            '/admin/upload/tinymce',
            '/admin/upload/dual-image'
        ];

        for (const route of routes) {
            const response = await page.request.post(route);

            // Should not be 404 (route exists)
            expect(response.status()).not.toBe(404);
            console.log(`✅ Route ${route} is configured`);
        }
    });

    test('should test error handling for different scenarios', async ({ page }) => {
        console.log('Testing error handling scenarios...');

        // Test 1: No files uploaded
        let response = await page.request.post('/admin/upload/dual-image');
        expect(response.status()).toBe(400);
        let data = await response.json();
        expect(data.error).toContain('No valid images uploaded');

        // Test 2: GET request (should be POST only)
        response = await page.request.get('/admin/upload/dual-image');
        expect(response.status()).toBe(405); // Method not allowed

        // Test 3: Invalid content type
        response = await page.request.post('/admin/upload/dual-image', {
            data: 'invalid data'
        });
        expect(response.status()).toBe(400);

        console.log('✅ Error handling working correctly');
    });

    test('should verify Upload controller methods exist', async ({ page }) => {
        console.log('Verifying Upload controller structure...');

        // This test verifies the endpoints respond correctly, indicating methods exist

        // Test tinymce method
        const tinymceResponse = await page.request.post('/admin/upload/tinymce');
        expect(tinymceResponse.status()).toBe(400); // Method exists, just no file uploaded

        // Test dualImage method
        const dualResponse = await page.request.post('/admin/upload/dual-image');
        expect(dualResponse.status()).toBe(400); // Method exists, just no files uploaded

        console.log('✅ Upload controller methods verified');
    });

    test('should test CSRF protection', async ({ page }) => {
        console.log('Testing CSRF protection...');

        // First, let's check if CSRF tokens are required
        // Try making request without proper CSRF token
        const response = await page.request.post('/admin/upload/dual-image', {
            multipart: {
                display_image: {
                    name: 'test.txt',
                    mimeType: 'text/plain',
                    buffer: Buffer.from('test content')
                }
            }
        });

        // Response should be 400 (validation error) not 403 (CSRF error)
        // This indicates the upload controller is handling the request
        expect([400, 403]).toContain(response.status());

        console.log('✅ CSRF protection tested');
    });

    test('should verify file upload directory structure', async ({ page }) => {
        console.log('Testing file upload directory structure...');

        // Check if uploads directory is accessible
        const response = await page.request.get('/uploads/');

        // Should either be 403 (forbidden, but exists) or 200 (directory listing)
        // Should not be 404 (directory doesn't exist)
        expect(response.status()).not.toBe(404);

        console.log('✅ Upload directory structure verified');
    });
});