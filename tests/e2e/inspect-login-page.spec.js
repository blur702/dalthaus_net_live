import { test, expect } from '@playwright/test';

test('Inspect login page structure', async ({ page }) => {
  console.log('🔍 Inspecting login page structure...');

  await page.goto('https://dalthaus.net/admin/login');

  // Take screenshot
  await page.screenshot({ path: 'login-page-inspection.png', fullPage: true });
  console.log('📸 Screenshot saved: login-page-inspection.png');

  // Get page HTML
  const html = await page.content();
  console.log('\n📄 Login page HTML:');
  console.log('==================');

  // Look for form elements
  const formInputs = await page.locator('input').all();
  console.log(`\nFound ${formInputs.length} input elements:`);

  for (let i = 0; i < formInputs.length; i++) {
    const input = formInputs[i];
    const type = await input.getAttribute('type') || 'text';
    const name = await input.getAttribute('name') || 'unnamed';
    const id = await input.getAttribute('id') || 'no-id';
    const value = await input.getAttribute('value') || '';

    console.log(`  ${i + 1}. Type: ${type}, Name: ${name}, ID: ${id}, Value: ${value}`);
  }

  // Look specifically for remember checkbox
  const rememberInputs = await page.locator('input[type="checkbox"]').all();
  console.log(`\nFound ${rememberInputs.length} checkbox elements:`);

  for (let i = 0; i < rememberInputs.length; i++) {
    const checkbox = rememberInputs[i];
    const name = await checkbox.getAttribute('name') || 'unnamed';
    const id = await checkbox.getAttribute('id') || 'no-id';
    const value = await checkbox.getAttribute('value') || '';

    console.log(`  ${i + 1}. Checkbox - Name: ${name}, ID: ${id}, Value: ${value}`);
  }

  // Look for labels that might be associated
  const labels = await page.locator('label').all();
  console.log(`\nFound ${labels.length} label elements:`);

  for (let i = 0; i < labels.length; i++) {
    const label = labels[i];
    const forAttr = await label.getAttribute('for') || 'no-for';
    const text = await label.textContent() || '';

    console.log(`  ${i + 1}. Label - For: ${forAttr}, Text: "${text.trim()}"`);
  }

  // Check form structure
  const forms = await page.locator('form').all();
  console.log(`\nFound ${forms.length} form elements:`);

  for (let i = 0; i < forms.length; i++) {
    const form = forms[i];
    const action = await form.getAttribute('action') || 'no-action';
    const method = await form.getAttribute('method') || 'get';

    console.log(`  ${i + 1}. Form - Action: ${action}, Method: ${method}`);
  }

  // Look for "remember" text anywhere on page
  const pageText = await page.textContent('body');
  const hasRememberText = pageText.toLowerCase().includes('remember');
  console.log(`\nPage contains "remember" text: ${hasRememberText}`);

  if (hasRememberText) {
    // Find elements containing "remember"
    const rememberElements = await page.locator('text=/remember/i').all();
    console.log(`Found ${rememberElements.length} elements with "remember" text:`);

    for (let i = 0; i < rememberElements.length; i++) {
      const element = rememberElements[i];
      const tagName = await element.evaluate(el => el.tagName);
      const text = await element.textContent();

      console.log(`  ${i + 1}. ${tagName}: "${text.trim()}"`);
    }
  }

  console.log('\n✅ Login page inspection complete');
});