import { test, expect } from '@playwright/test';

test.describe('Debug Raw Article Data', () => {
  test('Extract raw article data from page to see url_alias values', async ({ page }) => {
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
    
    // Inject a script to extract PHP variables from the page if possible
    const articleData = await page.evaluate(() => {
      // Look for any script tags or data attributes that might contain article data
      const scripts = Array.from(document.querySelectorAll('script'));
      const dataScripts = scripts.filter(script => 
        script.textContent && script.textContent.includes('article')
      );
      
      return {
        scriptsWithArticleData: dataScripts.map(script => script.textContent),
        totalScripts: scripts.length
      };
    });
    
    console.log('\n=== PAGE SCRIPT ANALYSIS ===');
    console.log(`Total scripts found: ${articleData.totalScripts}`);
    console.log(`Scripts containing "article": ${articleData.scriptsWithArticleData.length}`);
    
    if (articleData.scriptsWithArticleData.length > 0) {
      articleData.scriptsWithArticleData.forEach((script, index) => {
        console.log(`\nScript ${index + 1} with article data:`);
        console.log(script);
      });
    }
    
    // Check the actual HTML for each table row to see the raw href generation
    const tableRows = await page.locator('table tbody tr').all();
    
    console.log('\n=== RAW HTML ANALYSIS ===');
    
    for (let i = 0; i < Math.min(3, tableRows.length); i++) { // Only check first 3 for brevity
      const row = tableRows[i];
      const rowHTML = await row.innerHTML();
      
      console.log(`\nRow ${i + 1} HTML:`);
      console.log(rowHTML);
      
      // Extract the href using regex to see the exact generated value
      const hrefMatch = rowHTML.match(/href="([^"]*)"[^>]*>View</);
      if (hrefMatch) {
        console.log(`  Extracted View href: "${hrefMatch[1]}"`);
      }
    }
    
    // Let's also inspect the DOM structure more deeply
    const inspectionData = await page.evaluate(() => {
      const viewLinks = Array.from(document.querySelectorAll('a')).filter(a => 
        a.textContent && a.textContent.trim() === 'View'
      );
      
      return viewLinks.slice(0, 5).map((link, index) => {
        return {
          index: index + 1,
          href: link.href,
          getAttribute_href: link.getAttribute('href'),
          outerHTML: link.outerHTML,
          parentRow: link.closest('tr') ? link.closest('tr').innerHTML : 'No parent row'
        };
      });
    });
    
    console.log('\n=== DETAILED DOM INSPECTION ===');
    inspectionData.forEach(data => {
      console.log(`\nView Link ${data.index}:`);
      console.log(`  href property: "${data.href}"`);
      console.log(`  getAttribute('href'): "${data.getAttribute_href}"`);
      console.log(`  Full element: ${data.outerHTML}`);
    });
  });
});