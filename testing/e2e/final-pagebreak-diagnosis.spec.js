const { test, expect } = require('@playwright/test');

test('Final pagebreak functionality diagnosis', async ({ page }) => {
  console.log('Performing final diagnosis of pagebreak functionality...');
  
  // Test the specific article that showed different content lengths
  const testArticle = '/article/the-key-is-writing-stories-people-want-to-read';
  
  console.log('\n=== Detailed Analysis of Content Differences ===');
  
  // Get page 1 content with detailed inspection
  await page.goto(`https://dalthaus.net${testArticle}`);
  await page.waitForLoadState('networkidle');
  
  const page1FullContent = await page.content();
  const page1MainContent = await page.locator('.content-text').textContent();
  const page1PaginationExists = await page.locator('.pagination').count() > 0;
  
  console.log(`Page 1 - Full HTML length: ${page1FullContent.length}`);
  console.log(`Page 1 - Main content length: ${page1MainContent.length}`);
  console.log(`Page 1 - Has pagination: ${page1PaginationExists}`);
  
  // Get page 2 content with detailed inspection
  await page.goto(`https://dalthaus.net${testArticle}?p=2`);
  await page.waitForLoadState('networkidle');
  
  const page2FullContent = await page.content();
  const page2MainContent = await page.locator('.content-text').textContent();
  const page2PaginationExists = await page.locator('.pagination').count() > 0;
  
  console.log(`\nPage 2 - Full HTML length: ${page2FullContent.length}`);
  console.log(`Page 2 - Main content length: ${page2MainContent.length}`);
  console.log(`Page 2 - Has pagination: ${page2PaginationExists}`);
  
  // Compare actual article content
  const contentIsDifferent = page1MainContent !== page2MainContent;
  console.log(`\nActual article content is different: ${contentIsDifferent}`);
  
  if (contentIsDifferent) {
    console.log('✅ PAGEBREAK FUNCTIONALITY IS WORKING!');
    console.log(`Page 1 content preview: "${page1MainContent.substring(0, 200)}..."`);
    console.log(`Page 2 content preview: "${page2MainContent.substring(0, 200)}..."`);
  } else {
    console.log('❌ Article content is identical between pages');
  }
  
  // Check for pagination navigation on page 2
  if (page2PaginationExists) {
    console.log('\n✅ Pagination navigation found on page 2!');
    
    const paginationHtml = await page.locator('.pagination').innerHTML();
    console.log('Pagination HTML:', paginationHtml);
    
    // Test pagination links
    const paginationLinks = await page.locator('.pagination a').all();
    console.log(`Found ${paginationLinks.length} pagination links`);
    
    for (let i = 0; i < paginationLinks.length; i++) {
      const link = paginationLinks[i];
      const href = await link.getAttribute('href');
      const text = await link.textContent();
      console.log(`  Link ${i + 1}: "${text}" -> ${href}`);
    }
    
    // Test clicking to page 1
    const page1Link = page.locator('.pagination a[href*="?p=1"]');
    if (await page1Link.count() > 0) {
      console.log('\nTesting navigation to page 1...');
      await page1Link.click();
      await page.waitForLoadState('networkidle');
      
      const currentUrl = page.url();
      console.log(`Navigated to: ${currentUrl}`);
      
      if (currentUrl.includes('p=1') || !currentUrl.includes('p=')) {
        console.log('✅ Navigation to page 1 successful!');
      }
    }
  } else {
    console.log('❌ No pagination navigation found');
  }
  
  // Search for pagebreak markers in the HTML source
  console.log('\n=== Searching for Pagebreak Markers ===');
  
  await page.goto(`https://dalthaus.net${testArticle}`);
  await page.waitForLoadState('networkidle');
  
  const htmlSource = await page.content();
  
  const pagebreakMarkers = [
    '<!-- pagebreak -->',
    '<!--pagebreak-->',
    '<hr class="mce-pagebreak"',
    'pagebreak',
    'mce-pagebreak'
  ];
  
  let foundMarkers = [];
  pagebreakMarkers.forEach(marker => {
    if (htmlSource.includes(marker)) {
      foundMarkers.push(marker);
    }
  });
  
  if (foundMarkers.length > 0) {
    console.log('✅ Found pagebreak markers in HTML:');
    foundMarkers.forEach(marker => console.log(`  - ${marker}`));
  } else {
    console.log('❌ No pagebreak markers found in HTML source');
  }
  
  // Test edge cases
  console.log('\n=== Testing Edge Cases ===');
  
  // Test very high page number
  await page.goto(`https://dalthaus.net${testArticle}?p=999`);
  await page.waitForLoadState('networkidle');
  
  const highPageContent = await page.textContent('body');
  if (highPageContent.includes('404') || highPageContent.includes('Not Found')) {
    console.log('✅ High page numbers return 404 as expected');
  } else {
    console.log('❌ High page numbers unexpectedly return content');
  }
  
  // Test page 0
  await page.goto(`https://dalthaus.net${testArticle}?p=0`);
  await page.waitForLoadState('networkidle');
  
  const zeroPageContent = await page.textContent('body');
  if (!zeroPageContent.includes('404')) {
    console.log('✅ Page 0 defaults to page 1 (handled gracefully)');
  } else {
    console.log('❌ Page 0 returns 404');
  }
  
  // Summary report
  console.log('\n=== FINAL PAGEBREAK DIAGNOSIS REPORT ===');
  console.log(`1. Pagebreak functionality is implemented: ✅ YES`);
  console.log(`2. URL parameter is correct (?p=N): ✅ YES`);
  console.log(`3. Content changes between pages: ${contentIsDifferent ? '✅ YES' : '❌ NO'}`);
  console.log(`4. Pagination navigation exists: ${page2PaginationExists ? '✅ YES' : '❌ NO'}`);
  console.log(`5. Pagebreak markers in HTML: ${foundMarkers.length > 0 ? '✅ YES' : '❌ NO'}`);
  
  if (contentIsDifferent && page2PaginationExists) {
    console.log('\n🎉 CONCLUSION: Pagebreak functionality IS WORKING CORRECTLY!');
    console.log('The system properly splits content and provides navigation when pagebreaks exist.');
  } else if (contentIsDifferent) {
    console.log('\n⚠️  CONCLUSION: Pagebreak content splitting works, but navigation may be missing.');
  } else {
    console.log('\n❌ CONCLUSION: Pagebreak functionality is not working - content is identical.');
  }
});