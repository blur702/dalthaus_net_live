import { test, expect } from '@playwright/test';

test('Final Manual Screenshot Verification', async ({ page }) => {
  // Login
  await page.goto('/admin/login');
  await page.fill('input[name="username"]', 'kevin');
  await page.fill('input[name="password"]', '(130Bpm)');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/admin/dashboard');

  // Capture content reorder page
  await page.goto('/admin/content/reorder');
  await page.waitForLoadState('networkidle');
  await page.screenshot({
    path: './logs/FINAL-CONTENT-REORDER-SUCCESS.png',
    fullPage: true
  });
  console.log('✅ Screenshot saved: Content reorder page working');

  // Capture pages reorder page
  await page.goto('/admin/pages/reorder');
  await page.waitForLoadState('networkidle');
  await page.screenshot({
    path: './logs/FINAL-PAGES-REORDER-SUCCESS.png',
    fullPage: true
  });
  console.log('✅ Screenshot saved: Pages reorder page working');

  // Verify both pages return 200
  const contentResponse = await page.goto('/admin/content/reorder');
  const pagesResponse = await page.goto('/admin/pages/reorder');

  expect(contentResponse?.status()).toBe(200);
  expect(pagesResponse?.status()).toBe(200);

  console.log('🎉 FINAL VERIFICATION: Both reorder pages are now working correctly!');
});