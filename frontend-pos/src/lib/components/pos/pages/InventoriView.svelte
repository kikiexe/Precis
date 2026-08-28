<script lang="ts">
  import {
    Search,
    Calendar,
    Plus,
    FolderPlus,
    SlidersHorizontal,
    X,
    Check,
    AlertCircle,
    Trash2,
    Edit2,
  } from 'lucide-svelte';
  import type { RawMaterial } from '../../../types/pos';

  interface RawCategory {
    id: string;
    name: string;
  }

  interface Props {
    rawMaterials?: RawMaterial[];
    onUpdateMaterials?: (materials: RawMaterial[]) => void;
  }

  let { rawMaterials: initialMaterials = [], onUpdateMaterials }: Props = $props();

  // State Tanggal Filter (Default Hari Ini)
  let selectedDate = $state(new Date().toISOString().substring(0, 10));
  let searchQuery = $state('');
  let selectedCategoryId = $state('ALL');

  // Master Categories
  let categories = $state<RawCategory[]>([
    { id: 'cat-dairy', name: 'Dairy & Susu' },
    { id: 'cat-beans', name: 'Biji Kopi & Powder' },
    { id: 'cat-syrup', name: 'Sirup & Flavor' },
    { id: 'cat-packaging', name: 'Cup & Packaging' },
    { id: 'cat-other', name: 'Lainnya' },
  ]);

  const defaultMaterials: RawMaterial[] = [
    {
      id: 'raw-1',
      name: 'Fresh Milk Diamond 1L',
      category_id: 'cat-dairy',
      category_name: 'Dairy & Susu',
      stock_previous_day: 24,
      current_stock: 18,
      stock_used_today: 6,
      min_stock_alert: 10,
      unit: 'liter',
      last_adjusted_at: '2026-08-28 07:30',
    },
    {
      id: 'raw-2',
      name: 'Oatside Barista Oat Milk 1L',
      category_id: 'cat-dairy',
      category_name: 'Dairy & Susu',
      stock_previous_day: 12,
      current_stock: 6,
      stock_used_today: 6,
      min_stock_alert: 8,
      unit: 'liter',
      last_adjusted_at: '2026-08-28 08:00',
    },
    {
      id: 'raw-3',
      name: 'Biji Kopi House Blend (1kg)',
      category_id: 'cat-beans',
      category_name: 'Biji Kopi & Powder',
      stock_previous_day: 15,
      current_stock: 12,
      stock_used_today: 3,
      min_stock_alert: 5,
      unit: 'kg',
      last_adjusted_at: '2026-08-28 06:45',
    },
    {
      id: 'raw-4',
      name: 'Sirup Monin Caramel 700ml',
      category_id: 'cat-syrup',
      category_name: 'Sirup & Flavor',
      stock_previous_day: 3,
      current_stock: 2,
      stock_used_today: 1,
      min_stock_alert: 3,
      unit: 'botol',
      last_adjusted_at: '2026-08-28 09:30',
    },
    {
      id: 'raw-5',
      name: 'Paper Cup Hot 8oz (50 pcs)',
      category_id: 'cat-packaging',
      category_name: 'Cup & Packaging',
      stock_previous_day: 20,
      current_stock: 15,
      stock_used_today: 5,
      min_stock_alert: 8,
      unit: 'pack',
      last_adjusted_at: '2026-08-28 10:00',
    },
    {
      id: 'raw-6',
      name: 'Bubuk Matcha Uji Premium 1kg',
      category_id: 'cat-beans',
      category_name: 'Biji Kopi & Powder',
      stock_previous_day: 5,
      current_stock: 4,
      stock_used_today: 1,
      min_stock_alert: 2,
      unit: 'kg',
      last_adjusted_at: '2026-08-28 07:00',
    },
  ];

  // Master Raw Materials List
  let materials = $state<RawMaterial[]>(defaultMaterials);

  $effect(() => {
    if (initialMaterials && initialMaterials.length > 0) {
      materials = initialMaterials;
    }
  });

  // Modal States
  let isAddMaterialModalOpen = $state(false);
  let isCategoryModalOpen = $state(false);
  let adjustingMaterial = $state<RawMaterial | null>(null);
  let newCurrentStockInput = $state(0);

  // Form State: Add Raw Material
  let formName = $state('');
  let formCategoryId = $state('cat-dairy');
  let formUnit = $state('liter');
  let formInitialStock = $state(0);
  let formMinStock = $state(5);
  let formErrorMessage = $state('');

  // Form State: Category Management
  let newCategoryName = $state('');
  let editingCategoryId = $state<string | null>(null);
  let editingCategoryName = $state('');

  const standardUnits = [
    'liter',
    'ml',
    'kg',
    'gram',
    'pcs',
    'pack',
    'botol',
    'can',
    'box',
    'pouch',
  ];

  // Filtered List
  let filteredMaterials = $derived(
    materials.filter((m) => {
      const matchSearch =
        searchQuery.trim() === '' ||
        m.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (m.category_name && m.category_name.toLowerCase().includes(searchQuery.toLowerCase()));

      const matchCategory =
        selectedCategoryId === 'ALL' || m.category_id === selectedCategoryId;

      return matchSearch && matchCategory;
    })
  );

  // Actions: Sesuaikan Stok
  function handleOpenAdjustModal(mat: RawMaterial) {
    adjustingMaterial = mat;
    newCurrentStockInput = mat.current_stock;
  }

  function handleSaveAdjustStock() {
    if (!adjustingMaterial) return;
    const updated = Number(newCurrentStockInput);

    materials = materials.map((m) => {
      if (m.id === adjustingMaterial?.id) {
        const startStock = m.stock_previous_day ?? m.current_stock;
        const usedStock = Math.max(0, startStock - updated);
        return {
          ...m,
          current_stock: updated,
          stock_used_today: usedStock,
          last_adjusted_at: new Date().toISOString().replace('T', ' ').substring(0, 16),
        };
      }
      return m;
    });

    onUpdateMaterials?.(materials);
    adjustingMaterial = null;
  }

  // Actions: Tambah Bahan Baku Baru
  function handleOpenAddModal() {
    formName = '';
    formCategoryId = categories[0]?.id || 'cat-dairy';
    formUnit = 'liter';
    formInitialStock = 0;
    formMinStock = 5;
    formErrorMessage = '';
    isAddMaterialModalOpen = true;
  }

  function handleSaveNewMaterial(e: SubmitEvent) {
    e.preventDefault();
    formErrorMessage = '';

    if (!formName.trim()) {
      formErrorMessage = 'Nama bahan baku wajib diisi.';
      return;
    }
    if (!formUnit.trim()) {
      formErrorMessage = 'Satuan bahan baku wajib dipilih.';
      return;
    }
    if (formInitialStock === null || formInitialStock === undefined || isNaN(formInitialStock)) {
      formErrorMessage = 'Jumlah stok awal wajib diisi.';
      return;
    }

    const catObj = categories.find((c) => c.id === formCategoryId);

    const newMat: RawMaterial = {
      id: `raw-${Date.now()}`,
      name: formName.trim(),
      category_id: formCategoryId,
      category_name: catObj?.name || 'Umum',
      stock_previous_day: Number(formInitialStock),
      current_stock: Number(formInitialStock),
      stock_used_today: 0,
      min_stock_alert: Number(formMinStock) || 0,
      unit: formUnit.trim().toLowerCase(),
      last_adjusted_at: new Date().toISOString().replace('T', ' ').substring(0, 16),
    };

    materials = [newMat, ...materials];
    onUpdateMaterials?.(materials);
    isAddMaterialModalOpen = false;
  }

  // Actions: Kelola Kategori
  function handleAddCategory() {
    if (!newCategoryName.trim()) return;
    const newCat: RawCategory = {
      id: `cat-${Date.now()}`,
      name: newCategoryName.trim(),
    };
    categories = [...categories, newCat];
    newCategoryName = '';
  }

  function handleSaveEditCategory(id: string) {
    if (!editingCategoryName.trim()) return;
    categories = categories.map((c) => {
      if (c.id === id) {
        return { ...c, name: editingCategoryName.trim() };
      }
      return c;
    });
    // sync category names in materials
    materials = materials.map((m) => {
      if (m.category_id === id) {
        return { ...m, category_name: editingCategoryName.trim() };
      }
      return m;
    });
    editingCategoryId = null;
    editingCategoryName = '';
  }

  function handleDeleteCategory(id: string) {
    if (confirm('Hapus kategori ini?')) {
      categories = categories.filter((c) => c.id !== id);
      if (selectedCategoryId === id) selectedCategoryId = 'ALL';
    }
  }
</script>

<div class="flex-1 flex flex-col h-full bg-[#f4f6f9] overflow-hidden font-sans select-none">
  <!-- Top Bar -->
  <div class="h-14 bg-white border-b border-zinc-200 px-6 flex items-center justify-between shrink-0 shadow-2xs">
    <div class="flex items-center gap-3">
      <h1 class="text-base font-bold text-zinc-900 tracking-tight">Inventori Bahan Baku</h1>
      <span class="text-xs font-mono text-zinc-400">|</span>
      <!-- Date Picker (Per Hari & Riwayat Hari Sebelumnya) -->
      <div class="flex items-center gap-2 px-2.5 py-1 bg-zinc-50 border border-zinc-200 rounded-lg">
        <Calendar class="w-3.5 h-3.5 text-zinc-500" />
        <span class="text-[11px] font-semibold text-zinc-600">Tanggal:</span>
        <input
          type="date"
          bind:value={selectedDate}
          class="bg-transparent text-xs font-mono font-bold text-zinc-900 focus:outline-hidden cursor-pointer"
        />
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button
        type="button"
        onclick={() => (isCategoryModalOpen = true)}
        class="px-3 py-2 bg-white hover:bg-zinc-100 border border-zinc-200 text-zinc-800 rounded-xl text-xs font-semibold flex items-center gap-1.5 cursor-pointer shadow-2xs transition-all"
      >
        <FolderPlus class="w-3.5 h-3.5 text-zinc-600" />
        <span>Kelola Kategori</span>
      </button>

      <button
        type="button"
        onclick={handleOpenAddModal}
        class="px-3.5 py-2 bg-zinc-900 hover:bg-black text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 cursor-pointer shadow-2xs transition-all active:scale-[0.99]"
      >
        <Plus class="w-3.5 h-3.5" />
        <span>+ Tambah Bahan Baku</span>
      </button>
    </div>
  </div>

  <!-- Content Body -->
  <div class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto space-y-4">
    <div class="w-full max-w-7xl mx-auto space-y-3">
      
      <!-- Excel-like Toolbar: Search & Category Dropdown -->
      <div class="bg-white border border-zinc-200 rounded-xl p-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shadow-2xs">
        <div class="flex items-center gap-2 flex-1">
          <div class="relative flex-1 max-w-md">
            <Search class="w-4 h-4 text-zinc-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              bind:value={searchQuery}
              placeholder="Cari nama bahan baku, susu, sirup, beans..."
              class="w-full pl-9 pr-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg text-xs text-zinc-900 placeholder-zinc-400 focus:bg-white focus:border-zinc-900 focus:outline-hidden transition-all"
            />
            {#if searchQuery}
              <button
                type="button"
                onclick={() => (searchQuery = '')}
                class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-mono text-zinc-400 hover:text-zinc-700 cursor-pointer"
              >
                ✕
              </button>
            {/if}
          </div>

          <!-- Category Filter -->
          <select
            bind:value={selectedCategoryId}
            class="h-9 px-3 bg-zinc-50 border border-zinc-200 rounded-lg text-xs font-semibold text-zinc-700 cursor-pointer focus:bg-white focus:border-zinc-900 focus:outline-hidden"
          >
            <option value="ALL">Semua Kategori ({materials.length})</option>
            {#each categories as cat}
              <option value={cat.id}>{cat.name}</option>
            {/each}
          </select>
        </div>

        <div class="text-xs font-mono text-zinc-500 self-center">
          Total Bahan: <strong class="text-zinc-900">{filteredMaterials.length}</strong> Item
        </div>
      </div>

      <!-- Excel-like Spreadsheet Table -->
      <div class="bg-white border border-zinc-200 rounded-xl overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left border-collapse">
            <thead class="bg-zinc-100/90 border-b border-zinc-200 font-mono text-[11px] font-bold text-zinc-600 uppercase tracking-wider">
              <tr class="divide-x divide-zinc-200/80">
                <th class="py-3 px-3 w-12 text-center">No.</th>
                <th class="py-3 px-4">Nama Bahan Baku</th>
                <th class="py-3 px-4 w-40">Kategori</th>
                <th class="py-3 px-4 w-32 text-right bg-zinc-50">Stok Kemarin</th>
                <th class="py-3 px-4 w-32 text-right font-bold text-zinc-900">Stok Sekarang</th>
                <th class="py-3 px-4 w-32 text-right bg-red-50/40 text-red-800">Stok Terpakai</th>
                <th class="py-3 px-3 w-24 text-center">Satuan</th>
                <th class="py-3 px-4 w-36 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200/70 font-mono">
              {#if filteredMaterials.length === 0}
                <tr>
                  <td colspan="8" class="py-16 text-center text-zinc-400 font-sans">
                    <p class="text-sm font-semibold text-zinc-800">Tidak ada data bahan baku</p>
                    <p class="text-xs text-zinc-500 mt-0.5">Ubah kata kunci pencarian atau tambah bahan baku baru.</p>
                  </td>
                </tr>
              {:else}
                {#each filteredMaterials as mat, idx (mat.id)}
                  <tr class={`divide-x divide-zinc-200/60 transition-colors hover:bg-zinc-50/80 ${
                    idx % 2 === 1 ? 'bg-zinc-50/30' : 'bg-white'
                  }`}>
                    <!-- No -->
                    <td class="py-3 px-3 text-center text-zinc-400 text-[11px]">
                      {idx + 1}
                    </td>

                    <!-- Nama Bahan Baku -->
                    <td class="py-3 px-4 font-sans font-semibold text-zinc-900">
                      {mat.name}
                    </td>

                    <!-- Kategori -->
                    <td class="py-3 px-4 font-sans">
                      <span class="inline-block px-2.5 py-0.5 bg-zinc-100 text-zinc-700 rounded-md text-[11px] font-medium border border-zinc-200/60">
                        {mat.category_name || 'Umum'}
                      </span>
                    </td>

                    <!-- Stok Kemarin -->
                    <td class="py-3 px-4 text-right text-zinc-600 bg-zinc-50/50">
                      {mat.stock_previous_day ?? mat.current_stock}
                    </td>

                    <!-- Stok Sekarang -->
                    <td class="py-3 px-4 text-right font-bold text-zinc-900 text-xs">
                      {mat.current_stock}
                    </td>

                    <!-- Stok Terpakai -->
                    <td class="py-3 px-4 text-right font-bold text-red-700 bg-red-50/30">
                      {mat.stock_used_today ?? Math.max(0, (mat.stock_previous_day ?? mat.current_stock) - mat.current_stock)}
                    </td>

                    <!-- Satuan -->
                    <td class="py-3 px-3 text-center text-zinc-600 text-[11px]">
                      {mat.unit}
                    </td>

                    <!-- Aksi: Sesuaikan Stok -->
                    <td class="py-3 px-4 text-center font-sans">
                      <button
                        type="button"
                        onclick={() => handleOpenAdjustModal(mat)}
                        class="px-3 py-1.5 bg-zinc-900 hover:bg-black text-white rounded-lg text-[11px] font-semibold transition-all cursor-pointer shadow-2xs active:scale-95 flex items-center gap-1.5 mx-auto"
                      >
                        <SlidersHorizontal class="w-3 h-3" />
                        <span>Sesuaikan Stok</span>
                      </button>
                    </td>
                  </tr>
                {/each}
              {/if}
            </tbody>
          </table>
        </div>

        <!-- Summary Footer -->
        <div class="bg-zinc-50 border-t border-zinc-200 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs font-mono text-zinc-600">
          <div>
            Data inventori tanggal: <strong class="text-zinc-900">{selectedDate}</strong>
          </div>
          <div>
            Total Terdaftar: <strong class="text-zinc-900">{filteredMaterials.length}</strong> Bahan Baku
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Sesuaikan Stok Sekarang -->
{#if adjustingMaterial}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans select-none">
    <div class="bg-white border border-zinc-200 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-2xl animate-in zoom-in-95 duration-150">
      <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-zinc-100 text-zinc-900 flex items-center justify-center border border-zinc-200">
            <SlidersHorizontal class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-zinc-900">Sesuaikan Stok Fisik</h3>
            <p class="text-[11px] text-zinc-500 font-mono">{adjustingMaterial.name}</p>
          </div>
        </div>
        <button type="button" onclick={() => (adjustingMaterial = null)} class="text-zinc-400 hover:text-zinc-700 cursor-pointer p-1">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="space-y-3.5 text-xs">
        <div class="grid grid-cols-2 gap-3 p-3.5 bg-zinc-50 rounded-xl border border-zinc-200">
          <div>
            <div class="text-[10px] text-zinc-500 uppercase font-mono">Stok Sebelumnya</div>
            <div class="font-bold text-sm font-mono text-zinc-900 mt-0.5">
              {adjustingMaterial.current_stock} {adjustingMaterial.unit}
            </div>
          </div>
          <div>
            <div class="text-[10px] text-zinc-500 uppercase font-mono">Selisih Fisik</div>
            <div class={`font-bold text-sm font-mono mt-0.5 ${
              (newCurrentStockInput - adjustingMaterial.current_stock) === 0
                ? 'text-emerald-600'
                : (newCurrentStockInput - adjustingMaterial.current_stock) < 0
                ? 'text-red-600'
                : 'text-blue-600'
            }`}>
              {(newCurrentStockInput - adjustingMaterial.current_stock) > 0
                ? `+${newCurrentStockInput - adjustingMaterial.current_stock}`
                : (newCurrentStockInput - adjustingMaterial.current_stock)} {adjustingMaterial.unit}
            </div>
          </div>
        </div>

        <div class="space-y-1">
          <label for="input-adjust-stock" class="font-semibold text-zinc-900 block">
            Stok Sekarang (Fisik Aktual di Bar)
          </label>
          <div class="relative">
            <input
              id="input-adjust-stock"
              type="number"
              bind:value={newCurrentStockInput}
              step="any"
              class="w-full px-3.5 py-2.5 bg-zinc-50 border border-zinc-200 rounded-xl font-mono text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden text-center text-lg font-bold"
            />
            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-mono text-zinc-400 font-bold">
              {adjustingMaterial.unit}
            </span>
          </div>
        </div>
      </div>

      <div class="pt-2 flex gap-2.5">
        <button
          type="button"
          onclick={() => (adjustingMaterial = null)}
          class="flex-1 py-2.5 text-xs font-semibold border border-zinc-200 rounded-xl text-zinc-700 hover:bg-zinc-100 cursor-pointer transition-colors"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSaveAdjustStock}
          class="flex-1 py-2.5 text-xs font-semibold bg-zinc-900 hover:bg-black text-white rounded-xl flex items-center justify-center gap-1.5 cursor-pointer shadow-xs transition-all active:scale-[0.99]"
        >
          <span>Simpan Stok Sekarang</span>
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Modal: Tambah Bahan Baku Baru (Wajib Satuan & Jumlah Awal) -->
{#if isAddMaterialModalOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans select-none">
    <div class="bg-white border border-zinc-200 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-2xl animate-in zoom-in-95 duration-150">
      <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-zinc-100 text-zinc-900 flex items-center justify-center border border-zinc-200">
            <Plus class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-zinc-900">Tambah Bahan Baku Baru</h3>
            <p class="text-[11px] text-zinc-500">Daftarkan bahan baku baru ke stok bar</p>
          </div>
        </div>
        <button type="button" onclick={() => (isAddMaterialModalOpen = false)} class="text-zinc-400 hover:text-zinc-700 cursor-pointer p-1">
          <X class="w-4 h-4" />
        </button>
      </div>

      {#if formErrorMessage}
        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs flex items-center gap-2">
          <AlertCircle class="w-4 h-4 shrink-0" />
          <span>{formErrorMessage}</span>
        </div>
      {/if}

      <form onsubmit={handleSaveNewMaterial} class="space-y-3.5 text-xs">
        <div class="space-y-1">
          <label for="form-mat-name" class="font-semibold text-zinc-900 block">
            Nama Bahan Baku <span class="text-red-500">*</span>
          </label>
          <input
            id="form-mat-name"
            type="text"
            bind:value={formName}
            placeholder="Contoh: Fresh Milk Diamond 1L"
            required
            class="w-full px-3.5 py-2 bg-zinc-50 border border-zinc-200 rounded-xl text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden text-xs"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label for="form-mat-category" class="font-semibold text-zinc-900 block">
              Kategori <span class="text-red-500">*</span>
            </label>
            <select
              id="form-mat-category"
              bind:value={formCategoryId}
              class="w-full px-3 py-2 bg-zinc-50 border border-zinc-200 rounded-xl text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden text-xs font-medium cursor-pointer"
            >
              {#each categories as cat}
                <option value={cat.id}>{cat.name}</option>
              {/each}
            </select>
          </div>

          <div class="space-y-1">
            <label for="form-mat-unit" class="font-semibold text-zinc-900 block">
              Satuan Takaran <span class="text-red-500">*</span>
            </label>
            <select
              id="form-mat-unit"
              bind:value={formUnit}
              class="w-full px-3 py-2 bg-zinc-50 border border-zinc-200 rounded-xl text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden text-xs font-medium cursor-pointer font-mono"
            >
              {#each standardUnits as u}
                <option value={u}>{u}</option>
              {/each}
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label for="form-mat-initial" class="font-semibold text-zinc-900 block">
              Jumlah Stok Awal <span class="text-red-500">*</span>
            </label>
            <input
              id="form-mat-initial"
              type="number"
              bind:value={formInitialStock}
              step="any"
              required
              class="w-full px-3.5 py-2 bg-zinc-50 border border-zinc-200 rounded-xl text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden text-xs font-mono font-bold"
            />
          </div>

          <div class="space-y-1">
            <label for="form-mat-min" class="font-semibold text-zinc-900 block">
              Batas Minimum Alert
            </label>
            <input
              id="form-mat-min"
              type="number"
              bind:value={formMinStock}
              step="any"
              class="w-full px-3.5 py-2 bg-zinc-50 border border-zinc-200 rounded-xl text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden text-xs font-mono"
            />
          </div>
        </div>

        <div class="pt-3 flex gap-2.5">
          <button
            type="button"
            onclick={() => (isAddMaterialModalOpen = false)}
            class="flex-1 py-2.5 text-xs font-semibold border border-zinc-200 rounded-xl text-zinc-700 hover:bg-zinc-100 cursor-pointer transition-colors"
          >
            Batal
          </button>
          <button
            type="submit"
            class="flex-1 py-2.5 text-xs font-semibold bg-zinc-900 hover:bg-black text-white rounded-xl flex items-center justify-center gap-1.5 cursor-pointer shadow-xs transition-all active:scale-[0.99]"
          >
            <span>Simpan Bahan Baku</span>
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<!-- Modal: Kelola Kategori Bahan Baku -->
{#if isCategoryModalOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans select-none">
    <div class="bg-white border border-zinc-200 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-2xl animate-in zoom-in-95 duration-150">
      <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-zinc-100 text-zinc-900 flex items-center justify-center border border-zinc-200">
            <FolderPlus class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-zinc-900">Kelola Kategori Bahan Baku</h3>
            <p class="text-[11px] text-zinc-500">Tambah, ubah, atau hapus kategori inventori</p>
          </div>
        </div>
        <button type="button" onclick={() => (isCategoryModalOpen = false)} class="text-zinc-400 hover:text-zinc-700 cursor-pointer p-1">
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Add Category Input -->
      <div class="flex items-center gap-2">
        <input
          type="text"
          bind:value={newCategoryName}
          placeholder="Nama kategori baru..."
          class="flex-1 px-3.5 py-2 bg-zinc-50 border border-zinc-200 rounded-xl text-xs text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden"
        />
        <button
          type="button"
          onclick={handleAddCategory}
          class="px-3.5 py-2 bg-zinc-900 hover:bg-black text-white text-xs font-semibold rounded-xl cursor-pointer shadow-2xs transition-all active:scale-95 shrink-0"
        >
          + Tambah
        </button>
      </div>

      <!-- Categories List -->
      <div class="max-h-60 overflow-y-auto divide-y divide-zinc-100 border border-zinc-200 rounded-xl">
        {#each categories as cat (cat.id)}
          <div class="p-3 flex items-center justify-between gap-2 hover:bg-zinc-50">
            {#if editingCategoryId === cat.id}
              <input
                type="text"
                bind:value={editingCategoryName}
                class="flex-1 px-2 py-1 bg-white border border-zinc-300 rounded text-xs text-zinc-900 focus:outline-hidden"
              />
              <div class="flex items-center gap-1">
                <button
                  type="button"
                  onclick={() => handleSaveEditCategory(cat.id)}
                  class="p-1 text-emerald-600 hover:bg-emerald-50 rounded"
                >
                  <Check class="w-4 h-4" />
                </button>
                <button
                  type="button"
                  onclick={() => (editingCategoryId = null)}
                  class="p-1 text-zinc-400 hover:bg-zinc-100 rounded"
                >
                  <X class="w-4 h-4" />
                </button>
              </div>
            {:else}
              <span class="text-xs font-medium text-zinc-900">{cat.name}</span>
              <div class="flex items-center gap-1">
                <button
                  type="button"
                  onclick={() => {
                    editingCategoryId = cat.id;
                    editingCategoryName = cat.name;
                  }}
                  class="p-1 text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 rounded"
                >
                  <Edit2 class="w-3.5 h-3.5" />
                </button>
                <button
                  type="button"
                  onclick={() => handleDeleteCategory(cat.id)}
                  class="p-1 text-zinc-400 hover:text-red-600 hover:bg-red-50 rounded"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            {/if}
          </div>
        {/each}
      </div>

      <div class="pt-2">
        <button
          type="button"
          onclick={() => (isCategoryModalOpen = false)}
          class="w-full py-2.5 text-xs font-semibold bg-zinc-100 hover:bg-zinc-200 text-zinc-800 rounded-xl cursor-pointer transition-colors"
        >
          Selesai
        </button>
      </div>
    </div>
  </div>
{/if}
