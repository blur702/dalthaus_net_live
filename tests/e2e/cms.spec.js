const { test, expect } = require('@playwright/test');

test.describe('CMS E2E Tests', () => {
  // Test data
  const adminUser = {
    username: 'kevin',
    password: '(130Bpm)'
  };

  test('Admin login works', async ({ page }) => {
    await page.goto('/admin/login');
    
    // Fill login form
    await page.fill('input[name="username"]', adminUser.username);
    await page.fill('input[name="password"]', adminUser.password);
    
    // Submit form
    await page.click('button[type="submit"]');
    
    // Should redirect to dashboard
    await expect(page).toHaveURL(/.*\/admin\/dashboard/);
    await expect(page.locator('text=Dashboard')).toBeVisible();
  });

  test('Create article without TinyMCE errors', async ({ page }) => {
    // First login
    await page.goto('/admin/login');
    await page.fill('input[name="username"]', adminUser.username);
    await page.fill('input[name="password"]', adminUser.password);
    await page.click('button[type="submit"]');
    
    // Navigate to create article
    await page.goto('/admin/content/create?type=article');
    
    // Check that TinyMCE loads without errors
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });
    
    // Wait for TinyMCE to initialize
    await page.waitForTimeout(2000);
    
    // Fill form
    await page.fill('input[name="title"]', 'Test Article ' + Date.now());
    await page.fill('input[name="url_alias"]', 'test-article-' + Date.now());
    
    // Fill TinyMCE content
    const iframe = page.frameLocator('iframe').first();
    await iframe.locator('body').fill('This is test content for the article.');
    
    // Save as draft
    await page.click('button[value="draft"]');
    
    // Check for validation errors
    const errorMessage = page.locator('text=Please fix the validation errors below');
    const hasValidationError = await errorMessage.isVisible().catch(() => false);
    
    // Should not have validation errors
    expect(hasValidationError).toBe(false);
    
    // Check console errors - ignore browser extension errors
    const relevantErrors = consoleErrors.filter(err => 
      !err.includes('webcomponents-ce.js') && 
      !err.includes('overlay_bundle.js')
    );
    expect(relevantErrors).toHaveLength(0);
  });

  test('Dashboard loads without TinyMCE', async ({ page }) => {
    // Login
    await page.goto('/admin/login');
    await page.fill('input[name="username"]', adminUser.username);
    await page.fill('input[name="password"]', adminUser.password);
    await page.click('button[type="submit"]');
    
    // Go to dashboard
    await page.goto('/admin/dashboard');
    
    // Check that TinyMCE script is NOT loaded on dashboard
    const tinymceScript = await page.locator('script[src*="tinymce"]').count();
    expect(tinymceScript).toBe(0);
    
    // Dashboard should load properly
    await expect(page.locator('text=Dashboard')).toBeVisible();
    await expect(page.locator('text=Recent Content')).toBeVisible();
  });

  test('Content listing page works', async ({ page }) => {
    // Login
    await page.goto('/admin/login');
    await page.fill('input[name="username"]', adminUser.username);
    await page.fill('input[name="password"]', adminUser.password);
    await page.click('button[type="submit"]');
    
    // Go to content listing
    await page.goto('/admin/content');
    
    // Should show content listing
    await expect(page.locator('h2:has-text("Content Management")')).toBeVisible();
    
    // TinyMCE should NOT be loaded here either
    const tinymceScript = await page.locator('script[src*="tinymce"]').count();
    expect(tinymceScript).toBe(0);
  });

  test('Image upload works', async ({ page }) => {
    // Login
    await page.goto('/admin/login');
    await page.fill('input[name="username"]', adminUser.username);
    await page.fill('input[name="password"]', adminUser.password);
    await page.click('button[type="submit"]');
    
    // Navigate to create article
    await page.goto('/admin/content/create?type=article');
    
    // Fill basic fields
    await page.fill('input[name="title"]', 'Article with Image ' + Date.now());
    await page.fill('input[name="url_alias"]', 'article-image-' + Date.now());
    
    // Create a test image file
    const buffer = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==', 'base64');
    
    // Upload featured image
    await page.setInputFiles('input[name="featured_image"]', {
      name: 'test.png',
      mimeType: 'image/png',
      buffer: buffer
    });
    
    // Fill TinyMCE content
    const iframe = page.frameLocator('iframe').first();
    await iframe.locator('body').fill('Article with image upload test.');
    
    // Save
    await page.click('button[value="draft"]');
    
    // Should not have "Null byte detected" error
    const nullByteError = page.locator('text=Null byte detected');
    const hasNullByteError = await nullByteError.isVisible().catch(() => false);
    expect(hasNullByteError).toBe(false);
  });
});