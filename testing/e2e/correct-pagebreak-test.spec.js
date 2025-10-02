const { test, expect } = require('@playwright/test');

test('Test pagebreak functionality with correct parameter', async ({ page }) => {
  console.log('Testing pagebreak functionality with correct URL parameter (p instead of page)...');
  
  const testArticle = '/article/the-key-is-writing-stories-people-want-to-read';
  
  // Test with correct parameter 'p' instead of 'page'
  console.log('\n=== Testing with correct parameter "p" ===');
  
  // Get page 1 content
  await page.goto(`https://dalthaus.net${testArticle}`);
  await page.waitForLoadState('networkidle');
  
  const page1Content = await page.textContent('main, .content, .article-content, body');
  console.log(`Page 1 content length: ${page1Content.length} characters`);
  
  // Get page 2 content using correct parameter
  await page.goto(`https://dalthaus.net${testArticle}?p=2`);
  await page.waitForLoadState('networkidle');
  
  const page2Content = await page.textContent('main, .content, .article-content, body');
  console.log(`Page 2 content length: ${page2Content.length} characters`);
  
  if (page1Content !== page2Content) {
    console.log('✅ Content is different between page 1 and page 2 - pagebreaks working!');
    
    console.log(`Page 1 snippet: "${page1Content.substring(0, 150).replace(/\s+/g, ' ').trim()}..."`);
    console.log(`Page 2 snippet: "${page2Content.substring(0, 150).replace(/\s+/g, ' ').trim()}..."`);
  } else {
    console.log('❌ Content is identical - no pagebreak functionality detected');
  }
  
  // Test multiple pages
  console.log('\n=== Testing Multiple Pages with Correct Parameter ===');
  
  const pageResults = {};
  for (let pageNum = 1; pageNum <= 5; pageNum++) {
    console.log(`Testing page ${pageNum} with ?p=${pageNum}...`);
    
    await page.goto(`https://dalthaus.net${testArticle}?p=${pageNum}`);
    await page.waitForLoadState('networkidle');
    
    const content = await page.textContent('body');
    if (content.includes('404') || content.includes('Not Found')) {
      console.log(`  Page ${pageNum}: 404 Not Found`);
      break;
    }
    
    const contentSnippet = content.substring(0, 100).replace(/\s+/g, ' ').trim();
    pageResults[pageNum] = {
      length: content.length,
      snippet: contentSnippet
    };
    
    console.log(`  Page ${pageNum}: ${content.length} chars - "${contentSnippet}..."`);
  }
  
  // Analyze if pages are different
  const pageNumbers = Object.keys(pageResults);
  if (pageNumbers.length > 1) {
    let uniqueContent = new Set();
    pageNumbers.forEach(pageNum => {
      uniqueContent.add(pageResults[pageNum].snippet);
    });
    
    if (uniqueContent.size === 1) {
      console.log('❌ All pages show identical content');
    } else {
      console.log(`✅ Found ${uniqueContent.size} unique page contents - pagebreaks are working!`);
    }
  }
  
  // Test other published articles
  console.log('\n=== Testing Other Published Articles ===');
  
  // Get article list
  await page.goto('https://dalthaus.net/articles');
  await page.waitForLoadState('networkidle');
  
  const articleLinks = await page.locator('a[href*="/article/"]').all();
  const uniqueArticles = new Set();
  
  for (const link of articleLinks) {
    const href = await link.getAttribute('href');
    if (href && !href.includes('Read More')) {
      uniqueArticles.add(href);
    }
  }
  
  const articlesToTest = Array.from(uniqueArticles).slice(0, 3);
  
  for (const articleUrl of articlesToTest) {
    console.log(`\nTesting article: ${articleUrl}`);
    
    // Test page 1
    await page.goto(articleUrl);
    await page.waitForLoadState('networkidle');
    const content1 = await page.textContent('body');
    
    // Test page 2
    await page.goto(`${articleUrl}?p=2`);
    await page.waitForLoadState('networkidle');
    const content2 = await page.textContent('body');
    
    if (content2.includes('404')) {
      console.log('  No page 2 available');
    } else if (content1 === content2) {
      console.log('  ❌ Same content on both pages');
    } else {
      console.log('  ✅ Different content - has pagebreaks!');
    }
  }
  
  // Test navigation elements
  console.log('\n=== Checking for Navigation Elements ===');
  
  await page.goto(`https://dalthaus.net${testArticle}?p=2`);
  await page.waitForLoadState('networkidle');
  
  // Look for pagination navigation
  const navSelectors = [
    'a[href*="?p="]',
    '.pagination',
    '.page-nav',
    '.next-page',
    '.prev-page',
    '[href*="p=1"]',
    '[href*="p=3"]'
  ];
  
  for (const selector of navSelectors) {
    const count = await page.locator(selector).count();
    if (count > 0) {
      console.log(`✅ Found navigation element: ${selector} (${count} elements)`);
      
      // Get the actual elements
      const elements = await page.locator(selector).all();
      for (let i = 0; i < Math.min(3, elements.length); i++) {
        const text = await elements[i].textContent();
        const href = await elements[i].getAttribute('href');
        console.log(`  - "${text?.trim()}" -> ${href}`);
      }
    }
  }
  
  console.log('\n=== Testing Draft Article Access (Article ID 14) ===');
  
  // Try to get content data for article ID 14 to see what the actual alias should be
  const draftUrls = [
    'https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible',
    'https://dalthaus.net/article/storytelling-in-photography-telling-the-subjects-story-as-completely-as-possible',
    'https://dalthaus.net/article/telling-the-subjects-story-as-completely-as-possible',
    'https://dalthaus.net/article/telling-the-subject-s-story-as-completely-as-possible'
  ];
  
  for (const url of draftUrls) {
    console.log(`Trying: ${url}`);
    await page.goto(url);
    await page.waitForLoadState('networkidle');
    
    const content = await page.textContent('body');
    if (!content.includes('404') && !content.includes('Not Found')) {
      console.log('✅ Draft article found! Testing pagebreaks...');
      
      // Test page 2
      await page.goto(`${url}?p=2`);
      await page.waitForLoadState('networkidle');
      
      const page2Content = await page.textContent('body');
      if (!page2Content.includes('404')) {
        console.log('✅ Draft article page 2 accessible');
        
        // Compare page 1 vs page 2
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        const draftPage1 = await page.textContent('body');
        
        if (draftPage1 !== page2Content) {
          console.log('🎉 DRAFT ARTICLE HAS WORKING PAGEBREAKS!');
        }
      }
      break;
    } else {
      console.log('❌ Not found');
    }
  }
  
  console.log('\n=== FINAL SUMMARY ===');
  console.log('Pagebreak functionality analysis complete. Check results above.');
});