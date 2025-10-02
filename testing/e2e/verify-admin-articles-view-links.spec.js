const { test, expect } = require('@playwright/test');

test.describe('Admin Articles View Links Verification', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to login page
    await page.goto('https://dalthaus.net/admin/login');
    
    // Login with admin credentials
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    
    // Wait for redirect to dashboard
    await page.waitForURL('**/admin/dashboard');
    
    // Navigate to admin articles page
    await page.goto('https://dalthaus.net/admin/articles');
    await page.waitForLoadState('networkidle');
  });

  test('should verify View links generate correct URLs and work properly', async ({ page }) => {
    console.log('Starting verification of admin articles View links...');
    
    // Wait for the articles table to load
    await page.waitForSelector('table', { timeout: 10000 });
    
    // Get all View links in the table
    const viewLinks = await page.locator('a:has-text("View")').all();
    console.log(`Found ${viewLinks.length} View links to test`);
    
    // Verify we have at least some View links to test
    expect(viewLinks.length).toBeGreaterThan(0);
    
    const testResults = [];
    let articleLinksFound = 0;
    const maxArticleLinksToTest = 5; // Test up to 5 article links
    
    for (let i = 0; i < viewLinks.length && articleLinksFound < maxArticleLinksToTest; i++) {
      const link = viewLinks[i];
      
      // Get the href attribute
      const href = await link.getAttribute('href');
      console.log(`Testing View link ${i + 1}: ${href}`);
      
      // Skip non-article links (like homepage "/")
      if (!href.includes('/article/')) {
        console.log(`Skipping non-article link: ${href}`);
        continue;
      }
      
      // Verify the URL format is correct (/article/ not /articles/)
      expect(href).toMatch(/\/article\/[^\/]+$/);
      expect(href).not.toContain('/articles/');
      
      // Extract article alias from URL for reporting
      const aliasMatch = href.match(/\/article\/([^\/]+)$/);
      const alias = aliasMatch ? aliasMatch[1] : 'unknown';
      
      // Click the link and verify it works
      const [newPage] = await Promise.all([
        page.context().waitForEvent('page'),
        link.click()
      ]);
      
      // Wait for the new page to load
      await newPage.waitForLoadState('networkidle');
      
      // Check that we didn't get a 404 error
      const response = newPage.url();
      console.log(`Navigated to: ${response}`);
      
      // Verify we're on the correct article page (compare path only)
      const expectedUrl = `https://dalthaus.net${href}`;
      expect(newPage.url()).toBe(expectedUrl);
      
      // Check for 404 indicators
      const pageContent = await newPage.content();
      expect(pageContent).not.toContain('404');
      expect(pageContent).not.toContain('Not Found');
      expect(pageContent).not.toContain('Page not found');
      
      // Verify the page has article content (look for typical article elements)
      const hasArticleContent = await newPage.locator('article, .article-content, .content, h1, h2').count() > 0;
      expect(hasArticleContent).toBe(true);
      
      articleLinksFound++;
      testResults.push({
        linkNumber: articleLinksFound,
        href: href,
        alias: alias,
        status: 'PASS',
        finalUrl: newPage.url()
      });
      
      console.log(`✓ Article View link ${articleLinksFound} (${alias}) working correctly`);
      
      // Close the new page
      await newPage.close();
      
      // Small delay between tests
      await page.waitForTimeout(1000);
    }
    
    // Generate summary report
    console.log('\n=== VERIFICATION SUMMARY ===');
    console.log(`Total View links tested: ${testResults.length}`);
    console.log(`All links using correct URL format: ✓`);
    console.log(`All links working without 404 errors: ✓`);
    
    testResults.forEach(result => {
      console.log(`${result.linkNumber}. ${result.alias} - ${result.status} (${result.href})`);
    });
    
    // Verify all tests passed
    expect(testResults.every(result => result.status === 'PASS')).toBe(true);
  });

  test('should verify no View links use the old /articles/ format', async ({ page }) => {
    console.log('Checking for any remaining /articles/ URLs in View links...');
    
    // Wait for the articles table to load
    await page.waitForSelector('table', { timeout: 10000 });
    
    // Get all View links
    const viewLinks = await page.locator('a:has-text("View")').all();
    
    for (let i = 0; i < viewLinks.length; i++) {
      const href = await viewLinks[i].getAttribute('href');
      
      // Ensure no View link uses the old /articles/ format
      expect(href).not.toContain('/articles/');
      console.log(`✓ Link ${i + 1}: ${href} - Correct format`);
    }
    
    console.log(`Verified ${viewLinks.length} View links all use correct /article/ format`);
  });

  test('should take screenshot of admin articles page for documentation', async ({ page }) => {
    // Wait for the page to fully load
    await page.waitForSelector('table', { timeout: 10000 });
    
    // Take screenshot for documentation
    await page.screenshot({ 
      path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/admin-articles-view-links-verified.png',
      fullPage: true 
    });
    
    console.log('Screenshot saved to testing/screenshots/admin-articles-view-links-verified.png');
  });
});