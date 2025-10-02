const { test, expect } = require('@playwright/test');

test('Get actual article list and test pagebreaks', async ({ page }) => {
  console.log('Getting actual article list from the website...');
  
  // Get list of articles from the articles page
  await page.goto('https://dalthaus.net/articles');
  await page.waitForLoadState('networkidle');
  
  // Find all article links
  const articleLinks = await page.locator('a[href*="/article/"]').all();
  console.log(`Found ${articleLinks.length} article links`);
  
  const articles = [];
  for (let i = 0; i < Math.min(10, articleLinks.length); i++) {
    try {
      const href = await articleLinks[i].getAttribute('href');
      const text = await articleLinks[i].textContent();
      articles.push({ href, text: text.trim() });
    } catch (error) {
      console.log(`Error getting article ${i}: ${error.message}`);
    }
  }
  
  console.log('\nFound articles:');
  articles.forEach((article, index) => {
    console.log(`${index + 1}. ${article.text} - ${article.href}`);
  });
  
  // Test first 5 articles for pagebreak functionality
  console.log('\n=== Testing articles for pagebreak functionality ===');
  
  for (let i = 0; i < Math.min(5, articles.length); i++) {
    const article = articles[i];
    console.log(`\nTesting: ${article.text}`);
    console.log(`URL: ${article.href}`);
    
    await page.goto(article.href);
    await page.waitForLoadState('networkidle');
    
    // Check for various pagebreak indicators
    const checks = {
      'page-navigation class': await page.locator('.page-navigation').count(),
      'pagination class': await page.locator('.pagination').count(),
      'page links': await page.locator('a[href*="page="]').count(),
      'next-page class': await page.locator('.next-page').count(),
      'prev-page class': await page.locator('.prev-page').count(),
      'page break text': await page.locator('text=/page\\s*\\d+/i').count(),
      'numbered pagination': await page.locator('a[href*="?page="], a[href*="&page="]').count()
    };
    
    let hasAnyPagebreak = false;
    Object.entries(checks).forEach(([checkName, count]) => {
      if (count > 0) {
        console.log(`  ✅ ${checkName}: ${count} found`);
        hasAnyPagebreak = true;
      }
    });
    
    if (!hasAnyPagebreak) {
      console.log('  ❌ No pagebreak functionality detected');
    }
    
    // Check page source for pagebreak markers
    const content = await page.content();
    const markers = {
      '<!--pagebreak-->': content.includes('<!--pagebreak-->'),
      '[pagebreak]': content.includes('[pagebreak]'),
      'class containing "page"': content.includes('class="page') || content.includes("class='page"),
    };
    
    Object.entries(markers).forEach(([marker, found]) => {
      if (found) {
        console.log(`  ✅ Found in source: ${marker}`);
        hasAnyPagebreak = true;
      }
    });
    
    // If we found pagebreak indicators, try to test them
    if (hasAnyPagebreak) {
      const pageLinks = await page.locator('a[href*="page="]').all();
      if (pageLinks.length > 0) {
        const firstPageLink = pageLinks[0];
        const linkUrl = await firstPageLink.getAttribute('href');
        console.log(`  Testing page link: ${linkUrl}`);
        
        try {
          await firstPageLink.click();
          await page.waitForLoadState('networkidle', { timeout: 5000 });
          
          const newUrl = page.url();
          console.log(`  Navigated to: ${newUrl}`);
          
          if (newUrl.includes('page=')) {
            console.log('  ✅ Pagebreak navigation successful!');
          }
        } catch (error) {
          console.log(`  ❌ Error testing pagebreak navigation: ${error.message}`);
        }
      }
    }
  }
  
  console.log('\n=== Final Test: Manual pagebreak URL testing ===');
  
  // Try some manual pagebreak URLs based on the first article we found
  if (articles.length > 0) {
    const firstArticle = articles[0];
    const baseUrl = firstArticle.href;
    
    const testUrls = [
      `${baseUrl}?page=2`,
      `${baseUrl}&page=2`,
      `${baseUrl}#page2`
    ];
    
    for (const testUrl of testUrls) {
      console.log(`\nTesting pagebreak URL: ${testUrl}`);
      await page.goto(testUrl);
      
      try {
        await page.waitForLoadState('networkidle', { timeout: 5000 });
        const content = await page.textContent('body');
        
        if (!content.includes('404') && !content.includes('Not Found')) {
          console.log('✅ URL loads successfully');
          
          const pageTitle = await page.title();
          console.log(`Page title: ${pageTitle}`);
          
          // Check if content is different (indicating it's actually a different page)
          const hasPageIndicator = content.includes('Page 2') || 
                                 content.includes('page 2') ||
                                 page.url().includes('page=2');
          if (hasPageIndicator) {
            console.log('✅ Appears to be showing page 2 content');
          }
        } else {
          console.log('❌ URL returns 404 or error');
        }
      } catch (error) {
        console.log(`❌ Error loading URL: ${error.message}`);
      }
    }
  }
});