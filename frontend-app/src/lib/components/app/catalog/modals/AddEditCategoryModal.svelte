<script lang="ts">
  import { X, ChevronDown } from 'lucide-svelte';

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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] w-full max-w-md p-6 space-y-4 shadow-2xl">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3">
        <h3 class="text-base font-medium text-[#212121]">Tambah Kategori Baru</h3>
        <button type="button" onclick={onClose} class="p-1 text-[#616161] hover:text-[#212121] cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="space-y-3 text-xs">
        <div class="space-y-1">
          <label for="cat-name" class="block font-medium text-[#212121]">Nama Kategori</label>
          <input
            id="cat-name"
            type="text"
            bind:value={name}
            placeholder="Contoh: Pastry &amp; Bakery"
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1">
          <label for="cat-type" class="block font-medium text-[#212121]">Tipe Kategori</label>
          <div class="relative">
            <select
              id="cat-type"
              bind:value={type}
              class="appearance-none w-full border border-[#d9d9dd] rounded-full px-3.5 pr-8 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7]/60 text-xs text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
            >
              <option value="MENU">Menu Jualan POS</option>
              <option value="RAW_MATERIAL">Bahan Baku / Raw Material</option>
            </select>
            <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
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
          {isSubmitting ? 'Menyimpan...' : 'Simpan Kategori'}
        </button>
      </div>
    </div>
  </div>
{/if}
