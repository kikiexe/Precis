import { describe, it, expect } from 'vitest';
import { calculateExpectedCash } from './pos-calculations';
import type { OutletPurchase } from '../types/pos';

describe('calculateExpectedCash', () => {
  it('calculates expected cash without purchases and refunds', () => {
    // Opening 200.000 + Sales 300.000 = 500.000
    expect(calculateExpectedCash(200000, 300000)).toBe(500000);
  });

  it('deducts cash purchases from expected drawer cash', () => {
    // Opening 200.000 + Sales 300.000 - Purchases 50.000 = 450.000
    expect(calculateExpectedCash(200000, 300000, 500000 * 0.1)).toBe(450000);
  });

  it('deducts both cash purchases and cash refunds accurately', () => {
    // Opening 200.000 + Sales 500.000 - Purchases 75.000 - Refunds 25.000 = 600.000
    expect(calculateExpectedCash(200000, 500000, 75000, 25000)).toBe(600000);
  });

  it('never returns negative expected cash', () => {
    expect(calculateExpectedCash(50000, 20000, 100000, 0)).toBe(0);
  });
});

describe('OutletPurchase Filtering and Totals', () => {
  const mockPurchases: OutletPurchase[] = [
    {
      id: 'pur-1',
      workspace_id: 'ws-1',
      branch_id: 'br-1',
      pos_session_id: 'sess-1',
      item_name: 'Es Batu Kristal 10kg',
      unit: 'Pack',
      quantity: 3,
      unit_price: 12000,
      total_price: 36000,
      category: 'BAHAN_BAKU_DARURAT',
      funding_source: 'CASH_DRAWER',
      recorded_by_user_id: 'u1',
      created_at: new Date().toISOString(),
    },
    {
      id: 'pur-2',
      workspace_id: 'ws-1',
      branch_id: 'br-1',
      pos_session_id: 'sess-1',
      item_name: 'Susu UHT Segar 2L',
      unit: 'Liter',
      quantity: 2,
      unit_price: 15000,
      total_price: 30000,
      category: 'BAHAN_BAKU_DARURAT',
      funding_source: 'CASH_DRAWER',
      recorded_by_user_id: 'u1',
      created_at: new Date().toISOString(),
    },
    {
      id: 'pur-3',
      workspace_id: 'ws-1',
      branch_id: 'br-1',
      pos_session_id: 'sess-1',
      item_name: 'Kabel Terminal Listrik',
      unit: 'Pcs',
      quantity: 1,
      unit_price: 25000,
      total_price: 25000,
      category: 'OPERASIONAL_TOKO',
      funding_source: 'EXTERNAL_REIMBURSE',
      recorded_by_user_id: 'u1',
      created_at: new Date().toISOString(),
    },
  ];

  it('correctly calculates total cash drawer purchases vs reimburse purchases', () => {
    const cashDrawerTotal = mockPurchases
      .filter((p) => p.funding_source === 'CASH_DRAWER')
      .reduce((sum, p) => sum + p.total_price, 0);

    const reimburseTotal = mockPurchases
      .filter((p) => p.funding_source === 'EXTERNAL_REIMBURSE')
      .reduce((sum, p) => sum + p.total_price, 0);

    // 36.000 + 30.000 = 66.000
    expect(cashDrawerTotal).toBe(66000);
    // 25.000
    expect(reimburseTotal).toBe(25000);
  });
});
