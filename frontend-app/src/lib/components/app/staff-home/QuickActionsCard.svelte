<script lang="ts">
  import {
    Wallet,
    ArrowRightLeft,
    FileText,
    ChevronRight,
  } from 'lucide-svelte';
  import type { CashAdvance, PayrollSlipData, User } from '../../../types/app';
  import { formatRupiah } from '../../../utils/formatters';

  interface Props {
    currentUser: User;
    myPayrollSlip: PayrollSlipData | null;
    myCashAdvances: CashAdvance[];
    onOpenSwapModal: () => void;
    onOpenKasbonModal: () => void;
    onOpenSlipModal: () => void;
    onNavigateFinance: () => void;
  }

  let {
    currentUser,
    myPayrollSlip = null,
    myCashAdvances = [],
    onOpenSwapModal,
    onOpenKasbonModal,
    onOpenSlipModal,
    onNavigateFinance,
  }: Props = $props();

  let totalLateMinutes = $derived(myPayrollSlip?.total_late_minutes || 0);
  let totalLatePenalty = $derived(myPayrollSlip?.late_penalty || totalLateMinutes * 2000);
  let totalOvertimeMinutes = $derived(myPayrollSlip?.total_overtime_minutes || 0);
  let totalOvertimePay = $derived(myPayrollSlip?.overtime_pay || 0);
  let totalActiveKasbon = $derived(
    myCashAdvances.filter((k) => k.status === 'APPROVED').reduce((s, k) => s + k.amount, 0)
  );
  let estimatedTakeHomePay = $derived(
    myPayrollSlip?.net_salary || (currentUser.base_salary || 3000000) + totalOvertimePay - totalLatePenalty - totalActiveKasbon
  );
</script>

<div class="space-y-4 font-sans">
  <!-- Quick Action Buttons -->
  <div class="grid grid-cols-3 gap-3">
    <button
      type="button"
      onclick={onOpenSwapModal}
      class="bg-white border border-[#e5e5ea] hover:border-[#17171c] rounded-2xl sm:rounded-3xl p-4 text-center transition-all cursor-pointer group shadow-2xs flex flex-col items-center justify-center gap-2"
    >
      <div class="w-10 h-10 rounded-2xl bg-[#eff6ff] text-[#2563eb] flex items-center justify-center border border-[#bfdbfe]">
        <ArrowRightLeft class="w-4 h-4" />
      </div>
      <span class="text-xs font-bold text-[#17171c]">Tukar Shift</span>
    </button>

    <button
      type="button"
      onclick={onOpenKasbonModal}
      class="bg-white border border-[#e5e5ea] hover:border-[#17171c] rounded-2xl sm:rounded-3xl p-4 text-center transition-all cursor-pointer group shadow-2xs flex flex-col items-center justify-center gap-2"
    >
      <div class="w-10 h-10 rounded-2xl bg-[#ecfdf5] text-[#059669] flex items-center justify-center border border-[#a7f3d0]">
        <Wallet class="w-4 h-4" />
      </div>
      <span class="text-xs font-bold text-[#17171c]">Ajukan Kasbon</span>
    </button>

    <button
      type="button"
      onclick={onOpenSlipModal}
      class="bg-white border border-[#e5e5ea] hover:border-[#17171c] rounded-2xl sm:rounded-3xl p-4 text-center transition-all cursor-pointer group shadow-2xs flex flex-col items-center justify-center gap-2"
    >
      <div class="w-10 h-10 rounded-2xl bg-[#f4f4f6] text-[#17171c] flex items-center justify-center border border-[#e5e5ea]">
        <FileText class="w-4 h-4" />
      </div>
      <span class="text-xs font-bold text-[#17171c]">Slip Gaji</span>
    </button>
  </div>

  <!-- Finansial & Kedisiplinan Berjalan -->
  <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 space-y-4 shadow-2xs">
    <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
      <div class="flex items-center gap-2.5">
        <Wallet class="w-4 h-4 text-[#17171c]" />
        <h3 class="text-sm sm:text-base font-bold text-[#17171c]">Finansial &amp; Kedisiplinan Berjalan</h3>
      </div>
      <button
        type="button"
        onclick={onNavigateFinance}
        class="text-xs font-mono font-semibold text-[#2563eb] hover:underline cursor-pointer flex items-center gap-1"
      >
        <span>Detail</span>
        <ChevronRight class="w-3.5 h-3.5" />
      </button>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="p-3.5 bg-[#f8f8fa] border border-[#ececee] rounded-2xl space-y-1">
        <span class="text-[10.5px] font-mono text-[#8e8e93] uppercase font-semibold">Total Keterlambatan</span>
        <div class="text-base font-bold font-mono text-[#e5484d]">{totalLateMinutes} mnt</div>
        <div class="text-[11px] text-[#8e8e93]">Denda: {formatRupiah(totalLatePenalty)}</div>
      </div>

      <div class="p-3.5 bg-[#f8f8fa] border border-[#ececee] rounded-2xl space-y-1">
        <span class="text-[10.5px] font-mono text-[#8e8e93] uppercase font-semibold">Upah Lembur</span>
        <div class="text-base font-bold font-mono text-[#059669]">+{formatRupiah(totalOvertimePay)}</div>
        <div class="text-[11px] text-[#8e8e93]">{totalOvertimeMinutes} menit lembur</div>
      </div>

      <div class="p-3.5 bg-[#f8f8fa] border border-[#ececee] rounded-2xl space-y-1">
        <span class="text-[10.5px] font-mono text-[#8e8e93] uppercase font-semibold">Kasbon Aktif</span>
        <div class="text-base font-bold font-mono text-[#e5484d]">-{formatRupiah(totalActiveKasbon)}</div>
        <div class="text-[11px] text-[#8e8e93]">Dipotong saat gajian</div>
      </div>

      <div class="p-3.5 bg-[#17171c] text-white rounded-2xl space-y-1 shadow-xs">
        <span class="text-[10.5px] font-mono text-white/70 uppercase font-semibold">Estimasi Take-Home Pay</span>
        <div class="text-base font-bold font-mono text-white">{formatRupiah(estimatedTakeHomePay)}</div>
        <div class="text-[11px] text-white/70">Periode berjalan</div>
      </div>
    </div>
  </div>
</div>
