import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/Playwright',
  use: {
    baseURL: 'http://127.0.0.1:8000',
    headless: true,
  },
});
