import { test, expect } from '@playwright/test';

test('Front page should load successfully', async ({ page }) => {
  console.log('Starting front page test...');
  
  // Navigate to the site
  await page.goto('https://dalthaus.net/', { 
    waitUntil: 'networkidle',
    timeout: 30000 
  });
  
  // Take a screenshot for visual verification
  await page.screenshot({ path: 'screenshots/front-page-test.png', fullPage: true });
  
  // Check page title
  const title = await page.title();
  console.log('Page title:', title);
  
  // Check if we're getting the CMS page or an error/placeholder
  const pageContent = await page.content();
  
  // Check for common indicators of the CMS working
  const hasViewport = await page.$('meta[name="viewport"]');
  console.log('Has viewport meta tag:', !!hasViewport);
  
  // Check for any error messages
  const has404 = pageContent.includes('404') || pageContent.includes('Not Found');
  const hasError = pageContent.includes('Error') || pageContent.includes('error');
  const hasUnderConstruction = pageContent.includes('Under Construction') || pageContent.includes('maintenance');
  
  console.log('Page checks:');
  console.log('- Contains 404:', has404);
  console.log('- Contains Error:', hasError);
  console.log('- Contains Under Construction:', hasUnderConstruction);
  
  // Check response status
  const response = await page.goto('https://dalthaus.net/', { waitUntil: 'networkidle' });
  const status = response.status();
  console.log('HTTP Status:', status);
  
  // Check for specific CMS elements
  const hasHeader = await page.$('header') !== null;
  const hasNav = await page.$('nav') !== null;
  const hasMain = await page.$('main') !== null;
  const hasFooter = await page.$('footer') !== null;
  
  console.log('Page structure:');
  console.log('- Has header:', hasHeader);
  console.log('- Has nav:', hasNav);
  console.log('- Has main:', hasMain);
  console.log('- Has footer:', hasFooter);
  
  // Check for content sections
  const hasArticles = pageContent.includes('article') || pageContent.includes('Article');
  const hasPhotobooks = pageContent.includes('photobook') || pageContent.includes('Photobook');
  
  console.log('Content checks:');
  console.log('- References articles:', hasArticles);
  console.log('- References photobooks:', hasPhotobooks);
  
  // Get any visible text content
  const bodyText = await page.evaluate(() => {
    const body = document.querySelector('body');
    return body ? body.innerText.substring(0, 500) : 'No body element found';
  });
  console.log('\nFirst 500 chars of visible text:');
  console.log(bodyText);
  
  // Assertions
  expect(status).toBe(200); // Should return 200 OK
  expect(has404).toBe(false); // Should not show 404
  
  // Check if it's the redirect page or actual CMS
  if (hasUnderConstruction) {
    console.log('\n⚠️  WARNING: Site is showing Under Construction page, not the CMS');
  } else if (hasHeader || hasNav || hasMain || hasFooter) {
    console.log('\n✅ SUCCESS: CMS appears to be loading with proper structure');
  } else {
    console.log('\n⚠️  WARNING: Page loaded but CMS structure not detected');
  }
});

test('index.php should be accessible', async ({ page }) => {
  console.log('\nTesting direct index.php access...');
  
  const response = await page.goto('https://dalthaus.net/index.php', { 
    waitUntil: 'networkidle',
    timeout: 30000 
  });
  
  const status = response.status();
  console.log('index.php HTTP Status:', status);
  
  // Take a screenshot
  await page.screenshot({ path: 'screenshots/index-php-test.png' });
  
  if (status === 404) {
    console.log('❌ ERROR: index.php returns 404');
  } else if (status === 200) {
    console.log('✅ SUCCESS: index.php is accessible');
  } else {
    console.log(`⚠️  WARNING: index.php returned status ${status}`);
  }
  
  expect([200, 301, 302]).toContain(status); // Should be 200 or redirect
});