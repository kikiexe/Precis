<script lang="ts">
  import {
    Wallet,
    ArrowRightLeft,
    FileText,
    TrendingDown,
    TrendingUp,
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

<div class="space-y-3 sm:space-y-4 font-sans">
  <!-- Quick Action Buttons -->
  <div class="grid grid-cols-3 gap-2">
    <button
      type="button"
      onclick={onOpenSwapModal}
      class="bg-white border border-[#d9d9dd] hover:border-[#17171c] rounded-2xl p-3 text-center transition-all cursor-pointer group shadow-none flex flex-col items-center justify-center gap-1.5"
    >
      <div class="w-7 h-7 rounded-full bg-[#f1f5ff] text-[#1863dc] flex items-center justify-center">
        <ArrowRightLeft class="w-3.5 h-3.5" />
      </div>
      <span class="text-[11px] font-medium text-[#212121]">Tukar Shift</span>
    </button>

    <button
      type="button"
      onclick={onOpenKasbonModal}
      class="bg-white border border-[#d9d9dd] hover:border-[#17171c] rounded-2xl p-3 text-center transition-all cursor-pointer group shadow-none flex flex-col items-center justify-center gap-1.5"
    >
      <div class="w-7 h-7 rounded-full bg-[#edfce9] text-[#00875a] flex items-center justify-center">
        <Wallet class="w-3.5 h-3.5" />
      </div>
      <span class="text-[11px] font-medium text-[#212121]">Ajukan Kasbon</span>
    </button>

    <button
      type="button"
      onclick={onOpenSlipModal}
      class="bg-white border border-[#d9d9dd] hover:border-[#17171c] rounded-2xl p-3 text-center transition-all cursor-pointer group shadow-none flex flex-col items-center justify-center gap-1.5"
    >
      <div class="w-7 h-7 rounded-full bg-[#eeece7] text-[#17171c] flex items-center justify-center">
        <FileText class="w-3.5 h-3.5" />
      </div>
      <span class="text-[11px] font-medium text-[#212121]">Slip Gaji</span>
    </button>
  </div>

  <!-- Finansial & Kedisiplinan Berjalan -->
  <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 sm:p-5 space-y-3.5 shadow-none">
    <div class="flex items-center justify-between border-b border-[#f2f2f2] pb-2.5">
      <div class="flex items-center gap-2">
        <Wallet class="w-4 h-4 text-[#17171c]" />
        <h3 class="text-xs sm:text-sm font-medium text-[#212121]">Finansial &amp; Kedisiplinan Berjalan</h3>
      </div>
      <button
        type="button"
        onclick={onNavigateFinance}
        class="text-[10px] font-mono text-[#1863dc] hover:underline cursor-pointer flex items-center gap-0.5"
      >
        <span>Detail</span>
        <ChevronRight class="w-3 h-3" />
      </button>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
      <!-- Akumulasi Telat & Denda -->
      <div class="bg-[#fbfbfb] border border-[#d9d9dd]/60 rounded-xl p-2.5 space-y-1">
        <div class="flex items-center justify-between text-[10px] text-[#75758a] font-mono">
          <span>Keterlambatan</span>
          <TrendingDown class="w-3 h-3 text-[#e5484d]" />
        </div>
        <div class="font-mono text-sm font-semibold text-[#e5484d]">
          {totalLateMinutes} Menit
        </div>
        <div class="text-[9px] text-[#93939f] font-mono">
          Denda: -{formatRupiah(totalLatePenalty)}
        </div>
      </div>

      <!-- Akumulasi Lembur & Upah -->
      <div class="bg-[#fbfbfb] border border-[#d9d9dd]/60 rounded-xl p-2.5 space-y-1">
        <div class="flex items-center justify-between text-[10px] text-[#75758a] font-mono">
          <span>Lembur Berjalan</span>
          <TrendingUp class="w-3 h-3 text-[#00875a]" />
        </div>
        <div class="font-mono text-sm font-semibold text-[#00875a]">
          {Math.round(totalOvertimeMinutes / 60)} Jam
        </div>
        <div class="text-[9px] text-[#93939f] font-mono">
          Upah: +{formatRupiah(totalOvertimePay)}
        </div>
      </div>

      <!-- Sisa Kasbon Aktif -->
      <div class="bg-[#fbfbfb] border border-[#d9d9dd]/60 rounded-xl p-2.5 space-y-1">
        <div class="flex items-center justify-between text-[10px] text-[#75758a] font-mono">
          <span>Sisa Kasbon</span>
          <Wallet class="w-3 h-3 text-[#75758a]" />
        </div>
        <div class="font-mono text-sm font-semibold text-[#17171c]">
          {formatRupiah(totalActiveKasbon)}
        </div>
        <div class="text-[9px] text-[#93939f] font-mono">
          {totalActiveKasbon > 0 ? 'Dipotong payroll' : 'Lunas'}
        </div>
      </div>

      <!-- Estimasi Take Home Pay -->
      <div class="bg-[#17171c] text-white rounded-xl p-2.5 space-y-1">
        <div class="text-[10px] text-white/70 font-mono">
          Estimasi Gaji Bersih
        </div>
        <div class="font-mono text-sm font-semibold text-white truncate">
          {formatRupiah(estimatedTakeHomePay)}
        </div>
        <div class="text-[9px] text-[#00875a] font-mono font-medium">
          Periode Berjalan
        </div>
      </div>
    </div>
  </div>
</div>
