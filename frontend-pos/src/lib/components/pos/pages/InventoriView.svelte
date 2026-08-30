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
    Flame,
    Boxes,
  } from 'lucide-svelte';
  import type { RawMaterial, Product, CashierUser, StockWaste } from '../../../types/pos';
  import StockWasteModal from '../StockWasteModal.svelte';

  import { posService } from '../../../services/pos-service';

  interface RawCategory {
    id: string;
    name: string;
  }

  interface Props {
    rawMaterials?: RawMaterial[];
    products?: Product[];
    cashiers?: CashierUser[];
    activeCashier?: CashierUser | null;
    stockWastes?: StockWaste[];
    onUpdateMaterials?: (materials: RawMaterial[]) => void;
    onRecordWaste?: (waste: StockWaste) => void;
  }

  let {
    rawMaterials: initialMaterials = [],
    products = [],
    cashiers = [],
    activeCashier = null,
    stockWastes: initialWastes = [],
    onUpdateMaterials,
    onRecordWaste,
  }: Props = $props();

  // tab state stok bahan baku vs catatan kerugian waste
  let activeTab = $state<'STOCK' | 'WASTE'>('STOCK');
  let isWasteModalOpen = $state(false);
  let selectedWasteReason = $state<string>('ALL');
  let wastes = $state<StockWaste[]>([]);

  $effect(() => {
    if (initialWastes) {
      wastes = initialWastes;
    }
  });

  // state tanggal filter
  let selectedDate = $state(new Date().toISOString().substring(0, 10));
  let searchQuery = $state('');
  let selectedCategoryId = $state('ALL');

  // master kategori
  let categories = $state<RawCategory[]>([]);

  // master daftar bahan baku
  let materials = $state<RawMaterial[]>([]);

  $effect(() => {
    posService.getInventoryCategories().then((cats) => {
      if (cats && cats.length > 0) {
        categories = cats;
      }
    });

    if (initialMaterials && initialMaterials.length > 0) {
      materials = initialMaterials;
    } else {
      posService.getRawMaterials().then((data) => {
        if (data && data.length > 0) {
          materials = data;
        }
      });
    }
  });

  // state modal
  let isAddMaterialModalOpen = $state(false);
  let isCategoryModalOpen = $state(false);
  let adjustingMaterial = $state<RawMaterial | null>(null);
  let newCurrentStockInput = $state(0);

  // state form tambah bahan baku
  let formName = $state('');
  let formCategoryId = $state('');
  let formUnit = $state('liter');
  let formInitialStock = $state(0);
  let formMinStock = $state(5);
  let formErrorMessage = $state('');

  // state form kelola kategori
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

  // daftar bahan baku terfilter
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

  // daftar barang terbuang terfilter
  let filteredWastes = $derived(
    wastes.filter((w) => {
      const matchSearch =
        searchQuery.trim() === '' ||
        w.item_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (w.notes && w.notes.toLowerCase().includes(searchQuery.toLowerCase()));

      const matchReason = selectedWasteReason === 'ALL' || w.reason === selectedWasteReason;

      return matchSearch && matchReason;
    })
  );

  let totalLossAmount = $derived(
    filteredWastes.reduce((acc, curr) => acc + Number(curr.total_loss_cost || 0), 0)
  );

  function formatRupiah(amount: number): string {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount);
  }

  function getReasonBadge(reason: string): { label: string; bg: string; text: string } {
    switch (reason) {
      case 'EXPIRED':
        return { label: 'Kedaluwarsa', bg: 'bg-rose-500/15', text: 'text-rose-600' };
      case 'SPOILED':
        return { label: 'Basi / Rusak', bg: 'bg-red-500/15', text: 'text-red-700' };
      case 'ACCIDENT_SPILL':
        return { label: 'Tumpah / Pecah', bg: 'bg-amber-500/15', text: 'text-amber-700' };
      case 'BARISTA_MISTAKE':
        return { label: 'Salah Buat', bg: 'bg-orange-500/15', text: 'text-orange-700' };
      case 'QC_REJECT':
        return { label: 'Reject QC', bg: 'bg-purple-500/15', text: 'text-purple-700' };
      default:
        return { label: 'Lainnya', bg: 'bg-slate-500/15', text: 'text-slate-700' };
    }
  }

  // aksi sesuaikan stok
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

  // aksi tambah bahan baku baru
  function handleOpenAddModal() {
    formName = '';
    formCategoryId = categories[0]?.id || '';
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

  // aksi kelola kategori
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
  <!-- Top Bar with Sub-Tab Navigation -->
  <div
    class="flex h-14 shrink-0 items-center justify-between border-b border-zinc-200 bg-white px-6 shadow-2xs"
  >
    <div class="flex items-center gap-3">
      <!-- Tabs -->
      <div class="flex items-center gap-1 rounded-xl bg-zinc-100 p-1">
        <button
          type="button"
          onclick={() => (activeTab = 'STOCK')}
          class={`flex cursor-pointer items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition-all ${
            activeTab === 'STOCK'
              ? 'bg-white text-zinc-900 shadow-2xs'
              : 'text-zinc-500 hover:text-zinc-800'
          }`}
        >
          <Boxes class="size-3.5" />
          <span>Stok Bahan Baku</span>
        </button>
        <button
          type="button"
          onclick={() => (activeTab = 'WASTE')}
          class={`flex cursor-pointer items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition-all ${
            activeTab === 'WASTE'
              ? 'bg-rose-600 text-white shadow-2xs'
              : 'text-zinc-500 hover:text-zinc-800'
          }`}
        >
          <Flame class="size-3.5" />
          <span>Catatan Kerugian (Waste)</span>
          {#if wastes.length > 0}
            <span
              class={`py-0.2 rounded-full px-1.5 text-[10px] font-extrabold ${
                activeTab === 'WASTE' ? 'bg-rose-800 text-white' : 'bg-rose-100 text-rose-700'
              }`}
            >
              {wastes.length}
            </span>
          {/if}
        </button>
      </div>

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
      {#if activeTab === 'STOCK'}
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
      {:else}
        <button
          type="button"
          onclick={() => (isWasteModalOpen = true)}
          class="active:scale-0.99 flex cursor-pointer items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 py-2 text-xs font-semibold text-white shadow-2xs shadow-rose-600/20 transition-all hover:bg-rose-700"
        >
          <Plus class="size-3.5" />
          <span>+ Catat Stock Waste</span>
        </button>
      {/if}
    </div>
  </div>

  <!-- Content Body -->
  <div class="flex-1 space-y-4 overflow-y-auto p-4 sm:p-6 lg:p-8">
    <div class="mx-auto w-full max-w-7xl space-y-4">
      {#if activeTab === 'STOCK'}
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
      {:else}
        <!-- Waste Summary KPI Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div class="rounded-2xl border border-rose-200/80 bg-rose-50/50 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-rose-800">Total Estimasi Kerugian</span>
              <div class="rounded-lg bg-rose-500/15 p-1.5 text-rose-600">
                <Flame class="size-4" />
              </div>
            </div>
            <div class="mt-2 text-2xl font-black tracking-tight text-rose-900">
              {formatRupiah(totalLossAmount)}
            </div>
            <p class="mt-1 text-[11px] text-rose-700/80">Total kerugian operasional bahan baku</p>
          </div>

          <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-2xs">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-zinc-600">Total Insiden Waste</span>
              <div class="rounded-lg bg-zinc-100 p-1.5 text-zinc-600">
                <AlertCircle class="size-4" />
              </div>
            </div>
            <div class="mt-2 text-2xl font-black tracking-tight text-zinc-900">
              {filteredWastes.length} <span class="text-sm font-semibold text-zinc-500">Insiden</span>
            </div>
            <p class="mt-1 text-[11px] text-zinc-400">Tercatat di sistem kasir & portal</p>
          </div>
        </div>

        <!-- Waste Filter Toolbar -->
        <div
          class="flex flex-col items-stretch justify-between gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-2xs sm:flex-row sm:items-center"
        >
          <div class="flex flex-1 items-center gap-2">
            <div class="relative max-w-md flex-1">
              <Search class="absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-zinc-400" />
              <input
                type="text"
                bind:value={searchQuery}
                placeholder="Cari item terbuang, catatan kronologi..."
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

            <!-- Reason Filter -->
            <select
              bind:value={selectedWasteReason}
              class="h-9 cursor-pointer rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-xs font-semibold text-zinc-700 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
            >
              <option value="ALL">Semua Alasan Waste ({wastes.length})</option>
              <option value="SPOILED">Basi / Rusak Kulkas</option>
              <option value="EXPIRED">Kedaluwarsa (Expired)</option>
              <option value="ACCIDENT_SPILL">Tumpah / Pecah</option>
              <option value="BARISTA_MISTAKE">Salah Buat / Racik</option>
              <option value="QC_REJECT">Reject QC / Mutu</option>
              <option value="OTHER">Lainnya</option>
            </select>
          </div>
        </div>

        <!-- Waste Records Table -->
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xs">
          <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
              <thead
                class="border-b border-zinc-200 bg-zinc-100/90 font-mono text-[11px] font-bold tracking-wider text-zinc-600 uppercase"
              >
                <tr class="divide-x divide-zinc-200/80">
                  <th class="w-12 p-3 text-center">No.</th>
                  <th class="w-36 px-4 py-3">Tanggal / Waktu</th>
                  <th class="px-4 py-3">Nama Bahan / Menu Terbuang</th>
                  <th class="w-36 px-4 py-3 text-center">Alasan</th>
                  <th class="w-28 px-4 py-3 text-right">Jumlah</th>
                  <th class="w-32 px-4 py-3 text-right">Modal / Unit</th>
                  <th class="w-36 bg-rose-50/50 px-4 py-3 text-right font-bold text-rose-900">Total Kerugian</th>
                  <th class="w-48 px-4 py-3">Pelapor / Catatan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-zinc-200/70 font-mono">
                {#if filteredWastes.length === 0}
                  <tr>
                    <td colspan="8" class="py-16 text-center font-sans text-zinc-400">
                      <p class="text-sm font-semibold text-zinc-800">Tidak ada catatan stock waste</p>
                      <p class="mt-0.5 text-xs text-zinc-500">
                        Belum ada insiden bahan baku rusak atau terbuang yang dicatat.
                      </p>
                    </td>
                  </tr>
                {:else}
                  {#each filteredWastes as w, idx (w.id)}
                    {@const badge = getReasonBadge(w.reason)}
                    <tr
                      class={`divide-x divide-zinc-200/60 transition-colors hover:bg-zinc-50/80 ${
                        idx % 2 === 1 ? 'bg-zinc-50/30' : 'bg-white'
                      }`}
                    >
                      <td class="p-3 text-center text-[11px] text-zinc-400">{idx + 1}</td>
                      <td class="px-4 py-3 text-[11px] text-zinc-500">
                        {w.created_at ? new Date(w.created_at).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }) : '-'}
                      </td>
                      <td class="px-4 py-3 font-sans font-semibold text-zinc-900">
                        {w.item_name}
                      </td>
                      <td class="px-4 py-3 text-center font-sans">
                        <span class={`inline-block rounded-full px-2.5 py-0.5 text-[10px] font-bold ${badge.bg} ${badge.text}`}>
                          {badge.label}
                        </span>
                      </td>
                      <td class="px-4 py-3 text-right font-bold text-zinc-800">
                        {w.quantity} {w.unit}
                      </td>
                      <td class="px-4 py-3 text-right text-zinc-600">
                        {formatRupiah(w.cost_per_unit)}
                      </td>
                      <td class="bg-rose-50/30 px-4 py-3 text-right font-black text-rose-700">
                        {formatRupiah(w.total_loss_cost)}
                      </td>
                      <td class="px-4 py-3 font-sans text-xs">
                        <div class="font-semibold text-zinc-800">{w.recorded_by_user?.name || 'Kasir'}</div>
                        {#if w.notes}
                          <p class="mt-0.5 line-clamp-1 text-[11px] text-zinc-500 italic">{w.notes}</p>
                        {/if}
                      </td>
                    </tr>
                  {/each}
                {/if}
              </tbody>
            </table>
          </div>

          <!-- Waste Table Footer -->
          <div
            class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50 px-4 py-2.5 font-mono text-xs text-zinc-600"
          >
            <div>
              Total Insiden Tercatat: <strong class="text-zinc-900">{filteredWastes.length}</strong>
            </div>
            <div>
              Akumulasi Kerugian: <strong class="text-rose-700">{formatRupiah(totalLossAmount)}</strong>
            </div>
          </div>
        </div>
      {/if}
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

<!-- Modal Catat Stock Waste -->
<StockWasteModal
  isOpen={isWasteModalOpen}
  {products}
  {cashiers}
  {activeCashier}
  onRecorded={(waste) => {
    wastes = [waste, ...wastes];
    onRecordWaste?.(waste);
  }}
  onClose={() => (isWasteModalOpen = false)}
/>

