import { test, expect } from '@playwright/test';

test.describe('Content and Page Reordering Tests', () => {
  // Test configuration
  const baseUrl = 'http://localhost:8000';
  const adminCredentials = {
    username: 'kevin',
    password: '(130Bpm)'
  };

  test.beforeEach(async ({ page }) => {
    // Login to admin panel
    await page.goto(`${baseUrl}/admin/login`);
    await page.fill('input[name="username"]', adminCredentials.username);
    await page.fill('input[name="password"]', adminCredentials.password);
    await page.click('button[type="submit"]');

    // Wait for dashboard to load
    await page.waitForURL(`${baseUrl}/admin/dashboard`);
    await expect(page.locator('h1')).toContainText('Dashboard');
  });

  test('Content reorder page loads correctly', async ({ page }) => {
    // Navigate to content reorder page
    await page.goto(`${baseUrl}/admin/content/reorder`);

    // Check page loaded correctly
    await expect(page.locator('h2')).toContainText('Reorder Content');

    // Check for filter dropdown
    const filterDropdown = page.locator('select#type_filter');
    await expect(filterDropdown).toBeVisible();

    // Check filter options
    const options = await filterDropdown.locator('option').allTextContents();
    expect(options).toContain('All Content');
    expect(options).toContain('Articles Only');
    expect(options).toContain('Photobooks Only');

    // Check for sortable items or empty state
    const sortableContent = page.locator('#sortable-content');
    const emptyState = page.locator('text=No content to reorder');

    const hasSortableContent = await sortableContent.count() > 0;
    const hasEmptyState = await emptyState.count() > 0;

    expect(hasSortableContent || hasEmptyState).toBeTruthy();

    // Check for save button
    const saveButton = page.locator('button:has-text("Save New Order")');
    await expect(saveButton).toBeVisible();
  });

  test('Pages reorder page loads correctly', async ({ page }) => {
    // Navigate to pages reorder page
    await page.goto(`${baseUrl}/admin/pages/reorder`);

    // Check page loaded correctly
    await expect(page.locator('h2')).toContainText('Reorder Pages');

    // Check for instructions
    await expect(page.locator('text=Drag pages to reorder them')).toBeVisible();

    // Check for sortable items or empty state
    const sortablePages = page.locator('#sortable-pages');
    const emptyState = page.locator('text=No pages to reorder');

    const hasSortablePages = await sortablePages.count() > 0;
    const hasEmptyState = await emptyState.count() > 0;

    expect(hasSortablePages || hasEmptyState).toBeTruthy();

    // Check for save button
    const saveButton = page.locator('button:has-text("Save New Order")');
    await expect(saveButton).toBeVisible();
  });

  test('Content management page has reorder button', async ({ page }) => {
    // Navigate to content management
    await page.goto(`${baseUrl}/admin/content`);

    // Check for reorder button
    const reorderButton = page.locator('a:has-text("Reorder")');
    await expect(reorderButton).toBeVisible();

    // Click reorder button
    await reorderButton.click();

    // Should navigate to reorder page
    await expect(page).toHaveURL(`${baseUrl}/admin/content/reorder`);
    await expect(page.locator('h2')).toContainText('Reorder Content');
  });

  test('Pages management page has reorder button', async ({ page }) => {
    // Navigate to pages management
    await page.goto(`${baseUrl}/admin/pages`);

    // Check for reorder button
    const reorderButton = page.locator('a:has-text("Reorder")');
    await expect(reorderButton).toBeVisible();

    // Click reorder button
    await reorderButton.click();

    // Should navigate to reorder page
    await expect(page).toHaveURL(`${baseUrl}/admin/pages/reorder`);
    await expect(page.locator('h2')).toContainText('Reorder Pages');
  });

  test('Content filter works correctly', async ({ page }) => {
    // Navigate to content reorder page
    await page.goto(`${baseUrl}/admin/content/reorder`);

    // Test filtering by articles
    await page.selectOption('select#type_filter', 'article');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveURL(`${baseUrl}/admin/content/reorder?type=article`);

    // Test filtering by photobooks
    await page.selectOption('select#type_filter', 'photobook');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveURL(`${baseUrl}/admin/content/reorder?type=photobook`);

    // Test clearing filter
    await page.selectOption('select#type_filter', '');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveURL(`${baseUrl}/admin/content/reorder`);
  });

  test('Sortable.js library is loaded', async ({ page }) => {
    // Navigate to content reorder page
    await page.goto(`${baseUrl}/admin/content/reorder`);

    // Check if Sortable is defined
    const sortableExists = await page.evaluate(() => {
      return typeof window.Sortable !== 'undefined';
    });

    expect(sortableExists).toBeTruthy();
  });

  test('Back buttons work correctly', async ({ page }) => {
    // Test content reorder back button
    await page.goto(`${baseUrl}/admin/content/reorder`);
    const contentBackButton = page.locator('a:has-text("Back to Content")');
    await expect(contentBackButton).toBeVisible();
    await contentBackButton.click();
    await expect(page).toHaveURL(`${baseUrl}/admin/content`);

    // Test pages reorder back button
    await page.goto(`${baseUrl}/admin/pages/reorder`);
    const pagesBackButton = page.locator('a:has-text("Back to Pages")');
    await expect(pagesBackButton).toBeVisible();
    await pagesBackButton.click();
    await expect(page).toHaveURL(`${baseUrl}/admin/pages`);
  });
});