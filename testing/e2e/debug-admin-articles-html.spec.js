import { test, expect } from '@playwright/test';

test.describe('Debug Admin Articles HTML', () => {
  test('Capture exact HTML and href attributes on admin articles page', async ({ page }) => {
    // Navigate to the admin login page
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('networkidle');
    
    // Fill in the login form
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    
    // Submit the login form
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Navigate to admin articles page
    await page.goto('https://dalthaus.net/admin/articles');
    await page.waitForLoadState('networkidle');
    
    console.log('\n=== FULL PAGE HTML ===');
    const pageHTML = await page.content();
    console.log(pageHTML);
    
    // Find all View links
    const viewLinks = await page.locator('a:has-text("View")').all();
    
    console.log('\n=== VIEW LINKS ANALYSIS ===');
    console.log(`Found ${viewLinks.length} View links on the page`);
    
    for (let i = 0; i < viewLinks.length; i++) {
      const link = viewLinks[i];
      const href = await link.getAttribute('href');
      const text = await link.textContent();
      const outerHTML = await link.evaluate(el => el.outerHTML);
      
      console.log(`\nView Link ${i + 1}:`);
      console.log(`  Text: "${text}"`);
      console.log(`  Href: "${href}"`);
      console.log(`  Full HTML: ${outerHTML}`);
    }
    
    // Get the table containing the articles
    const articlesTable = await page.locator('table').first();
    const tableHTML = await articlesTable.innerHTML();
    
    console.log('\n=== ARTICLES TABLE HTML ===');
    console.log(tableHTML);
    
    // Look for any links containing "/article/" or "/articles/"
    const articleLinks = await page.locator('a[href*="/article"]').all();
    
    console.log('\n=== ALL ARTICLE-RELATED LINKS ===');
    console.log(`Found ${articleLinks.length} links containing "/article"`);
    
    for (let i = 0; i < articleLinks.length; i++) {
      const link = articleLinks[i];
      const href = await link.getAttribute('href');
      const text = await link.textContent();
      
      console.log(`\nArticle Link ${i + 1}:`);
      console.log(`  Text: "${text?.trim()}"`);
      console.log(`  Href: "${href}"`);
    }
    
    // Use browser developer tools to inspect elements
    console.log('\n=== BROWSER DEV TOOLS INSPECTION ===');
    
    // Get computed styles and attributes via JavaScript
    const linkInspection = await page.evaluate(() => {
      const viewLinks = Array.from(document.querySelectorAll('a')).filter(a => a.textContent?.includes('View'));
      return viewLinks.map((link, index) => ({
        index: index + 1,
        href: link.href,
        getAttribute_href: link.getAttribute('href'),
        textContent: link.textContent,
        innerHTML: link.innerHTML,
        outerHTML: link.outerHTML,
        className: link.className,
        id: link.id
      }));
    });
    
    console.log('JavaScript evaluation of View links:');
    linkInspection.forEach(link => {
      console.log(`\nLink ${link.index}:`);
      console.log(`  textContent: "${link.textContent}"`);
      console.log(`  href property: "${link.href}"`);
      console.log(`  getAttribute('href'): "${link.getAttribute_href}"`);
      console.log(`  className: "${link.className}"`);
      console.log(`  id: "${link.id}"`);
      console.log(`  Full HTML: ${link.outerHTML}`);
    });
    
    // Take a screenshot for visual verification
    await page.screenshot({ 
      path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/tests/screenshots/admin-articles-debug.png',
      fullPage: true 
    });
    
    // Check for any JavaScript that might be modifying URLs
    const scripts = await page.locator('script').all();
    console.log('\n=== SCRIPT TAGS ===');
    console.log(`Found ${scripts.length} script tags`);
    
    for (let i = 0; i < scripts.length; i++) {
      const script = scripts[i];
      const src = await script.getAttribute('src');
      const content = await script.textContent();
      
      if (src) {
        console.log(`\nScript ${i + 1} (external): ${src}`);
      } else if (content && content.trim()) {
        console.log(`\nScript ${i + 1} (inline):`);
        console.log(content);
      }
    }
    
    // Check the network requests to see what URLs are being requested
    console.log('\n=== NETWORK ANALYSIS ===');
    
    // Listen for network requests
    page.on('request', request => {
      console.log(`Request: ${request.method()} ${request.url()}`);
    });
    
    // Click on the first View link to see what URL it actually navigates to
    if (viewLinks.length > 0) {
      const firstViewLink = viewLinks[0];
      const href = await firstViewLink.getAttribute('href');
      
      console.log(`\n=== CLICKING FIRST VIEW LINK ===`);
      console.log(`About to click link with href: "${href}"`);
      
      // Click and wait for navigation
      const [response] = await Promise.all([
        page.waitForResponse(response => response.url().includes('article')),
        firstViewLink.click()
      ]);
      
      console.log(`Navigation completed to: ${page.url()}`);
      console.log(`Response URL: ${response.url()}`);
      console.log(`Response status: ${response.status()}`);
    }
  });
});