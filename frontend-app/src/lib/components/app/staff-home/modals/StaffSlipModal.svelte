<script lang="ts">
  import { FileText, Printer, X } from 'lucide-svelte';
  import type { PayrollSlipData, User } from '../../../../types/app';
  import { formatRupiah } from '../../../../utils/formatters';

  interface Props {
    isOpen: boolean;
    currentUser: User;
    myPayrollSlip: PayrollSlipData | null;
    todayIso: string;
    totalLateMinutes: number;
    totalLatePenalty: number;
    totalOvertimeMinutes: number;
    totalOvertimePay: number;
    totalActiveKasbon: number;
    estimatedTakeHomePay: number;
    onClose: () => void;
  }

  let {
    isOpen,
    currentUser,
    myPayrollSlip,
    todayIso,
    totalLateMinutes,
    totalLatePenalty,
    totalOvertimeMinutes,
    totalOvertimePay,
    totalActiveKasbon,
    estimatedTakeHomePay,
    onClose,
  }: Props = $props();
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4 font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-xl space-y-5 animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#f4f4f6] text-[#17171c] flex items-center justify-center border border-[#e5e5ea]">
            <FileText class="w-5 h-5" />
          </div>
          <div>
            <h3 class="font-bold text-base text-[#17171c]">Slip Gaji Digital</h3>
            <p class="text-xs font-mono text-[#8e8e93]">Periode {myPayrollSlip?.period_start || todayIso} s/d {myPayrollSlip?.period_end || todayIso}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            onclick={() => window.print()}
            class="p-2 border border-[#e5e5ea] rounded-xl hover:bg-[#f4f4f6] text-[#686873] hover:text-[#17171c] cursor-pointer transition-all"
            title="Cetak Slip Gaji"
          >
            <Printer class="w-4 h-4" />
          </button>
          <button
            type="button"
            onclick={onClose}
            class="p-2 text-[#8e8e93] hover:text-[#17171c] hover:bg-[#f4f4f6] rounded-xl cursor-pointer transition-all"
          >
            <X class="w-5 h-5" />
          </button>
        </div>
      </div>

      <div class="space-y-4 text-xs">
        <div class="bg-[#f8f8fa] p-4 rounded-2xl border border-[#ececee] flex items-center justify-between">
          <div>
            <div class="font-bold text-sm text-[#17171c]">{currentUser.name}</div>
            <div class="text-[11px] font-mono text-[#8e8e93]">{currentUser.role} &bull; {currentUser.email}</div>
          </div>
          <div class="text-right font-mono text-[11px] text-[#8e8e93]">
            Rek: {currentUser.bank_name || 'BCA'} {currentUser.bank_account_number || '-'}
          </div>
        </div>

        <div class="space-y-2.5 pt-1">
          <div class="flex justify-between py-2 border-b border-[#f2f2f4]">
            <span class="text-[#686873]">Gaji Pokok</span>
            <span class="font-mono font-bold text-[#17171c]">{formatRupiah(myPayrollSlip?.base_salary || currentUser.base_salary || 3000000)}</span>
          </div>
          <div class="flex justify-between py-2 border-b border-[#f2f2f4]">
            <span class="text-[#686873]">Upah Lembur ({Math.round(totalOvertimeMinutes / 60)} Jam)</span>
            <span class="font-mono font-bold text-[#059669]">+{formatRupiah(totalOvertimePay)}</span>
          </div>
          <div class="flex justify-between py-2 border-b border-[#f2f2f4]">
            <span class="text-[#686873]">Denda Keterlambatan ({totalLateMinutes} Menit)</span>
            <span class="font-mono font-bold text-[#e5484d]">-{formatRupiah(totalLatePenalty)}</span>
          </div>
          <div class="flex justify-between py-2 border-b border-[#f2f2f4]">
            <span class="text-[#686873]">Potongan Kasbon</span>
            <span class="font-mono font-bold text-[#e5484d]">-{formatRupiah(totalActiveKasbon)}</span>
          </div>
          <div class="flex justify-between py-3.5 text-sm font-bold bg-[#fafafc] border border-[#e5e5ea] px-4 rounded-2xl shadow-2xs">
            <span class="text-[#17171c]">Take Home Pay (Gaji Bersih)</span>
            <span class="font-mono text-[#059669] text-base">{formatRupiah(estimatedTakeHomePay)}</span>
          </div>
        </div>
      </div>

      <div class="pt-2">
        <button
          type="button"
          onclick={onClose}
          class="w-full py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full cursor-pointer transition-all shadow-xs"
        >
          Tutup
        </button>
      </div>
    </div>
  </div>
{/if}
