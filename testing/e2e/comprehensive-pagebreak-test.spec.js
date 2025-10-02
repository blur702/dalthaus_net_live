const { test, expect } = require('@playwright/test');

test.describe('Comprehensive Pagebreak Functionality Test', () => {
  const articleUrl = 'https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible';
  
  test('Article with pagebreaks loads correctly and displays page 1 by default', async ({ page }) => {
    await page.goto(articleUrl);
    
    // Wait for page to load completely
    await page.waitForLoadState('networkidle');
    
    // Verify article title is present
    await expect(page.locator('h1')).toContainText('Storytelling in Photography');
    
    // Check that we're on page 1 by default
    const url = page.url();
    console.log('Default URL:', url);
    
    // Look for pagination controls
    const paginationExists = await page.locator('.pagination, .page-nav, [class*="page"]').count() > 0;
    console.log('Pagination controls found:', paginationExists);
    
    if (paginationExists) {
      // Take screenshot of page 1
      await page.screenshot({ 
        path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/pagebreak-page1.png',
        fullPage: true 
      });
    }
    
    // Check content structure
    const content = await page.locator('.content, .article-content, main').first().textContent();
    console.log('Content length on page 1:', content?.length || 0);
    
    // Look for page navigation elements
    const nextButton = await page.locator('a:has-text("Next"), a:has-text("Page 2"), [href*="p=2"]').count();
    console.log('Next page navigation found:', nextButton > 0);
  });

  test('Navigate from page 1 to page 2', async ({ page }) => {
    await page.goto(articleUrl);
    await page.waitForLoadState('networkidle');
    
    // Look for next page link
    const nextPageLink = page.locator('a:has-text("Next"), a:has-text("Page 2"), a:has-text("2"), [href*="p=2"]').first();
    const nextPageExists = await nextPageLink.count() > 0;
    
    if (nextPageExists) {
      console.log('Found next page link, clicking...');
      await nextPageLink.click();
      await page.waitForLoadState('networkidle');
      
      // Verify we're on page 2
      const currentUrl = page.url();
      console.log('Current URL after navigation:', currentUrl);
      expect(currentUrl).toContain('p=2');
      
      // Take screenshot of page 2
      await page.screenshot({ 
        path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/pagebreak-page2.png',
        fullPage: true 
      });
      
      // Verify content is different from page 1
      const page2Content = await page.locator('.content, .article-content, main').first().textContent();
      console.log('Content length on page 2:', page2Content?.length || 0);
      
      // Look for previous page navigation
      const prevButton = await page.locator('a:has-text("Previous"), a:has-text("Page 1"), a:has-text("1"), [href*="p=1"]').count();
      console.log('Previous page navigation found:', prevButton > 0);
    } else {
      console.log('No next page link found - article may be single page or pagination not implemented');
    }
  });

  test('Navigate back from page 2 to page 1', async ({ page }) => {
    // Start on page 2
    await page.goto(`${articleUrl}?p=2`);
    await page.waitForLoadState('networkidle');
    
    // Look for previous page link
    const prevPageLink = page.locator('a:has-text("Previous"), a:has-text("Page 1"), a:has-text("1"), [href*="p=1"], a[href$="?p=1"]').first();
    const prevPageExists = await prevPageLink.count() > 0;
    
    if (prevPageExists) {
      console.log('Found previous page link, clicking...');
      await prevPageLink.click();
      await page.waitForLoadState('networkidle');
      
      // Verify we're back on page 1
      const currentUrl = page.url();
      console.log('Current URL after back navigation:', currentUrl);
      
      // Should either have p=1 or no p parameter (defaults to page 1)
      const isPage1 = currentUrl.includes('p=1') || !currentUrl.includes('p=');
      expect(isPage1).toBe(true);
      
      // Verify content matches page 1
      const page1Content = await page.locator('.content, .article-content, main').first().textContent();
      console.log('Content length back on page 1:', page1Content?.length || 0);
    } else {
      console.log('No previous page link found on page 2');
    }
  });

  test('Direct URL access with page parameters', async ({ page }) => {
    // Test direct access to page 1
    console.log('Testing direct access to page 1...');
    await page.goto(`${articleUrl}?p=1`);
    await page.waitForLoadState('networkidle');
    
    let currentUrl = page.url();
    console.log('Page 1 direct access URL:', currentUrl);
    expect(currentUrl).toContain('p=1');
    
    // Verify page loads correctly
    await expect(page.locator('h1')).toContainText('Storytelling in Photography');
    
    // Test direct access to page 2
    console.log('Testing direct access to page 2...');
    await page.goto(`${articleUrl}?p=2`);
    await page.waitForLoadState('networkidle');
    
    currentUrl = page.url();
    console.log('Page 2 direct access URL:', currentUrl);
    
    // Check if page 2 exists (should either load page 2 or redirect/show error)
    const pageContent = await page.locator('body').textContent();
    const isValidPage = !pageContent?.toLowerCase().includes('not found') && 
                       !pageContent?.toLowerCase().includes('error') &&
                       !pageContent?.toLowerCase().includes('404');
    
    console.log('Page 2 loads successfully:', isValidPage);
    
    if (isValidPage) {
      expect(currentUrl).toContain('p=2');
      await expect(page.locator('h1')).toContainText('Storytelling in Photography');
    }
  });

  test('Invalid page number handling', async ({ page }) => {
    // Test page 0 (invalid)
    console.log('Testing invalid page number: 0...');
    await page.goto(`${articleUrl}?p=0`);
    await page.waitForLoadState('networkidle');
    
    let currentUrl = page.url();
    console.log('Page 0 URL result:', currentUrl);
    
    // Should either redirect to page 1 or show page 1 content
    const pageContent = await page.locator('body').textContent();
    const hasValidContent = pageContent?.includes('Storytelling in Photography');
    console.log('Page 0 shows valid content:', hasValidContent);
    
    // Test page 99 (likely invalid)
    console.log('Testing invalid page number: 99...');
    await page.goto(`${articleUrl}?p=99`);
    await page.waitForLoadState('networkidle');
    
    currentUrl = page.url();
    console.log('Page 99 URL result:', currentUrl);
    
    const page99Content = await page.locator('body').textContent();
    const page99Valid = page99Content?.includes('Storytelling in Photography');
    console.log('Page 99 shows valid content:', page99Valid);
    
    // Check for error handling
    const hasErrorMessage = page99Content?.toLowerCase().includes('not found') || 
                           page99Content?.toLowerCase().includes('error') ||
                           page99Content?.toLowerCase().includes('invalid page');
    console.log('Page 99 shows error message:', hasErrorMessage);
  });

  test('Verify visual elements and pagination controls', async ({ page }) => {
    await page.goto(articleUrl);
    await page.waitForLoadState('networkidle');
    
    // Look for pagination elements
    const paginationSelectors = [
      '.pagination',
      '.page-nav',
      '.page-navigation',
      '[class*="page"]',
      'nav[aria-label*="page"]'
    ];
    
    let paginationFound = false;
    let paginationSelector = '';
    
    for (const selector of paginationSelectors) {
      const count = await page.locator(selector).count();
      if (count > 0) {
        paginationFound = true;
        paginationSelector = selector;
        break;
      }
    }
    
    console.log('Pagination controls found:', paginationFound);
    console.log('Pagination selector:', paginationSelector);
    
    if (paginationFound) {
      // Check for page numbers
      const pageNumbers = await page.locator(`${paginationSelector} a, ${paginationSelector} span`).count();
      console.log('Pagination elements count:', pageNumbers);
      
      // Check for navigation arrows
      const nextArrow = await page.locator('a:has-text("→"), a:has-text("Next"), a:has-text("›")').count();
      const prevArrow = await page.locator('a:has-text("←"), a:has-text("Previous"), a:has-text("‹")').count();
      
      console.log('Next arrow found:', nextArrow > 0);
      console.log('Previous arrow found:', prevArrow > 0);
      
      // Take screenshot of pagination area
      if (pageNumbers > 0) {
        await page.locator(paginationSelector).screenshot({ 
          path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/pagination-controls.png' 
        });
      }
    }
    
    // Check for styled pagination elements
    const styledElements = await page.evaluate(() => {
      const elements = document.querySelectorAll('[class*="page"], .pagination, .page-nav');
      const styles = [];
      elements.forEach(el => {
        const computedStyle = window.getComputedStyle(el);
        styles.push({
          display: computedStyle.display,
          visibility: computedStyle.visibility,
          fontSize: computedStyle.fontSize,
          color: computedStyle.color,
          backgroundColor: computedStyle.backgroundColor
        });
      });
      return styles;
    });
    
    console.log('Pagination styling:', JSON.stringify(styledElements, null, 2));
  });

  test('Check content integrity across pages', async ({ page }) => {
    // Get content from page 1
    await page.goto(articleUrl);
    await page.waitForLoadState('networkidle');
    
    const page1Content = await page.locator('.content, .article-content, main, .post-content').first().textContent();
    console.log('Page 1 content length:', page1Content?.length || 0);
    
    // Check for article metadata on page 1
    const hasTitle = await page.locator('h1').count() > 0;
    const hasAuthor = await page.locator('[class*="author"], .byline, .meta').count() > 0;
    const hasDate = await page.locator('[class*="date"], .published, .meta').count() > 0;
    
    console.log('Page 1 metadata - Title:', hasTitle, 'Author:', hasAuthor, 'Date:', hasDate);
    
    // Try to get content from page 2
    await page.goto(`${articleUrl}?p=2`);
    await page.waitForLoadState('networkidle');
    
    const page2Exists = !(await page.locator('body').textContent())?.toLowerCase().includes('not found');
    
    if (page2Exists) {
      const page2Content = await page.locator('.content, .article-content, main, .post-content').first().textContent();
      console.log('Page 2 content length:', page2Content?.length || 0);
      
      // Check for article metadata on page 2
      const page2HasTitle = await page.locator('h1').count() > 0;
      const page2HasAuthor = await page.locator('[class*="author"], .byline, .meta').count() > 0;
      const page2HasDate = await page.locator('[class*="date"], .published, .meta').count() > 0;
      
      console.log('Page 2 metadata - Title:', page2HasTitle, 'Author:', page2HasAuthor, 'Date:', page2HasDate);
      
      // Verify content is different (not duplicated)
      const contentOverlap = page1Content && page2Content ? 
        (page1Content === page2Content ? 'IDENTICAL' : 'DIFFERENT') : 'UNKNOWN';
      console.log('Content comparison:', contentOverlap);
      
      if (contentOverlap === 'IDENTICAL') {
        console.warn('WARNING: Page 1 and Page 2 have identical content - possible duplication issue');
      }
      
      // Check total content length
      const totalContentLength = (page1Content?.length || 0) + (page2Content?.length || 0);
      console.log('Total content across both pages:', totalContentLength);
    } else {
      console.log('Page 2 does not exist - article is single page');
    }
    
    // Final screenshot of current state
    await page.screenshot({ 
      path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/final-pagebreak-test.png',
      fullPage: true 
    });
  });

  test('Additional pagebreak edge cases', async ({ page }) => {
    // Test with various URL formats
    const urlVariations = [
      `${articleUrl}`,
      `${articleUrl}/`,
      `${articleUrl}?p=`,
      `${articleUrl}?p=1&other=param`,
      `${articleUrl}?other=param&p=1`
    ];
    
    for (const url of urlVariations) {
      console.log(`Testing URL variation: ${url}`);
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle', { timeout: 10000 });
        
        const currentUrl = page.url();
        const hasTitle = await page.locator('h1').count() > 0;
        console.log(`URL: ${url} -> Result: ${currentUrl}, Has title: ${hasTitle}`);
      } catch (error) {
        console.log(`URL: ${url} -> Error: ${error.message}`);
      }
    }
    
    // Test page parameter with letters (should be handled gracefully)
    try {
      await page.goto(`${articleUrl}?p=abc`);
      await page.waitForLoadState('networkidle');
      const currentUrl = page.url();
      const hasValidContent = (await page.locator('h1').count()) > 0;
      console.log(`Non-numeric page parameter result: ${currentUrl}, Valid content: ${hasValidContent}`);
    } catch (error) {
      console.log(`Non-numeric page parameter error: ${error.message}`);
    }
  });
});