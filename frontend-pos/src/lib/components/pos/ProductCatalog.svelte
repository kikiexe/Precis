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

<div class="flex-1 flex flex-col h-full bg-[#f4f4f4] overflow-hidden">
  <!-- Search & Category Bar -->
  <div class="bg-white border-b border-[#e0e0e0] p-3 space-y-3 shrink-0">
    <!-- Search Box -->
    <div class="relative">
      <Search class="w-4 h-4 text-[#8c8c8c] absolute left-3 top-1/2 -translate-y-1/2" />
      <input
        type="text"
        bind:value={searchQuery}
        placeholder="Cari menu minuman, makanan, atau snack..."
        class="w-full bg-[#f4f4f4] text-[#161616] pl-9 pr-4 py-2.5 text-sm border border-[#e0e0e0] focus:border-[#0f62fe] focus:outline-none transition-colors"
      />
      {#if searchQuery}
        <button
          type="button"
          onclick={() => (searchQuery = '')}
          class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-mono text-[#8c8c8c] hover:text-[#161616]"
        >
          ✕
        </button>
      {/if}
    </div>

    <!-- Category Tabs -->
    <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
      {#each categories as category}
        <button
          type="button"
          onclick={() => onSelectCategory(category.id)}
          class={`px-4 py-2 text-xs font-medium whitespace-nowrap transition-colors border cursor-pointer ${
            selectedCategoryId === category.id
              ? 'bg-[#0f62fe] text-white border-[#0f62fe] shadow-xs'
              : 'bg-white text-[#525252] hover:text-[#161616] border-[#e0e0e0] hover:bg-[#f4f4f4]'
          }`}
        >
          {category.name}
        </button>
      {/each}
    </div>
  </div>

  <!-- Product Grid (Touch Optimized for Tablet) -->
  <div class="flex-1 overflow-y-auto p-4">
    {#if filteredProducts.length === 0}
      <div class="h-64 flex flex-col items-center justify-center text-center text-[#8c8c8c]">
        <Coffee class="w-10 h-10 mb-2 opacity-40 text-[#8c8c8c]" />
        <p class="text-sm font-medium text-[#525252]">Menu tidak ditemukan</p>
        <p class="text-xs text-[#8c8c8c]">Coba ubah kata kunci pencarian atau kategori.</p>
      </div>
    {:else}
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
        {#each filteredProducts as product (product.id)}
          <button
            type="button"
            onclick={() => onAddToCart(product)}
            class="bg-white border border-[#e0e0e0] hover:border-[#0f62fe] p-3.5 text-left flex flex-col justify-between transition-all duration-150 active:scale-[0.98] group cursor-pointer h-36 relative select-none hover:shadow-xs"
          >
            <div>
              <div class="font-medium text-sm text-[#161616] group-hover:text-[#0f62fe] transition-colors line-clamp-2 leading-snug">
                {product.name}
              </div>
              {#if product.description}
                <div class="text-[11px] text-[#8c8c8c] line-clamp-2 mt-1 leading-tight">
                  {product.description}
                </div>
              {/if}
            </div>

            <div class="flex items-end justify-between mt-2 pt-2 border-t border-[#f4f4f4]">
              <div class="font-mono text-sm font-semibold text-[#161616]">
                {formatCurrency(product.base_price)}
              </div>
              <div class="w-7 h-7 bg-[#f4f4f4] group-hover:bg-[#0f62fe] group-hover:text-white text-[#525252] flex items-center justify-center transition-colors">
                <Plus class="w-4 h-4" />
              </div>
            </div>
          </button>
        {/each}
      </div>
    {/if}
  </div>
</div>
