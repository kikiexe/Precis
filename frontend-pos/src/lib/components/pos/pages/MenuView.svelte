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

<div class="flex-1 flex flex-col h-full bg-[#f4f6f9] overflow-hidden font-sans select-none">
  <!-- Top Bar -->
  <div class="h-14 bg-white border-b border-zinc-200 px-6 flex items-center justify-between shrink-0 shadow-2xs">
    <div class="flex items-center gap-3">
      <h1 class="text-base font-bold text-zinc-900 tracking-tight">Katalog Menu Jualan</h1>
      <span class="text-xs font-mono text-zinc-400">|</span>
      <span class="text-xs text-zinc-500 font-medium">Pengaturan Produk POS</span>
    </div>

    <div class="flex items-center gap-2">
      <button
        type="button"
        onclick={() => (isCategoryModalOpen = true)}
        class="px-3 py-2 bg-white hover:bg-zinc-100 border border-zinc-200 text-zinc-800 rounded-xl text-xs font-semibold flex items-center gap-1.5 cursor-pointer shadow-2xs transition-all"
      >
        <FolderPlus class="w-3.5 h-3.5 text-zinc-600" />
        <span>Kelola Kategori</span>
      </button>

      <button
        type="button"
        onclick={handleOpenAddModal}
        class="px-3.5 py-2 bg-zinc-900 hover:bg-black text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 cursor-pointer shadow-2xs transition-all active:scale-[0.99]"
      >
        <Plus class="w-3.5 h-3.5" />
        <span>+ Tambah Menu Baru</span>
      </button>
    </div>
  </div>

  <!-- Content Body -->
  <div class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto space-y-4">
    <div class="w-full max-w-7xl mx-auto space-y-3">
      
      <!-- Excel-like Toolbar: Search & Category Filter -->
      <div class="bg-white border border-zinc-200 rounded-xl p-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shadow-2xs">
        <div class="flex items-center gap-2 flex-1">
          <div class="relative flex-1 max-w-md">
            <Search class="w-4 h-4 text-zinc-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              bind:value={searchQuery}
              placeholder="Cari menu minuman, makanan, kopi, pastry..."
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

          <!-- Category Filter -->
          <select
            bind:value={selectedCategoryFilter}
            class="h-9 px-3 bg-zinc-50 border border-zinc-200 rounded-lg text-xs font-semibold text-zinc-700 cursor-pointer focus:bg-white focus:border-zinc-900 focus:outline-hidden"
          >
            <option value="cat-all">Semua Kategori ({products.length})</option>
            {#each categories as cat}
              <option value={cat.id}>{cat.name}</option>
            {/each}
          </select>
        </div>

        <div class="text-xs font-mono text-zinc-500 self-center">
          Total Menu: <strong class="text-zinc-900">{filteredProducts.length}</strong> Produk
        </div>
      </div>

      <!-- Excel-like Spreadsheet Table -->
      <div class="bg-white border border-zinc-200 rounded-xl overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left border-collapse">
            <thead class="bg-zinc-100/90 border-b border-zinc-200 font-mono text-[11px] font-bold text-zinc-600 uppercase tracking-wider">
              <tr class="divide-x divide-zinc-200/80">
                <th class="py-3 px-3 w-12 text-center">No.</th>
                <th class="py-3 px-3 w-16 text-center">Foto</th>
                <th class="py-3 px-4">Nama Produk / Menu</th>
                <th class="py-3 px-4 w-40">Kategori</th>
                <th class="py-3 px-4 w-36 text-right font-bold">Harga Jual</th>
                <th class="py-3 px-4 w-32 text-center">Status</th>
                <th class="py-3 px-4 w-40 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200/70 font-mono">
              {#if filteredProducts.length === 0}
                <tr>
                  <td colspan="7" class="py-16 text-center text-zinc-400 font-sans">
                    <UtensilsCrossed class="w-8 h-8 mx-auto opacity-30 text-zinc-400 mb-2" />
                    <p class="text-sm font-semibold text-zinc-800">Tidak ada produk menu</p>
                    <p class="text-xs text-zinc-500 mt-0.5">Ubah kata kunci pencarian atau tambah menu jualan baru.</p>
                  </td>
                </tr>
              {:else}
                {#each filteredProducts as product, idx (product.id)}
                  <tr class={`divide-x divide-zinc-200/60 transition-colors hover:bg-zinc-50/80 ${
                    idx % 2 === 1 ? 'bg-zinc-50/30' : 'bg-white'
                  }`}>
                    <!-- No -->
                    <td class="py-3 px-3 text-center text-zinc-400 text-[11px]">
                      {idx + 1}
                    </td>

                    <!-- Foto Thumbnail (Optional Image Support) -->
                    <td class="py-2 px-3 text-center">
                      {#if product.image_url}
                        <img
                          src={product.image_url}
                          alt={product.name}
                          class="w-10 h-10 rounded-lg object-cover mx-auto border border-zinc-200 shadow-2xs"
                        />
                      {:else}
                        <div class="w-10 h-10 rounded-lg bg-zinc-100 border border-zinc-200 text-zinc-400 flex items-center justify-center mx-auto text-[10px] font-bold">
                          {product.name.substring(0, 2).toUpperCase()}
                        </div>
                      {/if}
                    </td>

                    <!-- Nama & Deskripsi -->
                    <td class="py-3 px-4 font-sans font-semibold text-zinc-900">
                      <div>{product.name}</div>
                      {#if product.description}
                        <div class="text-[11px] text-zinc-500 font-normal mt-0.5 line-clamp-1">
                          {product.description}
                        </div>
                      {/if}
                    </td>

                    <!-- Kategori -->
                    <td class="py-3 px-4 font-sans">
                      <span class="inline-block px-2.5 py-0.5 bg-zinc-100 text-zinc-700 rounded-md text-[11px] font-medium border border-zinc-200/60">
                        {getCategoryName(product.category_id)}
                      </span>
                    </td>

                    <!-- Harga Jual -->
                    <td class="py-3 px-4 text-right font-bold text-zinc-900 text-xs">
                      {formatCurrency(product.base_price)}
                    </td>

                    <!-- Status Penjualan -->
                    <td class="py-3 px-4 text-center font-sans">
                      <span class={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold ${
                        product.is_active
                          ? 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                          : 'bg-zinc-200 text-zinc-600'
                      }`}>
                        {product.is_active ? 'Dijual' : 'Nonaktif'}
                      </span>
                    </td>

                    <!-- Aksi -->
                    <td class="py-3 px-4 text-center font-sans">
                      <div class="flex items-center justify-center gap-1.5">
                        <button
                          type="button"
                          onclick={() => onToggleProductActive(product.id)}
                          class={`px-2 py-1 rounded text-[10px] font-semibold border transition-all cursor-pointer ${
                            product.is_active
                              ? 'bg-zinc-100 text-zinc-700 border-zinc-300 hover:bg-zinc-200'
                              : 'bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-700'
                          }`}
                        >
                          {product.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                        </button>
                        <button
                          type="button"
                          onclick={() => handleOpenEditModal(product)}
                          class="p-1 text-zinc-500 hover:text-zinc-900 hover:bg-zinc-100 rounded cursor-pointer"
                          title="Edit Menu"
                        >
                          <Edit2 class="w-3.5 h-3.5" />
                        </button>
                        <button
                          type="button"
                          onclick={() => handleDeleteProduct(product.id)}
                          class="p-1 text-zinc-400 hover:text-red-600 hover:bg-red-50 rounded cursor-pointer"
                          title="Hapus Menu"
                        >
                          <Trash2 class="w-3.5 h-3.5" />
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
        <div class="bg-zinc-50 border-t border-zinc-200 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs font-mono text-zinc-600">
          <div>
            Katalog aktif disinkronkan langsung ke layar kasir Point of Sale
          </div>
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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans select-none">
    <div class="bg-white border border-zinc-200 rounded-2xl w-full max-w-lg p-6 space-y-4 shadow-2xl animate-in zoom-in-95 duration-150 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-zinc-100 text-zinc-900 flex items-center justify-center border border-zinc-200">
            <UtensilsCrossed class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-zinc-900">
              {editingProductId ? 'Ubah Menu Jualan' : 'Tambah Menu Jualan Baru'}
            </h3>
            <p class="text-[11px] text-zinc-500">Konfigurasi nama, kategori, harga, dan foto produk</p>
          </div>
        </div>
        <button type="button" onclick={() => (isAddMenuModalOpen = false)} class="text-zinc-400 hover:text-zinc-700 cursor-pointer p-1">
          <X class="w-4 h-4" />
        </button>
      </div>

      {#if formErrorMessage}
        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs flex items-center gap-2">
          <AlertCircle class="w-4 h-4 shrink-0" />
          <span>{formErrorMessage}</span>
        </div>
      {/if}

      <form onsubmit={handleSaveProduct} class="space-y-3.5 text-xs">
        <div class="space-y-1">
          <label for="menu-name" class="font-semibold text-zinc-900 block">
            Nama Produk / Menu <span class="text-red-500">*</span>
          </label>
          <input
            id="menu-name"
            type="text"
            bind:value={formName}
            placeholder="Contoh: Iced Caramel Macchiato"
            required
            class="w-full px-3.5 py-2 bg-zinc-50 border border-zinc-200 rounded-xl text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden text-xs"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label for="menu-category" class="font-semibold text-zinc-900 block">
              Kategori Menu <span class="text-red-500">*</span>
            </label>
            <select
              id="menu-category"
              bind:value={formCategoryId}
              class="w-full px-3 py-2 bg-zinc-50 border border-zinc-200 rounded-xl text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden text-xs font-medium cursor-pointer"
            >
              {#each categories as cat}
                <option value={cat.id}>{cat.name}</option>
              {/each}
            </select>
          </div>

          <div class="space-y-1">
            <label for="menu-price" class="font-semibold text-zinc-900 block">
              Harga Jual (Rp) <span class="text-red-500">*</span>
            </label>
            <input
              id="menu-price"
              type="number"
              bind:value={formBasePrice}
              min="0"
              step="500"
              required
              class="w-full px-3.5 py-2 bg-zinc-50 border border-zinc-200 rounded-xl text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden text-xs font-mono font-bold"
            />
          </div>
        </div>

        <div class="space-y-1">
          <label for="menu-desc" class="font-semibold text-zinc-900 block">
            Deskripsi Menu (Opsional)
          </label>
          <textarea
            id="menu-desc"
            bind:value={formDescription}
            rows="2"
            placeholder="Keterangan singkat komposisi atau varian rasa..."
            class="w-full px-3.5 py-2 bg-zinc-50 border border-zinc-200 rounded-xl text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden text-xs resize-none"
          ></textarea>
        </div>

        <!-- Foto Produk (Opsional) -->
        <div class="space-y-2 p-3 bg-zinc-50 rounded-xl border border-zinc-200">
          <div class="flex items-center justify-between">
            <label class="font-semibold text-zinc-900 flex items-center gap-1.5">
              <ImageIcon class="w-3.5 h-3.5 text-zinc-600" />
              <span>Foto Produk Menu (Opsional)</span>
            </label>
            {#if formImageUrl}
              <button
                type="button"
                onclick={() => (formImageUrl = '')}
                class="text-[10px] text-red-600 hover:underline cursor-pointer"
              >
                Hapus Foto
              </button>
            {/if}
          </div>

          <div class="flex items-center gap-3">
            <!-- Preview Box -->
            <div class="w-16 h-16 rounded-xl border border-zinc-200 bg-white overflow-hidden flex items-center justify-center shrink-0">
              {#if formImageUrl}
                <img src={formImageUrl} alt="Preview" class="w-full h-full object-cover" />
              {:else}
                <ImageIcon class="w-6 h-6 text-zinc-300" />
              {/if}
            </div>

            <div class="flex-1 space-y-1.5">
              <label class="px-3 py-1.5 bg-white hover:bg-zinc-100 border border-zinc-200 text-zinc-800 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5 cursor-pointer shadow-2xs transition-all">
                <Upload class="w-3.5 h-3.5 text-zinc-600" />
                <span>Pilih File Gambar</span>
                <input
                  type="file"
                  accept="image/*"
                  onchange={handleImageFileUpload}
                  class="hidden"
                />
              </label>

              <div class="text-[10px] text-zinc-400">
                Atau masukkan tautan URL gambar online:
              </div>
              <input
                type="url"
                bind:value={formImageUrl}
                placeholder="https://example.com/foto-kopi.jpg"
                class="w-full px-2.5 py-1 bg-white border border-zinc-200 rounded-lg text-[11px] font-mono text-zinc-900 focus:outline-hidden focus:border-zinc-900"
              />
            </div>
          </div>
        </div>

        <div class="pt-2 flex gap-2.5">
          <button
            type="button"
            onclick={() => (isAddMenuModalOpen = false)}
            class="flex-1 py-2.5 text-xs font-semibold border border-zinc-200 rounded-xl text-zinc-700 hover:bg-zinc-100 cursor-pointer transition-colors"
          >
            Batal
          </button>
          <button
            type="submit"
            class="flex-1 py-2.5 text-xs font-semibold bg-zinc-900 hover:bg-black text-white rounded-xl flex items-center justify-center gap-1.5 cursor-pointer shadow-xs transition-all active:scale-[0.99]"
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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans select-none">
    <div class="bg-white border border-zinc-200 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-2xl animate-in zoom-in-95 duration-150">
      <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-zinc-100 text-zinc-900 flex items-center justify-center border border-zinc-200">
            <FolderPlus class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-zinc-900">Kelola Kategori Menu</h3>
            <p class="text-[11px] text-zinc-500">Kategori produk yang tampil pada kasir</p>
          </div>
        </div>
        <button type="button" onclick={() => (isCategoryModalOpen = false)} class="text-zinc-400 hover:text-zinc-700 cursor-pointer p-1">
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Add Category Input -->
      <div class="flex items-center gap-2">
        <input
          type="text"
          bind:value={newCategoryName}
          placeholder="Nama kategori menu baru..."
          class="flex-1 px-3.5 py-2 bg-zinc-50 border border-zinc-200 rounded-xl text-xs text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden"
        />
        <button
          type="button"
          onclick={handleAddCategory}
          class="px-3.5 py-2 bg-zinc-900 hover:bg-black text-white text-xs font-semibold rounded-xl cursor-pointer shadow-2xs transition-all active:scale-95 shrink-0"
        >
          + Tambah
        </button>
      </div>

      <!-- Categories List -->
      <div class="max-h-60 overflow-y-auto divide-y divide-zinc-100 border border-zinc-200 rounded-xl">
        {#each categories as cat (cat.id)}
          <div class="p-3 flex items-center justify-between gap-2 hover:bg-zinc-50">
            {#if editingCategoryId === cat.id}
              <input
                type="text"
                bind:value={editingCategoryName}
                class="flex-1 px-2 py-1 bg-white border border-zinc-300 rounded text-xs text-zinc-900 focus:outline-hidden"
              />
              <div class="flex items-center gap-1">
                <button
                  type="button"
                  onclick={() => handleSaveEditCategory(cat.id)}
                  class="p-1 text-emerald-600 hover:bg-emerald-50 rounded"
                >
                  <Check class="w-4 h-4" />
                </button>
                <button
                  type="button"
                  onclick={() => (editingCategoryId = null)}
                  class="p-1 text-zinc-400 hover:bg-zinc-100 rounded"
                >
                  <X class="w-4 h-4" />
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
                  class="p-1 text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 rounded cursor-pointer"
                >
                  <Edit2 class="w-3.5 h-3.5" />
                </button>
                <button
                  type="button"
                  onclick={() => handleDeleteCategory(cat.id)}
                  class="p-1 text-zinc-400 hover:text-red-600 hover:bg-red-50 rounded cursor-pointer"
                >
                  <Trash2 class="w-3.5 h-3.5" />
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
          class="w-full py-2.5 text-xs font-semibold bg-zinc-100 hover:bg-zinc-200 text-zinc-800 rounded-xl cursor-pointer transition-colors"
        >
          Selesai
        </button>
      </div>
    </div>
  </div>
{/if}
