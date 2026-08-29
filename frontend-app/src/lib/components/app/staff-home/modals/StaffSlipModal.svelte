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
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="animate-in fade-in zoom-in-95 w-full max-w-lg space-y-5 rounded-3xl border border-[#e5e5ea] bg-white p-6 shadow-xl sm:p-7"
    >
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-3">
          <div
            class="flex size-10 items-center justify-center rounded-2xl border border-[#e5e5ea] bg-[#f4f4f6] text-[#17171c]"
          >
            <FileText class="size-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Slip Gaji Digital</h3>
            <p class="font-mono text-xs text-[#8e8e93]">
              Periode {myPayrollSlip?.period_start || todayIso} s/d {myPayrollSlip?.period_end ||
                todayIso}
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            onclick={() => window.print()}
            class="cursor-pointer rounded-xl border border-[#e5e5ea] p-2 text-[#686873] transition-all hover:bg-[#f4f4f6] hover:text-[#17171c]"
            title="Cetak Slip Gaji"
          >
            <Printer class="size-4" />
          </button>
          <button
            type="button"
            onclick={onClose}
            class="cursor-pointer rounded-xl p-2 text-[#8e8e93] transition-all hover:bg-[#f4f4f6] hover:text-[#17171c]"
          >
            <X class="size-5" />
          </button>
        </div>
      </div>

      <div class="space-y-4 text-xs">
        <div
          class="flex items-center justify-between rounded-2xl border border-[#ececee] bg-[#f8f8fa] p-4"
        >
          <div>
            <div class="text-sm font-bold text-[#17171c]">{currentUser.name}</div>
            <div class="font-mono text-[11px] text-[#8e8e93]">
              {currentUser.role} &bull; {currentUser.email}
            </div>
          </div>
          <div class="text-right font-mono text-[11px] text-[#8e8e93]">
            Rek: {currentUser.bank_name || 'BCA'}
            {currentUser.bank_account_number || '-'}
          </div>
        </div>

        <div class="space-y-2.5 pt-1">
          <div class="flex justify-between border-b border-[#f2f2f4] py-2">
            <span class="text-[#686873]">Gaji Pokok</span>
            <span class="font-mono font-bold text-[#17171c]"
              >{formatRupiah(
                myPayrollSlip?.base_salary || currentUser.base_salary || 3000000
              )}</span
            >
          </div>
          <div class="flex justify-between border-b border-[#f2f2f4] py-2">
            <span class="text-[#686873]"
              >Upah Lembur ({Math.round(totalOvertimeMinutes / 60)} Jam)</span
            >
            <span class="font-mono font-bold text-[#059669]">+{formatRupiah(totalOvertimePay)}</span
            >
          </div>
          <div class="flex justify-between border-b border-[#f2f2f4] py-2">
            <span class="text-[#686873]">Denda Keterlambatan ({totalLateMinutes} Menit)</span>
            <span class="font-mono font-bold text-[#e5484d]">-{formatRupiah(totalLatePenalty)}</span
            >
          </div>
          <div class="flex justify-between border-b border-[#f2f2f4] py-2">
            <span class="text-[#686873]">Potongan Kasbon</span>
            <span class="font-mono font-bold text-[#e5484d]"
              >-{formatRupiah(totalActiveKasbon)}</span
            >
          </div>
          <div
            class="flex justify-between rounded-2xl border border-[#e5e5ea] bg-[#fafafc] px-4 py-3.5 text-sm font-bold shadow-2xs"
          >
            <span class="text-[#17171c]">Take Home Pay (Gaji Bersih)</span>
            <span class="font-mono text-base text-[#059669]"
              >{formatRupiah(estimatedTakeHomePay)}</span
            >
          </div>
        </div>
      </div>

      <div class="pt-2">
        <button
          type="button"
          onclick={onClose}
          class="w-full cursor-pointer rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
        >
          Tutup
        </button>
      </div>
    </div>
  </div>
{/if}
