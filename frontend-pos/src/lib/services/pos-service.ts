import { posApiClient } from './api-client';
import { db } from '../db/pos-db';
import type {
  PosTerminalInfo,
  CatalogCategoryItem,
  Category,
  Product,
  PosSession,
  OpenSessionResponse,
  CloseSessionResponse,
  MasterUnlockResult,
  OfflineOrder,
  AddonCategory,
} from '../types/pos';

export class PosService {
  public async getTerminalInfo(): Promise<PosTerminalInfo> {
    const response = await posApiClient.get<PosTerminalInfo>('/pos/terminal-info');
    if (response.data) {
      return response.data;
    }
    if ((response as unknown as { terminal_id?: string }).terminal_id) {
      return response as unknown as PosTerminalInfo;
    }
    throw new Error(response.message || 'Gagal memuat informasi terminal POS.');
  }

  public async syncCatalogToLocalDb(): Promise<{ categoriesCount: number; productsCount: number }> {
    const response = await posApiClient.get<CatalogCategoryItem[]>('/pos/products');

    if (!response.data || !Array.isArray(response.data)) {
      throw new Error(response.message || 'Format katalog produk tidak valid.');
    }

    const categories: Category[] = [];
    const products: Product[] = [];

    response.data.forEach((catItem) => {
      categories.push({
        id: catItem.id,
        name: catItem.name,
      });

      if (catItem.products && Array.isArray(catItem.products)) {
        catItem.products.forEach((prod) => {
          products.push({
            id: prod.id,
            category_id: prod.category_id,
            name: prod.name,
            base_price: Number(prod.base_price),
            is_active: prod.is_active,
            addon_category_ids: prod.addon_category_ids || [],
          });
        });
      }
    });

    if (categories.length > 0) {
      await db.categories.bulkPut(categories);
    }

    if (products.length > 0) {
      await db.products.bulkPut(products);
    }

    return {
      categoriesCount: categories.length,
      productsCount: products.length,
    };
  }

  public async syncAddonsToLocalDb(): Promise<{ addonCategoriesCount: number }> {
    try {
      const response = await posApiClient.get<AddonCategory[]>('/pos/addons');
      if (response.data && Array.isArray(response.data)) {
        await db.addonCategories.bulkPut(response.data);
        return { addonCategoriesCount: response.data.length };
      }
    } catch (err: unknown) {
      if (typeof navigator !== 'undefined' && !navigator.onLine) {
        return { addonCategoriesCount: 0 };
      }
      console.warn('[POS Sync] Gagal menyinkronkan data add-on ke IndexedDB:', err);
    }
    return { addonCategoriesCount: 0 };
  }

  public async syncRecentOrdersToLocalDb(): Promise<{ ordersCount: number }> {
    try {
      const response = await posApiClient.get<OfflineOrder[]>('/pos/orders');
      if (response.data && Array.isArray(response.data) && response.data.length > 0) {
        await db.orders.bulkPut(response.data);
        return { ordersCount: response.data.length };
      }
    } catch (err: unknown) {
      if (typeof navigator !== 'undefined' && !navigator.onLine) {
        // mode offline normal: kasir beroperasi tanpa koneksi internet
        return { ordersCount: 0 };
      }
      console.warn('[POS Sync] Gagal menyinkronkan riwayat transaksi server ke IndexedDB:', err);
    }
    return { ordersCount: 0 };
  }

  public async openSession(
    cashierUserId: string,
    pin: string,
    openingCash: number,
    notes?: string
  ): Promise<PosSession> {
    const payload = {
      cashier_user_id: cashierUserId,
      pin,
      opening_cash: openingCash,
      notes: notes || undefined,
    };

    const response = await posApiClient.post<OpenSessionResponse>('/pos/sessions/open', payload);

    if (response.data) {
      const data = response.data;
      const newSession: PosSession = {
        id: data.id,
        workspace_id: '',
        branch_id: data.branch_id,
        opened_by_user_id: data.opened_by_user_id,
        opening_cash: Number(data.opening_cash),
        status: 'OPEN',
        opened_at: data.opened_at,
        total_cash_sales: 0,
        total_qris_sales: 0,
        total_transfer_sales: 0,
        discrepancy_amount: 0,
        order_count: 0,
      };

      await db.sessions.put(newSession);
      return newSession;
    }

    throw new Error(response.message || 'Gagal membuka sesi kasir.');
  }

  public async closeSession(
    posSessionId: string,
    closingCashActual: number,
    closedByUserId?: string,
    notes?: string
  ): Promise<CloseSessionResponse> {
    const payload = {
      pos_session_id: posSessionId,
      closing_cash_actual: closingCashActual,
      closed_by_user_id: closedByUserId || undefined,
      notes: notes || undefined,
    };

    const response = await posApiClient.post<CloseSessionResponse>('/pos/sessions/close', payload);

    if (response.data) {
      const data = response.data;
      await db.sessions.update(posSessionId, {
        status: 'CLOSED',
        closing_cash_actual: Number(data.closing_cash_actual),
        closing_cash_expected: Number(data.closing_cash_expected),
        discrepancy_amount: Number(data.discrepancy_amount),
        closed_at: data.closed_at,
      });

      return data;
    }

    throw new Error(response.message || 'Gagal menutup sesi kasir.');
  }

  public async masterUnlock(email: string, password: string): Promise<MasterUnlockResult> {
    const payload = { email, password };
    const response = await posApiClient.post<MasterUnlockResult>('/pos/master-unlock', payload);

    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Otorisasi Master Lock gagal.');
  }

  public async pairDevice(deviceToken: string): Promise<PosTerminalInfo> {
    posApiClient.setDeviceToken(deviceToken);
    return await this.getTerminalInfo();
  }

  public async voidOrder(
    orderId: string,
    approverUserId: string,
    pin: string,
    reason: string
  ): Promise<{
    id: string;
    order_number: string;
    payment_status: string;
    void_reason: string;
    voided_by_user_id: string;
    voided_at: string;
  }> {
    const payload = {
      approver_user_id: approverUserId,
      pin,
      reason,
    };

    const response = await posApiClient.post<{
      id: string;
      order_number: string;
      payment_status: string;
      void_reason: string;
      voided_by_user_id: string;
      voided_at: string;
    }>(`/pos/orders/${orderId}/void`, payload);

    if (response.data) {
      try {
        await db.orders.where('client_order_id').equals(orderId).or('id').equals(orderId).modify({
          payment_status: 'VOIDED',
          void_reason: reason,
          voided_by_user_id: approverUserId,
          voided_at: response.data.voided_at,
        });
      } catch {
        // abaikan jika index lokal belum sinkron
      }
      return response.data;
    }

    throw new Error(response.message || 'Gagal membatalkan (void) pesanan.');
  }

  public async refundOrder(
    orderId: string,
    approverUserId: string,
    pin: string,
    reason: string,
    refundAmount?: number,
    refundMethod: string = 'CASH_DRAWER'
  ): Promise<{
    id: string;
    order_number: string;
    payment_status: string;
    refund_amount: number;
    refund_reason: string;
    refund_method: string;
    refunded_in_session_id?: string;
    refunded_by_user_id: string;
    refunded_at: string;
  }> {
    const payload = {
      approver_user_id: approverUserId,
      pin,
      reason,
      refund_amount: refundAmount !== undefined ? refundAmount : undefined,
      refund_method: refundMethod,
    };

    const response = await posApiClient.post<{
      id: string;
      order_number: string;
      payment_status: string;
      refund_amount: number;
      refund_reason: string;
      refund_method: string;
      refunded_in_session_id?: string;
      refunded_by_user_id: string;
      refunded_at: string;
    }>(`/pos/orders/${orderId}/refund`, payload);

    if (response.data) {
      try {
        await db.orders.where('client_order_id').equals(orderId).or('id').equals(orderId).modify({
          payment_status: response.data.payment_status as OfflineOrder['payment_status'],
          refund_amount: response.data.refund_amount,
          refund_reason: response.data.refund_reason,
          refund_method: response.data.refund_method,
          refunded_in_session_id: response.data.refunded_in_session_id,
          refunded_by_user_id: response.data.refunded_by_user_id,
          refunded_at: response.data.refunded_at,
        });
      } catch {
        // abaikan jika index lokal belum sinkron
      }
      return response.data;
    }

    throw new Error(response.message || 'Gagal memproses refund pesanan.');
  }
}

export const posService = new PosService();
