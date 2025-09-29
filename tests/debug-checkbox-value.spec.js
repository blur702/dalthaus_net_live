import { test, expect } from '@playwright/test';

const PROD_URL = 'https://dalthaus.net';
const USERNAME = 'kevin';
const PASSWORD = '(130Bpm)';

test('Debug exact checkbox value being sent', async ({ page }) => {
  console.log('=== DEBUGGING EXACT CHECKBOX VALUE ===\n');

  // Capture the exact form data being sent
  let sentData = null;

  page.on('request', request => {
    if (request.url().includes('/admin/login') && request.method() === 'POST') {
      sentData = request.postData();
      console.log('Raw POST data captured:', sentData);
    }
  });

  await page.goto(`${PROD_URL}/admin/login`);

  // Fill credentials
  await page.fill('input[name="username"]', USERNAME);
  await page.fill('input[name="password"]', PASSWORD);

  // Check the remember me checkbox
  const rememberCheckbox = page.locator('input[name="remember_me"]');
  await rememberCheckbox.check();

  // Verify the checkbox state in the DOM
  const isChecked = await rememberCheckbox.isChecked();
  const checkboxValue = await rememberCheckbox.getAttribute('value');
  console.log('Checkbox checked:', isChecked);
  console.log('Checkbox value attribute:', checkboxValue);

  // Use page.evaluate to inspect the checkbox directly
  const domInfo = await page.evaluate(() => {
    const checkbox = document.querySelector('input[name="remember_me"]');
    return {
      checked: checkbox.checked,
      value: checkbox.value,
      name: checkbox.name,
      type: checkbox.type,
      outerHTML: checkbox.outerHTML
    };
  });

  console.log('DOM checkbox info:', domInfo);

  // Create a FormData object to see what would be sent
  const formDataPreview = await page.evaluate(() => {
    const form = document.querySelector('form');
    const formData = new FormData(form);
    const data = {};
    for (let [key, value] of formData.entries()) {
      data[key] = value;
    }
    return data;
  });

  console.log('FormData preview:', formDataPreview);

  // Submit and capture the actual data
  await page.click('button[type="submit"]');
  await page.waitForTimeout(2000);

  if (sentData) {
    console.log('\nAnalyzing sent POST data:');

    // Parse the URL-encoded data
    const params = new URLSearchParams(sentData);
    const rememberValue = params.get('remember_me');

    console.log('remember_me parameter value:', rememberValue);
    console.log('remember_me parameter type:', typeof rememberValue);
    console.log('remember_me is truthy:', !!rememberValue);
    console.log('remember_me === "1":', rememberValue === "1");
    console.log('remember_me === "on":', rememberValue === "on");

    // Check all parameters
    console.log('\nAll form parameters:');
    for (const [key, value] of params.entries()) {
      console.log(`  ${key}: "${value}"`);
    }
  } else {
    console.log('No POST data captured!');
  }

  const finalUrl = page.url();
  console.log('\nFinal URL:', finalUrl);
  console.log('Login successful:', finalUrl.includes('/admin/dashboard'));
});