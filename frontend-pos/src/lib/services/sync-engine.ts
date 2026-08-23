import { db } from '../db/pos-db';
import { posApiClient } from './api-client';
import type { OfflineOrder } from '../types/pos';

export interface SyncStatus {
  isSyncing: boolean;
  pendingCount: number;
  lastSyncedAt: Date | null;
  lastError: string | null;
}

export type SyncStatusListener = (status: SyncStatus) => void;

export class SyncEngine {
  private isSyncing = false;
  private lastSyncedAt: Date | null = null;
  private lastError: string | null = null;
  private listeners: Set<SyncStatusListener> = new Set();

  public subscribe(listener: SyncStatusListener): () => void {
    this.listeners.add(listener);
    this.emitStatus();
    return () => this.listeners.delete(listener);
  }

  private async emitStatus(): Promise<void> {
    const pendingCount = await this.getPendingCount();
    const status: SyncStatus = {
      isSyncing: this.isSyncing,
      pendingCount,
      lastSyncedAt: this.lastSyncedAt,
      lastError: this.lastError,
    };

    for (const listener of this.listeners) {
      listener(status);
    }
  }

  public async getPendingCount(): Promise<number> {
    try {
      return await db.orders.where('sync_status').equals('PENDING').count();
    } catch {
      return 0;
    }
  }

  public async syncPendingOrders(): Promise<{ success: boolean; syncedCount: number }> {
    if (this.isSyncing) {
      return { success: false, syncedCount: 0 };
    }

    if (typeof navigator !== 'undefined' && !navigator.onLine) {
      return { success: false, syncedCount: 0 };
    }

    this.isSyncing = true;
    this.lastError = null;
    await this.emitStatus();

    try {
      const pendingOrders: OfflineOrder[] = await db.orders
        .where('sync_status')
        .equals('PENDING')
        .toArray();

      if (pendingOrders.length === 0) {
        this.isSyncing = false;
        await this.emitStatus();
        return { success: true, syncedCount: 0 };
      }

      // format payload batch sesuai SyncOrderBatchRequest
      const payload = {
        orders: pendingOrders.map((ord) => ({
          client_order_id: ord.client_order_id,
          order_number: ord.order_number,
          total_amount: Number(ord.total_amount),
          discount_amount: Number(ord.discount_amount || 0),
          final_amount: Number(ord.final_amount),
          payment_method: ord.payment_method,
          payment_status: 'PAID',
          pos_session_id: ord.pos_session_id || undefined,
          cashier_user_id: ord.cashier_user_id || undefined,
          items: ord.items.map((item) => ({
            product_id: item.product_id || undefined,
            product_name: item.product_name,
            unit_price: Number(item.unit_price),
            quantity: Number(item.quantity),
            subtotal: Number(item.subtotal),
            notes: item.notes || undefined,
          })),
        })),
      };

      const response = await posApiClient.post<{ synced_count: number; order_ids: string[] }>(
        '/pos/orders/sync-batch',
        payload
      );

      // update status pesanan jadi SYNCED sekaligus di database lokal
      await db.transaction('rw', db.orders, async () => {
        for (const ord of pendingOrders) {
          await db.orders.update(ord.client_order_id, { sync_status: 'SYNCED' });
        }
      });

      this.lastSyncedAt = new Date();
      this.lastError = null;
      this.isSyncing = false;
      await this.emitStatus();

      return {
        success: true,
        syncedCount: response.data?.synced_count ?? pendingOrders.length,
      };
    } catch (err: unknown) {
      if (err instanceof Error) {
        this.lastError = err.message;
      } else {
        this.lastError = 'Gagal melakukan sinkronisasi batch transaksi.';
      }
      this.isSyncing = false;
      await this.emitStatus();
      return { success: false, syncedCount: 0 };
    }
  }

  public initAutoSync(): () => void {
    if (typeof window === 'undefined') {
      return () => {};
    }

    const handleOnline = () => {
      this.syncPendingOrders();
    };

    window.addEventListener('online', handleOnline);

    // trigger sinkronisasi awal saat aplikasi dimuat
    if (navigator.onLine) {
      this.syncPendingOrders();
    }

    return () => {
      window.removeEventListener('online', handleOnline);
    };
  }
}

export const syncEngine = new SyncEngine();
