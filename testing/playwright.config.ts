import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './e2e',
  timeout: 120000, // 2 minutes for each test
  expect: {
    timeout: 30000
  },
  fullyParallel: false, // Run tests sequentially for better debugging
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1, // Single worker for sequential execution
  reporter: [
    ['html', { outputFolder: 'reports/html-report' }],
    ['json', { outputFile: 'results/test-results.json' }],
    ['list']
  ],
  outputDir: 'results/',
  use: {
    baseURL: 'https://dalthaus.net',
    headless: false, // Show browser for debugging
    viewport: { width: 1280, height: 720 },
    ignoreHTTPSErrors: true,
    screenshot: 'on',
    video: 'retain-on-failure',
    trace: 'retain-on-failure',
    actionTimeout: 30000,
    navigationTimeout: 60000,
    // Add user agent to avoid bot detection
    userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
  },
  projects: [
    {
      name: 'diagnosis',
      use: {
        browserName: 'chromium',
        headless: false
      },
    },
  ],
});