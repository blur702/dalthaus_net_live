const { test, expect } = require('@playwright/test');

test('verify content reordering page loads without PHP errors', async ({ page }) => {
  console.log('Testing content reordering fix...');

  // Navigate to the live login page
  await page.goto('https://dalthaus.net/admin/login');

  // Fill login form
  await page.fill('input[name="username"]', 'kevin');
  await page.fill('input[name="password"]', '(130Bpm)');

  // Submit login
  await page.click('button[type="submit"]');

  // Wait for redirect to dashboard
  await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
  console.log('✅ Login successful');

  // Navigate to content reordering page
  console.log('🔍 Testing content reordering page...');
  await page.goto('https://dalthaus.net/admin/content/reorder');

  // Check if page loaded successfully (no PHP errors)
  const pageContent = await page.content();

  // Should NOT contain PHP error messages
  expect(pageContent).not.toContain('Fatal error');
  expect(pageContent).not.toContain('Cannot use object of type');
  expect(pageContent).not.toContain('Parse error');
  expect(pageContent).not.toContain('Uncaught exception');

  // Should contain expected content
  expect(pageContent).toContain('Reorder Content');
  expect(pageContent).toContain('Drag items to reorder');

  // Check if sortable content area exists
  const sortableContent = await page.locator('#sortable-content');
  await expect(sortableContent).toBeVisible();

  console.log('✅ Content reordering page loaded successfully');

  // Also test pages reordering to ensure it still works
  console.log('🔍 Testing pages reordering page...');
  await page.goto('https://dalthaus.net/admin/pages/reorder');

  const pagesContent = await page.content();

  // Should NOT contain PHP error messages
  expect(pagesContent).not.toContain('Fatal error');
  expect(pagesContent).not.toContain('Cannot use object of type');

  // Should contain expected content
  expect(pagesContent).toContain('Reorder Pages');
  expect(pagesContent).toContain('Drag pages to reorder');

  // Check if sortable pages area exists
  const sortablePages = await page.locator('#sortable-pages');
  await expect(sortablePages).toBeVisible();

  console.log('✅ Pages reordering page still works correctly');

  console.log('🎉 CONTENT REORDERING FIX VERIFIED!');
});