<script lang="ts">
  import {
    FileText,
    Wallet,
    BarChart3,
    CheckCircle2,
    AlertCircle,
    X,
    QrCode,
    CreditCard,
    Coins,
    TrendingUp,
  } from 'lucide-svelte';
  import type {
    CashAdvance,
    PayrollSlipData,
    PayrollPreviewData,
    User,
    BranchItem,
    TimeframePeriod,
    TimeframeMetricData,
  } from '../../types/app';
  import { inventoryService } from '../../services/inventory-service';
  import { formatRupiah } from '@precis/shared-utils';
  import AdminPayrollView from './finance/AdminPayrollView.svelte';
  import StaffPayrollSlipView from './finance/StaffPayrollSlipView.svelte';
  import RequestKasbonModal from './finance/modals/RequestKasbonModal.svelte';
  import ConfirmDisburseModal from './finance/modals/ConfirmDisburseModal.svelte';

  interface Props {
    currentUser: User;
    workspace?: { workspace_id?: string; workspace_name?: string; id?: string; name?: string } | null;
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
    workspace = null,
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

  // Live analytics state for Audit & Laporan
  let selectedAnalyticsTimeframe = $state<TimeframePeriod>('month');
  let liveAnalytics = $state<TimeframeMetricData | null>(null);
  let isAnalyticsLoading = $state(false);

  let isConfirmDisburseOpen = $state(false);
  let isKasbonModalOpen = $state(false);
  let successNotification = $state<string | null>(null);
  let errorNotification = $state<string | null>(null);

  $effect(() => {
    if (activeSubTab === 'laporan') {
      const period = selectedAnalyticsTimeframe;
      const branch = selectedBranchFilter;
      isAnalyticsLoading = true;
      inventoryService
        .fetchLiveSalesAnalytics(period, branch)
        .then((data) => {
          liveAnalytics = data;
          isAnalyticsLoading = false;
        })
        .catch(() => {
          isAnalyticsLoading = false;
        });
    }
  });

  let canViewPayroll = $derived(
    currentUser.role === 'OWNER' ||
    currentUser.role === 'ADMIN' ||
    Boolean(
      currentUser.permissions?.includes('payroll.view') ||
      currentUser.permissions?.includes('payroll.disburse')
    )
  );

  let canDisbursePayroll = $derived(
    currentUser.role === 'OWNER' ||
    currentUser.role === 'ADMIN' ||
    Boolean(currentUser.permissions?.includes('payroll.disburse'))
  );

  let financeTabs = $derived(
    canViewPayroll
      ? [
          { id: 'payroll' as const, label: 'Payroll Karyawan', icon: FileText },
          { id: 'laporan' as const, label: 'Audit & Opname', icon: BarChart3 },
        ]
      : [
          { id: 'laporan' as const, label: 'Audit & Opname', icon: BarChart3 },
        ]
  );

  $effect(() => {
    if (currentUser.role === 'STAFF') {
      activeSubTab = 'staff_ess';
    } else if (!canViewPayroll) {
      activeSubTab = 'laporan';
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
    successNotification = null;
    errorNotification = null;
    try {
      await onDisbursePayroll(payrollPreview.period_start, payrollPreview.period_end);
      isConfirmDisburseOpen = false;
      successNotification = 'Penggajian periode ini berhasil dicairkan! Status kasbon telah dilunasi dan slip gaji resmi telah diterbitkan.';
    } catch (err: unknown) {
      errorNotification = err instanceof Error ? err.message : 'Gagal memproses pencairan payroll.';
    }
  }
</script>

<div class="space-y-6 font-sans pb-8">
  {#if successNotification}
    <div class="p-4 bg-[#ecfdf5] border border-[#a7f3d0] rounded-2xl text-xs text-[#065f46] flex items-center justify-between gap-3 shadow-2xs animate-in fade-in">
      <div class="flex items-center gap-2.5">
        <CheckCircle2 class="w-4 h-4 text-[#059669] shrink-0" />
        <span class="font-medium">{successNotification}</span>
      </div>
      <button
        type="button"
        onclick={() => (successNotification = null)}
        class="p-1 hover:bg-[#d1fae5] rounded-lg transition-all cursor-pointer text-[#059669]"
      >
        <X class="w-3.5 h-3.5" />
      </button>
    </div>
  {/if}

  {#if errorNotification}
    <div class="p-4 bg-[#fef2f2] border border-[#fecaca] rounded-2xl text-xs text-[#991b1b] flex items-center justify-between gap-3 shadow-2xs animate-in fade-in">
      <div class="flex items-center gap-2.5">
        <AlertCircle class="w-4 h-4 text-[#e5484d] shrink-0" />
        <span class="font-medium">{errorNotification}</span>
      </div>
      <button
        type="button"
        onclick={() => (errorNotification = null)}
        class="p-1 hover:bg-[#fee2e2] rounded-lg transition-all cursor-pointer text-[#e5484d]"
      >
        <X class="w-3.5 h-3.5" />
      </button>
    </div>
  {/if}

  <!-- Segmented Navigation for Admin/Owner/Manager -->
  {#if currentUser.role !== 'STAFF'}
    <div class="flex items-center justify-between gap-4 overflow-x-auto no-scrollbar py-1">
      <div class="inline-flex items-center gap-1.5 p-1.5 bg-white border border-[#e5e5ea] rounded-2xl shadow-2xs">
        {#each financeTabs as tab}
          {@const Icon = tab.icon}
          {@const isActive = activeSubTab === tab.id}
          <button
            type="button"
            onclick={() => (activeSubTab = tab.id)}
            class={`px-4 py-2 rounded-xl text-xs font-medium transition-all duration-200 cursor-pointer flex items-center gap-2 shrink-0 ${
              isActive
                ? 'bg-[#17171c] text-white shadow-xs font-semibold'
                : 'text-[#686873] hover:text-[#17171c] hover:bg-[#f4f4f6]'
            }`}
          >
            <Icon class={`w-4 h-4 ${isActive ? 'text-white' : 'text-[#8e8e93]'}`} />
            <span class="whitespace-nowrap">{tab.label}</span>
          </button>
        {/each}
      </div>
    </div>
  {/if}

  <div class="min-w-0 animate-in fade-in duration-200">
    {#if activeSubTab === 'payroll'}
      <AdminPayrollView
        {branches}
        {payrollPreview}
        {selectedBranchFilter}
        {canDisbursePayroll}
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
      {@const adjustmentLogs = inventoryService.getAdjustmentLogs()}
      {@const damagedLogs = adjustmentLogs.filter((l) => l.reason === 'DAMAGED' || l.reason === 'EXPIRED' || l.reason === 'WASTE')}
      {@const totalWasteCost = damagedLogs.reduce((sum, l) => sum + Math.abs(l.adjusted_amount * 20000), 0)}
      {@const paymentMethods = liveAnalytics?.payment_methods || []}
      {@const totalRevenue = liveAnalytics?.total_revenue || 0}
      {@const totalOrders = liveAnalytics?.total_orders || 0}
      {@const totalDiscount = liveAnalytics?.total_discount || 0}
      {@const aov = liveAnalytics?.average_order_value || 0}

      <div class="space-y-6">
        <!-- Controls & Filters Toolbar -->
        <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs">
          <!-- Timeframe Selector -->
          <div class="flex items-center gap-1 bg-[#f4f4f6] p-1 rounded-full overflow-x-auto no-scrollbar">
            {#each [
              { label: 'Hari', value: 'day' as const },
              { label: 'Pekan', value: 'week' as const },
              { label: 'Bulan', value: 'month' as const },
              { label: 'Tahun', value: 'year' as const },
              { label: 'Semua', value: 'all' as const },
            ] as tf}
              <button
                type="button"
                onclick={() => (selectedAnalyticsTimeframe = tf.value)}
                class={`px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer ${
                  selectedAnalyticsTimeframe === tf.value
                    ? 'bg-[#17171c] text-white shadow-xs'
                    : 'text-[#686873] hover:text-[#17171c]'
                }`}
              >
                {tf.label}
              </button>
            {/each}
          </div>

          <!-- Branch / Workspace Filter Dropdown -->
          {#if branches.length > 0}
            <div class="flex items-center gap-2">
              <span class="text-xs text-[#8e8e93] font-medium hidden sm:inline">Outlet:</span>
              <select
                bind:value={selectedBranchFilter}
                class="px-3.5 py-2 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-xs font-medium text-[#17171c] focus:outline-hidden focus:border-[#17171c] cursor-pointer shadow-2xs transition-all"
              >
                <option value="ALL">{workspace?.workspace_name || workspace?.name || 'Semua Outlet'}</option>
                {#each branches as b}
                  {#if b.name !== (workspace?.workspace_name || workspace?.name)}
                    <option value={b.id}>{b.name}</option>
                  {/if}
                {/each}
              </select>
            </div>
          {/if}
        </div>

        <!-- Summary KPI Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
          <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4 sm:p-5 space-y-1 shadow-2xs">
            <span class="text-[11px] font-mono uppercase text-[#8e8e93] font-semibold">Omzet Bersih</span>
            <div class="text-lg sm:text-xl font-bold font-mono text-[#17171c] tracking-tight">
              {formatRupiah(totalRevenue)}
            </div>
            <div class="text-[10.5px] text-[#059669] font-medium flex items-center gap-1">
              <TrendingUp class="w-3 h-3" />
              <span>{liveAnalytics?.growth_label || 'vs periode lalu'}</span>
            </div>
          </div>

          <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4 sm:p-5 space-y-1 shadow-2xs">
            <span class="text-[11px] font-mono uppercase text-[#8e8e93] font-semibold">Total Transaksi</span>
            <div class="text-lg sm:text-xl font-bold font-mono text-[#17171c] tracking-tight">
              {totalOrders.toLocaleString('id-ID')}
            </div>
            <span class="text-[10.5px] text-[#8e8e93]">Pesanan kasir selesai</span>
          </div>

          <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4 sm:p-5 space-y-1 shadow-2xs">
            <span class="text-[11px] font-mono uppercase text-[#8e8e93] font-semibold">Potongan Diskon</span>
            <div class="text-lg sm:text-xl font-bold font-mono text-[#e5484d] tracking-tight">
              -{formatRupiah(totalDiscount)}
            </div>
            <span class="text-[10.5px] text-[#8e8e93]">Promosi &amp; voucher</span>
          </div>

          <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4 sm:p-5 space-y-1 shadow-2xs">
            <span class="text-[11px] font-mono uppercase text-[#8e8e93] font-semibold">Rata-rata Tiket (AOV)</span>
            <div class="text-lg sm:text-xl font-bold font-mono text-[#17171c] tracking-tight">
              {formatRupiah(aov)}
            </div>
            <span class="text-[10.5px] text-[#8e8e93]">Per transaksi pesanan</span>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
          <!-- Card: Komposisi Metode Pembayaran -->
          <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 space-y-5 shadow-2xs">
            <div class="flex items-start justify-between gap-2">
              <div>
                <h3 class="text-base font-bold text-[#17171c]">Komposisi Metode Pembayaran</h3>
                <p class="text-xs text-[#8e8e93]">Distribusi omzet per saluran bayar kasir</p>
              </div>
              <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-mono font-semibold bg-[#f4f4f6] text-[#17171c]">
                {paymentMethods.length} Saluran
              </span>
            </div>

            {#if isAnalyticsLoading}
              <div class="py-12 flex flex-col items-center justify-center space-y-3">
                <div class="w-6 h-6 border-2 border-[#17171c] border-t-transparent rounded-full animate-spin"></div>
                <span class="text-xs text-[#8e8e93]">Memuat data saluran bayar...</span>
              </div>
            {:else if paymentMethods.length === 0}
              <div class="py-8 text-center text-[#8e8e93] space-y-1">
                <Wallet class="w-6 h-6 mx-auto opacity-40" />
                <p class="text-xs font-semibold text-[#17171c]">Belum ada transaksi pembayaran</p>
                <p class="text-[11px] text-[#8e8e93]">Data akan muncul saat kasir memproses pesanan di periode ini.</p>
              </div>
            {:else}
              <div class="space-y-4">
                {#each paymentMethods as method}
                  {@const isQris = method.method.toLowerCase().includes('qris')}
                  {@const isCash = method.method.toLowerCase().includes('tunai') || method.method.toLowerCase().includes('cash')}
                  {@const isCard = method.method.toLowerCase().includes('kartu') || method.method.toLowerCase().includes('edc') || method.method.toLowerCase().includes('debit')}
                  <div class="space-y-2 p-3.5 bg-[#fbfbfa] border border-[#ececee] rounded-2xl">
                    <div class="flex items-center justify-between text-xs">
                      <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-7 h-7 rounded-lg bg-white border border-[#e5e5ea] flex items-center justify-center shrink-0">
                          {#if isQris}
                            <QrCode class="w-4 h-4 text-[#17171c]" />
                          {:else if isCash}
                            <Coins class="w-4 h-4 text-[#059669]" />
                          {:else if isCard}
                            <CreditCard class="w-4 h-4 text-[#2563eb]" />
                          {:else}
                            <Wallet class="w-4 h-4 text-[#7c3aed]" />
                          {/if}
                        </div>
                        <div class="min-w-0">
                          <div class="font-bold text-[#17171c] truncate">{method.method}</div>
                          <div class="text-[10.5px] font-mono text-[#8e8e93]">{method.count} Transaksi</div>
                        </div>
                      </div>
                      <div class="text-right shrink-0">
                        <div class="font-bold font-mono text-[#17171c]">{formatRupiah(method.amount)}</div>
                        <div class="text-[10.5px] font-mono font-semibold text-[#059669]">{method.percent}%</div>
                      </div>
                    </div>
                    <!-- Visual Progress Bar -->
                    <div class="w-full bg-[#e5e5ea] h-2 rounded-full overflow-hidden">
                      <div
                        class={`h-full rounded-full transition-all duration-500 ${
                          isQris
                            ? 'bg-[#17171c]'
                            : isCash
                            ? 'bg-[#059669]'
                            : isCard
                            ? 'bg-[#2563eb]'
                            : 'bg-[#7c3aed]'
                        }`}
                        style={`width: ${Math.max(method.percent, 2)}%`}
                      ></div>
                    </div>
                  </div>
                {/each}
              </div>
            {/if}
          </div>

          <!-- Card: Audit Selisih Opname & Waste -->
          <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 space-y-5 shadow-2xs">
            <div class="flex items-start justify-between gap-2">
              <div>
                <h3 class="text-base font-bold text-[#17171c]">Audit Selisih Opname &amp; Waste</h3>
                <p class="text-xs text-[#8e8e93]">Rekonsiliasi pemakaian bahan baku bar vs hitung fisik closing</p>
              </div>
              <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-mono font-semibold bg-[#f4f4f6] text-[#17171c]">
                {damagedLogs.length} Insiden Waste
              </span>
            </div>

            <div class="p-4 bg-[#fbfbfa] border border-[#ececee] rounded-2xl flex items-center justify-between">
              <div>
                <span class="text-[10.5px] font-mono uppercase text-[#8e8e93] font-semibold">Estimasi Biaya Waste/Rusak</span>
                <div class="text-xl font-bold font-mono text-[#e5484d] mt-0.5">{formatRupiah(totalWasteCost)}</div>
              </div>
              <span class="px-3 py-1 rounded-full bg-[#fef2f2] text-[#e5484d] text-[11px] font-mono font-semibold border border-[#fecaca]">
                {damagedLogs.length} Insiden
              </span>
            </div>

            <!-- Recent Adjustment Activity -->
            <div class="space-y-2.5">
              <span class="text-[11px] font-mono uppercase text-[#8e8e93] font-semibold">Riwayat Opname Terakhir</span>
              {#if adjustmentLogs.length === 0}
                <div class="py-6 text-center text-[#8e8e93] space-y-1">
                  <FileText class="w-6 h-6 mx-auto opacity-40" />
                  <p class="text-xs font-semibold text-[#17171c]">Belum ada riwayat stock opname</p>
                </div>
              {:else}
                <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                  {#each adjustmentLogs.slice(0, 4) as log}
                    <div class="p-3 bg-[#f8f8fa] border border-[#e5e5ea] rounded-xl flex items-center justify-between text-xs gap-3">
                      <div class="min-w-0">
                        <div class="font-bold text-[#17171c] truncate">{log.material_name}</div>
                        <div class="text-[10.5px] text-[#8e8e93] truncate">{log.notes || 'Penyesuaian stok reguler'} &bull; {log.performed_by}</div>
                      </div>
                      <div class="text-right shrink-0">
                        <span class={`font-mono font-bold ${log.adjusted_amount < 0 ? 'text-[#e5484d]' : 'text-[#059669]'}`}>
                          {log.adjusted_amount > 0 ? `+${log.adjusted_amount}` : log.adjusted_amount}
                        </span>
                        <div class="text-[10px] text-[#8e8e93] font-mono">{log.created_at}</div>
                      </div>
                    </div>
                  {/each}
                </div>
              {/if}
            </div>
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
