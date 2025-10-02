const { test, expect } = require('@playwright/test');

test.describe('Pagebreak Functionality Testing', () => {
  test('Test pagebreak functionality across articles', async ({ page }) => {
    console.log('Starting comprehensive pagebreak functionality test...');
    
    // Test 1: Direct access to Article ID 14 (draft with pagebreaks)
    console.log('\n=== Test 1: Direct access to Article ID 14 (draft) ===');
    await page.goto('https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible');
    
    // Check if page loads or returns 404
    const pageTitle = await page.title();
    const pageContent = await page.textContent('body');
    
    console.log('Page title:', pageTitle);
    
    if (pageContent.includes('404') || pageContent.includes('Not Found') || pageContent.includes('Page not found')) {
      console.log('❌ Article ID 14 not accessible (likely due to draft status)');
    } else {
      console.log('✅ Article ID 14 is accessible');
      
      // Check for pagebreak indicators
      const hasPageNavigation = await page.locator('.page-navigation, .pagination, .page-break-nav').count() > 0;
      const hasNextPage = await page.locator('a[href*="page="], .next-page, [href*="&page="]').count() > 0;
      const hasPreviousPage = await page.locator('.prev-page, .previous-page').count() > 0;
      
      console.log('Has page navigation:', hasPageNavigation);
      console.log('Has next page link:', hasNextPage);
      console.log('Has previous page link:', hasPreviousPage);
      
      if (hasPageNavigation || hasNextPage) {
        console.log('✅ Pagebreak functionality appears to be present');
        
        // Try to click next page if available
        const nextPageLink = page.locator('a[href*="page="], .next-page').first();
        if (await nextPageLink.count() > 0) {
          const nextPageUrl = await nextPageLink.getAttribute('href');
          console.log('Next page URL:', nextPageUrl);
          
          await nextPageLink.click();
          await page.waitForLoadState('networkidle');
          
          const secondPageContent = await page.textContent('body');
          console.log('Second page loaded successfully');
        }
      } else {
        console.log('❌ No pagebreak functionality detected on Article ID 14');
      }
    }
    
    // Test 2: Check admin login status and access
    console.log('\n=== Test 2: Admin access check ===');
    await page.goto('https://dalthaus.net/admin/login');
    
    const loginForm = await page.locator('form[action*="login"]').count() > 0;
    if (loginForm) {
      console.log('Admin login form found, attempting login...');
      
      // Try to login as admin
      await page.fill('input[name="username"], input[name="email"]', 'kevin');
      await page.fill('input[name="password"]', '(130Bpm)');
      await page.click('button[type="submit"], input[type="submit"]');
      
      await page.waitForLoadState('networkidle');
      
      const currentUrl = page.url();
      if (currentUrl.includes('/admin/dashboard') || currentUrl.includes('/admin')) {
        console.log('✅ Successfully logged in as admin');
        
        // Now try to access the draft article again
        console.log('\n=== Test 2b: Access Article ID 14 while logged in as admin ===');
        await page.goto('https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible');
        
        const adminPageContent = await page.textContent('body');
        if (!adminPageContent.includes('404') && !adminPageContent.includes('Not Found')) {
          console.log('✅ Article ID 14 accessible while logged in as admin');
          
          // Re-check for pagebreak functionality
          const hasPageNavigation = await page.locator('.page-navigation, .pagination, .page-break-nav').count() > 0;
          const hasNextPage = await page.locator('a[href*="page="], .next-page, [href*="&page="]').count() > 0;
          
          console.log('Has page navigation (admin view):', hasPageNavigation);
          console.log('Has next page link (admin view):', hasNextPage);
        } else {
          console.log('❌ Article ID 14 still not accessible even when logged in as admin');
        }
      } else {
        console.log('❌ Failed to login as admin');
      }
    }
    
    // Test 3: Check published articles for pagebreak functionality
    console.log('\n=== Test 3: Testing published articles for pagebreak functionality ===');
    
    // First, get list of published articles
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    console.log(`Found ${articleLinks.length} article links`);
    
    // Test first few articles
    const articlesToTest = Math.min(5, articleLinks.length);
    for (let i = 0; i < articlesToTest; i++) {
      const articleUrl = await articleLinks[i].getAttribute('href');
      console.log(`\nTesting article ${i + 1}: ${articleUrl}`);
      
      await page.goto(articleUrl);
      await page.waitForLoadState('networkidle');
      
      const hasPageNavigation = await page.locator('.page-navigation, .pagination, .page-break-nav').count() > 0;
      const hasNextPage = await page.locator('a[href*="page="], .next-page, [href*="&page="]').count() > 0;
      const content = await page.textContent('body');
      
      console.log(`  Has page navigation: ${hasPageNavigation}`);
      console.log(`  Has next page link: ${hasNextPage}`);
      
      if (hasPageNavigation || hasNextPage) {
        console.log(`  ✅ Article has pagebreak functionality!`);
        
        // Test the pagebreak navigation
        const nextPageLink = page.locator('a[href*="page="], .next-page').first();
        if (await nextPageLink.count() > 0) {
          const nextPageUrl = await nextPageLink.getAttribute('href');
          console.log(`  Next page URL: ${nextPageUrl}`);
          
          await nextPageLink.click();
          await page.waitForLoadState('networkidle');
          
          const secondPageUrl = page.url();
          console.log(`  Second page URL: ${secondPageUrl}`);
          
          if (secondPageUrl.includes('page=')) {
            console.log(`  ✅ Pagebreak navigation working!`);
          }
        }
        break; // Found working pagebreaks, no need to test more
      } else {
        console.log(`  ❌ No pagebreak functionality detected`);
      }
    }
    
    // Test 4: Direct pagebreak URL testing
    console.log('\n=== Test 4: Direct pagebreak URL testing ===');
    
    // Try accessing a potential pagebreak URL directly
    const testUrls = [
      'https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible?page=2',
      'https://dalthaus.net/article/storytelling-in-photography-telling-the-subject-s-story-as-completely-as-possible&page=2'
    ];
    
    for (const testUrl of testUrls) {
      console.log(`Testing direct pagebreak URL: ${testUrl}`);
      await page.goto(testUrl);
      await page.waitForLoadState('networkidle');
      
      const content = await page.textContent('body');
      if (!content.includes('404') && !content.includes('Not Found')) {
        console.log('✅ Pagebreak URL accessible');
        
        const hasPageNavigation = await page.locator('.page-navigation, .pagination, .page-break-nav').count() > 0;
        console.log(`Has page navigation: ${hasPageNavigation}`);
      } else {
        console.log('❌ Pagebreak URL not accessible');
      }
    }
    
    console.log('\n=== Test Complete ===');
    console.log('Check the console output above for detailed results of pagebreak functionality testing.');
  });
});