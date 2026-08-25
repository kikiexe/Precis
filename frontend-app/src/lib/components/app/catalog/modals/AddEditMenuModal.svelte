<script lang="ts">
  import { X, ChevronDown } from 'lucide-svelte';
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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] w-full max-w-md p-6 space-y-4 shadow-2xl">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3">
        <h3 class="text-base font-medium text-[#212121]">Tambah Menu Jualan Baru</h3>
        <button type="button" onclick={onClose} class="p-1 text-[#616161] hover:text-[#212121] cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="space-y-3 text-xs">
        <div class="space-y-1">
          <label for="menu-name" class="block font-medium text-[#212121]">Nama Menu</label>
          <input
            id="menu-name"
            type="text"
            bind:value={name}
            placeholder="Contoh: Es Kopi Susu Aren"
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1">
          <label for="menu-category" class="block font-medium text-[#212121]">Kategori Menu</label>
          <div class="relative">
            <select
              id="menu-category"
              bind:value={category_id}
              class="appearance-none w-full border border-[#d9d9dd] rounded-full px-3.5 pr-8 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7]/60 text-xs text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
            >
              {#each categories.filter((c) => c.type === 'MENU') as cat}
                <option value={cat.id}>{cat.name}</option>
              {/each}
            </select>
            <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>

        <div class="space-y-1">
          <label for="menu-price" class="block font-medium text-[#212121]">Harga Jual (IDR)</label>
          <input
            id="menu-price"
            type="number"
            bind:value={price}
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1">
          <label for="menu-desc" class="block font-medium text-[#212121]">Deskripsi / Resep Singkat</label>
          <textarea
            id="menu-desc"
            bind:value={description}
            rows="2"
            placeholder="Deskripsi bahan dan rasa..."
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden resize-none"
          ></textarea>
        </div>
      </div>

      <div class="pt-2 flex gap-3">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#eeece7] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSubmit}
          disabled={isSubmitting}
          class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer disabled:opacity-50"
        >
          {isSubmitting ? 'Menyimpan...' : 'Simpan Menu'}
        </button>
      </div>
    </div>
  </div>
{/if}
