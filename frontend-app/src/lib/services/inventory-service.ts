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
  // cabang dan terminal pos
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

  // kategori
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

  // menu produk
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

  // bahan baku
  async getRawMaterials(): Promise<RawMaterialItem[]> {
    const res = await apiClient.get<RawMaterialItem[]>('/raw-materials');
    return res.data || [];
  }

  async createRawMaterial(data: Omit<RawMaterialItem, 'id' | 'last_adjusted_at'>): Promise<RawMaterialItem> {
    const res = await apiClient.post<RawMaterialItem>('/raw-materials', data);
    if (!res.data) {
      throw new Error(res.message || 'Gagal membuat bahan baku baru.');
    }
    return res.data;
  }

  async deleteRawMaterial(id: string): Promise<void> {
    await apiClient.delete(`/raw-materials/${id}`);
  }

  async getAdjustmentLogs(): Promise<StockAdjustmentLog[]> {
    const res = await apiClient.get<StockAdjustmentLog[]>('/inventory/adjustments');
    return res.data || [];
  }

  async adjustStock(params: {
    material_id: string;
    new_stock?: number;
    delta_stock?: number;
    reason: StockAdjustmentReason;
    notes?: string;
    performed_by?: string;
  }): Promise<{ log: StockAdjustmentLog; material: RawMaterialItem } | null> {
    const res = await apiClient.post<{ log: StockAdjustmentLog; material: RawMaterialItem }>(
      '/inventory/adjustments',
      params
    );
    return res.data || null;
  }

  // analitik penjualan
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
