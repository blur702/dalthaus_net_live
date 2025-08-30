const https = require('https');

async function checkErrors() {
  console.log('Checking detailed error information...');
  
  try {
    // Get homepage with full error details
    console.log('\n=== Homepage Error Details ===');
    const homepageResponse = await new Promise((resolve, reject) => {
      const req = https.get('https://dalthaus.net/', { timeout: 30000 }, (res) => {
        let data = '';
        res.on('data', (chunk) => data += chunk);
        res.on('end', () => resolve({ statusCode: res.statusCode, body: data }));
      });
      req.on('error', reject);
    });
    
    console.log('Status:', homepageResponse.statusCode);
    console.log('Response body:');
    console.log(homepageResponse.body);
    
    // Try admin page errors
    console.log('\n=== Admin Login Error Details ===');
    const adminResponse = await new Promise((resolve, reject) => {
      const req = https.get('https://dalthaus.net/admin/login.php', { timeout: 30000 }, (res) => {
        let data = '';
        res.on('data', (chunk) => data += chunk);
        res.on('end', () => resolve({ statusCode: res.statusCode, body: data }));
      });
      req.on('error', reject);
    });
    
    console.log('Admin Status:', adminResponse.statusCode);
    if (adminResponse.body.includes('Fatal error') || adminResponse.body.includes('Warning')) {
      console.log('Error content:');
      console.log(adminResponse.body.substring(0, 1000));
    }
    
  } catch (error) {
    console.error('Error:', error.message);
  }
}

checkErrors();