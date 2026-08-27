<script lang="ts">
  import { X, ChevronDown, Package, Plus } from 'lucide-svelte';
  import type { CategoryItem } from '../../../../types/app';
  import { DEFAULT_MENU_PRICE_IDR } from '../../../../constants/defaults';

  interface Props {
    isOpen: boolean;
    categories: CategoryItem[];
    onClose: () => void;
    onSave: (menu: {
      name: string;
      category_id: string;
      price: number;
      description: string;
      is_available: boolean;
    }) => Promise<void>;
  }

  let { isOpen, categories = [], onClose, onSave }: Props = $props();

  let name = $state('');
  let category_id = $state('');
  let price = $state(DEFAULT_MENU_PRICE_IDR);
  let description = $state('');
  let is_available = $state(true);
  let isSubmitting = $state(false);

  $effect(() => {
    if (isOpen) {
      name = '';
      category_id = categories.find((c) => c.type === 'MENU')?.id || categories[0]?.id || '';
      price = DEFAULT_MENU_PRICE_IDR;
      description = '';
      is_available = true;
    }
  });

  async function handleSubmit() {
    if (!name.trim() || price <= 0) return;
    isSubmitting = true;
    try {
      await onSave({
        name: name.trim(),
        category_id: category_id || (categories[0]?.id ?? ''),
        price,
        description: description.trim(),
        is_available,
      });
      onClose();
    } finally {
      isSubmitting = false;
    }
  }
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl w-full max-w-lg p-6 sm:p-7 space-y-5 shadow-xl animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between pb-3 border-b border-[#f2f2f4]">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#f4f4f6] text-[#17171c] flex items-center justify-center border border-[#e5e5ea]">
            <Package class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Tambah Menu Jualan Baru</h3>
            <p class="text-xs text-[#8e8e93]">Tambahkan item ke katalog kasir POS</p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="p-2 text-[#8e8e93] hover:text-[#17171c] hover:bg-[#f4f4f6] rounded-xl cursor-pointer transition-all"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <div class="space-y-4 text-xs">
        <div class="space-y-1.5">
          <label for="menu-name" class="block font-bold text-[#17171c]">Nama Menu</label>
          <input
            id="menu-name"
            type="text"
            bind:value={name}
            placeholder="Contoh: Es Kopi Susu Aren"
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
          />
        </div>

        <div class="space-y-1.5">
          <label for="menu-category" class="block font-bold text-[#17171c]">Kategori Menu</label>
          <div class="relative">
            <select
              id="menu-category"
              bind:value={category_id}
              class="appearance-none w-full border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-xl px-4 pr-10 py-2.5 bg-[#f8f8fa] hover:bg-white text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
            >
              {#each categories.filter((c) => c.type === 'MENU') as cat}
                <option value={cat.id}>{cat.name}</option>
              {/each}
            </select>
            <ChevronDown class="w-4 h-4 text-[#8e8e93] absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>

        <div class="space-y-1.5">
          <label for="menu-price" class="block font-bold text-[#17171c]">Harga Jual (IDR)</label>
          <input
            id="menu-price"
            type="number"
            bind:value={price}
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl font-mono text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
          />
        </div>

        <div class="space-y-1.5">
          <label for="menu-desc" class="block font-bold text-[#17171c]">Deskripsi / Resep Singkat</label>
          <textarea
            id="menu-desc"
            bind:value={description}
            rows="2"
            placeholder="Keterangan rasa, komposisi, atau panduan penyajian"
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-[#17171c] focus:border-[#17171c] focus:outline-hidden resize-none transition-all shadow-2xs"
          ></textarea>
        </div>

        <div class="flex items-center justify-between p-3.5 bg-[#f8f8fa] rounded-2xl border border-[#ececee]">
          <span class="font-bold text-[#17171c]">Status Ketersediaan Langsung</span>
          <button
            type="button"
            onclick={() => (is_available = !is_available)}
            class={`px-3.5 py-1 rounded-full text-xs font-mono font-semibold transition-all cursor-pointer ${
              is_available
                ? 'bg-[#ecfdf5] text-[#059669] border border-[#a7f3d0]'
                : 'bg-[#fef2f2] text-[#dc2626] border border-[#fecaca]'
            }`}
          >
            {is_available ? 'Tersedia' : 'Habis'}
          </button>
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-3 border border-[#e5e5ea] hover:bg-[#f4f4f6] text-[#686873] text-xs font-semibold rounded-full cursor-pointer transition-all"
        >
          Batal
        </button>
        <button
          type="button"
          disabled={isSubmitting || !name.trim() || price <= 0}
          onclick={handleSubmit}
          class="flex-1 py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full cursor-pointer transition-all disabled:opacity-50 flex items-center justify-center gap-2 shadow-xs"
        >
          {#if isSubmitting}
            <span>Menyimpan...</span>
          {:else}
            <Plus class="w-4 h-4" />
            <span>Simpan Menu</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
