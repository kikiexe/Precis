<script lang="ts">
  import { AlertTriangle, Trash2, X } from 'lucide-svelte';

  interface Props {
    isOpen?: boolean;
    title?: string;
    message?: string;
    target?: {
      type: 'menu' | 'kategori' | 'bahan';
      id: string;
      name: string;
    } | null;
    errorMessage?: string | null;
    onClose: () => void;
    onConfirm: () => Promise<void> | void;
  }

  let {
    isOpen = true,
    title = 'Konfirmasi Hapus',
    message = '',
    target = null,
    errorMessage = null,
    onClose,
    onConfirm,
  }: Props = $props();

  let show = $derived(isOpen || !!target);
</script>

{#if show}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl w-full max-w-md p-6 sm:p-7 space-y-5 shadow-xl animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between pb-3 border-b border-[#f2f2f4]">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#fef2f2] text-[#dc2626] flex items-center justify-center border border-[#fecaca]">
            <AlertTriangle class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">{title}</h3>
            <p class="text-xs text-[#8e8e93]">Tindakan ini tidak dapat dibatalkan</p>
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

      <div class="text-xs text-[#686873] space-y-3">
        <p class="leading-relaxed">
          {message || `Apakah Anda yakin ingin menghapus "${target?.name || 'item ini'}"?`}
        </p>

        {#if target}
          <div class="p-3.5 rounded-2xl bg-[#f8f8fa] border border-[#ececee] font-bold text-[#17171c]">
            {target.name}
          </div>
        {/if}

        {#if errorMessage}
          <div class="p-3.5 rounded-2xl bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs font-medium leading-relaxed">
            {errorMessage}
          </div>
        {/if}
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-3 text-xs font-semibold border border-[#e5e5ea] hover:bg-[#f4f4f6] rounded-full text-[#686873] cursor-pointer transition-all"
        >
          Batal
        </button>
        {#if !errorMessage}
          <button
            type="button"
            onclick={onConfirm}
            class="flex-1 py-3 text-xs font-semibold bg-[#dc2626] hover:bg-[#b91c1c] text-white rounded-full cursor-pointer transition-all shadow-xs flex items-center justify-center gap-2"
          >
            <Trash2 class="w-4 h-4" />
            <span>Hapus Permanen</span>
          </button>
        {/if}
      </div>
    </div>
  </div>
{/if}
