const https = require('https');

async function testPhpInfo() {
  console.log('Testing basic PHP functionality...');
  
  // Try to access the simple test PHP file that should show database connection status
  try {
    const response = await new Promise((resolve, reject) => {
      const req = https.get('https://dalthaus.net/test-db-connection.php', { timeout: 30000 }, (res) => {
        let data = '';
        res.on('data', (chunk) => data += chunk);
        res.on('end', () => resolve({ statusCode: res.statusCode, body: data }));
      });
      req.on('error', reject);
    });
    
    console.log('test-db-connection.php Status:', response.statusCode);
    console.log('Response:', response.body);
    
    // Also try the comprehensive test
    const compResponse = await new Promise((resolve, reject) => {
      const req = https.get('https://dalthaus.net/comprehensive-test.php', { timeout: 30000 }, (res) => {
        let data = '';
        res.on('data', (chunk) => data += chunk);
        res.on('end', () => resolve({ statusCode: res.statusCode, body: data }));
      });
      req.on('error', reject);
    });
    
    console.log('\ncomprehensive-test.php Status:', compResponse.statusCode);
    if (compResponse.body.length > 0) {
      console.log('Response (first 1000 chars):', compResponse.body.substring(0, 1000));
    }
    
  } catch (error) {
    console.error('Error:', error.message);
  }
}

testPhpInfo();