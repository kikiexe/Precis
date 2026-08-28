<script lang="ts">
  import { Package, Layers, Calendar, History } from 'lucide-svelte';
  import type { Product, Category, RawMaterial, StockAdjustmentReason, StockAdjustmentLog } from '../../../types/pos';
  import PosMenuTab from './catalog/PosMenuTab.svelte';
  import PosRawMaterialTab from './catalog/PosRawMaterialTab.svelte';
  import PosDailyReconciliationTab from './catalog/PosDailyReconciliationTab.svelte';
  import PosStockLogTab from './catalog/PosStockLogTab.svelte';
  import PosAddMenuModal from './catalog/modals/PosAddMenuModal.svelte';
  import PosAddMaterialModal from './catalog/modals/PosAddMaterialModal.svelte';
  import PosOpnameModal from './catalog/modals/PosOpnameModal.svelte';

  interface Props {
    products: Product[];
    categories: Category[];
    onToggleProductActive: (productId: string) => void;
    onAddNewProduct: (product: Product) => void;
  }

  let { products = [], categories = [], onToggleProductActive, onAddNewProduct }: Props = $props();

  let activeSubTab = $state<'bahan' | 'rekap' | 'menu' | 'logs'>('bahan');

  // Raw Materials in POS state (with Flux-inspired daily tracking)
  let rawMaterials = $state<Array<RawMaterial & { stock_previous_day?: number }>>([
    {
      id: 'raw-pos-1',
      name: 'Fresh Milk Diamond 1L',
      category_id: 'cat-dairy',
      category_name: 'Dairy',
      stock_previous_day: 24,
      stock_in_today: 0,
      stock_used_today: 6,
      current_stock: 18,
      min_stock_alert: 10,
      unit: 'liter',
      last_adjusted_at: '2026-08-28 07:30',
    },
    {
      id: 'raw-pos-2',
      name: 'Oatside Barista Oat Milk 1L',
      category_id: 'cat-dairy',
      category_name: 'Dairy',
      stock_previous_day: 12,
      stock_in_today: 0,
      stock_used_today: 6,
      current_stock: 6,
      min_stock_alert: 8,
      unit: 'liter',
      last_adjusted_at: '2026-08-28 08:00',
    },
    {
      id: 'raw-pos-3',
      name: 'Biji Kopi House Blend (1kg)',
      category_id: 'cat-beans',
      category_name: 'Beans & Powder',
      stock_previous_day: 15,
      stock_in_today: 0,
      stock_used_today: 3,
      current_stock: 12,
      min_stock_alert: 5,
      unit: 'kg',
      last_adjusted_at: '2026-08-28 06:45',
    },
    {
      id: 'raw-pos-4',
      name: 'Sirup Monin Caramel 700ml',
      category_id: 'cat-syrup',
      category_name: 'Sirup',
      stock_previous_day: 3,
      stock_in_today: 0,
      stock_used_today: 1,
      current_stock: 2,
      min_stock_alert: 3,
      unit: 'botol',
      last_adjusted_at: '2026-08-28 09:30',
    },
    {
      id: 'raw-pos-5',
      name: 'Paper Cup Hot 8oz (50 pcs)',
      category_id: 'cat-packaging',
      category_name: 'Packaging',
      stock_previous_day: 20,
      stock_in_today: 0,
      stock_used_today: 5,
      current_stock: 15,
      min_stock_alert: 8,
      unit: 'pack',
      last_adjusted_at: '2026-08-28 10:00',
    },
  ]);

  // Stock Adjustment Audit Trail (Flux-like Entity)
  let stockLogs = $state<StockAdjustmentLog[]>([
    {
      id: 'log-pos-1',
      raw_material_id: 'raw-pos-1',
      raw_material_name: 'Fresh Milk Diamond 1L',
      previous_stock: 24,
      new_stock: 18,
      variance: -6,
      reason: 'STOCK_TAKE',
      notes: 'Opname rutin pergantian shift pagi',
      adjusted_by: 'Budi (Barista)',
      created_at: '2026-08-28 07:30',
    },
    {
      id: 'log-pos-2',
      raw_material_id: 'raw-pos-2',
      raw_material_name: 'Oatside Barista Oat Milk 1L',
      previous_stock: 12,
      new_stock: 6,
      variance: -6,
      reason: 'STOCK_TAKE',
      notes: 'Pengecekan chiller susu bar',
      adjusted_by: 'Budi (Barista)',
      created_at: '2026-08-28 08:00',
    },
    {
      id: 'log-pos-3',
      raw_material_id: 'raw-pos-3',
      raw_material_name: 'Biji Kopi House Blend (1kg)',
      previous_stock: 10,
      new_stock: 15,
      variance: 5,
      reason: 'RESTOCK',
      notes: 'Penerimaan 5 pack biji kopi dari roastery',
      adjusted_by: 'Paundra (Manager)',
      created_at: '2026-08-27 15:00',
    },
  ]);

  // Modals state
  let isAddMenuModalOpen = $state(false);
  let isAddMaterialModalOpen = $state(false);
  let editingMaterial = $state<(RawMaterial & { stock_previous_day?: number }) | null>(null);
  let adjustingMaterial = $state<(RawMaterial & { stock_previous_day?: number }) | null>(null);

  const rawMaterialCategories = [
    { id: 'cat-dairy', name: 'Dairy & Milk' },
    { id: 'cat-beans', name: 'Coffee Beans & Powder' },
    { id: 'cat-syrup', name: 'Syrup & Flavor' },
    { id: 'cat-packaging', name: 'Packaging & Cups' },
    { id: 'cat-other', name: 'Lainnya' },
  ];

  function handleSaveMaterial(materialData: {
    name: string;
    category_id: string;
    category_name: string;
    current_stock: number;
    min_stock_alert: number;
    unit: string;
  }) {
    if (editingMaterial) {
      rawMaterials = rawMaterials.map((m) => {
        if (m.id === editingMaterial?.id) {
          return {
            ...m,
            ...materialData,
          };
        }
        return m;
      });
    } else {
      const newMat: RawMaterial & { stock_previous_day?: number } = {
        id: `raw-pos-${Date.now()}`,
        ...materialData,
        stock_previous_day: materialData.current_stock,
        stock_in_today: 0,
        stock_used_today: 0,
        last_adjusted_at: new Date().toISOString().replace('T', ' ').substring(0, 16),
      };
      rawMaterials = [newMat, ...rawMaterials];
    }
  }

  function handleConfirmOpname(
    materialId: string,
    physicalCount: number,
    reason: StockAdjustmentReason,
    notes: string
  ) {
    const targetMat = rawMaterials.find((m) => m.id === materialId);
    if (!targetMat) return;

    const prevStock = targetMat.current_stock;
    const newStock = Number(physicalCount);
    const variance = newStock - prevStock;
    const nowStr = new Date().toISOString().replace('T', ' ').substring(0, 16);

    // Update raw materials
    rawMaterials = rawMaterials.map((m) => {
      if (m.id === materialId) {
        return {
          ...m,
          stock_previous_day: m.current_stock,
          current_stock: newStock,
          last_adjusted_at: nowStr,
        };
      }
      return m;
    });

    // Record audit log
    const newLog: StockAdjustmentLog = {
      id: `log-pos-${Date.now()}`,
      raw_material_id: materialId,
      raw_material_name: targetMat.name,
      previous_stock: prevStock,
      new_stock: newStock,
      variance,
      reason,
      notes: notes.trim() || undefined,
      adjusted_by: 'Kasir Bertugas',
      created_at: nowStr,
    };

    stockLogs = [newLog, ...stockLogs];
  }

  function handleDeleteMaterial(id: string) {
    if (confirm('Hapus bahan baku ini dari bar POS?')) {
      rawMaterials = rawMaterials.filter((m) => m.id !== id);
    }
  }
</script>

<div class="flex-1 flex flex-col h-full bg-[#f4f6f9] overflow-y-auto p-4 sm:p-6 lg:p-8 font-sans">
  <div class="w-full max-w-7xl mx-auto space-y-5 pb-8">
    <!-- Header Sub-Tabs (Excel / Enterprise POS Architecture) -->
    <div class="flex items-center justify-between gap-4 border-b border-zinc-200 pb-3 overflow-x-auto no-scrollbar">
      <div class="flex items-center gap-2">
        <button
          type="button"
          onclick={() => (activeSubTab = 'bahan')}
          class={`px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all cursor-pointer border whitespace-nowrap ${
            activeSubTab === 'bahan'
              ? 'bg-zinc-900 text-white border-zinc-900 shadow-2xs'
              : 'bg-white border-zinc-200 text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100/70'
          }`}
        >
          <Layers class="w-4 h-4" />
          <span>Stok Bahan Baku ({rawMaterials.length})</span>
        </button>

        <button
          type="button"
          onclick={() => (activeSubTab = 'rekap')}
          class={`px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all cursor-pointer border whitespace-nowrap ${
            activeSubTab === 'rekap'
              ? 'bg-zinc-900 text-white border-zinc-900 shadow-2xs'
              : 'bg-white border-zinc-200 text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100/70'
          }`}
        >
          <Calendar class="w-4 h-4" />
          <span>Rekap Harian &amp; Mutasi</span>
        </button>

        <button
          type="button"
          onclick={() => (activeSubTab = 'menu')}
          class={`px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all cursor-pointer border whitespace-nowrap ${
            activeSubTab === 'menu'
              ? 'bg-zinc-900 text-white border-zinc-900 shadow-2xs'
              : 'bg-white border-zinc-200 text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100/70'
          }`}
        >
          <Package class="w-4 h-4" />
          <span>Menu Jualan POS ({products.length})</span>
        </button>

        <button
          type="button"
          onclick={() => (activeSubTab = 'logs')}
          class={`px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all cursor-pointer border whitespace-nowrap ${
            activeSubTab === 'logs'
              ? 'bg-zinc-900 text-white border-zinc-900 shadow-2xs'
              : 'bg-white border-zinc-200 text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100/70'
          }`}
        >
          <History class="w-4 h-4" />
          <span>Log Audit Opname ({stockLogs.length})</span>
        </button>
      </div>
    </div>

    <!-- Active Tab Content -->
    {#if activeSubTab === 'bahan'}
      <PosRawMaterialTab
        {rawMaterials}
        onOpenAddMaterial={() => {
          editingMaterial = null;
          isAddMaterialModalOpen = true;
        }}
        onOpenEditMaterial={(mat) => {
          editingMaterial = mat;
          isAddMaterialModalOpen = true;
        }}
        onOpenOpname={(mat) => (adjustingMaterial = mat)}
        onDeleteMaterial={handleDeleteMaterial}
      />
    {:else if activeSubTab === 'rekap'}
      <PosDailyReconciliationTab
        {rawMaterials}
        onOpenOpname={(mat) => (adjustingMaterial = mat)}
      />
    {:else if activeSubTab === 'menu'}
      <PosMenuTab
        {products}
        {categories}
        {onToggleProductActive}
        onOpenAddModal={() => (isAddMenuModalOpen = true)}
      />
    {:else if activeSubTab === 'logs'}
      <PosStockLogTab logs={stockLogs} />
    {/if}
  </div>
</div>

<PosAddMenuModal
  isOpen={isAddMenuModalOpen}
  {categories}
  onClose={() => (isAddMenuModalOpen = false)}
  onSave={onAddNewProduct}
/>

<PosAddMaterialModal
  isOpen={isAddMaterialModalOpen}
  {editingMaterial}
  {rawMaterialCategories}
  onClose={() => (isAddMaterialModalOpen = false)}
  onSave={handleSaveMaterial}
/>

<PosOpnameModal
  material={adjustingMaterial}
  onClose={() => (adjustingMaterial = null)}
  onSave={handleConfirmOpname}
/>
