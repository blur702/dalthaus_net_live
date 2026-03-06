const { test, expect } = require('@playwright/test');

/**
 * Debug test to check photobook ordering in database
 */

test('Debug photobook sort_order values', async ({ page, context }) => {
    console.log('\n[TEST] Debugging photobook sort_order...');

    // Login
    await page.goto('https://dalthaus.net/admin/login');
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard');

    // Go to reorder page and get current order
    await page.goto('https://dalthaus.net/admin/photobooks/reorder');
    await page.waitForLoadState('networkidle');

    const items = await page.locator('.sortable-item').all();
    console.log(`\n[ADMIN] Found ${items.length} photobooks:`);

    for (const item of items) {
        const id = await item.getAttribute('data-id');
        const title = await item.locator('h3').first().textContent();
        const orderBadge = await item.locator('.bg-gray-200').first().textContent();
        console.log(`  ID ${id}: "${title.trim()}" - Order: ${orderBadge}`);
    }

    // Check public page
    await page.goto('https://dalthaus.net/photobooks');
    await page.waitForLoadState('networkidle');

    const publicLinks = await page.locator('article h3 a').all();
    console.log(`\n[PUBLIC] Found ${publicLinks.length} photobooks on public page:`);

    for (let i = 0; i < publicLinks.length; i++) {
        const title = await publicLinks[i].textContent();
        console.log(`  ${i + 1}. "${title.trim()}"`);
    }

    // Try to reverse order and save
    console.log('\n[TEST] Attempting to reverse order...');
    await page.goto('https://dalthaus.net/admin/photobooks/reorder');
    await page.waitForLoadState('networkidle');

    const itemsForReorder = await page.locator('.sortable-item').all();
    const orderData = {};

    for (let i = 0; i < itemsForReorder.length; i++) {
        const id = await itemsForReorder[i].getAttribute('data-id');
        // Reverse: first item gets highest number
        orderData[id] = itemsForReorder.length - i;
    }

    console.log('[TEST] Sending this order:', orderData);

    const csrfToken = await page.locator('input[name="_token"]').first().getAttribute('value');
    const formData = new URLSearchParams();
    formData.append('_token', csrfToken);
    formData.append('order', JSON.stringify(orderData));

    const response = await page.request.post('https://dalthaus.net/admin/photobooks/update-order', {
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        data: formData.toString()
    });

    console.log('[TEST] Response status:', response.status());
    const responseText = await response.text();
    console.log('[TEST] Response:', responseText);

    // Wait a moment for database to update
    await page.waitForTimeout(1000);

    // Check admin page again
    await page.goto('https://dalthaus.net/admin/photobooks/reorder');
    await page.waitForLoadState('networkidle');

    const itemsAfter = await page.locator('.sortable-item').all();
    console.log(`\n[ADMIN AFTER SAVE] Photobooks order:`);

    for (const item of itemsAfter) {
        const id = await item.getAttribute('data-id');
        const title = await item.locator('h3').first().textContent();
        const orderBadge = await item.locator('.bg-gray-200').first().textContent();
        console.log(`  ID ${id}: "${title.trim()}" - Order: ${orderBadge}`);
    }

    // Check public page again
    await page.goto('https://dalthaus.net/photobooks');
    await page.waitForLoadState('networkidle');

    const publicLinksAfter = await page.locator('article h3 a').all();
    console.log(`\n[PUBLIC AFTER SAVE] Photobooks on public page:`);

    for (let i = 0; i < publicLinksAfter.length; i++) {
        const title = await publicLinksAfter[i].textContent();
        console.log(`  ${i + 1}. "${title.trim()}"`);
    }
});
