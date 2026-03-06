const { test, expect } = require('@playwright/test');

test('Simple Production Login Test', async ({ page }) => {
    // Navigate to login
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('networkidle');

    // Fill credentials
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');

    // Submit and wait for navigation
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard', { timeout: 15000 });

    // Verify we're on dashboard
    const url = page.url();
    console.log('Final URL:', url);
    expect(url).toContain('/admin/dashboard');
});
