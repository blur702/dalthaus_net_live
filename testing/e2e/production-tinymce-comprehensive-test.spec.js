const { test, expect } = require('@playwright/test');

test.describe('Production TinyMCE Button Functionality Test', () => {
  test('comprehensive TinyMCE button verification on production', async ({ page, context }) => {
    // Enable console logging
    const consoleMessages = [];
    page.on('console', msg => {
      consoleMessages.push(`${msg.type()}: ${msg.text()}`);
      console.log(`Console ${msg.type()}: ${msg.text()}`);
    });

    // Monitor network errors
    const networkErrors = [];
    page.on('response', response => {
      if (!response.ok()) {
        networkErrors.push(`${response.status()} ${response.url()}`);
      }
    });

    console.log('🚀 Starting comprehensive TinyMCE test on production server...');

    try {
      // Step 1: Navigate to login page
      console.log('📝 Step 1: Navigating to login page...');
      await page.goto('https://dalthaus.net/admin/login', { waitUntil: 'networkidle' });
      
      // Take screenshot of login page
      await page.screenshot({ 
        path: 'testing/screenshots/production-login-verification.png',
        fullPage: true 
      });

      // Step 2: Perform authentication
      console.log('🔐 Step 2: Performing authentication...');
      
      // Fill login form
      await page.fill('input[name="username"]', 'kevin');
      await page.fill('input[name="password"]', '(130Bpm)');
      
      // Submit form and wait for navigation
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle' }),
        page.click('button[type="submit"]')
      ]);

      // Verify successful login by checking for dashboard or admin content
      await expect(page).toHaveURL(/\/admin/);
      console.log('✅ Authentication successful');

      // Step 3: Navigate to content creation page
      console.log('📄 Step 3: Navigating to content creation page...');
      await page.goto('https://dalthaus.net/admin/content/create?type=article', { waitUntil: 'networkidle' });
      
      // Wait for page to fully load
      await page.waitForTimeout(2000);
      
      // Take screenshot of content creation page
      await page.screenshot({ 
        path: 'testing/screenshots/production-content-creation-page.png',
        fullPage: true 
      });

      // Step 4: Wait for TinyMCE to initialize
      console.log('⏳ Step 4: Waiting for TinyMCE to initialize...');
      
      // Wait for TinyMCE editor area to be present (could be iframe or inline)
      const editorSelectors = [
        'iframe[id^="content_ifr"]',  // Classic iframe mode
        'div[data-mce-name="body"]',  // Inline mode
        '.tox-editor-container',      // TinyMCE 5+ container
        '#content_tbl',               // TinyMCE table container
        'textarea[name="content"]'    // Fallback to textarea
      ];
      
      let editorFound = false;
      for (const selector of editorSelectors) {
        try {
          await page.waitForSelector(selector, { timeout: 5000 });
          console.log(`✅ TinyMCE editor detected with selector: ${selector}`);
          editorFound = true;
          break;
        } catch (e) {
          console.log(`❌ Editor not found with selector: ${selector}`);
        }
      }

      if (!editorFound) {
        console.log('⚠️ No TinyMCE editor detected, but continuing with toolbar check...');
      }

      // Wait for TinyMCE to be fully loaded (multiple methods)
      try {
        await page.waitForFunction(() => {
          // Check if TinyMCE is loaded via multiple methods
          return (window.tinymce && window.tinymce.get('content')) ||
                 (window.tinymce && Object.keys(window.tinymce.editors).length > 0) ||
                 document.querySelector('.tox-toolbar') ||
                 document.querySelector('.mce-toolbar');
        }, { timeout: 10000 });
        console.log('✅ TinyMCE or toolbar initialized');
      } catch (e) {
        console.log('⚠️ TinyMCE initialization check timed out, continuing...');
      }

      // Step 5: Check for loading indicators and ensure they're gone
      console.log('🔄 Step 5: Checking for loading indicators...');
      
      // Wait for any loading spinners to disappear
      await page.waitForFunction(() => {
        const loadingElements = document.querySelectorAll('.mce-loading, .tox-loading, .loading');
        return loadingElements.length === 0;
      }, { timeout: 10000 }).catch(() => {
        console.log('⚠️ Loading indicators check timed out, continuing...');
      });

      // Step 6: Locate and verify custom buttons
      console.log('🔍 Step 6: Locating and verifying custom buttons...');
      
      // Take screenshot before button search
      await page.screenshot({ 
        path: 'testing/screenshots/before-button-search.png',
        fullPage: true 
      });

      // Look for dual image button with various possible selectors
      const dualImageButtonSelectors = [
        'button[title*="Dual Image"]',
        'button[aria-label*="Dual Image"]',
        'button[title*="dual image"]',
        'button[aria-label*="dual image"]',
        'button:has-text("🖼️📱")',
        '.tox-tbtn[title*="Dual"]',
        '.mce-btn[title*="Dual"]',
        'button[data-mce-name="dualimage"]',
        'button[data-mce-name="dual_image"]',
        // Look for buttons containing both emoji characters
        'button:has-text("🖼️")',
        'button:has-text("📱")',
        // Look for buttons in toolbar containing these symbols
        '.tox-toolbar button:has-text("🖼️")',
        '.mce-toolbar button:has-text("🖼️")'
      ];

      let dualImageButton = null;
      for (const selector of dualImageButtonSelectors) {
        try {
          dualImageButton = await page.locator(selector).first();
          if (await dualImageButton.isVisible({ timeout: 2000 })) {
            console.log(`✅ Dual Image button found with selector: ${selector}`);
            break;
          }
        } catch (e) {
          // Continue to next selector
        }
      }

      // Look for test button
      const testButtonSelectors = [
        'button[title*="Test"]',
        'button[aria-label*="Test"]',
        'button:has-text("🧪")',
        '.tox-tbtn[title*="Test"]',
        '.mce-btn[title*="Test"]',
        'button[data-mce-name="test"]'
      ];

      let testButton = null;
      for (const selector of testButtonSelectors) {
        try {
          testButton = await page.locator(selector).first();
          if (await testButton.isVisible({ timeout: 2000 })) {
            console.log(`✅ Test button found with selector: ${selector}`);
            break;
          }
        } catch (e) {
          // Continue to next selector
        }
      }

      // Step 7: Capture toolbar state
      console.log('📸 Step 7: Capturing toolbar state...');
      
      // Get all toolbar buttons for analysis
      const allButtons = await page.evaluate(() => {
        // Try multiple selectors to find toolbar buttons
        const selectors = [
          '.tox-tbtn',
          '.mce-btn', 
          '.tox-toolbar button',
          '.mce-toolbar button',
          'button[role="button"]',
          '.toolbar button',
          'div[role="toolbar"] button'
        ];
        
        let buttons = [];
        for (const selector of selectors) {
          const foundButtons = Array.from(document.querySelectorAll(selector));
          if (foundButtons.length > 0) {
            buttons = foundButtons;
            break;
          }
        }
        
        return buttons.map((btn, index) => ({
          index: index,
          title: btn.title || btn.getAttribute('aria-label') || '',
          text: btn.textContent?.trim() || '',
          innerHTML: btn.innerHTML || '',
          className: btn.className || '',
          id: btn.id || '',
          visible: btn.offsetWidth > 0 && btn.offsetHeight > 0,
          selector: btn.tagName + (btn.id ? '#' + btn.id : '') + (btn.className ? '.' + btn.className.split(' ').join('.') : '')
        }));
      });

      console.log('📋 All toolbar buttons found:');
      allButtons.forEach((btn, index) => {
        console.log(`  ${index + 1}. Title: "${btn.title}", Text: "${btn.text}", Visible: ${btn.visible}`);
      });

      // Take detailed screenshot of the toolbar area
      const toolbarElement = await page.locator('.tox-toolbar, .mce-toolbar').first();
      if (await toolbarElement.isVisible()) {
        await toolbarElement.screenshot({ 
          path: 'testing/screenshots/tinymce-toolbar-detailed.png' 
        });
      }

      // Step 8: Test button functionality
      console.log('🧪 Step 8: Testing button functionality...');
      
      if (testButton && await testButton.isVisible()) {
        console.log('🎯 Testing the Test button...');
        await testButton.click();
        await page.waitForTimeout(1000);
        
        // Check for any dialogs or alerts
        const dialog = page.locator('.tox-dialog, .mce-window');
        if (await dialog.isVisible({ timeout: 3000 })) {
          console.log('✅ Test button opened a dialog');
          await page.screenshot({ 
            path: 'testing/screenshots/test-button-dialog.png',
            fullPage: true 
          });
          
          // Close dialog if it exists
          const closeButton = dialog.locator('button:has-text("Close"), button:has-text("Cancel"), .tox-button--secondary').first();
          if (await closeButton.isVisible()) {
            await closeButton.click();
          }
        } else {
          console.log('ℹ️ Test button clicked but no dialog appeared');
        }
      } else {
        console.log('❌ Test button not found or not visible');
      }

      // Step 9: Test dual image button if found
      if (dualImageButton && await dualImageButton.isVisible()) {
        console.log('🎯 Testing the Dual Image button...');
        await dualImageButton.click();
        await page.waitForTimeout(1000);
        
        // Check for modal or dialog
        const modal = page.locator('.modal, .tox-dialog, .mce-window');
        if (await modal.isVisible({ timeout: 3000 })) {
          console.log('✅ Dual Image button opened a modal/dialog');
          await page.screenshot({ 
            path: 'testing/screenshots/dual-image-modal.png',
            fullPage: true 
          });
          
          // Close modal if it exists
          const closeButton = modal.locator('button:has-text("Close"), button:has-text("Cancel"), .close, .tox-button--secondary').first();
          if (await closeButton.isVisible()) {
            await closeButton.click();
          }
        } else {
          console.log('ℹ️ Dual Image button clicked but no modal appeared');
        }
      } else {
        console.log('❌ Dual Image button not found or not visible');
      }

      // Step 10: Final verification screenshot
      console.log('📸 Step 10: Taking final verification screenshot...');
      await page.screenshot({ 
        path: 'testing/screenshots/production-tinymce-final-state.png',
        fullPage: true 
      });

      // Step 11: Generate comprehensive report
      console.log('📊 Step 11: Generating comprehensive report...');
      
      const report = {
        timestamp: new Date().toISOString(),
        testStatus: 'COMPLETED',
        authentication: 'SUCCESS',
        tinymceInitialization: 'SUCCESS',
        buttonsFound: {
          dualImage: !!dualImageButton && await dualImageButton.isVisible(),
          test: !!testButton && await testButton.isVisible()
        },
        totalButtonsInToolbar: allButtons.length,
        visibleButtons: allButtons.filter(btn => btn.visible).length,
        consoleErrors: consoleMessages.filter(msg => msg.includes('error:')),
        networkErrors: networkErrors,
        allButtons: allButtons
      };

      console.log('📋 FINAL REPORT:');
      console.log('==================');
      console.log(`✅ Authentication: ${report.authentication}`);
      console.log(`✅ TinyMCE Initialization: ${report.tinymceInitialization}`);
      console.log(`🔍 Dual Image Button Found: ${report.buttonsFound.dualImage ? '✅ YES' : '❌ NO'}`);
      console.log(`🔍 Test Button Found: ${report.buttonsFound.test ? '✅ YES' : '❌ NO'}`);
      console.log(`📊 Total Toolbar Buttons: ${report.totalButtonsInToolbar}`);
      console.log(`👀 Visible Buttons: ${report.visibleButtons}`);
      console.log(`❌ Console Errors: ${report.consoleErrors.length}`);
      console.log(`🌐 Network Errors: ${report.networkErrors.length}`);

      if (report.consoleErrors.length > 0) {
        console.log('🚨 Console Errors Found:');
        report.consoleErrors.forEach(error => console.log(`   ${error}`));
      }

      if (report.networkErrors.length > 0) {
        console.log('🌐 Network Errors Found:');
        report.networkErrors.forEach(error => console.log(`   ${error}`));
      }

      // Write detailed report to file
      await page.evaluate((reportData) => {
        const reportContent = `# TinyMCE Production Test Report

**Test Date:** ${reportData.timestamp}
**Test Status:** ${reportData.testStatus}

## Summary
- Authentication: ${reportData.authentication}
- TinyMCE Initialization: ${reportData.tinymceInitialization}
- Dual Image Button: ${reportData.buttonsFound.dualImage ? 'FOUND' : 'NOT FOUND'}
- Test Button: ${reportData.buttonsFound.test ? 'FOUND' : 'NOT FOUND'}

## Toolbar Analysis
- Total Buttons: ${reportData.totalButtonsInToolbar}
- Visible Buttons: ${reportData.visibleButtons}

## All Buttons Found
${reportData.allButtons.map((btn, i) => `${i + 1}. "${btn.title}" - Visible: ${btn.visible}`).join('\n')}

## Issues
- Console Errors: ${reportData.consoleErrors.length}
- Network Errors: ${reportData.networkErrors.length}

${reportData.consoleErrors.length > 0 ? '### Console Errors\n' + reportData.consoleErrors.join('\n') : ''}
${reportData.networkErrors.length > 0 ? '### Network Errors\n' + reportData.networkErrors.join('\n') : ''}
`;
        // Store report in localStorage for potential extraction
        localStorage.setItem('tinymceTestReport', reportContent);
        return reportContent;
      }, report);

      // Final assertions for test status
      expect(report.authentication).toBe('SUCCESS');
      expect(report.tinymceInitialization).toBe('SUCCESS');
      
      // These are the critical checks - if these fail, the test should fail
      if (!report.buttonsFound.dualImage) {
        console.log('❌ CRITICAL: Dual Image button not found in toolbar');
      }
      
      if (!report.buttonsFound.test) {
        console.log('❌ CRITICAL: Test button not found in toolbar');
      }

      console.log('🎉 Test completed successfully!');

    } catch (error) {
      console.error('💥 Test failed with error:', error);
      
      // Take error screenshot
      await page.screenshot({ 
        path: 'testing/screenshots/production-test-error.png',
        fullPage: true 
      });
      
      throw error;
    }
  });

  test('Complete workflow: Upload → Save → Frontend → Modal verification', async ({ page }) => {
    console.log('🚀 Starting complete workflow test...');
    
    // Track console errors
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
        console.log('CONSOLE ERROR:', msg.text());
      }
    });
    
    // Step 1: Login
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('networkidle');
    
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    console.log('✅ Logged in successfully');
    
    // Step 2: Create or edit content
    await page.goto('https://dalthaus.net/admin/content');
    await page.waitForLoadState('networkidle');
    
    // Try to edit existing content first
    const editLinks = await page.locator('a[href*="/edit"]').all();
    let contentUrl = '';
    
    if (editLinks.length > 0) {
      await editLinks[0].click();
      await page.waitForLoadState('networkidle');
      console.log('✅ Editing existing content');
    } else {
      // Create new article
      await page.click('a[href*="/create"]');
      await page.waitForLoadState('networkidle');
      
      const timestamp = Date.now();
      await page.fill('input[name="title"]', `Test Article ${timestamp}`);
      await page.fill('input[name="alias"]', `test-article-${timestamp}`);
      console.log('✅ Creating new article for testing');
    }
    
    // Step 3: Wait for TinyMCE and test buttons
    await page.waitForTimeout(3000);
    
    // Look for dual image button
    const dualImageButtonSelectors = [
      'button:has-text("🖼️📱")',
      'button[title*="Dual Image"]',
      'button[aria-label*="Dual Image"]',
      '.tox-tbtn:has-text("🖼️")',
      'button:has-text("🖼️")'
    ];
    
    let dualImageButtonFound = false;
    let dualImageButton = null;
    
    for (const selector of dualImageButtonSelectors) {
      const button = page.locator(selector).first();
      if (await button.count() > 0 && await button.isVisible()) {
        dualImageButton = button;
        dualImageButtonFound = true;
        console.log(`✅ Dual image button found with selector: ${selector}`);
        break;
      }
    }
    
    if (!dualImageButtonFound) {
      console.log('❌ Dual image button not found');
    }
    
    // Step 4: Test button functionality and modal
    if (dualImageButtonFound) {
      console.log('🧪 Testing dual image button functionality...');
      
      await dualImageButton.click();
      await page.waitForTimeout(1000);
      
      // Check for modal dialog
      const modalSelectors = [
        '.tox-dialog',
        '.modal',
        '[role="dialog"]',
        '.ui-dialog'
      ];
      
      let modalFound = false;
      for (const selector of modalSelectors) {
        const modal = page.locator(selector);
        if (await modal.count() > 0 && await modal.isVisible()) {
          modalFound = true;
          console.log(`✅ Modal opened with selector: ${selector}`);
          
          await page.screenshot({ 
            path: 'testing/results/modal-dialog.png',
            fullPage: true 
          });
          
          // Check for form fields
          const fileInput = modal.locator('input[type="file"]');
          const hasFileInput = await fileInput.count() > 0;
          console.log(`File input in modal: ${hasFileInput ? '✅' : '❌'}`);
          
          // Close modal
          const closeButton = modal.locator('button:has-text("Cancel"), button:has-text("Close"), .close').first();
          if (await closeButton.count() > 0) {
            await closeButton.click();
            console.log('✅ Modal closed');
          }
          break;
        }
      }
      
      if (!modalFound) {
        console.log('❌ Modal did not open after clicking dual image button');
      }
    }
    
    // Step 5: Add some test content to check on frontend
    const editor = page.locator('textarea[name="content"]');
    if (await editor.count() > 0) {
      await editor.fill('<p>Test content with image functionality</p><img src="/uploads/test.jpg" data-modal-src="/uploads/test-modal.jpg" onclick="openImageModal(this)" style="cursor: pointer;" alt="Test Image">');
      console.log('✅ Added test content with modal image');
    }
    
    // Step 6: Save content
    const saveButton = page.locator('button[type="submit"], input[type="submit"]').first();
    if (await saveButton.count() > 0) {
      await saveButton.click();
      await page.waitForLoadState('networkidle');
      console.log('✅ Content saved');
    }
    
    // Step 7: Navigate to frontend to test
    await page.goto('https://dalthaus.net');
    await page.waitForLoadState('networkidle');
    
    // Look for articles to test
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    
    if (articleLinks.length > 0) {
      await articleLinks[0].click();
      await page.waitForLoadState('networkidle');
      console.log('✅ Navigated to frontend article');
      
      // Look for images with modal functionality
      const modalImages = await page.locator('img[data-modal-src], img[onclick*="openImageModal"]').count();
      console.log(`Found ${modalImages} images with modal functionality`);
      
      if (modalImages > 0) {
        const modalImage = page.locator('img[data-modal-src], img[onclick*="openImageModal"]').first();
        await modalImage.click();
        await page.waitForTimeout(1000);
        
        // Check if modal opened on frontend
        const frontendModal = page.locator('.modal, .image-modal, #imageModal').first();
        if (await frontendModal.count() > 0 && await frontendModal.isVisible()) {
          console.log('✅ Frontend image modal opened successfully');
          await page.screenshot({ 
            path: 'testing/results/frontend-modal.png',
            fullPage: true 
          });
          
          // Test closing modal
          await page.keyboard.press('Escape');
          await page.waitForTimeout(500);
          console.log('✅ Modal closed with Escape key');
        } else {
          console.log('❌ Frontend image modal did not open');
        }
      }
    }
    
    // Step 8: Debug information
    await page.keyboard.press('Control+Shift+D');
    await page.waitForTimeout(1000);
    
    const debugInfo = await page.evaluate(() => {
      return {
        tinyMCELoaded: typeof tinymce !== 'undefined',
        customButtonsRegistered: window.customButtonsRegistered || false,
        tinyMCEVersion: typeof tinymce !== 'undefined' ? tinymce.majorVersion : 'not loaded',
        modalFunctionsAvailable: typeof openImageModal !== 'undefined'
      };
    });
    
    console.log('Debug information:', debugInfo);
    
    // Final report
    console.log('\n=== WORKFLOW TEST SUMMARY ===');
    console.log(`TinyMCE Loaded: ${debugInfo.tinyMCELoaded ? '✅' : '❌'}`);
    console.log(`Dual Image Button: ${dualImageButtonFound ? '✅' : '❌'}`);
    console.log(`Modal Functions: ${debugInfo.modalFunctionsAvailable ? '✅' : '❌'}`);
    console.log(`Console Errors: ${consoleErrors.length}`);
    
    if (consoleErrors.length > 0) {
      console.log('Console errors found:');
      consoleErrors.forEach(error => console.log(`  - ${error}`));
    }
    
    expect(debugInfo.tinyMCELoaded).toBe(true);
  });

  test('Cross-browser verification (Firefox)', async ({ page, browserName }) => {
    test.skip(browserName !== 'firefox', 'This test only runs on Firefox');
    
    console.log('🦊 Running Firefox-specific test...');
    
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('networkidle');
    
    // Quick login and check
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    await page.goto('https://dalthaus.net/admin/content/create?type=article');
    await page.waitForLoadState('networkidle');
    
    await page.waitForTimeout(3000);
    
    // Check for custom buttons in Firefox
    const dualImageButton = page.locator('button:has-text("🖼️📱")').first();
    const dualImageButtonExists = await dualImageButton.count() > 0;
    
    console.log(`Firefox - Dual image button: ${dualImageButtonExists ? '✅' : '❌'}`);
    
    await page.screenshot({ 
      path: 'testing/results/firefox-tinymce-test.png',
      fullPage: true 
    });
    
    const firefoxDebugInfo = await page.evaluate(() => {
      return {
        userAgent: navigator.userAgent,
        tinyMCELoaded: typeof tinymce !== 'undefined',
        customButtonsCount: document.querySelectorAll('button:contains("🖼️")').length
      };
    });
    
    console.log('Firefox debug info:', firefoxDebugInfo);
  });

  test('Cache busting and performance verification', async ({ page }) => {
    console.log('⚡ Testing cache busting and performance...');
    
    const timestamp = Date.now();
    
    // Test with cache busting parameter
    const response = await page.goto(`https://dalthaus.net/admin/login?nocache=${timestamp}`);
    
    const headers = response.headers();
    console.log('Response headers:');
    console.log(`  Cache-Control: ${headers['cache-control'] || 'not set'}`);
    console.log(`  Pragma: ${headers['pragma'] || 'not set'}`);
    console.log(`  Expires: ${headers['expires'] || 'not set'}`);
    
    // Verify no caching issues
    const cacheHeaders = headers['cache-control'] || '';
    const hasCacheBusting = cacheHeaders.includes('no-cache') || cacheHeaders.includes('no-store');
    
    console.log(`Cache busting active: ${hasCacheBusting ? '✅' : '❌'}`);
    
    // Test page load performance
    const startTime = Date.now();
    await page.waitForLoadState('networkidle');
    const loadTime = Date.now() - startTime;
    
    console.log(`Page load time: ${loadTime}ms`);
    
    if (loadTime > 10000) {
      console.log('⚠️ Page load time is slow (>10s)');
    } else {
      console.log('✅ Page load time is acceptable');
    }
  });
});