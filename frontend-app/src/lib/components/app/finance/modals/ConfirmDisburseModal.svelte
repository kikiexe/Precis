<script lang="ts">
  import { formatRupiah } from '../../../../utils/formatters';

  interface Props {
    isOpen: boolean;
    totalPayrollDisbursement: number;
    onClose: () => void;
    onConfirm: () => Promise<void> | void;
  }

  let { isOpen, totalPayrollDisbursement, onClose, onConfirm }: Props = $props();
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-3xl w-full max-w-md p-6 space-y-4 shadow-2xl">
      <h3 class="text-base font-medium text-[#212121]">Konfirmasi Pencairan Payroll</h3>
      <p class="text-xs text-[#616161]">
        Total pencairan sebesar <strong class="text-[#17171c] font-mono">{formatRupiah(totalPayrollDisbursement)}</strong> akan disetujui, dan seluruh kasbon aktif karyawan otomatis dinyatakan lunas.
      </p>
      <div class="flex gap-3 pt-2">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#eeece7] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={onConfirm}
          class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer"
        >
          Konfirmasi &amp; Cairkan
        </button>
      </div>
    </div>
  </div>
{/if}
