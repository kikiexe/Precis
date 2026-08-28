<script lang="ts">
  import { X } from 'lucide-svelte';
  import type { Category, Product } from '../../../../../types/pos';
  import { DEFAULT_MENU_PRICE_IDR } from '../../../../../constants/defaults';

  interface Props {
    isOpen: boolean;
    categories: Category[];
    onClose: () => void;
    onSave: (product: Product) => void;
  }

  let { isOpen, categories = [], onClose, onSave }: Props = $props();

  let name = $state('');
  let category_id = $state('cat-coffee');
  let base_price = $state(DEFAULT_MENU_PRICE_IDR || 25000);
  let description = $state('');

  $effect(() => {
    if (isOpen) {
      name = '';
      category_id = categories[0]?.id || 'cat-coffee';
      base_price = 25000;
      description = '';
    }
  });

  function handleSubmit() {
    if (!name.trim() || base_price <= 0) return;
    onSave({
      id: `p-${Date.now()}`,
      category_id,
      name: name.trim(),
      base_price,
      description: description.trim() || undefined,
      is_active: true,
    });
    onClose();
  }
</script>

{#if isOpen}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="w-full max-w-md space-y-4 rounded-3xl border border-[#d9d9dd] bg-white p-6 shadow-xl"
    >
      <div class="flex items-center justify-between border-b border-[#e5e5e5] pb-3">
        <h3 class="text-sm font-semibold text-[#17171c]">Tambah Menu Jualan POS Baru</h3>
        <button
          type="button"
          onclick={onClose}
          class="cursor-pointer text-[#75758a] hover:text-[#17171c]"
        >
          <X class="h-4 w-4" />
        </button>
      </div>

      <div class="space-y-3 text-xs">
        <div class="space-y-1">
          <label for="pos-new-menu-name" class="font-medium text-[#17171c]"
            >Nama Produk / Minuman</label
          >
          <input
            id="pos-new-menu-name"
            type="text"
            bind:value={name}
            placeholder="Contoh: Es Kopi Susu Aren"
            class="w-full rounded-full border border-[#d9d9dd] bg-white px-3.5 py-2 text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1">
          <label for="pos-new-menu-cat" class="font-medium text-[#17171c]">Kategori Menu</label>
          <select
            id="pos-new-menu-cat"
            bind:value={category_id}
            class="w-full rounded-full border border-[#d9d9dd] bg-white px-3.5 py-2 text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
          >
            {#each categories as cat}
              <option value={cat.id}>{cat.name}</option>
            {/each}
          </select>
        </div>

        <div class="space-y-1">
          <label for="pos-new-menu-price" class="font-medium text-[#17171c]"
            >Harga Jual Normal (IDR)</label
          >
          <input
            id="pos-new-menu-price"
            type="number"
            bind:value={base_price}
            class="w-full rounded-full border border-[#d9d9dd] bg-white px-3.5 py-2 font-mono text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1">
          <label for="pos-new-menu-desc" class="font-medium text-[#17171c]">Deskripsi Singkat</label
          >
          <textarea
            id="pos-new-menu-desc"
            bind:value={description}
            rows="2"
            placeholder="Deskripsi singkat rasa dan racikan..."
            class="w-full resize-none rounded-2xl border border-[#d9d9dd] bg-white px-3.5 py-2 text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
          ></textarea>
        </div>
      </div>

      <div class="flex gap-2.5 pt-2">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 cursor-pointer rounded-full border border-[#d9d9dd] py-2 text-xs font-medium text-[#616161] hover:bg-[#f4f4f4]"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSubmit}
          class="flex-1 cursor-pointer rounded-full bg-[#17171c] py-2 text-xs font-medium text-white hover:bg-black"
        >
          Simpan Produk
        </button>
      </div>
    </div>
  </div>
{/if}
