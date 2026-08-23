<script lang="ts">
  import { Check, Download, Users, Send, RefreshCw, ShieldCheck } from 'lucide-svelte';
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

<div class="space-y-6 max-w-6xl mx-auto p-4 md:p-8 pb-24 lg:pb-8">
  <!-- navigasi subtab kontrol admin -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#e0e0e0] pb-4">
    <div>
      <h2 class="text-xl font-bold text-[#161616] font-display">Dashboard Audit &amp; Kontrol Owner</h2>
      <p class="text-xs text-[#525252] font-mono">Pemeriksaan bukti foto kehadiran, otorisasi persetujuan, dan penggajian</p>
    </div>

    <!-- subtab navigasi admin -->
    <div class="flex gap-1 bg-[#f4f4f4] p-1 border border-[#e0e0e0]">
      <button
        type="button"
        onclick={() => (activeAdminTab = 'wall')}
        class={`px-3 py-1.5 text-xs font-semibold cursor-pointer transition-colors ${
          activeAdminTab === 'wall'
            ? 'bg-[#0f62fe] text-white shadow-xs'
            : 'text-[#525252] hover:text-[#161616]'
        }`}
      >
        Wall of Faces ({attendances.length})
      </button>

      <button
        type="button"
        onclick={() => (activeAdminTab = 'approvals')}
        class={`px-3 py-1.5 text-xs font-semibold cursor-pointer transition-colors relative ${
          activeAdminTab === 'approvals'
            ? 'bg-[#0f62fe] text-white shadow-xs'
            : 'text-[#525252] hover:text-[#161616]'
        }`}
      >
        Persetujuan ({pendingSwaps.length + pendingKasbons.length})
        {#if (pendingSwaps.length + pendingKasbons.length) > 0}
          <span class="absolute -top-1 -right-1 w-2 h-2 bg-[#da1e28] rounded-full"></span>
        {/if}
      </button>

      <button
        type="button"
        onclick={() => (activeAdminTab = 'payroll')}
        class={`px-3 py-1.5 text-xs font-semibold cursor-pointer transition-colors ${
          activeAdminTab === 'payroll'
            ? 'bg-[#0f62fe] text-white shadow-xs'
            : 'text-[#525252] hover:text-[#161616]'
        }`}
      >
        Penggajian (Payroll)
      </button>
    </div>
  </div>

  {#if actionMessage}
    <div class="p-3 bg-[#0f62fe]/10 border border-[#0f62fe]/30 text-[#0f62fe] text-xs font-mono flex items-center justify-between">
      <span>{actionMessage}</span>
      <button type="button" onclick={() => (actionMessage = null)} class="text-[#525252] hover:text-[#161616] cursor-pointer">✕</button>
    </div>
  {/if}

  <!-- konten tab aktif -->
  {#if activeAdminTab === 'wall'}
    <!-- feed audit wall of faces -->
    <div class="space-y-4">
      <div class="flex items-center justify-between bg-white p-4 border border-[#e0e0e0] shadow-xs">
        <div class="text-xs font-mono text-[#525252]">
          Menampilkan <strong class="text-[#161616]">{attendances.length}</strong> foto selfie presensi terverifikasi GPS hari ini
        </div>
        <span class="text-[10px] font-mono text-[#24a148] bg-[#24a148]/10 px-2 py-1 border border-[#24a148]/30 font-semibold">
          Kebijakan Retensi 60 Hari
        </span>
      </div>

      {#if attendances.length === 0}
        <div class="p-12 bg-white border border-[#e0e0e0] text-center space-y-2 shadow-xs">
          <Users class="w-8 h-8 text-[#8c8c8c] mx-auto opacity-40" />
          <h3 class="text-sm font-bold text-[#161616]">Belum Ada Presensi Masuk Hari Ini</h3>
          <p class="text-xs text-[#8c8c8c]">Foto selfie dan koordinat GPS staf yang melakukan clock-in akan muncul di sini secara realtime.</p>
        </div>
      {:else}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {#each attendances as att (att.id)}
            <div class="bg-white border border-[#e0e0e0] overflow-hidden shadow-xs hover:border-[#0f62fe] transition-colors group">
              <!-- header informasi staf -->
              <div class="p-3 border-b border-[#e0e0e0] flex items-center justify-between bg-[#f4f4f4]">
                <div>
                  <div class="font-bold text-xs text-[#161616]">{att.user_name}</div>
                  <div class="text-[10px] font-mono text-[#525252]">{att.branch_name}</div>
                </div>
                <span class={`text-[9px] font-mono px-2 py-0.5 border font-semibold ${
                  att.status === 'ON_TIME'
                    ? 'bg-[#24a148]/10 text-[#24a148] border-[#24a148]/30'
                    : 'bg-[#da1e28]/10 text-[#da1e28] border-[#da1e28]/30'
                }`}>
                  {att.status === 'ON_TIME' ? 'Tepat Waktu' : `Telat ${att.late_minutes} Mnt`}
                </span>
              </div>

              <!-- foto selfie dengan watermark canvas -->
              <div class="relative bg-black aspect-3/4">
                <img
                  src={att.photo_in_url}
                  alt={`Selfie ${att.user_name}`}
                  class="w-full h-full object-cover"
                />
              </div>

              <!-- footer metadata kartu presensi -->
              <div class="p-3 text-xs space-y-1 text-[#525252] font-mono bg-white">
                <div class="flex justify-between">
                  <span>Waktu Masuk:</span>
                  <span class="font-bold text-[#161616]">{att.clock_in_time}</span>
                </div>
                <div class="flex justify-between text-[11px] text-[#8c8c8c]">
                  <span>Audit Geofence:</span>
                  <span>Lat: {att.lat_in.toFixed(4)}, Lng: {att.lng_in.toFixed(4)}</span>
                </div>
              </div>
            </div>
          {/each}
        </div>
      {/if}
    </div>
  {:else if activeAdminTab === 'approvals'}
    <!-- pusat persetujuan (tampilan 2 kolom di desktop) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- 1. antrean pengajuan tukar shift -->
      <div class="space-y-3">
        <h3 class="text-xs font-mono font-bold text-[#525252] uppercase tracking-wider">
          1. Pengajuan Tukar Shift ({pendingSwaps.length})
        </h3>
        {#if pendingSwaps.length === 0}
          <div class="p-8 bg-white border border-[#e0e0e0] text-center text-xs text-[#8c8c8c] shadow-xs">
            Tidak ada permohonan tukar shift yang tertunda.
          </div>
        {:else}
          <div class="space-y-3">
            {#each pendingSwaps as swap}
              <div class="bg-white border border-[#e0e0e0] p-4 text-xs space-y-2.5 shadow-xs">
                <div class="font-semibold text-sm text-[#161616]">
                  {swap.assigned_user?.name || 'Staf'} ➔ {swap.actual_user?.name || 'Pengganti'}
                </div>
                <div class="text-[11px] text-[#525252] font-mono">
                  Tanggal: {swap.date} ({swap.template?.name || 'Shift'})
                </div>
                <div class="text-[11px] text-[#8c8c8c]">
                  Jam: {swap.template?.expected_clock_in || '07:00'} - {swap.template?.expected_clock_out || '15:00'} WIB
                </div>
                <div class="flex gap-2 pt-2 border-t border-[#f4f4f4]">
                  <button
                    type="button"
                    onclick={() => onRejectSwap(swap.id)}
                    class="flex-1 py-2 bg-[#f4f4f4] hover:bg-[#e0e0e0] text-[#da1e28] text-xs font-semibold cursor-pointer border border-[#e0e0e0]"
                  >
                    Tolak
                  </button>
                  <button
                    type="button"
                    onclick={() => onApproveSwap(swap.id)}
                    class="flex-2 py-2 bg-[#24a148] hover:bg-[#1e8a3d] text-white text-xs font-semibold flex items-center justify-center gap-1 cursor-pointer shadow-xs"
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
        <h3 class="text-xs font-mono font-bold text-[#525252] uppercase tracking-wider">
          2. Otorisasi Pencairan Kasbon ({pendingKasbons.length})
        </h3>
        {#if pendingKasbons.length === 0}
          <div class="p-8 bg-white border border-[#e0e0e0] text-center text-xs text-[#8c8c8c] shadow-xs">
            Tidak ada permohonan kasbon yang tertunda.
          </div>
        {:else}
          <div class="space-y-3">
            {#each pendingKasbons as kasbon}
              <div class="bg-white border border-[#e0e0e0] p-4 text-xs space-y-2.5 shadow-xs">
                <div class="flex justify-between items-center">
                  <span class="font-bold text-sm text-[#161616]">{kasbon.user?.name || kasbon.user_name || 'Staf'}</span>
                  <span class="font-mono text-base font-bold text-[#0f62fe]">{formatRp(kasbon.amount)}</span>
                </div>
                <div class="text-[11px] text-[#525252] font-mono">Diajukan: {kasbon.request_date}</div>
                <div class="flex gap-2 pt-2 border-t border-[#f4f4f4]">
                  <button
                    type="button"
                    onclick={() => onRejectKasbon(kasbon.id)}
                    class="flex-1 py-2 bg-[#f4f4f4] hover:bg-[#e0e0e0] text-[#da1e28] text-xs font-semibold cursor-pointer border border-[#e0e0e0]"
                  >
                    Tolak
                  </button>
                  <button
                    type="button"
                    onclick={() => onApproveKasbon(kasbon.id)}
                    class="flex-2 py-2 bg-[#0f62fe] hover:bg-[#0050e6] text-white text-xs font-semibold flex items-center justify-center gap-1 cursor-pointer shadow-xs"
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
      <div class="bg-white border border-[#e0e0e0] p-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h3 class="text-base font-bold text-[#161616]">Rekapitulasi Penggajian Seluruh Karyawan</h3>
          <p class="text-xs text-[#525252] font-mono">
            {payrollPreview ? `Periode Aktif: ${payrollPreview.period_start} s/d ${payrollPreview.period_end}` : 'Kalkulasi otomatis presisi matematis'}
          </p>
        </div>

        <!-- formulir filter rentang tanggal periode -->
        <div class="flex flex-wrap items-center gap-2 text-xs font-mono">
          <div class="flex items-center gap-1.5 bg-[#f4f4f4] p-1 border border-[#e0e0e0]">
            <input
              type="date"
              bind:value={filterPeriodStart}
              class="bg-white border border-[#8c8c8c] px-2 py-1 text-xs font-mono text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
            />
            <span class="text-[#525252]">s/d</span>
            <input
              type="date"
              bind:value={filterPeriodEnd}
              class="bg-white border border-[#8c8c8c] px-2 py-1 text-xs font-mono text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
            />
            <button
              type="button"
              disabled={isFiltering}
              onclick={handleFilterPeriod}
              class="px-2.5 py-1 bg-[#0f62fe] text-white text-xs font-semibold cursor-pointer hover:bg-[#0050e6] transition-colors disabled:opacity-50 flex items-center gap-1"
            >
              <RefreshCw class={`w-3 h-3 ${isFiltering ? 'animate-spin' : ''}`} />
              <span>{isFiltering ? 'Memuat...' : 'Hitung Ulang'}</span>
            </button>
          </div>

          <select
            bind:value={selectedBankFormat}
            class="border border-[#8c8c8c] bg-white p-1.5 text-xs font-mono text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
          >
            <option value="BCA">Format CSV: BCA Payroll</option>
            <option value="MANDIRI">Format CSV: Mandiri MCM</option>
          </select>
        </div>
      </div>

      {#if !payrollPreview || payrollPreview.items.length === 0}
        <div class="p-12 bg-white border border-[#e0e0e0] text-center space-y-2 shadow-xs">
          <Users class="w-8 h-8 text-[#8c8c8c] mx-auto opacity-40" />
          <h3 class="text-sm font-bold text-[#161616]">Belum Ada Data Rekapitulasi Payroll</h3>
          <p class="text-xs text-[#8c8c8c]">Data payroll akan dihitung otomatis berdasarkan kehadiran dan catatan kasbon karyawan pada periode aktif.</p>
        </div>
      {:else}
        <!-- grid kartu ringkasan metrik finansial -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 font-mono text-xs">
          <div class="bg-white border border-[#e0e0e0] p-3 shadow-xs">
            <div class="text-[#8c8c8c] text-[10px] uppercase">Gaji Pokok ({totalMembersCount} Staf)</div>
            <div class="text-sm font-bold text-[#161616] mt-0.5">{formatRp(totalBaseSalary)}</div>
          </div>
          <div class="bg-white border border-[#e0e0e0] p-3 shadow-xs">
            <div class="text-[#24a148] text-[10px] uppercase">+ Total Lembur</div>
            <div class="text-sm font-bold text-[#24a148] mt-0.5">+{formatRp(totalOvertimePay)}</div>
          </div>
          <div class="bg-white border border-[#e0e0e0] p-3 shadow-xs">
            <div class="text-[#da1e28] text-[10px] uppercase">- Total Denda Telat</div>
            <div class="text-sm font-bold text-[#da1e28] mt-0.5">-{formatRp(totalLatePenalty)}</div>
          </div>
          <div class="bg-white border border-[#e0e0e0] p-3 shadow-xs">
            <div class="text-[#da1e28] text-[10px] uppercase">- Potongan Kasbon</div>
            <div class="text-sm font-bold text-[#da1e28] mt-0.5">-{formatRp(totalCashAdvanceDeduction)}</div>
          </div>
          <div class="bg-[#0f62fe]/10 border border-[#0f62fe]/30 p-3 shadow-xs col-span-2 sm:col-span-1">
            <div class="text-[#0f62fe] text-[10px] uppercase font-bold">Total THP Bersih</div>
            <div class="text-sm font-bold text-[#0f62fe] mt-0.5">{formatRp(totalPayrollDisbursement)}</div>
          </div>
        </div>

        <!-- tabel rekapitulasi penggajian -->
        <div class="bg-white border border-[#e0e0e0] overflow-x-auto shadow-xs">
          <table class="w-full text-xs text-left">
            <thead class="bg-[#f4f4f4] border-b border-[#e0e0e0] font-mono text-[11px] text-[#525252]">
              <tr>
                <th class="p-3.5">Nama Staf</th>
                <th class="p-3.5">Gaji Pokok</th>
                <th class="p-3.5">Lembur</th>
                <th class="p-3.5">Potongan Telat</th>
                <th class="p-3.5">Potongan Kasbon</th>
                <th class="p-3.5 text-right">Gaji Bersih (THP)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#f4f4f4]">
              {#each payrollPreview.items as p}
                <tr class="hover:bg-[#f4f4f4]/60 transition-colors">
                  <td class="p-3.5 font-medium text-[#161616]">
                    <div class="font-bold">{p.name}</div>
                    <div class="text-[10px] text-[#8c8c8c] font-mono">{p.role} • {p.branch_name || 'Outlet'}</div>
                  </td>
                  <td class="p-3.5 font-mono">{formatRp(p.base_salary)}</td>
                  <td class="p-3.5 font-mono text-[#24a148]">+{formatRp(p.overtime_pay)}</td>
                  <td class="p-3.5 font-mono text-[#da1e28]">-{formatRp(p.late_penalty)}</td>
                  <td class="p-3.5 font-mono text-[#da1e28]">-{formatRp(p.cash_advance_deduction)}</td>
                  <td class="p-3.5 font-mono font-bold text-right text-sm text-[#0f62fe]">{formatRp(p.net_salary)}</td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>

        <!-- bilah ringkasan total dan aksi eksekusi -->
        <div class="p-5 bg-[#161616] text-white border border-[#262626] flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="text-xs font-mono text-[#8c8c8c]">Total Pengeluaran Payroll ({totalMembersCount} Karyawan):</div>
            <div class="text-2xl font-bold font-mono text-[#24a148] mt-1">
              {formatRp(totalPayrollDisbursement)}
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2.5">
            <button
              type="button"
              disabled={isExporting}
              onclick={handleExport}
              class="bg-white hover:bg-[#f4f4f4] text-[#161616] px-4 py-2.5 text-xs font-semibold flex items-center gap-2 cursor-pointer shadow-xs transition-colors disabled:opacity-50"
            >
              <Download class="w-4 h-4 text-[#0f62fe]" />
              <span>{isExporting ? 'Mengunduh...' : `Ekspor CSV (${selectedBankFormat})`}</span>
            </button>

            <button
              type="button"
              disabled={isDisbursing}
              onclick={() => (isConfirmDisburseOpen = true)}
              class="bg-[#0f62fe] hover:bg-[#0050e6] text-white px-5 py-2.5 text-xs font-semibold flex items-center gap-2 cursor-pointer shadow-xs transition-colors disabled:opacity-50"
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
  <div class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
    <div class="bg-white border border-[#e0e0e0] max-w-md w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3">
        <h3 class="text-sm font-bold text-[#161616] flex items-center gap-2">
          <ShieldCheck class="w-4 h-4 text-[#0f62fe]" />
          <span>Konfirmasi Eksekusi Pencairan Gaji</span>
        </h3>
        <button type="button" onclick={() => (isConfirmDisburseOpen = false)} class="text-[#8c8c8c] hover:text-[#161616] cursor-pointer">✕</button>
      </div>

      <div class="space-y-3 text-xs">
        <p class="text-[#525252]">
          Aksi ini akan mencatat status penggajian resmi <strong class="text-[#161616]">DISBURSED</strong> untuk <strong class="text-[#161616]">{totalMembersCount} karyawan</strong> pada periode:
        </p>

        <div class="p-3 bg-[#f4f4f4] border border-[#e0e0e0] font-mono space-y-1">
          <div class="flex justify-between">
            <span class="text-[#525252]">Rentang Periode:</span>
            <span class="font-bold text-[#161616]">{payrollPreview.period_start} s/d {payrollPreview.period_end}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-[#525252]">Total Dana Bersih:</span>
            <span class="font-bold text-[#24a148]">{formatRp(totalPayrollDisbursement)}</span>
          </div>
          <div class="flex justify-between text-[#da1e28]">
            <span>Kasbon Dilunaskan:</span>
            <span class="font-bold">{formatRp(totalCashAdvanceDeduction)}</span>
          </div>
        </div>

        <div class="p-3 bg-[#f1c21b]/10 border border-[#f1c21b]/30 text-[#8a6d00] text-[11px] font-mono">
          Perhatian: Seluruh kasbon aktif yang terpotong akan otomatis beralih ke status DEDUCTED (Lunas).
        </div>

        <div class="pt-2 flex gap-2">
          <button
            type="button"
            onclick={() => (isConfirmDisburseOpen = false)}
            class="flex-1 py-2.5 bg-[#f4f4f4] text-[#525252] border border-[#e0e0e0] text-xs font-semibold cursor-pointer"
          >
            Batal
          </button>
          <button
            type="button"
            onclick={handleDisburse}
            disabled={isDisbursing}
            class="flex-2 py-2.5 bg-[#0f62fe] hover:bg-[#0050e6] text-white text-xs font-semibold flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <Send class="w-3.5 h-3.5" />
            <span>{isDisbursing ? 'Memproses...' : 'Konfirmasi & Cairkan'}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}
