export interface PosTerminalInfo {
  terminal_id: string;
  terminal_name: string;
  workspace_id: string;
  branch_id: string;
  branch_name?: string;
  qris_image_url?: string | null;
  cashiers?: CashierUser[];
}

export interface Product {
  id: string;
  category_id: string;
  name: string;
  base_price: number;
  image_url?: string;
  description?: string;
  is_active: boolean;
  stock?: number;
  addon_category_ids?: string[];
}

export interface Addon {
  id: string;
  addon_category_id: string;
  name: string;
  price: number;
  is_active: boolean;
}

export interface AddonCategory {
  id: string;
  name: string;
  selection_type: 'SINGLE' | 'MULTIPLE';
  is_required: boolean;
  min_selection: number;
  max_selection: number;
  product_ids?: string[];
  addons: Addon[];
}

export interface SelectedModifier {
  addon_id: string;
  addon_category_id?: string;
  name: string;
  price: number;
}

export interface Category {
  id: string;
  name: string;
  icon?: string;
}

export interface CatalogCategoryItem {
  id: string;
  name: string;
  products: Array<{
    id: string;
    category_id: string;
    name: string;
    base_price: number;
    is_active: boolean;
    addon_category_ids?: string[];
  }>;
}

export interface CartItem {
  product: Product;
  quantity: number;
  unit_price: number;
  notes: string;
  modifiers?: SelectedModifier[];
}

export type PosPage =
  'penjualan' | 'transaksi' | 'shift' | 'settlement' | 'menu' | 'inventori' | 'profil';

export type PaymentMethod = 'CASH' | 'QRIS' | 'EDC' | 'TRANSFER';
export type OrderType = 'DINE_IN' | 'TAKE_AWAY' | 'DELIVERY';

export interface PosSession {
  id: string;
  workspace_id: string;
  branch_id: string;
  opened_by_user_id: string;
  cashier_name?: string;
  opening_cash: number;
  closing_cash_actual?: number;
  closing_cash_expected?: number;
  discrepancy_amount: number;
  notes?: string;
  status: 'OPEN' | 'CLOSED';
  opened_at: string;
  closed_at?: string;
  total_cash_sales: number;
  total_qris_sales: number;
  total_edc_sales?: number;
  total_transfer_sales: number;
  order_count: number;
  is_settled?: boolean;
  settled_at?: string;
  settled_by?: string;
  settlement_notes?: string;
}

export interface OpenSessionResponse {
  id: string;
  branch_id: string;
  opened_by_user_id: string;
  opening_cash: number;
  status: 'OPEN' | 'CLOSED';
  opened_at: string;
}

export interface CloseSessionResponse {
  id: string;
  opening_cash: number;
  closing_cash_expected: number;
  closing_cash_actual: number;
  discrepancy_amount: number;
  status: 'CLOSED';
  closed_at: string;
}

export interface MasterUnlockResult {
  unlocked_at: string;
  terminal_id: string;
  branch_id: string;
  workspace_id: string;
}

export interface OfflineOrder {
  client_order_id: string; // UUIDv4
  order_number: string;
  workspace_id: string;
  branch_id: string;
  pos_session_id: string;
  cashier_user_id: string;
  cashier_name: string;
  order_type: OrderType;
  customer_name?: string;
  table_number?: string;
  total_amount: number;
  discount_amount: number;
  final_amount: number;
  payment_method: PaymentMethod;
  cash_tendered?: number;
  change_amount?: number;
  items: Array<{
    product_id: string;
    product_name: string;
    quantity: number;
    unit_price: number;
    subtotal: number;
    notes?: string;
    modifiers?: SelectedModifier[];
  }>;
  created_at: string;
  sync_status: 'PENDING' | 'SYNCED' | 'FAILED';
}

export interface CashierUser {
  id: string;
  name: string;
  role: 'STAFF' | 'ADMIN' | 'OWNER' | string;
  pin: string; // 4-digit PIN
  avatar_url?: string;
}

export interface OpenBill {
  id: string;
  order_number: string;
  order_type: OrderType;
  customer_name: string;
  items: CartItem[];
  discount_percent: number;
  discount_nominal: number;
  subtotal: number;
  final_total: number;
  saved_at: string;
  notes?: string;
}

export type StockAdjustmentReason =
  'STOCK_TAKE' | 'RESTOCK' | 'WASTE' | 'EXPIRED' | 'DAMAGED' | 'OTHER';

export interface ProductRecipeItem {
  raw_material_id: string;
  raw_material_name: string;
  quantity: number;
  unit: string;
}

export interface RawMaterial {
  id: string;
  name: string;
  category_id: string;
  category_name?: string;
  stock_previous_day?: number;
  stock_in_today?: number;
  stock_used_today?: number;
  current_stock: number;
  min_stock_alert: number;
  unit: string;
  cost_per_unit?: number;
  last_adjusted_at?: string;
}

export interface StockAdjustmentLog {
  id: string;
  raw_material_id: string;
  raw_material_name: string;
  previous_stock: number;
  new_stock: number;
  variance: number;
  reason: StockAdjustmentReason;
  notes?: string;
  adjusted_by: string;
  created_at: string;
}
