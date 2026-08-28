<script lang="ts">
  import { X, ChevronDown, FolderTree, Plus } from 'lucide-svelte';

  interface Props {
    isOpen: boolean;
    onClose: () => void;
    onSave: (category: { name: string; type: 'MENU' | 'RAW_MATERIAL' }) => Promise<void>;
  }

  let { isOpen, onClose, onSave }: Props = $props();

  let name = $state('');
  let type = $state<'MENU' | 'RAW_MATERIAL'>('MENU');
  let isSubmitting = $state(false);

  $effect(() => {
    if (isOpen) {
      name = '';
      type = 'MENU';
    }
  });

  async function handleSubmit() {
    if (!name.trim()) return;
    isSubmitting = true;
    try {
      await onSave({ name: name.trim(), type });
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
            <FolderTree class="h-5 w-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Tambah Kategori Baru</h3>
            <p class="text-xs text-[#8e8e93]">Klasifikasi menu POS atau stok bahan baku</p>
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
          <label for="cat-name" class="block font-bold text-[#17171c]">Nama Kategori</label>
          <input
            id="cat-name"
            type="text"
            bind:value={name}
            placeholder="Contoh: Pastry &amp; Bakery, Sirup &amp; Flavour"
            class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1.5">
          <label for="cat-type" class="block font-bold text-[#17171c]">Tipe Kategori</label>
          <div class="relative">
            <select
              id="cat-type"
              bind:value={type}
              class="w-full cursor-pointer appearance-none rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 pr-10 text-xs text-[#17171c] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden"
            >
              <option value="MENU">Menu Jualan POS</option>
              <option value="RAW_MATERIAL">Bahan Baku / Raw Material</option>
            </select>
            <ChevronDown
              class="pointer-events-none absolute top-1/2 right-3.5 h-4 w-4 -translate-y-1/2 text-[#8e8e93]"
            />
          </div>
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
          onclick={handleSubmit}
          disabled={isSubmitting || !name.trim()}
          class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
        >
          {#if isSubmitting}
            <span>Menyimpan...</span>
          {:else}
            <Plus class="h-4 w-4" />
            <span>Simpan Kategori</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
