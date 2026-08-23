<script lang="ts">
  import { Check, Download, Users } from 'lucide-svelte';
  import type { AttendanceRecord, PendingSwapItem, CashAdvance } from '../../types/app';

  export interface PayrollEmployeeSummary {
    name: string;
    role: string;
    base: number;
    ot: number;
    late: number;
    kasbon: number;
    net: number;
  }

  interface Props {
    attendances?: AttendanceRecord[];
    pendingSwaps?: PendingSwapItem[];
    pendingKasbons?: CashAdvance[];
    payrollList?: PayrollEmployeeSummary[];
    onApproveSwap: (swapId: string) => void;
    onRejectSwap: (swapId: string) => void;
    onApproveKasbon: (kasbonId: string) => void;
    onRejectKasbon: (kasbonId: string) => void;
  }

  let {
    attendances = [],
    pendingSwaps = [],
    pendingKasbons = [],
    payrollList = [],
    onApproveSwap,
    onRejectSwap,
    onApproveKasbon,
    onRejectKasbon,
  }: Props = $props();

  let activeAdminTab = $state<'wall' | 'approvals' | 'payroll'>('wall');

  function formatRp(num: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);
  }

  let totalPayrollDisbursement = $derived(
    payrollList.reduce((sum, item) => sum + item.net, 0)
  );
</script>

<div class="space-y-6 max-w-6xl mx-auto p-4 md:p-8 pb-24 lg:pb-8">
  <!-- Top Navigation Subtabs -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#e0e0e0] pb-4">
    <div>
      <h2 class="text-xl font-bold text-[#161616] font-display">Dashboard Audit &amp; Kontrol Owner</h2>
      <p class="text-xs text-[#525252] font-mono">Pemeriksaan bukti foto kehadiran, otorisasi persetujuan, dan penggajian</p>
    </div>

    <!-- Admin Sub-tabs -->
    <div class="flex gap-1 bg-[#f4f4f4] p-1 border border-[#e0e0e0]">
      <button
        type="button"
        onclick={() => (activeAdminTab = 'wall')}
        class={`px-4 py-2 text-xs font-mono font-medium transition-colors cursor-pointer ${
          activeAdminTab === 'wall' ? 'bg-white text-[#0f62fe] font-bold shadow-xs' : 'text-[#525252] hover:text-[#161616]'
        }`}
      >
        Wall of Faces
      </button>

      <button
        type="button"
        onclick={() => (activeAdminTab = 'approvals')}
        class={`px-4 py-2 text-xs font-mono font-medium transition-colors cursor-pointer relative ${
          activeAdminTab === 'approvals' ? 'bg-white text-[#0f62fe] font-bold shadow-xs' : 'text-[#525252] hover:text-[#161616]'
        }`}
      >
        <span>Approval Hub</span>
        {#if pendingSwaps.length + pendingKasbons.length > 0}
          <span class="ml-1 px-1.5 py-0.2 bg-[#da1e28] text-white text-[9px] font-bold rounded-full">
            {pendingSwaps.length + pendingKasbons.length}
          </span>
        {/if}
      </button>

      <button
        type="button"
        onclick={() => (activeAdminTab = 'payroll')}
        class={`px-4 py-2 text-xs font-mono font-medium transition-colors cursor-pointer ${
          activeAdminTab === 'payroll' ? 'bg-white text-[#0f62fe] font-bold shadow-xs' : 'text-[#525252] hover:text-[#161616]'
        }`}
      >
        Otomasi Payroll
      </button>
    </div>
  </div>

  {#if activeAdminTab === 'wall'}
    <!-- Wall of Faces -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
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
              <!-- Staff Header -->
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

              <!-- Selfie Photo with Burned Watermark -->
              <div class="relative bg-black aspect-3/4">
                <img
                  src={att.photo_in_url}
                  alt={`Selfie ${att.user_name}`}
                  class="w-full h-full object-cover"
                />
              </div>

              <!-- Metadata Card Footer -->
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
    <!-- Approval Hub (Side-by-side 2 columns on desktop) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- 1. Pending Shift Swaps -->
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

      <!-- 2. Pending Kasbons -->
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
                  <span class="font-bold text-sm text-[#161616]">{kasbon.user_name}</span>
                  <span class="font-mono text-base font-bold text-[#0f62fe]">{formatRp(kasbon.amount)}</span>
                </div>
                <div class="text-[11px] text-[#525252]">Keperluan: "{kasbon.purpose}"</div>
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
    <!-- Otomasi Payroll Engine -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-base font-bold text-[#161616]">Rekapitulasi Penggajian Seluruh Karyawan</h3>
          <p class="text-xs text-[#525252] font-mono">Kalkulasi 1-Klik otomatis potong denda terlambat &amp; angsuran kasbon</p>
        </div>
      </div>

      {#if payrollList.length === 0}
        <div class="p-12 bg-white border border-[#e0e0e0] text-center space-y-2 shadow-xs">
          <Users class="w-8 h-8 text-[#8c8c8c] mx-auto opacity-40" />
          <h3 class="text-sm font-bold text-[#161616]">Belum Ada Data Rekapitulasi Payroll</h3>
          <p class="text-xs text-[#8c8c8c]">Data payroll akan dihitung otomatis berdasarkan kehadiran dan catatan kasbon karyawan pada periode aktif.</p>
        </div>
      {:else}
        <!-- Wide Payroll Table -->
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
              {#each payrollList as p}
                <tr class="hover:bg-[#f4f4f4]/60 transition-colors">
                  <td class="p-3.5 font-medium text-[#161616]">
                    <div class="font-bold">{p.name}</div>
                    <div class="text-[10px] text-[#8c8c8c] font-mono">{p.role}</div>
                  </td>
                  <td class="p-3.5 font-mono">{formatRp(p.base)}</td>
                  <td class="p-3.5 font-mono text-[#24a148]">+{formatRp(p.ot)}</td>
                  <td class="p-3.5 font-mono text-[#da1e28]">-{formatRp(p.late)}</td>
                  <td class="p-3.5 font-mono text-[#da1e28]">-{formatRp(p.kasbon)}</td>
                  <td class="p-3.5 font-mono font-bold text-right text-sm text-[#0f62fe]">{formatRp(p.net)}</td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>

        <!-- Total & Bank CSV Export Bar -->
        <div class="p-5 bg-[#161616] text-white border border-[#262626] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <div class="text-xs font-mono text-[#8c8c8c]">Total Pengeluaran Payroll Periode Ini:</div>
            <div class="text-2xl font-bold font-mono text-[#24a148] mt-1">
              {formatRp(totalPayrollDisbursement)}
            </div>
          </div>
          <button
            type="button"
            onclick={() => alert('File CSV payroll siap diimpor ke Portal Bank BCA / Mandiri MCM.')}
            class="bg-[#0f62fe] hover:bg-[#0050e6] text-white px-5 py-3 text-xs font-semibold flex items-center gap-2 cursor-pointer shadow-xs transition-colors self-start sm:self-auto"
          >
            <Download class="w-4 h-4" />
            <span>Ekspor CSV Batch Transfer Bank</span>
          </button>
        </div>
      {/if}
    </div>
  {/if}
</div>
