<script lang="ts">
  import { Search, Plus, Edit2, SlidersHorizontal, Trash2, AlertTriangle } from 'lucide-svelte';
  import type { RawMaterial } from '../../../../types/pos';

  interface Props {
    rawMaterials: Array<RawMaterial & { stock_previous_day?: number }>;
    onOpenAddMaterial: () => void;
    onOpenEditMaterial: (mat: RawMaterial & { stock_previous_day?: number }) => void;
    onOpenOpname: (mat: RawMaterial & { stock_previous_day?: number }) => void;
    onDeleteMaterial: (id: string) => void;
  }

  let {
    rawMaterials = [],
    onOpenAddMaterial,
    onOpenEditMaterial,
    onOpenOpname,
    onDeleteMaterial,
  }: Props = $props();

  let searchQuery = $state('');
  let selectedCategoryFilter = $state('ALL');

  let categoriesList = $derived(
    Array.from(new Set(rawMaterials.map((m) => m.category_name || 'Lainnya')))
  );

  let filteredRawMaterials = $derived(
    rawMaterials.filter((m) => {
      const matchSearch =
        searchQuery.trim() === '' ||
        m.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (m.category_name && m.category_name.toLowerCase().includes(searchQuery.toLowerCase()));

      const matchCat =
        selectedCategoryFilter === 'ALL' ||
        (m.category_name || 'Lainnya') === selectedCategoryFilter;

      return matchSearch && matchCat;
    })
  );

  let lowStockCount = $derived(
    rawMaterials.filter((m) => m.current_stock <= m.min_stock_alert).length
  );
</script>

<div class="space-y-3 font-sans">
  <!-- Excel Toolbar: Search & Action Controls -->
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

      <!-- Filter Kategori -->
      <select
        bind:value={selectedCategoryFilter}
        class="h-9 cursor-pointer rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-xs font-medium text-zinc-700 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
      >
        <option value="ALL">Semua Kategori ({rawMaterials.length})</option>
        {#each categoriesList as cat}
          <option value={cat}>{cat}</option>
        {/each}
      </select>
    </div>

    <button
      type="button"
      onclick={onOpenAddMaterial}
      class="active:scale-0.99 flex shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-lg bg-zinc-900 px-4 py-2 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
    >
      <Plus class="size-3.5" />
      <span>+ Bahan Baku Baru</span>
    </button>
  </div>

  <!-- Excel-like Spreadsheet Table -->
  <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xs">
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-left text-xs">
        <thead
          class="border-b border-zinc-200 bg-zinc-100/80 font-mono text-[11px] font-bold tracking-wider text-zinc-600 uppercase"
        >
          <tr class="divide-x divide-zinc-200/80">
            <th class="w-12 p-3 text-center">No.</th>
            <th class="px-4 py-3">Nama Bahan Baku</th>
            <th class="w-36 px-4 py-3">Kategori</th>
            <th class="w-32 px-4 py-3 text-right">Stok Fisik</th>
            <th class="w-32 px-4 py-3 text-right">Batas Minimum</th>
            <th class="w-32 px-4 py-3 text-center">Status</th>
            <th class="w-36 px-4 py-3 text-center">Audit Terakhir</th>
            <th class="w-44 px-4 py-3 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200/70">
          {#if filteredRawMaterials.length === 0}
            <tr>
              <td colspan="8" class="py-16 text-center text-zinc-400">
                <p class="text-sm font-semibold text-zinc-800">Tidak ada data bahan baku</p>
                <p class="mt-0.5 text-xs text-zinc-500">
                  Coba ubah kata kunci pencarian atau filter kategori.
                </p>
              </td>
            </tr>
          {:else}
            {#each filteredRawMaterials as mat, idx (mat.id)}
              {@const isLow = mat.current_stock <= mat.min_stock_alert}
              <tr
                class={`divide-x divide-zinc-200/60 transition-colors hover:bg-zinc-50/80 ${
                  idx % 2 === 1 ? 'bg-zinc-50/30' : 'bg-white'
                }`}
              >
                <!-- No -->
                <td class="p-3 text-center font-mono text-[11px] text-zinc-400">
                  {idx + 1}
                </td>

                <!-- Nama Bahan Baku -->
                <td class="px-4 py-3 font-semibold text-zinc-900">
                  <div class="flex items-center gap-2">
                    {#if isLow}
                      <AlertTriangle class="size-3.5 shrink-0 text-red-500" />
                    {/if}
                    <span>{mat.name}</span>
                  </div>
                </td>

                <!-- Kategori -->
                <td class="px-4 py-3">
                  <span
                    class="inline-block max-w-[130px] truncate rounded-md border border-zinc-200/60 bg-zinc-100 px-2.5 py-0.5 text-[11px] font-medium text-zinc-700"
                  >
                    {mat.category_name || 'Lainnya'}
                  </span>
                </td>

                <!-- Stok Fisik -->
                <td
                  class={`px-4 py-3 text-right font-mono text-xs font-bold ${
                    isLow ? 'bg-red-50/40 text-red-600' : 'text-zinc-900'
                  }`}
                >
                  {mat.current_stock}
                  <span class="text-[10px] font-normal text-zinc-500">{mat.unit}</span>
                </td>

                <!-- Batas Minimum -->
                <td class="px-4 py-3 text-right font-mono text-xs text-zinc-600">
                  {mat.min_stock_alert}
                  <span class="text-[10px] font-normal text-zinc-400">{mat.unit}</span>
                </td>

                <!-- Status -->
                <td class="px-4 py-3 text-center">
                  <span
                    class={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold ${
                      isLow ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-800'
                    }`}
                  >
                    {#if isLow}
                      <span>Menipis</span>
                    {:else}
                      <span>Aman</span>
                    {/if}
                  </span>
                </td>

                <!-- Audit Terakhir -->
                <td class="px-4 py-3 text-center font-mono text-[11px] text-zinc-500">
                  {mat.last_adjusted_at || '-'}
                </td>

                <!-- Aksi -->
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      type="button"
                      onclick={() => onOpenOpname(mat)}
                      class="flex cursor-pointer items-center gap-1 rounded-lg bg-zinc-900 px-2.5 py-1.5 text-[11px] font-medium text-white shadow-2xs transition-all hover:bg-black active:scale-95"
                      title="Audit Stok Opname"
                    >
                      <SlidersHorizontal class="size-3" />
                      <span>Opname</span>
                    </button>

                    <button
                      type="button"
                      onclick={() => onOpenEditMaterial(mat)}
                      class="cursor-pointer rounded-lg border border-zinc-200 p-1.5 text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-900"
                      title="Edit Bahan Baku"
                    >
                      <Edit2 class="size-3.5" />
                    </button>

                    <button
                      type="button"
                      onclick={() => onDeleteMaterial(mat.id)}
                      class="cursor-pointer rounded-lg border border-zinc-200 p-1.5 text-zinc-400 transition-colors hover:bg-red-50 hover:text-red-600"
                      title="Hapus Bahan Baku"
                    >
                      <Trash2 class="size-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
            {/each}
          {/if}
        </tbody>
      </table>
    </div>

    <!-- Excel Status Bar / Summary Footer -->
    <div
      class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50 px-4 py-2.5 font-mono text-xs text-zinc-600"
    >
      <div class="flex items-center gap-4">
        <span
          >Total Bahan: <strong class="text-zinc-900">{filteredRawMaterials.length}</strong></span
        >
        <span>Stok Menipis: <strong class="text-red-600">{lowStockCount}</strong></span>
        <span
          >Stok Aman: <strong class="text-emerald-700">{rawMaterials.length - lowStockCount}</strong
          ></span
        >
      </div>

      <div class="text-[11px] text-zinc-400">
        Klik tombol "Opname" untuk rekonsiliasi selisih fisik laci/bar
      </div>
    </div>
  </div>
</div>
