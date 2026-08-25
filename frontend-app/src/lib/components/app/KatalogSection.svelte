<script lang="ts">
  import { Package, FolderTree, Layers, Plus } from 'lucide-svelte';
  import type {
    ProductMenuItem,
    CategoryItem,
    RawMaterialItem,
    RawMaterialUnit,
    User,
  } from '../../types/app';
  import { inventoryService } from '../../services/inventory-service';
  import MenuProductTab from './catalog/MenuProductTab.svelte';
  import CategoryTab from './catalog/CategoryTab.svelte';
  import RawMaterialTab from './catalog/RawMaterialTab.svelte';
  import AddEditMenuModal from './catalog/modals/AddEditMenuModal.svelte';
  import AddEditCategoryModal from './catalog/modals/AddEditCategoryModal.svelte';
  import AddEditMaterialModal from './catalog/modals/AddEditMaterialModal.svelte';
  import DeleteConfirmModal from './catalog/modals/DeleteConfirmModal.svelte';
  import StockAdjustmentModal from './StockAdjustmentModal.svelte';

  interface Props {
    currentUser: User;
    initialSubTab?: string;
  }

  let { currentUser, initialSubTab = 'menu' }: Props = $props();

  let activeSubTab = $state<'menu' | 'kategori' | 'bahan'>('menu');

  $effect(() => {
    if (initialSubTab === 'kategori' || initialSubTab === 'bahan' || initialSubTab === 'menu') {
      activeSubTab = initialSubTab;
    }
  });

  let menuItems = $state<ProductMenuItem[]>([]);
  let categories = $state<CategoryItem[]>([]);
  let rawMaterials = $state<RawMaterialItem[]>(inventoryService.getRawMaterials());
  let isLoading = $state(false);

  // Modal States
  let isAddMenuModalOpen = $state(false);
  let isAddCategoryModalOpen = $state(false);
  let isAddMaterialModalOpen = $state(false);
  let adjustingMaterial = $state<RawMaterialItem | null>(null);

  // Delete State
  let deleteTarget = $state<{
    type: 'menu' | 'kategori' | 'bahan';
    id: string;
    name: string;
  } | null>(null);
  let deleteErrorMessage = $state<string | null>(null);

  async function loadData() {
    isLoading = true;
    try {
      const [cats, prods] = await Promise.all([
        inventoryService.fetchLiveCategories(),
        inventoryService.fetchLiveProducts(),
      ]);
      categories = cats;
      menuItems = prods;
    } finally {
      isLoading = false;
    }
  }

  $effect(() => {
    loadData();
  });

  async function handleToggleAvailability(item: ProductMenuItem) {
    const updated = await inventoryService.updateMenuItem(item.id, { is_available: !item.is_available });
    if (updated) {
      await loadData();
    }
  }

  async function handleSaveMenu(menu: {
    name: string;
    category_id: string;
    price: number;
    description: string;
    is_available: boolean;
  }) {
    await inventoryService.createMenuItem(menu);
    await loadData();
  }

  async function handleSaveCategory(category: { name: string; type: 'MENU' | 'RAW_MATERIAL' }) {
    await inventoryService.createCategory(category.name);
    await loadData();
  }

  function handleSaveMaterial(material: {
    name: string;
    category_id: string;
    current_stock: number;
    min_stock_alert: number;
    unit: RawMaterialUnit;
  }) {
    inventoryService.createRawMaterial(material);
    rawMaterials = inventoryService.getRawMaterials();
  }

  async function executeDelete() {
    if (!deleteTarget) return;
    if (deleteTarget.type === 'menu') {
      await inventoryService.deleteMenuItem(deleteTarget.id);
      await loadData();
      deleteTarget = null;
    } else if (deleteTarget.type === 'bahan') {
      inventoryService.deleteRawMaterial(deleteTarget.id);
      rawMaterials = inventoryService.getRawMaterials();
      deleteTarget = null;
    } else if (deleteTarget.type === 'kategori') {
      const res = await inventoryService.deleteCategory(deleteTarget.id);
      if (!res.success) {
        deleteErrorMessage = res.message || 'Kategori tidak dapat dihapus.';
        return;
      }
      await loadData();
      deleteTarget = null;
    }
  }
</script>

<div class="space-y-6 font-sans">
  <!-- Top Segmented Tabs Wrapper -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white border border-[#d9d9dd] rounded-[24px] p-2 sm:p-2.5">
    <div class="flex items-center gap-1.5 w-full sm:w-auto bg-[#eeece7]/40 sm:bg-transparent p-1 sm:p-0 rounded-full">
      <button
        type="button"
        title={`Menu (${menuItems.length})`}
        onclick={() => (activeSubTab = 'menu')}
        class={`text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-2 ${
          activeSubTab === 'menu'
            ? 'flex-1 sm:flex-initial bg-[#17171c] text-white shadow-xs px-4 py-2 sm:px-5 sm:py-2.5'
            : 'text-[#616161] hover:text-[#212121] hover:bg-white/80 w-9 h-9 sm:w-auto sm:h-auto sm:p-2.5 shrink-0'
        }`}
      >
        <Package class="w-4 h-4 shrink-0" />
        {#if activeSubTab === 'menu'}
          <span class="whitespace-nowrap truncate">Menu ({menuItems.length})</span>
        {/if}
      </button>

      <button
        type="button"
        title={`Kategori (${categories.length})`}
        onclick={() => (activeSubTab = 'kategori')}
        class={`text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-2 ${
          activeSubTab === 'kategori'
            ? 'flex-1 sm:flex-initial bg-[#17171c] text-white shadow-xs px-4 py-2 sm:px-5 sm:py-2.5'
            : 'text-[#616161] hover:text-[#212121] hover:bg-white/80 w-9 h-9 sm:w-auto sm:h-auto sm:p-2.5 shrink-0'
        }`}
      >
        <FolderTree class="w-4 h-4 shrink-0" />
        {#if activeSubTab === 'kategori'}
          <span class="whitespace-nowrap truncate">Kategori ({categories.length})</span>
        {/if}
      </button>

      <button
        type="button"
        title={`Bahan (${rawMaterials.length})`}
        onclick={() => (activeSubTab = 'bahan')}
        class={`text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-2 ${
          activeSubTab === 'bahan'
            ? 'flex-1 sm:flex-initial bg-[#17171c] text-white shadow-xs px-4 py-2 sm:px-5 sm:py-2.5'
            : 'text-[#616161] hover:text-[#212121] hover:bg-white/80 w-9 h-9 sm:w-auto sm:h-auto sm:p-2.5 shrink-0'
        }`}
      >
        <Layers class="w-4 h-4 shrink-0" />
        {#if activeSubTab === 'bahan'}
          <span class="whitespace-nowrap truncate">Bahan ({rawMaterials.length})</span>
        {/if}
      </button>
    </div>

    <div class="self-stretch sm:self-auto flex justify-end">
      {#if activeSubTab === 'menu'}
        <button
          type="button"
          onclick={() => (isAddMenuModalOpen = true)}
          class="w-full sm:w-auto px-4 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full transition-all cursor-pointer flex items-center justify-center gap-1.5 shrink-0"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>Tambah Menu</span>
        </button>
      {:else if activeSubTab === 'kategori'}
        <button
          type="button"
          onclick={() => (isAddCategoryModalOpen = true)}
          class="w-full sm:w-auto px-4 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full transition-all cursor-pointer flex items-center justify-center gap-1.5 shrink-0"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>Tambah Kategori</span>
        </button>
      {:else}
        <button
          type="button"
          onclick={() => (isAddMaterialModalOpen = true)}
          class="w-full sm:w-auto px-4 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full transition-all cursor-pointer flex items-center justify-center gap-1.5 shrink-0"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>Tambah Bahan Baku</span>
        </button>
      {/if}
    </div>
  </div>

  {#if activeSubTab === 'menu'}
    <MenuProductTab
      {menuItems}
      {categories}
      {isLoading}
      onToggleAvailability={handleToggleAvailability}
      onPromptDelete={(id, name) => {
        deleteTarget = { type: 'menu', id, name };
        deleteErrorMessage = null;
      }}
    />
  {:else if activeSubTab === 'kategori'}
    <CategoryTab
      {categories}
      onPromptDelete={(id, name) => {
        deleteTarget = { type: 'kategori', id, name };
        deleteErrorMessage = null;
      }}
    />
  {:else if activeSubTab === 'bahan'}
    <RawMaterialTab
      {rawMaterials}
      {categories}
      onAdjustStock={(mat) => (adjustingMaterial = mat)}
      onPromptDelete={(id, name) => {
        deleteTarget = { type: 'bahan', id, name };
        deleteErrorMessage = null;
      }}
    />
  {/if}
</div>

<AddEditMenuModal
  isOpen={isAddMenuModalOpen}
  {categories}
  onClose={() => (isAddMenuModalOpen = false)}
  onSave={handleSaveMenu}
/>

<AddEditCategoryModal
  isOpen={isAddCategoryModalOpen}
  onClose={() => (isAddCategoryModalOpen = false)}
  onSave={handleSaveCategory}
/>

<AddEditMaterialModal
  isOpen={isAddMaterialModalOpen}
  {categories}
  onClose={() => (isAddMaterialModalOpen = false)}
  onSave={handleSaveMaterial}
/>

{#if adjustingMaterial}
  <StockAdjustmentModal
    material={adjustingMaterial}
    currentUserName={currentUser.name}
    onClose={() => (adjustingMaterial = null)}
    onSuccess={() => {
      rawMaterials = inventoryService.getRawMaterials();
      adjustingMaterial = null;
    }}
  />
{/if}

<DeleteConfirmModal
  target={deleteTarget}
  errorMessage={deleteErrorMessage}
  onClose={() => {
    deleteTarget = null;
    deleteErrorMessage = null;
  }}
  onConfirm={executeDelete}
/>
