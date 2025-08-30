const https = require('https');

async function testCurrentConfiguration() {
  console.log('Testing current configuration...');
  
  try {
    // Test homepage
    console.log('\n=== Testing Homepage ===');
    const homepageResponse = await new Promise((resolve, reject) => {
      const req = https.get('https://dalthaus.net/', { timeout: 30000 }, (res) => {
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
    
    console.log('Homepage Status Code:', homepageResponse.statusCode);
    
    if (homepageResponse.body.includes('500') || homepageResponse.body.includes('Internal Server Error')) {
      console.log('❌ Homepage still has 500 error');
      console.log('First 500 chars of response:', homepageResponse.body.substring(0, 500));
    } else if (homepageResponse.statusCode === 200 && homepageResponse.body.includes('<title>')) {
      console.log('✅ Homepage appears to be working');
      // Extract title if possible
      const titleMatch = homepageResponse.body.match(/<title>(.*?)<\/title>/i);
      if (titleMatch) {
        console.log('Page title:', titleMatch[1]);
      }
    } else {
      console.log('⚠️  Unclear homepage status');
    }

    // Test admin login page
    console.log('\n=== Testing Admin Login ===');
    const adminResponse = await new Promise((resolve, reject) => {
      const req = https.get('https://dalthaus.net/admin/login.php', { timeout: 30000 }, (res) => {
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
    
    console.log('Admin Login Status Code:', adminResponse.statusCode);
    
    if (adminResponse.body.includes('500') || adminResponse.body.includes('Internal Server Error')) {
      console.log('❌ Admin login has 500 error');
    } else if (adminResponse.statusCode === 200 && adminResponse.body.includes('<form')) {
      console.log('✅ Admin login page is accessible');
    } else {
      console.log('⚠️  Admin login status unclear');
    }
    
    // Test a few content pages
    console.log('\n=== Testing Content Pages ===');
    const testUrls = [
      'https://dalthaus.net/articles',
      'https://dalthaus.net/photobooks'
    ];
    
    for (const url of testUrls) {
      try {
        const response = await new Promise((resolve, reject) => {
          const req = https.get(url, { timeout: 15000 }, (res) => {
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
        
        console.log(`${url}: ${response.statusCode}`, response.statusCode === 200 ? '✅' : '❌');
        
        if (response.body.includes('500') || response.body.includes('Fatal error')) {
          console.log(`   ❌ Has errors`);
        } else if (response.statusCode === 200) {
          console.log(`   ✅ Working`);
        }
      } catch (error) {
        console.log(`${url}: Error - ${error.message}`);
      }
    }
    
  } catch (error) {
    console.error('Test failed:', error.message);
  }
}

testCurrentConfiguration();