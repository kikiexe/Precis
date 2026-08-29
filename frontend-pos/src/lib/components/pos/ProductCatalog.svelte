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
      const matchCategory =
        selectedCategoryId === 'cat-all' || p.category_id === selectedCategoryId;
      const matchSearch =
        searchQuery.trim() === '' ||
        p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (p.description && p.description.toLowerCase().includes(searchQuery.toLowerCase()));
      return matchCategory && matchSearch && p.is_active;
    })
  );
</script>

<div class="flex h-full flex-1 flex-col overflow-hidden bg-[#f4f6f9] font-sans">
  <!-- Search & Category Bar -->
  <div class="shrink-0 space-y-2.5 border-b border-zinc-200 bg-white p-3 shadow-2xs">
    <!-- Search Box -->
    <div class="relative">
      <Search class="absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-zinc-400" />
      <input
        type="text"
        bind:value={searchQuery}
        placeholder="Cari menu minuman, makanan, atau snack..."
        class="w-full rounded-xl border border-zinc-200 bg-zinc-50 py-2 pr-4 pl-10 text-xs text-zinc-900 placeholder-zinc-400 transition-all focus:border-zinc-900 focus:bg-white focus:ring-1 focus:ring-zinc-900 focus:outline-hidden"
      />
      {#if searchQuery}
        <button
          type="button"
          onclick={() => (searchQuery = '')}
          class="absolute top-1/2 right-3.5 -translate-y-1/2 cursor-pointer p-1 font-mono text-xs text-zinc-400 hover:text-zinc-700"
        >
          ✕
        </button>
      {/if}
    </div>

    <!-- Category Tabs (Pills) -->
    <div class="no-scrollbar flex gap-1.5 overflow-x-auto pb-0.5">
      {#each categories as category}
        <button
          type="button"
          onclick={() => {
            onSelectCategory(category.id);
            catalogTab = 'library';
          }}
          class={`cursor-pointer rounded-lg border px-3 py-1.5 text-xs font-semibold whitespace-nowrap transition-all ${
            selectedCategoryId === category.id && catalogTab === 'library'
              ? 'border-zinc-900 bg-zinc-900 text-white shadow-xs'
              : 'border-zinc-200 bg-zinc-100/90 text-zinc-700 hover:bg-zinc-200/70 hover:text-zinc-900'
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
        <div class="flex h-64 flex-col items-center justify-center text-center text-zinc-400">
          <Coffee class="mb-2 size-10 text-zinc-400 opacity-40" />
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
              class="group active:scale-0.98 relative flex min-h-[135px] cursor-pointer flex-col justify-between rounded-xl border border-zinc-200 bg-white p-3 text-left shadow-2xs transition-all duration-150 select-none hover:border-zinc-900 hover:shadow-sm"
            >
              <div class="space-y-1.5">
                <!-- Abbreviation Badge -->
                <div
                  class="flex size-9 items-center justify-center rounded-lg border border-zinc-200 bg-zinc-100 text-xs font-bold text-zinc-900 transition-colors group-hover:bg-zinc-200"
                >
                  {abbr}
                </div>

                <div
                  class="line-clamp-2 text-xs leading-snug font-semibold text-zinc-900 transition-colors group-hover:text-black sm:text-[13px]"
                >
                  {product.name}
                </div>

                {#if product.description}
                  <div class="line-clamp-1 text-[10.5px] leading-tight font-normal text-zinc-500">
                    {product.description}
                  </div>
                {/if}
              </div>

              <div class="mt-2 flex items-center justify-between border-t border-zinc-100 pt-2">
                <span class="font-mono text-xs font-bold text-zinc-900 sm:text-[13px]">
                  {formatCurrency(product.base_price)}
                </span>
                <div
                  class="flex size-6 shrink-0 items-center justify-center rounded-md bg-zinc-100 text-zinc-700 transition-colors group-hover:bg-zinc-900 group-hover:text-white"
                >
                  <Plus class="size-3.5" />
                </div>
              </div>
            </button>
          {/each}
        </div>
      {/if}
    {:else}
      <!-- Custom Amount Keypad -->
      <div
        class="mx-auto max-w-md space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm"
      >
        <div>
          <h3 class="text-sm font-bold text-zinc-900">Input Nominal Manual (Custom Amount)</h3>
          <p class="text-xs text-zinc-500">Ketik nominal harga untuk item custom tanpa katalog</p>
        </div>

        <div class="space-y-2">
          <input
            type="text"
            bind:value={customItemName}
            placeholder="Nama Item (Opsional, e.g. Roti Bakar Custom)..."
            class="h-9 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-xs text-zinc-900 placeholder-zinc-400 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
          />

          <!-- Display Box -->
          <div
            class="flex items-center justify-between rounded-xl border border-zinc-200 bg-zinc-50 p-3"
          >
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
              class="flex h-12 cursor-pointer items-center justify-center rounded-xl bg-zinc-100 font-mono text-base font-bold text-zinc-900 transition-all hover:bg-zinc-200/80 active:bg-zinc-300"
            >
              {#if key === 'DEL'}
                <Delete class="size-5 text-zinc-600" />
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
            class="flex-1 cursor-pointer rounded-xl bg-zinc-100 py-3 text-xs font-semibold text-zinc-700 transition-all hover:bg-zinc-200"
          >
            Reset
          </button>
          <button
            type="button"
            disabled={parseInt(customAmount || '0', 10) <= 0}
            onclick={handleAddCustomItem}
            class={`flex flex-2 cursor-pointer items-center justify-center gap-2 rounded-xl py-3 text-xs font-semibold transition-all ${
              parseInt(customAmount || '0', 10) <= 0
                ? 'cursor-not-allowed bg-zinc-200 text-zinc-400'
                : 'bg-zinc-900 text-white shadow-xs hover:bg-black'
            }`}
          >
            <Plus class="size-4" />
            <span>Tambah ke Pesanan</span>
          </button>
        </div>
      </div>
    {/if}
  </div>

  <!-- Bottom Navigation Switcher (Library vs Custom) -->
  <div
    class="flex shrink-0 items-center justify-center gap-2 border-t border-zinc-200 bg-white px-4 py-2 shadow-2xs"
  >
    <button
      type="button"
      onclick={() => (catalogTab = 'library')}
      class={`flex max-w-50 flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border py-1.5 text-xs font-semibold transition-all ${
        catalogTab === 'library'
          ? 'border-zinc-900 bg-zinc-900 text-white shadow-2xs'
          : 'border-zinc-200 bg-zinc-100 text-zinc-600 hover:text-zinc-900'
      }`}
    >
      <LayoutGrid class="size-3.5" />
      <span>Library</span>
    </button>

    <button
      type="button"
      onclick={() => (catalogTab = 'custom')}
      class={`flex max-w-50 flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border py-1.5 text-xs font-semibold transition-all ${
        catalogTab === 'custom'
          ? 'border-zinc-900 bg-zinc-900 text-white shadow-2xs'
          : 'border-zinc-200 bg-zinc-100 text-zinc-600 hover:text-zinc-900'
      }`}
    >
      <Calculator class="size-3.5" />
      <span>Custom</span>
    </button>
  </div>
</div>
