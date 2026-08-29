<script lang="ts">
  import { Search, Trash2, X, AlertTriangle } from 'lucide-svelte';
  import type { CategoryItem, RawMaterialItem } from '../../../types/app';

  interface Props {
    rawMaterials: RawMaterialItem[];
    categories: CategoryItem[];
    onAdjustStock: (material: RawMaterialItem) => void;
    onPromptDelete: (id: string, name: string) => void;
  }

  let { rawMaterials = [], categories = [], onAdjustStock, onPromptDelete }: Props = $props();

  let searchQuery = $state('');
  let selectedCategoryFilter = $state('ALL');

  let filteredRawMaterials = $derived(
    rawMaterials.filter((mat) => {
      const matchSearch =
        searchQuery.trim() === '' || mat.name.toLowerCase().includes(searchQuery.toLowerCase());
      const matchCat =
        selectedCategoryFilter === 'ALL' || mat.category_id === selectedCategoryFilter;
      return matchSearch && matchCat;
    })
  );

  let rawMaterialCategories = $derived(categories.filter((c) => c.type === 'RAW_MATERIAL'));
</script>

<div class="space-y-5 font-sans">
  <!-- Search & Filters Container -->
  <div
    class="space-y-3 rounded-2xl border border-[#e5e5ea] bg-white p-3.5 shadow-2xs sm:rounded-3xl sm:p-4 lg:flex lg:items-center lg:justify-between lg:gap-4 lg:space-y-0"
  >
    <!-- Search Input -->
    <div class="relative w-full shrink-0 lg:w-72 xl:w-80">
      <Search
        class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-[#8e8e93]"
      />
      <input
        type="text"
        bind:value={searchQuery}
        placeholder="Cari nama bahan baku..."
        class="w-full rounded-full border border-[#e5e5ea] bg-[#f8f8fa] py-2 pr-9 pl-10 text-xs text-[#17171c] placeholder-[#8e8e93] shadow-2xs transition-all hover:bg-[#f2f2f5] focus:border-[#17171c] focus:outline-hidden"
      />
      {#if searchQuery}
        <button
          type="button"
          onclick={() => (searchQuery = '')}
          class="absolute top-1/2 right-3 -translate-y-1/2 cursor-pointer rounded-full p-0.5 text-[#8e8e93] hover:text-[#17171c]"
          title="Hapus pencarian"
        >
          <X class="size-3.5" />
        </button>
      {/if}
    </div>

    <!-- Category Filter Capsules -->
    <div class="w-full min-w-0 overflow-hidden lg:flex-1">
      <div
        class="no-scrollbar flex w-full items-center gap-1.5 overflow-x-auto py-0.5 lg:justify-end"
      >
        <button
          type="button"
          onclick={() => (selectedCategoryFilter = 'ALL')}
          class={`shrink-0 cursor-pointer rounded-full px-4 py-2 text-xs font-medium transition-all ${
            selectedCategoryFilter === 'ALL'
              ? 'bg-[#17171c] font-semibold text-white shadow-xs'
              : 'border border-[#e5e5ea] bg-[#f4f4f6] text-[#686873] hover:bg-[#ececee] hover:text-[#17171c]'
          }`}
        >
          Semua ({rawMaterials.length})
        </button>
        {#each rawMaterialCategories as cat}
          {@const count = rawMaterials.filter((m) => m.category_id === cat.id).length}
          <button
            type="button"
            onclick={() => (selectedCategoryFilter = cat.id)}
            class={`shrink-0 cursor-pointer rounded-full px-4 py-2 text-xs font-medium whitespace-nowrap transition-all ${
              selectedCategoryFilter === cat.id
                ? 'bg-[#17171c] font-semibold text-white shadow-xs'
                : 'border border-[#e5e5ea] bg-[#f4f4f6] text-[#686873] hover:bg-[#ececee] hover:text-[#17171c]'
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
    <div class="space-y-2 rounded-3xl border border-[#e5e5ea] bg-white p-12 text-center shadow-2xs">
      <p class="text-xs font-bold text-[#17171c]">Tidak ada bahan baku yang sesuai</p>
      <p class="text-[11px] text-[#8e8e93]">
        {searchQuery
          ? `Tidak ditemukan bahan dengan kata kunci "${searchQuery}".`
          : 'Belum ada bahan baku di kategori ini.'}
      </p>
    </div>
  {:else}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {#each filteredRawMaterials as mat}
        {@const isLowStock = mat.current_stock <= (mat.min_stock_alert || 0)}
        <div
          class="group flex flex-col justify-between space-y-4 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs transition-all hover:border-[#17171c]/40 hover:shadow-xs"
        >
          <div class="space-y-2">
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-1">
                <span class="font-mono text-[10px] font-semibold text-[#8e8e93] uppercase">
                  {mat.category_name ||
                    categories.find((c) => c.id === mat.category_id)?.name ||
                    'Raw Material'}
                </span>
                <h4 class="text-sm leading-snug font-bold text-[#17171c] group-hover:text-black">
                  {mat.name}
                </h4>
              </div>
              <button
                type="button"
                onclick={() => onPromptDelete(mat.id, mat.name)}
                class="shrink-0 cursor-pointer rounded-xl p-2 text-[#8e8e93] opacity-80 transition-all group-hover:opacity-100 hover:bg-[#fef2f2] hover:text-[#dc2626]"
                title="Hapus Bahan"
              >
                <Trash2 class="size-4" />
              </button>
            </div>

            <!-- Stock Status Metric -->
            <div class="flex items-baseline justify-between pt-1">
              <div>
                <span class="font-mono text-2xl font-bold text-[#17171c]">{mat.current_stock}</span>
                <span class="ml-1 font-mono text-xs text-[#8e8e93]">{mat.unit}</span>
              </div>

              {#if isLowStock}
                <div
                  class="flex items-center gap-1 rounded-full border border-[#fef3c7] bg-[#fffbeb] px-2.5 py-1 text-[10.5px] font-semibold text-[#d97706]"
                >
                  <AlertTriangle class="size-3.5" />
                  <span>Stok Menipis</span>
                </div>
              {:else}
                <span
                  class="rounded-full border border-[#a7f3d0] bg-[#ecfdf5] px-2.5 py-1 text-[10.5px] font-semibold text-[#059669]"
                >
                  Aman
                </span>
              {/if}
            </div>
          </div>

          <div class="flex items-center justify-between border-t border-[#f2f2f4] pt-3">
            <span class="font-mono text-[11px] text-[#8e8e93]">
              Min: {mat.min_stock_alert}
              {mat.unit}
            </span>
            <button
              type="button"
              onclick={() => onAdjustStock(mat)}
              class="cursor-pointer rounded-full bg-[#17171c] px-3.5 py-1.5 text-xs font-semibold text-white shadow-2xs transition-all hover:bg-black"
            >
              Sesuaikan Stok
            </button>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>
