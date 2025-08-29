const https = require('https');
const querystring = require('querystring');
const cheerio = require('cheerio');

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

async function extractErrorMessage() {
    console.log('🔍 EXTRACTING EXACT ERROR MESSAGE');
    console.log('=' .repeat(50));
    
    try {
        // Get login page first
        const loginPage = await makeRequest('https://dalthaus.net/admin/login.php');
        const csrfMatch = loginPage.body.match(/name="csrf_token" value="([^"]+)"/);
        const csrfToken = csrfMatch ? csrfMatch[1] : null;
        
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
        
        // Parse HTML to find error messages
        const $ = cheerio.load(response.body);
        
        console.log('🔍 SEARCHING FOR ERROR MESSAGES:');
        console.log('=' .repeat(30));
        
        // Look for common error message selectors
        const errorSelectors = [
            '.error-message',
            '.error',
            '.alert-danger',
            '.alert',
            '.message',
            '.notification',
            '[class*="error"]',
            '[class*="alert"]'
        ];
        
        let foundError = false;
        
        errorSelectors.forEach(selector => {
            const errorElement = $(selector);
            if (errorElement.length > 0) {
                console.log(`Found error in ${selector}: "${errorElement.text().trim()}"`);
                foundError = true;
            }
        });
        
        // Also search for text patterns
        const errorPatterns = [
            /Invalid\s+[^<]+/gi,
            /Error:\s+[^<]+/gi,
            /Login\s+failed[^<]*/gi,
            /Authentication\s+[^<]+/gi
        ];
        
        console.log('\n🔍 SEARCHING FOR ERROR PATTERNS:');
        console.log('=' .repeat(30));
        
        errorPatterns.forEach(pattern => {
            const matches = response.body.match(pattern);
            if (matches) {
                matches.forEach(match => {
                    console.log(`Pattern match: "${match.trim()}"`);
                    foundError = true;
                });
            }
        });
        
        if (!foundError) {
            console.log('No specific error messages found in selectors.');
            console.log('\nSearching raw HTML for "Invalid" context:');
            
            const lines = response.body.split('\n');
            lines.forEach((line, index) => {
                if (line.toLowerCase().includes('invalid')) {
                    console.log(`Line ${index + 1}: ${line.trim()}`);
                }
            });
        }
        
        // Check if this might be a database connectivity issue
        console.log('\n🔍 DATABASE CONNECTIVITY TEST:');
        console.log('=' .repeat(30));
        
        // Try to access the homepage which should show content count
        const homepageResponse = await makeRequest('https://dalthaus.net/');
        console.log(`Homepage response: ${homepageResponse.statusCode}`);
        
        if (homepageResponse.body.includes('Published content items:')) {
            const contentMatch = homepageResponse.body.match(/Published content items: (\d+)/);
            if (contentMatch) {
                console.log(`✅ Database appears to be working (${contentMatch[1]} content items found)`);
            }
        } else {
            console.log('⚠️ Cannot determine database status from homepage');
        }
        
    } catch (error) {
        console.error('❌ Error:', error.message);
    }
}

extractErrorMessage().catch(console.error);