import { describe, it, expect, beforeEach } from 'vitest';
import 'fake-indexeddb/auto';
import { PrecisPosDatabase } from '../db/pos-db';
import type { OfflineOrder, PosSession, Product } from '../types/pos';

describe('PrecisPosDatabase Offline Dexie.js Integration', () => {
  let testDb: PrecisPosDatabase;

  beforeEach(async () => {
    testDb = new PrecisPosDatabase();
    await testDb.delete();
    await testDb.open();
  });

  it('persists products catalog locally in IndexedDB', async () => {
    const mockProduct: Product = {
      id: 'prod-latte-01',
      name: 'Caramel Macchiato',
      base_price: 38000,
      category_id: 'cat-coffee',
      is_active: true,
    };

    await testDb.products.put(mockProduct);
    const retrieved = await testDb.products.get('prod-latte-01');

    expect(retrieved).toBeDefined();
    expect(retrieved?.name).toBe('Caramel Macchiato');
    expect(retrieved?.base_price).toBe(38000);
  });

  it('saves offline order with PENDING sync status', async () => {
    const mockOrder: OfflineOrder = {
      client_order_id: 'ord-uuid-001',
      order_number: 'ORD-892101',
      workspace_id: 'ws-test',
      branch_id: 'branch-test',
      pos_session_id: 'sess-001',
      cashier_user_id: 'user-001',
      cashier_name: 'Barista Budi',
      order_type: 'DINE_IN',
      total_amount: 50000,
      discount_amount: 5000,
      final_amount: 45000,
      payment_method: 'EDC',
      items: [
        {
          product_id: 'prod-latte-01',
          product_name: 'Caramel Macchiato',
          quantity: 1,
          unit_price: 50000,
          subtotal: 50000,
        },
      ],
      created_at: new Date().toISOString(),
      sync_status: 'PENDING',
    };

    await testDb.orders.add(mockOrder);

    const pendingOrders = await testDb.orders.where('sync_status').equals('PENDING').toArray();

    expect(pendingOrders.length).toBe(1);
    expect(pendingOrders[0].order_number).toBe('ORD-892101');
    expect(pendingOrders[0].payment_method).toBe('EDC');
  });

  it('saves offline order with QRIS payment method', async () => {
    const qrisOrder: OfflineOrder = {
      client_order_id: 'ord-uuid-qris-002',
      order_number: 'ORD-892102',
      workspace_id: 'ws-test',
      branch_id: 'branch-test',
      pos_session_id: 'sess-001',
      cashier_user_id: 'user-001',
      cashier_name: 'Barista Budi',
      order_type: 'TAKE_AWAY',
      total_amount: 35000,
      discount_amount: 0,
      final_amount: 35000,
      payment_method: 'QRIS',
      items: [
        {
          product_id: 'prod-latte-01',
          product_name: 'Caramel Macchiato',
          quantity: 1,
          unit_price: 35000,
          subtotal: 35000,
        },
      ],
      created_at: new Date().toISOString(),
      sync_status: 'PENDING',
    };

    await testDb.orders.add(qrisOrder);
    const saved = await testDb.orders.get('ord-uuid-qris-002');

    expect(saved).toBeDefined();
    expect(saved?.payment_method).toBe('QRIS');
    expect(saved?.final_amount).toBe(35000);
  });

  it('manages pos session lifecycle accurately offline', async () => {
    const session: PosSession = {
      id: 'sess-active-01',
      workspace_id: 'ws-test',
      branch_id: 'branch-test',
      opened_by_user_id: 'user-001',
      cashier_name: 'Barista Budi',
      opening_cash: 100000,
      discrepancy_amount: 0,
      status: 'OPEN',
      opened_at: new Date().toISOString(),
      total_cash_sales: 50000,
      total_qris_sales: 30000,
      total_transfer_sales: 45000,
      total_edc_sales: 45000,
      order_count: 3,
    };

    await testDb.sessions.put(session);
    const active = await testDb.sessions.get('sess-active-01');
    expect(active?.status).toBe('OPEN');

    // Simulate close session
    await testDb.sessions.update('sess-active-01', {
      status: 'CLOSED',
      closing_cash_actual: 150000,
      closed_at: new Date().toISOString(),
    });

    const closed = await testDb.sessions.get('sess-active-01');
    expect(closed?.status).toBe('CLOSED');
    expect(closed?.closing_cash_actual).toBe(150000);
  });
});
