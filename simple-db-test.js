const https = require('https');

async function testDatabaseConnection() {
  console.log('Testing direct database connection via web...');
  
  try {
    // Try to access the RUN_FIX_NOW.php which should be simpler
    const response = await new Promise((resolve, reject) => {
      const req = https.get('https://dalthaus.net/RUN_FIX_NOW.php', { timeout: 30000 }, (res) => {
        let data = '';
        res.on('data', (chunk) => data += chunk);
        res.on('end', () => resolve({ statusCode: res.statusCode, body: data }));
      });
      req.on('error', reject);
      req.on('timeout', () => {
        req.destroy();
        reject(new Error('Timeout'));
      });
    });
    
    console.log('RUN_FIX_NOW.php Status:', response.statusCode);
    console.log('Response:', response.body);
    
    // Try SIMPLE_FIX.php
    const simpleResponse = await new Promise((resolve, reject) => {
      const req = https.get('https://dalthaus.net/SIMPLE_FIX.php', { timeout: 30000 }, (res) => {
        let data = '';
        res.on('data', (chunk) => data += chunk);
        res.on('end', () => resolve({ statusCode: res.statusCode, body: data }));
      });
      req.on('error', reject);
      req.on('timeout', () => {
        req.destroy();
        reject(new Error('Timeout'));
      });
    });
    
    console.log('\nSIMPLE_FIX.php Status:', simpleResponse.statusCode);
    console.log('Response:', simpleResponse.body);
    
  } catch (error) {
    console.error('Error:', error.message);
  }
}

testDatabaseConnection();