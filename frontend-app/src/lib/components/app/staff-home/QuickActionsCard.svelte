<script lang="ts">
  import { Wallet, ArrowRightLeft, FileText, ChevronRight } from 'lucide-svelte';
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
    myPayrollSlip?.net_salary ||
      (currentUser.base_salary || 3000000) + totalOvertimePay - totalLatePenalty - totalActiveKasbon
  );
</script>

<div class="space-y-4 font-sans">
  <!-- Quick Action Buttons -->
  <div class="grid grid-cols-3 gap-3">
    <button
      type="button"
      onclick={onOpenSwapModal}
      class="group flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border border-[#e5e5ea] bg-white p-4 text-center shadow-2xs transition-all hover:border-[#17171c] sm:rounded-3xl"
    >
      <div
        class="flex h-10 w-10 items-center justify-center rounded-2xl border border-[#bfdbfe] bg-[#eff6ff] text-[#2563eb]"
      >
        <ArrowRightLeft class="h-4 w-4" />
      </div>
      <span class="text-xs font-bold text-[#17171c]">Tukar Shift</span>
    </button>

    <button
      type="button"
      onclick={onOpenKasbonModal}
      class="group flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border border-[#e5e5ea] bg-white p-4 text-center shadow-2xs transition-all hover:border-[#17171c] sm:rounded-3xl"
    >
      <div
        class="flex h-10 w-10 items-center justify-center rounded-2xl border border-[#a7f3d0] bg-[#ecfdf5] text-[#059669]"
      >
        <Wallet class="h-4 w-4" />
      </div>
      <span class="text-xs font-bold text-[#17171c]">Ajukan Kasbon</span>
    </button>

    <button
      type="button"
      onclick={onOpenSlipModal}
      class="group flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border border-[#e5e5ea] bg-white p-4 text-center shadow-2xs transition-all hover:border-[#17171c] sm:rounded-3xl"
    >
      <div
        class="flex h-10 w-10 items-center justify-center rounded-2xl border border-[#e5e5ea] bg-[#f4f4f6] text-[#17171c]"
      >
        <FileText class="h-4 w-4" />
      </div>
      <span class="text-xs font-bold text-[#17171c]">Slip Gaji</span>
    </button>
  </div>

  <!-- Finansial & Kedisiplinan Berjalan -->
  <div
    class="space-y-4 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs sm:rounded-3xl sm:p-6"
  >
    <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
      <div class="flex items-center gap-2.5">
        <Wallet class="h-4 w-4 text-[#17171c]" />
        <h3 class="text-sm font-bold text-[#17171c] sm:text-base">
          Finansial &amp; Kedisiplinan Berjalan
        </h3>
      </div>
      <button
        type="button"
        onclick={onNavigateFinance}
        class="flex cursor-pointer items-center gap-1 font-mono text-xs font-semibold text-[#2563eb] hover:underline"
      >
        <span>Detail</span>
        <ChevronRight class="h-3.5 w-3.5" />
      </button>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <div class="space-y-1 rounded-2xl border border-[#ececee] bg-[#f8f8fa] p-3.5">
        <span class="font-mono text-[10.5px] font-semibold text-[#8e8e93] uppercase"
          >Total Keterlambatan</span
        >
        <div class="font-mono text-base font-bold text-[#e5484d]">{totalLateMinutes} mnt</div>
        <div class="text-[11px] text-[#8e8e93]">Denda: {formatRupiah(totalLatePenalty)}</div>
      </div>

      <div class="space-y-1 rounded-2xl border border-[#ececee] bg-[#f8f8fa] p-3.5">
        <span class="font-mono text-[10.5px] font-semibold text-[#8e8e93] uppercase"
          >Upah Lembur</span
        >
        <div class="font-mono text-base font-bold text-[#059669]">
          +{formatRupiah(totalOvertimePay)}
        </div>
        <div class="text-[11px] text-[#8e8e93]">{totalOvertimeMinutes} menit lembur</div>
      </div>

      <div class="space-y-1 rounded-2xl border border-[#ececee] bg-[#f8f8fa] p-3.5">
        <span class="font-mono text-[10.5px] font-semibold text-[#8e8e93] uppercase"
          >Kasbon Aktif</span
        >
        <div class="font-mono text-base font-bold text-[#e5484d]">
          -{formatRupiah(totalActiveKasbon)}
        </div>
        <div class="text-[11px] text-[#8e8e93]">Dipotong saat gajian</div>
      </div>

      <div class="space-y-1 rounded-2xl bg-[#17171c] p-3.5 text-white shadow-xs">
        <span class="font-mono text-[10.5px] font-semibold text-white/70 uppercase"
          >Estimasi Take-Home Pay</span
        >
        <div class="font-mono text-base font-bold text-white">
          {formatRupiah(estimatedTakeHomePay)}
        </div>
        <div class="text-[11px] text-white/70">Periode berjalan</div>
      </div>
    </div>
  </div>
</div>
