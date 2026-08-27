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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl w-full max-w-lg p-6 sm:p-7 space-y-5 shadow-xl animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between pb-3 border-b border-[#f2f2f4]">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#f4f4f6] text-[#17171c] flex items-center justify-center border border-[#e5e5ea]">
            <FolderTree class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Tambah Kategori Baru</h3>
            <p class="text-xs text-[#8e8e93]">Klasifikasi menu POS atau stok bahan baku</p>
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
          <label for="cat-name" class="block font-bold text-[#17171c]">Nama Kategori</label>
          <input
            id="cat-name"
            type="text"
            bind:value={name}
            placeholder="Contoh: Pastry &amp; Bakery, Sirup &amp; Flavour"
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
          />
        </div>

        <div class="space-y-1.5">
          <label for="cat-type" class="block font-bold text-[#17171c]">Tipe Kategori</label>
          <div class="relative">
            <select
              id="cat-type"
              bind:value={type}
              class="appearance-none w-full border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-xl px-4 pr-10 py-2.5 bg-[#f8f8fa] hover:bg-white text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
            >
              <option value="MENU">Menu Jualan POS</option>
              <option value="RAW_MATERIAL">Bahan Baku / Raw Material</option>
            </select>
            <ChevronDown class="w-4 h-4 text-[#8e8e93] absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
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
          onclick={handleSubmit}
          disabled={isSubmitting || !name.trim()}
          class="flex-1 py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full cursor-pointer transition-all disabled:opacity-50 flex items-center justify-center gap-2 shadow-xs"
        >
          {#if isSubmitting}
            <span>Menyimpan...</span>
          {:else}
            <Plus class="w-4 h-4" />
            <span>Simpan Kategori</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
