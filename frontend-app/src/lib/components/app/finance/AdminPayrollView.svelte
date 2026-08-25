<script lang="ts">
  import { ChevronDown, Download, Check } from 'lucide-svelte';
  import type { BranchItem, PayrollPreviewData } from '../../../types/app';
  import { formatRupiah } from '../../../utils/formatters';

  interface Props {
    branches: BranchItem[];
    payrollPreview: PayrollPreviewData | null;
    selectedBranchFilter: string;
    onSelectBranchFilter: (branch: string) => void;
    onFilterPeriod: (start: string, end: string) => Promise<void>;
    onOpenConfirmDisburse: () => void;
    onExportCsv: (start: string, end: string, format: 'BCA' | 'MANDIRI') => Promise<void>;
  }

  let {
    branches = [],
    payrollPreview = null,
    selectedBranchFilter = 'ALL',
    onSelectBranchFilter,
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
  let selectedBankFormat = $state<'BCA' | 'MANDIRI'>('BCA');
  let isFiltering = $state(false);
  let isExporting = $state(false);
  let actionMessage = $state<string | null>(null);

  $effect(() => {
    if (payrollPreview?.period_start) filterPeriodStart = payrollPreview.period_start;
    if (payrollPreview?.period_end) filterPeriodEnd = payrollPreview.period_end;
  });

  let filteredPayrollItems = $derived(
    (payrollPreview?.items || []).filter((item) => {
      if (item.role === 'OWNER') return false;
      if (selectedBranchFilter === 'ALL') return true;
      return (
        item.branch_id === selectedBranchFilter ||
        (item.branch_name && item.branch_name.toLowerCase().includes(selectedBranchFilter.toLowerCase()))
      );
    })
  );

  let totalBaseSalary = $derived(
    filteredPayrollItems.reduce((sum, item) => sum + item.base_salary, 0)
  );

  let totalLatePenalty = $derived(
    filteredPayrollItems.reduce((sum, item) => sum + item.late_penalty, 0)
  );

  let totalOvertimePay = $derived(
    filteredPayrollItems.reduce((sum, item) => sum + item.overtime_pay, 0)
  );

  let totalCashAdvanceDeduction = $derived(
    filteredPayrollItems.reduce((sum, item) => sum + item.cash_advance_deduction, 0)
  );

  let totalPayrollDisbursement = $derived(
    filteredPayrollItems.reduce((sum, item) => sum + item.net_salary, 0)
  );

  async function handleFilter() {
    isFiltering = true;
    actionMessage = null;
    try {
      await onFilterPeriod(filterPeriodStart, filterPeriodEnd);
      actionMessage = `Pratinjau payroll periode ${filterPeriodStart} s/d ${filterPeriodEnd} berhasil dimuat.`;
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
      await onExportCsv(filterPeriodStart, filterPeriodEnd, selectedBankFormat);
      actionMessage = `File CSV transfer bank ${selectedBankFormat} berhasil diexport.`;
    } catch (e: unknown) {
      actionMessage = e instanceof Error ? e.message : 'Gagal mengunduh CSV payroll.';
    } finally {
      isExporting = false;
    }
  }
</script>

<div class="space-y-6 font-sans">
  {#if actionMessage}
    <div class="p-4 bg-[#f1f5ff] border border-[#d9d9dd] rounded-2xl text-xs font-medium text-[#1863dc] flex items-center justify-between">
      <span>{actionMessage}</span>
      <button type="button" onclick={() => (actionMessage = null)} class="text-[#75758a] hover:text-[#212121] cursor-pointer">&times;</button>
    </div>
  {/if}

  <!-- Period & Branch Filter + Export Bar -->
  <div class="bg-white border border-[#d9d9dd] rounded-2xl p-3.5 sm:p-4 flex flex-col lg:flex-row lg:items-center justify-between gap-3 text-xs">
    <div class="flex flex-wrap items-center gap-2.5">
      {#if branches.length > 0}
        <div class="relative">
          <select
            value={selectedBranchFilter}
            onchange={(e) => onSelectBranchFilter(e.currentTarget.value)}
            class="appearance-none px-3 pr-7 py-1.5 bg-[#eeece7]/50 hover:bg-[#eeece7] border border-[#d9d9dd] rounded-full text-xs text-[#212121] font-mono focus:outline-hidden cursor-pointer transition-all shadow-2xs"
          >
            <option value="ALL">Semua Cabang (Konsolidasi)</option>
            {#each branches as b}
              <option value={b.id}>{b.name}</option>
            {/each}
          </select>
          <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
        </div>
      {/if}

      <div class="flex items-center gap-1.5 font-mono text-[11px]">
        <input
          id="filter-period-start"
          type="date"
          bind:value={filterPeriodStart}
          class="bg-[#eeece7]/40 border border-[#d9d9dd] rounded-full px-2.5 py-1 text-xs text-[#212121] focus:outline-hidden"
        />
        <span class="text-[#75758a]">s/d</span>
        <input
          id="filter-period-end"
          type="date"
          bind:value={filterPeriodEnd}
          class="bg-[#eeece7]/40 border border-[#d9d9dd] rounded-full px-2.5 py-1 text-xs text-[#212121] focus:outline-hidden"
        />
      </div>

      <button
        type="button"
        onclick={handleFilter}
        disabled={isFiltering}
        class="px-3 py-1.5 bg-[#eeece7] hover:bg-[#d9d9dd] text-[#212121] rounded-full text-xs font-medium transition-all cursor-pointer disabled:opacity-50"
      >
        {isFiltering ? 'Memuat...' : 'Hitung Ulang'}
      </button>
    </div>

    <!-- Disburse & Export Buttons -->
    <div class="flex items-center gap-2 pt-2 lg:pt-0 border-t lg:border-t-0 border-[#d9d9dd]">
      <div class="relative">
        <select
          bind:value={selectedBankFormat}
          class="appearance-none px-3 pr-7 py-1.5 bg-[#eeece7]/50 hover:bg-[#eeece7] border border-[#d9d9dd] rounded-full text-xs text-[#212121] font-mono focus:outline-hidden cursor-pointer transition-all shadow-2xs"
        >
          <option value="BCA">BCA CSV</option>
          <option value="MANDIRI">Mandiri CSV</option>
        </select>
        <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
      </div>

      <button
        type="button"
        onclick={handleExport}
        disabled={isExporting || !payrollPreview}
        class="px-3.5 py-1.5 border border-[#d9d9dd] bg-white hover:bg-[#eeece7] text-[#212121] rounded-full text-xs font-medium transition-all cursor-pointer disabled:opacity-50 flex items-center gap-1"
      >
        <Download class="w-3.5 h-3.5" />
        <span>Export</span>
      </button>

      <button
        type="button"
        onclick={onOpenConfirmDisburse}
        disabled={!payrollPreview}
        class="px-4 py-1.5 bg-[#17171c] hover:bg-black text-white rounded-full text-xs font-medium transition-all cursor-pointer disabled:opacity-50 flex items-center gap-1.5 shadow-xs"
      >
        <Check class="w-3.5 h-3.5" />
        <span>Cairkan Payroll</span>
      </button>
    </div>
  </div>

  <!-- Financial Totals Compact High-Density KPI Strip -->
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5">
    <div class="bg-white border border-[#d9d9dd] rounded-xl p-3">
      <div class="text-[9px] sm:text-[10px] font-mono uppercase text-[#75758a] truncate">Gaji Pokok Total</div>
      <div class="text-sm sm:text-base font-medium font-mono text-[#212121] mt-0.5 truncate">{formatRupiah(totalBaseSalary)}</div>
    </div>
    <div class="bg-white border border-[#d9d9dd] rounded-xl p-3">
      <div class="text-[9px] sm:text-[10px] font-mono uppercase text-[#75758a] truncate">Upah Lembur</div>
      <div class="text-sm sm:text-base font-medium font-mono text-[#003c33] mt-0.5 truncate">+{formatRupiah(totalOvertimePay)}</div>
    </div>
    <div class="bg-white border border-[#d9d9dd] rounded-xl p-3">
      <div class="text-[9px] sm:text-[10px] font-mono uppercase text-[#75758a] truncate">Denda Telat</div>
      <div class="text-sm sm:text-base font-medium font-mono text-[#e5484d] mt-0.5 truncate">-{formatRupiah(totalLatePenalty)}</div>
    </div>
    <div class="bg-white border border-[#d9d9dd] rounded-xl p-3">
      <div class="text-[9px] sm:text-[10px] font-mono uppercase text-[#75758a] truncate">Potongan Kasbon</div>
      <div class="text-sm sm:text-base font-medium font-mono text-[#e5484d] mt-0.5 truncate">-{formatRupiah(totalCashAdvanceDeduction)}</div>
    </div>
    <div class="col-span-2 sm:col-span-1 bg-[#17171c] text-white rounded-xl p-3">
      <div class="text-[9px] sm:text-[10px] font-mono uppercase text-white/70 truncate">Pencairan Bersih (Net)</div>
      <div class="text-sm sm:text-base font-medium font-mono text-white mt-0.5 truncate">{formatRupiah(totalPayrollDisbursement)}</div>
    </div>
  </div>

  <!-- Payroll Items Table -->
  <div class="bg-white border border-[#d9d9dd] rounded-2xl overflow-hidden">
    <div class="p-4 sm:p-5 border-b border-[#d9d9dd] flex items-center justify-between">
      <div>
        <h2 class="text-sm font-medium text-[#212121]">Rincian Gaji Bersih per Karyawan</h2>
        <p class="text-xs text-[#75758a]">Kalkulasi otomatis presensi selfie, keterlambatan, dan kasbon</p>
      </div>
      <span class="text-[10px] font-mono text-[#75758a]">{filteredPayrollItems.length} Karyawan</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-[#eeece7]/40 border-b border-[#d9d9dd] text-[#75758a] font-mono uppercase text-[10px]">
            <th class="py-3 px-4">Nama Karyawan</th>
            <th class="py-3 px-4">Cabang</th>
            <th class="py-3 px-4 text-right">Gaji Pokok</th>
            <th class="py-3 px-4 text-right">Lembur</th>
            <th class="py-3 px-4 text-right">Denda Telat</th>
            <th class="py-3 px-4 text-right">Kasbon</th>
            <th class="py-3 px-4 text-right font-medium text-[#17171c]">Gaji Bersih (Net)</th>
            <th class="py-3 px-4 text-center">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#d9d9dd]">
          {#if filteredPayrollItems.length > 0}
            {#each filteredPayrollItems as item}
              <tr class="hover:bg-[#eeece7]/20 transition-all">
                <td class="py-3.5 px-4">
                  <div class="font-medium text-[#212121]">{item.name}</div>
                  <div class="text-[10px] text-[#75758a] font-mono">{item.role}</div>
                </td>
                <td class="py-3.5 px-4 text-[#616161]">{item.branch_name || 'Cabang Utama'}</td>
                <td class="py-3.5 px-4 text-right font-mono text-[#212121]">{formatRupiah(item.base_salary)}</td>
                <td class="py-3.5 px-4 text-right font-mono text-[#003c33]">+{formatRupiah(item.overtime_pay)}</td>
                <td class="py-3.5 px-4 text-right font-mono text-[#e5484d]">-{formatRupiah(item.late_penalty)}</td>
                <td class="py-3.5 px-4 text-right font-mono text-[#e5484d]">-{formatRupiah(item.cash_advance_deduction)}</td>
                <td class="py-3.5 px-4 text-right font-mono font-medium text-[#17171c]">{formatRupiah(item.net_salary)}</td>
                <td class="py-3.5 px-4 text-center">
                  <span class="text-[9px] font-mono font-medium px-2 py-0.5 rounded-full bg-[#edfce9] text-[#003c33] border border-[#bbf7d0]">
                    ESTIMATED
                  </span>
                </td>
              </tr>
            {/each}
          {:else}
            <tr>
              <td colspan="8" class="py-10 text-center text-[#75758a]">
                Belum ada data staf untuk cabang / periode ini.
              </td>
            </tr>
          {/if}
        </tbody>
      </table>
    </div>
  </div>
</div>
