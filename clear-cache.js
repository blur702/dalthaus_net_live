const https = require('https');

async function clearCacheAndTest() {
  console.log('Attempting to clear cache and test...');
  
  try {
    // Try to access a cache clearing endpoint if it exists
    const clearResponse = await new Promise((resolve, reject) => {
      const req = https.get('https://dalthaus.net/cache', { timeout: 15000 }, (res) => {
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
    
    console.log('Cache clear attempt status:', clearResponse.statusCode);
    
    // Wait a moment then test again
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    // Test the comprehensive test page again
    const testResponse = await new Promise((resolve, reject) => {
      const req = https.get('https://dalthaus.net/comprehensive-test.php', { timeout: 30000 }, (res) => {
        let data = '';
        res.on('data', (chunk) => data += chunk);
        res.on('end', () => resolve({ statusCode: res.statusCode, body: data }));
      });
      req.on('error', reject);
    });
    
    console.log('Test page status:', testResponse.statusCode);
    console.log('Response (first 1500 chars):');
    console.log(testResponse.body.substring(0, 1500));
    
    // Count warnings and errors
    const warnings = (testResponse.body.match(/Warning:/g) || []).length;
    const errors = (testResponse.body.match(/Fatal error:/g) || []).length;
    const deprecated = (testResponse.body.match(/Deprecated:/g) || []).length;
    
    console.log(`\nSummary: ${errors} errors, ${warnings} warnings, ${deprecated} deprecated notices`);
    
  } catch (error) {
    console.error('Error:', error.message);
  }
}

clearCacheAndTest();