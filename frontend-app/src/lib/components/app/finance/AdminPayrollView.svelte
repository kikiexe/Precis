<script lang="ts">
  import { Download, Check, RefreshCw, Calendar } from 'lucide-svelte';
  import type { BranchItem, PayrollPreviewData } from '../../../types/app';
  import { formatRupiah } from '@precis/shared-utils';

  interface Props {
    branches?: BranchItem[];
    payrollPreview: PayrollPreviewData | null;
    selectedBranchFilter?: string;
    canDisbursePayroll?: boolean;
    onSelectBranchFilter?: (branch: string) => void;
    onFilterPeriod: (start: string, end: string) => Promise<void>;
    onOpenConfirmDisburse: () => void;
    onExportCsv: (start: string, end: string, format: 'BCA' | 'MANDIRI') => Promise<void>;
  }

  let {
    payrollPreview = null,
    canDisbursePayroll = true,
    onFilterPeriod,
    onOpenConfirmDisburse,
    onExportCsv,
  }: Props = $props();

  function getDefaultPeriodStart(): string {
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
  }

  function getDefaultPeriodEnd(): string {
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
  }

  let filterPeriodStart = $state(getDefaultPeriodStart());
  let filterPeriodEnd = $state(getDefaultPeriodEnd());
  let isFiltering = $state(false);
  let isExporting = $state(false);
  let actionMessage = $state<string | null>(null);

  $effect(() => {
    if (payrollPreview?.period_start) filterPeriodStart = payrollPreview.period_start;
    if (payrollPreview?.period_end) filterPeriodEnd = payrollPreview.period_end;
  });

  let payrollItems = $derived(
    (payrollPreview?.items || []).filter((item) => item.role !== 'OWNER')
  );

  let isAllDisbursed = $derived(
    payrollItems.length > 0 && payrollItems.every((item) => item.status === 'DISBURSED')
  );

  let totalBaseSalary = $derived(payrollItems.reduce((sum, item) => sum + item.base_salary, 0));

  let totalLatePenalty = $derived(payrollItems.reduce((sum, item) => sum + item.late_penalty, 0));

  let totalOvertimePay = $derived(payrollItems.reduce((sum, item) => sum + item.overtime_pay, 0));

  let totalCashAdvanceDeduction = $derived(
    payrollItems.reduce((sum, item) => sum + item.cash_advance_deduction, 0)
  );

  let totalPayrollDisbursement = $derived(
    payrollItems.reduce((sum, item) => sum + item.net_salary, 0)
  );

  async function handleFilter() {
    isFiltering = true;
    actionMessage = null;
    try {
      await onFilterPeriod(filterPeriodStart, filterPeriodEnd);
      actionMessage = `Pratinjau payroll periode ${filterPeriodStart} s/d ${filterPeriodEnd} berhasil diperbarui.`;
    } catch (e: unknown) {
      actionMessage = e instanceof Error ? e.message : 'Gagal memuat pratinjau payroll.';
    } finally {
      isFiltering = false;
    }
  }

  async function handleExport() {
    isExporting = true;
    actionMessage = null;
    try {
      await onExportCsv(filterPeriodStart, filterPeriodEnd, 'BCA');
      actionMessage = 'File rekap CSV payroll berhasil diunduh.';
    } catch (e: unknown) {
      actionMessage = e instanceof Error ? e.message : 'Gagal mengunduh CSV payroll.';
    } finally {
      isExporting = false;
    }
  }
</script>

<div class="space-y-6 font-sans">
  {#if actionMessage}
    <div
      class="flex items-center justify-between rounded-2xl border border-[#bfdbfe] bg-[#eff6ff] p-4 text-xs font-semibold text-[#1d4ed8] shadow-2xs"
    >
      <span>{actionMessage}</span>
      <button
        type="button"
        onclick={() => (actionMessage = null)}
        class="cursor-pointer text-[#8e8e93] hover:text-[#17171c]">&times;</button
      >
    </div>
  {/if}

  <!-- Unified Payroll Control Toolbar -->
  <div
    class="space-y-4 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs sm:rounded-3xl sm:p-6"
  >
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
      <!-- Date Range & Refresh -->
      <div class="flex max-w-lg flex-1 items-center gap-2.5">
        <div
          class="flex min-w-0 flex-1 items-center gap-2 rounded-2xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 font-mono text-xs shadow-2xs"
        >
          <Calendar class="size-4 shrink-0 text-[#8e8e93]" />
          <input
            id="filter-period-start"
            type="date"
            bind:value={filterPeriodStart}
            class="w-full min-w-0 cursor-pointer bg-transparent text-xs text-[#17171c] focus:outline-hidden"
          />
          <span class="px-1 font-sans text-[#8e8e93]">s/d</span>
          <input
            id="filter-period-end"
            type="date"
            bind:value={filterPeriodEnd}
            class="w-full min-w-0 cursor-pointer bg-transparent text-xs text-[#17171c] focus:outline-hidden"
          />
        </div>

        <button
          type="button"
          onclick={handleFilter}
          disabled={isFiltering}
          class="flex shrink-0 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-[#17171c] px-4 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
          title="Hitung ulang kalkulasi penggajian"
        >
          <RefreshCw class={`size-3.5 ${isFiltering ? 'animate-spin' : ''}`} />
          <span class="hidden sm:inline">{isFiltering ? 'Memuat...' : 'Hitung Ulang'}</span>
        </button>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
        {#if canDisbursePayroll}
          <button
            type="button"
            onclick={handleExport}
            disabled={isExporting || !payrollPreview}
            class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-[#e5e5ea] bg-white px-4 py-2.5 text-xs font-semibold text-[#17171c] shadow-2xs transition-all hover:bg-[#f8f8fa] disabled:opacity-50 sm:flex-none"
          >
            <Download class="size-4 shrink-0 text-[#8e8e93]" />
            <span>{isExporting ? 'Mengunduh...' : 'Export CSV (BCA)'}</span>
          </button>

          <button
            type="button"
            onclick={onOpenConfirmDisburse}
            disabled={!payrollPreview || isAllDisbursed}
            class="px-5 py-2.5 {isAllDisbursed
              ? 'cursor-default border border-[#a7f3d0] bg-[#ecfdf5] text-[#059669]'
              : 'cursor-pointer bg-[#059669] text-white hover:bg-[#047857]'} flex flex-1 items-center justify-center gap-2 rounded-2xl text-xs font-semibold shadow-xs transition-all disabled:opacity-50 sm:flex-none"
          >
            <Check class="size-4 shrink-0" />
            <span>{isAllDisbursed ? 'Sudah Dicairkan' : 'Cairkan Payroll'}</span>
          </button>
        {:else}
          <div
            class="flex items-center gap-2 rounded-2xl border border-[#e5e5ea] bg-[#f8f8fa] px-3.5 py-2 text-[11px] font-medium text-[#8e8e93]"
          >
            <span>Pratinjau Rekap (Pencairan Khusus Owner / Manager Payroll)</span>
          </div>
        {/if}
      </div>
    </div>
  </div>

  <!-- Financial KPI Metrics Grid -->
  <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-4 lg:grid-cols-5">
    <div
      class="flex flex-col justify-between rounded-2xl border border-[#e5e5ea] bg-white p-4 shadow-2xs sm:p-5"
    >
      <span class="truncate text-[11px] font-medium tracking-wider text-[#8e8e93] uppercase"
        >Gaji Pokok Total</span
      >
      <div class="mt-1 truncate font-mono text-sm font-bold text-[#17171c] sm:text-base">
        {formatRupiah(totalBaseSalary)}
      </div>
    </div>
    <div
      class="flex flex-col justify-between rounded-2xl border border-[#e5e5ea] bg-white p-4 shadow-2xs sm:p-5"
    >
      <span class="truncate text-[11px] font-medium tracking-wider text-[#059669] uppercase"
        >Upah Lembur</span
      >
      <div class="mt-1 truncate font-mono text-sm font-bold text-[#059669] sm:text-base">
        +{formatRupiah(totalOvertimePay)}
      </div>
    </div>
    <div
      class="flex flex-col justify-between rounded-2xl border border-[#e5e5ea] bg-white p-4 shadow-2xs sm:p-5"
    >
      <span class="truncate text-[11px] font-medium tracking-wider text-[#e5484d] uppercase"
        >Denda Telat</span
      >
      <div class="mt-1 truncate font-mono text-sm font-bold text-[#e5484d] sm:text-base">
        -{formatRupiah(totalLatePenalty)}
      </div>
    </div>
    <div
      class="flex flex-col justify-between rounded-2xl border border-[#e5e5ea] bg-white p-4 shadow-2xs sm:p-5"
    >
      <span class="truncate text-[11px] font-medium tracking-wider text-[#e5484d] uppercase"
        >Potongan Kasbon</span
      >
      <div class="mt-1 truncate font-mono text-sm font-bold text-[#e5484d] sm:text-base">
        -{formatRupiah(totalCashAdvanceDeduction)}
      </div>
    </div>
    <div
      class="col-span-2 flex flex-col justify-between rounded-2xl bg-[#17171c] p-4 text-white shadow-xs sm:col-span-4 sm:p-5 lg:col-span-1"
    >
      <span class="truncate text-[11px] font-medium tracking-wider text-white/70 uppercase"
        >Total Pencairan Bersih</span
      >
      <div class="mt-1 truncate font-mono text-base font-bold text-white sm:text-lg">
        {formatRupiah(totalPayrollDisbursement)}
      </div>
    </div>
  </div>

  <!-- Payroll Breakdown Table -->
  <div
    class="overflow-hidden rounded-2xl border border-[#e5e5ea] bg-white shadow-2xs sm:rounded-3xl"
  >
    <div class="flex items-center justify-between gap-3 border-b border-[#e5e5ea] p-5">
      <div>
        <h3 class="text-base font-bold text-[#17171c]">Rincian Gaji Bersih per Karyawan</h3>
        <p class="mt-0.5 text-xs text-[#8e8e93]">
          Kalkulasi presensi, absensi, denda terlambat, dan potongan kasbon
        </p>
      </div>
      <span
        class="rounded-full bg-[#f4f4f6] px-3 py-1 font-mono text-xs font-semibold text-[#686873]"
      >
        {payrollItems.length} Karyawan
      </span>
    </div>

    <!-- Mobile Card View -->
    <div class="block divide-y divide-[#f2f2f4] md:hidden">
      {#if payrollItems.length > 0}
        {#each payrollItems as item}
          <div class="space-y-3.5 p-5">
            <div class="flex items-start justify-between gap-2">
              <div>
                <h4 class="text-sm font-bold text-[#17171c]">{item.name}</h4>
                <p class="mt-0.5 font-mono text-xs text-[#8e8e93]">{item.role}</p>
              </div>
              {#if item.status === 'DISBURSED'}
                <span
                  class="flex items-center gap-1 rounded-full border border-[#a7f3d0] bg-[#ecfdf5] px-2.5 py-0.5 font-mono text-[10px] font-semibold text-[#059669]"
                >
                  <Check class="size-3" />
                  <span>DICAIRKAN</span>
                </span>
              {:else}
                <span
                  class="rounded-full border border-[#fde68a] bg-[#fffbeb] px-2.5 py-0.5 font-mono text-[10px] font-semibold text-[#d97706]"
                >
                  ESTIMASI
                </span>
              {/if}
            </div>

            <!-- Breakdown 2x2 Grid -->
            <div
              class="grid grid-cols-2 gap-2 rounded-2xl border border-[#ececee] bg-[#f8f8fa] p-3 font-mono text-xs"
            >
              <div>
                <div class="text-[10px] text-[#8e8e93] uppercase">Gaji Pokok</div>
                <div class="mt-0.5 font-bold text-[#17171c]">{formatRupiah(item.base_salary)}</div>
              </div>
              <div>
                <div class="text-[10px] text-[#8e8e93] uppercase">Upah Lembur</div>
                <div class="mt-0.5 font-bold text-[#059669]">
                  +{formatRupiah(item.overtime_pay)}
                </div>
              </div>
              <div>
                <div class="text-[10px] text-[#8e8e93] uppercase">Denda Telat</div>
                <div class="mt-0.5 font-bold text-[#e5484d]">
                  -{formatRupiah(item.late_penalty)}
                </div>
              </div>
              <div>
                <div class="text-[10px] text-[#8e8e93] uppercase">Kasbon</div>
                <div class="mt-0.5 font-bold text-[#e5484d]">
                  -{formatRupiah(item.cash_advance_deduction)}
                </div>
              </div>
            </div>

            <!-- Net Salary Footer -->
            <div class="flex items-center justify-between border-t border-[#f2f2f4] pt-1">
              <span class="text-xs font-medium text-[#8e8e93]">Gaji Bersih Diterima</span>
              <span class="font-mono text-base font-bold text-[#17171c]"
                >{formatRupiah(item.net_salary)}</span
              >
            </div>
          </div>
        {/each}
      {:else}
        <div class="py-12 text-center text-xs text-[#8e8e93]">
          Belum ada data staf untuk periode ini.
        </div>
      {/if}
    </div>

    <!-- Desktop Table View -->
    <div class="hidden overflow-x-auto md:block">
      <table class="w-full border-collapse text-left text-xs">
        <thead>
          <tr
            class="border-b border-[#e5e5ea] bg-[#fafafc] font-mono text-[10.5px] text-[#8e8e93] uppercase"
          >
            <th class="px-5 py-4 font-bold">Nama Karyawan</th>
            <th class="px-5 py-4 text-right font-bold">Gaji Pokok</th>
            <th class="px-5 py-4 text-right font-bold text-[#059669]">Lembur</th>
            <th class="px-5 py-4 text-right font-bold text-[#e5484d]">Denda Telat</th>
            <th class="px-5 py-4 text-right font-bold text-[#e5484d]">Kasbon</th>
            <th class="px-5 py-4 text-right font-bold text-[#17171c]">Gaji Bersih (Net)</th>
            <th class="px-5 py-4 text-center font-bold">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#e5e5ea]">
          {#if payrollItems.length > 0}
            {#each payrollItems as item}
              <tr class="transition-all hover:bg-[#fafafc]">
                <td class="px-5 py-4">
                  <div class="text-xs font-bold text-[#17171c]">{item.name}</div>
                  <div class="font-mono text-[11px] text-[#8e8e93]">{item.role}</div>
                </td>
                <td class="px-5 py-4 text-right font-mono text-[#17171c]"
                  >{formatRupiah(item.base_salary)}</td
                >
                <td class="px-5 py-4 text-right font-mono font-semibold text-[#059669]"
                  >+{formatRupiah(item.overtime_pay)}</td
                >
                <td class="px-5 py-4 text-right font-mono font-semibold text-[#e5484d]"
                  >-{formatRupiah(item.late_penalty)}</td
                >
                <td class="px-5 py-4 text-right font-mono font-semibold text-[#e5484d]"
                  >-{formatRupiah(item.cash_advance_deduction)}</td
                >
                <td class="px-5 py-4 text-right font-mono text-sm font-bold text-[#17171c]"
                  >{formatRupiah(item.net_salary)}</td
                >
                <td class="px-5 py-4 text-center">
                  {#if item.status === 'DISBURSED'}
                    <span
                      class="inline-flex items-center gap-1 rounded-full border border-[#a7f3d0] bg-[#ecfdf5] px-2.5 py-0.5 font-mono text-[10px] font-semibold text-[#059669]"
                    >
                      <Check class="size-3" />
                      <span>DICAIRKAN</span>
                    </span>
                  {:else}
                    <span
                      class="rounded-full border border-[#fde68a] bg-[#fffbeb] px-2.5 py-0.5 font-mono text-[10px] font-semibold text-[#d97706]"
                    >
                      ESTIMASI
                    </span>
                  {/if}
                </td>
              </tr>
            {/each}
          {:else}
            <tr>
              <td colspan="7" class="py-12 text-center text-[#8e8e93]">
                Belum ada data staf untuk periode ini.
              </td>
            </tr>
          {/if}
        </tbody>
      </table>
    </div>
  </div>
</div>
