import { describe, it, expect, beforeEach } from 'vitest';
import 'fake-indexeddb/auto';
import { PrecisPosDatabase } from '../db/pos-db';
import type { StockWaste, OutletPurchase } from '../types/pos';

describe('PrecisPosDatabase Stock Waste & Outlet Purchases Offline Integration', () => {
  let testDb: PrecisPosDatabase;

  beforeEach(async () => {
    testDb = new PrecisPosDatabase();
    await testDb.delete();
    await testDb.open();
  });

  it('persists stock waste logs accurately in IndexedDB', async () => {
    const mockWaste: StockWaste = {
      id: 'waste-milk-01',
      workspace_id: 'ws-norde',
      branch_id: 'branch-seturan',
      item_name: 'Fresh Milk Diamond 1L',
      quantity: 3,
      unit: 'Liter',
      cost_per_unit: 18000,
      total_loss_cost: 54000,
      reason: 'SPOILED',
      notes: 'Kulkas mati semalaman',
      recorded_by_user_id: 'user-ami',
      created_at: new Date().toISOString(),
    };

    await testDb.stockWastes.put(mockWaste);
    const retrieved = await testDb.stockWastes.get('waste-milk-01');

    expect(retrieved).toBeDefined();
    expect(retrieved?.item_name).toBe('Fresh Milk Diamond 1L');
    expect(retrieved?.total_loss_cost).toBe(54000);
    expect(retrieved?.reason).toBe('SPOILED');
  });

  it('filters stock waste logs by reason correctly', async () => {
    const wasteList: StockWaste[] = [
      {
        id: 'w-1',
        workspace_id: 'ws-norde',
        branch_id: 'branch-seturan',
        item_name: 'Sirup Hazelnut',
        quantity: 1,
        unit: 'Botol',
        cost_per_unit: 120000,
        total_loss_cost: 120000,
        reason: 'ACCIDENT_SPILL',
        recorded_by_user_id: 'user-ami',
        created_at: new Date().toISOString(),
      },
      {
        id: 'w-2',
        workspace_id: 'ws-norde',
        branch_id: 'branch-seturan',
        item_name: 'Roti Croissant',
        quantity: 5,
        unit: 'Pcs',
        cost_per_unit: 15000,
        total_loss_cost: 75000,
        reason: 'EXPIRED',
        recorded_by_user_id: 'user-ami',
        created_at: new Date().toISOString(),
      },
      {
        id: 'w-3',
        workspace_id: 'ws-norde',
        branch_id: 'branch-seturan',
        item_name: 'Single Origin Gayo 200g',
        quantity: 1,
        unit: 'Pack',
        cost_per_unit: 80000,
        total_loss_cost: 80000,
        reason: 'BARISTA_MISTAKE',
        recorded_by_user_id: 'user-ami',
        created_at: new Date().toISOString(),
      },
    ];

    await testDb.stockWastes.bulkPut(wasteList);

    const expiredOnly = await testDb.stockWastes.where('reason').equals('EXPIRED').toArray();
    expect(expiredOnly.length).toBe(1);
    expect(expiredOnly[0].item_name).toBe('Roti Croissant');

    const allWastes = await testDb.stockWastes.toArray();
    const totalAccumulatedLoss = allWastes.reduce((acc, curr) => acc + curr.total_loss_cost, 0);
    expect(totalAccumulatedLoss).toBe(275000);
  });

  it('persists outlet petty cash purchases in IndexedDB', async () => {
    const mockPurchase: OutletPurchase = {
      id: 'purch-ice-01',
      workspace_id: 'ws-norde',
      branch_id: 'branch-seturan',
      pos_session_id: 'sess-01',
      item_name: 'Es Batu Kristal 10kg',
      unit: 'Pack',
      quantity: 2,
      unit_price: 12000,
      total_price: 24000,
      category: 'BAHAN_BAKU_DARURAT',
      funding_source: 'CASH_DRAWER',
      notes: 'Beli darurat siang hari',
      recorded_by_user_id: 'user-ami',
      created_at: new Date().toISOString(),
    };

    await testDb.purchases.put(mockPurchase);
    const saved = await testDb.purchases.get('purch-ice-01');

    expect(saved).toBeDefined();
    expect(saved?.item_name).toBe('Es Batu Kristal 10kg');
    expect(saved?.total_price).toBe(24000);
    expect(saved?.funding_source).toBe('CASH_DRAWER');
  });
});
