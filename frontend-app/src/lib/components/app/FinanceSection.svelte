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
    workspace?: {
      workspace_id?: string;
      workspace_name?: string;
      id?: string;
      name?: string;
    } | null;
    branches?: BranchItem[];
    initialSubTab?: string;
    cashAdvances?: CashAdvance[];
    payrollSlip?: PayrollSlipData | null;
    payrollPreview?: PayrollPreviewData | null;
    onRequestKasbon: (amount: number, purpose?: string) => Promise<void> | void;
    onFilterPayrollPeriod?: (periodStart: string, periodEnd: string) => Promise<void> | void;
    onDisbursePayroll?: (periodStart: string, periodEnd: string) => Promise<void>;
    onExportCsv?: (
      periodStart: string,
      periodEnd: string,
      format: 'BCA' | 'MANDIRI'
    ) => Promise<void>;
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
      : [{ id: 'laporan' as const, label: 'Audit & Opname', icon: BarChart3 }]
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
          (item.branch_name &&
            item.branch_name.toLowerCase().includes(selectedBranchFilter.toLowerCase()))
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
      successNotification =
        'Penggajian periode ini berhasil dicairkan! Status kasbon telah dilunasi dan slip gaji resmi telah diterbitkan.';
    } catch (err: unknown) {
      errorNotification = err instanceof Error ? err.message : 'Gagal memproses pencairan payroll.';
    }
  }
</script>

<div class="space-y-6 pb-8 font-sans">
  {#if successNotification}
    <div
      class="animate-in fade-in flex items-center justify-between gap-3 rounded-2xl border border-[#a7f3d0] bg-[#ecfdf5] p-4 text-xs text-[#065f46] shadow-2xs"
    >
      <div class="flex items-center gap-2.5">
        <CheckCircle2 class="size-4 shrink-0 text-[#059669]" />
        <span class="font-medium">{successNotification}</span>
      </div>
      <button
        type="button"
        onclick={() => (successNotification = null)}
        class="cursor-pointer rounded-lg p-1 text-[#059669] transition-all hover:bg-[#d1fae5]"
      >
        <X class="size-3.5" />
      </button>
    </div>
  {/if}

  {#if errorNotification}
    <div
      class="animate-in fade-in flex items-center justify-between gap-3 rounded-2xl border border-[#fecaca] bg-[#fef2f2] p-4 text-xs text-[#991b1b] shadow-2xs"
    >
      <div class="flex items-center gap-2.5">
        <AlertCircle class="size-4 shrink-0 text-[#e5484d]" />
        <span class="font-medium">{errorNotification}</span>
      </div>
      <button
        type="button"
        onclick={() => (errorNotification = null)}
        class="cursor-pointer rounded-lg p-1 text-[#e5484d] transition-all hover:bg-[#fee2e2]"
      >
        <X class="size-3.5" />
      </button>
    </div>
  {/if}

  <!-- Segmented Navigation for Admin/Owner/Manager -->
  {#if currentUser.role !== 'STAFF'}
    <div class="no-scrollbar flex items-center justify-between gap-4 overflow-x-auto py-1">
      <div
        class="inline-flex items-center gap-1.5 rounded-2xl border border-[#e5e5ea] bg-white p-1.5 shadow-2xs"
      >
        {#each financeTabs as tab}
          {@const Icon = tab.icon}
          {@const isActive = activeSubTab === tab.id}
          <button
            type="button"
            onclick={() => (activeSubTab = tab.id)}
            class={`flex shrink-0 cursor-pointer items-center gap-2 rounded-xl px-4 py-2 text-xs font-medium transition-all duration-200 ${
              isActive
                ? 'bg-[#17171c] font-semibold text-white shadow-xs'
                : 'text-[#686873] hover:bg-[#f4f4f6] hover:text-[#17171c]'
            }`}
          >
            <Icon class={`size-4 ${isActive ? 'text-white' : 'text-[#8e8e93]'}`} />
            <span class="whitespace-nowrap">{tab.label}</span>
          </button>
        {/each}
      </div>
    </div>
  {/if}

  <div class="animate-in fade-in min-w-0 duration-200">
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
      {@const damagedLogs = adjustmentLogs.filter(
        (l) => l.reason === 'DAMAGED' || l.reason === 'EXPIRED' || l.reason === 'WASTE'
      )}
      {@const totalWasteCost = damagedLogs.reduce(
        (sum, l) => sum + Math.abs(l.adjusted_amount * 20000),
        0
      )}
      {@const paymentMethods = liveAnalytics?.payment_methods || []}
      {@const totalRevenue = liveAnalytics?.total_revenue || 0}
      {@const totalOrders = liveAnalytics?.total_orders || 0}
      {@const totalDiscount = liveAnalytics?.total_discount || 0}
      {@const aov = liveAnalytics?.average_order_value || 0}

      <div class="space-y-6">
        <!-- Controls & Filters Toolbar -->
        <div
          class="flex flex-col justify-between gap-4 rounded-2xl border border-[#e5e5ea] bg-white p-4 shadow-2xs sm:flex-row sm:items-center sm:rounded-3xl sm:p-5"
        >
          <!-- Timeframe Selector -->
          <div
            class="no-scrollbar flex items-center gap-1 overflow-x-auto rounded-full bg-[#f4f4f6] p-1"
          >
            {#each [{ label: 'Hari', value: 'day' as const }, { label: 'Pekan', value: 'week' as const }, { label: 'Bulan', value: 'month' as const }, { label: 'Tahun', value: 'year' as const }, { label: 'Semua', value: 'all' as const }] as tf}
              <button
                type="button"
                onclick={() => (selectedAnalyticsTimeframe = tf.value)}
                class={`cursor-pointer rounded-full px-3.5 py-1.5 text-xs font-semibold transition-all ${
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
              <span class="hidden text-xs font-medium text-[#8e8e93] sm:inline">Outlet:</span>
              <select
                bind:value={selectedBranchFilter}
                class="cursor-pointer rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-3.5 py-2 text-xs font-medium text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
              >
                <option value="ALL"
                  >{workspace?.workspace_name || workspace?.name || 'Semua Outlet'}</option
                >
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
        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
          <div class="space-y-1 rounded-2xl border border-[#e5e5ea] bg-white p-4 shadow-2xs sm:p-5">
            <span class="font-mono text-[11px] font-semibold text-[#8e8e93] uppercase"
              >Omzet Bersih</span
            >
            <div class="font-mono text-lg font-bold tracking-tight text-[#17171c] sm:text-xl">
              {formatRupiah(totalRevenue)}
            </div>
            <div class="flex items-center gap-1 text-[10.5px] font-medium text-[#059669]">
              <TrendingUp class="size-3" />
              <span>{liveAnalytics?.growth_label || 'vs periode lalu'}</span>
            </div>
          </div>

          <div class="space-y-1 rounded-2xl border border-[#e5e5ea] bg-white p-4 shadow-2xs sm:p-5">
            <span class="font-mono text-[11px] font-semibold text-[#8e8e93] uppercase"
              >Total Transaksi</span
            >
            <div class="font-mono text-lg font-bold tracking-tight text-[#17171c] sm:text-xl">
              {totalOrders.toLocaleString('id-ID')}
            </div>
            <span class="text-[10.5px] text-[#8e8e93]">Pesanan kasir selesai</span>
          </div>

          <div class="space-y-1 rounded-2xl border border-[#e5e5ea] bg-white p-4 shadow-2xs sm:p-5">
            <span class="font-mono text-[11px] font-semibold text-[#8e8e93] uppercase"
              >Potongan Diskon</span
            >
            <div class="font-mono text-lg font-bold tracking-tight text-[#e5484d] sm:text-xl">
              -{formatRupiah(totalDiscount)}
            </div>
            <span class="text-[10.5px] text-[#8e8e93]">Promosi &amp; voucher</span>
          </div>

          <div class="space-y-1 rounded-2xl border border-[#e5e5ea] bg-white p-4 shadow-2xs sm:p-5">
            <span class="font-mono text-[11px] font-semibold text-[#8e8e93] uppercase"
              >Rata-rata Tiket (AOV)</span
            >
            <div class="font-mono text-lg font-bold tracking-tight text-[#17171c] sm:text-xl">
              {formatRupiah(aov)}
            </div>
            <span class="text-[10.5px] text-[#8e8e93]">Per transaksi pesanan</span>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-2">
          <!-- Card: Komposisi Metode Pembayaran -->
          <div
            class="space-y-5 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs sm:rounded-3xl sm:p-6"
          >
            <div class="flex items-start justify-between gap-2">
              <div>
                <h3 class="text-base font-bold text-[#17171c]">Komposisi Metode Pembayaran</h3>
                <p class="text-xs text-[#8e8e93]">Distribusi omzet per saluran bayar kasir</p>
              </div>
              <span
                class="rounded-full bg-[#f4f4f6] px-2.5 py-0.5 font-mono text-[10.5px] font-semibold text-[#17171c]"
              >
                {paymentMethods.length} Saluran
              </span>
            </div>

            {#if isAnalyticsLoading}
              <div class="flex flex-col items-center justify-center space-y-3 py-12">
                <div
                  class="size-6 animate-spin rounded-full border-2 border-[#17171c] border-t-transparent"
                ></div>
                <span class="text-xs text-[#8e8e93]">Memuat data saluran bayar...</span>
              </div>
            {:else if paymentMethods.length === 0}
              <div class="space-y-1 py-8 text-center text-[#8e8e93]">
                <Wallet class="mx-auto size-6 opacity-40" />
                <p class="text-xs font-semibold text-[#17171c]">Belum ada transaksi pembayaran</p>
                <p class="text-[11px] text-[#8e8e93]">
                  Data akan muncul saat kasir memproses pesanan di periode ini.
                </p>
              </div>
            {:else}
              <div class="space-y-4">
                {#each paymentMethods as method}
                  {@const isQris = method.method.toLowerCase().includes('qris')}
                  {@const isCash =
                    method.method.toLowerCase().includes('tunai') ||
                    method.method.toLowerCase().includes('cash')}
                  {@const isCard =
                    method.method.toLowerCase().includes('kartu') ||
                    method.method.toLowerCase().includes('edc') ||
                    method.method.toLowerCase().includes('debit')}
                  <div class="space-y-2 rounded-2xl border border-[#ececee] bg-[#fbfbfa] p-3.5">
                    <div class="flex items-center justify-between text-xs">
                      <div class="flex min-w-0 items-center gap-2.5">
                        <div
                          class="flex size-7 shrink-0 items-center justify-center rounded-lg border border-[#e5e5ea] bg-white"
                        >
                          {#if isQris}
                            <QrCode class="size-4 text-[#17171c]" />
                          {:else if isCash}
                            <Coins class="size-4 text-[#059669]" />
                          {:else if isCard}
                            <CreditCard class="size-4 text-[#2563eb]" />
                          {:else}
                            <Wallet class="size-4 text-[#7c3aed]" />
                          {/if}
                        </div>
                        <div class="min-w-0">
                          <div class="truncate font-bold text-[#17171c]">{method.method}</div>
                          <div class="font-mono text-[10.5px] text-[#8e8e93]">
                            {method.count} Transaksi
                          </div>
                        </div>
                      </div>
                      <div class="shrink-0 text-right">
                        <div class="font-mono font-bold text-[#17171c]">
                          {formatRupiah(method.amount)}
                        </div>
                        <div class="font-mono text-[10.5px] font-semibold text-[#059669]">
                          {method.percent}%
                        </div>
                      </div>
                    </div>
                    <!-- Visual Progress Bar -->
                    <div class="h-2 w-full overflow-hidden rounded-full bg-[#e5e5ea]">
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
          <div
            class="space-y-5 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs sm:rounded-3xl sm:p-6"
          >
            <div class="flex items-start justify-between gap-2">
              <div>
                <h3 class="text-base font-bold text-[#17171c]">Audit Selisih Opname &amp; Waste</h3>
                <p class="text-xs text-[#8e8e93]">
                  Rekonsiliasi pemakaian bahan baku bar vs hitung fisik closing
                </p>
              </div>
              <span
                class="rounded-full bg-[#f4f4f6] px-2.5 py-0.5 font-mono text-[10.5px] font-semibold text-[#17171c]"
              >
                {damagedLogs.length} Insiden Waste
              </span>
            </div>

            <div
              class="flex items-center justify-between rounded-2xl border border-[#ececee] bg-[#fbfbfa] p-4"
            >
              <div>
                <span class="font-mono text-[10.5px] font-semibold text-[#8e8e93] uppercase"
                  >Estimasi Biaya Waste/Rusak</span
                >
                <div class="mt-0.5 font-mono text-xl font-bold text-[#e5484d]">
                  {formatRupiah(totalWasteCost)}
                </div>
              </div>
              <span
                class="rounded-full border border-[#fecaca] bg-[#fef2f2] px-3 py-1 font-mono text-[11px] font-semibold text-[#e5484d]"
              >
                {damagedLogs.length} Insiden
              </span>
            </div>

            <!-- Recent Adjustment Activity -->
            <div class="space-y-2.5">
              <span class="font-mono text-[11px] font-semibold text-[#8e8e93] uppercase"
                >Riwayat Opname Terakhir</span
              >
              {#if adjustmentLogs.length === 0}
                <div class="space-y-1 py-6 text-center text-[#8e8e93]">
                  <FileText class="mx-auto size-6 opacity-40" />
                  <p class="text-xs font-semibold text-[#17171c]">Belum ada riwayat stock opname</p>
                </div>
              {:else}
                <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                  {#each adjustmentLogs.slice(0, 4) as log}
                    <div
                      class="flex items-center justify-between gap-3 rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] p-3 text-xs"
                    >
                      <div class="min-w-0">
                        <div class="truncate font-bold text-[#17171c]">{log.material_name}</div>
                        <div class="truncate text-[10.5px] text-[#8e8e93]">
                          {log.notes || 'Penyesuaian stok reguler'} &bull; {log.performed_by}
                        </div>
                      </div>
                      <div class="shrink-0 text-right">
                        <span
                          class={`font-mono font-bold ${log.adjusted_amount < 0 ? 'text-[#e5484d]' : 'text-[#059669]'}`}
                        >
                          {log.adjusted_amount > 0
                            ? `+${log.adjusted_amount}`
                            : log.adjusted_amount}
                        </span>
                        <div class="font-mono text-[10px] text-[#8e8e93]">{log.created_at}</div>
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
