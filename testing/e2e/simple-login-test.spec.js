import { test, expect } from '@playwright/test';

test('Simple login test', async ({ page }) => {
    // Listen for console logs
    page.on('console', msg => {
        console.log(`Browser: ${msg.text()}`);
    });
    
    // Listen for page errors
    page.on('pageerror', error => {
        console.log(`Page error: ${error.message}`);
    });
    
    // Go to login page
    await page.goto('http://localhost:8000/admin/login');
    
    // Take screenshot of login page
    await page.screenshot({ path: 'testing/results/login-page-test.png' });
    
    // Check if form elements exist
    const usernameInput = page.locator('input[name="username"]');
    const passwordInput = page.locator('input[name="password"]');
    const submitButton = page.locator('button[type="submit"]');
    
    await expect(usernameInput).toBeVisible();
    await expect(passwordInput).toBeVisible();
    await expect(submitButton).toBeVisible();
    
    console.log('Login form elements found');
    
    // Check for CSRF token
    const csrfToken = page.locator('input[name="_token"]');
    if (await csrfToken.isVisible()) {
        const tokenValue = await csrfToken.getAttribute('value');
        console.log('CSRF token found:', tokenValue);
    } else {
        console.log('No CSRF token found');
    }
    
    // Fill credentials
    await usernameInput.fill('kevin');
    await passwordInput.fill('(130Bpm)');
    
    // Take screenshot before submit
    await page.screenshot({ path: 'testing/results/before-login-submit.png' });
    
    // Submit form
    await submitButton.click();
    
    // Wait a bit for response
    await page.waitForTimeout(3000);
    
    // Take screenshot after submit
    await page.screenshot({ path: 'testing/results/after-login-submit.png' });
    
    // Check current URL
    console.log('Current URL after login:', page.url());
    
    // Check for error messages
    const errorMessage = page.locator('.text-red-500, .error, .alert-danger');
    if (await errorMessage.isVisible()) {
        const errorText = await errorMessage.textContent();
        console.log('Error message:', errorText);
    }
    
    // Check if we got redirected to dashboard
    if (page.url().includes('/admin/dashboard')) {
        console.log('✓ Login successful - redirected to dashboard');
    } else {
        console.log('❌ Login failed - still on login page or other location');
    }
});