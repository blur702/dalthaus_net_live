const { test, expect } = require('@playwright/test');

test.describe('Media Page Access Test', () => {
  test('should login and successfully access /admin/media page', async ({ page }) => {
    // Navigate to login page
    await page.goto('http://localhost:8000/admin/login');

    // Fill in login credentials
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');

    // Submit the form
    await page.click('button[type="submit"]');

    // Wait for redirect to dashboard
    await page.waitForURL('**/admin/dashboard', { timeout: 5000 });

    // Verify we're logged in by checking for dashboard content
    await expect(page.locator('body')).toContainText('Dashboard');

    // Now navigate to the media page
    await page.goto('http://localhost:8000/admin/media');

    // Verify we're NOT redirected to login
    await expect(page).not.toHaveURL(/\/admin\/login/);

    // Verify we're on the media page
    await expect(page).toHaveURL('http://localhost:8000/admin/media');

    // Check for media page content (adjust selector based on actual page)
    await expect(page.locator('body')).toContainText('Media');

    console.log('✓ Successfully logged in and accessed /admin/media page');
  });
});
