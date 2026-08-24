import { apiClient } from './api-client';
import type {
  ProductMenuItem,
  CategoryItem,
  RawMaterialItem,
  StockAdjustmentLog,
  StockAdjustmentReason,
  TimeframePeriod,
  TimeframeMetricData,
} from '../types/app';

const DEMO_WORKSPACE_ID = 'a2943977-8f47-47fc-88ce-7c7fec43aea3';

const DEMO_CATEGORIES: CategoryItem[] = [
  { id: 'cat-coffee', name: 'Coffee & Espresso', type: 'MENU', item_count: 5 },
  { id: 'cat-non-coffee', name: 'Non-Coffee & Tea', type: 'MENU', item_count: 4 },
  { id: 'cat-food', name: 'Makanan & Snack', type: 'MENU', item_count: 3 },
  { id: 'cat-dairy', name: 'Dairy & Milk', type: 'RAW_MATERIAL', item_count: 3 },
  { id: 'cat-beans', name: 'Biji Kopi & Powder', type: 'RAW_MATERIAL', item_count: 4 },
  { id: 'cat-syrup', name: 'Sirup & Flavor', type: 'RAW_MATERIAL', item_count: 3 },
  { id: 'cat-packaging', name: 'Kemasan & Cup', type: 'RAW_MATERIAL', item_count: 3 },
];

const DEMO_MENU_ITEMS: ProductMenuItem[] = [
  {
    id: 'prod-01',
    name: 'Amore Signature Kopi Susu',
    category_id: 'cat-coffee',
    category_name: 'Coffee & Espresso',
    price: 22000,
    description: 'Espresso double shot, susu segar, dan gula aren organik khas Amore.',
    is_available: true,
  },
  {
    id: 'prod-02',
    name: 'Caffe Latte Art 250ml',
    category_id: 'cat-coffee',
    category_name: 'Coffee & Espresso',
    price: 28000,
    description: 'Espresso lembut dengan microfoam susu segar dan latte art.',
    is_available: true,
  },
  {
    id: 'prod-03',
    name: 'Caramel Macchiato Iced',
    category_id: 'cat-coffee',
    category_name: 'Coffee & Espresso',
    price: 32000,
    description: 'Espresso layer, sirup vanilla, susu dingin, dan drizzle saus karamel.',
    is_available: true,
  },
  {
    id: 'prod-04',
    name: 'Americano Sleman Reserve',
    category_id: 'cat-coffee',
    category_name: 'Coffee & Espresso',
    price: 20000,
    description: 'Ekstraksi espresso murni biji Arabika Merapi dan air mineral dingin.',
    is_available: true,
  },
  {
    id: 'prod-05',
    name: 'Matcha Uji Artisan Latte',
    category_id: 'cat-non-coffee',
    category_name: 'Non-Coffee & Tea',
    price: 30000,
    description: 'Pure ceremonial grade green tea powder import dengan fresh milk.',
    is_available: true,
  },
  {
    id: 'prod-06',
    name: 'Earl Grey Peach Iced Tea',
    category_id: 'cat-non-coffee',
    category_name: 'Non-Coffee & Tea',
    price: 24000,
    description: 'Teh bergamot diseduh dingin dengan irisan buah persik asli.',
    is_available: false,
  },
  {
    id: 'prod-07',
    name: 'Butter Croissant Artisan',
    category_id: 'cat-food',
    category_name: 'Makanan & Snack',
    price: 25000,
    description: 'Pastry renyah dengan 100% Elle & Vire French butter.',
    is_available: true,
  },
  {
    id: 'prod-08',
    name: 'Smoked Beef Panini Melt',
    category_id: 'cat-food',
    category_name: 'Makanan & Snack',
    price: 38000,
    description: 'Roti ciabatta panggang dengan daging asap dan keju mozarella leleh.',
    is_available: true,
  },
];

const DEMO_RAW_MATERIALS: RawMaterialItem[] = [
  {
    id: 'raw-01',
    name: 'Fresh Milk Diamond Pasteurisasi 1L',
    category_id: 'cat-dairy',
    category_name: 'Dairy & Milk',
    current_stock: 18,
    min_stock_alert: 10,
    unit: 'liter',
    last_adjusted_at: '2026-08-24 07:30',
  },
  {
    id: 'raw-02',
    name: 'Oatside Barista Oat Milk 1L',
    category_id: 'cat-dairy',
    category_name: 'Dairy & Milk',
    current_stock: 6,
    min_stock_alert: 8,
    unit: 'liter',
    last_adjusted_at: '2026-08-23 18:00',
  },
  {
    id: 'raw-03',
    name: 'Biji Kopi House Blend Amore (1kg)',
    category_id: 'cat-beans',
    category_name: 'Biji Kopi & Powder',
    current_stock: 12,
    min_stock_alert: 5,
    unit: 'kg',
    last_adjusted_at: '2026-08-24 06:45',
  },
  {
    id: 'raw-04',
    name: 'Bubuk Matcha Ceremonial Uji 500g',
    category_id: 'cat-beans',
    category_name: 'Biji Kopi & Powder',
    current_stock: 3,
    min_stock_alert: 4,
    unit: 'pack',
    last_adjusted_at: '2026-08-22 14:00',
  },
  {
    id: 'raw-05',
    name: 'Sirup Monin Caramel 700ml',
    category_id: 'cat-syrup',
    category_name: 'Sirup & Flavor',
    current_stock: 2,
    min_stock_alert: 3,
    unit: 'botol',
    last_adjusted_at: '2026-08-21 16:30',
  },
  {
    id: 'raw-06',
    name: 'Gula Aren Cair Organik 1L',
    category_id: 'cat-syrup',
    category_name: 'Sirup & Flavor',
    current_stock: 14,
    min_stock_alert: 6,
    unit: 'liter',
    last_adjusted_at: '2026-08-24 07:15',
  },
];

class InventoryService {
  private getStorageKey(type: string): string {
    const wsId = apiClient.getWorkspaceId() || 'default';
    return `precis_ws_${wsId}_${type}`;
  }

  private isDemoWorkspace(): boolean {
    const wsId = apiClient.getWorkspaceId();
    return wsId === DEMO_WORKSPACE_ID;
  }

  getCategories(): CategoryItem[] {
    if (typeof window === 'undefined') return [];
    const raw = localStorage.getItem(this.getStorageKey('categories'));
    if (raw) {
      try {
        return JSON.parse(raw);
      } catch {
        return [];
      }
    }

    if (this.isDemoWorkspace()) {
      return DEMO_CATEGORIES;
    }

    // Newly registered tenants start with completely empty categories
    return [];
  }

  saveCategory(item: { name: string; type: 'MENU' | 'RAW_MATERIAL' }): CategoryItem {
    const categories = this.getCategories();
    const newCat: CategoryItem = {
      id: `cat-${Date.now()}`,
      name: item.name,
      type: item.type,
      item_count: 0,
    };
    categories.push(newCat);
    if (typeof window !== 'undefined') {
      localStorage.setItem(this.getStorageKey('categories'), JSON.stringify(categories));
    }
    return newCat;
  }

  deleteCategory(id: string): { success: boolean; message?: string } {
    const menuItems = this.getMenuItems();
    const rawMaterials = this.getRawMaterials();
    const isUsedInMenu = menuItems.some((m) => m.category_id === id);
    const isUsedInRaw = rawMaterials.some((r) => r.category_id === id);

    if (isUsedInMenu || isUsedInRaw) {
      return {
        success: false,
        message: 'Kategori tidak dapat dihapus karena masih digunakan oleh produk menu atau bahan baku.',
      };
    }

    const categories = this.getCategories().filter((c) => c.id !== id);
    if (typeof window !== 'undefined') {
      localStorage.setItem(this.getStorageKey('categories'), JSON.stringify(categories));
    }
    return { success: true };
  }

  getMenuItems(): ProductMenuItem[] {
    if (typeof window === 'undefined') return [];
    const raw = localStorage.getItem(this.getStorageKey('menu_items'));
    if (raw) {
      try {
        return JSON.parse(raw);
      } catch {
        return [];
      }
    }

    if (this.isDemoWorkspace()) {
      return DEMO_MENU_ITEMS;
    }

    // New workspace starts with empty menu items
    return [];
  }

  saveMenuItem(item: Partial<ProductMenuItem> & { name: string; price: number; category_id: string }): ProductMenuItem {
    const items = this.getMenuItems();
    const categories = this.getCategories();
    const cat = categories.find((c) => c.id === item.category_id);
    const category_name = cat ? cat.name : 'Umum';

    if (item.id) {
      const idx = items.findIndex((i) => i.id === item.id);
      if (idx !== -1) {
        items[idx] = { ...items[idx], ...item, category_name } as ProductMenuItem;
        if (typeof window !== 'undefined') {
          localStorage.setItem(this.getStorageKey('menu_items'), JSON.stringify(items));
        }
        return items[idx];
      }
    }

    const newItem: ProductMenuItem = {
      id: `prod-${Date.now()}`,
      name: item.name,
      category_id: item.category_id,
      category_name,
      price: item.price,
      description: item.description || '',
      is_available: item.is_available ?? true,
    };
    items.unshift(newItem);
    if (typeof window !== 'undefined') {
      localStorage.setItem(this.getStorageKey('menu_items'), JSON.stringify(items));
    }
    return newItem;
  }

  toggleMenuItemAvailability(id: string): ProductMenuItem | null {
    const items = this.getMenuItems();
    const index = items.findIndex((i) => i.id === id);
    if (index === -1) return null;
    items[index].is_available = !items[index].is_available;
    if (typeof window !== 'undefined') {
      localStorage.setItem(this.getStorageKey('menu_items'), JSON.stringify(items));
    }
    return items[index];
  }

  deleteMenuItem(id: string): void {
    const items = this.getMenuItems().filter((i) => i.id !== id);
    if (typeof window !== 'undefined') {
      localStorage.setItem(this.getStorageKey('menu_items'), JSON.stringify(items));
    }
  }

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

    if (this.isDemoWorkspace()) {
      return DEMO_RAW_MATERIALS;
    }

    // New workspace starts with empty raw materials
    return [];
  }

  saveRawMaterial(
    item: Partial<RawMaterialItem> & {
      name: string;
      category_id: string;
      current_stock: number;
      min_stock_alert: number;
      unit: any;
    }
  ): RawMaterialItem {
    const materials = this.getRawMaterials();
    const categories = this.getCategories();
    const cat = categories.find((c) => c.id === item.category_id);
    const category_name = cat ? cat.name : 'Bahan Baku';

    if (item.id) {
      const idx = materials.findIndex((m) => m.id === item.id);
      if (idx !== -1) {
        materials[idx] = {
          ...materials[idx],
          ...item,
          category_name,
          last_adjusted_at: new Date().toISOString().replace('T', ' ').substring(0, 16),
        } as RawMaterialItem;
        if (typeof window !== 'undefined') {
          localStorage.setItem(this.getStorageKey('raw_materials'), JSON.stringify(materials));
        }
        return materials[idx];
      }
    }

    const newMaterial: RawMaterialItem = {
      id: `raw-${Date.now()}`,
      name: item.name,
      category_id: item.category_id,
      category_name,
      current_stock: item.current_stock,
      min_stock_alert: item.min_stock_alert,
      unit: item.unit,
      last_adjusted_at: new Date().toISOString().replace('T', ' ').substring(0, 16),
    };
    materials.unshift(newMaterial);
    if (typeof window !== 'undefined') {
      localStorage.setItem(this.getStorageKey('raw_materials'), JSON.stringify(materials));
    }
    return newMaterial;
  }

  deleteRawMaterial(id: string): void {
    const materials = this.getRawMaterials().filter((m) => m.id !== id);
    if (typeof window !== 'undefined') {
      localStorage.setItem(this.getStorageKey('raw_materials'), JSON.stringify(materials));
    }
  }

  getAdjustmentLogs(): StockAdjustmentLog[] {
    if (typeof window === 'undefined') return [];
    const raw = localStorage.getItem(this.getStorageKey('adjustment_logs'));
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
    performed_by: string;
  }): { material: RawMaterialItem; log: StockAdjustmentLog } {
    const materials = this.getRawMaterials();
    const material = materials.find((m) => m.id === params.material_id);
    if (!material) throw new Error('Bahan baku tidak ditemukan');

    const prevStock = material.current_stock;
    let newStock = prevStock;
    let adjustedAmount = 0;

    if (params.new_stock !== undefined) {
      newStock = Math.max(0, params.new_stock);
      adjustedAmount = newStock - prevStock;
    } else if (params.delta_stock !== undefined) {
      adjustedAmount = params.delta_stock;
      newStock = Math.max(0, prevStock + adjustedAmount);
    }

    material.current_stock = newStock;
    material.last_adjusted_at = new Date().toISOString().replace('T', ' ').substring(0, 16);

    const log: StockAdjustmentLog = {
      id: `log-${Date.now()}`,
      material_id: params.material_id,
      material_name: material.name,
      prev_stock: prevStock,
      adjusted_amount: adjustedAmount,
      new_stock: newStock,
      reason: params.reason,
      notes: params.notes || '',
      performed_by: params.performed_by,
      created_at: material.last_adjusted_at,
    };

    const logs = this.getAdjustmentLogs();
    logs.unshift(log);

    if (typeof window !== 'undefined') {
      localStorage.setItem(this.getStorageKey('raw_materials'), JSON.stringify(materials));
      localStorage.setItem(this.getStorageKey('adjustment_logs'), JSON.stringify(logs));
    }

    return { material, log };
  }

  getTimeframeMetrics(timeframe: TimeframePeriod): TimeframeMetricData {
    // If this is the demo seed workspace, return the demo numbers
    if (this.isDemoWorkspace()) {
      switch (timeframe) {
        case 'day':
          return {
            period: 'day',
            period_label: 'Hari Ini',
            total_revenue: 3840000,
            total_orders: 142,
            average_order_value: 27042,
            growth_percent: 14.2,
            growth_label: 'vs kemarin',
            gross_sales: 4120000,
            total_discount: 280000,
            net_revenue: 3840000,
            breakdown: [
              { id: 'h-08', label: '08:00', subLabel: 'Pagi', revenue: 320000, orders_count: 14, average_ticket: 22857 },
              { id: 'h-10', label: '10:00', subLabel: 'Siang', revenue: 540000, orders_count: 22, average_ticket: 24545 },
              { id: 'h-12', label: '12:00', subLabel: 'Makan Siang', revenue: 880000, orders_count: 31, average_ticket: 28387 },
              { id: 'h-14', label: '14:00', subLabel: 'Sore Awal', revenue: 490000, orders_count: 18, average_ticket: 27222 },
              { id: 'h-16', label: '16:00', subLabel: 'Coffee Break', revenue: 620000, orders_count: 23, average_ticket: 26956 },
              { id: 'h-18', label: '18:00', subLabel: 'Peak Dinner', revenue: 710000, orders_count: 24, average_ticket: 29583 },
              { id: 'h-20', label: '20:00', subLabel: 'Malam', revenue: 280000, orders_count: 10, average_ticket: 28000 },
            ],
            top_products: [
              { name: 'Amore Signature Kopi Susu', quantity: 68, total_amount: 1496000, share_percent: 39 },
              { name: 'Matcha Uji Artisan Latte', quantity: 34, total_amount: 1020000, share_percent: 26 },
              { name: 'Caramel Macchiato Iced', quantity: 22, total_amount: 704000, share_percent: 18 },
              { name: 'Butter Croissant Artisan', quantity: 18, total_amount: 450000, share_percent: 12 },
              { name: 'Americano Sleman Reserve', quantity: 12, total_amount: 240000, share_percent: 5 },
            ],
            category_breakdown: [
              { name: 'Coffee & Espresso', total_amount: 2440000, share_percent: 63.5 },
              { name: 'Non-Coffee & Tea', total_amount: 950000, share_percent: 24.7 },
              { name: 'Makanan & Pastry', total_amount: 450000, share_percent: 11.8 },
            ],
            payment_methods: [
              { method: 'QRIS Dinamis (GoPay/Shopee/OVO)', count: 84, amount: 2227200, percent: 58 },
              { method: 'Tunai / Cash Register', count: 40, amount: 1075200, percent: 28 },
              { method: 'Kartu Debit EDC (BCA/Mandiri)', count: 18, amount: 537600, percent: 14 },
            ],
          };

        case 'week':
        default:
          return {
            period: 'week',
            period_label: 'Pekan Ini',
            total_revenue: 26240000,
            total_orders: 946,
            average_order_value: 27737,
            growth_percent: 8.6,
            growth_label: 'vs pekan lalu',
            gross_sales: 27890000,
            total_discount: 1650000,
            net_revenue: 26240000,
            breakdown: [
              { id: 'w-sen', label: 'Senin', subLabel: '18 Agu', revenue: 2950000, orders_count: 108, average_ticket: 27314 },
              { id: 'w-sel', label: 'Selasa', subLabel: '19 Agu', revenue: 3120000, orders_count: 114, average_ticket: 27368 },
              { id: 'w-rab', label: 'Rabu', subLabel: '20 Agu', revenue: 2880000, orders_count: 102, average_ticket: 28235 },
              { id: 'w-kam', label: 'Kamis', subLabel: '21 Agu', revenue: 3450000, orders_count: 126, average_ticket: 27380 },
              { id: 'w-jum', label: 'Jumat', subLabel: '22 Agu', revenue: 4620000, orders_count: 168, average_ticket: 27500 },
              { id: 'w-sab', label: 'Sabtu', subLabel: '23 Agu', revenue: 5380000, orders_count: 186, average_ticket: 28924 },
              { id: 'w-min', label: 'Minggu', subLabel: '24 Agu', revenue: 3840000, orders_count: 142, average_ticket: 27042 },
            ],
            top_products: [
              { name: 'Amore Signature Kopi Susu', quantity: 462, total_amount: 10164000, share_percent: 38 },
              { name: 'Matcha Uji Artisan Latte', quantity: 215, total_amount: 6450000, share_percent: 24 },
              { name: 'Caramel Macchiato Iced', quantity: 158, total_amount: 5056000, share_percent: 19 },
              { name: 'Butter Croissant Artisan', quantity: 122, total_amount: 3050000, share_percent: 12 },
              { name: 'Americano Sleman Reserve', quantity: 76, total_amount: 1520000, share_percent: 7 },
            ],
            category_breakdown: [
              { name: 'Coffee & Espresso', total_amount: 16740000, share_percent: 63.8 },
              { name: 'Non-Coffee & Tea', total_amount: 6450000, share_percent: 24.6 },
              { name: 'Makanan & Pastry', total_amount: 3050000, share_percent: 11.6 },
            ],
            payment_methods: [
              { method: 'QRIS Dinamis (GoPay/Shopee/OVO)', count: 548, amount: 15219200, percent: 58 },
              { method: 'Tunai / Cash Register', count: 265, amount: 7347200, percent: 28 },
              { method: 'Kartu Debit EDC (BCA/Mandiri)', count: 133, amount: 3673600, percent: 14 },
            ],
          };
      }
    }

    // For any newly created tenant workspace, return CLEAN 0 state
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
