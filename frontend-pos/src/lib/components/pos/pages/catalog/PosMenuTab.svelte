<script lang="ts">
  import { Search, Plus, CheckCircle2, XCircle } from 'lucide-svelte';
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

  let activeCount = $derived(products.filter((p) => p.is_active).length);
</script>

<div class="space-y-3 font-sans">
  <!-- Excel Toolbar: Search, Category Filter, and Add Button -->
  <div class="bg-white border border-zinc-200 rounded-xl p-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shadow-2xs">
    <div class="flex items-center gap-2 flex-1">
      <div class="relative flex-1 max-w-md">
        <Search class="w-4 h-4 text-zinc-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Cari nama produk, minuman, atau makanan..."
          class="w-full pl-9 pr-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg text-xs text-zinc-900 placeholder-zinc-400 focus:bg-white focus:border-zinc-900 focus:outline-hidden transition-all"
        />
        {#if searchQuery}
          <button
            type="button"
            onclick={() => (searchQuery = '')}
            class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-mono text-zinc-400 hover:text-zinc-700 cursor-pointer"
          >
            ✕
          </button>
        {/if}
      </div>

      <!-- Category Filter Dropdown -->
      <select
        bind:value={selectedCategory}
        class="h-9 px-3 bg-zinc-50 border border-zinc-200 rounded-lg text-xs font-medium text-zinc-700 cursor-pointer focus:bg-white focus:border-zinc-900 focus:outline-hidden"
      >
        <option value="cat-all">Semua Kategori ({products.length})</option>
        {#each categories as cat}
          <option value={cat.id}>{cat.name}</option>
        {/each}
      </select>
    </div>

    <button
      type="button"
      onclick={onOpenAddModal}
      class="px-4 py-2 bg-zinc-900 hover:bg-black text-white rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 shrink-0 cursor-pointer shadow-xs transition-all active:scale-[0.99]"
    >
      <Plus class="w-3.5 h-3.5" />
      <span>+ Tambah Menu Baru</span>
    </button>
  </div>

  <!-- Excel-like Spreadsheet Table -->
  <div class="bg-white border border-zinc-200 rounded-xl overflow-hidden shadow-2xs">
    <div class="overflow-x-auto">
      <table class="w-full text-xs text-left border-collapse">
        <thead class="bg-zinc-100/80 border-b border-zinc-200 font-mono text-[11px] font-bold text-zinc-600 uppercase tracking-wider">
          <tr class="divide-x divide-zinc-200/80">
            <th class="py-3 px-3 w-12 text-center">No.</th>
            <th class="py-3 px-4">Nama Produk / Menu</th>
            <th class="py-3 px-4 w-40">Kategori</th>
            <th class="py-3 px-4 w-36 text-right">Harga Jual</th>
            <th class="py-3 px-4 w-32 text-center">Status</th>
            <th class="py-3 px-4 w-36 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200/70">
          {#if filteredProducts.length === 0}
            <tr>
              <td colspan="6" class="py-16 text-center text-zinc-400">
                <p class="text-sm font-semibold text-zinc-800">Tidak ada data produk</p>
                <p class="text-xs text-zinc-500 mt-0.5">Coba ubah kata kunci pencarian atau kategori.</p>
              </td>
            </tr>
          {:else}
            {#each filteredProducts as product, idx (product.id)}
              {@const catName = categories.find((c) => c.id === product.category_id)?.name || product.category_id.replace('cat-', '')}
              <tr class={`divide-x divide-zinc-200/60 transition-colors hover:bg-zinc-50/80 ${
                idx % 2 === 1 ? 'bg-zinc-50/30' : 'bg-white'
              }`}>
                <!-- No -->
                <td class="py-3 px-3 font-mono text-center text-zinc-400 text-[11px]">
                  {idx + 1}
                </td>

                <!-- Nama Produk & Deskripsi -->
                <td class="py-3 px-4 font-semibold text-zinc-900">
                  <div>{product.name}</div>
                  {#if product.description}
                    <div class="text-[11px] text-zinc-500 font-normal mt-0.5 line-clamp-1">
                      {product.description}
                    </div>
                  {/if}
                </td>

                <!-- Kategori -->
                <td class="py-3 px-4">
                  <span class="inline-block px-2.5 py-0.5 bg-zinc-100 text-zinc-700 rounded-md text-[11px] font-medium border border-zinc-200/60 truncate max-w-[140px]">
                    {catName}
                  </span>
                </td>

                <!-- Harga Jual -->
                <td class="py-3 px-4 font-mono font-bold text-right text-xs text-zinc-900">
                  {formatRupiah(product.base_price)}
                </td>

                <!-- Status Penjualan -->
                <td class="py-3 px-4 text-center">
                  <span class={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold ${
                    product.is_active
                      ? 'bg-emerald-100 text-emerald-800'
                      : 'bg-zinc-200 text-zinc-600'
                  }`}>
                    {#if product.is_active}
                      <CheckCircle2 class="w-3 h-3 text-emerald-600" />
                      <span>Aktif</span>
                    {:else}
                      <XCircle class="w-3 h-3 text-zinc-500" />
                      <span>Nonaktif</span>
                    {/if}
                  </span>
                </td>

                <!-- Aksi -->
                <td class="py-3 px-4 text-right">
                  <button
                    type="button"
                    onclick={() => onToggleProductActive(product.id)}
                    class={`px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-colors cursor-pointer ${
                      product.is_active
                        ? 'bg-red-50 text-red-700 hover:bg-red-100 border border-red-200'
                        : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200'
                    }`}
                  >
                    {product.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                  </button>
                </td>
              </tr>
            {/each}
          {/if}
        </tbody>
      </table>
    </div>

    <!-- Excel Status Bar / Summary Footer -->
    <div class="bg-zinc-50 border-t border-zinc-200 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs font-mono text-zinc-600">
      <div class="flex items-center gap-4">
        <span>Total Menu: <strong class="text-zinc-900">{filteredProducts.length}</strong></span>
        <span>Menu Aktif: <strong class="text-emerald-700">{activeCount}</strong></span>
        <span>Menu Nonaktif: <strong class="text-zinc-500">{products.length - activeCount}</strong></span>
      </div>

      <div class="text-[11px] text-zinc-400">
        Menu nonaktif tidak akan muncul di katalog pemesanan kasir
      </div>
    </div>
  </div>
</div>
