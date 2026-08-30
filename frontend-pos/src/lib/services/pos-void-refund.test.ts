import { describe, it, expect } from 'vitest';
import type { OfflineOrder } from '../types/pos';

function getRemainingRefundable(order: Pick<OfflineOrder, 'final_amount' | 'refund_amount'>): number {
  return Math.max(0, order.final_amount - (order.refund_amount || 0));
}

function determineRefundStatus(
  currentRefunded: number,
  additionalRefund: number,
  finalAmount: number
): 'REFUNDED' | 'PARTIALLY_REFUNDED' {
  const total = currentRefunded + additionalRefund;
  return total >= finalAmount ? 'REFUNDED' : 'PARTIALLY_REFUNDED';
}

function isVoidEligible(order: OfflineOrder, activeSessionId?: string): boolean {
  if (order.payment_status !== 'PAID') return false;
  if (!activeSessionId || order.pos_session_id !== activeSessionId) return false;
  return true;
}

describe('POS Void & Refund Calculations', () => {
  it('calculates full remaining refundable for fresh paid order', () => {
    const order = { final_amount: 50000, refund_amount: 0 };
    expect(getRemainingRefundable(order)).toBe(50000);
  });

  it('calculates remaining refundable after partial refund', () => {
    const order = { final_amount: 80000, refund_amount: 30000 };
    expect(getRemainingRefundable(order)).toBe(50000);
  });

  it('returns 0 remaining refundable when fully refunded', () => {
    const order = { final_amount: 50000, refund_amount: 50000 };
    expect(getRemainingRefundable(order)).toBe(0);
  });

  it('determines PARTIALLY_REFUNDED status when refund is less than total', () => {
    expect(determineRefundStatus(0, 25000, 50000)).toBe('PARTIALLY_REFUNDED');
  });

  it('determines REFUNDED status when full amount is returned', () => {
    expect(determineRefundStatus(20000, 30000, 50000)).toBe('REFUNDED');
  });

  it('validates void eligibility strictly for active session', () => {
    const order: OfflineOrder = {
      client_order_id: 'ord-1',
      order_number: 'ORD-001',
      workspace_id: 'ws-1',
      branch_id: 'br-1',
      pos_session_id: 'sess-today',
      cashier_user_id: 'usr-1',
      cashier_name: 'Ami',
      order_type: 'DINE_IN',
      total_amount: 40000,
      discount_amount: 0,
      final_amount: 40000,
      payment_method: 'CASH',
      payment_status: 'PAID',
      items: [],
      created_at: new Date().toISOString(),
      sync_status: 'SYNCED',
    };

    expect(isVoidEligible(order, 'sess-today')).toBe(true);
    expect(isVoidEligible(order, 'sess-yesterday')).toBe(false);
    expect(isVoidEligible({ ...order, payment_status: 'VOIDED' }, 'sess-today')).toBe(false);
    expect(isVoidEligible({ ...order, payment_status: 'REFUNDED' }, 'sess-today')).toBe(false);
  });
});
