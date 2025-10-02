const { test, expect } = require('@playwright/test');

test.describe('Admin Articles View Links Diagnosis', () => {
  test('diagnose View links on admin articles page', async ({ page }) => {
    console.log('🔍 Starting diagnosis of admin articles View links...');
    
    // Navigate to admin login
    await page.goto('https://dalthaus.net/admin/login');
    
    // Login with admin credentials
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    
    // Wait for redirect to dashboard
    await page.waitForURL(/admin\/dashboard/);
    console.log('✅ Successfully logged in');
    
    // Navigate to admin articles
    await page.goto('https://dalthaus.net/admin/articles');
    await page.waitForLoadState('networkidle');
    
    console.log('📊 Analyzing admin articles page...');
    
    // Get the page title to confirm we're on the right page
    const pageTitle = await page.title();
    console.log(`Page title: ${pageTitle}`);
    
    // Check if we're on the articles page
    const heading = await page.textContent('h1, h2, .page-title, .content-header h1');
    console.log(`Page heading: ${heading}`);
    
    // Find all View links
    const viewLinks = await page.locator('a').filter({ hasText: /^View$/i });
    const viewLinkCount = await viewLinks.count();
    console.log(`🔗 Found ${viewLinkCount} View links`);
    
    if (viewLinkCount === 0) {
      console.log('❌ No View links found on the page');
      
      // Capture the entire page HTML for debugging
      const pageContent = await page.content();
      console.log('📄 Page HTML structure:');
      console.log(pageContent.substring(0, 2000) + '...');
      
      // Look for any links in the table
      const allLinks = await page.locator('table a, .table a, [class*="table"] a');
      const allLinkCount = await allLinks.count();
      console.log(`Total links in table area: ${allLinkCount}`);
      
      for (let i = 0; i < Math.min(allLinkCount, 10); i++) {
        const link = allLinks.nth(i);
        const href = await link.getAttribute('href');
        const text = await link.textContent();
        console.log(`Link ${i + 1}: "${text}" -> ${href}`);
      }
      
      return;
    }
    
    // Analyze each View link
    const linkAnalysis = [];
    
    for (let i = 0; i < Math.min(viewLinkCount, 5); i++) {
      const link = viewLinks.nth(i);
      const href = await link.getAttribute('href');
      const text = await link.textContent();
      
      // Get the parent row to understand context
      const row = link.locator('xpath=ancestor::tr[1]');
      const rowText = await row.textContent();
      
      linkAnalysis.push({
        index: i + 1,
        text: text.trim(),
        href: href,
        rowContext: rowText.replace(/\s+/g, ' ').trim()
      });
      
      console.log(`🔗 View Link ${i + 1}:`);
      console.log(`   Text: "${text}"`);
      console.log(`   URL: ${href}`);
      console.log(`   Row context: ${rowText.replace(/\s+/g, ' ').trim().substring(0, 100)}...`);
    }
    
    // Test the first few View links
    console.log('\n🧪 Testing View link functionality...');
    
    for (let i = 0; i < Math.min(3, viewLinkCount); i++) {
      const link = viewLinks.nth(i);
      const href = await link.getAttribute('href');
      
      console.log(`\n🔍 Testing View link ${i + 1}: ${href}`);
      
      try {
        // Open link in new tab to avoid navigation issues
        const [newPage] = await Promise.all([
          page.context().waitForEvent('page'),
          link.click({ modifiers: ['Meta'] }) // Cmd+click on Mac
        ]);
        
        // Wait for the new page to load
        await newPage.waitForLoadState('networkidle', { timeout: 10000 });
        
        const newUrl = newPage.url();
        const newTitle = await newPage.title();
        const statusCode = await newPage.evaluate(() => {
          return fetch(window.location.href, { method: 'HEAD' })
            .then(response => response.status)
            .catch(() => 'fetch_error');
        });
        
        console.log(`   ✅ Link opened successfully`);
        console.log(`   Final URL: ${newUrl}`);
        console.log(`   Page title: ${newTitle}`);
        console.log(`   Status code: ${statusCode}`);
        
        // Check if it's a 404 page
        const bodyText = await newPage.textContent('body');
        if (bodyText.toLowerCase().includes('404') || bodyText.toLowerCase().includes('not found')) {
          console.log(`   ❌ Page appears to be a 404 error`);
        } else {
          console.log(`   ✅ Page loaded successfully`);
        }
        
        // Close the new tab
        await newPage.close();
        
      } catch (error) {
        console.log(`   ❌ Error testing link: ${error.message}`);
      }
    }
    
    // Capture the current table HTML for analysis
    console.log('\n📋 Capturing table HTML for analysis...');
    
    try {
      const tableHtml = await page.locator('table, .table, [class*="table"]').first().innerHTML();
      console.log('📄 Table HTML (first 1000 chars):');
      console.log(tableHtml.substring(0, 1000) + '...');
      
      // Save detailed analysis to file
      const analysis = {
        timestamp: new Date().toISOString(),
        pageTitle: pageTitle,
        pageHeading: heading,
        viewLinkCount: viewLinkCount,
        links: linkAnalysis,
        tableHtml: tableHtml
      };
      
      console.log('\n📊 Analysis Summary:');
      console.log(`- Found ${viewLinkCount} View links`);
      console.log(`- Page title: ${pageTitle}`);
      console.log(`- Page heading: ${heading}`);
      
      if (linkAnalysis.length > 0) {
        console.log('\n🔗 Sample View Links:');
        linkAnalysis.forEach(link => {
          console.log(`   ${link.index}. "${link.text}" -> ${link.href}`);
        });
      }
      
    } catch (error) {
      console.log(`❌ Error capturing table HTML: ${error.message}`);
    }
  });
  
  test('test direct URL patterns for articles', async ({ page }) => {
    console.log('\n🧪 Testing direct URL patterns...');
    
    const testUrls = [
      'https://dalthaus.net/article/test-article',
      'https://dalthaus.net/articles/test-article',
      'https://dalthaus.net/content/test-article',
      'https://dalthaus.net/test-article'
    ];
    
    for (const url of testUrls) {
      console.log(`\n🔍 Testing URL pattern: ${url}`);
      
      try {
        const response = await page.goto(url, { waitUntil: 'networkidle', timeout: 10000 });
        const status = response.status();
        const finalUrl = page.url();
        const title = await page.title();
        
        console.log(`   Status: ${status}`);
        console.log(`   Final URL: ${finalUrl}`);
        console.log(`   Title: ${title}`);
        
        if (status === 404) {
          console.log(`   ❌ 404 Not Found`);
        } else if (status === 200) {
          console.log(`   ✅ Success`);
        } else {
          console.log(`   ⚠️  Unexpected status: ${status}`);
        }
        
      } catch (error) {
        console.log(`   ❌ Error: ${error.message}`);
      }
    }
  });
});