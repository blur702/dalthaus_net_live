import { test, expect } from '@playwright/test';

test.describe('Debug URL Alias Values', () => {
  test('Check if articles have url_alias values', async ({ page }) => {
    // Navigate to the admin login page
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('networkidle');
    
    // Fill in the login form
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    
    // Submit the login form
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Navigate to admin articles page
    await page.goto('https://dalthaus.net/admin/articles');
    await page.waitForLoadState('networkidle');
    
    // Find all View links and examine their href attributes
    const viewLinks = await page.locator('a:has-text("View")').all();
    
    console.log('\n=== SIMPLIFIED VIEW LINKS ANALYSIS ===');
    console.log(`Found ${viewLinks.length} View links on the page`);
    
    for (let i = 0; i < viewLinks.length; i++) {
      const link = viewLinks[i];
      const href = await link.getAttribute('href');
      
      console.log(`View Link ${i + 1}: href="${href}"`);
      
      // Check if href is just "/" (which indicates empty url_alias)
      if (href === '/') {
        console.log(`  ⚠️ WARNING: View link ${i + 1} has empty url_alias!`);
      } else if (href && href.startsWith('/article/')) {
        const alias = href.replace('/article/', '');
        console.log(`  ✅ View link ${i + 1} has url_alias: "${alias}"`);
      } else {
        console.log(`  ❌ Unexpected href format: "${href}"`);
      }
    }
    
    // Also check the table data to see if we can extract article information
    const tableRows = await page.locator('table tbody tr').all();
    
    console.log('\n=== ARTICLE TABLE DATA ===');
    console.log(`Found ${tableRows.length} article rows`);
    
    for (let i = 0; i < tableRows.length; i++) {
      const row = tableRows[i];
      const titleElement = await row.locator('td:first-child .text-sm.font-medium').first();
      const title = await titleElement.textContent();
      const viewLink = await row.locator('a:has-text("View")').first();
      const href = await viewLink.getAttribute('href');
      
      console.log(`Article ${i + 1}:`);
      console.log(`  Title: "${title?.trim()}"`);
      console.log(`  View Link: "${href}"`);
    }
    
    // Take a screenshot for reference
    await page.screenshot({ 
      path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/url-alias-debug.png',
      fullPage: true 
    });
  });
});