<script lang="ts">
  import { Search, Trash2 } from 'lucide-svelte';
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
</script>

<div class="space-y-4 font-sans">
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

  <!-- Raw Materials Grid -->
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

        <!-- Stats Metric Grid -->
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
            onclick={() => onAdjustStock(mat)}
            class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-xl transition-all cursor-pointer text-center"
          >
            Sesuaikan Stok (Opname)
          </button>

          <button
            type="button"
            onclick={() => onPromptDelete(mat.id, mat.name)}
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
