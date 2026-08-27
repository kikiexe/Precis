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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl w-full max-w-md p-6 sm:p-7 space-y-5 shadow-xl animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between pb-3 border-b border-[#f2f2f4]">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#ecfdf5] text-[#059669] flex items-center justify-center border border-[#a7f3d0]">
            <DollarSign class="w-5 h-5" />
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
          class="p-2 text-[#8e8e93] hover:text-[#17171c] hover:bg-[#f4f4f6] rounded-xl cursor-pointer transition-all disabled:opacity-50"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <div class="p-4 bg-[#f8f8fa] border border-[#ececee] rounded-2xl space-y-1">
        <span class="text-[10.5px] font-mono uppercase text-[#8e8e93] font-semibold">Total Nominal Pencairan</span>
        <div class="text-2xl font-bold font-mono text-[#17171c]">
          {formatRupiah(totalPayrollDisbursement)}
        </div>
      </div>

      <p class="text-xs text-[#686873] leading-relaxed">
        Seluruh rincian gaji periode ini akan disetujui, dan pinjaman kasbon aktif yang dipotong akan otomatis dinyatakan lunas.
      </p>

      <div class="flex items-center gap-3 pt-2">
        <button
          type="button"
          onclick={onClose}
          disabled={isSubmitting}
          class="flex-1 py-3 text-xs font-semibold border border-[#e5e5ea] hover:bg-[#f4f4f6] rounded-full text-[#686873] cursor-pointer transition-all disabled:opacity-50"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleConfirm}
          disabled={isSubmitting}
          class="flex-1 py-3 text-xs font-semibold bg-[#059669] hover:bg-[#047857] text-white rounded-full cursor-pointer transition-all shadow-xs flex items-center justify-center gap-2 disabled:opacity-50"
        >
          {#if isSubmitting}
            <Loader2 class="w-4 h-4 animate-spin" />
            <span>Memproses...</span>
          {:else}
            <Check class="w-4 h-4" />
            <span>Konfirmasi &amp; Cairkan</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
