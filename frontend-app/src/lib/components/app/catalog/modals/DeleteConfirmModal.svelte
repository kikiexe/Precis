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
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="animate-in fade-in zoom-in-95 w-full max-w-md space-y-5 rounded-3xl border border-[#e5e5ea] bg-white p-6 shadow-xl sm:p-7"
    >
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-3">
          <div
            class="flex size-10 items-center justify-center rounded-2xl border border-[#fecaca] bg-[#fef2f2] text-[#dc2626]"
          >
            <AlertTriangle class="size-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">{title}</h3>
            <p class="text-xs text-[#8e8e93]">Tindakan ini tidak dapat dibatalkan</p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="cursor-pointer rounded-xl p-2 text-[#8e8e93] transition-all hover:bg-[#f4f4f6] hover:text-[#17171c]"
        >
          <X class="size-5" />
        </button>
      </div>

      <div class="space-y-3 text-xs text-[#686873]">
        <p class="leading-relaxed">
          {message || `Apakah Anda yakin ingin menghapus "${target?.name || 'item ini'}"?`}
        </p>

        {#if target}
          <div
            class="rounded-2xl border border-[#ececee] bg-[#f8f8fa] p-3.5 font-bold text-[#17171c]"
          >
            {target.name}
          </div>
        {/if}

        {#if errorMessage}
          <div
            class="rounded-2xl border border-[#fecaca] bg-[#fef2f2] p-3.5 text-xs leading-relaxed font-medium text-[#991b1b]"
          >
            {errorMessage}
          </div>
        {/if}
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 cursor-pointer rounded-full border border-[#e5e5ea] py-3 text-xs font-semibold text-[#686873] transition-all hover:bg-[#f4f4f6]"
        >
          Batal
        </button>
        {#if !errorMessage}
          <button
            type="button"
            onclick={onConfirm}
            class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#dc2626] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-[#b91c1c]"
          >
            <Trash2 class="size-4" />
            <span>Hapus Permanen</span>
          </button>
        {/if}
      </div>
    </div>
  </div>
{/if}
