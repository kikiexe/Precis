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
  PosTerminalItem,
  PresignUploadResponseData,
} from '../types/app';

class InventoryService {
  private getStorageKey(type: string): string {
    const wsId = apiClient.getWorkspaceId() || 'default';
    return `precis_ws_${wsId}_${type}`;
  }

  // --- BRANCHES & POS TERMINALS ---
  async getBranches(): Promise<BranchItem[]> {
    const res = await apiClient.get<BranchItem[]>('/branches');
    return res.data || [];
  }

  async updateBranch(
    id: string,
    data: Partial<
      Pick<
        BranchItem,
        | 'name'
        | 'lat'
        | 'lng'
        | 'radius_meters'
        | 'qris_image_url'
        | 'late_penalty_per_minute'
        | 'overtime_pay_per_hour'
        | 'min_overtime_threshold_minutes'
        | 'tax_enabled'
        | 'tax_name'
        | 'tax_rate'
        | 'tax_type'
        | 'show_tax_on_receipt'
      >
    >
  ): Promise<BranchItem> {
    const res = await apiClient.put<BranchItem>(`/branches/${id}`, data);
    if (!res.data) {
      throw new Error(res.message || 'Gagal memperbarui data cabang.');
    }
    return res.data;
  }

  async uploadQrisImage(file: File): Promise<string> {
    const allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowedMimeTypes.includes(file.type)) {
      throw new Error('Format gambar QRIS harus berupa PNG, JPG, atau WebP.');
    }

    const maxSizeBytes = 2 * 1024 * 1024; // 2MB
    if (file.size > maxSizeBytes) {
      throw new Error('Ukuran berkas QRIS tidak boleh melebihi 2MB.');
    }

    const presignRes = await apiClient.post<PresignUploadResponseData>('/media/presign-upload', {
      filename: file.name,
      mime_type: file.type === 'image/png' ? 'image/webp' : file.type,
      size_bytes: file.size,
    });

    if (!presignRes.data || !presignRes.data.upload_url) {
      throw new Error(presignRes.message || 'Gagal mendapatkan izin unggah gambar QRIS.');
    }

    const { upload_url, public_url } = presignRes.data;

    const uploadRes = await fetch(upload_url, {
      method: 'PUT',
      headers: {
        'Content-Type': file.type,
      },
      body: file,
    });

    if (!uploadRes.ok) {
      throw new Error(`Gagal mengunggah gambar QRIS ke storage: HTTP ${uploadRes.status}`);
    }

    return public_url;
  }

  async createTerminal(
    branchId: string,
    terminalName?: string,
    customToken?: string
  ): Promise<PosTerminalItem> {
    const res = await apiClient.post<PosTerminalItem>(`/branches/${branchId}/terminals`, {
      terminal_name: terminalName,
      device_token: customToken || undefined,
    });
    if (!res.data) {
      throw new Error(res.message || 'Gagal membuat terminal kasir baru.');
    }
    return res.data;
  }

  async regenerateTerminalToken(
    branchId: string,
    terminalId: string,
    customToken?: string
  ): Promise<PosTerminalItem> {
    const res = await apiClient.post<PosTerminalItem>(
      `/branches/${branchId}/terminals/${terminalId}/regenerate-token`,
      {
        device_token: customToken || undefined,
      }
    );
    if (!res.data) {
      throw new Error(res.message || 'Gagal memperbarui token terminal kasir.');
    }
    return res.data;
  }

  async deleteTerminal(branchId: string, terminalId: string): Promise<void> {
    await apiClient.delete(`/branches/${branchId}/terminals/${terminalId}`);
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
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed) && parsed.length > 0) {
          return parsed;
        }
      } catch {
        // fallback
      }
    }
    const defaultMaterials: RawMaterialItem[] = [
      {
        id: 'raw-1',
        name: 'Arabica House Blend Beans',
        category_id: 'cat-1',
        category_name: 'Kopi',
        current_stock: 12.5,
        min_stock_alert: 5,
        unit: 'kg',
        last_adjusted_at: '2026-08-27 10:00',
      },
      {
        id: 'raw-2',
        name: 'Fresh Milk Pasteurisasi',
        category_id: 'cat-2',
        category_name: 'Dairy',
        current_stock: 24,
        min_stock_alert: 10,
        unit: 'liter',
        last_adjusted_at: '2026-08-27 08:30',
      },
      {
        id: 'raw-3',
        name: 'Oatmilk Barista Edition',
        category_id: 'cat-2',
        category_name: 'Dairy',
        current_stock: 8,
        min_stock_alert: 5,
        unit: 'liter',
        last_adjusted_at: '2026-08-26 18:00',
      },
      {
        id: 'raw-4',
        name: 'Syrup Vanilla Artisan',
        category_id: 'cat-3',
        category_name: 'Sirup',
        current_stock: 3.5,
        min_stock_alert: 2,
        unit: 'botol',
        last_adjusted_at: '2026-08-25 14:00',
      },
      {
        id: 'raw-5',
        name: 'Paper Cup 8oz Hot',
        category_id: 'cat-4',
        category_name: 'Packaging',
        current_stock: 250,
        min_stock_alert: 100,
        unit: 'pcs',
        last_adjusted_at: '2026-08-27 07:00',
      },
    ];
    localStorage.setItem(this.getStorageKey('raw_materials'), JSON.stringify(defaultMaterials));
    return defaultMaterials;
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
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed) && parsed.length > 0) {
          return parsed;
        }
      } catch {
        // fallback
      }
    }
    const defaultLogs: StockAdjustmentLog[] = [
      {
        id: 'adj-1',
        material_id: 'raw-2',
        material_name: 'Fresh Milk Pasteurisasi',
        prev_stock: 28,
        new_stock: 24,
        adjusted_amount: -4,
        reason: 'EXPIRED',
        notes: 'Kadaluarsa saat inspeksi chiller pagi',
        performed_by: 'Paundra (Manager)',
        created_at: '2026-08-26 09:30',
      },
      {
        id: 'adj-2',
        material_id: 'raw-1',
        material_name: 'Arabica House Blend Beans',
        prev_stock: 15,
        new_stock: 12.5,
        adjusted_amount: -2.5,
        reason: 'RESTOCK',
        notes: 'Opname rutin mingguan bar',
        performed_by: 'Ami (Head Barista)',
        created_at: '2026-08-25 21:00',
      },
    ];
    localStorage.setItem(this.getStorageKey('stock_adjustment_logs'), JSON.stringify(defaultLogs));
    return defaultLogs;
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
  async fetchLiveSalesAnalytics(
    timeframe: TimeframePeriod,
    branchId?: string
  ): Promise<TimeframeMetricData> {
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
    const label =
      timeframe === 'day'
        ? 'Hari Ini'
        : timeframe === 'week'
          ? 'Pekan Ini'
          : timeframe === 'month'
            ? 'Bulan Ini'
            : timeframe === 'year'
              ? 'Tahun Ini'
              : 'Sepanjang Waktu';

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
