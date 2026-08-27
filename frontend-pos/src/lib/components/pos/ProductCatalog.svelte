<script lang="ts">
  import { Search, Plus, Coffee } from 'lucide-svelte';
  import type { Product, Category } from '../../types/pos';
  import { formatCurrency } from '../../services/printer-service';

  interface Props {
    categories: Category[];
    products: Product[];
    selectedCategoryId: string;
    onSelectCategory: (id: string) => void;
    onAddToCart: (product: Product) => void;
  }

  let {
    categories = [],
    products = [],
    selectedCategoryId = 'cat-all',
    onSelectCategory,
    onAddToCart,
  }: Props = $props();

  let searchQuery = $state('');

  let filteredProducts = $derived(
    products.filter((p) => {
      const matchCategory = selectedCategoryId === 'cat-all' || p.category_id === selectedCategoryId;
      const matchSearch =
        searchQuery.trim() === '' ||
        p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (p.description && p.description.toLowerCase().includes(searchQuery.toLowerCase()));
      return matchCategory && matchSearch && p.is_active;
    })
  );
</script>

<div class="flex-1 flex flex-col h-full bg-[#eeece7]/30 overflow-hidden font-sans">
  <!-- Search & Category Bar -->
  <div class="bg-white border-b border-[#d9d9dd] p-4 space-y-3 shrink-0">
    <!-- Search Box -->
    <div class="relative">
      <Search class="w-4 h-4 text-[#93939f] absolute left-3.5 top-1/2 -translate-y-1/2" />
      <input
        type="text"
        bind:value={searchQuery}
        placeholder="Cari menu minuman, makanan, atau snack..."
        class="w-full bg-[#eeece7]/40 text-[#212121] pl-10 pr-4 py-2.5 text-xs rounded-full border border-[#d9d9dd] placeholder-[#93939f] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden transition-all"
      />
      {#if searchQuery}
        <button
          type="button"
          onclick={() => (searchQuery = '')}
          class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-mono text-[#93939f] hover:text-[#212121] p-1"
        >
          ✕
        </button>
      {/if}
    </div>

    <!-- Category Tabs -->
    <div class="flex gap-2 overflow-x-auto no-scrollbar pb-0.5">
      {#each categories as category}
        <button
          type="button"
          onclick={() => onSelectCategory(category.id)}
          class={`px-4 py-2 text-xs font-medium whitespace-nowrap transition-all rounded-full cursor-pointer border ${
            selectedCategoryId === category.id
              ? 'bg-[#17171c] text-white border-[#17171c] shadow-none'
              : 'bg-[#eeece7]/50 text-[#616161] hover:text-[#212121] border-[#d9d9dd] hover:bg-[#eeece7]'
          }`}
        >
          {category.name}
        </button>
      {/each}
    </div>
  </div>

  <!-- Product Grid (Touch Optimized for Tablet) -->
  <div class="flex-1 overflow-y-auto p-4 sm:p-5">
    {#if filteredProducts.length === 0}
      <div class="h-64 flex flex-col items-center justify-center text-center text-[#93939f]">
        <Coffee class="w-10 h-10 mb-2 opacity-40 text-[#93939f]" />
        <p class="text-sm font-medium text-[#212121]">Menu tidak ditemukan</p>
        <p class="text-xs text-[#75758a]">Coba ubah kata kunci pencarian atau kategori.</p>
      </div>
    {:else}
      <div class="grid grid-cols-[repeat(auto-fill,minmax(175px,1fr))] gap-3.5 sm:gap-4">
        {#each filteredProducts as product (product.id)}
          <button
            type="button"
            onclick={() => onAddToCart(product)}
            class="bg-white border border-[#d9d9dd] hover:border-[#17171c] rounded-[20px] p-4 text-left flex flex-col justify-between transition-all duration-150 active:scale-[0.98] group cursor-pointer min-h-[148px] relative select-none shadow-2xs hover:shadow-xs"
          >
            <div class="space-y-1">
              <div class="font-bold text-xs sm:text-[13px] text-[#17171c] group-hover:text-black transition-colors line-clamp-2 leading-snug">
                {product.name}
              </div>
              {#if product.description}
                <div class="text-[10.5px] text-[#8e8e93] line-clamp-2 leading-tight font-normal">
                  {product.description}
                </div>
              {/if}
            </div>

            <div class="flex items-center justify-between pt-2.5 mt-2 border-t border-[#f2f2f4]">
              <span class="font-mono font-bold text-xs sm:text-[13px] text-[#17171c]">
                {formatCurrency(product.base_price)}
              </span>
              <div class="w-7 h-7 rounded-xl bg-[#f4f4f6] group-hover:bg-[#17171c] group-hover:text-white text-[#17171c] flex items-center justify-center transition-colors shrink-0">
                <Plus class="w-3.5 h-3.5" />
              </div>
            </div>
          </button>
        {/each}
      </div>
    {/if}
  </div>
</div>
