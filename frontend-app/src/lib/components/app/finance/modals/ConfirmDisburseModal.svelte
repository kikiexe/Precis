<script lang="ts">
  import { Check, X, DollarSign, Loader2 } from 'lucide-svelte';
  import { formatRupiah } from '@precis/shared-utils';

  interface Props {
    isOpen: boolean;
    totalPayrollDisbursement: number;
    onClose: () => void;
    onConfirm: () => Promise<void> | void;
  }

  let { isOpen, totalPayrollDisbursement, onClose, onConfirm }: Props = $props();

  let isSubmitting = $state(false);

  async function handleConfirm() {
    if (isSubmitting) return;
    isSubmitting = true;
    try {
      await onConfirm();
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
      class="animate-in fade-in zoom-in-95 w-full max-w-md space-y-5 rounded-3xl border border-[#e5e5ea] bg-white p-6 shadow-xl sm:p-7"
    >
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-3">
          <div
            class="flex h-10 w-10 items-center justify-center rounded-2xl border border-[#a7f3d0] bg-[#ecfdf5] text-[#059669]"
          >
            <DollarSign class="h-5 w-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Konfirmasi Pencairan Payroll</h3>
            <p class="text-xs text-[#8e8e93]">Persetujuan transfer gaji staf outlet</p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          disabled={isSubmitting}
          class="cursor-pointer rounded-xl p-2 text-[#8e8e93] transition-all hover:bg-[#f4f4f6] hover:text-[#17171c] disabled:opacity-50"
        >
          <X class="h-5 w-5" />
        </button>
      </div>

      <div class="space-y-1 rounded-2xl border border-[#ececee] bg-[#f8f8fa] p-4">
        <span class="font-mono text-[10.5px] font-semibold text-[#8e8e93] uppercase"
          >Total Nominal Pencairan</span
        >
        <div class="font-mono text-2xl font-bold text-[#17171c]">
          {formatRupiah(totalPayrollDisbursement)}
        </div>
      </div>

      <p class="text-xs leading-relaxed text-[#686873]">
        Seluruh rincian gaji periode ini akan disetujui, dan pinjaman kasbon aktif yang dipotong
        akan otomatis dinyatakan lunas.
      </p>

      <div class="flex items-center gap-3 pt-2">
        <button
          type="button"
          onclick={onClose}
          disabled={isSubmitting}
          class="flex-1 cursor-pointer rounded-full border border-[#e5e5ea] py-3 text-xs font-semibold text-[#686873] transition-all hover:bg-[#f4f4f6] disabled:opacity-50"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleConfirm}
          disabled={isSubmitting}
          class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#059669] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-[#047857] disabled:opacity-50"
        >
          {#if isSubmitting}
            <Loader2 class="h-4 w-4 animate-spin" />
            <span>Memproses...</span>
          {:else}
            <Check class="h-4 w-4" />
            <span>Konfirmasi &amp; Cairkan</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
