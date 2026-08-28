import type { CartItem } from '../types/pos';

export interface CartCalculationResult {
  totalAmount: number;
  discountAmount: number;
  finalAmount: number;
}

/**
 * Calculates cart subtotal, discount, and final amount
 */
export function calculateCartTotals(
  items: CartItem[],
  discountPercent = 0,
  discountNominal = 0
): CartCalculationResult {
  const totalAmount = items.reduce(
    (sum, item) => sum + item.unit_price * item.quantity,
    0
  );

  let discountAmount = 0;
  if (discountPercent > 0) {
    const clampedPercent = Math.min(100, Math.max(0, discountPercent));
    discountAmount = Math.round((totalAmount * clampedPercent) / 100);
  } else if (discountNominal > 0) {
    discountAmount = Math.min(totalAmount, Math.max(0, discountNominal));
  }

  const finalAmount = Math.max(0, totalAmount - discountAmount);

  return {
    totalAmount,
    discountAmount,
    finalAmount,
  };
}

/**
 * Calculates cash change amount
 */
export function calculateCashChange(cashTendered: number, finalAmount: number): number {
  if (cashTendered < finalAmount) return 0;
  return Math.max(0, cashTendered - finalAmount);
}

/**
 * Calculates inventory stock usage given start stock and updated current stock
 */
export function calculateStockUsed(stockPreviousDay: number, currentStock: number): number {
  return Math.max(0, stockPreviousDay - currentStock);
}
