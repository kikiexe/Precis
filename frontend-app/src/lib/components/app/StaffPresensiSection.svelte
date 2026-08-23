<script lang="ts">
  import { Camera, MapPin, Clock, CheckCircle2, AlertCircle, ChevronRight, LogOut } from 'lucide-svelte';
  import type { User, AttendanceRecord } from '../../types/app';

  interface Props {
    currentUser: User;
    todayAttendance: AttendanceRecord | null;
    onOpenLiveCamera: (actionType: 'CLOCK_IN' | 'CLOCK_OUT') => void;
    onOpenSlipModal: () => void;
    onOpenKasbonTab: () => void;
  }

  let {
    currentUser,
    todayAttendance,
    onOpenLiveCamera,
    onOpenSlipModal,
    onOpenKasbonTab,
  }: Props = $props();

  let liveTime = $state('');
  let todayDateStr = $state('');

  $effect(() => {
    const update = () => {
      const now = new Date();
      liveTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
      todayDateStr = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    };
    update();
    const interval = setInterval(update, 1000);
    return () => clearInterval(interval);
  });
</script>

<div class="space-y-6 max-w-6xl mx-auto p-4 md:p-8 pb-24 lg:pb-8">
  <!-- header sapaan staf dan jam live -->
  <div class="bg-[#161616] text-white p-6 border border-[#262626] shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <div class="flex items-center gap-3 text-xs text-[#8c8c8c] font-mono mb-2">
        <span>{todayDateStr}</span>
        <span class="text-[#0f62fe] bg-[#262626] px-2.5 py-0.5 border border-[#393939]">{liveTime} WIB</span>
      </div>

      <h1 class="text-2xl font-bold text-white font-display">
        Selamat Datang, {currentUser.name}
      </h1>
      <p class="text-xs text-[#c6c6c6] mt-1">
        Penempatan: <span class="font-semibold text-white">{currentUser.branch_name}</span> • Peran: <span class="font-mono text-[#0f62fe]">{currentUser.role}</span>
      </p>
    </div>

    <!-- status badge geofence -->
    <div class="flex items-center gap-2 text-xs font-mono bg-[#262626] p-3 border border-[#393939] text-[#24a148] self-start md:self-auto">
      <MapPin class="w-4 h-4 shrink-0" />
      <span>Geofence GPS Terverifikasi (Radius Maks 50m)</span>
    </div>
  </div>

  <!-- layout grid 2 kolom responsif -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- kolom kiri: jadwal shift dan tombol presensi selfie -->
    <div class="lg:col-span-2 space-y-6">
      <div class="bg-white border border-[#e0e0e0] p-6 space-y-5 shadow-xs">
        <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-4">
          <div class="flex items-center gap-2">
            <Clock class="w-5 h-5 text-[#0f62fe]" />
            <h2 class="text-sm font-bold text-[#161616] uppercase tracking-wide">Jadwal Shift Hari Ini</h2>
          </div>
          <span class="text-xs font-mono bg-[#f4f4f4] px-3 py-1 text-[#525252] border border-[#e0e0e0]">
            Shift Pagi (07:00 - 15:00 WIB)
          </span>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-[#f4f4f4] p-4 border border-[#e0e0e0]">
          <div>
            <div class="text-xl font-bold font-mono text-[#161616]">07:00 - 15:00 WIB</div>
            <div class="text-xs text-[#525252] mt-1">Toleransi keterlambatan 15 menit • Potongan denda Rp 2.000/menit</div>
          </div>
          <div>
            {#if !todayAttendance}
              <span class="inline-flex items-center gap-1.5 text-xs font-mono text-[#da1e28] bg-[#da1e28]/10 px-3 py-1.5 border border-[#da1e28]/30 font-semibold">
                <AlertCircle class="w-4 h-4" />
                <span>Belum Presensi Masuk</span>
              </span>
            {:else if !todayAttendance.clock_out_time}
              <span class="inline-flex items-center gap-1.5 text-xs font-mono text-[#0f62fe] bg-[#0f62fe]/10 px-3 py-1.5 border border-[#0f62fe]/30 font-semibold">
                <CheckCircle2 class="w-4 h-4" />
                <span>Shift Berjalan (Masuk: {todayAttendance.clock_in_time})</span>
              </span>
            {:else}
              <span class="inline-flex items-center gap-1.5 text-xs font-mono text-[#24a148] bg-[#24a148]/10 px-3 py-1.5 border border-[#24a148]/30 font-semibold">
                <CheckCircle2 class="w-4 h-4" />
                <span>Shift Selesai (Keluar: {todayAttendance.clock_out_time})</span>
              </span>
            {/if}
          </div>
        </div>

        <!-- tombol aksi selfie presensi -->
        {#if !todayAttendance}
          <button
            type="button"
            onclick={() => onOpenLiveCamera('CLOCK_IN')}
            class="w-full py-4 bg-[#0f62fe] hover:bg-[#0050e6] active:scale-[0.99] text-white font-semibold text-sm flex items-center justify-center gap-2.5 cursor-pointer shadow-md transition-all"
          >
            <Camera class="w-5 h-5" />
            <span>Buka Kamera &amp; Ambil Selfie Presensi Masuk</span>
          </button>
        {:else if !todayAttendance.clock_out_time}
          <div class="space-y-4">
            <div class="p-4 bg-[#f4f4f4] border border-[#e0e0e0] flex items-center gap-4">
              <img
                src={todayAttendance.photo_in_url}
                alt="Selfie Presensi Masuk"
                class="w-20 h-26 object-cover border border-[#e0e0e0] bg-black shrink-0"
              />
              <div class="flex-1 text-xs space-y-1">
                <div class="font-bold text-sm text-[#161616]">Presensi Masuk Berhasil Tersimpan</div>
                <div class="font-mono text-[#525252] text-xs">Foto WebP terenkripsi di Cloudflare R2 / S3 storage</div>
                <div class="text-xs text-[#24a148] font-mono font-medium">
                  Status: {todayAttendance.status === 'ON_TIME' ? 'Tepat Waktu' : `Terlambat ${todayAttendance.late_minutes} menit`}
                </div>
              </div>
            </div>

            <button
              type="button"
              onclick={() => onOpenLiveCamera('CLOCK_OUT')}
              class="w-full py-3.5 bg-[#da1e28] hover:bg-[#ba1b23] active:scale-[0.99] text-white font-semibold text-sm flex items-center justify-center gap-2.5 cursor-pointer shadow-md transition-all"
            >
              <LogOut class="w-5 h-5" />
              <span>Buka Kamera &amp; Ambil Selfie Presensi Keluar (Clock-Out)</span>
            </button>
          </div>
        {:else}
          <div class="p-4 bg-[#24a148]/10 border border-[#24a148]/30 flex items-center gap-4">
            <img
              src={todayAttendance.photo_out_url || todayAttendance.photo_in_url}
              alt="Selfie Presensi Keluar"
              class="w-20 h-26 object-cover border border-[#24a148]/30 bg-black shrink-0"
            />
            <div class="flex-1 text-xs space-y-1">
              <div class="font-bold text-sm text-[#24a148]">Presensi Hari Ini Telah Selesai</div>
              <div class="font-mono text-[#525252] text-xs">
                Masuk: {todayAttendance.clock_in_time} • Keluar: {todayAttendance.clock_out_time}
              </div>
              <div class="text-xs text-[#24a148] font-mono">
                {todayAttendance.overtime_minutes ? `Lembur: ${todayAttendance.overtime_minutes} menit` : 'Jam kerja terpenuhi normal'}
              </div>
            </div>
          </div>
        {/if}
      </div>
    </div>

    <!-- kolom kanan: metrik ringkas & pintasan -->
    <div class="space-y-4">
      <!-- kasbon card -->
      <button
        type="button"
        onclick={onOpenKasbonTab}
        class="w-full bg-white border border-[#e0e0e0] hover:border-[#0f62fe] p-5 text-left transition-colors cursor-pointer group shadow-xs space-y-2"
      >
        <div class="flex justify-between items-center text-xs font-mono text-[#525252]">
          <span>Sisa Kasbon Berjalan</span>
          <ChevronRight class="w-4 h-4 text-[#8c8c8c] group-hover:text-[#0f62fe]" />
        </div>
        <div class="text-2xl font-bold font-mono text-[#161616] group-hover:text-[#0f62fe]">Rp 150.000</div>
        <div class="text-xs text-[#8c8c8c]">Klik untuk mengajukan pinjaman baru</div>
      </button>

      <!-- slip gaji card -->
      <button
        type="button"
        onclick={onOpenSlipModal}
        class="w-full bg-white border border-[#e0e0e0] hover:border-[#0f62fe] p-5 text-left transition-colors cursor-pointer group shadow-xs space-y-2"
      >
        <div class="flex justify-between items-center text-xs font-mono text-[#525252]">
          <span>Estimasi Take Home Pay</span>
          <ChevronRight class="w-4 h-4 text-[#8c8c8c] group-hover:text-[#0f62fe]" />
        </div>
        <div class="text-2xl font-bold font-mono text-[#24a148]">Rp 3.120.000</div>
        <div class="text-xs text-[#8c8c8c]">Periode berjalan: Agustus 2026</div>
      </button>
    </div>
  </div>
</div>
