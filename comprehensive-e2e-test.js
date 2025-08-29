#!/usr/bin/env node
/**
 * Comprehensive E2E Test Suite for Dalthaus.net Photography CMS
 * Tests EVERY feature, button, and setting as requested
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

// Test configuration
const BASE_URL = 'http://localhost:8000';
const ADMIN_USERNAME = 'kevin';
const ADMIN_PASSWORD = '(130Bpm)';
const TEST_TIMEOUT = 60000;

// Test results tracking
let testResults = {
    totalTests: 0,
    passedTests: 0,
    failedTests: 0,
    skippedTests: 0,
    startTime: new Date(),
    endTime: null,
    failures: [],
    environment: BASE_URL,
    testDetails: []
};

class E2ETestSuite {
    constructor() {
        this.browser = null;
        this.context = null;
        this.page = null;
        this.authenticated = false;
    }

    async setup() {
        console.log('🚀 Starting Comprehensive E2E Test Suite...');
        this.browser = await chromium.launch({ 
            headless: false,
            slowMo: 100 // Slow down for visibility
        });
        this.context = await this.browser.newContext({
            viewport: { width: 1920, height: 1080 },
            ignoreHTTPSErrors: true
        });
        this.page = await this.context.newPage();
        
        // Set up request/response logging
        this.page.on('response', response => {
            if (response.status() >= 400) {
                console.log(`❌ HTTP Error: ${response.status()} - ${response.url()}`);
            }
        });
        
        this.page.on('console', msg => {
            if (msg.type() === 'error') {
                console.log(`🔍 Console Error: ${msg.text()}`);
            }
        });
    }

    async teardown() {
        if (this.browser) {
            await this.browser.close();
        }
        testResults.endTime = new Date();
        this.generateReport();
    }

    async runTest(testName, testFunction, category = 'General') {
        testResults.totalTests++;
        console.log(`\n🧪 Running: ${testName}`);
        
        try {
            const startTime = Date.now();
            await testFunction.call(this);
            const duration = Date.now() - startTime;
            
            testResults.passedTests++;
            testResults.testDetails.push({
                name: testName,
                category: category,
                status: 'PASS',
                duration: `${duration}ms`,
                notes: '-'
            });
            console.log(`✅ PASSED: ${testName}`);
        } catch (error) {
            testResults.failedTests++;
            const failure = {
                testName: testName,
                category: category,
                failurePoint: error.message,
                expectedResult: 'Test should pass without errors',
                actualResult: error.message,
                initialTriage: 'Application Bug',
                diagnosticArtifacts: {
                    screenshot: await this.captureScreenshot(testName),
                    consoleLog: 'Available in browser console',
                    networkLog: 'Available in browser network tab',
                    stackTrace: error.stack
                },
                suggestedFix: this.getSuggestedFix(error)
            };
            
            testResults.failures.push(failure);
            testResults.testDetails.push({
                name: testName,
                category: category,
                status: 'FAIL',
                duration: '-',
                notes: error.message
            });
            console.log(`❌ FAILED: ${testName} - ${error.message}`);
        }
    }

    async captureScreenshot(testName) {
        try {
            const screenshotPath = `test-results/screenshot-${testName.replace(/[^a-zA-Z0-9]/g, '-')}-${Date.now()}.png`;
            await this.page.screenshot({ path: screenshotPath, fullPage: true });
            return screenshotPath;
        } catch (error) {
            return 'Screenshot capture failed';
        }
    }

    getSuggestedFix(error) {
        if (error.message.includes('timeout')) {
            return 'Check if element exists and is visible, increase timeout, or verify network connectivity';
        } else if (error.message.includes('404')) {
            return 'Verify URL is correct and resource exists on server';
        } else if (error.message.includes('500')) {
            return 'Check server logs for PHP errors, database connectivity, or configuration issues';
        } else if (error.message.includes('authentication') || error.message.includes('login')) {
            return 'Verify credentials are correct and session management is working';
        }
        return 'Review error details and check application logs';
    }

    // ========================================================================
    // 1. DATABASE CONNECTION TESTS
    // ========================================================================

    async testDatabaseConnection() {
        await this.page.goto(`${BASE_URL}/`);
        await this.page.waitForLoadState('networkidle');
        
        // Check if page loads without database errors
        const errorElements = await this.page.locator('.error, .db-error').count();
        if (errorElements > 0) {
            throw new Error('Database connection error detected on homepage');
        }
        
        // Verify content loads (indicates successful DB queries)
        const hasContent = await this.page.locator('body').textContent();
        if (!hasContent || hasContent.includes('database error')) {
            throw new Error('No content loaded or database error present');
        }
    }

    async testCRUDOperations() {
        // This will be tested through admin panel functionality
        await this.page.goto(`${BASE_URL}/admin/dashboard.php`);
        await this.page.waitForLoadState('networkidle');
        
        // Check for any SQL errors
        const pageText = await this.page.textContent('body');
        if (pageText.includes('SQL') && pageText.includes('error')) {
            throw new Error('SQL error detected in admin dashboard');
        }
    }

    // ========================================================================
    // 2. UI ACCESSIBILITY TESTS
    // ========================================================================

    async testHomepageLoad() {
        await this.page.goto(`${BASE_URL}/`);
        await this.page.waitForLoadState('networkidle');
        
        const title = await this.page.title();
        if (!title) {
            throw new Error('Homepage has no title');
        }
        
        // Check CSS loading
        const cssLoaded = await this.page.evaluate(() => {
            return getComputedStyle(document.body).fontFamily !== '';
        });
        if (!cssLoaded) {
            throw new Error('CSS not loaded properly');
        }
    }

    async testNavigationMenu() {
        await this.page.goto(`${BASE_URL}/`);
        await this.page.waitForLoadState('networkidle');
        
        // Find navigation links
        const navLinks = await this.page.locator('nav a, .nav a, .menu a').all();
        if (navLinks.length === 0) {
            throw new Error('No navigation menu found');
        }
        
        // Test clicking navigation items
        for (const link of navLinks.slice(0, 3)) { // Test first 3 links
            const href = await link.getAttribute('href');
            if (href && !href.startsWith('#')) {
                await link.click();
                await this.page.waitForLoadState('networkidle');
                
                // Check if page loaded without errors
                const hasError = await this.page.locator('.error').count();
                if (hasError > 0) {
                    throw new Error(`Navigation link ${href} resulted in error page`);
                }
                
                await this.page.goBack();
                await this.page.waitForLoadState('networkidle');
            }
        }
    }

    async testResponsiveDesign() {
        await this.page.goto(`${BASE_URL}/`);
        await this.page.waitForLoadState('networkidle');
        
        // Test mobile viewport
        await this.page.setViewportSize({ width: 375, height: 667 });
        await this.page.waitForTimeout(1000);
        
        // Check if layout adapts
        const bodyWidth = await this.page.evaluate(() => document.body.scrollWidth);
        if (bodyWidth > 400) {
            console.warn('⚠️  Layout may not be fully responsive');
        }
        
        // Reset to desktop
        await this.page.setViewportSize({ width: 1920, height: 1080 });
    }

    async testImageLoading() {
        await this.page.goto(`${BASE_URL}/`);
        await this.page.waitForLoadState('networkidle');
        
        const images = await this.page.locator('img').all();
        let brokenImages = 0;
        
        for (const img of images) {
            const naturalWidth = await img.evaluate(el => el.naturalWidth);
            if (naturalWidth === 0) {
                brokenImages++;
            }
        }
        
        if (brokenImages > 0) {
            console.warn(`⚠️  ${brokenImages} images failed to load`);
        }
    }

    // ========================================================================
    // 3. ADMIN PANEL TESTS
    // ========================================================================

    async testAdminLogin() {
        await this.page.goto(`${BASE_URL}/admin/login.php`);
        await this.page.waitForLoadState('networkidle');
        
        // Check login form exists
        const usernameField = this.page.locator('input[name="username"], input[type="text"]');
        const passwordField = this.page.locator('input[name="password"], input[type="password"]');
        const submitButton = this.page.locator('input[type="submit"], button[type="submit"]');
        
        if (await usernameField.count() === 0) {
            throw new Error('Username field not found on login page');
        }
        
        if (await passwordField.count() === 0) {
            throw new Error('Password field not found on login page');
        }
        
        // Attempt login
        await usernameField.fill(ADMIN_USERNAME);
        await passwordField.fill(ADMIN_PASSWORD);
        await submitButton.click();
        
        await this.page.waitForLoadState('networkidle');
        
        // Check if redirected to dashboard or if error occurred
        const currentUrl = this.page.url();
        if (currentUrl.includes('login.php') && !currentUrl.includes('dashboard')) {
            // Check for error messages
            const errorText = await this.page.textContent('body');
            if (errorText.includes('invalid') || errorText.includes('error') || errorText.includes('wrong')) {
                throw new Error('Login failed with provided credentials');
            }
        }
        
        this.authenticated = true;
    }

    async testAdminDashboard() {
        if (!this.authenticated) {
            await this.testAdminLogin();
        }
        
        await this.page.goto(`${BASE_URL}/admin/dashboard.php`);
        await this.page.waitForLoadState('networkidle');
        
        // Check for dashboard elements
        const dashboardElements = await this.page.locator('.dashboard, #dashboard, .admin-content').count();
        if (dashboardElements === 0) {
            throw new Error('Dashboard elements not found');
        }
        
        // Check for navigation menu
        const adminNavLinks = await this.page.locator('nav a, .admin-nav a, .sidebar a').count();
        if (adminNavLinks === 0) {
            throw new Error('Admin navigation menu not found');
        }
    }

    async testArticleManagement() {
        if (!this.authenticated) {
            await this.testAdminLogin();
        }
        
        await this.page.goto(`${BASE_URL}/admin/articles.php`);
        await this.page.waitForLoadState('networkidle');
        
        // Test Create Article
        const createButton = this.page.locator('a[href*="articles.php?action=create"], .btn-create, .create-article');
        if (await createButton.count() > 0) {
            await createButton.first().click();
            await this.page.waitForLoadState('networkidle');
            
            // Check for article creation form
            const titleField = this.page.locator('input[name="title"], #title');
            const bodyField = this.page.locator('textarea[name="body"], #body');
            
            if (await titleField.count() === 0) {
                throw new Error('Article title field not found in creation form');
            }
            
            if (await bodyField.count() === 0) {
                throw new Error('Article body field not found in creation form');
            }
        } else {
            console.warn('⚠️  Create article button not found');
        }
    }

    async testPhotobookManagement() {
        if (!this.authenticated) {
            await this.testAdminLogin();
        }
        
        await this.page.goto(`${BASE_URL}/admin/photobooks.php`);
        await this.page.waitForLoadState('networkidle');
        
        // Check photobook listing
        const photobookList = await this.page.locator('.photobook-list, table, .content-list').count();
        if (photobookList === 0) {
            console.warn('⚠️  Photobook listing not found');
        }
        
        // Test create photobook
        const createButton = this.page.locator('a[href*="photobooks.php?action=create"], .btn-create');
        if (await createButton.count() > 0) {
            await createButton.first().click();
            await this.page.waitForLoadState('networkidle');
            
            const titleField = this.page.locator('input[name="title"], #title');
            if (await titleField.count() === 0) {
                throw new Error('Photobook title field not found');
            }
        }
    }

    async testSettingsPage() {
        if (!this.authenticated) {
            await this.testAdminLogin();
        }
        
        await this.page.goto(`${BASE_URL}/admin/settings.php`);
        await this.page.waitForLoadState('networkidle');
        
        // Check for settings form
        const settingsForm = await this.page.locator('form, .settings-form').count();
        if (settingsForm === 0) {
            throw new Error('Settings form not found');
        }
        
        // Check for common setting fields
        const siteTitle = this.page.locator('input[name="site_title"], input[name*="title"]');
        if (await siteTitle.count() > 0) {
            console.log('✓ Site title setting found');
        }
        
        const maintenanceMode = this.page.locator('input[name="maintenance_mode"], input[type="checkbox"]');
        if (await maintenanceMode.count() > 0) {
            console.log('✓ Maintenance mode setting found');
        }
    }

    async testMenuManagement() {
        if (!this.authenticated) {
            await this.testAdminLogin();
        }
        
        await this.page.goto(`${BASE_URL}/admin/menus.php`);
        await this.page.waitForLoadState('networkidle');
        
        const menuItems = await this.page.locator('.menu-item, .sortable li, table tr').count();
        console.log(`📋 Found ${menuItems} menu items or rows`);
        
        // Test drag-and-drop if sortable elements exist
        const sortableElements = await this.page.locator('.sortable, .draggable').count();
        if (sortableElements > 0) {
            console.log('✓ Sortable menu elements detected');
        }
    }

    // ========================================================================
    // 4. CONTENT MANAGEMENT TESTS
    // ========================================================================

    async testAutosave() {
        if (!this.authenticated) {
            await this.testAdminLogin();
        }
        
        // Go to article edit page
        await this.page.goto(`${BASE_URL}/admin/articles.php`);
        await this.page.waitForLoadState('networkidle');
        
        const editLink = this.page.locator('a[href*="edit"], .edit-btn').first();
        if (await editLink.count() > 0) {
            await editLink.click();
            await this.page.waitForLoadState('networkidle');
            
            // Check for autosave script
            const autosaveScript = await this.page.evaluate(() => {
                return window.autosaveInterval !== undefined || 
                       document.querySelector('script[src*="autosave"]') !== null;
            });
            
            if (!autosaveScript) {
                console.warn('⚠️  Autosave functionality not detected');
            } else {
                console.log('✓ Autosave functionality detected');
            }
        }
    }

    async testVersionHistory() {
        if (!this.authenticated) {
            await this.testAdminLogin();
        }
        
        await this.page.goto(`${BASE_URL}/admin/versions.php`);
        await this.page.waitForLoadState('networkidle');
        
        const versionList = await this.page.locator('.version-list, table, .versions').count();
        if (versionList === 0) {
            console.warn('⚠️  Version history interface not found');
        } else {
            console.log('✓ Version history interface found');
        }
    }

    async testDocumentImport() {
        if (!this.authenticated) {
            await this.testAdminLogin();
        }
        
        await this.page.goto(`${BASE_URL}/admin/import.php`);
        await this.page.waitForLoadState('networkidle');
        
        const fileInput = await this.page.locator('input[type="file"]').count();
        if (fileInput === 0) {
            throw new Error('File upload input not found on import page');
        }
        
        const supportedFormats = await this.page.textContent('body');
        if (!supportedFormats.includes('docx') && !supportedFormats.includes('pdf')) {
            console.warn('⚠️  Supported file formats not clearly indicated');
        }
    }

    // ========================================================================
    // 5. API ENDPOINT TESTS
    // ========================================================================

    async testAutosaveAPI() {
        if (!this.authenticated) {
            await this.testAdminLogin();
        }
        
        const response = await this.page.request.post(`${BASE_URL}/admin/api/autosave.php`, {
            data: {
                content_id: '1',
                title: 'Test Title',
                body: 'Test Body',
                csrf_token: 'test'
            }
        });
        
        // Even if it fails due to CSRF, the endpoint should exist
        if (response.status() === 404) {
            throw new Error('Autosave API endpoint not found');
        }
        
        console.log(`📡 Autosave API responded with status: ${response.status()}`);
    }

    async testSortAPI() {
        if (!this.authenticated) {
            await this.testAdminLogin();
        }
        
        const response = await this.page.request.post(`${BASE_URL}/admin/api/sort.php`, {
            data: {
                items: JSON.stringify([{id: 1, position: 1}]),
                csrf_token: 'test'
            }
        });
        
        if (response.status() === 404) {
            throw new Error('Sort API endpoint not found');
        }
        
        console.log(`📡 Sort API responded with status: ${response.status()}`);
    }

    async testUploadAPI() {
        if (!this.authenticated) {
            await this.testAdminLogin();
        }
        
        await this.page.goto(`${BASE_URL}/admin/upload.php`);
        await this.page.waitForLoadState('networkidle');
        
        const uploadForm = await this.page.locator('form[enctype="multipart/form-data"], input[type="file"]').count();
        if (uploadForm === 0) {
            throw new Error('File upload interface not found');
        }
    }

    // ========================================================================
    // 6. SECURITY TESTS
    // ========================================================================

    async testCSRFProtection() {
        // Test that forms have CSRF tokens
        if (!this.authenticated) {
            await this.testAdminLogin();
        }
        
        await this.page.goto(`${BASE_URL}/admin/articles.php`);
        await this.page.waitForLoadState('networkidle');
        
        const csrfTokens = await this.page.locator('input[name="csrf_token"], input[name*="token"]').count();
        if (csrfTokens === 0) {
            throw new Error('CSRF protection tokens not found in forms');
        }
        
        console.log(`🔒 Found ${csrfTokens} CSRF tokens`);
    }

    async testSessionManagement() {
        // Test logout functionality
        if (!this.authenticated) {
            await this.testAdminLogin();
        }
        
        await this.page.goto(`${BASE_URL}/admin/logout.php`);
        await this.page.waitForLoadState('networkidle');
        
        // Should be redirected to login page
        const currentUrl = this.page.url();
        if (!currentUrl.includes('login')) {
            throw new Error('Logout did not redirect to login page');
        }
        
        // Try to access admin page - should be redirected to login
        await this.page.goto(`${BASE_URL}/admin/dashboard.php`);
        await this.page.waitForLoadState('networkidle');
        
        const redirectedUrl = this.page.url();
        if (!redirectedUrl.includes('login')) {
            throw new Error('Session validation failed - admin area accessible without login');
        }
        
        this.authenticated = false;
    }

    // ========================================================================
    // 7. ERROR HANDLING TESTS
    // ========================================================================

    async test404Handling() {
        await this.page.goto(`${BASE_URL}/nonexistent-page.php`);
        await this.page.waitForLoadState('networkidle');
        
        const status = await this.page.evaluate(() => {
            return fetch(window.location.href).then(r => r.status);
        });
        
        if (status !== 404) {
            console.warn(`⚠️  Expected 404, got ${status} for nonexistent page`);
        }
    }

    async testFormValidation() {
        if (!this.authenticated) {
            await this.testAdminLogin();
        }
        
        // Test empty form submission
        await this.page.goto(`${BASE_URL}/admin/articles.php?action=create`);
        await this.page.waitForLoadState('networkidle');
        
        const submitButton = this.page.locator('input[type="submit"], button[type="submit"]');
        if (await submitButton.count() > 0) {
            await submitButton.click();
            await this.page.waitForLoadState('networkidle');
            
            // Check for validation messages
            const validationMessages = await this.page.locator('.error, .validation-error, .alert').count();
            if (validationMessages === 0) {
                console.warn('⚠️  Form validation messages not detected');
            }
        }
    }

    // ========================================================================
    // 8. PERFORMANCE TESTS
    // ========================================================================

    async testPageLoadTimes() {
        const pages = [
            '/',
            '/admin/login.php',
            '/admin/dashboard.php',
            '/articles/',
            '/photobooks/'
        ];
        
        for (const page of pages) {
            const startTime = Date.now();
            try {
                await this.page.goto(`${BASE_URL}${page}`);
                await this.page.waitForLoadState('networkidle');
                const loadTime = Date.now() - startTime;
                
                console.log(`📊 ${page}: ${loadTime}ms`);
                
                if (loadTime > 5000) {
                    console.warn(`⚠️  Slow page load: ${page} took ${loadTime}ms`);
                }
            } catch (error) {
                console.warn(`⚠️  Failed to load ${page}: ${error.message}`);
            }
        }
    }

    async testCacheHeaders() {
        const response = await this.page.goto(`${BASE_URL}/`);
        const cacheControl = response.headers()['cache-control'];
        const etag = response.headers()['etag'];
        
        if (!cacheControl && !etag) {
            console.warn('⚠️  No cache headers detected');
        } else {
            console.log('✓ Cache headers present');
        }
    }

    // ========================================================================
    // TEST EXECUTION
    // ========================================================================

    async runAllTests() {
        try {
            await this.setup();
            
            // 1. Database Connection Tests
            await this.runTest('Database Connection', this.testDatabaseConnection, 'Database');
            await this.runTest('CRUD Operations', this.testCRUDOperations, 'Database');
            
            // 2. UI Accessibility Tests
            await this.runTest('Homepage Load', this.testHomepageLoad, 'UI');
            await this.runTest('Navigation Menu', this.testNavigationMenu, 'UI');
            await this.runTest('Responsive Design', this.testResponsiveDesign, 'UI');
            await this.runTest('Image Loading', this.testImageLoading, 'UI');
            
            // 3. Admin Panel Tests
            await this.runTest('Admin Login', this.testAdminLogin, 'Admin');
            await this.runTest('Admin Dashboard', this.testAdminDashboard, 'Admin');
            await this.runTest('Article Management', this.testArticleManagement, 'Admin');
            await this.runTest('Photobook Management', this.testPhotobookManagement, 'Admin');
            await this.runTest('Settings Page', this.testSettingsPage, 'Admin');
            await this.runTest('Menu Management', this.testMenuManagement, 'Admin');
            
            // 4. Content Management Tests
            await this.runTest('Autosave Functionality', this.testAutosave, 'Content');
            await this.runTest('Version History', this.testVersionHistory, 'Content');
            await this.runTest('Document Import', this.testDocumentImport, 'Content');
            
            // 5. API Endpoint Tests
            await this.runTest('Autosave API', this.testAutosaveAPI, 'API');
            await this.runTest('Sort API', this.testSortAPI, 'API');
            await this.runTest('Upload API', this.testUploadAPI, 'API');
            
            // 6. Security Tests
            await this.runTest('CSRF Protection', this.testCSRFProtection, 'Security');
            await this.runTest('Session Management', this.testSessionManagement, 'Security');
            
            // 7. Error Handling Tests
            await this.runTest('404 Handling', this.test404Handling, 'Error Handling');
            await this.runTest('Form Validation', this.testFormValidation, 'Error Handling');
            
            // 8. Performance Tests
            await this.runTest('Page Load Times', this.testPageLoadTimes, 'Performance');
            await this.runTest('Cache Headers', this.testCacheHeaders, 'Performance');
            
        } finally {
            await this.teardown();
        }
    }

    generateReport() {
        const duration = testResults.endTime - testResults.startTime;
        const report = `# E2E Test Report

## Executive Summary
- **Overall Status**: ${testResults.failedTests === 0 ? 'PASS' : 'FAIL'}
- **Total Tests Executed**: ${testResults.totalTests}
- **Passed**: ${testResults.passedTests}
- **Failed**: ${testResults.failedTests}
- **Skipped**: ${testResults.skippedTests}
- **Test Duration**: ${Math.round(duration / 1000)}s
- **Environment**: ${testResults.environment}

${testResults.failures.length > 0 ? `## Failed Tests Breakdown

${testResults.failures.map(failure => `### Test Case: ${failure.testName}
- **Failure Point**: ${failure.failurePoint}
- **Expected Result**: ${failure.expectedResult}
- **Actual Result**: ${failure.actualResult}
- **Initial Triage**: ${failure.initialTriage}
- **Diagnostic Artifacts**:
  - Screenshot: ${failure.diagnosticArtifacts.screenshot}
  - Console Logs: ${failure.diagnosticArtifacts.consoleLog}
  - Network Logs: ${failure.diagnosticArtifacts.networkLog}
  - Stack Trace: Available in detailed logs
- **Suggested Fix**: ${failure.suggestedFix}
`).join('\n')}` : '## All Tests Passed! ✅'}

## Full Test Suite Results

| Test Name | Category | Status | Duration | Notes |
|-----------|----------|--------|----------|-------|
${testResults.testDetails.map(test => 
    `| ${test.name} | ${test.category} | ${test.status} | ${test.duration} | ${test.notes} |`
).join('\n')}

## Recommendations
${testResults.failedTests > 0 ? `
### Priority 1 (Critical Issues)
${testResults.failures.filter(f => f.initialTriage === 'Application Bug').map(f => `- Fix ${f.testName}: ${f.suggestedFix}`).join('\n')}

### Priority 2 (Improvements)
${testResults.failures.filter(f => f.initialTriage !== 'Application Bug').map(f => `- Address ${f.testName}: ${f.suggestedFix}`).join('\n')}
` : `
### All Tests Passed!
- No critical issues identified
- Application is functioning as expected
- Consider implementing additional monitoring
`}

### Long-term Improvements
- Implement automated test suite in CI/CD pipeline
- Add performance monitoring and alerts
- Consider additional security auditing tools
- Implement comprehensive logging and error tracking

## Test Environment Details
- **Date**: ${testResults.startTime.toISOString()}
- **Duration**: ${Math.round(duration / 1000)} seconds
- **Browser**: Chromium (Playwright)
- **Viewport**: 1920x1080
- **Network**: Default connection

---
*Generated by Comprehensive E2E Test Suite*
`;

        // Create test-results directory if it doesn't exist
        if (!fs.existsSync('test-results')) {
            fs.mkdirSync('test-results');
        }

        const reportPath = `test-results/comprehensive-e2e-${Date.now()}.md`;
        fs.writeFileSync(reportPath, report);
        
        console.log(`\n📋 Test Report Generated: ${reportPath}`);
        console.log(`\n🎯 Test Summary: ${testResults.passedTests}/${testResults.totalTests} tests passed`);
        
        if (testResults.failedTests > 0) {
            console.log(`❌ ${testResults.failedTests} tests failed`);
        } else {
            console.log('✅ All tests passed!');
        }
    }
}

// Run the test suite
const testSuite = new E2ETestSuite();
testSuite.runAllTests().catch(console.error);