const { test, expect } = require('@playwright/test');

test.describe('Simple Drafts Page Check', () => {
  test('should login and directly check drafts page', async ({ page }) => {
    console.log('🚀 Starting drafts page verification...');
    
    // Step 1: Login
    console.log('Step 1: Logging in...');
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('networkidle');
    
    // Fill login form
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button:has-text("Sign in")');
    
    // Wait for login and check result
    await page.waitForTimeout(3000);
    const loginUrl = page.url();
    console.log('After login URL:', loginUrl);
    
    if (loginUrl.includes('/admin/dashboard')) {
      console.log('✅ Login successful - reached dashboard');
      
      // Take screenshot of dashboard
      await page.screenshot({ 
        path: 'testing/results/dashboard-after-login.png',
        fullPage: true 
      });
      
      // Step 2: Check draft reminders on dashboard first
      console.log('Step 2: Checking draft reminders on dashboard...');
      const draftReminders = await page.locator('text=Draft Reminders').count();
      const autosaveElements = await page.locator('text=/[Aa]uto.*[Ss]ave|[Dd]raft/').count();
      
      console.log(`Found ${draftReminders} draft reminder sections`);
      console.log(`Found ${autosaveElements} autosave/draft elements`);
      
      if (autosaveElements > 0) {
        console.log('✅ Dashboard shows recent autosaves/drafts');
        
        // Get the text of draft reminders
        const draftTexts = await page.locator('text=/[Aa]uto.*[Ss]ave|[Dd]raft/').allTextContents();
        console.log('Draft entries found:');
        draftTexts.forEach((text, index) => {
          console.log(`  ${index + 1}. ${text.trim()}`);
        });
      }
      
      // Step 3: Try clicking "View All Content" button
      console.log('Step 3: Trying View All Content button...');
      const viewAllButton = page.locator('text=View All Content, button:has-text("View All Content")').first();
      if (await viewAllButton.count() > 0) {
        await viewAllButton.click();
        await page.waitForTimeout(2000);
        
        const contentUrl = page.url();
        console.log('After clicking View All Content:', contentUrl);
        
        await page.screenshot({ 
          path: 'testing/results/content-page-result.png',
          fullPage: true 
        });
        
        if (contentUrl.includes('/admin/content')) {
          console.log('✅ Successfully reached content page');
          
          // Check for drafts on this page
          const draftCount = await page.locator('text=/[Dd]raft/').count();
          console.log(`Found ${draftCount} draft-related elements on content page`);
        }
      }
      
      // Step 4: Try to access drafts page directly
      console.log('Step 4: Trying direct drafts URL...');
      await page.goto('https://dalthaus.net/admin/content/drafts');
      await page.waitForTimeout(2000);
      
      const draftsUrl = page.url();
      console.log('After drafts navigation URL:', draftsUrl);
      
      // Take screenshot of what we get
      await page.screenshot({ 
        path: 'testing/results/drafts-page-result.png',
        fullPage: true 
      });
      
      if (draftsUrl.includes('/admin/content/drafts')) {
        console.log('✅ Successfully reached drafts page');
        
        // Check for page content
        const pageContent = await page.content();
        
        // Look for drafts-related content
        if (pageContent.includes('draft') || pageContent.includes('Draft')) {
          console.log('✅ Page contains draft-related content');
        }
        
        // Look for table or list elements
        const tables = await page.locator('table').count();
        const lists = await page.locator('ul, ol').count();
        console.log(`Found ${tables} tables and ${lists} lists on page`);
        
        // Check for any visible text content
        const bodyText = await page.locator('body').textContent();
        if (bodyText.length > 100) {
          console.log('✅ Page has substantial content');
          console.log('Page text preview:', bodyText.substring(0, 200) + '...');
        } else {
          console.log('⚠️ Page has minimal content');
        }
        
      } else if (draftsUrl.includes('/admin/login')) {
        console.log('❌ Redirected back to login - session may have expired');
      } else {
        console.log('❓ Ended up on unexpected page:', draftsUrl);
      }
      
    } else if (loginUrl.includes('/admin/login')) {
      console.log('❌ Login failed - still on login page');
      
      // Check for error messages
      const errorElements = await page.locator('.error, .alert, .message').count();
      if (errorElements > 0) {
        const errorText = await page.locator('.error, .alert, .message').first().textContent();
        console.log('Error message:', errorText);
      }
    } else {
      console.log('❓ Unexpected redirect after login:', loginUrl);
    }
    
    console.log('🏁 Verification complete');
  });
});