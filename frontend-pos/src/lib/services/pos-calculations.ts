import type { CartItem, TaxSettings } from '../types/pos';

export interface CartCalculationResult {
  totalAmount: number;
  discountAmount: number;
  netAmount: number;
  taxName?: string;
  taxRate?: number;
  taxType?: 'INCLUSIVE' | 'EXCLUSIVE';
  taxAmount: number;
  finalAmount: number;
}

/**
 * kalkulasi subtotal keranjang, diskon, dan pajak dinamis (inclusive vs exclusive)
 */
export function calculateCartTotals(
  items: CartItem[],
  discountPercent = 0,
  discountNominal = 0,
  taxSettings?: TaxSettings | null
): CartCalculationResult {
  const totalAmount = items.reduce((sum, item) => sum + item.unit_price * item.quantity, 0);

  let discountAmount = 0;
  if (discountPercent > 0) {
    const clampedPercent = Math.min(100, Math.max(0, discountPercent));
    discountAmount = Math.round((totalAmount * clampedPercent) / 100);
  } else if (discountNominal > 0) {
    discountAmount = Math.min(totalAmount, Math.max(0, discountNominal));
  }

  const netAmount = Math.max(0, totalAmount - discountAmount);

  let taxAmount = 0;
  let taxName: string | undefined;
  let taxRate: number | undefined;
  let taxType: 'INCLUSIVE' | 'EXCLUSIVE' | undefined;
  let finalAmount = netAmount;

  if (taxSettings && taxSettings.tax_enabled && taxSettings.tax_rate > 0) {
    taxRate = taxSettings.tax_rate;
    taxType = taxSettings.tax_type ?? 'INCLUSIVE';
    taxName = taxSettings.tax_name ?? 'PB1';

    if (taxType === 'EXCLUSIVE') {
      taxAmount = Math.round(netAmount * (taxRate / 100));
      finalAmount = netAmount + taxAmount;
    } else {
      // INCLUSIVE (Pajak sudah terkandung di dalam netAmount)
      taxAmount = Math.round(netAmount - netAmount / (1 + taxRate / 100));
      finalAmount = netAmount;
    }
  }

  return {
    totalAmount,
    discountAmount,
    netAmount,
    taxName,
    taxRate,
    taxType,
    taxAmount,
    finalAmount,
  };
}

/**
 * kalkulasi uang kembalian pembayaran tunai
 */
export function calculateCashChange(cashTendered: number, finalAmount: number): number {
  if (cashTendered < finalAmount) return 0;
  return Math.max(0, cashTendered - finalAmount);
}

/**
 * kalkulasi penggunaan stok bahan baku
 */
export function calculateStockUsed(stockPreviousDay: number, currentStock: number): number {
  return Math.max(0, stockPreviousDay - currentStock);
}

/**
 * Calculates expected closing cash considering opening cash, cash sales, cash purchases (petty cash), and cash refunds
 */
export function calculateExpectedCash(
  openingCash: number,
  cashSales: number,
  cashPurchases = 0,
  cashRefunds = 0
): number {
  return Math.max(0, openingCash + cashSales - cashPurchases - cashRefunds);
}

/**
 * Calculates unit price with attached modifiers
 */
export function calculateItemUnitPrice(basePrice: number, modifiers?: Array<{ price: number }>): number {
  if (!modifiers || modifiers.length === 0) return basePrice;
  const modifierSum = modifiers.reduce((sum, mod) => sum + (mod.price || 0), 0);
  return basePrice + modifierSum;
}
