<script lang="ts">
  import { Package, Layers } from 'lucide-svelte';
  import type { Product, Category, RawMaterial, StockAdjustmentReason } from '../../../types/pos';
  import PosMenuTab from './catalog/PosMenuTab.svelte';
  import PosRawMaterialTab from './catalog/PosRawMaterialTab.svelte';
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

  let activeSubTab = $state<'bahan' | 'menu'>('bahan');

  // Raw Materials in POS state
  let rawMaterials = $state<Array<RawMaterial & { stock_previous_day?: number }>>([
    {
      id: 'raw-pos-1',
      name: 'Fresh Milk Diamond 1L',
      category_id: 'cat-dairy',
      category_name: 'Dairy',
      stock_previous_day: 24,
      current_stock: 18,
      min_stock_alert: 10,
      unit: 'liter',
      last_adjusted_at: '2026-08-24 07:30',
    },
    {
      id: 'raw-pos-2',
      name: 'Oatside Barista Oat Milk 1L',
      category_id: 'cat-dairy',
      category_name: 'Dairy',
      stock_previous_day: 12,
      current_stock: 6,
      min_stock_alert: 8,
      unit: 'liter',
      last_adjusted_at: '2026-08-23 18:00',
    },
    {
      id: 'raw-pos-3',
      name: 'Biji Kopi House Blend (1kg)',
      category_id: 'cat-beans',
      category_name: 'Beans & Powder',
      stock_previous_day: 15,
      current_stock: 12,
      min_stock_alert: 5,
      unit: 'kg',
      last_adjusted_at: '2026-08-24 06:45',
    },
    {
      id: 'raw-pos-4',
      name: 'Sirup Monin Caramel 700ml',
      category_id: 'cat-syrup',
      category_name: 'Sirup',
      stock_previous_day: 3,
      current_stock: 2,
      min_stock_alert: 3,
      unit: 'botol',
      last_adjusted_at: '2026-08-21 16:30',
    },
    {
      id: 'raw-pos-5',
      name: 'Paper Cup Hot 8oz (50 pcs)',
      category_id: 'cat-packaging',
      category_name: 'Packaging',
      stock_previous_day: 20,
      current_stock: 15,
      min_stock_alert: 8,
      unit: 'pack',
      last_adjusted_at: '2026-08-20 10:00',
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
        last_adjusted_at: new Date().toISOString().replace('T', ' ').substring(0, 16),
      };
      rawMaterials = [newMat, ...rawMaterials];
    }
  }

  function handleConfirmOpname(
    materialId: string,
    physicalCount: number,
    _reason: StockAdjustmentReason,
    _notes: string
  ) {
    rawMaterials = rawMaterials.map((m) => {
      if (m.id === materialId) {
        return {
          ...m,
          stock_previous_day: m.current_stock,
          current_stock: Number(physicalCount),
          last_adjusted_at: new Date().toISOString().replace('T', ' ').substring(0, 16),
        };
      }
      return m;
    });
  }

  function handleDeleteMaterial(id: string) {
    if (confirm('Hapus bahan baku ini dari bar POS?')) {
      rawMaterials = rawMaterials.filter((m) => m.id !== id);
    }
  }
</script>

<div class="space-y-4 max-w-6xl mx-auto font-sans pb-8">
  <div class="flex items-center justify-between gap-4 border-b border-[#e5e5e5] pb-3">
    <div class="flex items-center gap-2">
      <button
        type="button"
        onclick={() => (activeSubTab = 'bahan')}
        class={`px-4 py-2 rounded-full text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer ${
          activeSubTab === 'bahan'
            ? 'bg-[#17171c] text-white shadow-xs'
            : 'bg-white border border-[#d9d9dd] text-[#616161] hover:text-[#17171c]'
        }`}
      >
        <Layers class="w-3.5 h-3.5" />
        <span>Stok Bahan Baku Bar ({rawMaterials.length})</span>
      </button>

      <button
        type="button"
        onclick={() => (activeSubTab = 'menu')}
        class={`px-4 py-2 rounded-full text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer ${
          activeSubTab === 'menu'
            ? 'bg-[#17171c] text-white shadow-xs'
            : 'bg-white border border-[#d9d9dd] text-[#616161] hover:text-[#17171c]'
        }`}
      >
        <Package class="w-3.5 h-3.5" />
        <span>Menu Jualan POS ({products.length})</span>
      </button>
    </div>
  </div>

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
  {:else if activeSubTab === 'menu'}
    <PosMenuTab
      {products}
      {categories}
      {onToggleProductActive}
      onOpenAddModal={() => (isAddMenuModalOpen = true)}
    />
  {/if}
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
