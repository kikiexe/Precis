import { test, expect } from '@playwright/test';

test.describe('POS Kiosk Application E2E Flow', () => {
  test.beforeEach(async ({ page }) => {
    // Inject paired token in localStorage
    await page.addInitScript(() => {
      localStorage.setItem('precis_pos_device_token', 'tok-e2e-demo-terminal');
    });

    // Mock backend terminal info and catalog endpoints
    await page.route('**/api/v1/pos/terminal', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'success',
          data: {
            id: 'term-e2e-01',
            branch_id: 'branch-sleman',
            branch_name: 'Précis Sleman Outlet',
            workspace_id: 'ws-e2e',
            workspace_name: 'Précis Coffee',
            device_token: 'tok-e2e-demo-terminal',
            cashiers: [
              { id: 'usr-1', name: 'Barista Utama', role: 'KASIR', pin: '1234' },
            ],
            products: [],
            categories: [],
          },
        }),
      });
    });

    await page.route('**/api/v1/pos/products', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'success', data: [] }),
      });
    });

    await page.route('**/api/v1/pos/orders/recent', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'success', data: [] }),
      });
    });
  });

  test('renders POS layout and navigation successfully', async ({ page }) => {
    await page.goto('/');

    // Check navigation sidebar
    const navSidebar = page.locator('aside').first();
    await expect(navSidebar).toBeVisible();

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
