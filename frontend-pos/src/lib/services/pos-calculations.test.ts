import { describe, it, expect } from 'vitest';
import { calculateCartTotals, calculateCashChange, calculateStockUsed } from './pos-calculations';
import type { CartItem } from '../types/pos';

const mockItems: CartItem[] = [
  {
    product: {
      id: 'p1',
      name: 'Americano Double Shot',
      price: 25000,
      category_id: 'c1',
      is_active: true,
    },
    quantity: 2,
    unit_price: 25000,
    notes: '',
  },
  {
    product: {
      id: 'p2',
      name: 'Matcha Latte Oat',
      price: 35000,
      category_id: 'c1',
      is_active: true,
    },
    quantity: 1,
    unit_price: 35000,
    notes: 'Less sweet',
  },
];

describe('calculateCartTotals', () => {
  it('calculates total correctly without discount', () => {
    // 2 x 25.000 + 1 x 35.000 = 85.000
    const result = calculateCartTotals(mockItems);
    expect(result.totalAmount).toBe(85000);
    expect(result.discountAmount).toBe(0);
    expect(result.finalAmount).toBe(85000);
  });

  it('applies percentage discount accurately', () => {
    // 85.000 - 10% = 85.000 - 8.500 = 76.500
    const result = calculateCartTotals(mockItems, 10, 0);
    expect(result.totalAmount).toBe(85000);
    expect(result.discountAmount).toBe(8500);
    expect(result.finalAmount).toBe(76500);
  });

  it('clamps percentage discount between 0% and 100%', () => {
    const resultOver = calculateCartTotals(mockItems, 150, 0);
    expect(resultOver.discountAmount).toBe(85000);
    expect(resultOver.finalAmount).toBe(0);
  });

  it('applies nominal discount accurately', () => {
    // 85.000 - 15.000 = 70.000
    const result = calculateCartTotals(mockItems, 0, 15000);
    expect(result.totalAmount).toBe(85000);
    expect(result.discountAmount).toBe(15000);
    expect(result.finalAmount).toBe(70000);
  });

  it('nominal discount does not exceed total amount', () => {
    const result = calculateCartTotals(mockItems, 0, 100000);
    expect(result.discountAmount).toBe(85000);
    expect(result.finalAmount).toBe(0);
  });

  it('handles empty items array gracefully', () => {
    const result = calculateCartTotals([]);
    expect(result.totalAmount).toBe(0);
    expect(result.discountAmount).toBe(0);
    expect(result.finalAmount).toBe(0);
  });
});

describe('calculateCashChange', () => {
  it('calculates exact change when money tendered is sufficient', () => {
    expect(calculateCashChange(100000, 85000)).toBe(15000);
  });

  it('returns 0 change for exact payment', () => {
    expect(calculateCashChange(85000, 85000)).toBe(0);
  });

  it('returns 0 change if money tendered is less than bill amount', () => {
    expect(calculateCashChange(50000, 85000)).toBe(0);
  });
});

describe('calculateStockUsed', () => {
  it('calculates used stock when current stock is lower than previous stock', () => {
    // Stok kemarin 24, sekarang 10 -> terpakai 14
    expect(calculateStockUsed(24, 10)).toBe(14);
  });

  it('returns 0 used stock if restocked higher than previous day', () => {
    expect(calculateStockUsed(10, 20)).toBe(0);
  });
});
