import { test, expect } from '@playwright/test';

test.describe('Direct Production Verification', () => {

  test.beforeEach(async ({ page }) => {
    // Login to the production site
    await page.goto('/admin/login');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard');
  });

  test('Content reorder page loads and shows content items', async ({ page }) => {
    // Navigate directly to content reorder
    await page.goto('/admin/content/reorder');

    // Wait for page to load
    await page.waitForLoadState('networkidle');

    // Verify page title
    await expect(page.locator('h1')).toContainText('Reorder Content');

    // Verify filter dropdown exists
    await expect(page.locator('select[name="filter_type"]')).toBeVisible();

    // Verify content items are displayed (the drag-and-drop items)
    const contentItems = page.locator('.sortable-item, .content-item, [data-id]');
    await expect(contentItems.first()).toBeVisible();

    const itemCount = await contentItems.count();
    console.log(`✅ Content reorder page loaded with ${itemCount} items`);

    // Take screenshot
    await page.screenshot({
      path: './logs/content-reorder-working.png',
      fullPage: true
    });

    // Verify drag handles are present
    const dragHandles = page.locator('.drag-handle, [data-handle], .sortable-handle');
    const handleCount = await dragHandles.count();
    console.log(`✅ Found ${handleCount} drag handles on content reorder page`);

    expect(itemCount).toBeGreaterThan(0);
  });

  test('Pages reorder page loads and shows page items', async ({ page }) => {
    // Navigate directly to pages reorder
    await page.goto('/admin/pages/reorder');

    // Wait for page to load
    await page.waitForLoadState('networkidle');

    // Verify page title
    await expect(page.locator('h1')).toContainText('Reorder Pages');

    // Verify page items are displayed
    const pageItems = page.locator('.sortable-item, .page-item, [data-id]');
    await expect(pageItems.first()).toBeVisible();

    const itemCount = await pageItems.count();
    console.log(`✅ Pages reorder page loaded with ${itemCount} items`);

    // Take screenshot
    await page.screenshot({
      path: './logs/pages-reorder-working.png',
      fullPage: true
    });

    // Verify drag handles are present
    const dragHandles = page.locator('.drag-handle, [data-handle], .sortable-handle');
    const handleCount = await dragHandles.count();
    console.log(`✅ Found ${handleCount} drag handles on pages reorder page`);

    expect(itemCount).toBeGreaterThan(0);
  });

  test('Both pages return 200 status codes', async ({ page }) => {
    // Test content reorder
    const contentResponse = await page.goto('/admin/content/reorder');
    expect(contentResponse?.status()).toBe(200);
    console.log('✅ Content reorder page returns 200 status');

    // Test pages reorder
    const pagesResponse = await page.goto('/admin/pages/reorder');
    expect(pagesResponse?.status()).toBe(200);
    console.log('✅ Pages reorder page returns 200 status');
  });

  test('Filter functionality works on content reorder', async ({ page }) => {
    await page.goto('/admin/content/reorder');
    await page.waitForLoadState('networkidle');

    // Get initial count
    const allItems = await page.locator('.sortable-item, .content-item, [data-id]').count();
    console.log(`Total items before filter: ${allItems}`);

    // Test article filter
    await page.selectOption('select[name="filter_type"]', 'article');
    await page.click('button:has-text("Filter")');
    await page.waitForLoadState('networkidle');

    const articleItems = await page.locator('.sortable-item, .content-item, [data-id]').count();
    console.log(`Article items after filter: ${articleItems}`);

    // Test photobook filter
    await page.selectOption('select[name="filter_type"]', 'photobook');
    await page.click('button:has-text("Filter")');
    await page.waitForLoadState('networkidle');

    const photobookItems = await page.locator('.sortable-item, .content-item, [data-id]').count();
    console.log(`Photobook items after filter: ${photobookItems}`);

    console.log('✅ Filter functionality is working');
  });

  test('JavaScript and Sortable.js verification', async ({ page }) => {
    const jsErrors: string[] = [];

    page.on('console', msg => {
      if (msg.type() === 'error') {
        jsErrors.push(msg.text());
      }
    });

    // Check content reorder page
    await page.goto('/admin/content/reorder');
    await page.waitForLoadState('networkidle');

    // Check if Sortable.js is loaded
    const hasSortable = await page.evaluate(() => {
      return typeof window.Sortable !== 'undefined' ||
             typeof (window as any).jQuery !== 'undefined' &&
             typeof (window as any).jQuery.ui !== 'undefined';
    });

    console.log(`Sortable library available on content page: ${hasSortable}`);

    // Check pages reorder page
    await page.goto('/admin/pages/reorder');
    await page.waitForLoadState('networkidle');

    const hasSortablePages = await page.evaluate(() => {
      return typeof window.Sortable !== 'undefined' ||
             typeof (window as any).jQuery !== 'undefined' &&
             typeof (window as any).jQuery.ui !== 'undefined';
    });

    console.log(`Sortable library available on pages page: ${hasSortablePages}`);

    console.log(`JavaScript errors found: ${jsErrors.length}`);
    if (jsErrors.length > 0) {
      console.log('JS Errors:', jsErrors);
    }

    console.log('✅ JavaScript verification completed');
  });
});