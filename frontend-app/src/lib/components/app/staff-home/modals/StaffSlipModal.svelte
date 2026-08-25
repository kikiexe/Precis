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
  <div class="fixed inset-0 z-50 bg-[#17171c]/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] max-w-lg w-full p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 font-sans">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <div class="flex items-center gap-2">
          <FileText class="w-4 h-4 text-[#17171c]" />
          <div>
            <h3 class="font-medium text-base text-[#212121]">Slip Gaji Digital</h3>
            <p class="text-[10px] font-mono text-[#75758a]">Periode {myPayrollSlip?.period_start || todayIso} s/d {myPayrollSlip?.period_end || todayIso}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            onclick={() => window.print()}
            class="p-1.5 border border-[#d9d9dd] rounded-xl hover:bg-[#eeece7] text-[#616161] hover:text-[#212121] cursor-pointer"
            title="Cetak Slip Gaji"
          >
            <Printer class="w-4 h-4" />
          </button>
          <button
            type="button"
            onclick={onClose}
            class="text-[#93939f] hover:text-[#212121] cursor-pointer p-1"
          >
            <X class="w-4 h-4" />
          </button>
        </div>
      </div>

      <div class="space-y-3 text-xs">
        <div class="bg-[#eeece7]/40 p-3 rounded-xl flex items-center justify-between">
          <div>
            <div class="font-medium text-[#17171c]">{currentUser.name}</div>
            <div class="text-[10px] font-mono text-[#75758a]">{currentUser.role} &bull; {currentUser.email}</div>
          </div>
          <div class="text-right font-mono text-[10px] text-[#75758a]">
            Rek: {currentUser.bank_name || 'BCA'} {currentUser.bank_account_number || '-'}
          </div>
        </div>

        <div class="space-y-2 pt-1">
          <div class="flex justify-between py-1.5 border-b border-[#f2f2f2]">
            <span class="text-[#616161]">Gaji Pokok</span>
            <span class="font-mono text-[#212121]">{formatRupiah(myPayrollSlip?.base_salary || currentUser.base_salary || 3000000)}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-[#f2f2f2]">
            <span class="text-[#616161]">Upah Lembur ({Math.round(totalOvertimeMinutes / 60)} Jam)</span>
            <span class="font-mono text-[#00875a]">+{formatRupiah(totalOvertimePay)}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-[#f2f2f2]">
            <span class="text-[#616161]">Denda Keterlambatan ({totalLateMinutes} Menit)</span>
            <span class="font-mono text-[#e5484d]">-{formatRupiah(totalLatePenalty)}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-[#f2f2f2]">
            <span class="text-[#616161]">Potongan Kasbon</span>
            <span class="font-mono text-[#e5484d]">-{formatRupiah(totalActiveKasbon)}</span>
          </div>
          <div class="flex justify-between py-3 text-sm font-medium bg-[#fbfbfb] border border-[#d9d9dd] px-3.5 rounded-xl">
            <span class="text-[#17171c]">Take Home Pay (Gaji Bersih)</span>
            <span class="font-mono text-[#00875a] font-semibold">{formatRupiah(estimatedTakeHomePay)}</span>
          </div>
        </div>
      </div>

      <div class="pt-2">
        <button
          type="button"
          onclick={onClose}
          class="w-full py-2.5 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-full cursor-pointer transition-all"
        >
          Tutup
        </button>
      </div>
    </div>
  </div>
{/if}
