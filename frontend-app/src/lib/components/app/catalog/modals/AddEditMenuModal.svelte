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
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="animate-in fade-in zoom-in-95 w-full max-w-lg space-y-5 rounded-3xl border border-[#e5e5ea] bg-white p-6 shadow-xl sm:p-7"
    >
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-3">
          <div
            class="flex h-10 w-10 items-center justify-center rounded-2xl border border-[#e5e5ea] bg-[#f4f4f6] text-[#17171c]"
          >
            <Package class="h-5 w-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Tambah Menu Jualan Baru</h3>
            <p class="text-xs text-[#8e8e93]">Tambahkan item ke katalog kasir POS</p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="cursor-pointer rounded-xl p-2 text-[#8e8e93] transition-all hover:bg-[#f4f4f6] hover:text-[#17171c]"
        >
          <X class="h-5 w-5" />
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
            class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1.5">
          <label for="menu-category" class="block font-bold text-[#17171c]">Kategori Menu</label>
          <div class="relative">
            <select
              id="menu-category"
              bind:value={category_id}
              class="w-full cursor-pointer appearance-none rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 pr-10 text-xs text-[#17171c] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden"
            >
              {#each categories.filter((c) => c.type === 'MENU') as cat}
                <option value={cat.id}>{cat.name}</option>
              {/each}
            </select>
            <ChevronDown
              class="pointer-events-none absolute top-1/2 right-3.5 h-4 w-4 -translate-y-1/2 text-[#8e8e93]"
            />
          </div>
        </div>

        <div class="space-y-1.5">
          <label for="menu-price" class="block font-bold text-[#17171c]">Harga Jual (IDR)</label>
          <input
            id="menu-price"
            type="number"
            bind:value={price}
            class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 font-mono text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1.5">
          <label for="menu-desc" class="block font-bold text-[#17171c]"
            >Deskripsi / Resep Singkat</label
          >
          <textarea
            id="menu-desc"
            bind:value={description}
            rows="2"
            placeholder="Keterangan rasa, komposisi, atau panduan penyajian"
            class="w-full resize-none rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          ></textarea>
        </div>

        <div
          class="flex items-center justify-between rounded-2xl border border-[#ececee] bg-[#f8f8fa] p-3.5"
        >
          <span class="font-bold text-[#17171c]">Status Ketersediaan Langsung</span>
          <button
            type="button"
            onclick={() => (is_available = !is_available)}
            class={`cursor-pointer rounded-full px-3.5 py-1 font-mono text-xs font-semibold transition-all ${
              is_available
                ? 'border border-[#a7f3d0] bg-[#ecfdf5] text-[#059669]'
                : 'border border-[#fecaca] bg-[#fef2f2] text-[#dc2626]'
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
          class="flex-1 cursor-pointer rounded-full border border-[#e5e5ea] py-3 text-xs font-semibold text-[#686873] transition-all hover:bg-[#f4f4f6]"
        >
          Batal
        </button>
        <button
          type="button"
          disabled={isSubmitting || !name.trim() || price <= 0}
          onclick={handleSubmit}
          class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
        >
          {#if isSubmitting}
            <span>Menyimpan...</span>
          {:else}
            <Plus class="h-4 w-4" />
            <span>Simpan Menu</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
