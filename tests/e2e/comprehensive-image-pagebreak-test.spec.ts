import { test, expect } from '@playwright/test';

test('should verify image loading and pagebreak functionality', async ({ page }) => {
    // Test homepage
    console.log('Testing homepage...');
    await page.goto('https://dalthaus.net/', { waitUntil: 'networkidle' });
    
    // Take screenshot of homepage
    await page.screenshot({ path: 'screenshots/homepage-full-test.png', fullPage: true });
    
    // Check for images on homepage
    const images = await page.$$('img');
    console.log(`Found ${images.length} images on homepage`);
    
    for (let i = 0; i < Math.min(images.length, 5); i++) {
      const img = images[i];
      const src = await img.getAttribute('src');
      const alt = await img.getAttribute('alt');
      console.log(`Image ${i + 1}: src="${src}", alt="${alt}"`);
      
      if (src && src.startsWith('/uploads/')) {
        // Test if image loads
        const response = await page.request.get(`https://dalthaus.net${src}`);
        console.log(`Image ${src} status: ${response.status()}`);
      }
    }
    
    // Navigate to articles page
    console.log('\nTesting articles page...');
    await page.goto('https://dalthaus.net/articles', { waitUntil: 'networkidle' });
    await page.screenshot({ path: 'screenshots/articles-page.png', fullPage: true });
    
    // Get all article links
    const articleLinks = await page.$$('a[href^="/article/"]');
    console.log(`Found ${articleLinks.length} article links`);
    
    if (articleLinks.length > 0) {
      // Test first article
      const firstArticleHref = await articleLinks[0].getAttribute('href');
      console.log(`\nTesting first article: ${firstArticleHref}`);
      
      await page.goto(`https://dalthaus.net${firstArticleHref}`, { waitUntil: 'networkidle' });
      await page.screenshot({ path: 'screenshots/article-detail.png', fullPage: true });
      
      // Check for pagination elements
      const paginationWrapper = await page.$('.pagination-wrapper');
      if (paginationWrapper) {
        console.log('✅ Found pagination wrapper');
        
        const paginationLinks = await page.$$('.pagination a');
        console.log(`Found ${paginationLinks.length} pagination links`);
        
        // Check for page numbers
        const pageNumbers = await page.$$eval('.pagination a', links => 
          links.map(link => link.textContent?.trim()).filter(text => /^[0-9]+$/.test(text || ''))
        );
        console.log(`Page numbers found: ${pageNumbers.join(', ')}`);
        
        // Test pagination if available
        if (pageNumbers.length > 1) {
          console.log('✅ Multi-page content found - testing pagination');
          
          // Click on page 2 if available
          const page2Link = await page.$('.pagination a:has-text("2")');
          if (page2Link) {
            await page2Link.click();
            await page.waitForLoadState('networkidle');
            await page.screenshot({ path: 'screenshots/article-page-2.png', fullPage: true });
            
            // Check if URL changed to include ?p=2
            const currentUrl = page.url();
            if (currentUrl.includes('p=2')) {
              console.log('✅ Pagination URL parameter working');
            } else {
              console.log('⚠️ Pagination URL parameter not found');
            }
            
            // Check if content changed
            const pageContent = await page.textContent('body');
            console.log('Page 2 content length:', pageContent?.length || 0);
          }
        } else {
          console.log('ℹ️ Single page content - no pagination needed');
        }
      } else {
        console.log('ℹ️ No pagination wrapper found - checking if content has pagebreaks');
        
        // Check the raw content for pagebreak markers
        const bodyContent = await page.content();
        const hasPagebreakMarker = bodyContent.includes('<!-- pagebreak -->') || 
                                   bodyContent.includes('mce-pagebreak');
        
        if (hasPagebreakMarker) {
          console.log('⚠️ Content has pagebreak markers but pagination not displayed');
        } else {
          console.log('ℹ️ Content does not contain pagebreak markers');
        }
      }
      
      // Check for images in article
      const articleImages = await page.$$('img');
      console.log(`Found ${articleImages.length} images in article`);
      
      for (let i = 0; i < Math.min(articleImages.length, 3); i++) {
        const img = articleImages[i];
        const src = await img.getAttribute('src');
        const naturalWidth = await img.evaluate((el: any) => el.naturalWidth);
        const naturalHeight = await img.evaluate((el: any) => el.naturalHeight);
        
        if (src && src.startsWith('/uploads/')) {
          console.log(`Article image ${i + 1}: ${src} (${naturalWidth}x${naturalHeight})`);
        }
      }
    }
    
    // Test photobooks page
    console.log('\nTesting photobooks page...');
    await page.goto('https://dalthaus.net/photobooks', { waitUntil: 'networkidle' });
    await page.screenshot({ path: 'screenshots/photobooks-page.png', fullPage: true });
    
    const photobookLinks = await page.$$('a[href^="/photobook/"]');
    console.log(`Found ${photobookLinks.length} photobook links`);
    
    if (photobookLinks.length > 0) {
      // Test first photobook
      const firstPhotobookHref = await photobookLinks[0].getAttribute('href');
      console.log(`\nTesting first photobook: ${firstPhotobookHref}`);
      
      await page.goto(`https://dalthaus.net${firstPhotobookHref}`, { waitUntil: 'networkidle' });
      await page.screenshot({ path: 'screenshots/photobook-detail.png', fullPage: true });
      
      // Check for pagination in photobook
      const photobookPagination = await page.$('.pagination-wrapper');
      if (photobookPagination) {
        console.log('✅ Found pagination in photobook');
        
        const photobookPageNumbers = await page.$$eval('.pagination a', links => 
          links.map(link => link.textContent?.trim()).filter(text => /^[0-9]+$/.test(text || ''))
        );
        console.log(`Photobook page numbers: ${photobookPageNumbers.join(', ')}`);
      }
      
      // Check for images in photobook
      const photobookImages = await page.$$('img');
      console.log(`Found ${photobookImages.length} images in photobook`);
    }
    
    // Test direct upload URL access
    console.log('\nTesting direct upload access...');
    const testImageUrl = 'https://dalthaus.net/uploads/content/featured/2025/09/8fc5d5a9d1ddb15c0d44ffad84df6d50.png';
    const imageResponse = await page.request.get(testImageUrl);
    console.log(`Direct image access status: ${imageResponse.status()}`);
    
    if (imageResponse.status() === 200) {
      console.log('✅ Direct image access working');
    } else {
      console.log('❌ Direct image access failed');
    }
});