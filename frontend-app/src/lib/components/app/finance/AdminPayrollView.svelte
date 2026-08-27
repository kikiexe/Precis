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

  let totalBaseSalary = $derived(
    payrollItems.reduce((sum, item) => sum + item.base_salary, 0)
  );

  let totalLatePenalty = $derived(
    payrollItems.reduce((sum, item) => sum + item.late_penalty, 0)
  );

  let totalOvertimePay = $derived(
    payrollItems.reduce((sum, item) => sum + item.overtime_pay, 0)
  );

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
    <div class="p-4 bg-[#eff6ff] border border-[#bfdbfe] rounded-2xl text-xs font-semibold text-[#1d4ed8] flex items-center justify-between shadow-2xs">
      <span>{actionMessage}</span>
      <button type="button" onclick={() => (actionMessage = null)} class="text-[#8e8e93] hover:text-[#17171c] cursor-pointer">&times;</button>
    </div>
  {/if}

  <!-- Unified Payroll Control Toolbar -->
  <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 space-y-4 shadow-2xs">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <!-- Date Range & Refresh -->
      <div class="flex items-center gap-2.5 flex-1 max-w-lg">
        <div class="flex items-center gap-2 font-mono text-xs bg-[#f8f8fa] border border-[#e5e5ea] rounded-2xl px-4 py-2.5 flex-1 min-w-0 shadow-2xs">
          <Calendar class="w-4 h-4 text-[#8e8e93] shrink-0" />
          <input
            id="filter-period-start"
            type="date"
            bind:value={filterPeriodStart}
            class="bg-transparent text-xs text-[#17171c] focus:outline-hidden cursor-pointer w-full min-w-0"
          />
          <span class="text-[#8e8e93] font-sans px-1">s/d</span>
          <input
            id="filter-period-end"
            type="date"
            bind:value={filterPeriodEnd}
            class="bg-transparent text-xs text-[#17171c] focus:outline-hidden cursor-pointer w-full min-w-0"
          />
        </div>

        <button
          type="button"
          onclick={handleFilter}
          disabled={isFiltering}
          class="px-4 py-2.5 bg-[#17171c] hover:bg-black text-white rounded-2xl text-xs font-semibold transition-all cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2 shrink-0 shadow-xs"
          title="Hitung ulang kalkulasi penggajian"
        >
          <RefreshCw class={`w-3.5 h-3.5 ${isFiltering ? 'animate-spin' : ''}`} />
          <span class="hidden sm:inline">{isFiltering ? 'Memuat...' : 'Hitung Ulang'}</span>
        </button>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        {#if canDisbursePayroll}
          <button
            type="button"
            onclick={handleExport}
            disabled={isExporting || !payrollPreview}
            class="py-2.5 px-4 border border-[#e5e5ea] bg-white hover:bg-[#f8f8fa] text-[#17171c] rounded-2xl text-xs font-semibold transition-all cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2 shadow-2xs flex-1 sm:flex-none"
          >
            <Download class="w-4 h-4 text-[#8e8e93] shrink-0" />
            <span>{isExporting ? 'Mengunduh...' : 'Export CSV (BCA)'}</span>
          </button>

          <button
            type="button"
            onclick={onOpenConfirmDisburse}
            disabled={!payrollPreview || isAllDisbursed}
            class="py-2.5 px-5 {isAllDisbursed ? 'bg-[#ecfdf5] text-[#059669] border border-[#a7f3d0] cursor-default' : 'bg-[#059669] hover:bg-[#047857] text-white cursor-pointer'} rounded-2xl text-xs font-semibold transition-all disabled:opacity-50 flex items-center justify-center gap-2 shadow-xs flex-1 sm:flex-none"
          >
            <Check class="w-4 h-4 shrink-0" />
            <span>{isAllDisbursed ? 'Sudah Dicairkan' : 'Cairkan Payroll'}</span>
          </button>
        {:else}
          <div class="py-2 px-3.5 bg-[#f8f8fa] border border-[#e5e5ea] rounded-2xl text-[11px] font-medium text-[#8e8e93] flex items-center gap-2">
            <span>Pratinjau Rekap (Pencairan Khusus Owner / Manager Payroll)</span>
          </div>
        {/if}
      </div>
    </div>
  </div>

  <!-- Financial KPI Metrics Grid -->
  <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-3.5">
    <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4 sm:p-5 flex flex-col justify-between shadow-2xs">
      <span class="text-[11px] font-medium uppercase tracking-wider text-[#8e8e93] truncate">Gaji Pokok Total</span>
      <div class="text-sm sm:text-base font-bold font-mono text-[#17171c] mt-1 truncate">{formatRupiah(totalBaseSalary)}</div>
    </div>
    <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4 sm:p-5 flex flex-col justify-between shadow-2xs">
      <span class="text-[11px] font-medium uppercase tracking-wider text-[#059669] truncate">Upah Lembur</span>
      <div class="text-sm sm:text-base font-bold font-mono text-[#059669] mt-1 truncate">+{formatRupiah(totalOvertimePay)}</div>
    </div>
    <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4 sm:p-5 flex flex-col justify-between shadow-2xs">
      <span class="text-[11px] font-medium uppercase tracking-wider text-[#e5484d] truncate">Denda Telat</span>
      <div class="text-sm sm:text-base font-bold font-mono text-[#e5484d] mt-1 truncate">-{formatRupiah(totalLatePenalty)}</div>
    </div>
    <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4 sm:p-5 flex flex-col justify-between shadow-2xs">
      <span class="text-[11px] font-medium uppercase tracking-wider text-[#e5484d] truncate">Potongan Kasbon</span>
      <div class="text-sm sm:text-base font-bold font-mono text-[#e5484d] mt-1 truncate">-{formatRupiah(totalCashAdvanceDeduction)}</div>
    </div>
    <div class="col-span-2 sm:col-span-4 lg:col-span-1 bg-[#17171c] text-white rounded-2xl p-4 sm:p-5 flex flex-col justify-between shadow-xs">
      <span class="text-[11px] font-medium uppercase tracking-wider text-white/70 truncate">Total Pencairan Bersih</span>
      <div class="text-base sm:text-lg font-bold font-mono text-white mt-1 truncate">{formatRupiah(totalPayrollDisbursement)}</div>
    </div>
  </div>

  <!-- Payroll Breakdown Table -->
  <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl overflow-hidden shadow-2xs">
    <div class="p-5 border-b border-[#e5e5ea] flex items-center justify-between gap-3">
      <div>
        <h3 class="text-base font-bold text-[#17171c]">Rincian Gaji Bersih per Karyawan</h3>
        <p class="text-xs text-[#8e8e93] mt-0.5">Kalkulasi presensi, absensi, denda terlambat, dan potongan kasbon</p>
      </div>
      <span class="text-xs font-mono font-semibold text-[#686873] bg-[#f4f4f6] px-3 py-1 rounded-full">
        {payrollItems.length} Karyawan
      </span>
    </div>

    <!-- Mobile Card View -->
    <div class="block md:hidden divide-y divide-[#f2f2f4]">
      {#if payrollItems.length > 0}
        {#each payrollItems as item}
          <div class="p-5 space-y-3.5">
            <div class="flex items-start justify-between gap-2">
              <div>
                <h4 class="font-bold text-sm text-[#17171c]">{item.name}</h4>
                <p class="text-xs text-[#8e8e93] font-mono mt-0.5">{item.role}</p>
              </div>
              {#if item.status === 'DISBURSED'}
                <span class="text-[10px] font-mono font-semibold px-2.5 py-0.5 rounded-full bg-[#ecfdf5] text-[#059669] border border-[#a7f3d0] flex items-center gap-1">
                  <Check class="w-3 h-3" />
                  <span>DICAIRKAN</span>
                </span>
              {:else}
                <span class="text-[10px] font-mono font-semibold px-2.5 py-0.5 rounded-full bg-[#fffbeb] text-[#d97706] border border-[#fde68a]">
                  ESTIMASI
                </span>
              {/if}
            </div>

            <!-- Breakdown 2x2 Grid -->
            <div class="grid grid-cols-2 gap-2 bg-[#f8f8fa] border border-[#ececee] rounded-2xl p-3 text-xs font-mono">
              <div>
                <div class="text-[10px] text-[#8e8e93] uppercase">Gaji Pokok</div>
                <div class="font-bold text-[#17171c] mt-0.5">{formatRupiah(item.base_salary)}</div>
              </div>
              <div>
                <div class="text-[10px] text-[#8e8e93] uppercase">Upah Lembur</div>
                <div class="font-bold text-[#059669] mt-0.5">+{formatRupiah(item.overtime_pay)}</div>
              </div>
              <div>
                <div class="text-[10px] text-[#8e8e93] uppercase">Denda Telat</div>
                <div class="font-bold text-[#e5484d] mt-0.5">-{formatRupiah(item.late_penalty)}</div>
              </div>
              <div>
                <div class="text-[10px] text-[#8e8e93] uppercase">Kasbon</div>
                <div class="font-bold text-[#e5484d] mt-0.5">-{formatRupiah(item.cash_advance_deduction)}</div>
              </div>
            </div>

            <!-- Net Salary Footer -->
            <div class="flex items-center justify-between pt-1 border-t border-[#f2f2f4]">
              <span class="text-xs font-medium text-[#8e8e93]">Gaji Bersih Diterima</span>
              <span class="text-base font-bold font-mono text-[#17171c]">{formatRupiah(item.net_salary)}</span>
            </div>
          </div>
        {/each}
      {:else}
        <div class="py-12 text-center text-[#8e8e93] text-xs">
          Belum ada data staf untuk periode ini.
        </div>
      {/if}
    </div>

    <!-- Desktop Table View -->
    <div class="hidden md:block overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-[#fafafc] border-b border-[#e5e5ea] text-[#8e8e93] font-mono uppercase text-[10.5px]">
            <th class="py-4 px-5 font-bold">Nama Karyawan</th>
            <th class="py-4 px-5 text-right font-bold">Gaji Pokok</th>
            <th class="py-4 px-5 text-right font-bold text-[#059669]">Lembur</th>
            <th class="py-4 px-5 text-right font-bold text-[#e5484d]">Denda Telat</th>
            <th class="py-4 px-5 text-right font-bold text-[#e5484d]">Kasbon</th>
            <th class="py-4 px-5 text-right font-bold text-[#17171c]">Gaji Bersih (Net)</th>
            <th class="py-4 px-5 text-center font-bold">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#e5e5ea]">
          {#if payrollItems.length > 0}
            {#each payrollItems as item}
              <tr class="hover:bg-[#fafafc] transition-all">
                <td class="py-4 px-5">
                  <div class="font-bold text-xs text-[#17171c]">{item.name}</div>
                  <div class="text-[11px] text-[#8e8e93] font-mono">{item.role}</div>
                </td>
                <td class="py-4 px-5 text-right font-mono text-[#17171c]">{formatRupiah(item.base_salary)}</td>
                <td class="py-4 px-5 text-right font-mono font-semibold text-[#059669]">+{formatRupiah(item.overtime_pay)}</td>
                <td class="py-4 px-5 text-right font-mono font-semibold text-[#e5484d]">-{formatRupiah(item.late_penalty)}</td>
                <td class="py-4 px-5 text-right font-mono font-semibold text-[#e5484d]">-{formatRupiah(item.cash_advance_deduction)}</td>
                <td class="py-4 px-5 text-right font-mono font-bold text-sm text-[#17171c]">{formatRupiah(item.net_salary)}</td>
                <td class="py-4 px-5 text-center">
                  {#if item.status === 'DISBURSED'}
                    <span class="text-[10px] font-mono font-semibold px-2.5 py-0.5 rounded-full bg-[#ecfdf5] text-[#059669] border border-[#a7f3d0] inline-flex items-center gap-1">
                      <Check class="w-3 h-3" />
                      <span>DICAIRKAN</span>
                    </span>
                  {:else}
                    <span class="text-[10px] font-mono font-semibold px-2.5 py-0.5 rounded-full bg-[#fffbeb] text-[#d97706] border border-[#fde68a]">
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
