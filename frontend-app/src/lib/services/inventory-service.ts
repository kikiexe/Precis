import { apiClient } from './api-client';
import type {
  ProductMenuItem,
  CategoryItem,
  RawMaterialItem,
  StockAdjustmentLog,
  StockAdjustmentReason,
  TimeframePeriod,
  TimeframeMetricData,
  BranchItem,
} from '../types/app';

class InventoryService {
  private getStorageKey(type: string): string {
    const wsId = apiClient.getWorkspaceId() || 'default';
    return `precis_ws_${wsId}_${type}`;
  }

  // --- BRANCHES ---
  async getBranches(): Promise<BranchItem[]> {
    const res = await apiClient.get<BranchItem[]>('/branches');
    return res.data || [];
  }

  async updateBranch(
    id: string,
    data: Partial<Pick<BranchItem, 'name' | 'lat' | 'lng' | 'radius_meters' | 'late_penalty_per_minute' | 'overtime_pay_per_hour' | 'min_overtime_threshold_minutes'>>
  ): Promise<BranchItem> {
    const res = await apiClient.put<BranchItem>(`/branches/${id}`, data);
    if (!res.data) {
      throw new Error(res.message || 'Gagal memperbarui data cabang.');
    }
    return res.data;
  }

  // --- CATEGORIES ---
  async fetchLiveCategories(): Promise<CategoryItem[]> {
    const res = await apiClient.get<CategoryItem[]>('/categories');
    return res.data || [];
  }

  async createCategory(name: string): Promise<CategoryItem> {
    const res = await apiClient.post<CategoryItem>('/categories', { name });
    if (!res.data) {
      throw new Error(res.message || 'Gagal membuat kategori.');
    }
    return res.data;
  }

  async deleteCategory(id: string): Promise<{ success: boolean; message?: string }> {
    const res = await apiClient.delete(`/categories/${id}`);
    return { success: true, message: res.message };
  }

  // --- MENU ITEMS (PRODUCTS) ---
  async fetchLiveProducts(categoryId?: string): Promise<ProductMenuItem[]> {
    const params: Record<string, string> = {};
    if (categoryId && categoryId !== 'ALL') {
      params.category_id = categoryId;
    }
    const res = await apiClient.get<ProductMenuItem[]>('/products', params);
    return res.data || [];
  }

  async createMenuItem(data: {
    name: string;
    category_id: string;
    price: number;
    description?: string;
    is_available?: boolean;
  }): Promise<ProductMenuItem> {
    const res = await apiClient.post<ProductMenuItem>('/products', data);
    if (!res.data) {
      throw new Error(res.message || 'Gagal membuat menu.');
    }
    return res.data;
  }

  async updateMenuItem(id: string, data: Partial<ProductMenuItem>): Promise<ProductMenuItem> {
    const res = await apiClient.put<ProductMenuItem>(`/products/${id}`, data);
    if (!res.data) {
      throw new Error(res.message || 'Gagal memperbarui menu.');
    }
    return res.data;
  }

  async deleteMenuItem(id: string): Promise<boolean> {
    await apiClient.delete(`/products/${id}`);
    return true;
  }

  // --- RAW MATERIALS (INVENTORY TRACKING) ---
  getRawMaterials(): RawMaterialItem[] {
    if (typeof window === 'undefined') return [];
    const raw = localStorage.getItem(this.getStorageKey('raw_materials'));
    if (raw) {
      try {
        return JSON.parse(raw);
      } catch {
        return [];
      }
    }
    return [];
  }

  createRawMaterial(data: Omit<RawMaterialItem, 'id' | 'last_adjusted_at'>): RawMaterialItem {
    const newMat: RawMaterialItem = {
      ...data,
      id: `raw-${Date.now()}`,
      last_adjusted_at: new Date().toISOString().replace('T', ' ').substring(0, 16),
    };
    const current = this.getRawMaterials();
    current.unshift(newMat);
    if (typeof window !== 'undefined') {
      localStorage.setItem(this.getStorageKey('raw_materials'), JSON.stringify(current));
    }
    return newMat;
  }

  deleteRawMaterial(id: string): void {
    let current = this.getRawMaterials();
    current = current.filter((m) => m.id !== id);
    if (typeof window !== 'undefined') {
      localStorage.setItem(this.getStorageKey('raw_materials'), JSON.stringify(current));
    }
  }

  getAdjustmentLogs(): StockAdjustmentLog[] {
    if (typeof window === 'undefined') return [];
    const raw = localStorage.getItem(this.getStorageKey('stock_adjustment_logs'));
    if (raw) {
      try {
        return JSON.parse(raw);
      } catch {
        return [];
      }
    }
    return [];
  }

  adjustStock(params: {
    material_id: string;
    new_stock?: number;
    delta_stock?: number;
    reason: StockAdjustmentReason;
    notes?: string;
    performed_by?: string;
  }): { log: StockAdjustmentLog; material: RawMaterialItem } | null {
    const materials = this.getRawMaterials();
    const item = materials.find((m) => m.id === params.material_id);
    if (!item) return null;

    const prevStock = item.current_stock;
    let computedNewStock = prevStock;
    if (params.new_stock !== undefined) {
      computedNewStock = Math.max(0, params.new_stock);
    } else if (params.delta_stock !== undefined) {
      computedNewStock = Math.max(0, prevStock + params.delta_stock);
    }

    const diff = computedNewStock - prevStock;
    item.current_stock = computedNewStock;
    item.last_adjusted_at = new Date().toISOString().replace('T', ' ').substring(0, 16);

    if (typeof window !== 'undefined') {
      localStorage.setItem(this.getStorageKey('raw_materials'), JSON.stringify(materials));
    }

    const log: StockAdjustmentLog = {
      id: `adj-${Date.now()}`,
      material_id: params.material_id,
      material_name: item.name,
      prev_stock: prevStock,
      adjusted_amount: diff,
      new_stock: computedNewStock,
      reason: params.reason,
      notes: params.notes,
      performed_by: params.performed_by || 'Owner',
      created_at: item.last_adjusted_at,
    };

    const allLogs = this.getAdjustmentLogs();
    allLogs.unshift(log);
    if (typeof window !== 'undefined') {
      localStorage.setItem(this.getStorageKey('stock_adjustment_logs'), JSON.stringify(allLogs));
    }

    return { log, material: item };
  }

  // --- SALES ANALYTICS ---
  async fetchLiveSalesAnalytics(timeframe: TimeframePeriod, branchId?: string): Promise<TimeframeMetricData> {
    const params: Record<string, string> = { period: timeframe };
    if (branchId && branchId !== 'ALL') {
      params.branch_id = branchId;
    }
    const res = await apiClient.get<TimeframeMetricData>('/admin/analytics/sales', params);
    if (!res.data) {
      throw new Error(res.message || 'Gagal memuat analitik penjualan.');
    }
    return res.data;
  }

  getTimeframeMetrics(timeframe: TimeframePeriod): TimeframeMetricData {
    const label = timeframe === 'day' ? 'Hari Ini' : timeframe === 'week' ? 'Pekan Ini' : timeframe === 'month' ? 'Bulan Ini' : 'Tahun Ini';

    return {
      period: timeframe,
      period_label: label,
      total_revenue: 0,
      total_orders: 0,
      average_order_value: 0,
      growth_percent: 0,
      growth_label: 'vs periode lalu',
      gross_sales: 0,
      total_discount: 0,
      net_revenue: 0,
      breakdown: [],
      top_products: [],
      category_breakdown: [],
      payment_methods: [],
    };
  }
}

export const inventoryService = new InventoryService();
