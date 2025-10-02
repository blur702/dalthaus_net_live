const { test, expect } = require('@playwright/test');

test.describe('Capture Admin Articles Current State', () => {
  test('capture screenshot and details of admin articles page', async ({ page }) => {
    console.log('📸 Capturing current state of admin articles page...');
    
    // Navigate to admin login
    await page.goto('https://dalthaus.net/admin/login');
    
    // Login with admin credentials
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    
    // Wait for redirect to dashboard
    await page.waitForURL(/admin\/dashboard/);
    console.log('✅ Successfully logged in');
    
    // Navigate to admin articles
    await page.goto('https://dalthaus.net/admin/articles');
    await page.waitForLoadState('networkidle');
    
    // Take a screenshot
    await page.screenshot({ 
      path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/admin-articles-view-links-current-state.png',
      fullPage: true 
    });
    
    console.log('📸 Screenshot saved to testing/screenshots/admin-articles-view-links-current-state.png');
    
    // Get all View links and their targets
    const viewLinks = await page.locator('a').filter({ hasText: /^View$/i });
    const linkCount = await viewLinks.count();
    
    console.log(`\n🔗 Current View Links Analysis (${linkCount} total):`);
    
    for (let i = 0; i < linkCount; i++) {
      const link = viewLinks.nth(i);
      const href = await link.getAttribute('href');
      
      // Get the article title from the same row
      const row = link.locator('xpath=ancestor::tr[1]');
      const firstCell = await row.locator('td').first().textContent();
      const title = firstCell.split('\n')[0].trim();
      
      console.log(`\n   ${i + 1}. "${title}"`);
      console.log(`      Current URL: ${href}`);
      console.log(`      Should be: /article/${href.replace('/articles/', '')}`);
    }
    
    console.log('\n✅ Analysis complete!');
  });
});