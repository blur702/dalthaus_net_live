const { test, expect } = require('@playwright/test');

test.describe('URL Alias Database Check', () => {
  test('verify actual URL aliases in database', async ({ page }) => {
    console.log('🔍 Checking actual URL aliases in database...');
    
    // Navigate to admin login
    await page.goto('https://dalthaus.net/admin/login');
    
    // Login with admin credentials
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    
    // Wait for redirect to dashboard
    await page.waitForURL(/admin\/dashboard/);
    console.log('✅ Successfully logged in');
    
    // Navigate to admin articles to see the actual data
    await page.goto('https://dalthaus.net/admin/articles');
    await page.waitForLoadState('networkidle');
    
    // Get all rows and extract data
    const rows = await page.locator('tbody tr');
    const rowCount = await rows.count();
    
    console.log(`📊 Found ${rowCount} articles in the admin table`);
    
    for (let i = 0; i < Math.min(rowCount, 5); i++) {
      const row = rows.nth(i);
      
      // Get the title
      const titleElement = await row.locator('td').first();
      const titleText = await titleElement.textContent();
      const title = titleText.split('\n')[0].trim(); // Get just the title part
      
      // Get the View link
      const viewLink = await row.locator('a').filter({ hasText: /^View$/i });
      const href = await viewLink.getAttribute('href');
      
      console.log(`\n📄 Article ${i + 1}:`);
      console.log(`   Title: "${title}"`);
      console.log(`   View Link: ${href}`);
      
      // Extract the alias from the href
      if (href && href.includes('/articles/')) {
        const alias = href.replace('/articles/', '');
        console.log(`   Extracted alias: "${alias}"`);
      }
    }
    
    // Now try to test what the actual working URL pattern should be
    console.log('\n🧪 Testing different URL patterns with known article aliases...');
    
    const testAliases = [
      'how-i-learned-to-stop-worrying-protecting-your-work-form-ai',
      'how-i-learned-to-stop-worrying-understanding-ai-imaging',
      'photography-s-new-paradigm'
    ];
    
    for (const alias of testAliases) {
      console.log(`\n🔍 Testing alias: "${alias}"`);
      
      const urlPatterns = [
        `https://dalthaus.net/article/${alias}`,
        `https://dalthaus.net/${alias}`,
        `https://dalthaus.net/content/${alias}`,
        `https://dalthaus.net/articles/${alias}`
      ];
      
      for (const url of urlPatterns) {
        try {
          const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 5000 });
          const status = response.status();
          
          if (status === 200) {
            console.log(`   ✅ SUCCESS: ${url} (Status: ${status})`);
            const title = await page.title();
            console.log(`      Page title: ${title}`);
          } else {
            console.log(`   ❌ FAILED: ${url} (Status: ${status})`);
          }
        } catch (error) {
          console.log(`   ❌ ERROR: ${url} - ${error.message}`);
        }
      }
    }
  });
});