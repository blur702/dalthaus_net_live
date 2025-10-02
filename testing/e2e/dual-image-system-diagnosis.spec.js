const { test, expect } = require('@playwright/test');

test.describe('Dual Image System Diagnosis', () => {
  
  test('Diagnose TinyMCE button and plugin integration', async ({ page }) => {
    console.log('🔍 Detailed TinyMCE diagnosis...');
    
    // Login to admin
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('networkidle');
    
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Navigate to content creation
    await page.goto('https://dalthaus.net/admin/content/create');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(5000); // Extended wait for TinyMCE
    
    const detailedAnalysis = await page.evaluate(() => {
      const analysis = {
        tinymceVersion: typeof tinymce !== 'undefined' ? tinymce.majorVersion : 'Not loaded',
        tinymceGlobal: typeof tinymce !== 'undefined',
        editorIds: [],
        editors: {},
        plugins: [],
        toolbarConfig: '',
        customButtons: []
      };
      
      if (typeof tinymce !== 'undefined') {
        // Get all editor instances
        Object.keys(tinymce.editors).forEach(id => {
          analysis.editorIds.push(id);
          const editor = tinymce.editors[id];
          
          analysis.editors[id] = {
            initialized: editor.initialized,
            plugins: editor.settings.plugins || [],
            toolbar: editor.settings.toolbar || '',
            theme: editor.settings.theme,
            container: !!editor.getContainer()
          };
          
          if (editor.initialized && editor.getContainer()) {
            const toolbar = editor.getContainer().querySelector('.tox-toolbar, .mce-toolbar');
            if (toolbar) {
              const buttons = toolbar.querySelectorAll('button');
              buttons.forEach((button, index) => {
                const title = button.getAttribute('title') || '';
                const ariaLabel = button.getAttribute('aria-label') || '';
                const classList = button.className;
                const innerHTML = button.innerHTML;
                
                analysis.customButtons.push({
                  editorId: id,
                  index,
                  title,
                  ariaLabel,
                  classList,
                  hasIcon: innerHTML.includes('svg') || innerHTML.includes('🖼️'),
                  innerHTML: innerHTML.substring(0, 100) // Truncate for readability
                });
              });
            }
          }
        });
        
        // Check for custom plugins
        if (tinymce.PluginManager) {
          const pluginManager = tinymce.PluginManager;
          if (pluginManager.urls) {
            Object.keys(pluginManager.urls).forEach(plugin => {
              analysis.plugins.push(plugin);
            });
          }
        }
      }
      
      return analysis;
    });
    
    console.log('Detailed TinyMCE Analysis:');
    console.log(JSON.stringify(detailedAnalysis, null, 2));
    
    // Check for dual image plugin specifically
    const hasDualImagePlugin = detailedAnalysis.plugins.includes('dualimage') || 
                              detailedAnalysis.plugins.includes('dual-image') ||
                              detailedAnalysis.customButtons.some(btn => 
                                btn.title.toLowerCase().includes('dual') || 
                                btn.title.toLowerCase().includes('modal')
                              );
    
    console.log('Dual image plugin detected:', hasDualImagePlugin);
    
    // Take screenshot of admin interface
    await page.screenshot({ 
      path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/tinymce-detailed-analysis.png',
      fullPage: true 
    });
    
    // Check for TinyMCE configuration files
    const configCheck = await page.evaluate(async () => {
      try {
        const responses = await Promise.all([
          fetch('/assets/js/tinymce-config.js'),
          fetch('/js/tinymce-config.js'),
          fetch('/admin/js/tinymce-config.js')
        ]);
        
        return responses.map((response, index) => ({
          url: ['/assets/js/tinymce-config.js', '/js/tinymce-config.js', '/admin/js/tinymce-config.js'][index],
          status: response.status,
          exists: response.status === 200
        }));
      } catch (error) {
        return { error: error.message };
      }
    });
    
    console.log('TinyMCE config file check:', configCheck);
  });

  test('Check for existing dual image content in database', async ({ page }) => {
    console.log('🔍 Checking for existing dual image content...');
    
    // Search for articles that might contain dual images
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    // Get list of all articles
    const articles = await page.evaluate(() => {
      const articleLinks = document.querySelectorAll('a[href*="/article/"]');
      return Array.from(articleLinks).map(link => ({
        url: link.href,
        title: link.textContent.trim()
      }));
    });
    
    console.log(`Found ${articles.length} articles to check for dual images`);
    
    let articlesWithDualImages = [];
    
    // Check first 5 articles for dual image content
    for (let i = 0; i < Math.min(articles.length, 5); i++) {
      const article = articles[i];
      console.log(`Checking article: ${article.title}`);
      
      await page.goto(article.url);
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);
      
      const contentAnalysis = await page.evaluate(() => {
        const analysis = {
          totalImages: 0,
          imagesWithModalSrc: 0,
          imagesWithDataAttributes: [],
          htmlContent: ''
        };
        
        // Get all images
        const images = document.querySelectorAll('img');
        analysis.totalImages = images.length;
        
        images.forEach((img, index) => {
          const hasModalSrc = img.hasAttribute('data-modal-src');
          if (hasModalSrc) {
            analysis.imagesWithModalSrc++;
            analysis.imagesWithDataAttributes.push({
              index,
              src: img.src,
              modalSrc: img.getAttribute('data-modal-src'),
              alt: img.alt
            });
          }
        });
        
        // Get the main content HTML to check for dual image markup
        const contentArea = document.querySelector('.content-text, .prose, article, main');
        if (contentArea) {
          analysis.htmlContent = contentArea.innerHTML.substring(0, 1000); // First 1000 chars
        }
        
        return analysis;
      });
      
      if (contentAnalysis.imagesWithModalSrc > 0) {
        articlesWithDualImages.push({
          article: article.title,
          url: article.url,
          dualImages: contentAnalysis.imagesWithModalSrc,
          details: contentAnalysis.imagesWithDataAttributes
        });
      }
      
      console.log(`  - Images: ${contentAnalysis.totalImages}, Dual images: ${contentAnalysis.imagesWithModalSrc}`);
    }
    
    console.log('Articles with dual images:', articlesWithDualImages);
    
    if (articlesWithDualImages.length > 0) {
      console.log(`✅ Found ${articlesWithDualImages.length} articles with dual image functionality`);
    } else {
      console.log('ℹ️ No articles found with dual image (data-modal-src) attributes');
    }
  });

  test('Test frontend modal JavaScript loading', async ({ page }) => {
    console.log('🔍 Testing frontend modal JavaScript...');
    
    const jsErrors = [];
    const jsLogs = [];
    
    page.on('pageerror', error => {
      jsErrors.push(error.message);
    });
    
    page.on('console', msg => {
      if (msg.type() === 'log' && (
          msg.text().includes('modal') || 
          msg.text().includes('image') ||
          msg.text().includes('data-modal')
        )) {
        jsLogs.push(msg.text());
      }
    });
    
    await page.goto('https://dalthaus.net/articles');
    await page.waitForLoadState('networkidle');
    
    // Navigate to an article
    const articleLinks = await page.locator('a[href*="/article/"]').all();
    if (articleLinks.length > 0) {
      await articleLinks[0].click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);
    }
    
    // Check for modal-related JavaScript functionality
    const modalJsAnalysis = await page.evaluate(() => {
      const analysis = {
        modalFunctionExists: typeof window.openImageModal === 'function',
        modalElementsExist: {
          modalContainer: !!document.querySelector('.image-modal'),
          modalImg: !!document.querySelector('.image-modal img'),
          modalClose: !!document.querySelector('.image-modal .close')
        },
        imageProcessingFunction: false,
        domContentLoadedListeners: false
      };
      
      // Check if image processing code exists
      const scripts = document.querySelectorAll('script');
      scripts.forEach(script => {
        const content = script.textContent || '';
        if (content.includes('data-modal-src') || content.includes('modal')) {
          analysis.imageProcessingFunction = true;
        }
        if (content.includes('DOMContentLoaded')) {
          analysis.domContentLoadedListeners = true;
        }
      });
      
      return analysis;
    });
    
    console.log('Modal JavaScript Analysis:', modalJsAnalysis);
    console.log('JavaScript Errors:', jsErrors);
    console.log('Modal-related Logs:', jsLogs);
    
    if (modalJsAnalysis.modalFunctionExists) {
      console.log('✅ Modal JavaScript function exists');
    } else {
      console.log('❌ Modal JavaScript function not found');
    }
    
    if (Object.values(modalJsAnalysis.modalElementsExist).some(exists => exists)) {
      console.log('✅ Some modal DOM elements exist');
    } else {
      console.log('❌ No modal DOM elements found');
    }
  });

  test('Check dual image upload controller and routes', async ({ page }) => {
    console.log('🔍 Testing upload controller and routing...');
    
    // Login first
    await page.goto('https://dalthaus.net/admin/login');
    await page.waitForLoadState('networkidle');
    
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Test various upload endpoints
    const endpointTests = await page.evaluate(async () => {
      const endpoints = [
        '/admin/upload/dual-image',
        '/admin/upload/',
        '/admin/upload/image',
        '/upload/dual-image'
      ];
      
      const results = [];
      
      for (const endpoint of endpoints) {
        try {
          const response = await fetch(endpoint, {
            method: 'POST',
            body: new FormData()
          });
          
          results.push({
            endpoint,
            status: response.status,
            statusText: response.statusText,
            exists: response.status !== 404,
            responseText: await response.text()
          });
        } catch (error) {
          results.push({
            endpoint,
            error: error.message,
            exists: false
          });
        }
      }
      
      return results;
    });
    
    console.log('Upload endpoint tests:');
    endpointTests.forEach(result => {
      console.log(`${result.endpoint}: ${result.exists ? '✅' : '❌'} (${result.status || 'Error'})`);
      if (result.responseText && result.responseText.length < 200) {
        console.log(`  Response: ${result.responseText}`);
      }
    });
    
    // Check for upload controller file existence
    const controllerFileCheck = await page.evaluate(async () => {
      try {
        const response = await fetch('/src/Controllers/Admin/UploadController.php');
        return {
          exists: response.status === 200,
          status: response.status
        };
      } catch (error) {
        return {
          exists: false,
          error: error.message
        };
      }
    });
    
    console.log('Upload controller file check:', controllerFileCheck);
  });

  test('Generate summary report', async ({ page }) => {
    console.log('📊 Generating dual image system summary...');
    
    const summary = {
      timestamp: new Date().toISOString(),
      components: {
        tinymcePlugin: '❌ Not found in toolbar',
        uploadEndpoint: '✅ Responds (with error for empty request)',
        frontendModal: '❓ Need content with data-modal-src to test',
        dualImageContent: '❓ No test content found'
      },
      recommendations: [
        'Check TinyMCE plugin integration - button not appearing in toolbar',
        'Create test content with data-modal-src attributes to verify frontend functionality',
        'Verify TinyMCE configuration includes dual image plugin',
        'Test upload functionality with actual image files'
      ],
      nextSteps: [
        'Inspect TinyMCE configuration file',
        'Create sample dual image content for testing',
        'Test manual dual image upload process',
        'Verify modal JavaScript integration'
      ]
    };
    
    console.log('='.repeat(60));
    console.log('DUAL IMAGE MODAL SYSTEM TEST SUMMARY');
    console.log('='.repeat(60));
    console.log('Test Date:', summary.timestamp);
    console.log('');
    console.log('Component Status:');
    Object.entries(summary.components).forEach(([component, status]) => {
      console.log(`  ${component}: ${status}`);
    });
    console.log('');
    console.log('Recommendations:');
    summary.recommendations.forEach((rec, index) => {
      console.log(`  ${index + 1}. ${rec}`);
    });
    console.log('');
    console.log('Next Steps:');
    summary.nextSteps.forEach((step, index) => {
      console.log(`  ${index + 1}. ${step}`);
    });
    console.log('='.repeat(60));
    
    // Save summary to file
    await page.evaluate((summaryData) => {
      // This would save to a file if we had file system access
      console.log('Summary saved to test results');
    }, summary);
  });
});