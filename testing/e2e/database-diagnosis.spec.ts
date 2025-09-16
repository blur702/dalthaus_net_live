import { test, expect, Page } from '@playwright/test';
import { writeFileSync } from 'fs';
import { join } from 'path';

// Test configuration
const SITE_URL = 'https://dalthaus.net';
const AGENT_KEY = 'dalthaus_agent_key_2025';

test.describe('Database Connection Diagnosis', () => {
  let page: Page;

  test.beforeEach(async ({ page: testPage }) => {
    page = testPage;
    
    // Set longer timeout for network requests
    page.setDefaultTimeout(60000);
    
    // Log network requests and responses
    page.on('request', request => {
      console.log(`→ ${request.method()} ${request.url()}`);
    });
    
    page.on('response', response => {
      console.log(`← ${response.status()} ${response.url()}`);
    });
    
    // Log console messages from the page
    page.on('console', msg => {
      console.log(`Console [${msg.type()}]:`, msg.text());
    });
    
    // Log page errors
    page.on('pageerror', error => {
      console.error('Page error:', error);
    });
  });

  test('Admin page database connection diagnosis', async () => {
    console.log('\n=== Testing Admin Page ===');
    
    try {
      // Navigate to admin page
      const adminResponse = await page.goto(`${SITE_URL}/admin`);
      
      // Take screenshot of admin page
      await page.screenshot({ 
        path: 'test-results/admin-page-diagnosis.png', 
        fullPage: true 
      });
      
      // Check response status
      console.log('Admin page response status:', adminResponse?.status());
      
      // Get page content
      const adminContent = await page.content();
      
      // Look for database connection errors
      const hasDbError = adminContent.includes('cms_db') || 
                        adminContent.includes('cms_user') ||
                        adminContent.includes('Connection failed') ||
                        adminContent.includes('database connection') ||
                        adminContent.includes('SQLSTATE');
      
      // Look for correct database references
      const hasCorrectDb = adminContent.includes('dalthaus_maincms');
      
      console.log('Database error indicators found:', hasDbError);
      console.log('Correct database references found:', hasCorrectDb);
      
      // Extract visible text for analysis
      const bodyText = await page.locator('body').textContent();
      
      // Save page content for analysis
      writeFileSync(join('test-results', 'admin-page-content.html'), adminContent);
      writeFileSync(join('test-results', 'admin-page-text.txt'), bodyText || '');
      
      // Check if we can see the login form or are redirected
      const hasLoginForm = await page.locator('input[type="password"]').count() > 0;
      const hasErrorMessage = await page.locator('.error, .alert-danger, .message').count() > 0;
      
      console.log('Has login form:', hasLoginForm);
      console.log('Has error message:', hasErrorMessage);
      
      if (hasErrorMessage) {
        const errorText = await page.locator('.error, .alert-danger, .message').first().textContent();
        console.log('Error message:', errorText);
      }
      
      // Log current URL (check for redirects)
      console.log('Final URL:', page.url());
      
    } catch (error) {
      console.error('Admin page test error:', error);
      await page.screenshot({ 
        path: 'test-results/admin-page-error.png', 
        fullPage: true 
      });
    }
  });

  test('Diagnose page configuration check', async () => {
    console.log('\n=== Testing Diagnose Page ===');
    
    try {
      // Navigate to diagnose page
      const diagnoseResponse = await page.goto(`${SITE_URL}/diagnose.php`);
      
      // Take screenshot
      await page.screenshot({ 
        path: 'test-results/diagnose-page.png', 
        fullPage: true 
      });
      
      console.log('Diagnose page response status:', diagnoseResponse?.status());
      
      // Get page content
      const diagnoseContent = await page.content();
      
      // Save content for analysis
      writeFileSync(join('test-results', 'diagnose-page-content.html'), diagnoseContent);
      
      // Look for database configuration information
      const dbConfigMatch = diagnoseContent.match(/Database.*?Host.*?:.*?(\w+)/i);
      const dbNameMatch = diagnoseContent.match(/Database.*?Name.*?:.*?(\w+)/i);
      const dbUserMatch = diagnoseContent.match(/Database.*?User.*?:.*?(\w+)/i);
      
      console.log('Database host found:', dbConfigMatch?.[1]);
      console.log('Database name found:', dbNameMatch?.[1]);
      console.log('Database user found:', dbUserMatch?.[1]);
      
      // Extract all text for analysis
      const diagnoseText = await page.locator('body').textContent();
      writeFileSync(join('test-results', 'diagnose-page-text.txt'), diagnoseText || '');
      
      // Check for specific config values
      const hasOldDb = diagnoseContent.includes('cms_db') || diagnoseContent.includes('cms_user');
      const hasCorrectDb = diagnoseContent.includes('dalthaus_maincms');
      
      console.log('Contains old database references:', hasOldDb);
      console.log('Contains correct database references:', hasCorrectDb);
      
    } catch (error) {
      console.error('Diagnose page test error:', error);
      await page.screenshot({ 
        path: 'test-results/diagnose-page-error.png', 
        fullPage: true 
      });
    }
  });

  test('Agent status and configuration test', async () => {
    console.log('\n=== Testing Agent Status ===');
    
    try {
      // Navigate to agent test page
      const agentUrl = `${SITE_URL}/agent.php?action=test&key=${AGENT_KEY}`;
      const agentResponse = await page.goto(agentUrl);
      
      // Take screenshot
      await page.screenshot({ 
        path: 'test-results/agent-test.png', 
        fullPage: true 
      });
      
      console.log('Agent test response status:', agentResponse?.status());
      
      // Get page content
      const agentContent = await page.content();
      
      // Save content for analysis
      writeFileSync(join('test-results', 'agent-test-content.html'), agentContent);
      
      // Extract text content
      const agentText = await page.locator('body').textContent();
      writeFileSync(join('test-results', 'agent-test-text.txt'), agentText || '');
      
      console.log('Agent response preview:', agentText?.substring(0, 500));
      
      // Check for JSON response
      let agentData = null;
      try {
        // Try to parse as JSON
        agentData = JSON.parse(agentText || '');
        console.log('Agent JSON response:', JSON.stringify(agentData, null, 2));
      } catch {
        console.log('Agent response is not valid JSON');
      }
      
      // Look for database configuration in agent response
      if (agentData && typeof agentData === 'object') {
        console.log('Database config in agent response:', agentData.database_config || 'Not found');
        console.log('Config file path in agent response:', agentData.config_file || 'Not found');
      }
      
    } catch (error) {
      console.error('Agent test error:', error);
      await page.screenshot({ 
        path: 'test-results/agent-test-error.png', 
        fullPage: true 
      });
    }
  });

  test('Configuration file analysis via different endpoints', async () => {
    console.log('\n=== Testing Configuration Endpoints ===');
    
    // Test different diagnostic endpoints that might exist
    const endpoints = [
      '/check_server_config.php',
      '/test_config_check.php', 
      '/debug.php',
      '/show_errors.php',
      '/test_database_config.php'
    ];
    
    for (const endpoint of endpoints) {
      try {
        console.log(`\n--- Testing ${endpoint} ---`);
        
        const response = await page.goto(`${SITE_URL}${endpoint}`, { 
          waitUntil: 'networkidle',
          timeout: 30000
        });
        
        const status = response?.status();
        console.log(`${endpoint} response status:`, status);
        
        if (status === 200) {
          // Take screenshot
          await page.screenshot({ 
            path: `test-results/endpoint-${endpoint.replace(/[^a-zA-Z0-9]/g, '-')}.png`, 
            fullPage: true 
          });
          
          // Get content
          const content = await page.content();
          const text = await page.locator('body').textContent();
          
          // Save content
          const safeName = endpoint.replace(/[^a-zA-Z0-9]/g, '-');
          writeFileSync(join('test-results', `endpoint-${safeName}-content.html`), content);
          writeFileSync(join('test-results', `endpoint-${safeName}-text.txt`), text || '');
          
          // Check for database references
          const hasOldDb = content.includes('cms_db') || content.includes('cms_user');
          const hasCorrectDb = content.includes('dalthaus_maincms');
          
          console.log(`${endpoint} - Old DB refs:`, hasOldDb);
          console.log(`${endpoint} - Correct DB refs:`, hasCorrectDb);
          
          // Log preview of content
          console.log(`${endpoint} preview:`, text?.substring(0, 300));
        }
        
      } catch (error) {
        console.log(`${endpoint} failed:`, error.message);
      }
    }
  });

  test('Network and file system analysis', async () => {
    console.log('\n=== Network and File System Analysis ===');
    
    try {
      // Check if we can access the main site
      const homeResponse = await page.goto(SITE_URL);
      console.log('Homepage response status:', homeResponse?.status());
      
      await page.screenshot({ 
        path: 'test-results/homepage.png', 
        fullPage: true 
      });
      
      // Check for any config-related files that might be publicly accessible
      const configFiles = [
        '/config.php',
        '/config/config.php', 
        '/config.production.php',
        '/.env',
        '/composer.json',
        '/phpinfo.php'
      ];
      
      for (const configFile of configFiles) {
        try {
          const response = await page.goto(`${SITE_URL}${configFile}`);
          const status = response?.status();
          console.log(`${configFile} status:`, status);
          
          if (status === 200) {
            console.log(`⚠️  Config file ${configFile} is publicly accessible!`);
            const content = await page.content();
            writeFileSync(join('test-results', `public-${configFile.replace(/[^a-zA-Z0-9]/g, '-')}.html`), content);
          }
        } catch (error) {
          // Expected for most files
        }
      }
      
    } catch (error) {
      console.error('Network analysis error:', error);
    }
  });

  test('Database connection attempt simulation', async () => {
    console.log('\n=== Database Connection Simulation ===');
    
    // This test will try to trigger database operations through various pages
    const pagesWithDbAccess = [
      '/',
      '/admin',
      '/articles',
      '/photobooks'
    ];
    
    for (const pagePath of pagesWithDbAccess) {
      try {
        console.log(`\n--- Testing database access on ${pagePath} ---`);
        
        const response = await page.goto(`${SITE_URL}${pagePath}`);
        const status = response?.status();
        
        console.log(`${pagePath} status:`, status);
        
        // Look for database-related errors in the page
        const content = await page.content();
        const text = await page.locator('body').textContent();
        
        const hasDbError = content.match(/(SQLSTATE|Connection failed|Access denied|Unknown database)/i);
        const hasConfigError = content.match(/(cms_db|cms_user)/i);
        
        if (hasDbError) {
          console.log(`🔴 Database error on ${pagePath}:`, hasDbError[0]);
        }
        
        if (hasConfigError) {
          console.log(`🔴 Config error on ${pagePath}:`, hasConfigError[0]);
        }
        
        // Save screenshots of pages with errors
        if (hasDbError || hasConfigError) {
          await page.screenshot({ 
            path: `test-results/db-error-${pagePath.replace(/[^a-zA-Z0-9]/g, '-')}.png`, 
            fullPage: true 
          });
        }
        
      } catch (error) {
        console.log(`${pagePath} test failed:`, error.message);
      }
    }
  });

  test.afterEach(async () => {
    // Generate summary report
    const timestamp = new Date().toISOString();
    const summary = {
      timestamp,
      test_run: 'Database Connection Diagnosis',
      site_url: SITE_URL,
      expected_db: 'dalthaus_maincms',
      expected_user: 'dalthaus_maincms',
      problem_indicators: [
        'cms_db references found',
        'cms_user references found', 
        'Connection failed errors',
        'SQLSTATE errors'
      ]
    };
    
    writeFileSync(join('test-results', 'test-summary.json'), JSON.stringify(summary, null, 2));
    console.log('\n=== Test Summary Generated ===');
  });
});