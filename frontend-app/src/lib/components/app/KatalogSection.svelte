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

  let { currentUser: _currentUser, initialSubTab = 'menu' }: Props = $props();

  let activeSubTab = $state<'menu' | 'kategori' | 'bahan'>('menu');

  $effect(() => {
    if (initialSubTab === 'kategori' || initialSubTab === 'bahan' || initialSubTab === 'menu') {
      activeSubTab = initialSubTab;
    }
  });

  let menuItems = $state<ProductMenuItem[]>([]);
  let categories = $state<CategoryItem[]>([]);
  let rawMaterials = $state<RawMaterialItem[]>([]);
  let isLoading = $state(false);

  // state modal
  let isAddMenuModalOpen = $state(false);
  let isAddCategoryModalOpen = $state(false);
  let isAddMaterialModalOpen = $state(false);
  let adjustingMaterial = $state<RawMaterialItem | null>(null);

  // state hapus
  let deleteTarget = $state<{
    type: 'menu' | 'kategori' | 'bahan';
    id: string;
    name: string;
  } | null>(null);
  let deleteErrorMessage = $state<string | null>(null);

  async function loadData() {
    isLoading = true;
    try {
      const [cats, prods, raws] = await Promise.all([
        inventoryService.fetchLiveCategories(),
        inventoryService.fetchLiveProducts(),
        inventoryService.getRawMaterials(),
      ]);
      categories = cats;
      menuItems = prods;
      rawMaterials = raws;
    } finally {
      isLoading = false;
    }
  }

  $effect(() => {
    loadData();
  });

  async function handleToggleAvailability(item: ProductMenuItem) {
    const updated = await inventoryService.updateMenuItem(item.id, {
      is_available: !item.is_available,
    });
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

  async function handleSaveMaterial(material: {
    name: string;
    category_id: string;
    current_stock: number;
    min_stock_alert: number;
    unit: RawMaterialUnit;
  }) {
    await inventoryService.createRawMaterial(material);
    rawMaterials = await inventoryService.getRawMaterials();
  }

  async function executeDelete() {
    if (!deleteTarget) return;
    if (deleteTarget.type === 'menu') {
      await inventoryService.deleteMenuItem(deleteTarget.id);
      await loadData();
      deleteTarget = null;
    } else if (deleteTarget.type === 'bahan') {
      await inventoryService.deleteRawMaterial(deleteTarget.id);
      rawMaterials = await inventoryService.getRawMaterials();
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

  const catalogTabs = $derived([
    { id: 'menu' as const, label: 'Menu Jualan', count: menuItems.length, icon: Package },
    { id: 'kategori' as const, label: 'Kategori', count: categories.length, icon: FolderTree },
    { id: 'bahan' as const, label: 'Bahan Baku', count: rawMaterials.length, icon: Layers },
  ]);
</script>

<div class="space-y-6 pb-8 font-sans">
  <!-- Top Segmented Navigation & Action Button -->
  <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
    <div
      class="no-scrollbar inline-flex items-center gap-1.5 overflow-x-auto rounded-2xl border border-[#e5e5ea] bg-white p-1.5 shadow-2xs"
    >
      {#each catalogTabs as tab}
        {@const Icon = tab.icon}
        {@const isActive = activeSubTab === tab.id}
        <button
          type="button"
          onclick={() => (activeSubTab = tab.id)}
          class={`flex shrink-0 cursor-pointer items-center gap-2 rounded-xl px-4 py-2 text-xs font-medium transition-all duration-200 ${
            isActive
              ? 'bg-[#17171c] font-semibold text-white shadow-xs'
              : 'text-[#686873] hover:bg-[#f4f4f6] hover:text-[#17171c]'
          }`}
        >
          <Icon class={`size-4 ${isActive ? 'text-white' : 'text-[#8e8e93]'}`} />
          <span class="whitespace-nowrap">{tab.label}</span>
          <span
            class={`rounded-full px-2 py-0.5 font-mono text-[10px] font-semibold ${
              isActive ? 'bg-white/20 text-white' : 'bg-[#eeece7] text-[#616161]'
            }`}
          >
            {tab.count}
          </span>
        </button>
      {/each}
    </div>

    <div>
      {#if activeSubTab === 'menu'}
        <button
          type="button"
          onclick={() => (isAddMenuModalOpen = true)}
          class="flex w-full shrink-0 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] px-5 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black sm:w-auto"
        >
          <Plus class="size-4" />
          <span>Tambah Menu</span>
        </button>
      {:else if activeSubTab === 'kategori'}
        <button
          type="button"
          onclick={() => (isAddCategoryModalOpen = true)}
          class="flex w-full shrink-0 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] px-5 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black sm:w-auto"
        >
          <Plus class="size-4" />
          <span>Tambah Kategori</span>
        </button>
      {:else}
        <button
          type="button"
          onclick={() => (isAddMaterialModalOpen = true)}
          class="flex w-full shrink-0 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] px-5 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black sm:w-auto"
        >
          <Plus class="size-4" />
          <span>Tambah Bahan Baku</span>
        </button>
      {/if}
    </div>
  </div>

  <div class="animate-in fade-in min-w-0 duration-200">
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
        {menuItems}
        {rawMaterials}
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
</div>

<!-- Modal: Add/Edit Menu -->
<AddEditMenuModal
  isOpen={isAddMenuModalOpen}
  {categories}
  onClose={() => (isAddMenuModalOpen = false)}
  onSave={handleSaveMenu}
/>

<!-- Modal: Add/Edit Category -->
<AddEditCategoryModal
  isOpen={isAddCategoryModalOpen}
  onClose={() => (isAddCategoryModalOpen = false)}
  onSave={handleSaveCategory}
/>

<!-- Modal: Add/Edit Material -->
<AddEditMaterialModal
  isOpen={isAddMaterialModalOpen}
  {categories}
  onClose={() => (isAddMaterialModalOpen = false)}
  onSave={handleSaveMaterial}
/>

<!-- Modal: Stock Adjustment (Opname) -->
{#if adjustingMaterial}
  <StockAdjustmentModal
    material={adjustingMaterial}
    onClose={() => (adjustingMaterial = null)}
    onSuccess={async () => {
      rawMaterials = await inventoryService.getRawMaterials();
      adjustingMaterial = null;
    }}
  />
{/if}

<!-- Modal: Delete Confirmation -->
{#if deleteTarget}
  <DeleteConfirmModal
    isOpen={true}
    title={`Hapus ${deleteTarget.type === 'menu' ? 'Menu' : deleteTarget.type === 'kategori' ? 'Kategori' : 'Bahan Baku'}`}
    message={`Apakah Anda yakin ingin menghapus "${deleteTarget.name}"? Tindakan ini tidak dapat dibatalkan.`}
    errorMessage={deleteErrorMessage}
    onClose={() => {
      deleteTarget = null;
      deleteErrorMessage = null;
    }}
    onConfirm={executeDelete}
  />
{/if}
