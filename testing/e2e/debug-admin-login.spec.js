import { test, expect } from '@playwright/test';

test.describe('Debug Admin Login', () => {
  const baseURL = 'http://localhost:8000';

  test('Check admin login page structure', async ({ page }) => {
    console.log('🔍 Debugging admin login page...');
    
    await page.goto(`${baseURL}/admin/login`, { waitUntil: 'networkidle' });
    console.log('📄 Navigated to admin login page');
    
    // Take screenshot
    await page.screenshot({ 
      path: 'testing/screenshots/admin-login-debug.png',
      fullPage: true 
    });
    
    // Get page content
    const content = await page.content();
    console.log('📝 Page content length:', content.length);
    
    // Check for common form elements
    const usernameField = page.locator('input[name="username"], input[id="username"], input[type="text"]').first();
    const passwordField = page.locator('input[name="password"], input[id="password"], input[type="password"]').first();
    const submitButton = page.locator('button[type="submit"], input[type="submit"]').first();
    
    console.log('🔍 Checking form elements...');
    console.log('Username field exists:', await usernameField.count() > 0);
    console.log('Password field exists:', await passwordField.count() > 0);
    console.log('Submit button exists:', await submitButton.count() > 0);
    
    // Print all form elements
    const allInputs = await page.locator('input').all();
    console.log(`📋 Found ${allInputs.length} input elements:`);
    for (let i = 0; i < allInputs.length; i++) {
      const input = allInputs[i];
      const type = await input.getAttribute('type');
      const name = await input.getAttribute('name');
      const id = await input.getAttribute('id');
      console.log(`  Input ${i + 1}: type="${type}", name="${name}", id="${id}"`);
    }
    
    // Check for any error messages
    const errorElements = page.locator('.error, .alert, .message');
    const errorCount = await errorElements.count();
    console.log(`⚠️  Found ${errorCount} potential error elements`);
    
    if (errorCount > 0) {
      for (let i = 0; i < errorCount; i++) {
        const errorText = await errorElements.nth(i).textContent();
        console.log(`  Error ${i + 1}: ${errorText}`);
      }
    }
    
    // Check page title
    const title = await page.title();
    console.log('📑 Page title:', title);
    
    // Check if we're already logged in (redirect case)
    const currentUrl = page.url();
    console.log('🌐 Current URL:', currentUrl);
    
    if (currentUrl.includes('/admin/dashboard')) {
      console.log('✅ Already logged in, redirected to dashboard');
    }
  });
});