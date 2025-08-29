const https = require('https');
const http = require('http');
const fs = require('fs');
const path = require('path');

class ProductionTester {
    constructor() {
        this.baseUrl = 'https://dalthaus.net';
        this.results = [];
        this.startTime = Date.now();
    }

    async makeRequest(url, options = {}) {
        return new Promise((resolve, reject) => {
            const isHttps = url.startsWith('https://');
            const client = isHttps ? https : http;
            
            const requestOptions = {
                timeout: 10000,
                headers: {
                    'User-Agent': 'Dalthaus-CMS-E2E-Tester/1.0',
                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language': 'en-US,en;q=0.5',
                    'Accept-Encoding': 'gzip, deflate',
                    'Connection': 'keep-alive',
                    'Cache-Control': 'no-cache'
                },
                ...options
            };

            const req = client.request(url, requestOptions, (res) => {
                let data = '';
                
                res.on('data', (chunk) => {
                    data += chunk;
                });
                
                res.on('end', () => {
                    resolve({
                        statusCode: res.statusCode,
                        statusMessage: res.statusMessage,
                        headers: res.headers,
                        body: data,
                        url: url,
                        finalUrl: res.headers.location || url
                    });
                });
            });

            req.on('error', (err) => {
                reject({
                    error: err.message,
                    code: err.code,
                    url: url
                });
            });

            req.on('timeout', () => {
                req.destroy();
                reject({
                    error: 'Request timeout',
                    code: 'TIMEOUT',
                    url: url
                });
            });

            if (options.postData) {
                req.write(options.postData);
            }
            
            req.end();
        });
    }

    async testUrl(testName, url, expectedStatus = 200) {
        console.log(`\n🔍 Testing: ${testName}`);
        console.log(`   URL: ${url}`);
        
        try {
            const result = await this.makeRequest(url);
            const success = result.statusCode === expectedStatus;
            
            console.log(`   Status: ${result.statusCode} ${result.statusMessage}`);
            console.log(`   Content-Type: ${result.headers['content-type']}`);
            console.log(`   Content-Length: ${result.headers['content-length'] || 'unknown'}`);
            
            // Check for PHP execution
            const isPHPExecuted = !result.body.includes('<?php') && 
                                 (result.headers['content-type']?.includes('text/html') || 
                                  result.headers['content-type']?.includes('application/json'));
            
            // Check for CSS loading
            const hasCSSLinks = result.body.includes('<link') && result.body.includes('.css');
            const hasInlineStyles = result.body.includes('<style>');
            
            // Check for database connectivity hints
            const hasDBContent = result.body.includes('articles') || 
                                result.body.includes('content') || 
                                result.body.includes('menu');
            
            // Check for errors
            const hasErrors = result.body.includes('Fatal error') || 
                             result.body.includes('Parse error') || 
                             result.body.includes('Warning:') ||
                             result.body.includes('Error:');

            const testResult = {
                test: testName,
                url: url,
                status: success ? 'PASS' : 'FAIL',
                httpStatus: result.statusCode,
                httpMessage: result.statusMessage,
                contentType: result.headers['content-type'],
                contentLength: result.headers['content-length'],
                phpExecuted: isPHPExecuted,
                hasCSSLinks: hasCSSLinks,
                hasInlineStyles: hasInlineStyles,
                hasDBContent: hasDBContent,
                hasErrors: hasErrors,
                bodyPreview: result.body.substring(0, 500),
                timestamp: new Date().toISOString()
            };

            if (success) {
                console.log(`   ✅ ${testName} - PASS`);
            } else {
                console.log(`   ❌ ${testName} - FAIL (Expected ${expectedStatus}, got ${result.statusCode})`);
            }

            this.results.push(testResult);
            return testResult;

        } catch (error) {
            console.log(`   ❌ ${testName} - ERROR: ${error.error || error.message}`);
            
            const testResult = {
                test: testName,
                url: url,
                status: 'ERROR',
                error: error.error || error.message,
                errorCode: error.code,
                timestamp: new Date().toISOString()
            };
            
            this.results.push(testResult);
            return testResult;
        }
    }

    async testLoginAttempt() {
        console.log(`\n🔐 Testing Admin Login Flow`);
        
        try {
            // First get the login page
            const loginPage = await this.makeRequest(`${this.baseUrl}/admin/login.php`);
            
            if (loginPage.statusCode !== 200) {
                throw new Error(`Login page returned ${loginPage.statusCode}`);
            }

            // Extract CSRF token if present
            const csrfMatch = loginPage.body.match(/name="csrf_token"[^>]*value="([^"]+)"/);
            const csrfToken = csrfMatch ? csrfMatch[1] : '';

            console.log(`   Login page loaded, CSRF token: ${csrfToken ? 'Found' : 'Not found'}`);

            // Attempt login
            const loginData = new URLSearchParams({
                username: 'admin',
                password: '130Bpm',
                csrf_token: csrfToken
            }).toString();

            const loginResult = await this.makeRequest(`${this.baseUrl}/admin/login.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Content-Length': loginData.length,
                    'Cookie': this.extractCookies(loginPage.headers)
                },
                postData: loginData
            });

            const isRedirect = loginResult.statusCode >= 300 && loginResult.statusCode < 400;
            const redirectLocation = loginResult.headers.location;

            console.log(`   Login response: ${loginResult.statusCode}`);
            if (isRedirect) {
                console.log(`   Redirect to: ${redirectLocation}`);
            }

            this.results.push({
                test: 'Admin Login Flow',
                url: `${this.baseUrl}/admin/login.php`,
                status: isRedirect && redirectLocation?.includes('dashboard') ? 'PASS' : 'FAIL',
                httpStatus: loginResult.statusCode,
                redirectLocation: redirectLocation,
                csrfTokenPresent: !!csrfToken,
                bodyPreview: loginResult.body.substring(0, 500),
                timestamp: new Date().toISOString()
            });

        } catch (error) {
            console.log(`   ❌ Login test failed: ${error.message}`);
            this.results.push({
                test: 'Admin Login Flow',
                status: 'ERROR',
                error: error.message,
                timestamp: new Date().toISOString()
            });
        }
    }

    extractCookies(headers) {
        const cookies = headers['set-cookie'];
        if (!cookies) return '';
        return cookies.map(cookie => cookie.split(';')[0]).join('; ');
    }

    async runAllTests() {
        console.log('🚀 Starting Production HTTP E2E Tests for dalthaus.net');
        console.log('=' .repeat(60));
        console.log(`Started at: ${new Date().toISOString()}`);

        // Test core URLs from requirements
        const testCases = [
            ['Homepage', `${this.baseUrl}/`, 200],
            ['Admin Login Page', `${this.baseUrl}/admin/login.php`, 200],
            ['Admin Dashboard (Unauthenticated)', `${this.baseUrl}/admin/dashboard.php`, [200, 302, 403]],
            ['Articles Public Page', `${this.baseUrl}/public/articles.php`, 200],
            ['Setup Script', `${this.baseUrl}/setup.php`, [200, 403]],
            ['Auto-Deploy Endpoint', `${this.baseUrl}/auto-deploy.php?token=deploy-20250829`, [200, 403, 404]],
            
            // Additional critical endpoints
            ['CSS Stylesheet', `${this.baseUrl}/assets/css/style.css`, 200],
            ['JavaScript Assets', `${this.baseUrl}/assets/js/main.js`, [200, 404]],
            ['Admin Assets CSS', `${this.baseUrl}/assets/css/admin.css`, [200, 404]],
            ['API Endpoint Test', `${this.baseUrl}/admin/api/status.php`, [200, 404, 403]],
            ['Favicon', `${this.baseUrl}/favicon.ico`, [200, 404]],
            ['Robots.txt', `${this.baseUrl}/robots.txt`, [200, 404]]
        ];

        // Run all URL tests
        for (const [testName, url, expectedStatus] of testCases) {
            const expected = Array.isArray(expectedStatus) ? expectedStatus[0] : expectedStatus;
            await this.testUrl(testName, url, expected);
            
            // Small delay between requests
            await new Promise(resolve => setTimeout(resolve, 500));
        }

        // Test login flow
        await this.testLoginAttempt();

        // Generate comprehensive report
        this.generateReport();
    }

    generateReport() {
        const endTime = Date.now();
        const duration = (endTime - this.startTime) / 1000;

        console.log('\n' + '=' .repeat(60));
        console.log('📊 COMPREHENSIVE E2E TEST REPORT');
        console.log('=' .repeat(60));

        let passCount = 0;
        let failCount = 0;
        let errorCount = 0;

        // Executive Summary
        this.results.forEach(result => {
            if (result.status === 'PASS') passCount++;
            else if (result.status === 'FAIL') failCount++;
            else if (result.status === 'ERROR') errorCount++;
        });

        console.log('\n## EXECUTIVE SUMMARY');
        console.log(`Overall Status: ${failCount === 0 && errorCount === 0 ? 'PASS' : 'FAIL'}`);
        console.log(`Total Tests: ${this.results.length}`);
        console.log(`Passed: ${passCount}`);
        console.log(`Failed: ${failCount}`);
        console.log(`Errors: ${errorCount}`);
        console.log(`Duration: ${duration.toFixed(2)}s`);
        console.log(`Environment: Production (https://dalthaus.net)`);

        console.log('\n## DETAILED RESULTS');
        console.log('-' .repeat(60));

        this.results.forEach(result => {
            const icon = result.status === 'PASS' ? '✅' : result.status === 'FAIL' ? '❌' : '💥';
            console.log(`${icon} ${result.test}`);
            console.log(`   Status: ${result.status}`);
            
            if (result.httpStatus) {
                console.log(`   HTTP: ${result.httpStatus} ${result.httpMessage || ''}`);
            }
            
            if (result.error) {
                console.log(`   Error: ${result.error}`);
            }
            
            if (result.contentType) {
                console.log(`   Content-Type: ${result.contentType}`);
            }

            // Analysis flags
            if (result.phpExecuted !== undefined) {
                console.log(`   PHP Executed: ${result.phpExecuted ? 'Yes' : 'No'}`);
            }
            
            if (result.hasCSSLinks !== undefined) {
                console.log(`   CSS Links: ${result.hasCSSLinks ? 'Found' : 'Missing'}`);
            }
            
            if (result.hasErrors) {
                console.log(`   ⚠️  PHP Errors detected in content`);
            }

            console.log('');
        });

        // Critical Issues Summary
        console.log('\n## CRITICAL ISSUES IDENTIFIED');
        console.log('-' .repeat(60));

        const criticalIssues = this.results.filter(r => 
            r.status === 'FAIL' || 
            r.status === 'ERROR' || 
            r.hasErrors || 
            !r.phpExecuted
        );

        if (criticalIssues.length === 0) {
            console.log('✅ No critical issues detected');
        } else {
            criticalIssues.forEach(issue => {
                console.log(`❌ ${issue.test}:`);
                if (issue.error) console.log(`   - ${issue.error}`);
                if (issue.hasErrors) console.log(`   - PHP errors detected in output`);
                if (issue.phpExecuted === false) console.log(`   - PHP not executing properly`);
                if (issue.httpStatus && issue.httpStatus >= 400) console.log(`   - HTTP ${issue.httpStatus} error`);
            });
        }

        // Save detailed results
        const reportData = {
            summary: {
                overall: failCount === 0 && errorCount === 0 ? 'PASS' : 'FAIL',
                total: this.results.length,
                passed: passCount,
                failed: failCount,
                errors: errorCount,
                duration: duration,
                timestamp: new Date().toISOString(),
                environment: 'Production (https://dalthaus.net)'
            },
            results: this.results,
            criticalIssues: criticalIssues.length
        };

        // Create results directory if it doesn't exist
        const resultsDir = path.join(__dirname, 'test-results');
        if (!fs.existsSync(resultsDir)) {
            fs.mkdirSync(resultsDir, { recursive: true });
        }

        // Save JSON report
        fs.writeFileSync(
            path.join(resultsDir, `production-test-${Date.now()}.json`),
            JSON.stringify(reportData, null, 2)
        );

        console.log(`\n📁 Detailed results saved to: test-results/`);
        console.log('=' .repeat(60));

        return reportData;
    }
}

// Run the tests
const tester = new ProductionTester();
tester.runAllTests().catch(console.error);