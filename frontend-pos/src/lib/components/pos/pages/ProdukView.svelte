<script lang="ts">
  import { Package, Layers, Calendar, History } from 'lucide-svelte';
  import type {
    Product,
    Category,
    RawMaterial,
    StockAdjustmentReason,
    StockAdjustmentLog,
  } from '../../../types/pos';
  import PosMenuTab from './catalog/PosMenuTab.svelte';
  import PosRawMaterialTab from './catalog/PosRawMaterialTab.svelte';
  import PosDailyReconciliationTab from './catalog/PosDailyReconciliationTab.svelte';
  import PosStockLogTab from './catalog/PosStockLogTab.svelte';
  import PosAddMenuModal from './catalog/modals/PosAddMenuModal.svelte';
  import PosAddMaterialModal from './catalog/modals/PosAddMaterialModal.svelte';
  import PosOpnameModal from './catalog/modals/PosOpnameModal.svelte';
  import { posService } from '../../../services/pos-service';

  interface Props {
    products: Product[];
    categories: Category[];
    onToggleProductActive: (productId: string) => void;
    onAddNewProduct: (product: Product) => void;
  }

  let { products = [], categories = [], onToggleProductActive, onAddNewProduct }: Props = $props();

  let activeSubTab = $state<'bahan' | 'rekap' | 'menu' | 'logs'>('bahan');

  // bahan baku kasir
  let rawMaterials = $state<Array<RawMaterial & { stock_previous_day?: number }>>([]);

  // log audit opname stok
  let stockLogs = $state<StockAdjustmentLog[]>([]);

  // state modal
  let isAddMenuModalOpen = $state(false);
  let isAddMaterialModalOpen = $state(false);
  let editingMaterial = $state<(RawMaterial & { stock_previous_day?: number }) | null>(null);
  let adjustingMaterial = $state<(RawMaterial & { stock_previous_day?: number }) | null>(null);

  let rawMaterialCategories = $state<Array<{ id: string; name: string }>>([]);

  $effect(() => {
    posService.getRawMaterials().then((data) => {
      if (data && data.length > 0) {
        rawMaterials = data;
      }
    });

    posService.getInventoryCategories().then((cats) => {
      if (cats && cats.length > 0) {
        rawMaterialCategories = cats;
      }
    });

    posService.getStockLogs().then((logs) => {
      if (logs && logs.length > 0) {
        stockLogs = logs;
      }
    });
  });

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

    // perbarui bahan baku
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

    // catat log audit
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

<div class="flex h-full flex-1 flex-col overflow-y-auto bg-[#f4f6f9] p-4 font-sans sm:p-6 lg:p-8">
  <div class="mx-auto w-full max-w-7xl space-y-5 pb-8">
    <!-- Header Sub-Tabs (Excel / Enterprise POS Architecture) -->
    <div
      class="no-scrollbar flex items-center justify-between gap-4 overflow-x-auto border-b border-zinc-200 pb-3"
    >
      <div class="flex items-center gap-2">
        <button
          type="button"
          onclick={() => (activeSubTab = 'bahan')}
          class={`flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-xs font-semibold whitespace-nowrap transition-all ${
            activeSubTab === 'bahan'
              ? 'border-zinc-900 bg-zinc-900 text-white shadow-2xs'
              : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-100/70 hover:text-zinc-900'
          }`}
        >
          <Layers class="size-4" />
          <span>Stok Bahan Baku ({rawMaterials.length})</span>
        </button>

        <button
          type="button"
          onclick={() => (activeSubTab = 'rekap')}
          class={`flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-xs font-semibold whitespace-nowrap transition-all ${
            activeSubTab === 'rekap'
              ? 'border-zinc-900 bg-zinc-900 text-white shadow-2xs'
              : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-100/70 hover:text-zinc-900'
          }`}
        >
          <Calendar class="size-4" />
          <span>Rekap Harian &amp; Mutasi</span>
        </button>

        <button
          type="button"
          onclick={() => (activeSubTab = 'menu')}
          class={`flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-xs font-semibold whitespace-nowrap transition-all ${
            activeSubTab === 'menu'
              ? 'border-zinc-900 bg-zinc-900 text-white shadow-2xs'
              : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-100/70 hover:text-zinc-900'
          }`}
        >
          <Package class="size-4" />
          <span>Menu Jualan POS ({products.length})</span>
        </button>

        <button
          type="button"
          onclick={() => (activeSubTab = 'logs')}
          class={`flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-xs font-semibold whitespace-nowrap transition-all ${
            activeSubTab === 'logs'
              ? 'border-zinc-900 bg-zinc-900 text-white shadow-2xs'
              : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-100/70 hover:text-zinc-900'
          }`}
        >
          <History class="size-4" />
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
      <PosDailyReconciliationTab {rawMaterials} onOpenOpname={(mat) => (adjustingMaterial = mat)} />
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
