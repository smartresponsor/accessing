import { test, expect } from '@playwright/test';

test('sign-in page is reachable', async ({ page }) => {
  await page.goto('/access/signin');
  await expect(page.getByRole('heading', { name: /Sign in/i })).toBeVisible();
});

test('registration page is reachable', async ({ page }) => {
  await page.goto('/access/register');
  await expect(page.getByRole('heading', { name: /Register access/i })).toBeVisible();
});

test('recovery request page is reachable', async ({ page }) => {
  await page.goto('/access/recover');
  await expect(page.getByRole('heading', { name: /Recover access/i })).toBeVisible();
});

test('password reset request page is reachable', async ({ page }) => {
  await page.goto('/access/reset/password/request');
  await expect(page.getByRole('heading', { name: /Request password reset/i })).toBeVisible();
});
