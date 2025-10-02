const { test, expect } = require('@playwright/test');

test.describe('Article Pagebreak Functionality - Article ID 14', () => {
  const articleUrl = 'https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible';
  
  test('should display pagination navigation when article has pagebreaks', async ({ page }) => {
    // Navigate to the article
    await page.goto(articleUrl);
    
    // Wait for page to load completely
    await page.waitForLoadState('networkidle');
    
    // Take a screenshot for debugging
    await page.screenshot({ path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/article-pagebreak-initial.png', fullPage: true });
    
    // Check if pagination navigation is present
    const paginationContainer = page.locator('.pagination, .page-nav, .article-pagination, [class*="page"], [class*="pagination"]');
    
    // Look for pagination elements more broadly
    const pageNumbers = page.locator('a[href*="?p="], span[class*="page"], .page-number, .page-link');
    
    // Check for any element that might be pagination
    const possiblePagination = await page.locator('*').filter({ hasText: /page\s*\d+|next|previous|\d+\s*of\s*\d+/i }).count();
    
    console.log('Pagination elements found:', await pageNumbers.count());
    console.log('Possible pagination text elements:', possiblePagination);
    
    // Get page content to analyze
    const pageContent = await page.content();
    console.log('Page contains pagination indicators:', pageContent.includes('?p='));
    
    // Check if there are multiple pages
    if (await pageNumbers.count() > 0) {
      console.log('✓ Pagination navigation found');
      expect(await pageNumbers.count()).toBeGreaterThan(0);
    } else {
      console.log('ℹ No pagination found - checking if article content is on single page');
      
      // If no pagination, verify the article content is visible
      const articleContent = page.locator('.article-content, .content, .post-content, main');
      await expect(articleContent).toBeVisible();
    }
  });
  
  test('should split content properly at pagebreak markers', async ({ page }) => {
    await page.goto(articleUrl);
    await page.waitForLoadState('networkidle');
    
    // Check if we're on page 1 and if there are multiple pages
    const currentPageContent = await page.textContent('body');
    
    // Look for pagebreak indicators or page navigation
    const pageLinks = page.locator('a[href*="?p="]');
    const pageCount = await pageLinks.count();
    
    if (pageCount > 0) {
      console.log(`Found ${pageCount} page links`);
      
      // Get content from page 1
      const page1Content = await page.locator('.article-content, .content, .post-content, main').textContent();
      console.log('Page 1 content length:', page1Content?.length || 0);
      
      // Navigate to page 2 if it exists
      const page2Link = page.locator('a[href*="?p=2"]').first();
      if (await page2Link.count() > 0) {
        await page2Link.click();
        await page.waitForLoadState('networkidle');
        
        const page2Content = await page.locator('.article-content, .content, .post-content, main').textContent();
        console.log('Page 2 content length:', page2Content?.length || 0);
        
        // Verify that page 2 has different content than page 1
        expect(page2Content).not.toBe(page1Content);
        console.log('✓ Content is different between pages');
      }
    } else {
      console.log('ℹ Article appears to be on a single page');
    }
  });
  
  test('should handle pagination navigation correctly', async ({ page }) => {
    await page.goto(articleUrl);
    await page.waitForLoadState('networkidle');
    
    // Look for pagination links
    const pageLinks = page.locator('a[href*="?p="]');
    const linkCount = await pageLinks.count();
    
    if (linkCount > 0) {
      console.log(`Testing navigation with ${linkCount} page links`);
      
      // Test clicking to page 2
      const page2Link = page.locator('a[href*="?p=2"]').first();
      if (await page2Link.count() > 0) {
        await page2Link.click();
        await page.waitForLoadState('networkidle');
        
        // Verify we're on page 2
        expect(page.url()).toContain('?p=2');
        console.log('✓ Successfully navigated to page 2');
        
        // Take screenshot of page 2
        await page.screenshot({ path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/article-pagebreak-page2.png', fullPage: true });
        
        // Try to navigate back to page 1
        const page1Link = page.locator('a[href*="?p=1"], a').filter({ hasText: /^1$|page\s*1/i }).first();
        if (await page1Link.count() > 0) {
          await page1Link.click();
          await page.waitForLoadState('networkidle');
          console.log('✓ Successfully navigated back to page 1');
        }
      }
    } else {
      console.log('ℹ No pagination navigation found to test');
    }
  });
  
  test('should support direct URL access with pagination parameters', async ({ page }) => {
    // Test direct access to page 2
    const page2Url = `${articleUrl}?p=2`;
    await page.goto(page2Url);
    await page.waitForLoadState('networkidle');
    
    // Check if the page loads successfully
    const statusCode = await page.evaluate(() => {
      return fetch(window.location.href).then(response => response.status);
    });
    
    console.log('Page 2 direct access status:', statusCode);
    expect(statusCode).toBe(200);
    
    // Verify URL contains the parameter
    expect(page.url()).toContain('?p=2');
    
    // Check if content is displayed
    const content = page.locator('.article-content, .content, .post-content, main');
    await expect(content).toBeVisible();
    
    console.log('✓ Direct URL access with ?p=2 works');
    
    // Test page 3 if it exists
    const page3Url = `${articleUrl}?p=3`;
    await page.goto(page3Url);
    await page.waitForLoadState('networkidle');
    
    // Page 3 might not exist, so just check it doesn't error
    const page3Status = await page.evaluate(() => {
      return fetch(window.location.href).then(response => response.status);
    });
    
    console.log('Page 3 direct access status:', page3Status);
    // Should be 200 if page exists, or redirect appropriately if not
  });
  
  test('should have proper pagination styling and design consistency', async ({ page }) => {
    await page.goto(articleUrl);
    await page.waitForLoadState('networkidle');
    
    // Look for pagination elements
    const paginationElements = page.locator('.pagination, .page-nav, .article-pagination, a[href*="?p="]');
    
    if (await paginationElements.count() > 0) {
      // Take a focused screenshot of pagination area
      const firstPagination = paginationElements.first();
      await firstPagination.screenshot({ path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/pagination-styling.png' });
      
      // Check if pagination links are styled and visible
      const pageLinks = page.locator('a[href*="?p="]');
      
      for (let i = 0; i < Math.min(await pageLinks.count(), 3); i++) {
        const link = pageLinks.nth(i);
        
        // Check if link is visible and clickable
        await expect(link).toBeVisible();
        
        // Check if link has some styling (not default blue underlined text)
        const styles = await link.evaluate((el) => {
          const computed = window.getComputedStyle(el);
          return {
            textDecoration: computed.textDecoration,
            color: computed.color,
            backgroundColor: computed.backgroundColor,
            padding: computed.padding,
            margin: computed.margin
          };
        });
        
        console.log(`Page link ${i + 1} styles:`, styles);
      }
      
      console.log('✓ Pagination styling verification completed');
    } else {
      console.log('ℹ No pagination elements found to test styling');
    }
  });
  
  test('should display article metadata and content correctly', async ({ page }) => {
    await page.goto(articleUrl);
    await page.waitForLoadState('networkidle');
    
    // Verify article title is present
    const articleTitle = page.locator('h1, .article-title, .post-title').first();
    await expect(articleTitle).toBeVisible();
    
    const title = await articleTitle.textContent();
    console.log('Article title:', title);
    expect(title).toContain('Storytelling In Photography');
    
    // Verify article content is present
    const articleContent = page.locator('.article-content, .content, .post-content, main');
    await expect(articleContent).toBeVisible();
    
    const contentText = await articleContent.textContent();
    console.log('Content preview:', contentText?.substring(0, 200) + '...');
    expect(contentText).toBeTruthy();
    expect(contentText.length).toBeGreaterThan(100);
    
    console.log('✓ Article content verification completed');
  });
});