/**
 * E2E Test Suite for Database Configuration Issue
 * Tests the live website to identify why configuration keeps reverting
 */

const { test, expect } = require('@playwright/test');
const fs = require('fs').promises;
const path = require('path');

test.describe('Database Configuration Root Cause Analysis', () => {
  test.setTimeout(60000); // 60 second timeout for all tests

  test('should check admin page database error', async ({ page }) => {
    console.log('=== TEST 1: Admin Page Database Error Check ===');
    
    // Navigate to admin page
    const response = await page.goto('https://dalthaus.net/admin', {
      waitUntil: 'networkidle'
    });

    // Check response status
    const status = response.status();
    console.log(`Response status: ${status}`);

    // Take screenshot for evidence
    await page.screenshot({ 
      path: 'test-results/admin-error.png',
      fullPage: true 
    });

    // Check for database error message
    const pageContent = await page.content();
    
    // Look for specific error patterns
    const errorPatterns = [
      /Database connection failed/i,
      /Access denied for user/i,
      /cms_db/,
      /cms_user/,
      /Connection refused/i,
      /Unknown database/i
    ];

    const foundErrors = [];
    for (const pattern of errorPatterns) {
      if (pattern.test(pageContent)) {
        foundErrors.push(pattern.toString());
      }
    }

    if (foundErrors.length > 0) {
      console.log('✗ Database errors found:');
      foundErrors.forEach(error => console.log(`  - ${error}`));
      
      // Extract specific error message if visible
      const errorMessage = await page.locator('text=/error|failed|denied/i').first().textContent().catch(() => null);
      if (errorMessage) {
        console.log(`  Error message: ${errorMessage}`);
      }
    } else {
      console.log('✓ No database errors visible on page');
    }

    // Check if it's trying to use wrong credentials
    if (pageContent.includes('cms_db') || pageContent.includes('cms_user')) {
      console.log('✗ Page is using incorrect database credentials (cms_db/cms_user)');
      expect.fail('Wrong database credentials being used');
    }
  });

  test('should test diagnose.php endpoint', async ({ page }) => {
    console.log('\n=== TEST 2: Diagnose.php Server Configuration ===');
    
    const response = await page.goto('https://dalthaus.net/diagnose.php', {
      waitUntil: 'networkidle'
    });

    const status = response.status();
    console.log(`Response status: ${status}`);

    if (status === 200) {
      const content = await page.content();
      
      // Check for PHP info or configuration details
      if (content.includes('PHP Version')) {
        console.log('✓ Diagnose page accessible');
        
        // Look for database configuration info
        const dbPatterns = [
          /Database:.*?([\w_]+)/,
          /DB_NAME.*?([\w_]+)/,
          /mysql.*?([\w_]+)/
        ];

        for (const pattern of dbPatterns) {
          const match = content.match(pattern);
          if (match) {
            console.log(`  Found config: ${match[0]}`);
          }
        }
      }
    } else if (status === 404) {
      console.log('✗ diagnose.php not found (404)');
    } else {
      console.log(`✗ Unexpected status: ${status}`);
    }

    await page.screenshot({ 
      path: 'test-results/diagnose.png',
      fullPage: true 
    });
  });

  test('should test agent.php endpoint', async ({ page }) => {
    console.log('\n=== TEST 3: Agent.php Test Endpoint ===');
    
    const agentUrl = 'https://dalthaus.net/agent.php?action=test&key=dalthaus_agent_key_2025';
    const response = await page.goto(agentUrl, {
      waitUntil: 'networkidle'
    });

    const status = response.status();
    console.log(`Response status: ${status}`);

    if (status === 200) {
      const content = await page.content();
      
      // Check if response is JSON
      try {
        const jsonMatch = content.match(/{.*}/s);
        if (jsonMatch) {
          const data = JSON.parse(jsonMatch[0]);
          
          if (data.success) {
            console.log('✓ Agent test successful');
            if (data.database_config) {
              console.log(`  Database: ${data.database_config.dbname || 'unknown'}`);
              console.log(`  Username: ${data.database_config.username || 'unknown'}`);
              
              // Check if wrong config is being used
              if (data.database_config.dbname === 'cms_db') {
                console.log('✗ Agent is using wrong database name (cms_db)');
              }
            }
          } else {
            console.log('✗ Agent test failed');
            if (data.error) {
              console.log(`  Error: ${data.error}`);
            }
          }
        }
      } catch (e) {
        console.log('  Could not parse JSON response');
        console.log(`  Content preview: ${content.substring(0, 200)}`);
      }
    } else {
      console.log(`✗ Agent returned status ${status}`);
    }

    await page.screenshot({ 
      path: 'test-results/agent-test.png',
      fullPage: true 
    });
  });

  test('should check for config modification patterns', async ({ page }) => {
    console.log('\n=== TEST 4: Configuration Modification Patterns ===');
    
    // Test if there's a deployment script running
    const deployUrls = [
      'https://dalthaus.net/deploy.php',
      'https://dalthaus.net/update.php',
      'https://dalthaus.net/install.php',
      'https://dalthaus.net/setup.php'
    ];

    for (const url of deployUrls) {
      try {
        const response = await page.goto(url, {
          waitUntil: 'networkidle',
          timeout: 5000
        });
        
        if (response.status() === 200) {
          console.log(`⚠ Found accessible deployment script: ${url}`);
          const content = await page.content();
          if (content.includes('config') || content.includes('database')) {
            console.log('  ✗ Script may modify configuration!');
          }
        }
      } catch (e) {
        // Expected - most should not exist
      }
    }

    // Check if production config is being loaded
    const checkConfigUrl = 'https://dalthaus.net/check_config.php';
    try {
      const response = await page.goto(checkConfigUrl, {
        timeout: 5000
      });
      
      if (response.status() === 200) {
        const content = await page.content();
        console.log('✓ Config check endpoint exists');
        console.log(`  Content: ${content.substring(0, 200)}`);
      }
    } catch (e) {
      console.log('  check_config.php not accessible');
    }
  });

  test('should test homepage for database connectivity', async ({ page }) => {
    console.log('\n=== TEST 5: Homepage Database Connectivity ===');
    
    const response = await page.goto('https://dalthaus.net/', {
      waitUntil: 'networkidle'
    });

    const status = response.status();
    console.log(`Response status: ${status}`);

    if (status === 200) {
      console.log('✓ Homepage loads successfully');
      
      // Check for database errors on homepage
      const content = await page.content();
      if (content.includes('Database') && content.includes('error')) {
        console.log('✗ Database error on homepage');
      } else {
        console.log('✓ No visible database errors on homepage');
      }
      
      // Check if dynamic content is loading (indicates DB connection)
      const hasContent = await page.locator('.content-item, .article, .post').count();
      if (hasContent > 0) {
        console.log(`✓ Dynamic content found (${hasContent} items) - DB likely working`);
      } else {
        console.log('⚠ No dynamic content found - might indicate DB issue');
      }
    } else {
      console.log(`✗ Homepage returned status ${status}`);
    }

    await page.screenshot({ 
      path: 'test-results/homepage.png',
      fullPage: true 
    });
  });

  test('should perform root cause analysis', async ({ page }) => {
    console.log('\n=== ROOT CAUSE ANALYSIS ===');
    
    // Create a test script on the server to check configuration
    const testScriptContent = `<?php
    header('Content-Type: application/json');
    
    // Check which config is being loaded
    $configPath = __DIR__ . '/config/config.php';
    $prodConfigPath = __DIR__ . '/config/config.production.php';
    
    $result = [
      'config_exists' => file_exists($configPath),
      'prod_config_exists' => file_exists($prodConfigPath),
      'env_vars' => [
        'DB_HOST' => getenv('DB_HOST'),
        'DB_NAME' => getenv('DB_NAME'),
        'DB_USER' => getenv('DB_USER'),
        'DB_PASSWORD' => getenv('DB_PASSWORD') ? '***set***' : false
      ]
    ];
    
    if (file_exists($configPath)) {
      $config = require $configPath;
      $result['loaded_config'] = [
        'dbname' => $config['database']['dbname'],
        'username' => $config['database']['username']
      ];
    }
    
    if (file_exists($prodConfigPath) && !getenv('DB_NAME')) {
      $prodConfig = require $prodConfigPath;
      $result['prod_config_fallback'] = [
        'dbname' => $prodConfig['database']['dbname'],
        'username' => $prodConfig['database']['username']
      ];
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    ?>`;

    // Save test script locally
    await fs.writeFile(
      path.join(__dirname, '../../test_config_check.php'),
      testScriptContent
    );

    console.log('\nRoot Cause Hypothesis:');
    console.log('1. Environment variables are not set on the production server');
    console.log('2. config.production.php exists and uses fallback values when env vars are missing');
    console.log('3. The fallback values are cms_db and cms_user (incorrect)');
    console.log('4. Some deployment process might be using config.production.php instead of config.php');
    
    console.log('\nRecommended Fixes:');
    console.log('1. Set environment variables on the server:');
    console.log('   export DB_HOST=localhost');
    console.log('   export DB_NAME=dalthaus_maincms');
    console.log('   export DB_USER=dalthaus_maincms');
    console.log('   export DB_PASSWORD=f4!,Wpds=w6*=~+1');
    console.log('');
    console.log('2. OR remove/rename config.production.php if not needed');
    console.log('');
    console.log('3. OR update config.production.php fallback values to correct ones');
    console.log('');
    console.log('4. Check if any deployment scripts are overwriting config.php');
  });
});

// Test configuration
module.exports = {
  use: {
    headless: true,
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    trace: 'on-first-retry',
  },
  
  reporter: [
    ['list'],
    ['html', { outputFolder: 'test-results/html' }],
    ['json', { outputFile: 'test-results/results.json' }]
  ],
  
  outputDir: 'test-results/',
};