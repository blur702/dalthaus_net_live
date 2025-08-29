const https = require('https');

function makeRequest(url) {
    return new Promise((resolve, reject) => {
        https.get(url, (res) => {
            let data = '';
            res.on('data', (chunk) => {
                data += chunk;
            });
            res.on('end', () => {
                resolve({
                    statusCode: res.statusCode,
                    headers: res.headers,
                    body: data
                });
            });
        }).on('error', (err) => {
            reject(err);
        });
    });
}

async function investigateHomepage() {
    console.log('🔍 Investigating Homepage Content...\n');
    
    try {
        const response = await makeRequest('https://dalthaus.net/');
        console.log(`Status Code: ${response.statusCode}`);
        console.log(`Content-Type: ${response.headers['content-type']}`);
        console.log(`Content-Length: ${response.headers['content-length']}`);
        console.log('\n--- First 1000 characters ---');
        console.log(response.body.substring(0, 1000));
        console.log('\n--- Contains Analysis ---');
        console.log(`Has <html>: ${response.body.includes('<html>') ? '✅' : '❌'}`);
        console.log(`Has <title>: ${response.body.includes('<title>') ? '✅' : '❌'}`);
        console.log(`Has public.css: ${response.body.includes('public.css') ? '✅' : '❌'}`);
        console.log(`Has DOCTYPE: ${response.body.includes('<!DOCTYPE') ? '✅' : '❌'}`);
        console.log(`Body length: ${response.body.length} characters`);
        
        // Check if it's an error page or redirect
        if (response.body.includes('error') || response.body.includes('404') || response.body.includes('500')) {
            console.log('\n⚠️ Homepage appears to be showing an error page!');
        }
        
        if (response.body.length < 500) {
            console.log('\n⚠️ Homepage content is suspiciously short!');
            console.log('Full content:');
            console.log(response.body);
        }
        
    } catch (error) {
        console.error('❌ Error investigating homepage:', error.message);
    }
}

async function testAdminLogin() {
    console.log('\n🔍 Investigating Admin Login Page...\n');
    
    try {
        const response = await makeRequest('https://dalthaus.net/admin/login.php');
        console.log(`Status Code: ${response.statusCode}`);
        console.log(`Content-Type: ${response.headers['content-type']}`);
        console.log('\n--- First 1000 characters ---');
        console.log(response.body.substring(0, 1000));
        console.log('\n--- Form Analysis ---');
        console.log(`Has username field: ${response.body.includes('name="username"') ? '✅' : '❌'}`);
        console.log(`Has password field: ${response.body.includes('name="password"') ? '✅' : '❌'}`);
        console.log(`Has CSRF token: ${response.body.includes('csrf_token') ? '✅' : '❌'}`);
        console.log(`Has form tag: ${response.body.includes('<form') ? '✅' : '❌'}`);
        
    } catch (error) {
        console.error('❌ Error investigating admin login:', error.message);
    }
}

investigateHomepage().then(() => testAdminLogin()).catch(console.error);