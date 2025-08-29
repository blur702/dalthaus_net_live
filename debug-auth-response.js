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

async function debugAuthResponse() {
    console.log('🔍 DEBUGGING AUTHENTICATION RESPONSE');
    console.log('=' .repeat(50));
    
    try {
        // Get login page first
        const loginPage = await makeRequest('https://dalthaus.net/admin/login.php');
        const csrfMatch = loginPage.body.match(/name="csrf_token" value="([^"]+)"/);
        const csrfToken = csrfMatch ? csrfMatch[1] : null;
        
        console.log(`CSRF Token: ${csrfToken ? '✅ Found' : '❌ Not found'}`);
        
        // Test with admin credentials
        const response = await makeRequest('https://dalthaus.net/admin/login.php', {
            method: 'POST',
            data: {
                username: 'admin',
                password: '130Bpm',
                csrf_token: csrfToken
            },
            cookies: loginPage.cookies ? loginPage.cookies.join('; ') : ''
        });
        
        console.log('\n📋 RESPONSE DETAILS:');
        console.log(`Status: ${response.statusCode}`);
        console.log(`Content-Type: ${response.headers['content-type']}`);
        console.log(`Location: ${response.headers.location || 'None'}`);
        console.log(`Set-Cookie: ${response.headers['set-cookie'] || 'None'}`);
        console.log(`Body Length: ${response.body.length}`);
        
        console.log('\n📄 RESPONSE BODY (First 2000 chars):');
        console.log(response.body.substring(0, 2000));
        
        // Check for specific error messages
        console.log('\n🔍 ERROR ANALYSIS:');
        console.log(`Contains "Invalid": ${response.body.includes('Invalid') ? '✅' : '❌'}`);
        console.log(`Contains "error": ${response.body.includes('error') ? '✅' : '❌'}`);
        console.log(`Contains "login": ${response.body.includes('login') ? '✅' : '❌'}`);
        console.log(`Contains "dashboard": ${response.body.includes('dashboard') ? '✅' : '❌'}`);
        console.log(`Contains "username": ${response.body.includes('username') ? '✅' : '❌'}`);
        
        // Check for database errors
        console.log(`Contains "database": ${response.body.includes('database') ? '✅' : '❌'}`);
        console.log(`Contains "connection": ${response.body.includes('connection') ? '✅' : '❌'}`);
        console.log(`Contains "mysql": ${response.body.toLowerCase().includes('mysql') ? '✅' : '❌'}`);
        
    } catch (error) {
        console.error('❌ Debug error:', error.message);
    }
}

debugAuthResponse().catch(console.error);