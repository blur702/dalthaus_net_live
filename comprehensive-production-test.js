const https = require('https');
const http = require('http');
const fs = require('fs');
const querystring = require('querystring');

class ProductionTester {
    constructor() {
        this.baseUrl = 'https://dalthaus.net';
        this.results = [];
        this.sessionCookies = '';
    }

    async makeRequest(path, options = {}) {
        return new Promise((resolve, reject) => {
            const url = this.baseUrl + path;
            const urlObj = new URL(url);
            
            const requestOptions = {
                hostname: urlObj.hostname,
                port: urlObj.port || 443,
                path: urlObj.pathname + urlObj.search,
                method: options.method || 'GET',
                headers: {
                    'User-Agent': 'Dalthaus-E2E-Test/1.0',
                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language': 'en-US,en;q=0.5',
                    'Accept-Encoding': 'gzip, deflate',
                    'Connection': 'keep-alive',
                    ...options.headers
                }
            };

            if (this.sessionCookies) {
                requestOptions.headers['Cookie'] = this.sessionCookies;
            }

            if (options.data) {
                const postData = querystring.stringify(options.data);
                requestOptions.headers['Content-Type'] = 'application/x-www-form-urlencoded';
                requestOptions.headers['Content-Length'] = Buffer.byteLength(postData);
            }

            const req = https.request(requestOptions, (res) => {
                let data = '';
                
                // Handle cookies
                if (res.headers['set-cookie']) {
                    this.sessionCookies = res.headers['set-cookie'].join('; ');
                }

                res.on('data', (chunk) => {
                    data += chunk;
                });

                res.on('end', () => {
                    resolve({
                        statusCode: res.statusCode,
                        headers: res.headers,
                        body: data,
                        cookies: this.sessionCookies
                    });
                });
            });

            req.on('error', (error) => {
                reject(error);
            });

            if (options.data) {
                const postData = querystring.stringify(options.data);
                req.write(postData);
            }

            req.end();
        });
    }

    async testEndpoint(name, path, expectedStatus = 200, options = {}) {
        console.log(`\n🔍 Testing ${name}...`);
        try {
            const response = await this.makeRequest(path, options);
            
            if (response.statusCode === expectedStatus) {
                console.log(`  ✅ ${name}: HTTP ${response.statusCode} - PASS`);
                this.results.push({
                    test: name,
                    status: 'PASS',
                    statusCode: response.statusCode,
                    path: path,
                    responseSize: response.body.length
                });
                return response;
            } else {
                console.log(`  ❌ ${name}: Expected ${expectedStatus}, got ${response.statusCode} - FAIL`);
                this.results.push({
                    test: name,
                    status: 'FAIL',
                    statusCode: response.statusCode,
                    expectedStatusCode: expectedStatus,
                    path: path,
                    error: `Unexpected status code: ${response.statusCode}`
                });
                return response;
            }
        } catch (error) {
            console.log(`  ❌ ${name}: ${error.message} - FAIL`);
            this.results.push({
                test: name,
                status: 'FAIL',
                path: path,
                error: error.message
            });
            return null;
        }
    }

    async testCSSAsset(path) {
        console.log(`\n🎨 Testing CSS Asset: ${path}...`);
        try {
            const response = await this.makeRequest(path);
            
            if (response.statusCode === 200) {
                const contentType = response.headers['content-type'] || '';
                const isCSS = contentType.includes('text/css') || path.endsWith('.css');
                const containsCSS = response.body.includes('{') && response.body.includes('}');
                const notHTML = !response.body.includes('<html>') && !response.body.includes('<!DOCTYPE');
                
                if (isCSS && containsCSS && notHTML) {
                    console.log(`  ✅ CSS Asset ${path}: Valid CSS content - PASS`);
                    this.results.push({
                        test: `CSS Asset: ${path}`,
                        status: 'PASS',
                        statusCode: response.statusCode,
                        contentType: contentType,
                        size: response.body.length
                    });
                } else {
                    console.log(`  ❌ CSS Asset ${path}: Invalid content (might be HTML) - FAIL`);
                    this.results.push({
                        test: `CSS Asset: ${path}`,
                        status: 'FAIL',
                        statusCode: response.statusCode,
                        contentType: contentType,
                        error: 'CSS file returning HTML content'
                    });
                }
            } else {
                console.log(`  ❌ CSS Asset ${path}: HTTP ${response.statusCode} - FAIL`);
                this.results.push({
                    test: `CSS Asset: ${path}`,
                    status: 'FAIL',
                    statusCode: response.statusCode,
                    error: `HTTP ${response.statusCode}`
                });
            }
        } catch (error) {
            console.log(`  ❌ CSS Asset ${path}: ${error.message} - FAIL`);
            this.results.push({
                test: `CSS Asset: ${path}`,
                status: 'FAIL',
                error: error.message
            });
        }
    }

    async runAllTests() {
        console.log('🚀 COMPREHENSIVE E2E PRODUCTION TEST SUITE');
        console.log('Site: https://dalthaus.net');
        console.log('=' .repeat(60));

        // Test 1: Homepage
        const homepage = await this.testEndpoint('Homepage', '/');
        if (homepage && homepage.body) {
            const hasTitle = homepage.body.includes('<title>');
            const hasCSS = homepage.body.includes('public.css');
            console.log(`    Title found: ${hasTitle ? '✅' : '❌'}`);
            console.log(`    CSS linked: ${hasCSS ? '✅' : '❌'}`);
        }

        // Test 2: Admin Login Page
        const adminLogin = await this.testEndpoint('Admin Login Page', '/admin/login.php');
        if (adminLogin && adminLogin.body) {
            const hasLoginForm = adminLogin.body.includes('name="username"') && adminLogin.body.includes('name="password"');
            const hasCSRF = adminLogin.body.includes('csrf_token');
            console.log(`    Login form: ${hasLoginForm ? '✅' : '❌'}`);
            console.log(`    CSRF token: ${hasCSRF ? '✅' : '❌'}`);
        }

        // Test 3: Static Assets
        await this.testCSSAsset('/assets/css/public.css');
        await this.testCSSAsset('/assets/css/admin.css');

        // Test 4: JavaScript Assets
        await this.testEndpoint('Autosave JS', '/assets/js/autosave.js');
        await this.testEndpoint('Sorting JS', '/assets/js/sorting.js');

        // Test 5: Admin Authentication
        console.log(`\n🔐 Testing Admin Authentication...`);
        
        // First get the login page to extract CSRF token
        const loginPage = await this.makeRequest('/admin/login.php');
        let csrfToken = '';
        if (loginPage && loginPage.body) {
            const csrfMatch = loginPage.body.match(/name="csrf_token" value="([^"]+)"/);
            if (csrfMatch) {
                csrfToken = csrfMatch[1];
                console.log(`    CSRF token extracted: ✅`);
            } else {
                console.log(`    CSRF token extraction: ❌`);
            }
        }

        // Try authentication with kevin/130Bpm as requested
        const authResponse = await this.makeRequest('/admin/login.php', {
            method: 'POST',
            data: {
                username: 'kevin',
                password: '130Bpm',
                csrf_token: csrfToken
            }
        });

        if (authResponse) {
            if (authResponse.statusCode === 302 && authResponse.headers.location && authResponse.headers.location.includes('dashboard')) {
                console.log(`  ✅ Admin Authentication: Login successful, redirected to dashboard - PASS`);
                this.results.push({
                    test: 'Admin Authentication',
                    status: 'PASS',
                    statusCode: authResponse.statusCode,
                    redirect: authResponse.headers.location
                });
            } else if (authResponse.statusCode === 200 && authResponse.body.includes('Invalid')) {
                console.log(`  ❌ Admin Authentication: Invalid credentials - FAIL`);
                this.results.push({
                    test: 'Admin Authentication',
                    status: 'FAIL',
                    statusCode: authResponse.statusCode,
                    error: 'Invalid credentials for kevin/130Bpm'
                });
            } else {
                console.log(`  ⚠️  Admin Authentication: Unexpected response - WARN`);
                this.results.push({
                    test: 'Admin Authentication',
                    status: 'WARN',
                    statusCode: authResponse.statusCode,
                    note: 'Unexpected authentication response'
                });
            }
        }

        // Test 6: Protected Admin Pages (without authentication)
        await this.testEndpoint('Admin Dashboard (Unauthenticated)', '/admin/dashboard.php', 302);
        await this.testEndpoint('Admin Articles (Unauthenticated)', '/admin/articles.php', 302);
        await this.testEndpoint('Admin Settings (Unauthenticated)', '/admin/settings.php', 302);

        // Test 7: Public Content Pages
        await this.testEndpoint('Articles List', '/articles');
        await this.testEndpoint('Photobooks List', '/photobooks');

        // Test 8: API Endpoints (should be protected)
        await this.testEndpoint('Autosave API (Protected)', '/admin/api/autosave.php', 302);
        await this.testEndpoint('Sort API (Protected)', '/admin/api/sort.php', 302);

        // Test 9: Error Handling
        await this.testEndpoint('404 Page', '/nonexistent-page', 404);

        // Test 10: Security Headers Check
        console.log(`\n🔒 Testing Security Headers...`);
        const securityTest = await this.makeRequest('/');
        if (securityTest) {
            const headers = securityTest.headers;
            const hasXFrameOptions = headers['x-frame-options'];
            const hasXContentType = headers['x-content-type-options'];
            const hasXXSSProtection = headers['x-xss-protection'];
            
            console.log(`    X-Frame-Options: ${hasXFrameOptions ? '✅ ' + hasXFrameOptions : '❌'}`);
            console.log(`    X-Content-Type-Options: ${hasXContentType ? '✅ ' + hasXContentType : '❌'}`);
            console.log(`    X-XSS-Protection: ${hasXXSSProtection ? '✅ ' + hasXXSSProtection : '❌'}`);
            
            this.results.push({
                test: 'Security Headers',
                status: (hasXFrameOptions && hasXContentType) ? 'PASS' : 'WARN',
                headers: {
                    'x-frame-options': hasXFrameOptions,
                    'x-content-type-options': hasXContentType,
                    'x-xss-protection': hasXXSSProtection
                }
            });
        }

        this.generateReport();
    }

    generateReport() {
        console.log('\n' + '=' .repeat(60));
        console.log('📊 COMPREHENSIVE E2E TEST RESULTS');
        console.log('=' .repeat(60));

        let passCount = 0;
        let failCount = 0;
        let warnCount = 0;

        this.results.forEach(result => {
            const icon = result.status === 'PASS' ? '✅' : result.status === 'FAIL' ? '❌' : '⚠️';
            console.log(`${icon} ${result.test}: ${result.status}`);
            if (result.error) console.log(`    Error: ${result.error}`);
            if (result.statusCode) console.log(`    HTTP: ${result.statusCode}`);
            
            if (result.status === 'PASS') passCount++;
            else if (result.status === 'FAIL') failCount++;
            else warnCount++;
        });

        console.log('\n' + '=' .repeat(60));
        console.log(`SUMMARY: ${passCount} passed, ${failCount} failed, ${warnCount} warnings`);
        console.log(`OVERALL STATUS: ${failCount === 0 ? '✅ PASS' : '❌ FAIL'}`);
        console.log('=' .repeat(60));

        // Save detailed report
        const reportPath = `/var/public_html/www/test-results/comprehensive-e2e-${Date.now()}.json`;
        const reportData = {
            timestamp: new Date().toISOString(),
            site: this.baseUrl,
            summary: {
                total: this.results.length,
                passed: passCount,
                failed: failCount,
                warnings: warnCount,
                overallStatus: failCount === 0 ? 'PASS' : 'FAIL'
            },
            results: this.results
        };

        try {
            if (!fs.existsSync('/var/public_html/www/test-results')) {
                fs.mkdirSync('/var/public_html/www/test-results', { recursive: true });
            }
            fs.writeFileSync(reportPath, JSON.stringify(reportData, null, 2));
            console.log(`\nDetailed report saved: ${reportPath}`);
        } catch (err) {
            console.log(`Report save error: ${err.message}`);
        }

        return reportData;
    }
}

// Run the comprehensive test suite
const tester = new ProductionTester();
tester.runAllTests().catch(console.error);