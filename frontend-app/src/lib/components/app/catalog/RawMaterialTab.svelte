<script lang="ts">
  import { Search, Trash2, X, AlertTriangle } from 'lucide-svelte';
  import type { CategoryItem, RawMaterialItem } from '../../../types/app';

  interface Props {
    rawMaterials: RawMaterialItem[];
    categories: CategoryItem[];
    onAdjustStock: (material: RawMaterialItem) => void;
    onPromptDelete: (id: string, name: string) => void;
  }

  let {
    rawMaterials = [],
    categories = [],
    onAdjustStock,
    onPromptDelete,
  }: Props = $props();

  let searchQuery = $state('');
  let selectedCategoryFilter = $state('ALL');

  let filteredRawMaterials = $derived(
    rawMaterials.filter((mat) => {
      const matchSearch =
        searchQuery.trim() === '' ||
        mat.name.toLowerCase().includes(searchQuery.toLowerCase());
      const matchCat = selectedCategoryFilter === 'ALL' || mat.category_id === selectedCategoryFilter;
      return matchSearch && matchCat;
    })
  );

  let rawMaterialCategories = $derived(categories.filter((c) => c.type === 'RAW_MATERIAL'));
</script>

<div class="space-y-5 font-sans">
  <!-- Search & Filters Container -->
  <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-3.5 sm:p-4 space-y-3 lg:space-y-0 lg:flex lg:items-center lg:justify-between lg:gap-4 shadow-2xs">
    <!-- Search Input -->
    <div class="relative w-full lg:w-72 xl:w-80 shrink-0">
      <Search class="w-4 h-4 text-[#8e8e93] absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
      <input
        type="text"
        bind:value={searchQuery}
        placeholder="Cari nama bahan baku..."
        class="w-full bg-[#f8f8fa] hover:bg-[#f2f2f5] pl-10 pr-9 py-2 text-xs rounded-full border border-[#e5e5ea] placeholder-[#8e8e93] text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
      />
      {#if searchQuery}
        <button
          type="button"
          onclick={() => (searchQuery = '')}
          class="absolute right-3 top-1/2 -translate-y-1/2 text-[#8e8e93] hover:text-[#17171c] p-0.5 rounded-full cursor-pointer"
          title="Hapus pencarian"
        >
          <X class="w-3.5 h-3.5" />
        </button>
      {/if}
    </div>

    <!-- Category Filter Capsules -->
    <div class="w-full lg:flex-1 min-w-0 overflow-hidden">
      <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5 w-full lg:justify-end">
        <button
          type="button"
          onclick={() => (selectedCategoryFilter = 'ALL')}
          class={`px-4 py-2 text-xs rounded-full transition-all cursor-pointer shrink-0 font-medium ${
            selectedCategoryFilter === 'ALL'
              ? 'bg-[#17171c] text-white shadow-xs font-semibold'
              : 'bg-[#f4f4f6] text-[#686873] hover:text-[#17171c] hover:bg-[#ececee] border border-[#e5e5ea]'
          }`}
        >
          Semua ({rawMaterials.length})
        </button>
        {#each rawMaterialCategories as cat}
          {@const count = rawMaterials.filter((m) => m.category_id === cat.id).length}
          <button
            type="button"
            onclick={() => (selectedCategoryFilter = cat.id)}
            class={`px-4 py-2 text-xs rounded-full transition-all cursor-pointer shrink-0 font-medium whitespace-nowrap ${
              selectedCategoryFilter === cat.id
                ? 'bg-[#17171c] text-white shadow-xs font-semibold'
                : 'bg-[#f4f4f6] text-[#686873] hover:text-[#17171c] hover:bg-[#ececee] border border-[#e5e5ea]'
            }`}
          >
            {cat.name} ({count})
          </button>
        {/each}
      </div>
    </div>
  </div>

  <!-- Raw Materials Grid -->
  {#if filteredRawMaterials.length === 0}
    <div class="bg-white border border-[#e5e5ea] rounded-3xl p-12 text-center space-y-2 shadow-2xs">
      <p class="text-xs font-bold text-[#17171c]">Tidak ada bahan baku yang sesuai</p>
      <p class="text-[11px] text-[#8e8e93]">
        {searchQuery ? `Tidak ditemukan bahan dengan kata kunci "${searchQuery}".` : 'Belum ada bahan baku di kategori ini.'}
      </p>
    </div>
  {:else}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      {#each filteredRawMaterials as mat}
        {@const isLowStock = mat.current_stock <= (mat.min_stock_alert || 0)}
        <div class="bg-white border border-[#e5e5ea] hover:border-[#17171c]/40 rounded-2xl p-5 shadow-2xs hover:shadow-xs transition-all flex flex-col justify-between group space-y-4">
          <div class="space-y-2">
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-1">
                <span class="text-[10px] font-mono uppercase font-semibold text-[#8e8e93]">
                  {mat.category_name || categories.find((c) => c.id === mat.category_id)?.name || 'Raw Material'}
                </span>
                <h4 class="font-bold text-sm text-[#17171c] group-hover:text-black leading-snug">
                  {mat.name}
                </h4>
              </div>
              <button
                type="button"
                onclick={() => onPromptDelete(mat.id, mat.name)}
                class="p-2 text-[#8e8e93] hover:text-[#dc2626] hover:bg-[#fef2f2] rounded-xl transition-all cursor-pointer opacity-80 group-hover:opacity-100 shrink-0"
                title="Hapus Bahan"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>

            <!-- Stock Status Metric -->
            <div class="flex items-baseline justify-between pt-1">
              <div>
                <span class="text-2xl font-bold font-mono text-[#17171c]">{mat.current_stock}</span>
                <span class="text-xs text-[#8e8e93] ml-1 font-mono">{mat.unit}</span>
              </div>

              {#if isLowStock}
                <div class="flex items-center gap-1 text-[10.5px] font-semibold text-[#d97706] bg-[#fffbeb] border border-[#fef3c7] px-2.5 py-1 rounded-full">
                  <AlertTriangle class="w-3.5 h-3.5" />
                  <span>Stok Menipis</span>
                </div>
              {:else}
                <span class="text-[10.5px] font-semibold text-[#059669] bg-[#ecfdf5] border border-[#a7f3d0] px-2.5 py-1 rounded-full">
                  Aman
                </span>
              {/if}
            </div>
          </div>

          <div class="pt-3 border-t border-[#f2f2f4] flex items-center justify-between">
            <span class="text-[11px] text-[#8e8e93] font-mono">
              Min: {mat.min_stock_alert} {mat.unit}
            </span>
            <button
              type="button"
              onclick={() => onAdjustStock(mat)}
              class="px-3.5 py-1.5 rounded-full text-xs font-semibold bg-[#17171c] hover:bg-black text-white cursor-pointer transition-all shadow-2xs"
            >
              Sesuaikan Stok
            </button>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>
