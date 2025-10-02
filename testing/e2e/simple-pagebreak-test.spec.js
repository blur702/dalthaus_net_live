const { test, expect } = require('@playwright/test');

test.describe('Simple Pagebreak Functionality Test', () => {
  test('Check specific articles for pagebreak functionality', async ({ page }) => {
    console.log('Testing pagebreak functionality on specific articles...');
    
    // Test specific published articles
    const articlesToTest = [
      'the-key-is-writing-stories-people-want-to-read',
      'best-photography-for-beginners',
      'photojournalism-ethics-and-the-modern-photographer',
      'capturing-authentic-moments-in-portrait-photography',
      'the-art-of-visual-storytelling-in-documentary-photography'
    ];
    
    for (const articleSlug of articlesToTest) {
      console.log(`\n=== Testing article: ${articleSlug} ===`);
      
      const articleUrl = `https://dalthaus.net/article/${articleSlug}`;
      await page.goto(articleUrl);
      
      try {
        await page.waitForLoadState('networkidle', { timeout: 10000 });
        
        const pageTitle = await page.title();
        const content = await page.textContent('body');
        
        if (content.includes('404') || content.includes('Not Found')) {
          console.log('❌ Article not found');
          continue;
        }
        
        console.log(`Article title: ${pageTitle}`);
        
        // Check for pagebreak indicators
        const pageNavSelectors = [
          '.page-navigation',
          '.pagination', 
          '.page-break-nav',
          '.page-nav',
          '[class*="page"]',
          'a[href*="page="]',
          '.next-page',
          '.prev-page',
          '.previous-page'
        ];
        
        let hasPagebreak = false;
        let pagebreakDetails = [];
        
        for (const selector of pageNavSelectors) {
          const elements = await page.locator(selector).count();
          if (elements > 0) {
            hasPagebreak = true;
            pagebreakDetails.push(`${selector}: ${elements} elements`);
          }
        }
        
        if (hasPagebreak) {
          console.log('✅ Pagebreak elements found:');
          pagebreakDetails.forEach(detail => console.log(`  - ${detail}`));
          
          // Try to find and test pagination links
          const nextPageLink = page.locator('a[href*="page="]').first();
          if (await nextPageLink.count() > 0) {
            const nextUrl = await nextPageLink.getAttribute('href');
            console.log(`  Next page URL: ${nextUrl}`);
            
            // Try to navigate to next page
            await nextPageLink.click();
            await page.waitForLoadState('networkidle', { timeout: 5000 });
            
            const currentUrl = page.url();
            console.log(`  Current URL after click: ${currentUrl}`);
            
            if (currentUrl.includes('page=')) {
              console.log('  ✅ Pagebreak navigation working!');
            }
          }
        } else {
          console.log('❌ No pagebreak functionality detected');
        }
        
        // Also check page source for pagebreak markers
        const pageSource = await page.content();
        const hasPagebreakMarker = pageSource.includes('<!--pagebreak-->') || 
                                 pageSource.includes('[pagebreak]') ||
                                 pageSource.includes('page-break');
                                 
        if (hasPagebreakMarker) {
          console.log('✅ Pagebreak markers found in page source');
        }
        
      } catch (error) {
        console.log(`❌ Error loading article: ${error.message}`);
      }
    }
    
    // Test the draft article with direct URL manipulation
    console.log('\n=== Testing Article ID 14 with URL variations ===');
    const draftArticleVariations = [
      'https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible',
      'https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible?page=1',
      'https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible?page=2'
    ];
    
    for (const url of draftArticleVariations) {
      console.log(`Testing: ${url}`);
      await page.goto(url);
      
      try {
        await page.waitForLoadState('networkidle', { timeout: 5000 });
        const content = await page.textContent('body');
        
        if (!content.includes('404') && !content.includes('Not Found')) {
          console.log('✅ URL accessible');
          
          const hasPageNav = await page.locator('.page-navigation, .pagination, a[href*="page="]').count() > 0;
          console.log(`Has page navigation: ${hasPageNav}`);
        } else {
          console.log('❌ URL returns 404');
        }
      } catch (error) {
        console.log(`❌ Error: ${error.message}`);
      }
    }
    
    console.log('\n=== Summary ===');
    console.log('Pagebreak functionality test completed. Check console output for results.');
  });
});