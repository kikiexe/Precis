<script lang="ts">
  import { Search, Plus, Check, X } from 'lucide-svelte';
  import type { Product, Category } from '../../../types/pos';
  import { formatCurrency } from '../../../services/printer-service';

  interface Props {
    products: Product[];
    categories: Category[];
    onToggleProductActive: (productId: string) => void;
    onAddNewProduct: (product: Product) => void;
  }

  let { products = [], categories = [], onToggleProductActive, onAddNewProduct }: Props = $props();

  let searchQuery = $state('');
  let selectedCategory = $state('cat-all');
  let isAddModalOpen = $state(false);

  // New Product Form State
  let newName = $state('');
  let newCategory = $state('cat-coffee');
  let newPrice = $state(25000);
  let newDesc = $state('');

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

  function handleSaveProduct() {
    if (!newName.trim() || newPrice <= 0) return;
    const newProduct: Product = {
      id: `p-${Date.now()}`,
      category_id: newCategory,
      name: newName.trim(),
      base_price: newPrice,
      description: newDesc.trim() || undefined,
      is_active: true,
    };
    onAddNewProduct(newProduct);
    isAddModalOpen = false;
    newName = '';
    newDesc = '';
  }
</script>

<div class="flex-1 bg-[#eeece7]/30 p-4 sm:p-6 md:p-8 overflow-y-auto space-y-6 font-sans">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-xl font-medium text-[#212121] tracking-tight">Manajemen Menu &amp; Stok Produk</h2>
      <p class="text-xs text-[#616161] font-normal mt-0.5">Daftar item katalog penjualan, harga dasar, dan ketersediaan stok</p>
    </div>

    <button
      type="button"
      onclick={() => (isAddModalOpen = true)}
      class="bg-[#17171c] hover:bg-[#000000] text-white px-5 py-2.5 text-xs font-medium rounded-full flex items-center gap-2 cursor-pointer transition-all shadow-none"
    >
      <Plus class="w-4 h-4" />
      <span>Tambah Menu Baru</span>
    </button>
  </div>

  <!-- Search & Filter Controls -->
  <div class="bg-white border border-[#d9d9dd] rounded-[20px] p-4 flex flex-wrap gap-3 items-center justify-between shadow-none">
    <div class="relative flex-1 min-w-[240px]">
      <Search class="w-4 h-4 text-[#93939f] absolute left-3.5 top-1/2 -translate-y-1/2" />
      <input
        type="text"
        bind:value={searchQuery}
        placeholder="Cari nama menu atau deskripsi..."
        class="w-full bg-[#eeece7]/40 pl-10 pr-4 py-2 text-xs rounded-full border border-[#d9d9dd] placeholder-[#93939f] text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden transition-all"
      />
    </div>

    <div class="flex gap-1 overflow-x-auto bg-[#eeece7]/60 p-1 rounded-full border border-[#d9d9dd]">
      {#each categories as cat}
        <button
          type="button"
          onclick={() => (selectedCategory = cat.id)}
          class={`px-3.5 py-1 text-xs font-mono rounded-full transition-all cursor-pointer ${
            selectedCategory === cat.id
              ? 'bg-[#17171c] text-white font-medium shadow-none'
              : 'text-[#616161] hover:text-[#212121]'
          }`}
        >
          {cat.name}
        </button>
      {/each}
    </div>
  </div>

  <!-- Product Table List -->
  {#if filteredProducts.length === 0}
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] p-12 text-center space-y-3 shadow-none">
      <div class="w-12 h-12 bg-[#eeece7] text-[#93939f] rounded-full flex items-center justify-center mx-auto">
        <Search class="w-6 h-6" />
      </div>
      <div>
        <h3 class="text-sm font-medium text-[#212121]">Tidak Ada Menu Produk</h3>
        <p class="text-xs text-[#75758a] mt-0.5">Belum ada item menu yang terdaftar atau sesuai dengan filter pencarian.</p>
      </div>
      <button
        type="button"
        onclick={() => (isAddModalOpen = true)}
        class="px-5 py-2.5 bg-[#17171c] text-white text-xs font-medium rounded-full hover:bg-[#000000] transition-all inline-flex items-center gap-1.5 cursor-pointer"
      >
        <Plus class="w-3.5 h-3.5" />
        <span>Tambah Menu Baru</span>
      </button>
    </div>
  {:else}
    <div class="bg-white border border-[#d9d9dd] rounded-[20px] overflow-hidden shadow-none">
      <table class="w-full text-xs text-left border-collapse">
        <thead class="bg-[#eeece7]/50 border-b border-[#d9d9dd] font-mono text-[11px] text-[#616161]">
          <tr>
            <th class="p-3.5 font-medium">Nama Menu</th>
            <th class="p-3.5 font-medium">Kategori</th>
            <th class="p-3.5 font-medium">Harga Jual</th>
            <th class="p-3.5 font-medium">Status Ketersediaan</th>
            <th class="p-3.5 text-right font-medium">Aksi Cepat</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#d9d9dd]/60">
          {#each filteredProducts as product (product.id)}
            {@const catName = categories.find((c) => c.id === product.category_id)?.name || 'Menu'}
            <tr class="hover:bg-[#eeece7]/20 transition-colors">
              <td class="p-3.5">
                <div class="font-medium text-[#212121] text-sm">{product.name}</div>
                {#if product.description}
                  <div class="text-[11px] text-[#75758a] line-clamp-1 font-normal">{product.description}</div>
                {/if}
              </td>
              <td class="p-3.5 font-mono text-[#616161]">{catName}</td>
              <td class="p-3.5 font-mono font-medium text-[#212121]">{formatCurrency(product.base_price)}</td>
              <td class="p-3.5">
                <span class={`text-[10px] font-mono px-2.5 py-0.5 rounded-full font-medium ${
                  product.is_active
                    ? 'bg-[#edfce9] text-[#003c33]'
                    : 'bg-[#ffad9b]/20 text-[#b30000]'
                }`}>
                  {product.is_active ? 'Tersedia' : 'Stok Habis'}
                </span>
              </td>
              <td class="p-3.5 text-right">
                <button
                  type="button"
                  onclick={() => onToggleProductActive(product.id)}
                  class={`px-3.5 py-1 text-xs font-mono rounded-full border cursor-pointer transition-all ${
                    product.is_active
                      ? 'bg-white hover:bg-[#ffad9b]/15 text-[#b30000] border-[#d9d9dd]'
                      : 'bg-[#003c33] text-white hover:bg-[#002822] border-[#003c33]'
                  }`}
                >
                  {product.is_active ? 'Set Habis' : 'Aktifkan'}
                </button>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  {/if}
</div>

<!-- Modal Tambah Menu -->
{#if isAddModalOpen}
  <div class="fixed inset-0 z-50 bg-[#17171c]/40 backdrop-blur-xs flex items-center justify-center p-4 font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] max-w-md w-full p-6 shadow-none space-y-4">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <h3 class="text-sm font-medium text-[#212121]">Tambah Menu Produk Baru</h3>
        <button type="button" onclick={() => (isAddModalOpen = false)} class="text-[#93939f] hover:text-[#212121] cursor-pointer p-1">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="space-y-3.5 text-xs">
        <div>
          <label for="prod-name" class="block text-[#616161] font-medium mb-1.5">Nama Menu:</label>
          <input
            id="prod-name"
            type="text"
            bind:value={newName}
            placeholder="e.g. Hazelnut Latte Double"
            class="w-full bg-white border border-[#d9d9dd] rounded-[12px] p-2.5 text-[#212121] placeholder-[#93939f] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
          />
        </div>

        <div>
          <label for="prod-cat" class="block text-[#616161] font-medium mb-1.5">Kategori:</label>
          <select
            id="prod-cat"
            bind:value={newCategory}
            class="w-full bg-white border border-[#d9d9dd] rounded-[12px] p-2.5 text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
          >
            {#each categories.filter((c) => c.id !== 'cat-all') as cat}
              <option value={cat.id}>{cat.name}</option>
            {/each}
          </select>
        </div>

        <div>
          <label for="prod-price" class="block text-[#616161] font-medium mb-1.5">Harga Jual (Rp):</label>
          <input
            id="prod-price"
            type="number"
            bind:value={newPrice}
            step="1000"
            class="w-full bg-white border border-[#d9d9dd] rounded-[12px] p-2.5 font-mono font-medium text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
          />
        </div>

        <div>
          <label for="prod-desc" class="block text-[#616161] font-medium mb-1.5">Deskripsi Singkat:</label>
          <textarea
            id="prod-desc"
            bind:value={newDesc}
            rows="2"
            placeholder="e.g. Espresso, susu segar, hazelnut syrup..."
            class="w-full bg-white border border-[#d9d9dd] rounded-[12px] p-2.5 text-[#212121] placeholder-[#93939f] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
          ></textarea>
        </div>

        <div class="pt-3 flex gap-2.5 border-t border-[#d9d9dd]">
          <button
            type="button"
            onclick={() => (isAddModalOpen = false)}
            class="flex-1 py-2.5 bg-white text-[#616161] hover:bg-[#eeece7]/40 border border-[#d9d9dd] rounded-full text-xs font-medium cursor-pointer transition-all"
          >
            Batal
          </button>
          <button
            type="button"
            onclick={handleSaveProduct}
            disabled={!newName.trim() || newPrice <= 0}
            class="flex-2 py-2.5 bg-[#17171c] hover:bg-[#000000] text-white font-medium rounded-full flex items-center justify-center gap-1.5 cursor-pointer transition-all shadow-none disabled:opacity-50"
          >
            <Check class="w-3.5 h-3.5" />
            <span>Simpan Produk</span>
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}
