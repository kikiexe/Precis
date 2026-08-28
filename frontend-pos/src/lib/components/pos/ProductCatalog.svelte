<script lang="ts">
  import { Search, Plus, Coffee, LayoutGrid, Calculator, Delete } from 'lucide-svelte';
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
  let catalogTab = $state<'library' | 'custom'>('library');

  // Custom manual amount keypad state
  let customAmount = $state('0');
  let customItemName = $state('');

  function handleKeypadInput(val: string) {
    if (val === 'C') {
      customAmount = '0';
    } else if (val === 'DEL') {
      customAmount = customAmount.length > 1 ? customAmount.slice(0, -1) : '0';
    } else if (val === '000') {
      if (customAmount !== '0') customAmount += '000';
    } else {
      customAmount = customAmount === '0' ? val : customAmount + val;
    }
  }

  function handleAddCustomItem() {
    const price = parseInt(customAmount, 10);
    if (price <= 0) return;

    const customProduct: Product = {
      id: 'custom-' + Date.now(),
      category_id: 'cat-custom',
      name: customItemName.trim() || `Custom Item (Rp ${price.toLocaleString('id-ID')})`,
      base_price: price,
      is_active: true,
      description: 'Manual Custom Amount',
    };

    onAddToCart(customProduct);
    customAmount = '0';
    customItemName = '';
  }

  function getCategoryAbbr(name: string): string {
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) {
      return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2);
  }

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

<div class="flex-1 flex flex-col h-full bg-[#f4f6f9] overflow-hidden font-sans">
  <!-- Search & Category Bar -->
  <div class="bg-white border-b border-zinc-200 p-3 space-y-2.5 shrink-0 shadow-2xs">
    <!-- Search Box -->
    <div class="relative">
      <Search class="w-4 h-4 text-zinc-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
      <input
        type="text"
        bind:value={searchQuery}
        placeholder="Cari menu minuman, makanan, atau snack..."
        class="w-full bg-zinc-50 text-zinc-900 pl-10 pr-4 py-2 text-xs rounded-xl border border-zinc-200 placeholder-zinc-400 focus:bg-white focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:outline-hidden transition-all"
      />
      {#if searchQuery}
        <button
          type="button"
          onclick={() => (searchQuery = '')}
          class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-mono text-zinc-400 hover:text-zinc-700 p-1 cursor-pointer"
        >
          ✕
        </button>
      {/if}
    </div>

    <!-- Category Tabs (Pills) -->
    <div class="flex gap-1.5 overflow-x-auto no-scrollbar pb-0.5">
      {#each categories as category}
        <button
          type="button"
          onclick={() => {
            onSelectCategory(category.id);
            catalogTab = 'library';
          }}
          class={`px-3 py-1.5 text-xs font-semibold whitespace-nowrap transition-all rounded-lg cursor-pointer border ${
            selectedCategoryId === category.id && catalogTab === 'library'
              ? 'bg-zinc-900 text-white border-zinc-900 shadow-xs'
              : 'bg-zinc-100/90 text-zinc-700 hover:text-zinc-900 border-zinc-200 hover:bg-zinc-200/70'
          }`}
        >
          {category.name}
        </button>
      {/each}
    </div>
  </div>

  <!-- Content: Library (Product Grid) vs Custom (Manual Calculator) -->
  <div class="flex-1 overflow-y-auto p-3.5 sm:p-4">
    {#if catalogTab === 'library'}
      {#if filteredProducts.length === 0}
        <div class="h-64 flex flex-col items-center justify-center text-center text-zinc-400">
          <Coffee class="w-10 h-10 mb-2 opacity-40 text-zinc-400" />
          <p class="text-sm font-medium text-zinc-800">Menu tidak ditemukan</p>
          <p class="text-xs text-zinc-500">Coba ubah kata kunci pencarian atau kategori.</p>
        </div>
      {:else}
        <!-- Moka-Inspired Product Grid in Clean Black Accents -->
        <div class="grid grid-cols-[repeat(auto-fill,minmax(160px,1fr))] gap-3">
          {#each filteredProducts as product (product.id)}
            {@const catName = categories.find((c) => c.id === product.category_id)?.name || 'Menu'}
            {@const abbr = getCategoryAbbr(product.name || catName)}
            <button
              type="button"
              onclick={() => onAddToCart(product)}
              class="bg-white border border-zinc-200 hover:border-zinc-900 rounded-xl p-3 text-left flex flex-col justify-between transition-all duration-150 active:scale-[0.98] group cursor-pointer min-h-[135px] relative select-none shadow-2xs hover:shadow-sm"
            >
              <div class="space-y-1.5">
                <!-- Abbreviation Badge -->
                <div class="w-9 h-9 rounded-lg bg-zinc-100 group-hover:bg-zinc-200 text-zinc-900 font-bold text-xs flex items-center justify-center border border-zinc-200 transition-colors">
                  {abbr}
                </div>

                <div class="font-semibold text-xs sm:text-[13px] text-zinc-900 group-hover:text-black transition-colors line-clamp-2 leading-snug">
                  {product.name}
                </div>

                {#if product.description}
                  <div class="text-[10.5px] text-zinc-500 line-clamp-1 leading-tight font-normal">
                    {product.description}
                  </div>
                {/if}
              </div>

              <div class="flex items-center justify-between pt-2 mt-2 border-t border-zinc-100">
                <span class="font-mono font-bold text-xs sm:text-[13px] text-zinc-900">
                  {formatCurrency(product.base_price)}
                </span>
                <div class="w-6 h-6 rounded-md bg-zinc-100 group-hover:bg-zinc-900 group-hover:text-white text-zinc-700 flex items-center justify-center transition-colors shrink-0">
                  <Plus class="w-3.5 h-3.5" />
                </div>
              </div>
            </button>
          {/each}
        </div>
      {/if}
    {:else}
      <!-- Custom Amount Keypad -->
      <div class="max-w-md mx-auto bg-white rounded-2xl border border-zinc-200 p-5 shadow-sm space-y-4">
        <div>
          <h3 class="font-bold text-sm text-zinc-900">Input Nominal Manual (Custom Amount)</h3>
          <p class="text-xs text-zinc-500">Ketik nominal harga untuk item custom tanpa katalog</p>
        </div>

        <div class="space-y-2">
          <input
            type="text"
            bind:value={customItemName}
            placeholder="Nama Item (Opsional, e.g. Roti Bakar Custom)..."
            class="w-full h-9 bg-zinc-50 border border-zinc-200 rounded-lg px-3 text-xs text-zinc-900 placeholder-zinc-400 focus:bg-white focus:border-zinc-900 focus:outline-hidden"
          />

          <!-- Display Box -->
          <div class="p-3 bg-zinc-50 border border-zinc-200 rounded-xl flex items-center justify-between">
            <span class="text-xs font-medium text-zinc-500">Nominal:</span>
            <span class="font-mono text-xl font-bold text-zinc-900">
              Rp {parseInt(customAmount || '0', 10).toLocaleString('id-ID')}
            </span>
          </div>
        </div>

        <!-- Keypad Grid -->
        <div class="grid grid-cols-3 gap-2">
          {#each ['1', '2', '3', '4', '5', '6', '7', '8', '9', '000', '0', 'DEL'] as key}
            <button
              type="button"
              onclick={() => handleKeypadInput(key)}
              class="h-12 bg-zinc-100 hover:bg-zinc-200/80 active:bg-zinc-300 text-zinc-900 font-mono font-bold text-base rounded-xl transition-all cursor-pointer flex items-center justify-center"
            >
              {#if key === 'DEL'}
                <Delete class="w-5 h-5 text-zinc-600" />
              {:else}
                {key}
              {/if}
            </button>
          {/each}
        </div>

        <div class="flex gap-2 pt-2">
          <button
            type="button"
            onclick={() => handleKeypadInput('C')}
            class="flex-1 py-3 text-xs font-semibold rounded-xl bg-zinc-100 hover:bg-zinc-200 text-zinc-700 transition-all cursor-pointer"
          >
            Reset
          </button>
          <button
            type="button"
            disabled={parseInt(customAmount || '0', 10) <= 0}
            onclick={handleAddCustomItem}
            class={`flex-2 py-3 text-xs font-semibold rounded-xl flex items-center justify-center gap-2 transition-all cursor-pointer ${
              parseInt(customAmount || '0', 10) <= 0
                ? 'bg-zinc-200 text-zinc-400 cursor-not-allowed'
                : 'bg-zinc-900 hover:bg-black text-white shadow-xs'
            }`}
          >
            <Plus class="w-4 h-4" />
            <span>Tambah ke Pesanan</span>
          </button>
        </div>
      </div>
    {/if}
  </div>

  <!-- Bottom Navigation Switcher (Library vs Custom) -->
  <div class="bg-white border-t border-zinc-200 px-4 py-2 flex items-center justify-center gap-2 shrink-0 shadow-2xs">
    <button
      type="button"
      onclick={() => (catalogTab = 'library')}
      class={`flex-1 max-w-[200px] py-1.5 rounded-lg text-xs font-semibold flex items-center justify-center gap-2 transition-all cursor-pointer border ${
        catalogTab === 'library'
          ? 'bg-zinc-900 text-white border-zinc-900 shadow-2xs'
          : 'bg-zinc-100 text-zinc-600 hover:text-zinc-900 border-zinc-200'
      }`}
    >
      <LayoutGrid class="w-3.5 h-3.5" />
      <span>Library</span>
    </button>

    <button
      type="button"
      onclick={() => (catalogTab = 'custom')}
      class={`flex-1 max-w-[200px] py-1.5 rounded-lg text-xs font-semibold flex items-center justify-center gap-2 transition-all cursor-pointer border ${
        catalogTab === 'custom'
          ? 'bg-zinc-900 text-white border-zinc-900 shadow-2xs'
          : 'bg-zinc-100 text-zinc-600 hover:text-zinc-900 border-zinc-200'
      }`}
    >
      <Calculator class="w-3.5 h-3.5" />
      <span>Custom</span>
    </button>
  </div>
</div>
