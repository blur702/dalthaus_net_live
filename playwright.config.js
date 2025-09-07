module.exports = {
  use: {
    baseURL: 'http://localhost:8000',
    headless: true,
    screenshot: 'only-on-failure',
  },
  testDir: './tests/e2e',
  timeout: 30000,
  retries: 0,
};