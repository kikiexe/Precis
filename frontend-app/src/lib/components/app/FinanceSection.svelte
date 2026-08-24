<script lang="ts">
  import {
    Plus,
    Wallet,
    FileText,
    Printer,
    Download,
    Check,
    CreditCard,
    User as UserIcon,
    KeyRound
  } from 'lucide-svelte';
  import type {
    CashAdvance,
    PayrollSlipData,
    PayrollPreviewData,
    User
  } from '../../types/app';
  import { inventoryService } from '../../services/inventory-service';
  import { authService } from '../../services/auth-service';

  interface Props {
    currentUser: User;
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

  $effect(() => {
    if (currentUser.role === 'STAFF') {
      activeSubTab = 'staff_ess';
    } else if (initialSubTab === 'laporan' || initialSubTab === 'payroll') {
      activeSubTab = initialSubTab;
    }
  });

  // Admin Payroll State
  let selectedBankFormat = $state<'BCA' | 'MANDIRI'>('BCA');
  let isDisbursing = $state(false);
  let isExporting = $state(false);
  let isFiltering = $state(false);
  let isConfirmDisburseOpen = $state(false);
  let actionMessage = $state<string | null>(null);

  // Staff Kasbon Modal State
  let isKasbonModalOpen = $state(false);
  let requestAmount = $state(200000);
  let requestPurpose = $state('');
  let isSubmittingKasbon = $state(false);
  let kasbonErrorMessage = $state<string | null>(null);

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

  $effect(() => {
    if (payrollPreview?.period_start) filterPeriodStart = payrollPreview.period_start;
    if (payrollPreview?.period_end) filterPeriodEnd = payrollPreview.period_end;
  });

  function formatRp(num: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);
  }

  // Admin Payroll Derived Totals
  let totalBaseSalary = $derived(
    payrollPreview ? (payrollPreview.totals?.total_base_salary ?? payrollPreview.total_base_salary ?? payrollPreview.items.reduce((s, i) => s + i.base_salary, 0)) : 0
  );

  let totalLatePenalty = $derived(
    payrollPreview ? (payrollPreview.totals?.total_late_penalty ?? payrollPreview.total_late_penalty ?? payrollPreview.items.reduce((s, i) => s + i.late_penalty, 0)) : 0
  );

  let totalOvertimePay = $derived(
    payrollPreview ? (payrollPreview.totals?.total_overtime_pay ?? payrollPreview.total_overtime_pay ?? payrollPreview.items.reduce((s, i) => s + i.overtime_pay, 0)) : 0
  );

  let totalCashAdvanceDeduction = $derived(
    payrollPreview ? (payrollPreview.totals?.total_cash_advance_deduction ?? payrollPreview.total_cash_advance_deduction ?? payrollPreview.items.reduce((s, i) => s + i.cash_advance_deduction, 0)) : 0
  );

  let totalPayrollDisbursement = $derived(
    payrollPreview ? (payrollPreview.totals?.total_net_salary ?? payrollPreview.total_net_salary ?? payrollPreview.items.reduce((s, i) => s + i.net_salary, 0)) : 0
  );

  // Staff Derived
  let totalActiveKasbon = $derived(
    cashAdvances.filter((k) => k.status === 'APPROVED').reduce((sum, k) => sum + k.amount, 0)
  );

  async function handleFilterPeriod() {
    if (!onFilterPayrollPeriod) return;
    isFiltering = true;
    actionMessage = null;
    try {
      await onFilterPayrollPeriod(filterPeriodStart, filterPeriodEnd);
      actionMessage = `Pratinjau payroll periode ${filterPeriodStart} s/d ${filterPeriodEnd} berhasil dimuat.`;
    } catch (e: unknown) {
      actionMessage = e instanceof Error ? e.message : 'Gagal memuat pratinjau payroll.';
    } finally {
      isFiltering = false;
    }
  }

  async function handleDisburse() {
    if (!onDisbursePayroll) return;
    isDisbursing = true;
    actionMessage = null;
    try {
      await onDisbursePayroll(filterPeriodStart, filterPeriodEnd);
      isConfirmDisburseOpen = false;
      actionMessage = 'Pencairan penggajian periode ini berhasil diselesaikan.';
    } catch (e: unknown) {
      actionMessage = e instanceof Error ? e.message : 'Gagal mencairkan payroll.';
    } finally {
      isDisbursing = false;
    }
  }

  async function handleExport() {
    if (!onExportCsv) return;
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

  async function handleSendKasbon() {
    if (requestAmount <= 0) {
      kasbonErrorMessage = 'Masukkan nominal kasbon yang valid.';
      return;
    }
    isSubmittingKasbon = true;
    kasbonErrorMessage = null;
    try {
      await onRequestKasbon(requestAmount, requestPurpose);
      isKasbonModalOpen = false;
      requestPurpose = '';
    } catch (e: unknown) {
      kasbonErrorMessage = e instanceof Error ? e.message : 'Gagal mengajukan kasbon.';
    } finally {
      isSubmittingKasbon = false;
    }
  }

  // Staff Bank Account State & Handlers
  let bankName = $state('BCA');
  let bankAccountNumber = $state('');
  let bankAccountHolder = $state('');
  let isSavingBank = $state(false);
  let bankSuccessMsg = $state<string | null>(null);
  let bankErrorMsg = $state<string | null>(null);

  // Profile & Password State
  let profileName = $state('');
  let isSavingProfile = $state(false);
  let profileSuccessMsg = $state<string | null>(null);
  let profileErrorMsg = $state<string | null>(null);

  let currentPassword = $state('');
  let newPassword = $state('');
  let confirmPassword = $state('');
  let isSavingPassword = $state(false);
  let passwordSuccessMsg = $state<string | null>(null);
  let passwordErrorMsg = $state<string | null>(null);

  $effect(() => {
    bankName = currentUser.bank_name || 'BCA';
    bankAccountNumber = currentUser.bank_account_number || '';
    bankAccountHolder = currentUser.bank_account_holder || currentUser.name || '';
    profileName = currentUser.name || '';
  });

  async function handleSaveProfile() {
    if (!profileName.trim()) {
      profileErrorMsg = 'Nama lengkap tidak boleh kosong.';
      return;
    }
    isSavingProfile = true;
    profileSuccessMsg = null;
    profileErrorMsg = null;
    try {
      await authService.updateProfile(profileName.trim());
      currentUser.name = profileName.trim();
      profileSuccessMsg = 'Nama profil berhasil diperbarui.';
    } catch (e: unknown) {
      profileErrorMsg = e instanceof Error ? e.message : 'Gagal memperbarui profil.';
    } finally {
      isSavingProfile = false;
    }
  }

  async function handleSavePassword() {
    if (!currentPassword) {
      passwordErrorMsg = 'Kata sandi saat ini wajib diisi.';
      return;
    }
    if (!newPassword || newPassword.length < 8) {
      passwordErrorMsg = 'Kata sandi baru minimal 8 karakter.';
      return;
    }
    if (newPassword !== confirmPassword) {
      passwordErrorMsg = 'Konfirmasi kata sandi baru tidak cocok.';
      return;
    }
    isSavingPassword = true;
    passwordSuccessMsg = null;
    passwordErrorMsg = null;
    try {
      await authService.updatePassword(currentPassword, newPassword, confirmPassword);
      passwordSuccessMsg = 'Kata sandi berhasil diperbarui.';
      currentPassword = '';
      newPassword = '';
      confirmPassword = '';
    } catch (e: unknown) {
      passwordErrorMsg = e instanceof Error ? e.message : 'Gagal memperbarui kata sandi.';
    } finally {
      isSavingPassword = false;
    }
  }

  async function handleSaveBankAccount() {
    if (!bankAccountNumber.trim()) {
      bankErrorMsg = 'Nomor rekening bank wajib diisi.';
      return;
    }
    if (!bankAccountHolder.trim()) {
      bankErrorMsg = 'Nama pemilik rekening wajib diisi.';
      return;
    }
    isSavingBank = true;
    bankSuccessMsg = null;
    bankErrorMsg = null;
    try {
      await authService.updateBankAccount(bankName, bankAccountNumber.trim(), bankAccountHolder.trim());
      currentUser.bank_name = bankName;
      currentUser.bank_account_number = bankAccountNumber.trim();
      currentUser.bank_account_holder = bankAccountHolder.trim();
      bankSuccessMsg = 'Rekening bank berhasil disimpan untuk pencairan gaji.';
    } catch (e: unknown) {
      bankErrorMsg = e instanceof Error ? e.message : 'Gagal menyimpan rekening bank.';
    } finally {
      isSavingBank = false;
    }
  }
</script>

<div class="space-y-6 font-sans">
  {#if currentUser.role === 'OWNER' || currentUser.role === 'ADMIN'}
    <!-- Top Segmented Navigation for Owner/Admin -->
    <div class="bg-white border border-[#d9d9dd] rounded-3xl p-2 sm:p-2.5 flex items-center justify-between gap-2">
      <div class="flex items-center gap-1.5 w-full sm:w-auto bg-[#eeece7]/40 sm:bg-transparent p-1 sm:p-0 rounded-full">
        <button
          type="button"
          title="Payroll"
          onclick={() => (activeSubTab = 'payroll')}
          class={`text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-2 ${
            activeSubTab === 'payroll'
              ? 'flex-1 sm:flex-initial bg-[#17171c] text-white shadow-xs px-4 py-2 sm:px-5 sm:py-2.5'
              : 'text-[#616161] hover:text-[#212121] hover:bg-white/80 w-9 h-9 sm:w-auto sm:h-auto sm:p-2.5 shrink-0'
          }`}
        >
          <Wallet class="w-4 h-4 shrink-0" />
          {#if activeSubTab === 'payroll'}
            <span class="whitespace-nowrap truncate">Payroll</span>
          {/if}
        </button>

        <button
          type="button"
          title="Laporan"
          onclick={() => (activeSubTab = 'laporan')}
          class={`text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-2 ${
            activeSubTab === 'laporan'
              ? 'flex-1 sm:flex-initial bg-[#17171c] text-white shadow-xs px-4 py-2 sm:px-5 sm:py-2.5'
              : 'text-[#616161] hover:text-[#212121] hover:bg-white/80 w-9 h-9 sm:w-auto sm:h-auto sm:p-2.5 shrink-0'
          }`}
        >
          <FileText class="w-4 h-4 shrink-0" />
          {#if activeSubTab === 'laporan'}
            <span class="whitespace-nowrap truncate">Laporan</span>
          {/if}
        </button>
      </div>
    </div>
  {/if}

  <!-- ADMIN SUBTAB 1: PAYROLL CALCULATION -->
  {#if activeSubTab === 'payroll'}
    <div class="space-y-6">
      {#if actionMessage}
        <div class="p-4 bg-[#f1f5ff] border border-[#d9d9dd] rounded-2xl text-xs font-medium text-[#1863dc] flex items-center justify-between">
          <span>{actionMessage}</span>
          <button type="button" onclick={() => (actionMessage = null)} class="text-[#75758a] hover:text-[#212121] cursor-pointer">&times;</button>
        </div>
      {/if}

      <!-- Period Filter & Export Card -->
      <div class="bg-white border border-[#d9d9dd] rounded-3xl p-4 sm:p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row sm:items-end gap-3 text-xs w-full lg:w-auto">
          <div class="w-full sm:w-auto">
            <label for="filter-period-start" class="block text-[10px] font-mono uppercase text-[#75758a] mb-1">Periode Awal</label>
            <input
              id="filter-period-start"
              type="date"
              bind:value={filterPeriodStart}
              class="w-full sm:w-auto px-3.5 py-2 bg-[#eeece7]/40 border border-[#d9d9dd] rounded-full text-xs text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden"
            />
          </div>
          <div class="w-full sm:w-auto">
            <label for="filter-period-end" class="block text-[10px] font-mono uppercase text-[#75758a] mb-1">Periode Akhir</label>
            <input
              id="filter-period-end"
              type="date"
              bind:value={filterPeriodEnd}
              class="w-full sm:w-auto px-3.5 py-2 bg-[#eeece7]/40 border border-[#d9d9dd] rounded-full text-xs text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden"
            />
          </div>
          <button
            type="button"
            onclick={handleFilterPeriod}
            disabled={isFiltering}
            class="w-full sm:w-auto px-4 py-2 bg-[#eeece7] hover:bg-[#d9d9dd] text-[#212121] rounded-full text-xs font-medium transition-all cursor-pointer disabled:opacity-50 text-center"
          >
            {isFiltering ? 'Memuat...' : 'Hitung Payroll'}
          </button>
        </div>

        <!-- Disburse & Export Buttons -->
        <div class="flex flex-wrap items-center gap-2 pt-2 lg:pt-0 border-t lg:border-t-0 border-[#d9d9dd]">
          <select
            bind:value={selectedBankFormat}
            class="flex-1 sm:flex-none px-3 py-2 bg-[#eeece7]/40 border border-[#d9d9dd] rounded-full text-xs text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer"
          >
            <option value="BCA">BCA CSV</option>
            <option value="MANDIRI">Mandiri CSV</option>
          </select>

          <button
            type="button"
            onclick={handleExport}
            disabled={isExporting || !payrollPreview}
            class="flex-1 sm:flex-none px-4 py-2 border border-[#d9d9dd] bg-white hover:bg-[#eeece7] text-[#212121] rounded-full text-xs font-medium transition-all cursor-pointer disabled:opacity-50 flex items-center justify-center gap-1.5"
          >
            <Download class="w-3.5 h-3.5" />
            <span>{isExporting ? 'Export...' : 'Export CSV'}</span>
          </button>

          <button
            type="button"
            onclick={() => (isConfirmDisburseOpen = true)}
            disabled={isDisbursing || !payrollPreview}
            class="w-full sm:w-auto px-4 py-2 bg-[#17171c] hover:bg-black text-white rounded-full text-xs font-medium transition-all cursor-pointer disabled:opacity-50 flex items-center justify-center gap-1.5"
          >
            <Check class="w-3.5 h-3.5" />
            <span>Cairkan Payroll</span>
          </button>
        </div>
      </div>

      <!-- Financial Totals Summary Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white border border-[#d9d9dd] rounded-[20px] p-4">
          <div class="text-[10px] font-mono uppercase text-[#75758a]">Gaji Pokok Total</div>
          <div class="text-lg font-medium font-mono text-[#212121] mt-1">{formatRp(totalBaseSalary)}</div>
        </div>
        <div class="bg-white border border-[#d9d9dd] rounded-[20px] p-4">
          <div class="text-[10px] font-mono uppercase text-[#75758a]">Upah Lembur</div>
          <div class="text-lg font-medium font-mono text-[#003c33] mt-1">+{formatRp(totalOvertimePay)}</div>
        </div>
        <div class="bg-white border border-[#d9d9dd] rounded-[20px] p-4">
          <div class="text-[10px] font-mono uppercase text-[#75758a]">Potongan Terlambat</div>
          <div class="text-lg font-medium font-mono text-[#e5484d] mt-1">-{formatRp(totalLatePenalty)}</div>
        </div>
        <div class="bg-white border border-[#d9d9dd] rounded-[20px] p-4">
          <div class="text-[10px] font-mono uppercase text-[#75758a]">Potongan Kasbon</div>
          <div class="text-lg font-medium font-mono text-[#e5484d] mt-1">-{formatRp(totalCashAdvanceDeduction)}</div>
        </div>
        <div class="bg-[#17171c] text-white rounded-[20px] p-4">
          <div class="text-[10px] font-mono uppercase text-white/70">Total Pencairan Bersih</div>
          <div class="text-lg font-medium font-mono text-white mt-1">{formatRp(totalPayrollDisbursement)}</div>
        </div>
      </div>

      <!-- Payroll Items Table -->
      <div class="bg-white border border-[#d9d9dd] rounded-3xl overflow-hidden">
        <div class="p-6 border-b border-[#d9d9dd]">
          <h2 class="text-base font-medium text-[#212121]">Rincian Gaji Bersih per Karyawan</h2>
          <p class="text-xs text-[#75758a]">Kalkulasi otomatis presensi, denda keterlambatan, dan kasbon</p>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-[#eeece7]/40 border-b border-[#d9d9dd] text-[#75758a] font-mono uppercase text-[10px]">
                <th class="py-3.5 px-5">Nama Karyawan</th>
                <th class="py-3.5 px-5">Cabang</th>
                <th class="py-3.5 px-5 text-right">Gaji Pokok</th>
                <th class="py-3.5 px-5 text-right">Lembur</th>
                <th class="py-3.5 px-5 text-right">Denda Telat</th>
                <th class="py-3.5 px-5 text-right">Kasbon</th>
                <th class="py-3.5 px-5 text-right font-medium text-[#17171c]">Gaji Bersih (Net)</th>
                <th class="py-3.5 px-5 text-center">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#d9d9dd]">
              {#if payrollPreview?.items && payrollPreview.items.filter((item) => item.role !== 'OWNER').length > 0}
                {#each payrollPreview.items.filter((item) => item.role !== 'OWNER') as item}
                  <tr class="hover:bg-[#eeece7]/20 transition-all">
                    <td class="py-4 px-5">
                      <div class="font-medium text-[#212121]">{item.name}</div>
                      <div class="text-[10px] text-[#75758a] font-mono">{item.role}</div>
                    </td>
                    <td class="py-4 px-5 text-[#616161]">{item.branch_name || 'Cabang Utama'}</td>
                    <td class="py-4 px-5 text-right font-mono text-[#212121]">{formatRp(item.base_salary)}</td>
                    <td class="py-4 px-5 text-right font-mono text-[#003c33]">+{formatRp(item.overtime_pay)}</td>
                    <td class="py-4 px-5 text-right font-mono text-[#e5484d]">-{formatRp(item.late_penalty)}</td>
                    <td class="py-4 px-5 text-right font-mono text-[#e5484d]">-{formatRp(item.cash_advance_deduction)}</td>
                    <td class="py-4 px-5 text-right font-mono font-medium text-[#17171c]">{formatRp(item.net_salary)}</td>
                    <td class="py-4 px-5 text-center">
                      <span class="text-[10px] font-mono font-medium px-2 py-0.5 rounded-full bg-[#edfce9] text-[#003c33]">
                        ESTIMATED
                      </span>
                    </td>
                  </tr>
                {/each}
              {:else}
                <tr>
                  <td colspan="8" class="py-12 text-center text-[#75758a]">
                    Belum ada data staf untuk periode ini. Tambahkan staf di menu Tim terlebih dahulu.
                  </td>
                </tr>
              {/if}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  {/if}

  <!-- ADMIN SUBTAB 2: LAPORAN PENJUALAN & OPNAME -->
  {#if activeSubTab === 'laporan'}
    {@const metrics = inventoryService.getTimeframeMetrics('day')}
    {@const adjustmentLogs = inventoryService.getAdjustmentLogs()}
    {@const damagedLogs = adjustmentLogs.filter((l) => l.reason === 'DAMAGED' || l.reason === 'EXPIRED' || l.reason === 'WASTE')}
    {@const totalWasteCost = damagedLogs.reduce((sum, l) => sum + Math.abs(l.adjusted_amount * 20000), 0)}

    <div class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Sales Breakdown by Payment Method -->
        <div class="bg-white border border-[#d9d9dd] rounded-3xl p-6 space-y-4">
          <div>
            <h2 class="text-base font-medium text-[#212121]">Komposisi Metode Pembayaran</h2>
            <p class="text-xs text-[#75758a]">Distribusi omzet per saluran bayar kasir</p>
          </div>

          {#if metrics.payment_methods.length === 0}
            <div class="py-8 text-center text-[#93939f] space-y-1">
              <Wallet class="w-6 h-6 mx-auto text-[#93939f] opacity-40" />
              <p class="text-xs font-medium text-[#17171c]">Belum ada transaksi pembayaran</p>
              <p class="text-[11px] text-[#75758a]">Komposisi saluran bayar akan muncul setelah kasir memproses pesanan di POS.</p>
            </div>
          {:else}
            <div class="space-y-3 pt-2">
              {#each metrics.payment_methods as method, i}
                <div class="space-y-1 text-xs">
                  <div class="flex justify-between">
                    <span class="font-medium text-[#212121]">{method.method}</span>
                    <span class="font-mono text-[#17171c]">{formatRp(method.amount)} ({method.percent}%)</span>
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

        <!-- Stock Variance & Opname Discrepancy -->
        <div class="bg-white border border-[#d9d9dd] rounded-3xl p-6 space-y-4">
          <div>
            <h2 class="text-base font-medium text-[#212121]">Audit Selisih Opname &amp; Waste</h2>
            <p class="text-xs text-[#75758a]">Rekonsiliasi pemakaian bahan baku bar vs hitung fisik closing</p>
          </div>

          {#if adjustmentLogs.length === 0}
            <div class="py-8 text-center text-[#93939f] space-y-1">
              <FileText class="w-6 h-6 mx-auto text-[#93939f] opacity-40" />
              <p class="text-xs font-medium text-[#17171c]">Belum ada riwayat stock opname</p>
              <p class="text-[11px] text-[#75758a]">Lakukan opname berkala di menu Stok Opname POS untuk merekam selisih fisik.</p>
            </div>
          {:else}
            <div class="p-4 bg-[#eeece7]/40 border border-[#d9d9dd] rounded-2xl flex items-center justify-between">
              <div>
                <div class="text-[10px] font-mono uppercase text-[#75758a]">Estimasi Biaya Waste/Rusak</div>
                <div class="text-xl font-medium font-mono text-[#e5484d] mt-0.5">{formatRp(totalWasteCost)}</div>
              </div>
              <span class="px-2.5 py-1 rounded-full bg-[#ffefef] text-[#e5484d] text-[10px] font-mono font-medium">
                {damagedLogs.length} Insiden Waste
              </span>
            </div>

            <div class="text-xs text-[#616161] space-y-1.5">
              {#each adjustmentLogs.slice(0, 5) as log}
                <div class="flex justify-between py-1 border-b border-[#d9d9dd] last:border-b-0">
                  <span class="truncate">{log.material_name}</span>
                  <span class={`font-mono ${log.adjusted_amount < 0 ? 'text-[#e5484d]' : 'text-[#003c33]'}`}>
                    {log.adjusted_amount > 0 ? `+${log.adjusted_amount}` : log.adjusted_amount} ({log.reason})
                  </span>
                </div>
              {/each}
            </div>
          {/if}
        </div>
      </div>
    </div>
  {/if}

  <!-- STAFF SUBTAB: ESS KASBON & SLIP GAJI DIGITAL -->
  {#if activeSubTab === 'staff_ess'}
    <div class="space-y-3 sm:space-y-4 max-w-4xl mx-auto font-sans pb-6">
      <div class="flex items-center justify-between gap-3 pb-1">
        <div>
          <h2 class="text-sm sm:text-base font-medium text-[#212121]">Kasbon &amp; Slip Gaji Digital</h2>
          <p class="text-[11px] text-[#75758a]">Pengajuan pinjaman darurat &amp; rincian gaji berjalan</p>
        </div>
        <button
          type="button"
          onclick={() => { kasbonErrorMessage = null; isKasbonModalOpen = true; }}
          class="bg-[#17171c] hover:bg-black text-white px-3 py-1.5 text-xs font-medium rounded-full flex items-center gap-1.5 cursor-pointer transition-all shrink-0"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>Ajukan Kasbon</span>
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 sm:gap-4">
        <!-- Status Kasbon Aktif & Riwayat -->
        <div class="lg:col-span-6 space-y-3">
          <!-- Total Kasbon Card -->
          <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 flex items-center justify-between">
            <div>
              <div class="text-[11px] text-[#75758a]">Total Kasbon Belum Lunas</div>
              <div class={`text-xl font-medium font-mono mt-0.5 ${totalActiveKasbon > 0 ? 'text-[#e5484d]' : 'text-[#00875a]'}`}>
                {formatRp(totalActiveKasbon)}
              </div>
              <div class="text-[10px] text-[#93939f] mt-0.5">
                {totalActiveKasbon > 0 ? 'Dipotong otomatis saat payroll' : 'Tidak ada pinjaman kasbon aktif'}
              </div>
            </div>
          </div>

          <!-- Riwayat Kasbon -->
          <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 space-y-2.5">
            <div class="flex items-center justify-between border-b border-[#f2f2f2] pb-2">
              <h3 class="text-xs font-medium text-[#212121]">Riwayat Permohonan Kasbon</h3>
              <span class="text-[10px] font-mono text-[#75758a]">{cashAdvances.length} Pengajuan</span>
            </div>

            {#if cashAdvances.length === 0}
              <div class="py-6 text-center text-xs text-[#75758a]">Belum ada riwayat kasbon.</div>
            {:else}
              <div class="divide-y divide-[#f2f2f2]">
                {#each cashAdvances as k}
                  <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between text-xs">
                    <div>
                      <div class="font-mono font-medium text-[#17171c]">{formatRp(k.amount)}</div>
                      <div class="text-[10px] text-[#75758a] mt-0.5">{k.purpose || 'Pinjaman'} • {k.created_at || k.request_date || '-'}</div>
                    </div>
                    <span class={`text-[9px] font-mono font-medium px-2 py-0.5 rounded-full ${
                      k.status === 'APPROVED' ? 'bg-[#edfce9] text-[#00875a]' : k.status === 'PENDING' ? 'bg-[#eeece7] text-[#616161]' : 'bg-[#ffefef] text-[#e5484d]'
                    }`}>
                      {k.status}
                    </span>
                  </div>
                {/each}
              </div>
            {/if}
          </div>
        </div>

        <!-- Slip Gaji Digital Preview -->
        <div class="lg:col-span-6 space-y-3">
          <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 space-y-3">
            <div class="flex items-center justify-between border-b border-[#f2f2f2] pb-2.5">
              <div>
                <h3 class="text-xs sm:text-sm font-medium text-[#212121]">Slip Gaji Periode Berjalan</h3>
                <div class="text-[10px] font-mono text-[#75758a]">{payrollSlip?.period_start || ''} s/d {payrollSlip?.period_end || ''}</div>
              </div>
              <button
                type="button"
                onclick={() => window.print()}
                class="p-1.5 border border-[#d9d9dd] rounded-xl hover:bg-[#eeece7] text-[#616161] hover:text-[#212121] cursor-pointer"
                title="Cetak Slip Gaji"
              >
                <Printer class="w-4 h-4" />
              </button>
            </div>

            {#if payrollSlip}
              <div class="space-y-2 text-xs">
                <div class="flex justify-between py-1 border-b border-[#f2f2f2]">
                  <span class="text-[#616161]">Gaji Pokok</span>
                  <span class="font-mono text-[#212121]">{formatRp(payrollSlip.base_salary)}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-[#f2f2f2]">
                  <span class="text-[#616161]">Upah Lembur ({Math.round((payrollSlip.total_overtime_minutes || 0) / 60)} Jam)</span>
                  <span class="font-mono text-[#00875a]">+{formatRp(payrollSlip.overtime_pay)}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-[#f2f2f2]">
                  <span class="text-[#616161]">Denda Terlambat ({payrollSlip.total_late_minutes || 0} Mnt)</span>
                  <span class="font-mono text-[#e5484d]">-{formatRp(payrollSlip.late_penalty)}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-[#f2f2f2]">
                  <span class="text-[#616161]">Potongan Kasbon</span>
                  <span class="font-mono text-[#e5484d]">-{formatRp(payrollSlip.cash_advance_deduction)}</span>
                </div>
                <div class="flex justify-between py-2 text-xs sm:text-sm font-medium bg-[#fbfbfb] border border-[#d9d9dd]/60 px-3 rounded-xl">
                  <span class="text-[#17171c]">Take Home Pay (Gaji Bersih)</span>
                  <span class="font-mono text-[#00875a]">{formatRp(payrollSlip.net_salary)}</span>
                </div>
              </div>
            {:else}
              <div class="py-6 text-center text-xs text-[#75758a]">Data slip gaji belum tersedia.</div>
            {/if}
          </div>
        </div>
      </div>

      <!-- Rekening Bank Pencairan Payroll Staf -->
      <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 sm:p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-[#f2f2f2] pb-3">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-full bg-[#17171c] text-white flex items-center justify-center">
              <CreditCard class="w-4 h-4" />
            </div>
            <div>
              <h3 class="text-xs sm:text-sm font-medium text-[#212121]">Rekening Bank Pencairan Gaji</h3>
              <p class="text-[11px] text-[#75758a]">Data rekening bank pribadi untuk transfer otomatis payroll dari Owner</p>
            </div>
          </div>
          {#if currentUser.bank_account_number}
            <span class="text-[10px] font-mono text-[#00875a] bg-[#edfce9] px-2.5 py-1 rounded-full font-medium">
              Tersimpan
            </span>
          {:else}
            <span class="text-[10px] font-mono text-[#e5484d] bg-[#ffefef] px-2.5 py-1 rounded-full font-medium">
              Belum Diisi
            </span>
          {/if}
        </div>

        {#if bankSuccessMsg}
          <div class="p-3 bg-[#edfce9] border border-[#00875a]/20 text-[#003c33] text-xs rounded-xl">{bankSuccessMsg}</div>
        {/if}
        {#if bankErrorMsg}
          <div class="p-3 bg-[#ffefef] border border-[#e5484d]/20 text-[#e5484d] text-xs rounded-xl">{bankErrorMsg}</div>
        {/if}

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="space-y-1">
            <label for="staff-bank-name" class="text-[11px] font-medium text-[#212121]">Nama Bank</label>
            <select
              id="staff-bank-name"
              bind:value={bankName}
              class="w-full bg-[#fafafa] border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs text-[#17171c] focus:outline-none focus:border-[#17171c]"
            >
              <option value="BCA">BCA (Bank Central Asia)</option>
              <option value="Mandiri">Bank Mandiri</option>
              <option value="BRI">BRI (Bank Rakyat Indonesia)</option>
              <option value="BNI">BNI (Bank Negara Indonesia)</option>
              <option value="BSI">BSI (Bank Syariah Indonesia)</option>
              <option value="CIMB">CIMB Niaga</option>
              <option value="Permata">Bank Permata</option>
              <option value="SeaBank">SeaBank</option>
              <option value="Jago">Bank Jago</option>
              <option value="BCA Digital (Blu)">BCA Digital (Blu)</option>
            </select>
          </div>

          <div class="space-y-1">
            <label for="staff-bank-number" class="text-[11px] font-medium text-[#212121]">Nomor Rekening</label>
            <input
              id="staff-bank-number"
              type="text"
              bind:value={bankAccountNumber}
              placeholder="Contoh: 1234567890"
              class="w-full bg-[#fafafa] border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs font-mono text-[#17171c] focus:outline-none focus:border-[#17171c]"
            />
          </div>

          <div class="space-y-1">
            <label for="staff-bank-holder" class="text-[11px] font-medium text-[#212121]">Atas Nama Rekening</label>
            <input
              id="staff-bank-holder"
              type="text"
              bind:value={bankAccountHolder}
              placeholder="Nama Lengkap Pemilik Rekening"
              class="w-full bg-[#fafafa] border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs text-[#17171c] focus:outline-none focus:border-[#17171c]"
            />
          </div>
        </div>

        <div class="flex justify-end pt-1">
          <button
            type="button"
            onclick={handleSaveBankAccount}
            disabled={isSavingBank}
            class="px-4 py-2 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-full cursor-pointer transition-all disabled:opacity-50"
          >
            {isSavingBank ? 'Menyimpan...' : 'Simpan Rekening Bank'}
          </button>
        </div>
      </div>

      <!-- Profil Personal & Ganti Password -->
      <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 sm:p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-[#f2f2f2] pb-3">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-full bg-[#17171c] text-white flex items-center justify-center">
              <UserIcon class="w-4 h-4" />
            </div>
            <div>
              <h3 class="text-xs sm:text-sm font-medium text-[#212121]">Profil &amp; Keamanan Akun</h3>
              <p class="text-[11px] text-[#75758a]">Kelola nama profil personal dan pembaruan kata sandi login</p>
            </div>
          </div>
          <span class="text-[10px] font-mono text-[#75758a] bg-[#eeece7] px-2.5 py-1 rounded-full font-medium">
            {currentUser.email}
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Form Edit Nama Profil -->
          <div class="space-y-3">
            <div class="text-xs font-medium text-[#212121] flex items-center gap-1.5">
              <UserIcon class="w-3.5 h-3.5 text-[#75758a]" />
              <span>Data Personal</span>
            </div>

            {#if profileSuccessMsg}
              <div class="p-2.5 bg-[#edfce9] border border-[#00875a]/20 text-[#003c33] text-xs rounded-xl">{profileSuccessMsg}</div>
            {/if}
            {#if profileErrorMsg}
              <div class="p-2.5 bg-[#ffefef] border border-[#e5484d]/20 text-[#e5484d] text-xs rounded-xl">{profileErrorMsg}</div>
            {/if}

            <div class="space-y-1">
              <label for="profile-email-ro" class="text-[11px] font-medium text-[#212121]">Alamat Email (Akun)</label>
              <input
                id="profile-email-ro"
                type="email"
                value={currentUser.email}
                disabled
                class="w-full bg-[#eeece7]/50 border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs font-mono text-[#75758a] cursor-not-allowed"
              />
            </div>

            <div class="space-y-1">
              <label for="profile-name-input" class="text-[11px] font-medium text-[#212121]">Nama Lengkap</label>
              <input
                id="profile-name-input"
                type="text"
                bind:value={profileName}
                placeholder="Nama Lengkap Anda"
                class="w-full bg-[#fafafa] border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs text-[#17171c] focus:outline-none focus:border-[#17171c]"
              />
            </div>

            <button
              type="button"
              onclick={handleSaveProfile}
              disabled={isSavingProfile}
              class="px-4 py-2 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-full cursor-pointer transition-all disabled:opacity-50"
            >
              {isSavingProfile ? 'Menyimpan...' : 'Simpan Nama Profil'}
            </button>
          </div>

          <!-- Form Ganti Password -->
          <div class="space-y-3">
            <div class="text-xs font-medium text-[#212121] flex items-center gap-1.5">
              <KeyRound class="w-3.5 h-3.5 text-[#75758a]" />
              <span>Ganti Kata Sandi</span>
            </div>

            {#if passwordSuccessMsg}
              <div class="p-2.5 bg-[#edfce9] border border-[#00875a]/20 text-[#003c33] text-xs rounded-xl">{passwordSuccessMsg}</div>
            {/if}
            {#if passwordErrorMsg}
              <div class="p-2.5 bg-[#ffefef] border border-[#e5484d]/20 text-[#e5484d] text-xs rounded-xl">{passwordErrorMsg}</div>
            {/if}

            <div class="space-y-1">
              <label for="pwd-current" class="text-[11px] font-medium text-[#212121]">Kata Sandi Saat Ini</label>
              <input
                id="pwd-current"
                type="password"
                bind:value={currentPassword}
                placeholder="••••••••"
                class="w-full bg-[#fafafa] border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs text-[#17171c] focus:outline-none focus:border-[#17171c]"
              />
            </div>

            <div class="space-y-1">
              <label for="pwd-new" class="text-[11px] font-medium text-[#212121]">Kata Sandi Baru</label>
              <input
                id="pwd-new"
                type="password"
                bind:value={newPassword}
                placeholder="Minimal 8 karakter"
                class="w-full bg-[#fafafa] border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs text-[#17171c] focus:outline-none focus:border-[#17171c]"
              />
            </div>

            <div class="space-y-1">
              <label for="pwd-confirm" class="text-[11px] font-medium text-[#212121]">Konfirmasi Kata Sandi Baru</label>
              <input
                id="pwd-confirm"
                type="password"
                bind:value={confirmPassword}
                placeholder="Ulangi kata sandi baru"
                class="w-full bg-[#fafafa] border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs text-[#17171c] focus:outline-none focus:border-[#17171c]"
              />
            </div>

            <button
              type="button"
              onclick={handleSavePassword}
              disabled={isSavingPassword}
              class="px-4 py-2 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-full cursor-pointer transition-all disabled:opacity-50"
            >
              {isSavingPassword ? 'Memperbarui...' : 'Perbarui Kata Sandi'}
            </button>
          </div>
        </div>
      </div>
    </div>
  {/if}
</div>

<!-- Modal: Konfirmasi Pencairan Payroll -->
{#if isConfirmDisburseOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-3xl w-full max-w-md p-6 space-y-4 shadow-2xl">
      <h3 class="text-base font-medium text-[#212121]">Konfirmasi Pencairan Payroll</h3>
      <p class="text-xs text-[#616161]">
        Total pencairan sebesar <strong class="text-[#17171c] font-mono">{formatRp(totalPayrollDisbursement)}</strong> akan disetujui, dan seluruh kasbon aktif karyawan otomatis dinyatakan lunas.
      </p>
      <div class="flex gap-3 pt-2">
        <button
          type="button"
          onclick={() => (isConfirmDisburseOpen = false)}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#eeece7] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleDisburse}
          class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer"
        >
          Konfirmasi &amp; Cairkan
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Modal: Ajukan Kasbon Staff -->
{#if isKasbonModalOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-3xl w-full max-w-md p-6 space-y-4 shadow-2xl">
      <h3 class="text-base font-medium text-[#212121]">Ajukan Kasbon Karyawan</h3>
      {#if kasbonErrorMessage}
        <div class="p-3 bg-[#ffefef] text-[#e5484d] text-xs rounded-xl">{kasbonErrorMessage}</div>
      {/if}
      <div class="space-y-3 text-xs">
        <div class="space-y-1">
          <label for="kasbon-amount" class="block font-medium text-[#212121]">Nominal Pengajuan (IDR)</label>
          <input
            id="kasbon-amount"
            type="number"
            bind:value={requestAmount}
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>
        <div class="space-y-1">
          <label for="kasbon-purpose" class="block font-medium text-[#212121]">Keperluan / Alasan</label>
          <textarea
            id="kasbon-purpose"
            bind:value={requestPurpose}
            rows="2"
            placeholder="Keperluan mendesak..."
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl text-[#212121] focus:border-[#17171c] focus:outline-hidden resize-none"
          ></textarea>
        </div>
      </div>
      <div class="flex gap-3 pt-2">
        <button
          type="button"
          onclick={() => (isKasbonModalOpen = false)}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#eeece7] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSendKasbon}
          disabled={isSubmittingKasbon}
          class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer disabled:opacity-50"
        >
          Kirim Pengajuan
        </button>
      </div>
    </div>
  </div>
{/if}
