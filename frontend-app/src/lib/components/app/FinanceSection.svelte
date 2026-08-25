<script lang="ts">
  import { FileText, Wallet } from 'lucide-svelte';
  import type {
    CashAdvance,
    PayrollSlipData,
    PayrollPreviewData,
    User,
    BranchItem,
  } from '../../types/app';
  import { inventoryService } from '../../services/inventory-service';
  import { formatRupiah } from '../../utils/formatters';
  import AdminPayrollView from './finance/AdminPayrollView.svelte';
  import StaffPayrollSlipView from './finance/StaffPayrollSlipView.svelte';
  import RequestKasbonModal from './finance/modals/RequestKasbonModal.svelte';
  import ConfirmDisburseModal from './finance/modals/ConfirmDisburseModal.svelte';

  interface Props {
    currentUser: User;
    branches?: BranchItem[];
    initialSubTab?: string;
    cashAdvances?: CashAdvance[];
    payrollSlip?: PayrollSlipData | null;
    payrollPreview?: PayrollPreviewData | null;
    onRequestKasbon: (amount: number, purpose?: string) => Promise<void> | void;
    onFilterPayrollPeriod?: (periodStart: string, periodEnd: string) => Promise<void> | void;
    onDisbursePayroll?: (periodStart: string, periodEnd: string) => Promise<void>;
    onExportCsv?: (periodStart: string, periodEnd: string, format: 'BCA' | 'MANDIRI') => Promise<void>;
  }

  let {
    currentUser,
    branches = [],
    initialSubTab = 'payroll',
    cashAdvances = [],
    payrollSlip = null,
    payrollPreview = null,
    onRequestKasbon,
    onFilterPayrollPeriod,
    onDisbursePayroll,
    onExportCsv,
  }: Props = $props();

  let activeSubTab = $state<'payroll' | 'laporan' | 'staff_ess'>('payroll');
  let selectedBranchFilter = $state<string>('ALL');

  let isConfirmDisburseOpen = $state(false);
  let isKasbonModalOpen = $state(false);

  $effect(() => {
    if (currentUser.role === 'STAFF') {
      activeSubTab = 'staff_ess';
    } else if (initialSubTab === 'laporan' || initialSubTab === 'payroll') {
      activeSubTab = initialSubTab;
    }
  });

  let totalPayrollDisbursement = $derived(
    (payrollPreview?.items || [])
      .filter((item) => {
        if (item.role === 'OWNER') return false;
        if (selectedBranchFilter === 'ALL') return true;
        return (
          item.branch_id === selectedBranchFilter ||
          (item.branch_name && item.branch_name.toLowerCase().includes(selectedBranchFilter.toLowerCase()))
        );
      })
      .reduce((sum, item) => sum + item.net_salary, 0)
  );

  async function handleDisburse() {
    if (!onDisbursePayroll || !payrollPreview) return;
    await onDisbursePayroll(payrollPreview.period_start, payrollPreview.period_end);
    isConfirmDisburseOpen = false;
  }
</script>

<div class="space-y-4 sm:space-y-6 font-sans pb-4">
  <!-- Segmented Navigation for Admin/Owner -->
  {#if currentUser.role !== 'STAFF'}
    <div class="bg-white border border-[#d9d9dd] rounded-3xl p-2 sm:p-2.5 flex items-center justify-between gap-2 overflow-x-auto no-scrollbar">
      <div class="flex items-center gap-1.5 w-full sm:w-auto bg-[#eeece7]/40 sm:bg-transparent p-1 sm:p-0 rounded-full">
        <button
          type="button"
          onclick={() => (activeSubTab = 'payroll')}
          class={`text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-2 ${
            activeSubTab === 'payroll'
              ? 'flex-1 sm:flex-initial bg-[#17171c] text-white shadow-xs px-4 py-2 sm:px-5 sm:py-2.5'
              : 'text-[#616161] hover:text-[#212121] hover:bg-white/80 w-9 h-9 sm:w-auto sm:h-auto sm:p-2.5 shrink-0'
          }`}
        >
          <FileText class="w-4 h-4 shrink-0" />
          {#if activeSubTab === 'payroll'}
            <span class="whitespace-nowrap truncate">Payroll Karyawan</span>
          {/if}
        </button>

        <button
          type="button"
          onclick={() => (activeSubTab = 'laporan')}
          class={`text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-2 ${
            activeSubTab === 'laporan'
              ? 'flex-1 sm:flex-initial bg-[#17171c] text-white shadow-xs px-4 py-2 sm:px-5 sm:py-2.5'
              : 'text-[#616161] hover:text-[#212121] hover:bg-white/80 w-9 h-9 sm:w-auto sm:h-auto sm:p-2.5 shrink-0'
          }`}
        >
          <Wallet class="w-4 h-4 shrink-0" />
          {#if activeSubTab === 'laporan'}
            <span class="whitespace-nowrap truncate">Audit &amp; Opname</span>
          {/if}
        </button>
      </div>
    </div>
  {/if}

  {#if activeSubTab === 'payroll'}
    <AdminPayrollView
      {branches}
      {payrollPreview}
      {selectedBranchFilter}
      onSelectBranchFilter={(b) => (selectedBranchFilter = b)}
      onFilterPeriod={async (start, end) => {
        if (onFilterPayrollPeriod) await onFilterPayrollPeriod(start, end);
      }}
      onOpenConfirmDisburse={() => (isConfirmDisburseOpen = true)}
      onExportCsv={async (start, end, format) => {
        if (onExportCsv) await onExportCsv(start, end, format);
      }}
    />
  {:else if activeSubTab === 'laporan'}
    {@const metrics = inventoryService.getTimeframeMetrics('day')}
    {@const adjustmentLogs = inventoryService.getAdjustmentLogs()}
    {@const damagedLogs = adjustmentLogs.filter((l) => l.reason === 'DAMAGED' || l.reason === 'EXPIRED' || l.reason === 'WASTE')}
    {@const totalWasteCost = damagedLogs.reduce((sum, l) => sum + Math.abs(l.adjusted_amount * 20000), 0)}

    <div class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white border border-[#d9d9dd] rounded-3xl p-6 space-y-4">
          <div>
            <h2 class="text-base font-medium text-[#212121]">Komposisi Metode Pembayaran</h2>
            <p class="text-xs text-[#75758a]">Distribusi omzet per saluran bayar kasir</p>
          </div>
          {#if metrics.payment_methods.length === 0}
            <div class="py-8 text-center text-[#93939f] space-y-1">
              <Wallet class="w-6 h-6 mx-auto text-[#93939f] opacity-40" />
              <p class="text-xs font-medium text-[#17171c]">Belum ada transaksi pembayaran</p>
            </div>
          {:else}
            <div class="space-y-3 pt-2">
              {#each metrics.payment_methods as method, i}
                <div class="space-y-1 text-xs">
                  <div class="flex justify-between">
                    <span class="font-medium text-[#212121]">{method.method}</span>
                    <span class="font-mono text-[#17171c]">{formatRupiah(method.amount)} ({method.percent}%)</span>
                  </div>
                  <div class="w-full bg-[#eeece7] h-2 rounded-full overflow-hidden">
                    <div
                      class={`h-full rounded-full ${i === 0 ? 'bg-[#17171c]' : i === 1 ? 'bg-[#00875a]' : 'bg-[#1863dc]'}`}
                      style={`width: ${method.percent}%`}
                    ></div>
                  </div>
                </div>
              {/each}
            </div>
          {/if}
        </div>

        <div class="bg-white border border-[#d9d9dd] rounded-3xl p-6 space-y-4">
          <div>
            <h2 class="text-base font-medium text-[#212121]">Audit Selisih Opname &amp; Waste</h2>
            <p class="text-xs text-[#75758a]">Rekonsiliasi pemakaian bahan baku bar vs hitung fisik closing</p>
          </div>
          {#if adjustmentLogs.length === 0}
            <div class="py-8 text-center text-[#93939f] space-y-1">
              <FileText class="w-6 h-6 mx-auto text-[#93939f] opacity-40" />
              <p class="text-xs font-medium text-[#17171c]">Belum ada riwayat stock opname</p>
            </div>
          {:else}
            <div class="p-4 bg-[#eeece7]/40 border border-[#d9d9dd] rounded-2xl flex items-center justify-between">
              <div>
                <div class="text-[10px] font-mono uppercase text-[#75758a]">Estimasi Biaya Waste/Rusak</div>
                <div class="text-xl font-medium font-mono text-[#e5484d] mt-0.5">{formatRupiah(totalWasteCost)}</div>
              </div>
              <span class="px-2.5 py-1 rounded-full bg-[#ffefef] text-[#e5484d] text-[10px] font-mono font-medium">
                {damagedLogs.length} Insiden Waste
              </span>
            </div>
          {/if}
        </div>
      </div>
    </div>
  {:else if activeSubTab === 'staff_ess'}
    <StaffPayrollSlipView
      {currentUser}
      {cashAdvances}
      {payrollSlip}
      onOpenKasbonModal={() => (isKasbonModalOpen = true)}
    />
  {/if}
</div>

<ConfirmDisburseModal
  isOpen={isConfirmDisburseOpen}
  {totalPayrollDisbursement}
  onClose={() => (isConfirmDisburseOpen = false)}
  onConfirm={handleDisburse}
/>

<RequestKasbonModal
  isOpen={isKasbonModalOpen}
  onClose={() => (isKasbonModalOpen = false)}
  onSubmit={async (amount, purpose) => {
    await onRequestKasbon(amount, purpose);
  }}
/>
