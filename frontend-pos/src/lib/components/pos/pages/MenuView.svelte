<script lang="ts">
  import {
    Search,
    Plus,
    FolderPlus,
    Image as ImageIcon,
    Upload,
    X,
    Check,
    AlertCircle,
    Edit2,
    Trash2,
    UtensilsCrossed,
  } from 'lucide-svelte';
  import type { Product, Category } from '../../../types/pos';
  import { formatCurrency } from '../../../services/printer-service';

  interface Props {
    products: Product[];
    categories: Category[];
    onToggleProductActive: (productId: string) => void;
    onAddNewProduct: (product: Product) => void;
    onUpdateProduct?: (product: Product) => void;
    onDeleteProduct?: (productId: string) => void;
    onUpdateCategories?: (categories: Category[]) => void;
  }

  let {
    products = [],
    categories = [],
    onToggleProductActive,
    onAddNewProduct,
    onUpdateProduct,
    onDeleteProduct,
    onUpdateCategories,
  }: Props = $props();

  let searchQuery = $state('');
  let selectedCategoryFilter = $state('cat-all');

  // Modal States
  let isAddMenuModalOpen = $state(false);
  let isCategoryModalOpen = $state(false);
  let editingProductId = $state<string | null>(null);

  // Form State: Add/Edit Product
  let formName = $state('');
  let formCategoryId = $state('cat-coffee');
  let formBasePrice = $state(0);
  let formDescription = $state('');
  let formImageUrl = $state('');
  let formIsActive = $state(true);
  let formErrorMessage = $state('');

  // Form State: Category Management
  let newCategoryName = $state('');
  let editingCategoryId = $state<string | null>(null);
  let editingCategoryName = $state('');

  // Filtered products
  let filteredProducts = $derived(
    products.filter((p) => {
      const matchSearch =
        searchQuery.trim() === '' ||
        p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (p.description && p.description.toLowerCase().includes(searchQuery.toLowerCase()));

      const matchCategory =
        selectedCategoryFilter === 'cat-all' || p.category_id === selectedCategoryFilter;

      return matchSearch && matchCategory;
    })
  );

  function getCategoryName(catId: string): string {
    const cat = categories.find((c) => c.id === catId);
    return cat ? cat.name : catId.replace('cat-', '');
  }

  function handleOpenAddModal() {
    editingProductId = null;
    formName = '';
    formCategoryId = categories[0]?.id || 'cat-coffee';
    formBasePrice = 0;
    formDescription = '';
    formImageUrl = '';
    formIsActive = true;
    formErrorMessage = '';
    isAddMenuModalOpen = true;
  }

  function handleOpenEditModal(p: Product) {
    editingProductId = p.id;
    formName = p.name;
    formCategoryId = p.category_id;
    formBasePrice = p.base_price;
    formDescription = p.description || '';
    formImageUrl = p.image_url || '';
    formIsActive = p.is_active;
    formErrorMessage = '';
    isAddMenuModalOpen = true;
  }

  // Handle Photo File Upload
  function handleImageFileUpload(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
      const file = target.files[0];
      const reader = new FileReader();
      reader.onload = (uploadEvent) => {
        if (uploadEvent.target?.result) {
          formImageUrl = uploadEvent.target.result as string;
        }
      };
      reader.readAsDataURL(file);
    }
  }

  function handleSaveProduct(e: SubmitEvent) {
    e.preventDefault();
    formErrorMessage = '';

    if (!formName.trim()) {
      formErrorMessage = 'Nama produk / menu wajib diisi.';
      return;
    }
    if (formBasePrice === null || formBasePrice === undefined || formBasePrice < 0) {
      formErrorMessage = 'Harga jual wajib diisi dan tidak boleh negatif.';
      return;
    }

    if (editingProductId) {
      const updated: Product = {
        id: editingProductId,
        name: formName.trim(),
        category_id: formCategoryId,
        base_price: Number(formBasePrice),
        description: formDescription.trim() || undefined,
        image_url: formImageUrl.trim() || undefined,
        is_active: formIsActive,
      };
      onUpdateProduct?.(updated);
    } else {
      const newProd: Product = {
        id: `prod-${Date.now()}`,
        name: formName.trim(),
        category_id: formCategoryId,
        base_price: Number(formBasePrice),
        description: formDescription.trim() || undefined,
        image_url: formImageUrl.trim() || undefined,
        is_active: true,
      };
      onAddNewProduct(newProd);
    }

    isAddMenuModalOpen = false;
  }

  function handleDeleteProduct(productId: string) {
    if (confirm('Hapus menu jualan ini dari POS?')) {
      onDeleteProduct?.(productId);
    }
  }

  // Category Management
  function handleAddCategory() {
    if (!newCategoryName.trim()) return;
    const newCat: Category = {
      id: `cat-${Date.now()}`,
      name: newCategoryName.trim(),
    };
    const updated = [...categories, newCat];
    onUpdateCategories?.(updated);
    newCategoryName = '';
  }

  function handleSaveEditCategory(id: string) {
    if (!editingCategoryName.trim()) return;
    const updated = categories.map((c) => {
      if (c.id === id) {
        return { ...c, name: editingCategoryName.trim() };
      }
      return c;
    });
    onUpdateCategories?.(updated);
    editingCategoryId = null;
    editingCategoryName = '';
  }

  function handleDeleteCategory(id: string) {
    if (confirm('Hapus kategori menu ini?')) {
      const updated = categories.filter((c) => c.id !== id);
      onUpdateCategories?.(updated);
      if (selectedCategoryFilter === id) selectedCategoryFilter = 'cat-all';
    }
  }
</script>

<div class="flex h-full flex-1 flex-col overflow-hidden bg-[#f4f6f9] font-sans select-none">
  <!-- Top Bar -->
  <div
    class="flex h-14 shrink-0 items-center justify-between border-b border-zinc-200 bg-white px-6 shadow-2xs"
  >
    <div class="flex items-center gap-3">
      <h1 class="text-base font-bold tracking-tight text-zinc-900">Katalog Menu Jualan</h1>
      <span class="font-mono text-xs text-zinc-400">|</span>
      <span class="text-xs font-medium text-zinc-500">Pengaturan Produk POS</span>
    </div>

    <div class="flex items-center gap-2">
      <button
        type="button"
        onclick={() => (isCategoryModalOpen = true)}
        class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-800 shadow-2xs transition-all hover:bg-zinc-100"
      >
        <FolderPlus class="h-3.5 w-3.5 text-zinc-600" />
        <span>Kelola Kategori</span>
      </button>

      <button
        type="button"
        onclick={handleOpenAddModal}
        class="flex cursor-pointer items-center gap-1.5 rounded-xl bg-zinc-900 px-3.5 py-2 text-xs font-semibold text-white shadow-2xs transition-all hover:bg-black active:scale-[0.99]"
      >
        <Plus class="h-3.5 w-3.5" />
        <span>+ Tambah Menu Baru</span>
      </button>
    </div>
  </div>

  <!-- Content Body -->
  <div class="flex-1 space-y-4 overflow-y-auto p-4 sm:p-6 lg:p-8">
    <div class="mx-auto w-full max-w-7xl space-y-3">
      <!-- Excel-like Toolbar: Search & Category Filter -->
      <div
        class="flex flex-col items-stretch justify-between gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-2xs sm:flex-row sm:items-center"
      >
        <div class="flex flex-1 items-center gap-2">
          <div class="relative max-w-md flex-1">
            <Search class="absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-zinc-400" />
            <input
              type="text"
              bind:value={searchQuery}
              placeholder="Cari menu minuman, makanan, kopi, pastry..."
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

          <!-- Category Filter -->
          <select
            bind:value={selectedCategoryFilter}
            class="h-9 cursor-pointer rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-xs font-semibold text-zinc-700 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
          >
            <option value="cat-all">Semua Kategori ({products.length})</option>
            {#each categories as cat}
              <option value={cat.id}>{cat.name}</option>
            {/each}
          </select>
        </div>

        <div class="self-center font-mono text-xs text-zinc-500">
          Total Menu: <strong class="text-zinc-900">{filteredProducts.length}</strong> Produk
        </div>
      </div>

      <!-- Excel-like Spreadsheet Table -->
      <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xs">
        <div class="overflow-x-auto">
          <table class="w-full border-collapse text-left text-xs">
            <thead
              class="border-b border-zinc-200 bg-zinc-100/90 font-mono text-[11px] font-bold tracking-wider text-zinc-600 uppercase"
            >
              <tr class="divide-x divide-zinc-200/80">
                <th class="w-12 px-3 py-3 text-center">No.</th>
                <th class="w-16 px-3 py-3 text-center">Foto</th>
                <th class="px-4 py-3">Nama Produk / Menu</th>
                <th class="w-40 px-4 py-3">Kategori</th>
                <th class="w-36 px-4 py-3 text-right font-bold">Harga Jual</th>
                <th class="w-32 px-4 py-3 text-center">Status</th>
                <th class="w-40 px-4 py-3 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200/70 font-mono">
              {#if filteredProducts.length === 0}
                <tr>
                  <td colspan="7" class="py-16 text-center font-sans text-zinc-400">
                    <UtensilsCrossed class="mx-auto mb-2 h-8 w-8 text-zinc-400 opacity-30" />
                    <p class="text-sm font-semibold text-zinc-800">Tidak ada produk menu</p>
                    <p class="mt-0.5 text-xs text-zinc-500">
                      Ubah kata kunci pencarian atau tambah menu jualan baru.
                    </p>
                  </td>
                </tr>
              {:else}
                {#each filteredProducts as product, idx (product.id)}
                  <tr
                    class={`divide-x divide-zinc-200/60 transition-colors hover:bg-zinc-50/80 ${
                      idx % 2 === 1 ? 'bg-zinc-50/30' : 'bg-white'
                    }`}
                  >
                    <!-- No -->
                    <td class="px-3 py-3 text-center text-[11px] text-zinc-400">
                      {idx + 1}
                    </td>

                    <!-- Foto Thumbnail (Optional Image Support) -->
                    <td class="px-3 py-2 text-center">
                      {#if product.image_url}
                        <img
                          src={product.image_url}
                          alt={product.name}
                          class="mx-auto h-10 w-10 rounded-lg border border-zinc-200 object-cover shadow-2xs"
                        />
                      {:else}
                        <div
                          class="mx-auto flex h-10 w-10 items-center justify-center rounded-lg border border-zinc-200 bg-zinc-100 text-[10px] font-bold text-zinc-400"
                        >
                          {product.name.substring(0, 2).toUpperCase()}
                        </div>
                      {/if}
                    </td>

                    <!-- Nama & Deskripsi -->
                    <td class="px-4 py-3 font-sans font-semibold text-zinc-900">
                      <div>{product.name}</div>
                      {#if product.description}
                        <div class="mt-0.5 line-clamp-1 text-[11px] font-normal text-zinc-500">
                          {product.description}
                        </div>
                      {/if}
                    </td>

                    <!-- Kategori -->
                    <td class="px-4 py-3 font-sans">
                      <span
                        class="inline-block rounded-md border border-zinc-200/60 bg-zinc-100 px-2.5 py-0.5 text-[11px] font-medium text-zinc-700"
                      >
                        {getCategoryName(product.category_id)}
                      </span>
                    </td>

                    <!-- Harga Jual -->
                    <td class="px-4 py-3 text-right text-xs font-bold text-zinc-900">
                      {formatCurrency(product.base_price)}
                    </td>

                    <!-- Status Penjualan -->
                    <td class="px-4 py-3 text-center font-sans">
                      <span
                        class={`inline-block rounded-full px-2.5 py-0.5 text-[10px] font-bold ${
                          product.is_active
                            ? 'border border-emerald-200 bg-emerald-100 text-emerald-800'
                            : 'bg-zinc-200 text-zinc-600'
                        }`}
                      >
                        {product.is_active ? 'Dijual' : 'Nonaktif'}
                      </span>
                    </td>

                    <!-- Aksi -->
                    <td class="px-4 py-3 text-center font-sans">
                      <div class="flex items-center justify-center gap-1.5">
                        <button
                          type="button"
                          onclick={() => onToggleProductActive(product.id)}
                          class={`cursor-pointer rounded border px-2 py-1 text-[10px] font-semibold transition-all ${
                            product.is_active
                              ? 'border-zinc-300 bg-zinc-100 text-zinc-700 hover:bg-zinc-200'
                              : 'border-emerald-600 bg-emerald-600 text-white hover:bg-emerald-700'
                          }`}
                        >
                          {product.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                        </button>
                        <button
                          type="button"
                          onclick={() => handleOpenEditModal(product)}
                          class="cursor-pointer rounded p-1 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900"
                          title="Edit Menu"
                        >
                          <Edit2 class="h-3.5 w-3.5" />
                        </button>
                        <button
                          type="button"
                          onclick={() => handleDeleteProduct(product.id)}
                          class="cursor-pointer rounded p-1 text-zinc-400 hover:bg-red-50 hover:text-red-600"
                          title="Hapus Menu"
                        >
                          <Trash2 class="h-3.5 w-3.5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                {/each}
              {/if}
            </tbody>
          </table>
        </div>

        <!-- Summary Footer -->
        <div
          class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50 px-4 py-2.5 font-mono text-xs text-zinc-600"
        >
          <div>Katalog aktif disinkronkan langsung ke layar kasir Point of Sale</div>
          <div>
            Total Terdaftar: <strong class="text-zinc-900">{filteredProducts.length}</strong> Menu
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Tambah / Edit Menu Jualan (Termasuk Foto Opsional) -->
{#if isAddMenuModalOpen}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 font-sans backdrop-blur-xs select-none"
  >
    <div
      class="animate-in zoom-in-95 max-h-[90vh] w-full max-w-lg space-y-4 overflow-y-auto rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl duration-150"
    >
      <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div
            class="flex h-9 w-9 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-100 text-zinc-900"
          >
            <UtensilsCrossed class="h-4 w-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-zinc-900">
              {editingProductId ? 'Ubah Menu Jualan' : 'Tambah Menu Jualan Baru'}
            </h3>
            <p class="text-[11px] text-zinc-500">
              Konfigurasi nama, kategori, harga, dan foto produk
            </p>
          </div>
        </div>
        <button
          type="button"
          onclick={() => (isAddMenuModalOpen = false)}
          class="cursor-pointer p-1 text-zinc-400 hover:text-zinc-700"
        >
          <X class="h-4 w-4" />
        </button>
      </div>

      {#if formErrorMessage}
        <div
          class="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-700"
        >
          <AlertCircle class="h-4 w-4 shrink-0" />
          <span>{formErrorMessage}</span>
        </div>
      {/if}

      <form onsubmit={handleSaveProduct} class="space-y-3.5 text-xs">
        <div class="space-y-1">
          <label for="menu-name" class="block font-semibold text-zinc-900">
            Nama Produk / Menu <span class="text-red-500">*</span>
          </label>
          <input
            id="menu-name"
            type="text"
            bind:value={formName}
            placeholder="Contoh: Iced Caramel Macchiato"
            required
            class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 text-xs text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label for="menu-category" class="block font-semibold text-zinc-900">
              Kategori Menu <span class="text-red-500">*</span>
            </label>
            <select
              id="menu-category"
              bind:value={formCategoryId}
              class="w-full cursor-pointer rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs font-medium text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
            >
              {#each categories as cat}
                <option value={cat.id}>{cat.name}</option>
              {/each}
            </select>
          </div>

          <div class="space-y-1">
            <label for="menu-price" class="block font-semibold text-zinc-900">
              Harga Jual (Rp) <span class="text-red-500">*</span>
            </label>
            <input
              id="menu-price"
              type="number"
              bind:value={formBasePrice}
              min="0"
              step="500"
              required
              class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 font-mono text-xs font-bold text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
            />
          </div>
        </div>

        <div class="space-y-1">
          <label for="menu-desc" class="block font-semibold text-zinc-900">
            Deskripsi Menu (Opsional)
          </label>
          <textarea
            id="menu-desc"
            bind:value={formDescription}
            rows="2"
            placeholder="Keterangan singkat komposisi atau varian rasa..."
            class="w-full resize-none rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 text-xs text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
          ></textarea>
        </div>

        <!-- Foto Produk (Opsional) -->
        <div class="space-y-2 rounded-xl border border-zinc-200 bg-zinc-50 p-3">
          <div class="flex items-center justify-between">
            <label class="flex items-center gap-1.5 font-semibold text-zinc-900">
              <ImageIcon class="h-3.5 w-3.5 text-zinc-600" />
              <span>Foto Produk Menu (Opsional)</span>
            </label>
            {#if formImageUrl}
              <button
                type="button"
                onclick={() => (formImageUrl = '')}
                class="cursor-pointer text-[10px] text-red-600 hover:underline"
              >
                Hapus Foto
              </button>
            {/if}
          </div>

          <div class="flex items-center gap-3">
            <!-- Preview Box -->
            <div
              class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-zinc-200 bg-white"
            >
              {#if formImageUrl}
                <img src={formImageUrl} alt="Preview" class="h-full w-full object-cover" />
              {:else}
                <ImageIcon class="h-6 w-6 text-zinc-300" />
              {/if}
            </div>

            <div class="flex-1 space-y-1.5">
              <label
                class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-800 shadow-2xs transition-all hover:bg-zinc-100"
              >
                <Upload class="h-3.5 w-3.5 text-zinc-600" />
                <span>Pilih File Gambar</span>
                <input
                  type="file"
                  accept="image/*"
                  onchange={handleImageFileUpload}
                  class="hidden"
                />
              </label>

              <div class="text-[10px] text-zinc-400">Atau masukkan tautan URL gambar online:</div>
              <input
                type="url"
                bind:value={formImageUrl}
                placeholder="https://example.com/foto-kopi.jpg"
                class="w-full rounded-lg border border-zinc-200 bg-white px-2.5 py-1 font-mono text-[11px] text-zinc-900 focus:border-zinc-900 focus:outline-hidden"
              />
            </div>
          </div>
        </div>

        <div class="flex gap-2.5 pt-2">
          <button
            type="button"
            onclick={() => (isAddMenuModalOpen = false)}
            class="flex-1 cursor-pointer rounded-xl border border-zinc-200 py-2.5 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-100"
          >
            Batal
          </button>
          <button
            type="submit"
            class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-zinc-900 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black active:scale-[0.99]"
          >
            <span>{editingProductId ? 'Simpan Perubahan' : 'Tambah Menu'}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<!-- Modal: Kelola Kategori Menu -->
{#if isCategoryModalOpen}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 font-sans backdrop-blur-xs select-none"
  >
    <div
      class="animate-in zoom-in-95 w-full max-w-md space-y-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl duration-150"
    >
      <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div
            class="flex h-9 w-9 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-100 text-zinc-900"
          >
            <FolderPlus class="h-4 w-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-zinc-900">Kelola Kategori Menu</h3>
            <p class="text-[11px] text-zinc-500">Kategori produk yang tampil pada kasir</p>
          </div>
        </div>
        <button
          type="button"
          onclick={() => (isCategoryModalOpen = false)}
          class="cursor-pointer p-1 text-zinc-400 hover:text-zinc-700"
        >
          <X class="h-4 w-4" />
        </button>
      </div>

      <!-- Add Category Input -->
      <div class="flex items-center gap-2">
        <input
          type="text"
          bind:value={newCategoryName}
          placeholder="Nama kategori menu baru..."
          class="flex-1 rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 text-xs text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
        />
        <button
          type="button"
          onclick={handleAddCategory}
          class="shrink-0 cursor-pointer rounded-xl bg-zinc-900 px-3.5 py-2 text-xs font-semibold text-white shadow-2xs transition-all hover:bg-black active:scale-95"
        >
          + Tambah
        </button>
      </div>

      <!-- Categories List -->
      <div
        class="max-h-60 divide-y divide-zinc-100 overflow-y-auto rounded-xl border border-zinc-200"
      >
        {#each categories as cat (cat.id)}
          <div class="flex items-center justify-between gap-2 p-3 hover:bg-zinc-50">
            {#if editingCategoryId === cat.id}
              <input
                type="text"
                bind:value={editingCategoryName}
                class="flex-1 rounded border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 focus:outline-hidden"
              />
              <div class="flex items-center gap-1">
                <button
                  type="button"
                  onclick={() => handleSaveEditCategory(cat.id)}
                  class="rounded p-1 text-emerald-600 hover:bg-emerald-50"
                >
                  <Check class="h-4 w-4" />
                </button>
                <button
                  type="button"
                  onclick={() => (editingCategoryId = null)}
                  class="rounded p-1 text-zinc-400 hover:bg-zinc-100"
                >
                  <X class="h-4 w-4" />
                </button>
              </div>
            {:else}
              <span class="text-xs font-medium text-zinc-900">{cat.name}</span>
              <div class="flex items-center gap-1">
                <button
                  type="button"
                  onclick={() => {
                    editingCategoryId = cat.id;
                    editingCategoryName = cat.name;
                  }}
                  class="cursor-pointer rounded p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700"
                >
                  <Edit2 class="h-3.5 w-3.5" />
                </button>
                <button
                  type="button"
                  onclick={() => handleDeleteCategory(cat.id)}
                  class="cursor-pointer rounded p-1 text-zinc-400 hover:bg-red-50 hover:text-red-600"
                >
                  <Trash2 class="h-3.5 w-3.5" />
                </button>
              </div>
            {/if}
          </div>
        {/each}
      </div>

      <div class="pt-2">
        <button
          type="button"
          onclick={() => (isCategoryModalOpen = false)}
          class="w-full cursor-pointer rounded-xl bg-zinc-100 py-2.5 text-xs font-semibold text-zinc-800 transition-colors hover:bg-zinc-200"
        >
          Selesai
        </button>
      </div>
    </div>
  </div>
{/if}
