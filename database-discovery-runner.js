const https = require('https');
const fs = require('fs');

// Results object to store all test outcomes
const results = {
  gitPull: { success: false, output: '' },
  dbDiscovery: { success: false, output: '', workingCredentials: null, databases: [] },
  homepageTest: { success: false, error: '' },
  adminTest: { success: false, error: '' }
};

// Helper function to make HTTPS requests
function makeRequest(url, timeout = 30000) {
  return new Promise((resolve, reject) => {
    const req = https.get(url, { timeout }, (res) => {
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
    });
    
    req.on('error', (error) => {
      reject(error);
    });
    
    req.on('timeout', () => {
      req.destroy();
      reject(new Error('Request timeout'));
    });
  });
}

async function runDatabaseDiscovery() {
  console.log('\n=== STEP 1: Pulling Latest Code ===');
  
  try {
    const response = await makeRequest('https://dalthaus.net/git-pull.php?action=pull&token=agent-20250830', 60000);
    results.gitPull.output = response.body;
    results.gitPull.success = !response.body.includes('error') && !response.body.includes('Error') && response.statusCode === 200;
    
    console.log('Git Pull Status Code:', response.statusCode);
    console.log('Git Pull Output:', response.body);
    
    // Wait a moment for pull to complete
    await new Promise(resolve => setTimeout(resolve, 3000));
    
  } catch (error) {
    console.error('Git pull failed:', error.message);
    results.gitPull.output = `Error: ${error.message}`;
  }

  console.log('\n=== STEP 2: Running Database Discovery ===');

  try {
    const response = await makeRequest('https://dalthaus.net/DB_DISCOVER.php', 90000);
    results.dbDiscovery.output = response.body;
    
    console.log('DB Discovery Status Code:', response.statusCode);
    console.log('DB Discovery Output:', response.body);
    
    // Parse the output to determine success and extract information
    if (response.body.includes('SUCCESS') || 
        response.body.includes('Working connection found') || 
        response.body.includes('Configuration written successfully')) {
      results.dbDiscovery.success = true;
      
      // Extract working credentials if available
      const credentialsMatch = response.body.match(/Working connection.*?User:\s*(\S+).*?Password:\s*(\S+)/s);
      if (credentialsMatch) {
        results.dbDiscovery.workingCredentials = {
          user: credentialsMatch[1],
          password: credentialsMatch[2]
        };
      }
      
      // Extract database list
      const dbMatches = response.body.match(/Database:\s*(\S+)/g);
      if (dbMatches) {
        results.dbDiscovery.databases = dbMatches.map(match => match.replace('Database: ', ''));
      }
      
      // Look for content table confirmation
      if (response.body.includes("'content' table")) {
        console.log('✓ Content table found in database');
      }
    }
    
  } catch (error) {
    console.error('Database discovery failed:', error.message);
    results.dbDiscovery.output = `Error: ${error.message}`;
  }

  console.log('\n=== STEP 3: Testing Homepage After Fix ===');

  try {
    if (results.dbDiscovery.success) {
      // Wait a moment for configuration to be applied
      await new Promise(resolve => setTimeout(resolve, 2000));
      
      const response = await makeRequest('https://dalthaus.net/', 30000);
      
      console.log('Homepage Status Code:', response.statusCode);
      console.log('Homepage Response Length:', response.body.length);
      
      // Check for 500 errors or other issues
      if (response.body.includes('500') || response.body.includes('Internal Server Error')) {
        results.homepageTest.error = '500 Internal Server Error still present';
      } else if (response.statusCode === 200 && response.body.length > 1000) {
        results.homepageTest.success = true;
        console.log('✓ Homepage appears to be loading successfully');
        
        // Check if it looks like a proper HTML page
        if (response.body.includes('<title>') && response.body.includes('</html>')) {
          console.log('✓ Homepage has proper HTML structure');
        }
      } else {
        results.homepageTest.error = `Unexpected response: ${response.statusCode}`;
      }
    } else {
      results.homepageTest.error = 'Skipped - DB discovery failed';
    }
    
  } catch (error) {
    console.error('Homepage test failed:', error.message);
    results.homepageTest.error = error.message;
  }

  console.log('\n=== STEP 4: Testing Admin Login Page ===');

  try {
    const response = await makeRequest('https://dalthaus.net/admin/login.php', 30000);
    
    console.log('Admin Login Status Code:', response.statusCode);
    console.log('Admin Login Response Length:', response.body.length);
    
    if (response.statusCode === 200 && response.body.includes('<form')) {
      results.adminTest.success = true;
      console.log('✓ Admin login page is accessible and contains a form');
      
      // Check for specific login form elements
      if (response.body.includes('name="username"') && response.body.includes('name="password"')) {
        console.log('✓ Login form has username and password fields');
      }
    } else if (response.body.includes('500') || response.body.includes('Internal Server Error')) {
      results.adminTest.error = '500 Internal Server Error on admin page';
    } else {
      results.adminTest.error = `Unexpected admin response: ${response.statusCode}`;
    }
    
  } catch (error) {
    console.error('Admin test failed:', error.message);
    results.adminTest.error = error.message;
  }

  // Generate final report
  console.log('\n=== FINAL RESULTS ===');
  console.log('Git Pull Success:', results.gitPull.success);
  console.log('DB Discovery Success:', results.dbDiscovery.success);
  console.log('Working Credentials:', results.dbDiscovery.workingCredentials);
  console.log('Databases Found:', results.dbDiscovery.databases);
  console.log('Homepage Test Success:', results.homepageTest.success);
  console.log('Admin Test Success:', results.adminTest.success);

  // Create test-results directory if it doesn't exist
  if (!fs.existsSync('test-results')) {
    fs.mkdirSync('test-results');
  }

  // Write results to file for reporting
  fs.writeFileSync('test-results/database-discovery-results.json', JSON.stringify(results, null, 2));
  
  return results;
}

// Run the discovery process
runDatabaseDiscovery().then((results) => {
  console.log('\n=== DATABASE DISCOVERY COMPLETE ===');
  
  if (results.dbDiscovery.success) {
    console.log('✓ Database configuration has been fixed!');
  } else {
    console.log('✗ Database configuration fix failed');
  }
  
  process.exit(results.dbDiscovery.success ? 0 : 1);
}).catch((error) => {
  console.error('Fatal error:', error);
  process.exit(1);
});