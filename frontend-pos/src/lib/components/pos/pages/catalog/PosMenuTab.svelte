<script lang="ts">
  import { Search, Plus } from 'lucide-svelte';
  import type { Category, Product } from '../../../../types/pos';
  import { formatRupiah } from '../../../../utils/formatters';

  interface Props {
    products: Product[];
    categories: Category[];
    onToggleProductActive: (productId: string) => void;
    onOpenAddModal: () => void;
  }

  let {
    products = [],
    categories = [],
    onToggleProductActive,
    onOpenAddModal,
  }: Props = $props();

  let searchQuery = $state('');
  let selectedCategory = $state('cat-all');

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
</script>

<div class="space-y-4 font-sans">
  <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
    <div class="relative flex-1">
      <Search class="w-4 h-4 text-[#75758a] absolute left-3.5 top-1/2 -translate-y-1/2" />
      <input
        type="text"
        bind:value={searchQuery}
        placeholder="Cari produk &amp; minuman di kasir..."
        class="w-full pl-10 pr-4 py-2 bg-white border border-[#d9d9dd] rounded-full text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
      />
    </div>

    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 sm:pb-0">
      <button
        type="button"
        onclick={() => (selectedCategory = 'cat-all')}
        class={`px-3 py-1.5 rounded-full text-xs font-medium transition-all cursor-pointer shrink-0 ${
          selectedCategory === 'cat-all'
            ? 'bg-[#17171c] text-white'
            : 'bg-white border border-[#d9d9dd] text-[#616161] hover:text-[#17171c]'
        }`}
      >
        Semua Kategori
      </button>
      {#each categories as cat}
        <button
          type="button"
          onclick={() => (selectedCategory = cat.id)}
          class={`px-3 py-1.5 rounded-full text-xs font-medium transition-all cursor-pointer shrink-0 ${
            selectedCategory === cat.id
              ? 'bg-[#17171c] text-white'
              : 'bg-white border border-[#d9d9dd] text-[#616161] hover:text-[#17171c]'
          }`}
        >
          {cat.name}
        </button>
      {/each}

      <button
        type="button"
        onclick={onOpenAddModal}
        class="px-4 py-1.5 bg-[#17171c] hover:bg-black text-white rounded-full text-xs font-medium flex items-center gap-1.5 shrink-0 cursor-pointer shadow-xs"
      >
        <Plus class="w-3.5 h-3.5" />
        <span>Tambah Menu</span>
      </button>
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
    {#each filteredProducts as product}
      <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 flex flex-col justify-between space-y-3 hover:border-[#17171c] transition-all">
        <div class="space-y-1">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-mono text-[#75758a] uppercase">{product.category_id.replace('cat-', '')}</span>
            <span class={`w-2 h-2 rounded-full ${product.is_active ? 'bg-[#00875a]' : 'bg-[#e5484d]'}`}></span>
          </div>
          <h4 class="font-medium text-xs text-[#17171c] line-clamp-2">{product.name}</h4>
          {#if product.description}
            <p class="text-[10px] text-[#75758a] line-clamp-1">{product.description}</p>
          {/if}
        </div>

        <div class="pt-2 border-t border-[#f2f2f2] flex items-center justify-between gap-2">
          <span class="font-mono font-bold text-xs text-[#17171c]">{formatRupiah(product.base_price)}</span>
          <button
            type="button"
            onclick={() => onToggleProductActive(product.id)}
            class={`px-2.5 py-1 rounded-lg text-[10px] font-medium transition-colors cursor-pointer ${
              product.is_active
                ? 'bg-[#fee2e2] text-[#991b1b] hover:bg-[#fecaca]'
                : 'bg-[#dcfce7] text-[#14532d] hover:bg-[#bbf7d0]'
            }`}
          >
            {product.is_active ? 'Nonaktifkan' : 'Aktifkan'}
          </button>
        </div>
      </div>
    {/each}
  </div>
</div>
