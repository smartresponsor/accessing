import { test, expect } from '@playwright/test';
import path from 'node:path';

const visualDate = new Date().toISOString().slice(0, 10);
const visualRunId = process.env.ACCESSING_VISUAL_RUN_ID ?? 'engine-20261003180548-accessing-b3e807';
const visualRoot = path.resolve('..', 'var', 'Accessing', visualDate, visualRunId, 'screenshots', 'web');

test('sign-in page is reachable', async ({ page }) => {
  await page.goto('/access/signin');
  await expect(page.getByRole('heading', { name: 'Sign in' })).toBeVisible();
});

test('reset-password check-email page uses the canonical template', async ({ page }) => {
  const response = await page.goto('/access/reset/password/check/email');

  expect(response?.status()).toBe(200);
  await expect(page.getByRole('heading', { name: 'Check email' })).toBeVisible();
  await page.screenshot({
    path: path.join(visualRoot, 'reset-password-check-email.png'),
    fullPage: true,
  });
});
