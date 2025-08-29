const https = require('https');
const querystring = require('querystring');

function makeRequest(url, options = {}) {
    return new Promise((resolve, reject) => {
        const urlObj = new URL(url);
        
        const requestOptions = {
            hostname: urlObj.hostname,
            port: urlObj.port || 443,
            path: urlObj.pathname + urlObj.search,
            method: options.method || 'GET',
            headers: {
                'User-Agent': 'Dalthaus-E2E-Test/1.0',
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Content-Type': 'application/x-www-form-urlencoded',
                ...options.headers
            }
        };

        if (options.cookies) {
            requestOptions.headers['Cookie'] = options.cookies;
        }

        if (options.data) {
            const postData = querystring.stringify(options.data);
            requestOptions.headers['Content-Length'] = Buffer.byteLength(postData);
        }

        const req = https.request(requestOptions, (res) => {
            let data = '';
            
            res.on('data', (chunk) => {
                data += chunk;
            });

            res.on('end', () => {
                resolve({
                    statusCode: res.statusCode,
                    headers: res.headers,
                    body: data,
                    cookies: res.headers['set-cookie']
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

async function extractCSRFToken(html) {
    const csrfMatch = html.match(/name="csrf_token" value="([^"]+)"/);
    return csrfMatch ? csrfMatch[1] : null;
}

async function testAuthentication(username, password) {
    console.log(`\n🔐 Testing Authentication: ${username}/${password}`);
    console.log('=' .repeat(50));
    
    try {
        // Step 1: Get login page and CSRF token
        console.log('Step 1: Getting login page...');
        const loginPageResponse = await makeRequest('https://dalthaus.net/admin/login.php');
        
        if (loginPageResponse.statusCode !== 200) {
            console.log(`❌ Login page error: HTTP ${loginPageResponse.statusCode}`);
            return { success: false, error: `Login page returned ${loginPageResponse.statusCode}` };
        }
        
        const csrfToken = await extractCSRFToken(loginPageResponse.body);
        if (!csrfToken) {
            console.log(`❌ No CSRF token found`);
            return { success: false, error: 'No CSRF token found' };
        }
        
        console.log(`✅ CSRF token extracted: ${csrfToken.substring(0, 10)}...`);
        
        // Step 2: Submit login credentials
        console.log('Step 2: Submitting login credentials...');
        const loginResponse = await makeRequest('https://dalthaus.net/admin/login.php', {
            method: 'POST',
            data: {
                username: username,
                password: password,
                csrf_token: csrfToken
            },
            cookies: loginPageResponse.cookies ? loginPageResponse.cookies.join('; ') : ''
        });
        
        console.log(`Login response status: ${loginResponse.statusCode}`);
        console.log(`Redirect location: ${loginResponse.headers.location || 'None'}`);
        
        // Step 3: Analyze response
        if (loginResponse.statusCode === 302) {
            if (loginResponse.headers.location && loginResponse.headers.location.includes('dashboard')) {
                console.log(`✅ LOGIN SUCCESS: Redirected to dashboard`);
                
                // Step 4: Test accessing dashboard with session
                const sessionCookies = loginResponse.cookies ? loginResponse.cookies.join('; ') : '';
                const dashboardResponse = await makeRequest('https://dalthaus.net/admin/dashboard.php', {
                    cookies: sessionCookies
                });
                
                if (dashboardResponse.statusCode === 200) {
                    console.log(`✅ Dashboard accessible with session`);
                    return { 
                        success: true, 
                        sessionCookies: sessionCookies,
                        dashboardContent: dashboardResponse.body.substring(0, 200) + '...'
                    };
                } else {
                    console.log(`⚠️ Dashboard returned ${dashboardResponse.statusCode}`);
                    return { success: true, sessionCookies: sessionCookies, dashboardIssue: true };
                }
                
            } else {
                console.log(`❌ LOGIN FAILED: Redirected to ${loginResponse.headers.location}`);
                return { success: false, error: `Redirected to ${loginResponse.headers.location}` };
            }
        } else if (loginResponse.statusCode === 200) {
            if (loginResponse.body.includes('Invalid') || loginResponse.body.includes('error')) {
                console.log(`❌ LOGIN FAILED: Invalid credentials`);
                return { success: false, error: 'Invalid credentials' };
            } else {
                console.log(`⚠️ Unexpected 200 response (should be redirect)`);
                return { success: false, error: 'Unexpected response format' };
            }
        } else {
            console.log(`❌ LOGIN FAILED: HTTP ${loginResponse.statusCode}`);
            return { success: false, error: `HTTP ${loginResponse.statusCode}` };
        }
        
    } catch (error) {
        console.log(`❌ Authentication error: ${error.message}`);
        return { success: false, error: error.message };
    }
}

async function runAuthenticationTests() {
    console.log('🚀 AUTHENTICATION TEST SUITE');
    console.log('Site: https://dalthaus.net');
    console.log('=' .repeat(60));
    
    // Test 1: kevin/130Bpm (as requested)
    const kevinResult = await testAuthentication('kevin', '130Bpm');
    
    // Test 2: admin/130Bpm (default from documentation)
    const adminResult = await testAuthentication('admin', '130Bpm');
    
    // Test 3: Invalid credentials
    const invalidResult = await testAuthentication('wronguser', 'wrongpass');
    
    console.log('\n' + '=' .repeat(60));
    console.log('🏆 AUTHENTICATION RESULTS SUMMARY');
    console.log('=' .repeat(60));
    console.log(`kevin/130Bpm: ${kevinResult.success ? '✅ PASS' : '❌ FAIL'} ${kevinResult.error || ''}`);
    console.log(`admin/130Bpm: ${adminResult.success ? '✅ PASS' : '❌ FAIL'} ${adminResult.error || ''}`);
    console.log(`Invalid creds: ${!invalidResult.success ? '✅ PASS (correctly rejected)' : '❌ FAIL (should reject)'}`);
    
    if (kevinResult.success) {
        console.log(`\n🎯 Using kevin credentials for further testing...`);
        return kevinResult.sessionCookies;
    } else if (adminResult.success) {
        console.log(`\n🎯 Using admin credentials for further testing...`);
        return adminResult.sessionCookies;
    } else {
        console.log(`\n❌ No valid credentials found!`);
        return null;
    }
}

runAuthenticationTests().catch(console.error);