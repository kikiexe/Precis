import { test, expect } from '@playwright/test';

test.describe('POS Kiosk Application E2E Flow', () => {
  test('renders POS layout and navigation successfully', async ({ page }) => {
    await page.goto('/');

    // Check sidebar brand
    const sidebar = page.locator('aside');
    await expect(sidebar).toBeVisible();

    // Check navigation buttons exist
    await expect(page.getByRole('button', { name: /POINT OF SALE/i })).toBeVisible();
    await expect(page.getByRole('button', { name: /AKTIVITAS/i })).toBeVisible();
    await expect(page.getByRole('button', { name: /SHIFT KASIR/i })).toBeVisible();
    await expect(page.getByRole('button', { name: /SETTLEMENT/i })).toBeVisible();
    await expect(page.getByRole('button', { name: /MENU JUALAN/i })).toBeVisible();
    await expect(page.getByRole('button', { name: /INVENTORI/i })).toBeVisible();
  });

  test('switches tabs and displays spreadsheet inventory correctly', async ({ page }) => {
    await page.goto('/');

    // Navigate to INVENTORI tab
    await page.getByRole('button', { name: /INVENTORI/i }).click();

    // Check table headers
    await expect(page.getByText('Nama Bahan Baku')).toBeVisible();
    await expect(page.getByText('Stok Kemarin')).toBeVisible();
    await expect(page.getByText('Stok Sekarang')).toBeVisible();
    await expect(page.getByText('Stok Terpakai')).toBeVisible();
  });
});
