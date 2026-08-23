import Dexie, { type Table } from 'dexie';
import type { Product, Category, OfflineOrder, PosSession, CashierUser } from '../types/pos';

export class PrecisPosDatabase extends Dexie {
  products!: Table<Product, string>;
  categories!: Table<Category, string>;
  orders!: Table<OfflineOrder, string>;
  sessions!: Table<PosSession, string>;
  cashiers!: Table<CashierUser, string>;

  constructor() {
    super('PrecisPosDb');
    this.version(1).stores({
      products: 'id, category_id, name, is_active',
      categories: 'id, name',
      orders: 'client_order_id, workspace_id, branch_id, pos_session_id, sync_status, created_at',
      sessions: 'id, workspace_id, branch_id, status, opened_at',
      cashiers: 'id, name, pin',
    });
  }
}

export const db = new PrecisPosDatabase();
