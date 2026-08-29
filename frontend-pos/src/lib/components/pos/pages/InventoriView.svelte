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

      const matchCategory = selectedCategoryId === 'ALL' || m.category_id === selectedCategoryId;

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

<div class="flex h-full flex-1 flex-col overflow-hidden bg-[#f4f6f9] font-sans select-none">
  <!-- Top Bar -->
  <div
    class="flex h-14 shrink-0 items-center justify-between border-b border-zinc-200 bg-white px-6 shadow-2xs"
  >
    <div class="flex items-center gap-3">
      <h1 class="text-base font-bold tracking-tight text-zinc-900">Inventori Bahan Baku</h1>
      <span class="font-mono text-xs text-zinc-400">|</span>
      <!-- Date Picker (Per Hari & Riwayat Hari Sebelumnya) -->
      <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1">
        <Calendar class="size-3.5 text-zinc-500" />
        <span class="text-[11px] font-semibold text-zinc-600">Tanggal:</span>
        <input
          type="date"
          bind:value={selectedDate}
          class="cursor-pointer bg-transparent font-mono text-xs font-bold text-zinc-900 focus:outline-hidden"
        />
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button
        type="button"
        onclick={() => (isCategoryModalOpen = true)}
        class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-800 shadow-2xs transition-all hover:bg-zinc-100"
      >
        <FolderPlus class="size-3.5 text-zinc-600" />
        <span>Kelola Kategori</span>
      </button>

      <button
        type="button"
        onclick={handleOpenAddModal}
        class="active:scale-0.99 flex cursor-pointer items-center gap-1.5 rounded-xl bg-zinc-900 px-3.5 py-2 text-xs font-semibold text-white shadow-2xs transition-all hover:bg-black"
      >
        <Plus class="size-3.5" />
        <span>+ Tambah Bahan Baku</span>
      </button>
    </div>
  </div>

  <!-- Content Body -->
  <div class="flex-1 space-y-4 overflow-y-auto p-4 sm:p-6 lg:p-8">
    <div class="mx-auto w-full max-w-7xl space-y-3">
      <!-- Excel-like Toolbar: Search & Category Dropdown -->
      <div
        class="flex flex-col items-stretch justify-between gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-2xs sm:flex-row sm:items-center"
      >
        <div class="flex flex-1 items-center gap-2">
          <div class="relative max-w-md flex-1">
            <Search class="absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-zinc-400" />
            <input
              type="text"
              bind:value={searchQuery}
              placeholder="Cari nama bahan baku, susu, sirup, beans..."
              class="w-full rounded-lg border border-zinc-200 bg-zinc-50 py-2 pr-4 pl-9 text-xs text-zinc-900 placeholder-zinc-400 transition-all focus:border-zinc-900 focus:bg-white focus:outline-hidden"
            />
            {#if searchQuery}
              <button
                type="button"
                onclick={() => (searchQuery = '')}
                class="absolute top-1/2 right-3 -translate-y-1/2 cursor-pointer font-mono text-xs text-zinc-400 hover:text-zinc-700"
              >
                ✕
              </button>
            {/if}
          </div>

          <!-- Category Filter -->
          <select
            bind:value={selectedCategoryId}
            class="h-9 cursor-pointer rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-xs font-semibold text-zinc-700 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
          >
            <option value="ALL">Semua Kategori ({materials.length})</option>
            {#each categories as cat}
              <option value={cat.id}>{cat.name}</option>
            {/each}
          </select>
        </div>

        <div class="self-center font-mono text-xs text-zinc-500">
          Total Bahan: <strong class="text-zinc-900">{filteredMaterials.length}</strong> Item
        </div>
      </div>

      <!-- Excel-like Spreadsheet Table -->
      <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xs">
        <div class="overflow-x-auto">
          <table class="w-full border-collapse text-left text-xs">
            <thead
              class="border-b border-zinc-200 bg-zinc-100/90 font-mono text-[11px] font-bold tracking-wider text-zinc-600 uppercase"
            >
              <tr class="divide-x divide-zinc-200/80">
                <th class="w-12 p-3 text-center">No.</th>
                <th class="px-4 py-3">Nama Bahan Baku</th>
                <th class="w-40 px-4 py-3">Kategori</th>
                <th class="w-32 bg-zinc-50 px-4 py-3 text-right">Stok Kemarin</th>
                <th class="w-32 px-4 py-3 text-right font-bold text-zinc-900">Stok Sekarang</th>
                <th class="w-32 bg-red-50/40 px-4 py-3 text-right text-red-800">Stok Terpakai</th>
                <th class="w-24 p-3 text-center">Satuan</th>
                <th class="w-36 px-4 py-3 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200/70 font-mono">
              {#if filteredMaterials.length === 0}
                <tr>
                  <td colspan="8" class="py-16 text-center font-sans text-zinc-400">
                    <p class="text-sm font-semibold text-zinc-800">Tidak ada data bahan baku</p>
                    <p class="mt-0.5 text-xs text-zinc-500">
                      Ubah kata kunci pencarian atau tambah bahan baku baru.
                    </p>
                  </td>
                </tr>
              {:else}
                {#each filteredMaterials as mat, idx (mat.id)}
                  <tr
                    class={`divide-x divide-zinc-200/60 transition-colors hover:bg-zinc-50/80 ${
                      idx % 2 === 1 ? 'bg-zinc-50/30' : 'bg-white'
                    }`}
                  >
                    <!-- No -->
                    <td class="p-3 text-center text-[11px] text-zinc-400">
                      {idx + 1}
                    </td>

                    <!-- Nama Bahan Baku -->
                    <td class="px-4 py-3 font-sans font-semibold text-zinc-900">
                      {mat.name}
                    </td>

                    <!-- Kategori -->
                    <td class="px-4 py-3 font-sans">
                      <span
                        class="inline-block rounded-md border border-zinc-200/60 bg-zinc-100 px-2.5 py-0.5 text-[11px] font-medium text-zinc-700"
                      >
                        {mat.category_name || 'Umum'}
                      </span>
                    </td>

                    <!-- Stok Kemarin -->
                    <td class="bg-zinc-50/50 px-4 py-3 text-right text-zinc-600">
                      {mat.stock_previous_day ?? mat.current_stock}
                    </td>

                    <!-- Stok Sekarang -->
                    <td class="px-4 py-3 text-right text-xs font-bold text-zinc-900">
                      {mat.current_stock}
                    </td>

                    <!-- Stok Terpakai -->
                    <td class="bg-red-50/30 px-4 py-3 text-right font-bold text-red-700">
                      {mat.stock_used_today ??
                        Math.max(
                          0,
                          (mat.stock_previous_day ?? mat.current_stock) - mat.current_stock
                        )}
                    </td>

                    <!-- Satuan -->
                    <td class="p-3 text-center text-[11px] text-zinc-600">
                      {mat.unit}
                    </td>

                    <!-- Aksi: Sesuaikan Stok -->
                    <td class="px-4 py-3 text-center font-sans">
                      <button
                        type="button"
                        onclick={() => handleOpenAdjustModal(mat)}
                        class="mx-auto flex cursor-pointer items-center gap-1.5 rounded-lg bg-zinc-900 px-3 py-1.5 text-[11px] font-semibold text-white shadow-2xs transition-all hover:bg-black active:scale-95"
                      >
                        <SlidersHorizontal class="size-3" />
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
        <div
          class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50 px-4 py-2.5 font-mono text-xs text-zinc-600"
        >
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
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 font-sans backdrop-blur-xs select-none"
  >
    <div
      class="animate-in zoom-in-95 w-full max-w-md space-y-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl duration-150"
    >
      <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div
            class="flex size-9 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-100 text-zinc-900"
          >
            <SlidersHorizontal class="size-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-zinc-900">Sesuaikan Stok Fisik</h3>
            <p class="font-mono text-[11px] text-zinc-500">{adjustingMaterial.name}</p>
          </div>
        </div>
        <button
          type="button"
          onclick={() => (adjustingMaterial = null)}
          class="cursor-pointer p-1 text-zinc-400 hover:text-zinc-700"
        >
          <X class="size-4" />
        </button>
      </div>

      <div class="space-y-3.5 text-xs">
        <div class="grid grid-cols-2 gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-3.5">
          <div>
            <div class="font-mono text-[10px] text-zinc-500 uppercase">Stok Sebelumnya</div>
            <div class="mt-0.5 font-mono text-sm font-bold text-zinc-900">
              {adjustingMaterial.current_stock}
              {adjustingMaterial.unit}
            </div>
          </div>
          <div>
            <div class="font-mono text-[10px] text-zinc-500 uppercase">Selisih Fisik</div>
            <div
              class={`mt-0.5 font-mono text-sm font-bold ${
                newCurrentStockInput - adjustingMaterial.current_stock === 0
                  ? 'text-emerald-600'
                  : newCurrentStockInput - adjustingMaterial.current_stock < 0
                    ? 'text-red-600'
                    : 'text-blue-600'
              }`}
            >
              {newCurrentStockInput - adjustingMaterial.current_stock > 0
                ? `+${newCurrentStockInput - adjustingMaterial.current_stock}`
                : newCurrentStockInput - adjustingMaterial.current_stock}
              {adjustingMaterial.unit}
            </div>
          </div>
        </div>

        <div class="space-y-1">
          <label for="input-adjust-stock" class="block font-semibold text-zinc-900">
            Stok Sekarang (Fisik Aktual di Bar)
          </label>
          <div class="relative">
            <input
              id="input-adjust-stock"
              type="number"
              bind:value={newCurrentStockInput}
              step="any"
              class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2.5 text-center font-mono text-lg font-bold text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
            />
            <span
              class="absolute top-1/2 right-4 -translate-y-1/2 font-mono text-xs font-bold text-zinc-400"
            >
              {adjustingMaterial.unit}
            </span>
          </div>
        </div>
      </div>

      <div class="flex gap-2.5 pt-2">
        <button
          type="button"
          onclick={() => (adjustingMaterial = null)}
          class="flex-1 cursor-pointer rounded-xl border border-zinc-200 py-2.5 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-100"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSaveAdjustStock}
          class="active:scale-0.99 flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-zinc-900 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
        >
          <span>Simpan Stok Sekarang</span>
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Modal: Tambah Bahan Baku Baru (Wajib Satuan & Jumlah Awal) -->
{#if isAddMaterialModalOpen}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 font-sans backdrop-blur-xs select-none"
  >
    <div
      class="animate-in zoom-in-95 w-full max-w-md space-y-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl duration-150"
    >
      <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div
            class="flex size-9 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-100 text-zinc-900"
          >
            <Plus class="size-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-zinc-900">Tambah Bahan Baku Baru</h3>
            <p class="text-[11px] text-zinc-500">Daftarkan bahan baku baru ke stok bar</p>
          </div>
        </div>
        <button
          type="button"
          onclick={() => (isAddMaterialModalOpen = false)}
          class="cursor-pointer p-1 text-zinc-400 hover:text-zinc-700"
        >
          <X class="size-4" />
        </button>
      </div>

      {#if formErrorMessage}
        <div
          class="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-700"
        >
          <AlertCircle class="size-4 shrink-0" />
          <span>{formErrorMessage}</span>
        </div>
      {/if}

      <form onsubmit={handleSaveNewMaterial} class="space-y-3.5 text-xs">
        <div class="space-y-1">
          <label for="form-mat-name" class="block font-semibold text-zinc-900">
            Nama Bahan Baku <span class="text-red-500">*</span>
          </label>
          <input
            id="form-mat-name"
            type="text"
            bind:value={formName}
            placeholder="Contoh: Fresh Milk Diamond 1L"
            required
            class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 text-xs text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label for="form-mat-category" class="block font-semibold text-zinc-900">
              Kategori <span class="text-red-500">*</span>
            </label>
            <select
              id="form-mat-category"
              bind:value={formCategoryId}
              class="w-full cursor-pointer rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs font-medium text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
            >
              {#each categories as cat}
                <option value={cat.id}>{cat.name}</option>
              {/each}
            </select>
          </div>

          <div class="space-y-1">
            <label for="form-mat-unit" class="block font-semibold text-zinc-900">
              Satuan Takaran <span class="text-red-500">*</span>
            </label>
            <select
              id="form-mat-unit"
              bind:value={formUnit}
              class="w-full cursor-pointer rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 font-mono text-xs font-medium text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
            >
              {#each standardUnits as u}
                <option value={u}>{u}</option>
              {/each}
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label for="form-mat-initial" class="block font-semibold text-zinc-900">
              Jumlah Stok Awal <span class="text-red-500">*</span>
            </label>
            <input
              id="form-mat-initial"
              type="number"
              bind:value={formInitialStock}
              step="any"
              required
              class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 font-mono text-xs font-bold text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
            />
          </div>

          <div class="space-y-1">
            <label for="form-mat-min" class="block font-semibold text-zinc-900">
              Batas Minimum Alert
            </label>
            <input
              id="form-mat-min"
              type="number"
              bind:value={formMinStock}
              step="any"
              class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 font-mono text-xs text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
            />
          </div>
        </div>

        <div class="flex gap-2.5 pt-3">
          <button
            type="button"
            onclick={() => (isAddMaterialModalOpen = false)}
            class="flex-1 cursor-pointer rounded-xl border border-zinc-200 py-2.5 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-100"
          >
            Batal
          </button>
          <button
            type="submit"
            class="active:scale-0.99 flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-zinc-900 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
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
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 font-sans backdrop-blur-xs select-none"
  >
    <div
      class="animate-in zoom-in-95 w-full max-w-md space-y-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl duration-150"
    >
      <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div
            class="flex size-9 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-100 text-zinc-900"
          >
            <FolderPlus class="size-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-zinc-900">Kelola Kategori Bahan Baku</h3>
            <p class="text-[11px] text-zinc-500">Tambah, ubah, atau hapus kategori inventori</p>
          </div>
        </div>
        <button
          type="button"
          onclick={() => (isCategoryModalOpen = false)}
          class="cursor-pointer p-1 text-zinc-400 hover:text-zinc-700"
        >
          <X class="size-4" />
        </button>
      </div>

      <!-- Add Category Input -->
      <div class="flex items-center gap-2">
        <input
          type="text"
          bind:value={newCategoryName}
          placeholder="Nama kategori baru..."
          class="flex-1 rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 text-xs text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
        />
        <button
          type="button"
          onclick={handleAddCategory}
          class="shrink-0 cursor-pointer rounded-xl bg-zinc-900 px-3.5 py-2 text-xs font-semibold text-white shadow-2xs transition-all hover:bg-black active:scale-95"
        >
          + Tambah
        </button>
      </div>

      <!-- Categories List -->
      <div
        class="max-h-60 divide-y divide-zinc-100 overflow-y-auto rounded-xl border border-zinc-200"
      >
        {#each categories as cat (cat.id)}
          <div class="flex items-center justify-between gap-2 p-3 hover:bg-zinc-50">
            {#if editingCategoryId === cat.id}
              <input
                type="text"
                bind:value={editingCategoryName}
                class="flex-1 rounded border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 focus:outline-hidden"
              />
              <div class="flex items-center gap-1">
                <button
                  type="button"
                  onclick={() => handleSaveEditCategory(cat.id)}
                  class="rounded p-1 text-emerald-600 hover:bg-emerald-50"
                >
                  <Check class="size-4" />
                </button>
                <button
                  type="button"
                  onclick={() => (editingCategoryId = null)}
                  class="rounded p-1 text-zinc-400 hover:bg-zinc-100"
                >
                  <X class="size-4" />
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
                  class="rounded p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700"
                >
                  <Edit2 class="size-3.5" />
                </button>
                <button
                  type="button"
                  onclick={() => handleDeleteCategory(cat.id)}
                  class="rounded p-1 text-zinc-400 hover:bg-red-50 hover:text-red-600"
                >
                  <Trash2 class="size-3.5" />
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
          class="w-full cursor-pointer rounded-xl bg-zinc-100 py-2.5 text-xs font-semibold text-zinc-800 transition-colors hover:bg-zinc-200"
        >
          Selesai
        </button>
      </div>
    </div>
  </div>
{/if}
