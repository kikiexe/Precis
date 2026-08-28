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

      <!-- Filter Kategori -->
      <select
        bind:value={selectedCategoryFilter}
        class="h-9 px-3 bg-zinc-50 border border-zinc-200 rounded-lg text-xs font-medium text-zinc-700 cursor-pointer focus:bg-white focus:border-zinc-900 focus:outline-hidden"
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
      class="px-4 py-2 bg-zinc-900 hover:bg-black text-white rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 shrink-0 cursor-pointer shadow-xs transition-all active:scale-[0.99]"
    >
      <Plus class="w-3.5 h-3.5" />
      <span>+ Bahan Baku Baru</span>
    </button>
  </div>

  <!-- Excel-like Spreadsheet Table -->
  <div class="bg-white border border-zinc-200 rounded-xl overflow-hidden shadow-2xs">
    <div class="overflow-x-auto">
      <table class="w-full text-xs text-left border-collapse">
        <thead class="bg-zinc-100/80 border-b border-zinc-200 font-mono text-[11px] font-bold text-zinc-600 uppercase tracking-wider">
          <tr class="divide-x divide-zinc-200/80">
            <th class="py-3 px-3 w-12 text-center">No.</th>
            <th class="py-3 px-4">Nama Bahan Baku</th>
            <th class="py-3 px-4 w-36">Kategori</th>
            <th class="py-3 px-4 w-32 text-right">Stok Fisik</th>
            <th class="py-3 px-4 w-32 text-right">Batas Minimum</th>
            <th class="py-3 px-4 w-32 text-center">Status</th>
            <th class="py-3 px-4 w-36 text-center">Audit Terakhir</th>
            <th class="py-3 px-4 w-44 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200/70">
          {#if filteredRawMaterials.length === 0}
            <tr>
              <td colspan="8" class="py-16 text-center text-zinc-400">
                <p class="text-sm font-semibold text-zinc-800">Tidak ada data bahan baku</p>
                <p class="text-xs text-zinc-500 mt-0.5">Coba ubah kata kunci pencarian atau filter kategori.</p>
              </td>
            </tr>
          {:else}
            {#each filteredRawMaterials as mat, idx (mat.id)}
              {@const isLow = mat.current_stock <= mat.min_stock_alert}
              <tr class={`divide-x divide-zinc-200/60 transition-colors hover:bg-zinc-50/80 ${
                idx % 2 === 1 ? 'bg-zinc-50/30' : 'bg-white'
              }`}>
                <!-- No -->
                <td class="py-3 px-3 font-mono text-center text-zinc-400 text-[11px]">
                  {idx + 1}
                </td>

                <!-- Nama Bahan Baku -->
                <td class="py-3 px-4 font-semibold text-zinc-900">
                  <div class="flex items-center gap-2">
                    {#if isLow}
                      <AlertTriangle class="w-3.5 h-3.5 text-red-500 shrink-0" />
                    {/if}
                    <span>{mat.name}</span>
                  </div>
                </td>

                <!-- Kategori -->
                <td class="py-3 px-4">
                  <span class="inline-block px-2.5 py-0.5 bg-zinc-100 text-zinc-700 rounded-md text-[11px] font-medium border border-zinc-200/60 truncate max-w-[130px]">
                    {mat.category_name || 'Lainnya'}
                  </span>
                </td>

                <!-- Stok Fisik -->
                <td class={`py-3 px-4 font-mono font-bold text-right text-xs ${
                  isLow ? 'text-red-600 bg-red-50/40' : 'text-zinc-900'
                }`}>
                  {mat.current_stock} <span class="font-normal text-[10px] text-zinc-500">{mat.unit}</span>
                </td>

                <!-- Batas Minimum -->
                <td class="py-3 px-4 font-mono text-right text-zinc-600 text-xs">
                  {mat.min_stock_alert} <span class="font-normal text-[10px] text-zinc-400">{mat.unit}</span>
                </td>

                <!-- Status -->
                <td class="py-3 px-4 text-center">
                  <span class={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold ${
                    isLow
                      ? 'bg-red-100 text-red-700'
                      : 'bg-emerald-100 text-emerald-800'
                  }`}>
                    {#if isLow}
                      <span>Menipis</span>
                    {:else}
                      <span>Aman</span>
                    {/if}
                  </span>
                </td>

                <!-- Audit Terakhir -->
                <td class="py-3 px-4 font-mono text-center text-zinc-500 text-[11px]">
                  {mat.last_adjusted_at || '-'}
                </td>

                <!-- Aksi -->
                <td class="py-3 px-4 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      type="button"
                      onclick={() => onOpenOpname(mat)}
                      class="px-2.5 py-1.5 bg-zinc-900 hover:bg-black text-white text-[11px] font-medium rounded-lg flex items-center gap-1 transition-all cursor-pointer shadow-2xs active:scale-95"
                      title="Audit Stok Opname"
                    >
                      <SlidersHorizontal class="w-3 h-3" />
                      <span>Opname</span>
                    </button>

                    <button
                      type="button"
                      onclick={() => onOpenEditMaterial(mat)}
                      class="p-1.5 text-zinc-500 hover:text-zinc-900 hover:bg-zinc-100 border border-zinc-200 rounded-lg transition-colors cursor-pointer"
                      title="Edit Bahan Baku"
                    >
                      <Edit2 class="w-3.5 h-3.5" />
                    </button>

                    <button
                      type="button"
                      onclick={() => onDeleteMaterial(mat.id)}
                      class="p-1.5 text-zinc-400 hover:text-red-600 hover:bg-red-50 border border-zinc-200 rounded-lg transition-colors cursor-pointer"
                      title="Hapus Bahan Baku"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
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
    <div class="bg-zinc-50 border-t border-zinc-200 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs font-mono text-zinc-600">
      <div class="flex items-center gap-4">
        <span>Total Bahan: <strong class="text-zinc-900">{filteredRawMaterials.length}</strong></span>
        <span>Stok Menipis: <strong class="text-red-600">{lowStockCount}</strong></span>
        <span>Stok Aman: <strong class="text-emerald-700">{rawMaterials.length - lowStockCount}</strong></span>
      </div>

      <div class="text-[11px] text-zinc-400">
        Klik tombol "Opname" untuk rekonsiliasi selisih fisik laci/bar
      </div>
    </div>
  </div>
</div>
