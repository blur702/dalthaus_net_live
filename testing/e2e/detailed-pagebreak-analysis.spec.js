const { test, expect } = require('@playwright/test');

test('Detailed pagebreak functionality analysis', async ({ page }) => {
  console.log('Performing detailed analysis of pagebreak functionality...');
  
  const testArticle = '/article/the-key-is-writing-stories-people-want-to-read';
  
  // Test 1: Compare page 1 vs page 2 content
  console.log('\n=== Comparing Page 1 vs Page 2 Content ===');
  
  // Get page 1 content
  await page.goto(`https://dalthaus.net${testArticle}`);
  await page.waitForLoadState('networkidle');
  
  const page1Content = await page.textContent('main, .content, .article-content, body');
  const page1Html = await page.content();
  const page1Length = page1Content.length;
  
  console.log(`Page 1 content length: ${page1Length} characters`);
  console.log(`Page 1 first 200 chars: ${page1Content.substring(0, 200)}...`);
  
  // Get page 2 content
  await page.goto(`https://dalthaus.net${testArticle}?page=2`);
  await page.waitForLoadState('networkidle');
  
  const page2Content = await page.textContent('main, .content, .article-content, body');
  const page2Html = await page.content();
  const page2Length = page2Content.length;
  
  console.log(`\nPage 2 content length: ${page2Length} characters`);
  console.log(`Page 2 first 200 chars: ${page2Content.substring(0, 200)}...`);
  
  // Compare content
  const contentIsDifferent = page1Content !== page2Content;
  console.log(`\nContent is different between pages: ${contentIsDifferent}`);
  
  if (contentIsDifferent) {
    console.log('✅ Pagebreak functionality IS working - content changes between pages');
    
    // Look for navigation elements
    const navElements = await page.locator('a, button, .nav, .pagination, [href*="page"]').all();
    console.log(`\nFound ${navElements.length} potential navigation elements on page 2`);
    
    for (let i = 0; i < Math.min(10, navElements.length); i++) {
      try {
        const element = navElements[i];
        const text = await element.textContent();
        const href = await element.getAttribute('href');
        const className = await element.getAttribute('class');
        
        if (href && (href.includes('page') || text.toLowerCase().includes('page') || 
                    text.toLowerCase().includes('next') || text.toLowerCase().includes('prev'))) {
          console.log(`  Navigation element: "${text}" -> ${href} (class: ${className})`);
        }
      } catch (error) {
        // Skip elements that can't be read
      }
    }
  } else {
    console.log('❌ Pages show identical content - pagebreak may not be working');
  }
  
  // Test 3: Check multiple pages
  console.log('\n=== Testing Multiple Pages ===');
  
  const pagesToTest = [1, 2, 3, 4, 5];
  const pageContents = {};
  
  for (const pageNum of pagesToTest) {
    console.log(`Testing page ${pageNum}...`);
    await page.goto(`https://dalthaus.net${testArticle}?page=${pageNum}`);
    
    try {
      await page.waitForLoadState('networkidle', { timeout: 5000 });
      const content = await page.textContent('body');
      
      if (content.includes('404') || content.includes('Not Found')) {
        console.log(`  Page ${pageNum}: 404 Not Found`);
        break;
      } else {
        const contentSnippet = content.substring(0, 100).replace(/\s+/g, ' ').trim();
        pageContents[pageNum] = contentSnippet;
        console.log(`  Page ${pageNum}: OK (${content.length} chars) - "${contentSnippet}..."`);
      }
    } catch (error) {
      console.log(`  Page ${pageNum}: Error - ${error.message}`);
      break;
    }
  }
  
  // Analyze content differences
  console.log('\n=== Content Analysis ===');
  const pageNumbers = Object.keys(pageContents);
  if (pageNumbers.length > 1) {
    let allSame = true;
    const firstPageContent = pageContents[pageNumbers[0]];
    
    for (let i = 1; i < pageNumbers.length; i++) {
      if (pageContents[pageNumbers[i]] !== firstPageContent) {
        allSame = false;
        break;
      }
    }
    
    if (allSame) {
      console.log('❌ All pages show identical content');
    } else {
      console.log('✅ Pages show different content - pagebreak is working!');
      
      // Show content differences
      pageNumbers.forEach(pageNum => {
        console.log(`Page ${pageNum}: "${pageContents[pageNum]}..."`);
      });
    }
  }
  
  // Test 4: Look for pagebreak markers in the database content
  console.log('\n=== Checking for Pagebreak Markers in HTML Source ===');
  
  await page.goto(`https://dalthaus.net${testArticle}`);
  await page.waitForLoadState('networkidle');
  
  const htmlSource = await page.content();
  
  const markers = [
    '<!--pagebreak-->',
    '[pagebreak]',
    '<!--more-->',
    '[more]',
    'page-break',
    'pagebreak',
    'nextpage'
  ];
  
  markers.forEach(marker => {
    if (htmlSource.includes(marker)) {
      console.log(`✅ Found marker in HTML: ${marker}`);
    }
  });
  
  // Test 5: Check the article with known pagebreaks (Article ID 14) again with admin access
  console.log('\n=== Testing Article ID 14 with Admin Access ===');
  
  // Login as admin first
  await page.goto('https://dalthaus.net/admin/login');
  await page.fill('input[name="username"], input[name="email"]', 'kevin');
  await page.fill('input[name="password"]', '(130Bpm)');
  await page.click('button[type="submit"], input[type="submit"]');
  await page.waitForLoadState('networkidle');
  
  // Now try the article with pagebreaks
  const draftArticleUrl = 'https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible';
  
  console.log('Attempting to access draft article while logged in as admin...');
  await page.goto(draftArticleUrl);
  await page.waitForLoadState('networkidle');
  
  const draftContent = await page.textContent('body');
  if (!draftContent.includes('404') && !draftContent.includes('Not Found')) {
    console.log('✅ Draft article accessible while logged in as admin');
    
    // Test pagebreak functionality on the draft article
    await page.goto(`${draftArticleUrl}?page=2`);
    await page.waitForLoadState('networkidle');
    
    const draftPage2Content = await page.textContent('body');
    if (!draftPage2Content.includes('404')) {
      console.log('✅ Draft article page 2 accessible');
      
      // Compare page 1 vs page 2 of draft article
      await page.goto(draftArticleUrl);
      await page.waitForLoadState('networkidle');
      const draftPage1 = await page.textContent('body');
      
      await page.goto(`${draftArticleUrl}?page=2`);
      await page.waitForLoadState('networkidle');
      const draftPage2 = await page.textContent('body');
      
      if (draftPage1 !== draftPage2) {
        console.log('✅ Draft article shows different content between pages - pagebreaks working!');
      } else {
        console.log('❌ Draft article shows same content on both pages');
      }
    } else {
      console.log('❌ Draft article page 2 returns 404');
    }
  } else {
    console.log('❌ Draft article still not accessible even when logged in as admin');
  }
  
  console.log('\n=== FINAL ANALYSIS ===');
  console.log('Check the detailed output above to understand the current state of pagebreak functionality.');
});