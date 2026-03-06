const { test, expect } = require('@playwright/test');

test.describe('Production Media Page Access Test', () => {
  test('should login to production and access /admin/media page', async ({ page }) => {
    console.log('Starting production media page access test...');

    // Navigate to production login page
    await page.goto('https://dalthaus.net/admin/login');
    console.log('✓ Navigated to login page');

    // Wait for the page to load
    await page.waitForLoadState('networkidle');

    // Fill in login credentials
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    console.log('✓ Filled in credentials');

    // Submit the form
    await page.click('button[type="submit"]');
    console.log('✓ Clicked submit button');

    // Wait for redirect to dashboard
    await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
    console.log('✓ Redirected to dashboard');

    // Verify we're logged in by checking for dashboard content
    await expect(page.locator('body')).toContainText('Welcome to your CMS admin panel');
    console.log('✓ Dashboard loaded successfully');

    // Now navigate to the media page
    console.log('Navigating to /admin/media...');
    await page.goto('https://dalthaus.net/admin/media');

    // Wait for page to load
    await page.waitForLoadState('networkidle');

    // Verify we're NOT redirected to login
    const currentUrl = page.url();
    console.log('Current URL:', currentUrl);

    if (currentUrl.includes('/admin/login')) {
      console.log('❌ ERROR: Redirected back to login page');
      throw new Error('Redirected to login page - authentication failed');
    }

    // Verify we're on the media page
    await expect(page).toHaveURL(/\/admin\/media/);
    console.log('✓ On media page');

    // Check for media page content
    const bodyText = await page.locator('body').textContent();
    console.log('Page content includes "Media":', bodyText.includes('Media'));

    console.log('✅ Test passed: Successfully accessed /admin/media page');
  });
});
