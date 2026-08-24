<script lang="ts">
  import {
    Search,
    Plus,
    X,
    Package,
    Layers,
    ArrowRight,
    Edit2,
    Trash2,
    SlidersHorizontal,
    AlertTriangle
  } from 'lucide-svelte';
  import type { Product, Category, RawMaterial, StockAdjustmentReason } from '../../../types/pos';
  import { formatCurrency } from '../../../services/printer-service';

  interface Props {
    products: Product[];
    categories: Category[];
    onToggleProductActive: (productId: string) => void;
    onAddNewProduct: (product: Product) => void;
  }

  let { products = [], categories = [], onToggleProductActive, onAddNewProduct }: Props = $props();

  let activeSubTab = $state<'bahan' | 'menu'>('bahan');
  let searchQuery = $state('');
  let selectedCategory = $state('cat-all');

  // Menu Modal State
  let isAddMenuModalOpen = $state(false);
  let newMenuName = $state('');
  let newMenuCategory = $state('cat-coffee');
  let newMenuPrice = $state(25000);
  let newMenuDesc = $state('');

  // Raw Materials in POS (Bar & Storage) - Inspired by Flux
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

  // Add / Edit Raw Material Modal State
  let isAddMaterialModalOpen = $state(false);
  let editingMaterialId = $state<string | null>(null);
  let materialFormName = $state('');
  let materialFormCategory = $state('cat-dairy');
  let materialFormStock = $state(10);
  let materialFormUnit = $state('liter');
  let materialFormMinAlert = $state(5);

  // Adjust Stock (Opname) Modal State
  let adjustingMaterial = $state<(RawMaterial & { stock_previous_day?: number }) | null>(null);
  let physicalCountInput = $state(0);
  let selectedReason = $state<StockAdjustmentReason>('STOCK_TAKE');
  let adjustmentNotes = $state('');

  // Delete Confirmation State
  let deleteMaterialId = $state<string | null>(null);

  // Raw Material Categories
  const rawMaterialCategories = [
    { id: 'cat-dairy', name: 'Dairy & Milk' },
    { id: 'cat-beans', name: 'Coffee Beans & Powder' },
    { id: 'cat-syrup', name: 'Syrup & Flavor' },
    { id: 'cat-packaging', name: 'Packaging & Cups' },
    { id: 'cat-other', name: 'Lainnya' },
  ];

  let filteredProducts = $derived(
    products.filter((p) => {
      const matchCat = selectedCategory === 'cat-all' || p.category_id === selectedCategory;
      const matchSearch =
        searchQuery.trim() === '' ||
        p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (p.description && p.description.toLowerCase().includes(searchQuery.toLowerCase()));
      return matchCat && matchSearch;
    })
  );

  let filteredRawMaterials = $derived(
    rawMaterials.filter((m) => {
      return (
        searchQuery.trim() === '' ||
        m.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (m.category_name && m.category_name.toLowerCase().includes(searchQuery.toLowerCase()))
      );
    })
  );

  function handleSaveProduct() {
    if (!newMenuName.trim() || newMenuPrice <= 0) return;
    const newProduct: Product = {
      id: `p-${Date.now()}`,
      category_id: newMenuCategory,
      name: newMenuName.trim(),
      base_price: newMenuPrice,
      description: newMenuDesc.trim() || undefined,
      is_active: true,
    };
    onAddNewProduct(newProduct);
    isAddMenuModalOpen = false;
    newMenuName = '';
    newMenuDesc = '';
  }

  function handleOpenAddMaterial() {
    editingMaterialId = null;
    materialFormName = '';
    materialFormCategory = 'cat-dairy';
    materialFormStock = 10;
    materialFormUnit = 'liter';
    materialFormMinAlert = 5;
    isAddMaterialModalOpen = true;
  }

  function handleOpenEditMaterial(mat: RawMaterial) {
    editingMaterialId = mat.id;
    materialFormName = mat.name;
    materialFormCategory = mat.category_id;
    materialFormStock = mat.current_stock;
    materialFormUnit = mat.unit;
    materialFormMinAlert = mat.min_stock_alert;
    isAddMaterialModalOpen = true;
  }

  function handleSaveMaterial() {
    if (!materialFormName.trim() || materialFormStock < 0) return;
    const catObj = rawMaterialCategories.find((c) => c.id === materialFormCategory);
    const categoryName = catObj ? catObj.name : 'Bahan Baku';

    if (editingMaterialId) {
      rawMaterials = rawMaterials.map((m) => {
        if (m.id === editingMaterialId) {
          return {
            ...m,
            name: materialFormName.trim(),
            category_id: materialFormCategory,
            category_name: categoryName,
            current_stock: Number(materialFormStock),
            unit: materialFormUnit,
            min_stock_alert: Number(materialFormMinAlert),
          };
        }
        return m;
      });
    } else {
      const newMat: RawMaterial & { stock_previous_day?: number } = {
        id: `raw-pos-${Date.now()}`,
        name: materialFormName.trim(),
        category_id: materialFormCategory,
        category_name: categoryName,
        stock_previous_day: Number(materialFormStock),
        current_stock: Number(materialFormStock),
        unit: materialFormUnit,
        min_stock_alert: Number(materialFormMinAlert),
        last_adjusted_at: new Date().toISOString().replace('T', ' ').substring(0, 16),
      };
      rawMaterials = [newMat, ...rawMaterials];
    }
    isAddMaterialModalOpen = false;
  }

  function handleDeleteMaterial() {
    if (!deleteMaterialId) return;
    rawMaterials = rawMaterials.filter((m) => m.id !== deleteMaterialId);
    deleteMaterialId = null;
  }

  function handleSaveStockAdjust() {
    if (!adjustingMaterial) return;
    const updatedCount = Math.max(0, physicalCountInput);
    adjustingMaterial.stock_previous_day = adjustingMaterial.current_stock;
    adjustingMaterial.current_stock = updatedCount;
    adjustingMaterial.last_adjusted_at = new Date().toISOString().replace('T', ' ').substring(0, 16);
    rawMaterials = [...rawMaterials];
    adjustingMaterial = null;
    adjustmentNotes = '';
  }
</script>

<div class="flex-1 bg-[#eeece7]/30 p-4 sm:p-6 md:p-8 overflow-y-auto space-y-6 font-sans">
  <!-- Header Bar -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h2 class="text-xl font-medium text-[#212121] tracking-tight">Manajemen Bahan Baku &amp; Stok Opname</h2>
      <p class="text-xs text-[#616161] font-normal mt-0.5">Rekonsiliasi stok fisik harian bar, opname bahan closing, dan toggle menu kasir</p>
    </div>

    <div class="flex items-center gap-2.5 self-start sm:self-auto">
      {#if activeSubTab === 'bahan'}
        <button
          type="button"
          onclick={handleOpenAddMaterial}
          class="bg-[#17171c] hover:bg-black text-white px-4 py-2 text-xs font-medium rounded-full flex items-center gap-2 cursor-pointer transition-all shadow-none"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Bahan Baku</span>
        </button>
      {:else}
        <button
          type="button"
          onclick={() => (isAddMenuModalOpen = true)}
          class="bg-[#17171c] hover:bg-black text-white px-4 py-2 text-xs font-medium rounded-full flex items-center gap-2 cursor-pointer transition-all shadow-none"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Menu Baru</span>
        </button>
      {/if}
    </div>
  </div>

  <!-- Segmented Subtabs (Inspired by Flux) -->
  <div class="bg-white border border-[#d9d9dd] rounded-[20px] p-1.5 flex gap-2 w-fit">
    <button
      type="button"
      onclick={() => { activeSubTab = 'bahan'; searchQuery = ''; }}
      class={`px-4 py-2 text-xs font-medium rounded-full transition-all cursor-pointer flex items-center gap-2 ${
        activeSubTab === 'bahan' ? 'bg-[#17171c] text-white' : 'text-[#616161] hover:text-[#212121]'
      }`}
    >
      <Layers class="w-3.5 h-3.5" />
      <span>Bahan Baku &amp; Stok Bar ({rawMaterials.length})</span>
    </button>

    <button
      type="button"
      onclick={() => { activeSubTab = 'menu'; searchQuery = ''; }}
      class={`px-4 py-2 text-xs font-medium rounded-full transition-all cursor-pointer flex items-center gap-2 ${
        activeSubTab === 'menu' ? 'bg-[#17171c] text-white' : 'text-[#616161] hover:text-[#212121]'
      }`}
    >
      <Package class="w-3.5 h-3.5" />
      <span>Menu Jualan POS ({products.length})</span>
    </button>
  </div>

  <!-- Search & Filter Controls -->
  <div class="bg-white border border-[#d9d9dd] rounded-[20px] p-4 flex flex-wrap gap-3 items-center justify-between shadow-none">
    <div class="relative flex-1 min-w-[240px]">
      <Search class="w-4 h-4 text-[#93939f] absolute left-3.5 top-1/2 -translate-y-1/2" />
      <input
        type="text"
        bind:value={searchQuery}
        placeholder={activeSubTab === 'bahan' ? "Cari nama bahan baku bar..." : "Cari nama menu atau deskripsi..."}
        class="w-full bg-[#eeece7]/40 pl-10 pr-4 py-2 text-xs rounded-full border border-[#d9d9dd] placeholder-[#93939f] text-[#212121] focus:border-[#17171c] focus:outline-hidden transition-all"
      />
    </div>

    {#if activeSubTab === 'menu'}
      <div class="flex gap-1 overflow-x-auto bg-[#eeece7]/60 p-1 rounded-full border border-[#d9d9dd]">
        {#each categories as cat}
          <button
            type="button"
            onclick={() => (selectedCategory = cat.id)}
            class={`px-3.5 py-1 text-xs font-mono rounded-full transition-all cursor-pointer ${
              selectedCategory === cat.id
                ? 'bg-[#17171c] text-white font-medium shadow-none'
                : 'text-[#616161] hover:text-[#212121]'
            }`}
          >
            {cat.name}
          </button>
        {/each}
      </div>
    {/if}
  </div>

  <!-- TAB 1: BAHAN BAKU & STOCK OPNAME (FLUX STYLE) -->
  {#if activeSubTab === 'bahan'}
    {#if filteredRawMaterials.length === 0}
      <div class="bg-white border border-[#d9d9dd] rounded-[20px] p-12 text-center space-y-2">
        <Layers class="w-8 h-8 text-[#93939f] mx-auto opacity-50" />
        <h3 class="text-sm font-medium text-[#212121]">Tidak Ada Bahan Baku</h3>
        <p class="text-xs text-[#75758a]">Data bahan baku bar tidak ditemukan. Klik tombol Tambah Bahan Baku di atas.</p>
      </div>
    {:else}
      <div class="bg-white border border-[#d9d9dd] rounded-[20px] overflow-hidden shadow-none">
        <table class="w-full text-xs text-left border-collapse">
          <thead>
            <tr class="bg-[#eeece7]/50 border-b border-[#d9d9dd] text-[#75758a] font-mono uppercase text-[10px]">
              <th class="py-3.5 px-4 sm:px-6">Nama Bahan</th>
              <th class="py-3.5 px-4">Kategori</th>
              <th class="py-3.5 px-4 text-right">Stok Kemarin</th>
              <th class="py-3.5 px-4 text-right">Stok Hari Ini</th>
              <th class="py-3.5 px-4 text-center">Satuan</th>
              <th class="py-3.5 px-4 text-center">Status</th>
              <th class="py-3.5 px-4 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#d9d9dd]">
            {#each filteredRawMaterials as mat}
              {@const isLow = mat.current_stock <= mat.min_stock_alert}
              <tr class="hover:bg-[#eeece7]/20 transition-all">
                <td class="py-3.5 px-4 sm:px-6">
                  <div class="font-medium text-[#212121]">{mat.name}</div>
                  {#if mat.last_adjusted_at}
                    <div class="text-[10px] text-[#93939f] font-mono mt-0.5">Opname: {mat.last_adjusted_at}</div>
                  {/if}
                </td>
                <td class="py-3.5 px-4 text-[#616161]">
                  <span class="bg-[#eeece7] px-2 py-0.5 rounded-full font-mono text-[10px] text-[#212121]">
                    {mat.category_name || 'Umum'}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-right font-mono text-[#75758a]">
                  {mat.stock_previous_day ?? mat.current_stock}
                </td>
                <td class="py-3.5 px-4 text-right font-mono font-medium text-sm text-[#17171c]">
                  {mat.current_stock}
                </td>
                <td class="py-3.5 px-4 text-center font-mono text-[#616161]">
                  {mat.unit}
                </td>
                <td class="py-3.5 px-4 text-center">
                  <span class={`text-[10px] font-mono px-2 py-0.5 rounded-full font-medium ${
                    isLow ? 'bg-[#ffefef] text-[#e5484d]' : 'bg-[#edfce9] text-[#003c33]'
                  }`}>
                    {isLow ? `Menipis (< ${mat.min_stock_alert})` : 'Aman'}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <button
                      type="button"
                      onclick={() => {
                        adjustingMaterial = mat;
                        physicalCountInput = mat.current_stock;
                        selectedReason = 'STOCK_TAKE';
                        adjustmentNotes = '';
                      }}
                      class="px-3 py-1 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full transition-all cursor-pointer flex items-center gap-1"
                      title="Sesuaikan Stok Opname"
                    >
                      <SlidersHorizontal class="w-3 h-3" />
                      <span>Sesuaikan</span>
                    </button>

                    <button
                      type="button"
                      onclick={() => handleOpenEditMaterial(mat)}
                      class="p-1.5 border border-[#d9d9dd] rounded-full text-[#616161] hover:text-[#212121] hover:bg-[#eeece7] transition-all cursor-pointer"
                      title="Edit Bahan Baku"
                    >
                      <Edit2 class="w-3.5 h-3.5" />
                    </button>

                    <button
                      type="button"
                      onclick={() => (deleteMaterialId = mat.id)}
                      class="p-1.5 border border-[#ffefef] text-[#e5484d] hover:bg-[#ffefef] rounded-full transition-all cursor-pointer"
                      title="Hapus Bahan Baku"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    {/if}
  {/if}

  <!-- TAB 2: MENU JUALAN POS -->
  {#if activeSubTab === 'menu'}
    {#if filteredProducts.length === 0}
      <div class="bg-white border border-[#d9d9dd] rounded-[20px] p-12 text-center space-y-2">
        <Package class="w-8 h-8 text-[#93939f] mx-auto opacity-50" />
        <h3 class="text-sm font-medium text-[#212121]">Tidak Ada Menu Ditemukan</h3>
        <p class="text-xs text-[#75758a]">Coba ubah kata kunci pencarian atau kategori filter.</p>
      </div>
    {:else}
      <div class="bg-white border border-[#d9d9dd] rounded-[20px] overflow-hidden shadow-none">
        <table class="w-full text-xs text-left border-collapse">
          <thead>
            <tr class="bg-[#eeece7]/50 border-b border-[#d9d9dd] text-[#75758a] font-mono uppercase text-[10px]">
              <th class="py-3.5 px-4 sm:px-6">Nama Menu</th>
              <th class="py-3.5 px-4 text-right">Harga Jual</th>
              <th class="py-3.5 px-4 text-center">Status Jualan</th>
              <th class="py-3.5 px-4 text-center">Aksi Cepat</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#d9d9dd]">
            {#each filteredProducts as p}
              <tr class="hover:bg-[#eeece7]/20 transition-all">
                <td class="py-3.5 px-4 sm:px-6">
                  <div class="font-medium text-[#212121]">{p.name}</div>
                  {#if p.description}
                    <div class="text-[10px] text-[#75758a] truncate max-w-xs">{p.description}</div>
                  {/if}
                </td>
                <td class="py-3.5 px-4 text-right font-mono font-medium text-[#17171c]">
                  {formatCurrency(p.base_price)}
                </td>
                <td class="py-3.5 px-4 text-center">
                  <span class={`text-[10px] font-mono px-2 py-0.5 rounded-full font-medium ${
                    p.is_active ? 'bg-[#edfce9] text-[#003c33]' : 'bg-[#ffefef] text-[#e5484d]'
                  }`}>
                    {p.is_active ? 'Ready' : 'Sold Out'}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <button
                    type="button"
                    onclick={() => onToggleProductActive(p.id)}
                    class={`px-3 py-1 text-xs font-medium rounded-full border transition-all cursor-pointer ${
                      p.is_active
                        ? 'border-[#d9d9dd] text-[#616161] hover:bg-[#ffefef] hover:text-[#e5484d]'
                        : 'border-[#003c33] bg-[#edfce9] text-[#003c33]'
                    }`}
                  >
                    {p.is_active ? 'Set Sold Out' : 'Aktifkan'}
                  </button>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    {/if}
  {/if}
</div>

<!-- Modal Tambah / Edit Bahan Baku (Flux Style) -->
{#if isAddMaterialModalOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] w-full max-w-md p-6 space-y-4 shadow-2xl">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3">
        <h3 class="text-base font-medium text-[#212121]">
          {editingMaterialId ? 'Edit Bahan Baku' : 'Tambah Bahan Baku Baru'}
        </h3>
        <button type="button" onclick={() => (isAddMaterialModalOpen = false)} class="p-1 text-[#616161] hover:text-[#212121] cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="space-y-3 text-xs">
        <div class="space-y-1">
          <label for="material-cat-select" class="font-medium text-[#212121]">Kategori Bahan</label>
          <select
            id="material-cat-select"
            bind:value={materialFormCategory}
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden cursor-pointer"
          >
            {#each rawMaterialCategories as cat}
              <option value={cat.id}>{cat.name}</option>
            {/each}
          </select>
        </div>

        <div class="space-y-1">
          <label for="material-name-input" class="font-medium text-[#212121]">Nama Bahan Baku</label>
          <input
            id="material-name-input"
            type="text"
            bind:value={materialFormName}
            placeholder="Contoh: Fresh Milk Diamond 1L"
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label for="material-stock-input" class="font-medium text-[#212121]">Stok Awal</label>
            <input
              id="material-stock-input"
              type="number"
              min="0"
              bind:value={materialFormStock}
              class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>

          <div class="space-y-1">
            <label for="material-unit-select" class="font-medium text-[#212121]">Satuan</label>
            <select
              id="material-unit-select"
              bind:value={materialFormUnit}
              class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden cursor-pointer"
            >
              <option value="liter">liter</option>
              <option value="kg">kg</option>
              <option value="gram">gram</option>
              <option value="ml">ml</option>
              <option value="botol">botol</option>
              <option value="pack">pack</option>
              <option value="pcs">pcs</option>
            </select>
          </div>
        </div>

        <div class="space-y-1">
          <label for="material-min-input" class="font-medium text-[#212121]">Batas Stok Menipis</label>
          <input
            id="material-min-input"
            type="number"
            min="1"
            bind:value={materialFormMinAlert}
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
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
          {editingMaterialId ? 'Simpan Perubahan' : 'Tambah Bahan'}
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Modal Opname / Hitung Fisik Bahan POS (Flux Style) -->
{#if adjustingMaterial}
  {@const diff = physicalCountInput - adjustingMaterial.current_stock}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] w-full max-w-md p-6 space-y-4 shadow-2xl">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3">
        <div>
          <div class="text-[10px] font-mono uppercase text-[#75758a]">Stock Opname Bar / Kiosk</div>
          <h3 class="text-base font-medium text-[#212121] mt-0.5">{adjustingMaterial.name}</h3>
        </div>
        <button type="button" onclick={() => (adjustingMaterial = null)} class="p-1 text-[#616161] hover:text-[#212121] cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="p-4 bg-[#eeece7]/40 border border-[#d9d9dd] rounded-[16px] space-y-3">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-[10px] font-mono uppercase text-[#75758a]">Stok Sistem</div>
            <div class="text-lg font-mono font-medium text-[#212121]">{adjustingMaterial.current_stock} {adjustingMaterial.unit}</div>
          </div>
          <ArrowRight class="w-4 h-4 text-[#93939f]" />
          <div class="text-right">
            <div class="text-[10px] font-mono uppercase text-[#75758a]">Hitung Fisik</div>
            <div class="text-lg font-mono font-medium text-[#17171c]">{physicalCountInput} {adjustingMaterial.unit}</div>
          </div>
        </div>

        <div class="pt-2 border-t border-[#d9d9dd] flex items-center justify-between text-xs">
          <span class="text-[#75758a]">Selisih Stok:</span>
          <span class={`font-mono font-medium text-[11px] px-2.5 py-0.5 rounded-full ${
            diff > 0
              ? 'bg-[#edfce9] text-[#003c33]'
              : diff < 0
              ? 'bg-[#ffefef] text-[#e5484d]'
              : 'bg-[#eeece7] text-[#616161]'
          }`}>
            {diff > 0 ? `+${diff} (Surplus)` : diff < 0 ? `${diff} (Defisit / Selisih)` : '0 (Sesuai Sistem)'}
          </span>
        </div>
      </div>

      <div class="space-y-3 text-xs">
        <div class="space-y-1">
          <label for="physical-count-input" class="font-medium text-[#212121]">Jumlah Riil Aktual ({adjustingMaterial.unit})</label>
          <div class="flex items-center gap-2">
            <button
              type="button"
              onclick={() => (physicalCountInput = Math.max(0, physicalCountInput - 1))}
              class="w-10 h-10 rounded-[12px] bg-[#eeece7] hover:bg-[#d9d9dd] text-[#17171c] font-mono font-bold text-base flex items-center justify-center cursor-pointer transition-colors"
            >
              -
            </button>
            <input
              id="physical-count-input"
              type="number"
              min="0"
              step="any"
              bind:value={physicalCountInput}
              class="flex-1 px-3.5 py-2 text-center bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] font-mono text-base font-medium text-[#212121] focus:border-[#17171c] focus:outline-hidden"
            />
            <button
              type="button"
              onclick={() => (physicalCountInput = physicalCountInput + 1)}
              class="w-10 h-10 rounded-[12px] bg-[#eeece7] hover:bg-[#d9d9dd] text-[#17171c] font-mono font-bold text-base flex items-center justify-center cursor-pointer transition-colors"
            >
              +
            </button>
          </div>
        </div>

        <div class="space-y-1">
          <label for="opname-reason-select" class="font-medium text-[#212121]">Alasan Opname / Penyesuaian</label>
          <select
            id="opname-reason-select"
            bind:value={selectedReason}
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden cursor-pointer"
          >
            <option value="STOCK_TAKE">Opname Fisik (stock_take)</option>
            <option value="DAMAGED">Barang Rusak (damaged)</option>
            <option value="EXPIRED">Bahan Kedaluwarsa (expired)</option>
            <option value="RESTOCK">Restock / Masuk Baru (restock)</option>
            <option value="WASTE">Dibuang / Basi (waste)</option>
            <option value="OTHER">Alasan Lainnya (other)</option>
          </select>
        </div>

        {#if selectedReason === 'OTHER'}
          <div class="space-y-1">
            <label for="opname-notes-input" class="font-medium text-[#212121]">Catatan Alasan</label>
            <input
              id="opname-notes-input"
              type="text"
              bind:value={adjustmentNotes}
              placeholder="Jelaskan alasan penyesuaian..."
              class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>
        {/if}
      </div>

      <div class="pt-2 flex gap-3">
        <button
          type="button"
          onclick={() => (adjustingMaterial = null)}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#eeece7] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSaveStockAdjust}
          class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer"
        >
          Simpan Opname
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Modal Konfirmasi Hapus Bahan Baku -->
{#if deleteMaterialId}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] w-full max-w-sm p-6 space-y-4 shadow-2xl">
      <div class="w-12 h-12 rounded-full bg-[#ffefef] text-[#e5484d] flex items-center justify-center mx-auto">
        <AlertTriangle class="w-6 h-6" />
      </div>
      <div class="text-center space-y-1">
        <h3 class="text-base font-medium text-[#212121]">Hapus Bahan Baku?</h3>
        <p class="text-xs text-[#75758a]">Bahan baku ini akan dihapus dari daftar inventaris bar POS.</p>
      </div>
      <div class="flex gap-3 pt-2">
        <button
          type="button"
          onclick={() => (deleteMaterialId = null)}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#eeece7] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleDeleteMaterial}
          class="flex-1 py-2 text-xs font-medium bg-[#e5484d] hover:bg-[#c93b40] text-white rounded-full cursor-pointer"
        >
          Ya, Hapus
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Modal Tambah Menu Jualan Baru -->
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
          <label for="new-menu-name-input" class="font-medium text-[#212121]">Nama Menu</label>
          <input
            id="new-menu-name-input"
            type="text"
            bind:value={newMenuName}
            placeholder="Contoh: Es Kopi Susu Aren"
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1">
          <label for="new-menu-cat-select" class="font-medium text-[#212121]">Kategori</label>
          <select
            id="new-menu-cat-select"
            bind:value={newMenuCategory}
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden cursor-pointer"
          >
            {#each categories.filter((c) => c.id !== 'cat-all') as cat}
              <option value={cat.id}>{cat.name}</option>
            {/each}
          </select>
        </div>

        <div class="space-y-1">
          <label for="new-menu-price-input" class="font-medium text-[#212121]">Harga Dasar (IDR)</label>
          <input
            id="new-menu-price-input"
            type="number"
            bind:value={newMenuPrice}
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1">
          <label for="new-menu-desc-textarea" class="font-medium text-[#212121]">Deskripsi Singkat</label>
          <textarea
            id="new-menu-desc-textarea"
            bind:value={newMenuDesc}
            rows="2"
            placeholder="Deskripsi menu..."
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
          onclick={handleSaveProduct}
          class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer"
        >
          Simpan Menu
        </button>
      </div>
    </div>
  </div>
{/if}
