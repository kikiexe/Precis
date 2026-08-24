<script lang="ts">
  import {
    Plus,
    Search,
    Package,
    FolderTree,
    Layers,
    X,
    Trash2,
    AlertTriangle,
  } from 'lucide-svelte';
  import type {
    ProductMenuItem,
    CategoryItem,
    RawMaterialItem,
    RawMaterialUnit,
    User
  } from '../../types/app';
  import { inventoryService } from '../../services/inventory-service';
  import StockAdjustmentModal from './StockAdjustmentModal.svelte';

  interface Props {
    currentUser: User;
    initialSubTab?: string;
  }

  let { currentUser, initialSubTab = 'menu' }: Props = $props();

  let activeSubTab = $state<'menu' | 'kategori' | 'bahan'>('menu');

  // React to initialSubTab changes
  $effect(() => {
    if (initialSubTab === 'kategori' || initialSubTab === 'bahan' || initialSubTab === 'menu') {
      activeSubTab = initialSubTab;
    }
  });

  let menuItems = $state<ProductMenuItem[]>(inventoryService.getMenuItems());
  let categories = $state<CategoryItem[]>(inventoryService.getCategories());
  let rawMaterials = $state<RawMaterialItem[]>(inventoryService.getRawMaterials());

  let searchQuery = $state('');
  let selectedCategoryFilter = $state('ALL');

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

  function promptDelete(type: 'menu' | 'kategori' | 'bahan', id: string, name: string) {
    deleteTarget = { type, id, name };
    deleteErrorMessage = null;
  }

  function executeDelete() {
    if (!deleteTarget) return;
    if (deleteTarget.type === 'menu') {
      inventoryService.deleteMenuItem(deleteTarget.id);
      menuItems = inventoryService.getMenuItems();
      deleteTarget = null;
    } else if (deleteTarget.type === 'bahan') {
      inventoryService.deleteRawMaterial(deleteTarget.id);
      rawMaterials = inventoryService.getRawMaterials();
      deleteTarget = null;
    } else if (deleteTarget.type === 'kategori') {
      const res = inventoryService.deleteCategory(deleteTarget.id);
      if (!res.success) {
        deleteErrorMessage = res.message || 'Kategori tidak dapat dihapus.';
        return;
      }
      categories = inventoryService.getCategories();
      deleteTarget = null;
    }
  }

  // Add Menu Form
  let menuForm = $state({
    name: '',
    category_id: 'cat-coffee',
    price: 25000,
    description: '',
    is_available: true,
  });

  // Add Category Form
  let categoryForm = $state({
    name: '',
    type: 'MENU' as 'MENU' | 'RAW_MATERIAL',
  });

  // Add Material Form
  let materialForm = $state({
    name: '',
    category_id: 'cat-dairy',
    current_stock: 10,
    min_stock_alert: 5,
    unit: 'liter' as RawMaterialUnit,
  });

  // Filtered Menu Items
  let filteredMenuItems = $derived(
    menuItems.filter((item) => {
      const matchSearch =
        searchQuery.trim() === '' ||
        item.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (item.description && item.description.toLowerCase().includes(searchQuery.toLowerCase()));
      const matchCat = selectedCategoryFilter === 'ALL' || item.category_id === selectedCategoryFilter;
      return matchSearch && matchCat;
    })
  );

  // Filtered Raw Materials
  let filteredRawMaterials = $derived(
    rawMaterials.filter((mat) => {
      const matchSearch =
        searchQuery.trim() === '' ||
        mat.name.toLowerCase().includes(searchQuery.toLowerCase());
      const matchCat = selectedCategoryFilter === 'ALL' || mat.category_id === selectedCategoryFilter;
      return matchSearch && matchCat;
    })
  );

  function formatCurrency(amount: number) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(amount);
  }

  function handleToggleAvailability(item: ProductMenuItem) {
    const updated = inventoryService.toggleMenuItemAvailability(item.id);
    if (updated) {
      menuItems = inventoryService.getMenuItems();
    }
  }

  function handleSaveMenu() {
    if (!menuForm.name.trim() || menuForm.price <= 0) return;
    inventoryService.saveMenuItem({
      name: menuForm.name.trim(),
      category_id: menuForm.category_id,
      price: menuForm.price,
      description: menuForm.description.trim(),
      is_available: menuForm.is_available,
    });
    menuItems = inventoryService.getMenuItems();
    isAddMenuModalOpen = false;
    menuForm = { name: '', category_id: 'cat-coffee', price: 25000, description: '', is_available: true };
  }

  function handleSaveMaterial() {
    if (!materialForm.name.trim() || materialForm.current_stock < 0) return;
    inventoryService.saveRawMaterial({
      name: materialForm.name.trim(),
      category_id: materialForm.category_id,
      current_stock: Number(materialForm.current_stock),
      min_stock_alert: Number(materialForm.min_stock_alert),
      unit: materialForm.unit,
    });
    rawMaterials = inventoryService.getRawMaterials();
    isAddMaterialModalOpen = false;
    materialForm = { name: '', category_id: 'cat-dairy', current_stock: 10, min_stock_alert: 5, unit: 'liter' };
  }

  function handleSaveCategory() {
    if (!categoryForm.name.trim()) return;
    inventoryService.saveCategory({
      name: categoryForm.name.trim(),
      type: categoryForm.type,
    });
    categories = inventoryService.getCategories();
    isAddCategoryModalOpen = false;
    categoryForm = { name: '', type: 'MENU' };
  }
</script>

<div class="space-y-6 font-sans">
  <!-- Top Segmented Tabs Wrapper -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white border border-[#d9d9dd] rounded-[24px] p-2 sm:p-2.5">
    <div class="flex items-center gap-1.5 w-full sm:w-auto bg-[#eeece7]/40 sm:bg-transparent p-1 sm:p-0 rounded-full">
      <button
        type="button"
        title={`Menu (${menuItems.length})`}
        onclick={() => { activeSubTab = 'menu'; searchQuery = ''; selectedCategoryFilter = 'ALL'; }}
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
        onclick={() => { activeSubTab = 'kategori'; searchQuery = ''; selectedCategoryFilter = 'ALL'; }}
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
        onclick={() => { activeSubTab = 'bahan'; searchQuery = ''; selectedCategoryFilter = 'ALL'; }}
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

    <!-- Quick Action Button per Tab -->
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

  <!-- TAB 1: MENU JUALAN -->
  {#if activeSubTab === 'menu'}
    <div class="space-y-4">
      <!-- Search & Filters -->
      <div class="bg-white border border-[#d9d9dd] rounded-[20px] p-3 sm:p-4 flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center justify-between">
        <div class="relative flex-1 min-w-0">
          <Search class="w-4 h-4 text-[#93939f] absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            bind:value={searchQuery}
            placeholder="Cari nama menu jualan..."
            class="w-full bg-[#eeece7]/40 pl-10 pr-4 py-2 text-xs rounded-full border border-[#d9d9dd] placeholder-[#93939f] text-[#212121] focus:border-[#17171c] focus:outline-hidden transition-all"
          />
        </div>

        <div class="flex items-center gap-1 overflow-x-auto no-scrollbar bg-[#eeece7]/60 p-1 rounded-full border border-[#d9d9dd] shrink-0 max-w-full">
          <button
            type="button"
            onclick={() => (selectedCategoryFilter = 'ALL')}
            class={`px-3 py-1 text-xs rounded-full transition-all cursor-pointer shrink-0 ${
              selectedCategoryFilter === 'ALL'
                ? 'bg-[#17171c] text-white font-medium'
                : 'text-[#616161] hover:text-[#212121]'
            }`}
          >
            Semua
          </button>
          {#each categories.filter((c) => c.type === 'MENU') as cat}
            <button
              type="button"
              onclick={() => (selectedCategoryFilter = cat.id)}
              class={`px-3 py-1 text-xs rounded-full transition-all cursor-pointer shrink-0 ${
                selectedCategoryFilter === cat.id
                  ? 'bg-[#17171c] text-white font-medium'
                  : 'text-[#616161] hover:text-[#212121]'
              }`}
            >
              {cat.name}
            </button>
          {/each}
        </div>
      </div>

      <!-- Menu Items Square-Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-4">
        {#each filteredMenuItems as item}
          <div class="bg-white border border-[#d9d9dd] rounded-2xl p-3 sm:p-4 flex flex-col justify-between hover:border-[#17171c] transition-all min-h-[160px] space-y-2">
            <div class="space-y-1">
              <div class="flex items-center justify-between gap-1">
                <span class="text-[9px] font-mono text-[#75758a] uppercase truncate">{item.category_name}</span>
                <div class="flex items-center gap-1.5">
                  <span class={`w-2 h-2 rounded-full shrink-0 ${
                    item.is_available ? 'bg-[#00875a]' : 'bg-[#e5484d]'
                  }`} title={item.is_available ? 'Tersedia' : 'Habis'}></span>
                  <button
                    type="button"
                    onclick={() => promptDelete('menu', item.id, item.name)}
                    class="text-[#93939f] hover:text-[#e5484d] p-0.5 rounded hover:bg-[#ffefef] transition-all cursor-pointer"
                    title="Hapus Menu"
                  >
                    <Trash2 class="w-3 h-3" />
                  </button>
                </div>
              </div>

              <h3 class="text-xs sm:text-sm font-medium text-[#212121] tracking-tight line-clamp-2">{item.name}</h3>
              {#if item.description}
                <p class="text-[10px] text-[#75758a] line-clamp-1">{item.description}</p>
              {/if}
            </div>

            <div class="pt-2 border-t border-[#f2f2f2] flex flex-col gap-1.5">
              <div class="text-xs sm:text-sm font-medium font-mono text-[#17171c]">
                {formatCurrency(item.price)}
              </div>

              <button
                type="button"
                onclick={() => handleToggleAvailability(item)}
                class={`w-full py-1 text-[10px] sm:text-[11px] font-medium rounded-lg border transition-all cursor-pointer text-center ${
                  item.is_available
                    ? 'border-[#d9d9dd] bg-[#fbfbfb] text-[#616161] hover:bg-[#ffefef] hover:text-[#e5484d] hover:border-[#e5484d]/30'
                    : 'border-[#003c33] bg-[#edfce9] text-[#003c33]'
                }`}
              >
                {item.is_available ? 'Set Habis' : 'Set Tersedia'}
              </button>
            </div>
          </div>
        {/each}
      </div>
    </div>
  {/if}

  <!-- TAB 2: KATEGORI -->
  {#if activeSubTab === 'kategori'}
    <div class="space-y-3 font-sans">
      <div class="flex items-center justify-between pb-1">
        <div>
          <h2 class="text-sm font-medium text-[#212121]">Master Kategori Produk &amp; Bahan Baku</h2>
          <p class="text-[11px] text-[#75758a]">Pengelompokan menu POS dan klasifikasi stok bahan di bar</p>
        </div>
      </div>

      <!-- Responsive Flat Category Cards Grid (No double rounded bounding box) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        {#each categories as cat}
          <div class="bg-white border border-[#d9d9dd] rounded-2xl p-3.5 flex items-center justify-between gap-3 hover:border-[#17171c]/40 transition-all">
            <div class="flex items-center gap-3 min-w-0">
              <span class={`p-2 rounded-xl shrink-0 ${
                cat.type === 'MENU' ? 'bg-[#f1f5ff] text-[#1863dc]' : 'bg-[#eeece7] text-[#17171c]'
              }`}>
                {#if cat.type === 'MENU'}
                  <Package class="w-4 h-4" />
                {:else}
                  <Layers class="w-4 h-4" />
                {/if}
              </span>
              <div class="truncate">
                <div class="font-medium text-xs text-[#212121] truncate">{cat.name}</div>
                <div class="text-[10px] text-[#75758a] font-mono">{cat.id}</div>
              </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <div class="flex flex-col items-end gap-1 font-mono text-right">
                <span class={`text-[9px] font-medium px-2 py-0.5 rounded-full ${
                  cat.type === 'MENU' ? 'bg-[#f1f5ff] text-[#1863dc]' : 'bg-[#eeece7] text-[#17171c]'
                }`}>
                  {cat.type === 'MENU' ? 'Menu POS' : 'Bahan Baku'}
                </span>
                <span class="text-[10px] text-[#616161]">
                  {cat.item_count} item
                </span>
              </div>

              <button
                type="button"
                onclick={() => promptDelete('kategori', cat.id, cat.name)}
                class="p-2 text-[#93939f] hover:text-[#e5484d] hover:bg-[#ffefef] rounded-xl transition-all cursor-pointer"
                title="Hapus Kategori"
              >
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        {/each}
      </div>
    </div>
  {/if}

  <!-- TAB 3: BAHAN BAKU & STOK -->
  {#if activeSubTab === 'bahan'}
    <div class="space-y-4">
      <!-- Search & Filters -->
      <div class="bg-white border border-[#d9d9dd] rounded-[20px] p-3 sm:p-4 flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center justify-between">
        <div class="relative flex-1 min-w-0">
          <Search class="w-4 h-4 text-[#93939f] absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            bind:value={searchQuery}
            placeholder="Cari nama bahan baku..."
            class="w-full bg-[#eeece7]/40 pl-10 pr-4 py-2 text-xs rounded-full border border-[#d9d9dd] placeholder-[#93939f] text-[#212121] focus:border-[#17171c] focus:outline-hidden transition-all"
          />
        </div>

        <div class="flex items-center gap-1 overflow-x-auto no-scrollbar bg-[#eeece7]/60 p-1 rounded-full border border-[#d9d9dd] shrink-0 max-w-full">
          <button
            type="button"
            onclick={() => (selectedCategoryFilter = 'ALL')}
            class={`px-3 py-1 text-xs rounded-full transition-all cursor-pointer shrink-0 ${
              selectedCategoryFilter === 'ALL'
                ? 'bg-[#17171c] text-white font-medium'
                : 'text-[#616161] hover:text-[#212121]'
            }`}
          >
            Semua
          </button>
          {#each categories.filter((c) => c.type === 'RAW_MATERIAL') as cat}
            <button
              type="button"
              onclick={() => (selectedCategoryFilter = cat.id)}
              class={`px-3 py-1 text-xs rounded-full transition-all cursor-pointer shrink-0 ${
                selectedCategoryFilter === cat.id
                  ? 'bg-[#17171c] text-white font-medium'
                  : 'text-[#616161] hover:text-[#212121]'
              }`}
            >
              {cat.name}
            </button>
          {/each}
        </div>
      </div>

      <!-- Modern Responsive Raw Materials Grid (Mobile First, no broken tables!) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        {#each filteredRawMaterials as mat}
          {@const isLow = mat.current_stock <= mat.min_stock_alert}
          <div class={`bg-white border rounded-2xl p-4 flex flex-col justify-between space-y-3 transition-all ${
            isLow ? 'border-[#e5484d]/40 ring-1 ring-[#e5484d]/20' : 'border-[#d9d9dd] hover:border-[#17171c]'
          }`}>
            <!-- Header Row: Name, Category, Status -->
            <div class="space-y-1">
              <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] font-mono text-[#75758a] uppercase truncate">{mat.category_name}</span>
                <span class={`text-[9px] font-mono font-medium px-2 py-0.5 rounded-full shrink-0 ${
                  isLow ? 'bg-[#ffefef] text-[#e5484d]' : 'bg-[#edfce9] text-[#003c33]'
                }`}>
                  {isLow ? `Menipis (< ${mat.min_stock_alert})` : 'Stok Aman'}
                </span>
              </div>

              <h3 class="text-sm font-medium text-[#212121] tracking-tight">{mat.name}</h3>
              <div class="text-[10px] text-[#75758a] font-mono">
                Audit: {mat.last_adjusted_at || '-'}
              </div>
            </div>

            <!-- Stats Metric Grid (Clean 2 Columns) -->
            <div class="grid grid-cols-2 gap-2 py-2 px-2.5 bg-[#fbfbfb] border border-[#d9d9dd]/60 rounded-xl text-center">
              <div>
                <div class="text-[9px] text-[#75758a] uppercase font-mono">Stok Fisik</div>
                <div class={`text-xs sm:text-sm font-medium font-mono mt-0.5 ${isLow ? 'text-[#e5484d]' : 'text-[#17171c]'}`}>
                  {mat.current_stock} <span class="text-[10px] font-normal text-[#75758a]">{mat.unit}</span>
                </div>
              </div>

              <div>
                <div class="text-[9px] text-[#75758a] uppercase font-mono">Batas Minimum</div>
                <div class="text-xs font-medium font-mono text-[#75758a] mt-0.5">
                  {mat.min_stock_alert} <span class="text-[10px] font-normal">{mat.unit}</span>
                </div>
              </div>
            </div>

            <!-- Action Row -->
            <div class="pt-1 flex items-center gap-2">
              <button
                type="button"
                onclick={() => (adjustingMaterial = mat)}
                class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-xl transition-all cursor-pointer text-center"
              >
                Sesuaikan Stok (Opname)
              </button>

              <button
                type="button"
                onclick={() => promptDelete('bahan', mat.id, mat.name)}
                class="p-2 text-[#93939f] hover:text-[#e5484d] hover:bg-[#ffefef] border border-[#d9d9dd] rounded-xl transition-all cursor-pointer shrink-0"
                title="Hapus Bahan Baku"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </div>
        {/each}
      </div>
    </div>
  {/if}
</div>

<!-- Modal: Tambah Menu Baru -->
{#if isAddMenuModalOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] w-full max-w-md p-6 space-y-4 shadow-2xl">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3">
        <h3 class="text-base font-medium text-[#212121]">Tambah Menu Jualan Baru</h3>
        <button type="button" onclick={() => (isAddMenuModalOpen = false)} class="p-1 text-[#616161] hover:text-[#212121] cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="space-y-3 text-xs">
        <div class="space-y-1">
          <label for="menu-name" class="block font-medium text-[#212121]">Nama Menu</label>
          <input
            id="menu-name"
            type="text"
            bind:value={menuForm.name}
            placeholder="Contoh: Es Kopi Susu Aren"
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1">
          <label for="menu-category" class="block font-medium text-[#212121]">Kategori Menu</label>
          <select
            id="menu-category"
            bind:value={menuForm.category_id}
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden cursor-pointer"
          >
            {#each categories.filter((c) => c.type === 'MENU') as cat}
              <option value={cat.id}>{cat.name}</option>
            {/each}
          </select>
        </div>

        <div class="space-y-1">
          <label for="menu-price" class="block font-medium text-[#212121]">Harga Jual (IDR)</label>
          <input
            id="menu-price"
            type="number"
            bind:value={menuForm.price}
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1">
          <label for="menu-desc" class="block font-medium text-[#212121]">Deskripsi / Resep Singkat</label>
          <textarea
            id="menu-desc"
            bind:value={menuForm.description}
            rows="2"
            placeholder="Deskripsi bahan dan rasa..."
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden resize-none"
          ></textarea>
        </div>
      </div>

      <div class="pt-2 flex gap-3">
        <button
          type="button"
          onclick={() => (isAddMenuModalOpen = false)}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#eeece7] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSaveMenu}
          class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer"
        >
          Simpan Menu
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Modal: Tambah Bahan Baku Baru -->
{#if isAddMaterialModalOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] w-full max-w-md p-6 space-y-4 shadow-2xl">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3">
        <h3 class="text-base font-medium text-[#212121]">Tambah Bahan Baku Baru</h3>
        <button type="button" onclick={() => (isAddMaterialModalOpen = false)} class="p-1 text-[#616161] hover:text-[#212121] cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="space-y-3 text-xs">
        <div class="space-y-1">
          <label for="material-name" class="block font-medium text-[#212121]">Nama Bahan Baku</label>
          <input
            id="material-name"
            type="text"
            bind:value={materialForm.name}
            placeholder="Contoh: Fresh Milk Diamond 1L"
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label for="material-category" class="block font-medium text-[#212121]">Kategori Bahan</label>
            <select
              id="material-category"
              bind:value={materialForm.category_id}
              class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden cursor-pointer"
            >
              {#each categories.filter((c) => c.type === 'RAW_MATERIAL') as cat}
                <option value={cat.id}>{cat.name}</option>
              {/each}
            </select>
          </div>

          <div class="space-y-1">
            <label for="material-unit" class="block font-medium text-[#212121]">Satuan Unit</label>
            <select
              id="material-unit"
              bind:value={materialForm.unit}
              class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden cursor-pointer"
            >
              <option value="liter">liter</option>
              <option value="ml">ml</option>
              <option value="kg">kg</option>
              <option value="gram">gram</option>
              <option value="pcs">pcs</option>
              <option value="pack">pack</option>
              <option value="botol">botol</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label for="material-stock" class="block font-medium text-[#212121]">Stok Awal</label>
            <input
              id="material-stock"
              type="number"
              bind:value={materialForm.current_stock}
              class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>

          <div class="space-y-1">
            <label for="material-min-stock" class="block font-medium text-[#212121]">Batas Minimum</label>
            <input
              id="material-min-stock"
              type="number"
              bind:value={materialForm.min_stock_alert}
              class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>
        </div>
      </div>

      <div class="pt-2 flex gap-3">
        <button
          type="button"
          onclick={() => (isAddMaterialModalOpen = false)}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#eeece7] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSaveMaterial}
          class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer"
        >
          Simpan Bahan
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Modal: Tambah Kategori Baru -->
{#if isAddCategoryModalOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] w-full max-w-md p-6 space-y-4 shadow-2xl">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3">
        <h3 class="text-base font-medium text-[#212121]">Tambah Kategori Baru</h3>
        <button type="button" onclick={() => (isAddCategoryModalOpen = false)} class="p-1 text-[#616161] hover:text-[#212121] cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="space-y-3 text-xs">
        <div class="space-y-1">
          <label for="cat-name" class="block font-medium text-[#212121]">Nama Kategori</label>
          <input
            id="cat-name"
            type="text"
            bind:value={categoryForm.name}
            placeholder="Contoh: Pastry &amp; Bakery"
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1">
          <label for="cat-type" class="block font-medium text-[#212121]">Tipe Kategori</label>
          <select
            id="cat-type"
            bind:value={categoryForm.type}
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden cursor-pointer"
          >
            <option value="MENU">Menu Jualan POS</option>
            <option value="RAW_MATERIAL">Bahan Baku / Raw Material</option>
          </select>
        </div>
      </div>

      <div class="pt-2 flex gap-3">
        <button
          type="button"
          onclick={() => (isAddCategoryModalOpen = false)}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#eeece7] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSaveCategory}
          class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer"
        >
          Simpan Kategori
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Stock Adjustment Modal Dialog -->
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

<!-- Delete Confirmation Modal Dialog -->
{#if deleteTarget}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] w-full max-w-sm p-6 space-y-4 shadow-2xl">
      <div class="flex items-center gap-3 text-[#e5484d]">
        <div class="p-2.5 rounded-full bg-[#ffefef]">
          <AlertTriangle class="w-5 h-5" />
        </div>
        <div>
          <h3 class="text-sm font-medium text-[#212121]">Konfirmasi Hapus</h3>
          <p class="text-[11px] text-[#75758a]">Tindakan ini bersifat permanen</p>
        </div>
      </div>

      <div class="text-xs text-[#616161] space-y-2">
        <p>
          Apakah Anda yakin ingin menghapus {deleteTarget.type === 'menu' ? 'menu' : deleteTarget.type === 'kategori' ? 'kategori' : 'bahan baku'} berikut:
        </p>
        <div class="p-3 rounded-xl bg-[#eeece7]/50 border border-[#d9d9dd] font-medium text-[#17171c]">
          {deleteTarget.name}
        </div>

        {#if deleteErrorMessage}
          <div class="p-2.5 rounded-xl bg-[#ffefef] border border-[#e5484d]/30 text-[#e5484d] text-[11px] leading-relaxed">
            {deleteErrorMessage}
          </div>
        {/if}
      </div>

      <div class="pt-2 flex gap-2.5">
        <button
          type="button"
          onclick={() => { deleteTarget = null; deleteErrorMessage = null; }}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#eeece7] cursor-pointer"
        >
          Batal
        </button>
        {#if !deleteErrorMessage}
          <button
            type="button"
            onclick={executeDelete}
            class="flex-1 py-2 text-xs font-medium bg-[#e5484d] hover:bg-[#c93b40] text-white rounded-full cursor-pointer"
          >
            Hapus Permanen
          </button>
        {/if}
      </div>
    </div>
  </div>
{/if}
