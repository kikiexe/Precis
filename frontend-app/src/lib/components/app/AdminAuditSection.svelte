<script lang="ts">
  import { Check, Download, Users, Send, RefreshCw, ShieldCheck, ChevronDown, MapPin, ExternalLink, Eye, Camera, X } from 'lucide-svelte';
  import type { AttendanceRecord, PendingSwapItem, CashAdvance, PayrollPreviewData } from '../../types/app';

  interface Props {
    attendances?: AttendanceRecord[];
    pendingSwaps?: PendingSwapItem[];
    pendingKasbons?: CashAdvance[];
    payrollPreview?: PayrollPreviewData | null;
    onApproveSwap: (swapId: string) => void;
    onRejectSwap: (swapId: string) => void;
    onApproveKasbon: (kasbonId: string) => void;
    onRejectKasbon: (kasbonId: string) => void;
    onFilterPayrollPeriod?: (periodStart: string, periodEnd: string) => Promise<void> | void;
    onDisbursePayroll?: (periodStart: string, periodEnd: string) => Promise<void>;
    onExportCsv?: (periodStart: string, periodEnd: string, format: 'BCA' | 'MANDIRI') => Promise<void>;
  }

  let {
    attendances = [],
    pendingSwaps = [],
    pendingKasbons = [],
    payrollPreview = null,
    onApproveSwap,
    onRejectSwap,
    onApproveKasbon,
    onRejectKasbon,
    onFilterPayrollPeriod,
    onDisbursePayroll,
    onExportCsv,
  }: Props = $props();

  let activeAdminTab = $state<'wall' | 'approvals' | 'payroll'>('wall');
  let selectedBankFormat = $state<'BCA' | 'MANDIRI'>('BCA');
  let isDisbursing = $state(false);
  let isExporting = $state(false);
  let isFiltering = $state(false);
  let isConfirmDisburseOpen = $state(false);
  let actionMessage = $state<string | null>(null);

  function getDefaultPeriodStart(): string {
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
  }

  function getDefaultPeriodEnd(): string {
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
  }

  // filter tanggal periode payroll
  let filterPeriodStart = $state(getDefaultPeriodStart());
  let filterPeriodEnd = $state(getDefaultPeriodEnd());
  let selectedAttendanceDetail = $state<AttendanceRecord | null>(null);

  $effect(() => {
    if (payrollPreview?.period_start) {
      filterPeriodStart = payrollPreview.period_start;
    }
    if (payrollPreview?.period_end) {
      filterPeriodEnd = payrollPreview.period_end;
    }
  });

  function formatRp(num: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);
  }

  let totalMembersCount = $derived(
    payrollPreview ? (payrollPreview.total_members ?? payrollPreview.items.length) : 0
  );

  let totalBaseSalary = $derived(
    payrollPreview ? (payrollPreview.totals?.total_base_salary ?? payrollPreview.total_base_salary ?? payrollPreview.items.reduce((s, i) => s + i.base_salary, 0)) : 0
  );

  let totalOvertimePay = $derived(
    payrollPreview ? (payrollPreview.totals?.total_overtime_pay ?? payrollPreview.total_overtime_pay ?? payrollPreview.items.reduce((s, i) => s + i.overtime_pay, 0)) : 0
  );

  let totalLatePenalty = $derived(
    payrollPreview ? (payrollPreview.totals?.total_late_penalty ?? payrollPreview.total_late_penalty ?? payrollPreview.items.reduce((s, i) => s + i.late_penalty, 0)) : 0
  );

  let totalCashAdvanceDeduction = $derived(
    payrollPreview ? (payrollPreview.totals?.total_cash_advance_deduction ?? payrollPreview.total_cash_advance_deduction ?? payrollPreview.items.reduce((s, i) => s + i.cash_advance_deduction, 0)) : 0
  );

  let totalPayrollDisbursement = $derived(
    payrollPreview ? (payrollPreview.totals?.total_net_salary ?? payrollPreview.total_net_salary ?? payrollPreview.items.reduce((s, i) => s + i.net_salary, 0)) : 0
  );

  async function handleFilterPeriod() {
    if (!onFilterPayrollPeriod) return;
    isFiltering = true;
    actionMessage = null;

    try {
      await onFilterPayrollPeriod(filterPeriodStart, filterPeriodEnd);
      actionMessage = `Pratinjau payroll periode ${filterPeriodStart} s/d ${filterPeriodEnd} berhasil dimuat.`;
    } catch (e: unknown) {
      actionMessage = e instanceof Error ? e.message : 'Gagal memuat pratinjau periode payroll.';
    } finally {
      isFiltering = false;
    }
  }

  async function handleDisburse() {
    if (!onDisbursePayroll || !payrollPreview) return;
    isDisbursing = true;
    actionMessage = null;

    try {
      await onDisbursePayroll(payrollPreview.period_start, payrollPreview.period_end);
      isConfirmDisburseOpen = false;
      actionMessage = `Pencairan gaji periode ${payrollPreview.period_start} s/d ${payrollPreview.period_end} berhasil dieksekusi.`;
    } catch (e: unknown) {
      actionMessage = e instanceof Error ? e.message : 'Gagal mengeksekusi pencairan gaji.';
    } finally {
      isDisbursing = false;
    }
  }

  async function handleExport() {
    if (!onExportCsv || !payrollPreview) return;
    isExporting = true;
    actionMessage = null;

    try {
      await onExportCsv(payrollPreview.period_start, payrollPreview.period_end, selectedBankFormat);
      actionMessage = `Berkas CSV format ${selectedBankFormat} berhasil diunduh.`;
    } catch (e: unknown) {
      actionMessage = e instanceof Error ? e.message : 'Gagal mengekspor file CSV.';
    } finally {
      isExporting = false;
    }
  }
</script>

<div class="space-y-6 max-w-6xl mx-auto p-4 sm:p-6 md:p-8 pb-24 lg:pb-8 font-sans">
  <!-- navigasi subtab kontrol admin -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#d9d9dd] pb-4">
    <div>
      <h2 class="text-xl font-medium text-[#212121] tracking-tight">Dashboard Audit &amp; Kontrol Owner</h2>
      <p class="text-xs text-[#616161] font-normal mt-0.5">Pemeriksaan bukti foto kehadiran, otorisasi persetujuan, dan penggajian</p>
    </div>

    <!-- subtab navigasi admin -->
    <div class="flex gap-1 bg-[#eeece7]/60 p-1 rounded-full border border-[#d9d9dd] self-start sm:self-auto">
      <button
        type="button"
        onclick={() => (activeAdminTab = 'wall')}
        class={`px-4 py-1.5 text-xs font-medium rounded-full cursor-pointer transition-all ${
          activeAdminTab === 'wall'
            ? 'bg-[#17171c] text-white shadow-none'
            : 'text-[#616161] hover:text-[#212121]'
        }`}
      >
        Wall of Faces ({attendances.length})
      </button>

      <button
        type="button"
        onclick={() => (activeAdminTab = 'approvals')}
        class={`px-4 py-1.5 text-xs font-medium rounded-full cursor-pointer transition-all relative ${
          activeAdminTab === 'approvals'
            ? 'bg-[#17171c] text-white shadow-none'
            : 'text-[#616161] hover:text-[#212121]'
        }`}
      >
        Persetujuan ({pendingSwaps.length + pendingKasbons.length})
        {#if (pendingSwaps.length + pendingKasbons.length) > 0}
          <span class="absolute -top-1 -right-1 w-2 h-2 bg-[#ff7759] rounded-full"></span>
        {/if}
      </button>

      <button
        type="button"
        onclick={() => (activeAdminTab = 'payroll')}
        class={`px-4 py-1.5 text-xs font-medium rounded-full cursor-pointer transition-all ${
          activeAdminTab === 'payroll'
            ? 'bg-[#17171c] text-white shadow-none'
            : 'text-[#616161] hover:text-[#212121]'
        }`}
      >
        Penggajian (Payroll)
      </button>
    </div>
  </div>

  {#if actionMessage}
    <div class="p-3.5 bg-[#edfce9] border border-[#edfce9] text-[#003c33] text-xs font-mono rounded-[12px] flex items-center justify-between">
      <span>{actionMessage}</span>
      <button type="button" onclick={() => (actionMessage = null)} class="text-[#616161] hover:text-[#212121] cursor-pointer p-1">✕</button>
    </div>
  {/if}

  <!-- konten tab aktif -->
  {#if activeAdminTab === 'wall'}
    <!-- feed audit wall of faces -->
    <div class="space-y-4">
      <div class="flex items-center justify-between bg-white p-4 border border-[#d9d9dd] rounded-[16px] shadow-none">
        <div class="text-xs font-normal text-[#616161]">
          Menampilkan <strong class="text-[#212121] font-medium">{attendances.length}</strong> foto selfie presensi terverifikasi GPS hari ini
        </div>
        <span class="text-[10px] font-mono text-[#003c33] bg-[#edfce9] px-2.5 py-1 rounded-full font-medium">
          Kebijakan Retensi 60 Hari
        </span>
      </div>

      {#if attendances.length === 0}
        <div class="p-12 bg-white border border-[#d9d9dd] rounded-[22px] text-center space-y-2.5 shadow-none">
          <Users class="w-8 h-8 text-[#93939f] mx-auto opacity-50" />
          <h3 class="text-sm font-medium text-[#212121]">Belum Ada Presensi Masuk Hari Ini</h3>
          <p class="text-xs text-[#75758a]">Foto selfie dan koordinat GPS staf yang melakukan clock-in akan muncul di sini secara realtime.</p>
        </div>
      {:else}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {#each attendances as att (att.id)}
            <button
              type="button"
              onclick={() => (selectedAttendanceDetail = att)}
              class="bg-white border border-[#d9d9dd] rounded-[20px] overflow-hidden shadow-none hover:border-[#17171c] hover:shadow-md transition-all group text-left w-full cursor-pointer focus:outline-hidden"
            >
              <!-- header informasi staf -->
              <div class="p-3.5 border-b border-[#d9d9dd] flex items-center justify-between bg-[#eeece7]/40">
                <div>
                  <div class="font-medium text-xs text-[#212121]">{att.user_name}</div>
                  <div class="text-[10px] text-[#75758a]">{att.branch_name}</div>
                </div>
                <span class={`text-[9px] font-mono px-2 py-0.5 rounded-full font-medium ${
                  att.status === 'ON_TIME'
                    ? 'bg-[#edfce9] text-[#003c33]'
                    : 'bg-[#ffad9b]/20 text-[#b30000]'
                }`}>
                  {att.status === 'ON_TIME' ? 'Tepat Waktu' : `Telat ${att.late_minutes} Mnt`}
                </span>
              </div>

              <!-- foto selfie dengan watermark canvas -->
              <div class="relative bg-[#17171c] aspect-3/4 overflow-hidden">
                <img
                  src={att.photo_in_url}
                  alt={`Selfie ${att.user_name}`}
                  class="w-full h-full object-cover group-hover:scale-105 transition-all"
                />

                <!-- Hover Inspection Prompt -->
                <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                  <span class="px-3 py-1.5 bg-white/90 backdrop-blur-xs text-[#17171c] text-xs font-medium rounded-full shadow-xs flex items-center gap-1.5">
                    <Eye class="w-3.5 h-3.5" />
                    <span>Periksa Detail</span>
                  </span>
                </div>
              </div>

              <!-- footer metadata kartu presensi -->
              <div class="p-3.5 text-xs space-y-1.5 text-[#616161] font-mono bg-white">
                <div class="flex justify-between">
                  <span class="text-[#75758a]">Waktu Masuk:</span>
                  <span class="font-medium text-[#212121]">{att.clock_in_time}</span>
                </div>
                <div class="flex justify-between text-[11px] text-[#93939f]">
                  <span>Audit Geofence:</span>
                  <span>Lat: {att.lat_in.toFixed(4)}, Lng: {att.lng_in.toFixed(4)}</span>
                </div>
              </div>
            </button>
          {/each}
        </div>
      {/if}
    </div>
  {:else if activeAdminTab === 'approvals'}
    <!-- pusat persetujuan (tampilan 2 kolom di desktop) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- 1. antrean pengajuan tukar shift -->
      <div class="space-y-3">
        <h3 class="text-xs font-medium text-[#75758a] uppercase tracking-wider px-1">
          1. Pengajuan Tukar Shift ({pendingSwaps.length})
        </h3>
        {#if pendingSwaps.length === 0}
          <div class="p-8 bg-white border border-[#d9d9dd] rounded-[20px] text-center text-xs text-[#75758a] shadow-none">
            Tidak ada permohonan tukar shift yang tertunda.
          </div>
        {:else}
          <div class="space-y-3">
            {#each pendingSwaps as swap}
              <div class="bg-white border border-[#d9d9dd] rounded-[18px] p-5 text-xs space-y-3 shadow-none">
                <div class="font-medium text-sm text-[#212121]">
                  {swap.assigned_user?.name || 'Staf'} ➔ {swap.actual_user?.name || 'Pengganti'}
                </div>
                <div class="text-[11px] text-[#616161] font-mono">
                  Tanggal: {swap.date} ({swap.template?.name || 'Shift'})
                </div>
                <div class="text-[11px] text-[#93939f]">
                  Jam: {swap.template?.expected_clock_in || '07:00'} - {swap.template?.expected_clock_out || '15:00'} WIB
                </div>
                <div class="flex gap-2.5 pt-3 border-t border-[#d9d9dd]/60">
                  <button
                    type="button"
                    onclick={() => onRejectSwap(swap.id)}
                    class="flex-1 py-2 bg-white hover:bg-[#ffad9b]/15 text-[#b30000] text-xs font-medium rounded-full cursor-pointer border border-[#d9d9dd] transition-all"
                  >
                    Tolak
                  </button>
                  <button
                    type="button"
                    onclick={() => onApproveSwap(swap.id)}
                    class="flex-2 py-2 bg-[#003c33] hover:bg-[#002822] text-white text-xs font-medium rounded-full flex items-center justify-center gap-1 cursor-pointer transition-all shadow-none"
                  >
                    <Check class="w-3.5 h-3.5" />
                    <span>Setujui Pertukaran</span>
                  </button>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </div>

      <!-- 2. antrean otorisasi kasbon -->
      <div class="space-y-3">
        <h3 class="text-xs font-medium text-[#75758a] uppercase tracking-wider px-1">
          2. Otorisasi Pencairan Kasbon ({pendingKasbons.length})
        </h3>
        {#if pendingKasbons.length === 0}
          <div class="p-8 bg-white border border-[#d9d9dd] rounded-[20px] text-center text-xs text-[#75758a] shadow-none">
            Tidak ada permohonan kasbon yang tertunda.
          </div>
        {:else}
          <div class="space-y-3">
            {#each pendingKasbons as kasbon}
              <div class="bg-white border border-[#d9d9dd] rounded-[18px] p-5 text-xs space-y-3 shadow-none">
                <div class="flex justify-between items-center">
                  <span class="font-medium text-sm text-[#212121]">{kasbon.user?.name || kasbon.user_name || 'Staf'}</span>
                  <span class="font-mono text-base font-medium text-[#17171c]">{formatRp(kasbon.amount)}</span>
                </div>
                <div class="text-[11px] text-[#75758a] font-mono">Diajukan: {kasbon.request_date}</div>
                <div class="flex gap-2.5 pt-3 border-t border-[#d9d9dd]/60">
                  <button
                    type="button"
                    onclick={() => onRejectKasbon(kasbon.id)}
                    class="flex-1 py-2 bg-white hover:bg-[#ffad9b]/15 text-[#b30000] text-xs font-medium rounded-full cursor-pointer border border-[#d9d9dd] transition-all"
                  >
                    Tolak
                  </button>
                  <button
                    type="button"
                    onclick={() => onApproveKasbon(kasbon.id)}
                    class="flex-2 py-2 bg-[#17171c] hover:bg-[#000000] text-white text-xs font-medium rounded-full flex items-center justify-center gap-1 cursor-pointer transition-all shadow-none"
                  >
                    <Check class="w-3.5 h-3.5" />
                    <span>Cairkan Kasbon</span>
                  </button>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    </div>
  {:else}
    <!-- modul otomasi payroll -->
    <div class="space-y-4">
      <!-- bilah filter tanggal dan kontrol header -->
      <div class="bg-white border border-[#d9d9dd] rounded-[20px] p-5 shadow-none flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h3 class="text-base font-medium text-[#212121]">Rekapitulasi Penggajian Seluruh Karyawan</h3>
          <p class="text-xs text-[#616161] font-normal mt-0.5">
            {payrollPreview ? `Periode Aktif: ${payrollPreview.period_start} s/d ${payrollPreview.period_end}` : 'Kalkulasi otomatis presisi matematis'}
          </p>
        </div>

        <!-- formulir filter rentang tanggal periode -->
        <div class="flex flex-wrap items-center gap-2.5 text-xs font-mono">
          <div class="flex items-center gap-1.5 bg-[#eeece7]/40 p-1 rounded-full border border-[#d9d9dd]">
            <input
              type="date"
              bind:value={filterPeriodStart}
              class="bg-white border border-[#d9d9dd] rounded-full px-3 py-1 text-xs font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
            />
            <span class="text-[#75758a]">s/d</span>
            <input
              type="date"
              bind:value={filterPeriodEnd}
              class="bg-white border border-[#d9d9dd] rounded-full px-3 py-1 text-xs font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
            />
            <button
              type="button"
              disabled={isFiltering}
              onclick={handleFilterPeriod}
              class="px-3.5 py-1 bg-[#17171c] text-white text-xs font-medium rounded-full cursor-pointer hover:bg-[#000000] transition-all disabled:opacity-50 flex items-center gap-1"
            >
              <RefreshCw class={`w-3 h-3 ${isFiltering ? 'animate-spin' : ''}`} />
              <span>{isFiltering ? 'Memuat...' : 'Hitung'}</span>
            </button>
          </div>

          <div class="relative">
            <select
              bind:value={selectedBankFormat}
              class="appearance-none px-3 pr-7 py-1.5 bg-[#eeece7]/50 hover:bg-[#eeece7] border border-[#d9d9dd] rounded-full text-xs text-[#212121] font-mono focus:outline-hidden cursor-pointer transition-all shadow-2xs"
            >
              <option value="BCA">Format: BCA Payroll</option>
              <option value="MANDIRI">Format: Mandiri MCM</option>
            </select>
            <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>
      </div>

      {#if !payrollPreview || payrollPreview.items.length === 0}
        <div class="p-12 bg-white border border-[#d9d9dd] rounded-[22px] text-center space-y-2.5 shadow-none">
          <Users class="w-8 h-8 text-[#93939f] mx-auto opacity-50" />
          <h3 class="text-sm font-medium text-[#212121]">Belum Ada Data Rekapitulasi Payroll</h3>
          <p class="text-xs text-[#75758a]">Data payroll akan dihitung otomatis berdasarkan kehadiran dan catatan kasbon karyawan pada periode aktif.</p>
        </div>
      {:else}
        <!-- grid kartu ringkasan metrik finansial -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 font-mono text-xs">
          <div class="bg-white border border-[#d9d9dd] rounded-[16px] p-4 shadow-none">
            <div class="text-[#75758a] text-[10px] uppercase">Gaji Pokok ({totalMembersCount} Staf)</div>
            <div class="text-sm font-medium text-[#212121] mt-1">{formatRp(totalBaseSalary)}</div>
          </div>
          <div class="bg-white border border-[#d9d9dd] rounded-[16px] p-4 shadow-none">
            <div class="text-[#003c33] text-[10px] uppercase">+ Total Lembur</div>
            <div class="text-sm font-medium text-[#003c33] mt-1">+{formatRp(totalOvertimePay)}</div>
          </div>
          <div class="bg-white border border-[#d9d9dd] rounded-[16px] p-4 shadow-none">
            <div class="text-[#b30000] text-[10px] uppercase">- Total Denda Telat</div>
            <div class="text-sm font-medium text-[#b30000] mt-1">-{formatRp(totalLatePenalty)}</div>
          </div>
          <div class="bg-white border border-[#d9d9dd] rounded-[16px] p-4 shadow-none">
            <div class="text-[#b30000] text-[10px] uppercase">- Potongan Kasbon</div>
            <div class="text-sm font-medium text-[#b30000] mt-1">-{formatRp(totalCashAdvanceDeduction)}</div>
          </div>
          <div class="bg-[#eeece7] border border-[#d9d9dd] rounded-[16px] p-4 shadow-none col-span-2 sm:col-span-1">
            <div class="text-[#212121] text-[10px] uppercase font-medium">Total THP Bersih</div>
            <div class="text-sm font-medium text-[#17171c] mt-1">{formatRp(totalPayrollDisbursement)}</div>
          </div>
        </div>

        <!-- tabel rekapitulasi penggajian -->
        <div class="bg-white border border-[#d9d9dd] rounded-[22px] overflow-hidden shadow-none">
          <table class="w-full text-xs text-left border-collapse">
            <thead class="bg-[#eeece7]/50 border-b border-[#d9d9dd] font-mono text-[11px] text-[#616161]">
              <tr>
                <th class="p-4 font-medium">Nama Staf</th>
                <th class="p-4 font-medium">Gaji Pokok</th>
                <th class="p-4 font-medium">Lembur</th>
                <th class="p-4 font-medium">Potongan Telat</th>
                <th class="p-4 font-medium">Potongan Kasbon</th>
                <th class="p-4 font-medium text-right">Gaji Bersih (THP)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#d9d9dd]/60">
              {#each payrollPreview.items as p}
                <tr class="hover:bg-[#eeece7]/20 transition-colors">
                  <td class="p-4 font-medium text-[#212121]">
                    <div class="font-medium">{p.name}</div>
                    <div class="text-[10px] text-[#75758a] font-normal">{p.role} • {p.branch_name || 'Outlet'}</div>
                  </td>
                  <td class="p-4 font-mono">{formatRp(p.base_salary)}</td>
                  <td class="p-4 font-mono text-[#003c33]">+{formatRp(p.overtime_pay)}</td>
                  <td class="p-4 font-mono text-[#b30000]">-{formatRp(p.late_penalty)}</td>
                  <td class="p-4 font-mono text-[#b30000]">-{formatRp(p.cash_advance_deduction)}</td>
                  <td class="p-4 font-mono font-medium text-right text-sm text-[#17171c]">{formatRp(p.net_salary)}</td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>

        <!-- bilah ringkasan total dan aksi eksekusi -->
        <div class="p-6 bg-[#17171c] text-white rounded-[22px] border border-[#17171c] flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-none">
          <div>
            <div class="text-xs text-[#93939f]">Total Pengeluaran Payroll ({totalMembersCount} Karyawan):</div>
            <div class="text-2xl font-medium font-mono text-[#edfce9] mt-1">
              {formatRp(totalPayrollDisbursement)}
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <button
              type="button"
              disabled={isExporting}
              onclick={handleExport}
              class="bg-white/10 hover:bg-white/20 border border-white/20 text-white px-5 py-2.5 text-xs font-medium rounded-full flex items-center gap-2 cursor-pointer transition-all disabled:opacity-50"
            >
              <Download class="w-4 h-4 text-white" />
              <span>{isExporting ? 'Mengunduh...' : `Ekspor CSV (${selectedBankFormat})`}</span>
            </button>

            <button
              type="button"
              disabled={isDisbursing}
              onclick={() => (isConfirmDisburseOpen = true)}
              class="bg-white hover:bg-[#eeece7] text-[#17171c] px-6 py-2.5 text-xs font-medium rounded-full flex items-center gap-2 cursor-pointer transition-all disabled:opacity-50"
            >
              <Send class="w-4 h-4" />
              <span>{isDisbursing ? 'Memproses...' : 'Eksekusi Pencairan Gaji (Disburse)'}</span>
            </button>
          </div>
        </div>
      {/if}
    </div>
  {/if}
</div>

<!-- Modal Konfirmasi Pencairan Gaji (Disburse) -->
{#if isConfirmDisburseOpen && payrollPreview}
  <div class="fixed inset-0 z-50 bg-[#17171c]/40 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] max-w-md w-full p-6 shadow-none space-y-4 animate-in fade-in zoom-in-95 font-sans">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <h3 class="text-base font-medium text-[#212121] flex items-center gap-2">
          <ShieldCheck class="w-4 h-4 text-[#1863dc]" />
          <span>Konfirmasi Eksekusi Pencairan Gaji</span>
        </h3>
        <button type="button" onclick={() => (isConfirmDisburseOpen = false)} class="text-[#93939f] hover:text-[#212121] cursor-pointer p-1">✕</button>
      </div>

      <div class="space-y-3.5 text-xs">
        <p class="text-[#616161]">
          Aksi ini akan mencatat status penggajian resmi <strong class="text-[#212121]">DISBURSED</strong> untuk <strong class="text-[#212121]">{totalMembersCount} karyawan</strong> pada periode:
        </p>

        <div class="p-4 bg-[#eeece7]/40 border border-[#d9d9dd] rounded-[16px] font-mono space-y-1.5">
          <div class="flex justify-between">
            <span class="text-[#75758a]">Rentang Periode:</span>
            <span class="font-medium text-[#212121]">{payrollPreview.period_start} s/d {payrollPreview.period_end}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-[#75758a]">Total Dana Bersih:</span>
            <span class="font-medium text-[#003c33]">{formatRp(totalPayrollDisbursement)}</span>
          </div>
          <div class="flex justify-between text-[#b30000]">
            <span>Kasbon Dilunaskan:</span>
            <span class="font-medium">{formatRp(totalCashAdvanceDeduction)}</span>
          </div>
        </div>

        <div class="p-3 bg-[#edfce9] border border-[#edfce9] text-[#003c33] text-[11px] font-mono rounded-[12px]">
          Perhatian: Seluruh kasbon aktif yang terpotong akan otomatis beralih ke status DEDUCTED (Lunas).
        </div>

        <div class="pt-3 flex gap-2.5 border-t border-[#d9d9dd]">
          <button
            type="button"
            onclick={() => (isConfirmDisburseOpen = false)}
            class="flex-1 py-2.5 bg-white text-[#616161] hover:bg-[#eeece7]/40 border border-[#d9d9dd] rounded-full text-xs font-medium cursor-pointer transition-all"
          >
            Batal
          </button>
          <button
            type="button"
            onclick={handleDisburse}
            disabled={isDisbursing}
            class="flex-2 py-2.5 bg-[#17171c] hover:bg-[#000000] text-white text-xs font-medium rounded-full flex items-center justify-center gap-1.5 cursor-pointer transition-all disabled:opacity-50"
          >
            <Send class="w-3.5 h-3.5" />
            <span>{isDisbursing ? 'Memproses...' : 'Konfirmasi & Cairkan'}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}

<!-- Modal: Preview & Detail Foto Presensi Selfie + GPS Audit -->
{#if selectedAttendanceDetail}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl animate-in fade-in zoom-in-95">
      <!-- Modal Header -->
      <div class="p-4 sm:p-5 border-b border-[#d9d9dd] flex items-center justify-between bg-[#fafafa]">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-full bg-[#17171c] text-white flex items-center justify-center font-bold text-xs">
            {selectedAttendanceDetail.user_name?.charAt(0) || 'U'}
          </div>
          <div>
            <h3 class="text-sm font-semibold text-[#17171c]">{selectedAttendanceDetail.user_name}</h3>
            <p class="text-[11px] text-[#75758a] font-mono">{selectedAttendanceDetail.branch_name || 'Cabang'}</p>
          </div>
        </div>
        <button
          type="button"
          onclick={() => (selectedAttendanceDetail = null)}
          class="p-1.5 rounded-full hover:bg-[#eeece7] text-[#75758a] hover:text-[#17171c] transition-all cursor-pointer"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Photo & Metadata Body -->
      <div class="p-4 sm:p-5 space-y-4 max-h-[80vh] overflow-y-auto">
        <!-- Main High-Res Photo Container -->
        <div class="aspect-4/3 bg-[#eeece7] rounded-2xl overflow-hidden border border-[#d9d9dd] relative group">
          {#if selectedAttendanceDetail.photo_in_url || selectedAttendanceDetail.avatar_url}
            <img
              src={selectedAttendanceDetail.photo_in_url || selectedAttendanceDetail.avatar_url}
              alt={`Selfie ${selectedAttendanceDetail.user_name}`}
              class="w-full h-full object-cover"
            />
          {:else}
            <div class="w-full h-full flex flex-col items-center justify-center text-[#93939f] gap-2">
              <Camera class="w-10 h-10 opacity-40" />
              <span class="text-xs font-mono">Foto presensi tidak tersedia</span>
            </div>
          {/if}

          <!-- Status Badge Overlay -->
          <div class="absolute top-3 right-3">
            {#if !selectedAttendanceDetail.late_minutes || selectedAttendanceDetail.late_minutes === 0}
              <span class="text-xs font-mono font-medium px-3 py-1 rounded-full bg-[#edfce9] text-[#003c33] border border-[#bbf7d0] shadow-xs">
                TEPAT WAKTU
              </span>
            {:else}
              <span class="text-xs font-mono font-medium px-3 py-1 rounded-full bg-[#ffefef] text-[#e5484d] border border-[#fecaca] shadow-xs">
                TELAT {selectedAttendanceDetail.late_minutes} MENIT
              </span>
            {/if}
          </div>
        </div>

        <!-- Detail Metrics Strip -->
        <div class="grid grid-cols-2 gap-3 text-xs">
          <div class="p-3 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl space-y-0.5">
            <div class="text-[10px] font-mono uppercase text-[#75758a]">Waktu Masuk (Clock In)</div>
            <div class="font-mono font-medium text-[#17171c] text-sm">
              {selectedAttendanceDetail.clock_in_time}
            </div>
            <div class="text-[10px] text-[#75758a]">
              {selectedAttendanceDetail.shift_name ? `Shift: ${selectedAttendanceDetail.shift_name}` : 'Shift Reguler'}
            </div>
          </div>

          <div class="p-3 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl space-y-0.5">
            <div class="text-[10px] font-mono uppercase text-[#75758a]">Waktu Keluar (Clock Out)</div>
            <div class="font-mono font-medium text-[#17171c] text-sm">
              {selectedAttendanceDetail.clock_out_time ? selectedAttendanceDetail.clock_out_time : 'Sedang Bekerja'}
            </div>
            <div class="text-[10px] text-[#75758a]">
              {selectedAttendanceDetail.clock_out_time ? 'Selesai bertugas' : 'Belum clock out'}
            </div>
          </div>
        </div>

        <!-- Geolocation & GPS Coordinate Verification -->
        <div class="p-3.5 bg-[#fafafa] border border-[#d9d9dd] rounded-2xl space-y-2">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5 text-xs font-medium text-[#17171c]">
              <MapPin class="w-3.5 h-3.5 text-[#1863dc]" />
              <span>Verifikasi Geolocation GPS</span>
            </div>
            <span class="text-[10px] font-mono text-[#003c33] bg-[#edfce9] px-2 py-0.5 rounded-full border border-[#bbf7d0]">
              Radius Valid
            </span>
          </div>

          {#if selectedAttendanceDetail.lat_in && selectedAttendanceDetail.lng_in}
            <div class="flex items-center justify-between text-xs font-mono text-[#616161] pt-1">
              <span>Koordinat: {Number(selectedAttendanceDetail.lat_in).toFixed(6)}, {Number(selectedAttendanceDetail.lng_in).toFixed(6)}</span>
              <a
                href={`https://www.google.com/maps?q=${selectedAttendanceDetail.lat_in},${selectedAttendanceDetail.lng_in}`}
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1 text-[#1863dc] hover:underline font-sans text-xs"
              >
                <span>Buka Maps</span>
                <ExternalLink class="w-3 h-3" />
              </a>
            </div>
          {:else}
            <div class="text-xs text-[#75758a] font-mono">Koordinat GPS tidak terekam</div>
          {/if}
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="p-4 border-t border-[#d9d9dd] bg-[#fafafa] flex justify-end">
        <button
          type="button"
          onclick={() => (selectedAttendanceDetail = null)}
          class="px-5 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full transition-all cursor-pointer"
        >
          Tutup
        </button>
      </div>
    </div>
  </div>
{/if}
