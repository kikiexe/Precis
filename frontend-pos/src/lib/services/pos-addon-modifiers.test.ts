import { describe, it, expect } from 'vitest';
import { calculateItemUnitPrice, calculateCartTotals } from './pos-calculations';
import type { CartItem, SelectedModifier } from '../types/pos';
import { generateReceiptText } from './printer-service';

describe('calculateItemUnitPrice', () => {
  it('returns base price when no modifiers are attached', () => {
    expect(calculateItemUnitPrice(28000)).toBe(28000);
    expect(calculateItemUnitPrice(28000, [])).toBe(28000);
  });

  it('calculates single modifier addition correctly', () => {
    const modifiers: SelectedModifier[] = [
      { addon_id: 'm1', name: 'Oat Milk Substitute', price: 8000 },
    ];
    // Base 28.000 + 8.000 = 36.000
    expect(calculateItemUnitPrice(28000, modifiers)).toBe(36000);
  });

  it('calculates multiple modifiers addition correctly', () => {
    const modifiers: SelectedModifier[] = [
      { addon_id: 'm1', name: 'Extra Espresso Shot', price: 6000 },
      { addon_id: 'm2', name: 'Vanilla Syrup', price: 5000 },
      { addon_id: 'm3', name: 'Almond Milk', price: 10000 },
    ];
    // Base 30.000 + 6.000 + 5.000 + 10.000 = 51.000
    expect(calculateItemUnitPrice(30000, modifiers)).toBe(51000);
  });
});

describe('calculateCartTotals with Modifiers', () => {
  it('calculates cart total for items with modifiers and quantities', () => {
    const items: CartItem[] = [
      {
        product: {
          id: 'p1',
          name: 'Caffe Latte',
          base_price: 28000,
          category_id: 'c1',
          is_active: true,
        },
        quantity: 2,
        unit_price: 36000, // 28.000 + 8.000 Oat Milk
        notes: 'Less sugar',
        modifiers: [{ addon_id: 'm1', name: 'Oat Milk', price: 8000 }],
      },
      {
        product: {
          id: 'p2',
          name: 'Croissant Butter',
          base_price: 22000,
          category_id: 'c2',
          is_active: true,
        },
        quantity: 1,
        unit_price: 22000,
        notes: '',
        modifiers: [],
      },
    ];

    // (2 * 36.000) + (1 * 22.000) = 72.000 + 22.000 = 94.000
    const result = calculateCartTotals(items, 10, 0); // Diskon 10%
    expect(result.totalAmount).toBe(94000);
    expect(result.discountAmount).toBe(9400);
    expect(result.finalAmount).toBe(84600);
  });
});

describe('generateReceiptText with Modifiers', () => {
  it('includes modifier names on formatted receipt output', () => {
    const order = {
      client_order_id: '123e4567-e89b-12d3-a456-426614174000',
      order_number: 'ORD-TEST-001',
      workspace_id: 'ws-test',
      branch_id: 'br-test',
      pos_session_id: 'sess-test',
      cashier_user_id: 'u1',
      cashier_name: 'Ami',
      order_type: 'DINE_IN' as const,
      total_amount: 36000,
      discount_amount: 0,
      final_amount: 36000,
      payment_method: 'CASH' as const,
      cash_tendered: 50000,
      change_amount: 14000,
      items: [
        {
          product_id: 'p1',
          product_name: 'Caffe Latte',
          quantity: 1,
          unit_price: 36000,
          subtotal: 36000,
          notes: 'Less ice',
          modifiers: [{ addon_id: 'm1', name: 'Oat Milk', price: 8000 }],
        },
      ],
      created_at: new Date().toISOString(),
      sync_status: 'SYNCED' as const,
    };

    const receipt = generateReceiptText(order);
    expect(receipt).toContain('Caffe Latte');
    expect(receipt).toContain('(+Oat Milk)');
    expect(receipt).toContain('Less ice');
    expect(receipt).toContain('36.000');
  });
});
