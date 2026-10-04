import { test, expect } from '@playwright/test';
import path from 'node:path';

const visualDate = new Date().toISOString().slice(0, 10);
const visualRunId = process.env.ACCESSING_VISUAL_RUN_ID ?? 'engine-20261003180548-accessing-b3e807';
const visualRoot = path.resolve('..', 'var', 'Accessing', visualDate, visualRunId, 'screenshots', 'web');

test.setTimeout(120_000);

test('sign-in page is reachable', async ({ request }) => {
  const response = await request.get('/access/signin', {
    headers: { Connection: 'close' },
  });

  expect(response.status()).toBe(200);
  expect(await response.text()).toContain('<h1>Sign in to continue</h1>');
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
