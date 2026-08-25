<script lang="ts">
  import { AlertTriangle } from 'lucide-svelte';

  interface Props {
    target: {
      type: 'menu' | 'kategori' | 'bahan';
      id: string;
      name: string;
    } | null;
    errorMessage: string | null;
    onClose: () => void;
    onConfirm: () => Promise<void> | void;
  }

  let { target, errorMessage, onClose, onConfirm }: Props = $props();
</script>

{#if target}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] w-full max-w-sm p-6 space-y-4 shadow-2xl">
      <div class="flex items-center gap-3 text-[#e5484d]">
        <div class="p-2.5 rounded-full bg-[#ffefef]">
          <AlertTriangle class="w-5 h-5" />
        </div>
        <div>
          <h3 class="text-sm font-medium text-[#212121]">Konfirmasi Hapus</h3>
          <p class="text-[11px] text-[#75758a]">Tindakan ini bersifat permanen</p>
        </div>
      </div>

      <div class="text-xs text-[#616161] space-y-2">
        <p>
          Apakah Anda yakin ingin menghapus {target.type === 'menu' ? 'menu' : target.type === 'kategori' ? 'kategori' : 'bahan baku'} berikut:
        </p>
        <div class="p-3 rounded-xl bg-[#eeece7]/50 border border-[#d9d9dd] font-medium text-[#17171c]">
          {target.name}
        </div>

        {#if errorMessage}
          <div class="p-2.5 rounded-xl bg-[#ffefef] border border-[#e5484d]/30 text-[#e5484d] text-[11px] leading-relaxed">
            {errorMessage}
          </div>
        {/if}
      </div>

      <div class="pt-2 flex gap-2.5">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#eeece7] cursor-pointer"
        >
          Batal
        </button>
        {#if !errorMessage}
          <button
            type="button"
            onclick={onConfirm}
            class="flex-1 py-2 text-xs font-medium bg-[#e5484d] hover:bg-[#c93b40] text-white rounded-full cursor-pointer"
          >
            Hapus Permanen
          </button>
        {/if}
      </div>
    </div>
  </div>
{/if}
