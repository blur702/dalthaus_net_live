const { chromium } = require('playwright');

(async () => {
  console.log('=== Final E2E Check of dalthaus.net ===\n');
  
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();
  
  const results = {
    passed: [],
    failed: [],
    warnings: []
  };
  
  // Monitor console errors
  const consoleErrors = [];
  page.on('console', msg => {
    if (msg.type() === 'error') {
      consoleErrors.push(msg.text());
    }
  });
  
  // Test endpoints
  const tests = [
    { url: 'https://dalthaus.net/', name: 'Homepage' },
    { url: 'https://dalthaus.net/admin', name: 'Admin Panel' },
    { url: 'https://dalthaus.net/admin/login', name: 'Admin Login' },
    { url: 'https://dalthaus.net/diagnose.php', name: 'Diagnose Page' },
    { url: 'https://dalthaus.net/articles', name: 'Articles' },
    { url: 'https://dalthaus.net/photobooks', name: 'Photobooks' }
  ];
  
  for (const test of tests) {
    console.log(`Testing ${test.name}...`);
    consoleErrors.length = 0;
    
    try {
      const response = await page.goto(test.url, { 
        waitUntil: 'networkidle',
        timeout: 30000 
      });
      
      const status = response.status();
      const content = await page.content();
      
      // Check for specific errors
      if (content.includes('Database Connection Error')) {
        results.failed.push(`${test.name}: Database connection error found`);
      } else if (content.includes('cms_db') || content.includes('cms_user')) {
        results.warnings.push(`${test.name}: Old database references found`);
      } else if (status === 503) {
        results.failed.push(`${test.name}: 503 Service Unavailable`);
      } else if (status === 500) {
        results.failed.push(`${test.name}: 500 Internal Server Error`);
      } else if (status === 404 && test.url !== 'https://dalthaus.net/favicon.ico') {
        results.failed.push(`${test.name}: 404 Not Found`);
      } else if (status === 200 || status === 302) {
        results.passed.push(`${test.name}: OK (${status})`);
      } else {
        results.warnings.push(`${test.name}: Unexpected status ${status}`);
      }
      
      // Check console errors
      if (consoleErrors.length > 0) {
        results.warnings.push(`${test.name}: Console errors: ${consoleErrors.join(', ')}`);
      }
      
    } catch (error) {
      results.failed.push(`${test.name}: ${error.message}`);
    }
  }
  
  // Test agent
  console.log('Testing Agent API...');
  try {
    const agentResponse = await page.goto('https://dalthaus.net/agent.php?action=test&key=dalthaus_agent_key_2025');
    const agentData = await agentResponse.json();
    
    if (agentData.success) {
      results.passed.push(`Agent API: Operational`);
    } else {
      results.failed.push(`Agent API: Not responding correctly`);
    }
  } catch (error) {
    results.failed.push(`Agent API: ${error.message}`);
  }
  
  await browser.close();
  
  // Print results
  console.log('\n=== Test Results ===\n');
  
  console.log(`✅ PASSED (${results.passed.length}):`);
  results.passed.forEach(r => console.log(`   ${r}`));
  
  if (results.failed.length > 0) {
    console.log(`\n❌ FAILED (${results.failed.length}):`);
    results.failed.forEach(r => console.log(`   ${r}`));
  }
  
  if (results.warnings.length > 0) {
    console.log(`\n⚠️  WARNINGS (${results.warnings.length}):`);
    results.warnings.forEach(r => console.log(`   ${r}`));
  }
  
  const totalTests = tests.length + 1; // +1 for agent test
  const passRate = (results.passed.length / totalTests * 100).toFixed(1);
  
  console.log(`\n=== Summary ===`);
  console.log(`Pass Rate: ${passRate}%`);
  console.log(`Status: ${results.failed.length === 0 ? '✅ All tests passed!' : '❌ Some tests failed'}`);
  
  if (results.failed.length === 0 && results.warnings.length === 0) {
    console.log('\n🎉 The site is fully operational with no issues!');
  }
})();