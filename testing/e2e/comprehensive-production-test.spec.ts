import { test, expect, Page } from '@playwright/test';
import { writeFileSync, mkdirSync } from 'fs';
import { join, dirname } from 'path';

// Test configuration
const SITE_URL = 'https://dalthaus.net';
const AGENT_KEY = 'dalthaus_agent_key_2025';

// Helper function to ensure directory exists
function ensureDir(filePath: string) {
  const dir = dirname(filePath);
  try {
    mkdirSync(dir, { recursive: true });
  } catch (e) {
    // Directory might already exist
  }
}

// Helper function to save test artifacts
async function saveTestArtifacts(page: Page, testName: string, prefix: string = '') {
  const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
  const namePrefix = prefix ? `${prefix}-` : '';
  const baseName = `${namePrefix}${testName}-${timestamp}`;
  
  try {
    // Save screenshot
    const screenshotPath = join('test-results', `${baseName}-screenshot.png`);
    ensureDir(screenshotPath);
    await page.screenshot({ 
      path: screenshotPath, 
      fullPage: true 
    });
    
    // Save HTML content
    const content = await page.content();
    const htmlPath = join('test-results', `${baseName}-content.html`);
    ensureDir(htmlPath);
    writeFileSync(htmlPath, content);
    
    // Save text content
    const textContent = await page.locator('body').textContent();
    const textPath = join('test-results', `${baseName}-text.txt`);
    ensureDir(textPath);
    writeFileSync(textPath, textContent || '');
    
    return {
      screenshot: screenshotPath,
      html: htmlPath,
      text: textPath,
      content,
      textContent
    };
  } catch (error) {
    console.error(`Error saving test artifacts for ${testName}:`, error);
    return null;
  }
}

// Test suite for comprehensive production site testing
test.describe('Comprehensive Production Site Testing', () => {
  let page: Page;
  let testResults: any[] = [];
  let consoleErrors: string[] = [];
  let pageErrors: string[] = [];
  let networkErrors: any[] = [];

  test.beforeEach(async ({ page: testPage }) => {
    page = testPage;
    consoleErrors = [];
    pageErrors = [];
    networkErrors = [];
    
    // Set longer timeout for network requests
    page.setDefaultTimeout(60000);
    
    // Capture console errors
    page.on('console', msg => {
      const text = msg.text();
      console.log(`Console [${msg.type()}]:`, text);
      
      if (msg.type() === 'error') {
        consoleErrors.push(text);
      }
    });
    
    // Capture page errors
    page.on('pageerror', error => {
      console.error('Page error:', error.message);
      pageErrors.push(error.message);
    });
    
    // Capture network requests and responses
    page.on('request', request => {
      console.log(`→ ${request.method()} ${request.url()}`);
    });
    
    page.on('response', response => {
      const status = response.status();
      const url = response.url();
      console.log(`← ${status} ${url}`);
      
      // Track error responses
      if (status >= 400) {
        networkErrors.push({
          status,
          url,
          statusText: response.statusText()
        });
      }
    });
    
    // Capture failed requests
    page.on('requestfailed', request => {
      console.error('Request failed:', request.url(), request.failure()?.errorText);
      networkErrors.push({
        status: 'FAILED',
        url: request.url(),
        error: request.failure()?.errorText
      });
    });
  });

  test('Homepage loads without errors', async () => {
    console.log('\n=== Testing Homepage ===');
    
    try {
      const response = await page.goto(SITE_URL, { 
        waitUntil: 'networkidle',
        timeout: 60000
      });
      
      const artifacts = await saveTestArtifacts(page, 'homepage');
      
      // Check response status
      expect(response?.status()).toBe(200);
      console.log('✅ Homepage response status: 200');
      
      // Check for error messages in content
      const content = artifacts?.content || '';
      const hasDbError = /database|connection.*failed|sqlstate|cms_db|cms_user/i.test(content);
      const hasPhpError = /fatal error|parse error|warning.*in.*line|notice.*in.*line/i.test(content);
      const has503Error = /503|service unavailable|maintenance/i.test(content);
      const has500Error = /500|internal server error/i.test(content);
      
      expect(hasDbError).toBe(false);
      expect(hasPhpError).toBe(false);
      expect(has503Error).toBe(false);
      expect(has500Error).toBe(false);
      
      // Check for essential page elements
      await expect(page.locator('title')).not.toBeEmpty();
      
      testResults.push({
        test: 'homepage',
        status: 'PASS',
        url: page.url(),
        httpStatus: response?.status(),
        consoleErrors: consoleErrors.length,
        pageErrors: pageErrors.length,
        networkErrors: networkErrors.length,
        artifacts
      });
      
      console.log('✅ Homepage test completed successfully');
      
    } catch (error) {
      console.error('❌ Homepage test failed:', error);
      await saveTestArtifacts(page, 'homepage', 'ERROR');
      
      testResults.push({
        test: 'homepage',
        status: 'FAIL',
        error: error.message,
        consoleErrors: consoleErrors.length,
        pageErrors: pageErrors.length,
        networkErrors: networkErrors.length
      });
      
      throw error;
    }
  });

  test('Admin panel shows login form not database error', async () => {
    console.log('\n=== Testing Admin Panel ===');
    
    try {
      const response = await page.goto(`${SITE_URL}/admin`, { 
        waitUntil: 'networkidle',
        timeout: 60000
      });
      
      const artifacts = await saveTestArtifacts(page, 'admin-panel');
      
      // Check response status (should be 200 or redirect to login)
      const status = response?.status();
      expect([200, 302, 301]).toContain(status);
      console.log(`✅ Admin panel response status: ${status}`);
      
      // Check content for errors
      const content = artifacts?.content || '';
      const textContent = artifacts?.textContent || '';
      
      // Should NOT contain database errors
      const hasDbError = /database.*connection|sqlstate|access denied.*database|unknown database|cms_db|cms_user/i.test(content);
      const hasPhpError = /fatal error|parse error|warning.*in.*line/i.test(content);
      const has503Error = /503|service unavailable|maintenance/i.test(content);
      
      expect(hasDbError).toBe(false);
      expect(hasPhpError).toBe(false);
      expect(has503Error).toBe(false);
      
      // Should contain login form elements or be redirected to login
      const hasLoginForm = await page.locator('input[type="password"]').count() > 0;
      const hasUsernameField = await page.locator('input[name="username"], input[name="email"], input[type="text"]').count() > 0;
      const isLoginPage = /login|sign.*in/i.test(textContent);
      
      // At least one of these should be true
      expect(hasLoginForm || hasUsernameField || isLoginPage).toBe(true);
      
      console.log('✅ Admin panel shows proper login interface');
      
      testResults.push({
        test: 'admin-panel',
        status: 'PASS',
        url: page.url(),
        httpStatus: status,
        hasLoginForm,
        hasUsernameField,
        isLoginPage,
        consoleErrors: consoleErrors.length,
        pageErrors: pageErrors.length,
        networkErrors: networkErrors.length,
        artifacts
      });
      
    } catch (error) {
      console.error('❌ Admin panel test failed:', error);
      await saveTestArtifacts(page, 'admin-panel', 'ERROR');
      
      testResults.push({
        test: 'admin-panel',
        status: 'FAIL',
        error: error.message,
        consoleErrors: consoleErrors.length,
        pageErrors: pageErrors.length,
        networkErrors: networkErrors.length
      });
      
      throw error;
    }
  });

  test('Admin login page loads correctly', async () => {
    console.log('\n=== Testing Admin Login Page ===');
    
    try {
      const response = await page.goto(`${SITE_URL}/admin/login`, { 
        waitUntil: 'networkidle',
        timeout: 60000
      });
      
      const artifacts = await saveTestArtifacts(page, 'admin-login');
      
      // Check response status
      expect(response?.status()).toBe(200);
      console.log('✅ Admin login page response status: 200');
      
      // Check for errors in content
      const content = artifacts?.content || '';
      const hasDbError = /database|connection.*failed|sqlstate|cms_db|cms_user/i.test(content);
      const hasPhpError = /fatal error|parse error|warning.*in.*line/i.test(content);
      
      expect(hasDbError).toBe(false);
      expect(hasPhpError).toBe(false);
      
      // Should have login form elements
      const hasPasswordField = await page.locator('input[type="password"]').count() > 0;
      const hasSubmitButton = await page.locator('input[type="submit"], button[type="submit"]').count() > 0;
      
      expect(hasPasswordField).toBe(true);
      expect(hasSubmitButton).toBe(true);
      
      console.log('✅ Admin login page has proper form elements');
      
      testResults.push({
        test: 'admin-login',
        status: 'PASS',
        url: page.url(),
        httpStatus: response?.status(),
        hasPasswordField,
        hasSubmitButton,
        consoleErrors: consoleErrors.length,
        pageErrors: pageErrors.length,
        networkErrors: networkErrors.length,
        artifacts
      });
      
    } catch (error) {
      console.error('❌ Admin login test failed:', error);
      await saveTestArtifacts(page, 'admin-login', 'ERROR');
      
      testResults.push({
        test: 'admin-login',
        status: 'FAIL',
        error: error.message,
        consoleErrors: consoleErrors.length,
        pageErrors: pageErrors.length,
        networkErrors: networkErrors.length
      });
      
      throw error;
    }
  });

  test('Diagnose page provides system information', async () => {
    console.log('\n=== Testing Diagnose Page ===');
    
    try {
      const response = await page.goto(`${SITE_URL}/diagnose.php`, { 
        waitUntil: 'networkidle',
        timeout: 60000
      });
      
      const artifacts = await saveTestArtifacts(page, 'diagnose-page');
      
      // Check response status
      expect(response?.status()).toBe(200);
      console.log('✅ Diagnose page response status: 200');
      
      // Check content for system information
      const content = artifacts?.content || '';
      const textContent = artifacts?.textContent || '';
      
      // Should contain diagnostic information
      const hasSystemInfo = /php version|database|server|config/i.test(textContent);
      const hasDbConfig = /database.*host|database.*name|database.*user/i.test(textContent);
      
      expect(hasSystemInfo).toBe(true);
      console.log('✅ Diagnose page contains system information');
      
      if (hasDbConfig) {
        console.log('✅ Diagnose page shows database configuration');
        
        // Check for correct database references
        const hasCorrectDb = /dalthaus_maincms/i.test(textContent);
        const hasOldDb = /cms_db|cms_user/i.test(textContent);
        
        if (hasOldDb && !hasCorrectDb) {
          console.warn('⚠️  Diagnose page still shows old database references');
        }
      }
      
      testResults.push({
        test: 'diagnose-page',
        status: 'PASS',
        url: page.url(),
        httpStatus: response?.status(),
        hasSystemInfo,
        hasDbConfig,
        consoleErrors: consoleErrors.length,
        pageErrors: pageErrors.length,
        networkErrors: networkErrors.length,
        artifacts
      });
      
    } catch (error) {
      console.error('❌ Diagnose page test failed:', error);
      await saveTestArtifacts(page, 'diagnose-page', 'ERROR');
      
      testResults.push({
        test: 'diagnose-page',
        status: 'FAIL',
        error: error.message,
        consoleErrors: consoleErrors.length,
        pageErrors: pageErrors.length,
        networkErrors: networkErrors.length
      });
      
      throw error;
    }
  });

  test('Agent endpoint responds correctly', async () => {
    console.log('\n=== Testing Agent Endpoint ===');
    
    try {
      const agentUrl = `${SITE_URL}/agent.php?action=test&key=${AGENT_KEY}`;
      const response = await page.goto(agentUrl, { 
        waitUntil: 'networkidle',
        timeout: 60000
      });
      
      const artifacts = await saveTestArtifacts(page, 'agent-endpoint');
      
      // Check response status
      expect(response?.status()).toBe(200);
      console.log('✅ Agent endpoint response status: 200');
      
      // Get response content
      const textContent = artifacts?.textContent || '';
      console.log('Agent response preview:', textContent.substring(0, 500));
      
      // Try to parse as JSON
      let agentData = null;
      try {
        agentData = JSON.parse(textContent);
        console.log('✅ Agent response is valid JSON');
        console.log('Agent data keys:', Object.keys(agentData));
      } catch {
        console.log('ℹ️  Agent response is not JSON format');
      }
      
      // Check for error indicators
      const hasError = /error|failed|exception/i.test(textContent);
      const hasDbError = /database|connection.*failed|sqlstate/i.test(textContent);
      
      expect(hasDbError).toBe(false);
      
      testResults.push({
        test: 'agent-endpoint',
        status: 'PASS',
        url: page.url(),
        httpStatus: response?.status(),
        isJsonResponse: agentData !== null,
        hasError,
        hasDbError,
        responsePreview: textContent.substring(0, 200),
        consoleErrors: consoleErrors.length,
        pageErrors: pageErrors.length,
        networkErrors: networkErrors.length,
        artifacts
      });
      
      console.log('✅ Agent endpoint test completed');
      
    } catch (error) {
      console.error('❌ Agent endpoint test failed:', error);
      await saveTestArtifacts(page, 'agent-endpoint', 'ERROR');
      
      testResults.push({
        test: 'agent-endpoint',
        status: 'FAIL',
        error: error.message,
        consoleErrors: consoleErrors.length,
        pageErrors: pageErrors.length,
        networkErrors: networkErrors.length
      });
      
      throw error;
    }
  });

  test('Critical pages check for errors', async () => {
    console.log('\n=== Testing Critical Pages for Errors ===');
    
    const criticalPages = [
      { path: '/articles', name: 'articles-page' },
      { path: '/photobooks', name: 'photobooks-page' },
      { path: '/admin/dashboard', name: 'admin-dashboard' }
    ];
    
    for (const pageInfo of criticalPages) {
      try {
        console.log(`\n--- Testing ${pageInfo.path} ---`);
        
        const response = await page.goto(`${SITE_URL}${pageInfo.path}`, { 
          waitUntil: 'networkidle',
          timeout: 30000
        });
        
        const artifacts = await saveTestArtifacts(page, pageInfo.name);
        
        const status = response?.status();
        console.log(`${pageInfo.path} response status:`, status);
        
        // Check for error status codes
        expect([200, 302, 401, 403]).toContain(status); // Allow redirects and auth-related codes
        
        if (status === 200) {
          // Check content for errors if page loads
          const content = artifacts?.content || '';
          const hasDbError = /database.*connection|sqlstate|cms_db|cms_user/i.test(content);
          const hasPhpError = /fatal error|parse error|warning.*in.*line/i.test(content);
          const has503Error = /503|service unavailable/i.test(content);
          const has500Error = /500|internal server error/i.test(content);
          
          expect(hasDbError).toBe(false);
          expect(hasPhpError).toBe(false);
          expect(has503Error).toBe(false);
          expect(has500Error).toBe(false);
          
          console.log(`✅ ${pageInfo.path} loads without errors`);
        } else {
          console.log(`ℹ️  ${pageInfo.path} returned status ${status} (may require authentication)`);
        }
        
        testResults.push({
          test: `critical-page-${pageInfo.name}`,
          status: 'PASS',
          path: pageInfo.path,
          url: page.url(),
          httpStatus: status,
          consoleErrors: consoleErrors.length,
          pageErrors: pageErrors.length,
          networkErrors: networkErrors.length,
          artifacts
        });
        
      } catch (error) {
        console.error(`❌ ${pageInfo.path} test failed:`, error);
        await saveTestArtifacts(page, pageInfo.name, 'ERROR');
        
        testResults.push({
          test: `critical-page-${pageInfo.name}`,
          status: 'FAIL',
          path: pageInfo.path,
          error: error.message,
          consoleErrors: consoleErrors.length,
          pageErrors: pageErrors.length,
          networkErrors: networkErrors.length
        });
        
        // Don't fail the entire test for individual page failures
        console.log(`⚠️  Continuing with next page after ${pageInfo.path} failure`);
      }
    }
  });

  test('Check for JavaScript console errors across site', async () => {
    console.log('\n=== Testing for JavaScript Console Errors ===');
    
    const pagesToCheck = [
      { path: '/', name: 'homepage-js' },
      { path: '/admin', name: 'admin-js' },
      { path: '/articles', name: 'articles-js' }
    ];
    
    let totalJsErrors = 0;
    const jsErrorDetails: any[] = [];
    
    for (const pageInfo of pagesToCheck) {
      try {
        console.log(`\n--- Checking JavaScript errors on ${pageInfo.path} ---`);
        
        // Reset console errors for this page
        const pageConsoleErrors: string[] = [];
        
        // Capture console errors for this specific page
        const consoleHandler = (msg: any) => {
          if (msg.type() === 'error') {
            pageConsoleErrors.push(msg.text());
          }
        };
        
        page.on('console', consoleHandler);
        
        const response = await page.goto(`${SITE_URL}${pageInfo.path}`, { 
          waitUntil: 'networkidle',
          timeout: 30000
        });
        
        // Wait a bit more for any lazy-loaded JS
        await page.waitForTimeout(2000);
        
        page.off('console', consoleHandler);
        
        console.log(`${pageInfo.path} JavaScript errors found: ${pageConsoleErrors.length}`);
        
        if (pageConsoleErrors.length > 0) {
          console.log('JavaScript errors:', pageConsoleErrors);
          jsErrorDetails.push({
            page: pageInfo.path,
            errors: pageConsoleErrors
          });
        }
        
        totalJsErrors += pageConsoleErrors.length;
        
        // Save artifacts if there are JS errors
        if (pageConsoleErrors.length > 0) {
          await saveTestArtifacts(page, pageInfo.name, 'JS-ERRORS');
        }
        
      } catch (error) {
        console.error(`❌ JavaScript error check failed for ${pageInfo.path}:`, error);
      }
    }
    
    console.log(`\n=== Total JavaScript errors found: ${totalJsErrors} ===`);
    
    testResults.push({
      test: 'javascript-errors-check',
      status: totalJsErrors === 0 ? 'PASS' : 'WARNING',
      totalJsErrors,
      jsErrorDetails,
      pagesTested: pagesToCheck.length
    });
    
    // Don't fail the test for JS errors, but report them
    if (totalJsErrors > 0) {
      console.warn('⚠️  JavaScript errors detected but not failing test');
    } else {
      console.log('✅ No JavaScript console errors detected');
    }
  });

  test.afterEach(async () => {
    // Clear arrays for next test
    consoleErrors = [];
    pageErrors = [];
    networkErrors = [];
  });

  test.afterAll(async () => {
    // Generate comprehensive test report
    console.log('\n=== Generating Comprehensive Test Report ===');
    
    const timestamp = new Date().toISOString();
    const summary = {
      timestamp,
      site_url: SITE_URL,
      total_tests: testResults.length,
      passed_tests: testResults.filter(r => r.status === 'PASS').length,
      failed_tests: testResults.filter(r => r.status === 'FAIL').length,
      warning_tests: testResults.filter(r => r.status === 'WARNING').length,
      test_details: testResults,
      overall_status: testResults.every(r => r.status === 'PASS' || r.status === 'WARNING') ? 'HEALTHY' : 'ISSUES_DETECTED'
    };
    
    const reportPath = join('test-results', 'comprehensive-test-report.json');
    ensureDir(reportPath);
    writeFileSync(reportPath, JSON.stringify(summary, null, 2));
    
    // Generate markdown report
    const mdReport = `# Comprehensive Production Site Test Report

## Test Summary
- **Site URL**: ${SITE_URL}
- **Test Date**: ${timestamp}
- **Total Tests**: ${summary.total_tests}
- **Passed**: ${summary.passed_tests}
- **Failed**: ${summary.failed_tests}
- **Warnings**: ${summary.warning_tests}
- **Overall Status**: ${summary.overall_status}

## Test Results

${testResults.map(result => `
### ${result.test}
- **Status**: ${result.status}
- **URL**: ${result.url || 'N/A'}
- **HTTP Status**: ${result.httpStatus || 'N/A'}
- **Console Errors**: ${result.consoleErrors || 0}
- **Page Errors**: ${result.pageErrors || 0}
- **Network Errors**: ${result.networkErrors || 0}
${result.error ? `- **Error**: ${result.error}` : ''}
${result.artifacts ? `- **Screenshots**: Available in test-results/` : ''}
`).join('\n')}

## Recommendations

${summary.failed_tests > 0 ? '🔴 **CRITICAL**: Some tests failed. Please review the failed tests and fix the underlying issues.' : '✅ All critical tests passed.'}

${testResults.some(r => r.hasDbError) ? '🔴 **DATABASE**: Database connection errors detected. Check database configuration.' : '✅ No database connection errors detected.'}

${testResults.some(r => (r.consoleErrors || 0) > 0) ? '⚠️ **JAVASCRIPT**: JavaScript errors detected. Consider fixing for better user experience.' : '✅ No JavaScript console errors detected.'}

${testResults.some(r => (r.networkErrors || 0) > 0) ? '⚠️ **NETWORK**: Some network requests failed. Check server configuration.' : '✅ All network requests successful.'}
`;
    
    const mdReportPath = join('test-results', 'comprehensive-test-report.md');
    ensureDir(mdReportPath);
    writeFileSync(mdReportPath, mdReport);
    
    console.log(`✅ Test report generated: ${reportPath}`);
    console.log(`✅ Markdown report generated: ${mdReportPath}`);
    
    // Print summary to console
    console.log('\n=== FINAL TEST SUMMARY ===');
    console.log(`Overall Status: ${summary.overall_status}`);
    console.log(`Tests Passed: ${summary.passed_tests}/${summary.total_tests}`);
    
    if (summary.failed_tests > 0) {
      console.log('❌ FAILED TESTS:');
      testResults.filter(r => r.status === 'FAIL').forEach(result => {
        console.log(`  - ${result.test}: ${result.error}`);
      });
    }
    
    if (testResults.some(r => r.status === 'WARNING')) {
      console.log('⚠️  WARNING TESTS:');
      testResults.filter(r => r.status === 'WARNING').forEach(result => {
        console.log(`  - ${result.test}`);
      });
    }
    
    console.log('\n=== Test artifacts saved in test-results/ directory ===');
  });
});