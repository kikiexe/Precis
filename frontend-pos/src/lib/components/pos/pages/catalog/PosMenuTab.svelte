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

  let { products = [], categories = [], onToggleProductActive, onOpenAddModal }: Props = $props();

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
  <div
    class="flex flex-col items-stretch justify-between gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-2xs sm:flex-row sm:items-center"
  >
    <div class="flex flex-1 items-center gap-2">
      <div class="relative max-w-md flex-1">
        <Search class="absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-zinc-400" />
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Cari nama produk, minuman, atau makanan..."
          class="w-full rounded-lg border border-zinc-200 bg-zinc-50 py-2 pr-4 pl-9 text-xs text-zinc-900 placeholder-zinc-400 transition-all focus:border-zinc-900 focus:bg-white focus:outline-hidden"
        />
        {#if searchQuery}
          <button
            type="button"
            onclick={() => (searchQuery = '')}
            class="absolute top-1/2 right-3 -translate-y-1/2 cursor-pointer font-mono text-xs text-zinc-400 hover:text-zinc-700"
          >
            ✕
          </button>
        {/if}
      </div>

      <!-- Category Filter Dropdown -->
      <select
        bind:value={selectedCategory}
        class="h-9 cursor-pointer rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-xs font-medium text-zinc-700 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
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
      class="active:scale-0.99 flex shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-lg bg-zinc-900 px-4 py-2 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
    >
      <Plus class="size-3.5" />
      <span>+ Tambah Menu Baru</span>
    </button>
  </div>

  <!-- Excel-like Spreadsheet Table -->
  <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xs">
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-left text-xs">
        <thead
          class="border-b border-zinc-200 bg-zinc-100/80 font-mono text-[11px] font-bold tracking-wider text-zinc-600 uppercase"
        >
          <tr class="divide-x divide-zinc-200/80">
            <th class="w-12 p-3 text-center">No.</th>
            <th class="px-4 py-3">Nama Produk / Menu</th>
            <th class="w-40 px-4 py-3">Kategori</th>
            <th class="w-36 px-4 py-3 text-right">Harga Jual</th>
            <th class="w-32 px-4 py-3 text-center">Status</th>
            <th class="w-36 px-4 py-3 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200/70">
          {#if filteredProducts.length === 0}
            <tr>
              <td colspan="6" class="py-16 text-center text-zinc-400">
                <p class="text-sm font-semibold text-zinc-800">Tidak ada data produk</p>
                <p class="mt-0.5 text-xs text-zinc-500">
                  Coba ubah kata kunci pencarian atau kategori.
                </p>
              </td>
            </tr>
          {:else}
            {#each filteredProducts as product, idx (product.id)}
              {@const catName =
                categories.find((c) => c.id === product.category_id)?.name ||
                product.category_id.replace('cat-', '')}
              <tr
                class={`divide-x divide-zinc-200/60 transition-colors hover:bg-zinc-50/80 ${
                  idx % 2 === 1 ? 'bg-zinc-50/30' : 'bg-white'
                }`}
              >
                <!-- No -->
                <td class="p-3 text-center font-mono text-[11px] text-zinc-400">
                  {idx + 1}
                </td>

                <!-- Nama Produk & Deskripsi -->
                <td class="px-4 py-3 font-semibold text-zinc-900">
                  <div>{product.name}</div>
                  {#if product.description}
                    <div class="mt-0.5 line-clamp-1 text-[11px] font-normal text-zinc-500">
                      {product.description}
                    </div>
                  {/if}
                </td>

                <!-- Kategori -->
                <td class="px-4 py-3">
                  <span
                    class="inline-block max-w-35 truncate rounded-md border border-zinc-200/60 bg-zinc-100 px-2.5 py-0.5 text-[11px] font-medium text-zinc-700"
                  >
                    {catName}
                  </span>
                </td>

                <!-- Harga Jual -->
                <td class="px-4 py-3 text-right font-mono text-xs font-bold text-zinc-900">
                  {formatRupiah(product.base_price)}
                </td>

                <!-- Status Penjualan -->
                <td class="px-4 py-3 text-center">
                  <span
                    class={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold ${
                      product.is_active
                        ? 'bg-emerald-100 text-emerald-800'
                        : 'bg-zinc-200 text-zinc-600'
                    }`}
                  >
                    {#if product.is_active}
                      <CheckCircle2 class="size-3 text-emerald-600" />
                      <span>Aktif</span>
                    {:else}
                      <XCircle class="size-3 text-zinc-500" />
                      <span>Nonaktif</span>
                    {/if}
                  </span>
                </td>

                <!-- Aksi -->
                <td class="px-4 py-3 text-right">
                  <button
                    type="button"
                    onclick={() => onToggleProductActive(product.id)}
                    class={`cursor-pointer rounded-lg px-3 py-1.5 text-[11px] font-semibold transition-colors ${
                      product.is_active
                        ? 'border border-red-200 bg-red-50 text-red-700 hover:bg-red-100'
                        : 'border border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100'
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
    <div
      class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50 px-4 py-2.5 font-mono text-xs text-zinc-600"
    >
      <div class="flex items-center gap-4">
        <span>Total Menu: <strong class="text-zinc-900">{filteredProducts.length}</strong></span>
        <span>Menu Aktif: <strong class="text-emerald-700">{activeCount}</strong></span>
        <span
          >Menu Nonaktif: <strong class="text-zinc-500">{products.length - activeCount}</strong
          ></span
        >
      </div>

      <div class="text-[11px] text-zinc-400">
        Menu nonaktif tidak akan muncul di katalog pemesanan kasir
      </div>
    </div>
  </div>
</div>
