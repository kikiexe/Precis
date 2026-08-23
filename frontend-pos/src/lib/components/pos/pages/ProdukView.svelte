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

<div class="flex-1 bg-[#f4f4f4] p-6 overflow-y-auto space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-xl font-bold text-[#161616] font-display">Manajemen Menu &amp; Stok Produk</h2>
      <p class="text-xs text-[#525252] font-mono">Daftar item katalog penjualan, harga dasar, dan ketersediaan stok</p>
    </div>

    <button
      type="button"
      onclick={() => (isAddModalOpen = true)}
      class="bg-[#0f62fe] hover:bg-[#0050e6] text-white px-4 py-2.5 text-xs font-semibold flex items-center gap-2 cursor-pointer shadow-xs transition-colors"
    >
      <Plus class="w-4 h-4" />
      <span>Tambah Menu Baru</span>
    </button>
  </div>

  <!-- Search & Filter Controls -->
  <div class="bg-white border border-[#e0e0e0] p-4 flex flex-wrap gap-3 items-center justify-between shadow-xs">
    <div class="relative flex-1 min-w-[240px]">
      <Search class="w-4 h-4 text-[#8c8c8c] absolute left-3 top-1/2 -translate-y-1/2" />
      <input
        type="text"
        bind:value={searchQuery}
        placeholder="Cari nama menu atau deskripsi..."
        class="w-full bg-[#f4f4f4] pl-9 pr-3 py-2 text-xs border border-[#e0e0e0] focus:border-[#0f62fe] focus:outline-none"
      />
    </div>

    <div class="flex gap-1 overflow-x-auto">
      {#each categories as cat}
        <button
          type="button"
          onclick={() => (selectedCategory = cat.id)}
          class={`px-3 py-1.5 text-xs font-mono transition-colors border cursor-pointer ${
            selectedCategory === cat.id
              ? 'bg-[#0f62fe] text-white border-[#0f62fe] font-bold shadow-xs'
              : 'bg-[#f4f4f4] text-[#525252] border-[#e0e0e0] hover:bg-[#e0e0e0]'
          }`}
        >
          {cat.name}
        </button>
      {/each}
    </div>
  </div>

  <!-- Product Table List -->
  {#if filteredProducts.length === 0}
    <div class="bg-white border border-[#e0e0e0] p-12 text-center space-y-3 shadow-xs">
      <div class="w-12 h-12 bg-[#f4f4f4] text-[#8c8c8c] flex items-center justify-center mx-auto">
        <Search class="w-6 h-6" />
      </div>
      <div>
        <h3 class="text-sm font-bold text-[#161616]">Tidak Ada Menu Produk</h3>
        <p class="text-xs text-[#8c8c8c] mt-0.5">Belum ada item menu yang terdaftar atau sesuai dengan filter pencarian.</p>
      </div>
      <button
        type="button"
        onclick={() => (isAddModalOpen = true)}
        class="px-4 py-2 bg-[#0f62fe] text-white text-xs font-semibold hover:bg-[#0050e6] transition-colors inline-flex items-center gap-1.5"
      >
        <Plus class="w-3.5 h-3.5" />
        <span>Tambah Menu Baru</span>
      </button>
    </div>
  {:else}
    <div class="bg-white border border-[#e0e0e0] overflow-x-auto shadow-xs">
      <table class="w-full text-xs text-left">
        <thead class="bg-[#f4f4f4] border-b border-[#e0e0e0] font-mono text-[11px] text-[#525252]">
          <tr>
            <th class="p-3">Nama Menu</th>
            <th class="p-3">Kategori</th>
            <th class="p-3">Harga Jual</th>
            <th class="p-3">Status Ketersediaan</th>
            <th class="p-3 text-right">Aksi Cepat</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#f4f4f4]">
          {#each filteredProducts as product (product.id)}
            {@const catName = categories.find((c) => c.id === product.category_id)?.name || 'Menu'}
            <tr class="hover:bg-[#f4f4f4]/60 transition-colors">
              <td class="p-3">
                <div class="font-bold text-[#161616] text-sm">{product.name}</div>
                {#if product.description}
                  <div class="text-[11px] text-[#8c8c8c] line-clamp-1">{product.description}</div>
                {/if}
              </td>
              <td class="p-3 font-mono text-[#525252]">{catName}</td>
              <td class="p-3 font-mono font-bold text-[#161616]">{formatCurrency(product.base_price)}</td>
              <td class="p-3">
                <span class={`text-[10px] font-mono px-2 py-0.5 border ${
                  product.is_active
                    ? 'bg-[#24a148]/10 text-[#24a148] border-[#24a148]/30'
                    : 'bg-[#da1e28]/10 text-[#da1e28] border-[#da1e28]/30'
                }`}>
                  {product.is_active ? 'Tersedia' : 'Stok Habis'}
                </span>
              </td>
              <td class="p-3 text-right">
                <button
                  type="button"
                  onclick={() => onToggleProductActive(product.id)}
                  class={`px-3 py-1 text-xs font-mono border cursor-pointer transition-colors ${
                    product.is_active
                      ? 'bg-[#f4f4f4] hover:bg-[#da1e28]/10 text-[#da1e28] border-[#e0e0e0]'
                      : 'bg-[#24a148] text-white hover:bg-[#1e8a3d] border-[#24a148]'
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
  <div class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-[#e0e0e0] max-w-md w-full p-6 shadow-2xl space-y-4">
      <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3">
        <h3 class="text-sm font-bold text-[#161616]">Tambah Menu Produk Baru</h3>
        <button type="button" onclick={() => (isAddModalOpen = false)} class="text-[#8c8c8c] hover:text-[#161616] cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="space-y-3 text-xs">
        <div>
          <label for="prod-name" class="block font-mono text-[#525252] mb-1">Nama Menu:</label>
          <input
            id="prod-name"
            type="text"
            bind:value={newName}
            placeholder="e.g. Hazelnut Latte Double"
            class="w-full bg-[#f4f4f4] border border-[#e0e0e0] p-2 focus:border-[#0f62fe] focus:outline-none"
          />
        </div>

        <div>
          <label for="prod-cat" class="block font-mono text-[#525252] mb-1">Kategori:</label>
          <select
            id="prod-cat"
            bind:value={newCategory}
            class="w-full bg-[#f4f4f4] border border-[#e0e0e0] p-2 focus:border-[#0f62fe] focus:outline-none"
          >
            {#each categories.filter((c) => c.id !== 'cat-all') as cat}
              <option value={cat.id}>{cat.name}</option>
            {/each}
          </select>
        </div>

        <div>
          <label for="prod-price" class="block font-mono text-[#525252] mb-1">Harga Jual (Rp):</label>
          <input
            id="prod-price"
            type="number"
            bind:value={newPrice}
            step="1000"
            class="w-full bg-[#f4f4f4] border border-[#e0e0e0] p-2 font-mono font-bold focus:border-[#0f62fe] focus:outline-none"
          />
        </div>

        <div>
          <label for="prod-desc" class="block font-mono text-[#525252] mb-1">Deskripsi Singkat:</label>
          <textarea
            id="prod-desc"
            bind:value={newDesc}
            rows="2"
            placeholder="e.g. Espresso, susu segar, hazelnut syrup..."
            class="w-full bg-[#f4f4f4] border border-[#e0e0e0] p-2 focus:border-[#0f62fe] focus:outline-none"
          ></textarea>
        </div>

        <div class="pt-2 flex gap-2">
          <button
            type="button"
            onclick={() => (isAddModalOpen = false)}
            class="flex-1 py-2 bg-[#f4f4f4] text-[#525252] border border-[#e0e0e0] cursor-pointer"
          >
            Batal
          </button>
          <button
            type="button"
            onclick={handleSaveProduct}
            disabled={!newName.trim() || newPrice <= 0}
            class="flex-2 py-2 bg-[#0f62fe] hover:bg-[#0050e6] text-white font-semibold flex items-center justify-center gap-1.5 cursor-pointer disabled:bg-[#e0e0e0] disabled:text-[#8c8c8c]"
          >
            <Check class="w-3.5 h-3.5" />
            <span>Simpan Produk</span>
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}
