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

<div class="space-y-6 max-w-6xl mx-auto p-4 sm:p-6 md:p-8 pb-24 lg:pb-8 font-sans">
  <!-- header sapaan staf dan jam live -->
  <div class="bg-[#003c33] text-white p-6 sm:p-8 rounded-[22px] border border-[#003c33] flex flex-col md:flex-row md:items-center md:justify-between gap-4 shadow-none">
    <div>
      <div class="flex items-center gap-3 text-xs text-[#d9d9dd] font-mono mb-2">
        <span>{todayDateStr}</span>
        <span class="text-[#ffffff] bg-[#17171c]/40 px-3 py-1 rounded-full border border-white/10 font-medium">{liveTime} WIB</span>
      </div>

      <h1 class="text-2xl font-medium text-white tracking-tight">
        Selamat Datang, {currentUser.name}
      </h1>
      <p class="text-xs text-[#d9d9dd] mt-1 font-normal">
        Penempatan: <span class="font-medium text-white">{currentUser.branch_name}</span> • Peran: <span class="font-mono text-[#edfce9]">{currentUser.role}</span>
      </p>
    </div>

    <!-- status badge geofence -->
    <div class="flex items-center gap-2 text-xs font-mono bg-[#17171c]/30 px-3.5 py-2.5 rounded-full border border-white/10 text-[#edfce9] self-start md:self-auto">
      <MapPin class="w-4 h-4 shrink-0 text-[#edfce9]" />
      <span>Geofence GPS Terverifikasi (Radius Maks 50m)</span>
    </div>
  </div>

  <!-- layout grid 2 kolom responsif -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- kolom kiri: jadwal shift dan tombol presensi selfie -->
    <div class="lg:col-span-2 space-y-6">
      <div class="bg-white border border-[#d9d9dd] rounded-[22px] p-6 space-y-5 shadow-none">
        <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-4">
          <div class="flex items-center gap-2.5">
            <Clock class="w-4 h-4 text-[#1863dc]" />
            <h2 class="text-sm font-medium text-[#212121]">Jadwal Shift Hari Ini</h2>
          </div>
          <span class="text-xs font-mono bg-[#eeece7] px-3 py-1 rounded-full text-[#616161]">
            Shift Pagi (07:00 - 15:00 WIB)
          </span>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-[#eeece7]/30 p-4 rounded-[16px] border border-[#d9d9dd]">
          <div>
            <div class="text-xl font-medium font-mono text-[#212121]">07:00 - 15:00 WIB</div>
            <div class="text-xs text-[#75758a] mt-1">Toleransi keterlambatan 15 menit • Potongan denda Rp 2.000/menit</div>
          </div>
          <div>
            {#if !todayAttendance}
              <span class="inline-flex items-center gap-1.5 text-xs font-mono text-[#b30000] bg-[#ffad9b]/20 px-3 py-1.5 rounded-full font-medium">
                <AlertCircle class="w-3.5 h-3.5" />
                <span>Belum Presensi Masuk</span>
              </span>
            {:else if !todayAttendance.clock_out_time}
              <span class="inline-flex items-center gap-1.5 text-xs font-mono text-[#1863dc] bg-[#f1f5ff] px-3 py-1.5 rounded-full font-medium">
                <CheckCircle2 class="w-3.5 h-3.5" />
                <span>Shift Berjalan (Masuk: {todayAttendance.clock_in_time})</span>
              </span>
            {:else}
              <span class="inline-flex items-center gap-1.5 text-xs font-mono text-[#003c33] bg-[#edfce9] px-3 py-1.5 rounded-full font-medium">
                <CheckCircle2 class="w-3.5 h-3.5" />
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
            class="w-full py-3.5 bg-[#17171c] hover:bg-[#000000] active:scale-[0.99] text-white font-medium text-xs rounded-full flex items-center justify-center gap-2.5 cursor-pointer transition-all shadow-none"
          >
            <Camera class="w-4 h-4" />
            <span>Buka Kamera &amp; Ambil Selfie Presensi Masuk</span>
          </button>
        {:else if !todayAttendance.clock_out_time}
          <div class="space-y-4">
            <div class="p-4 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[16px] flex items-center gap-4">
              <img
                src={todayAttendance.photo_in_url}
                alt="Selfie Presensi Masuk"
                class="w-20 h-26 object-cover rounded-[12px] border border-[#d9d9dd] bg-black shrink-0"
              />
              <div class="flex-1 text-xs space-y-1.5">
                <div class="font-medium text-sm text-[#212121]">Presensi Masuk Berhasil Tersimpan</div>
                <div class="font-mono text-[#75758a] text-[11px]">Foto WebP terenkripsi di Cloudflare R2 / S3 storage</div>
                <div class="text-xs text-[#003c33] font-mono font-medium">
                  Status: {todayAttendance.status === 'ON_TIME' ? 'Tepat Waktu' : `Terlambat ${todayAttendance.late_minutes} menit`}
                </div>
              </div>
            </div>

            <button
              type="button"
              onclick={() => onOpenLiveCamera('CLOCK_OUT')}
              class="w-full py-3.5 bg-[#b30000] hover:bg-[#800000] active:scale-[0.99] text-white font-medium text-xs rounded-full flex items-center justify-center gap-2.5 cursor-pointer transition-all shadow-none"
            >
              <LogOut class="w-4 h-4" />
              <span>Buka Kamera &amp; Ambil Selfie Presensi Keluar (Clock-Out)</span>
            </button>
          </div>
        {:else}
          <div class="p-4 bg-[#edfce9] border border-[#edfce9] rounded-[16px] flex items-center gap-4">
            <img
              src={todayAttendance.photo_out_url || todayAttendance.photo_in_url}
              alt="Selfie Presensi Keluar"
              class="w-20 h-26 object-cover rounded-[12px] border border-[#d9d9dd] bg-black shrink-0"
            />
            <div class="flex-1 text-xs space-y-1.5">
              <div class="font-medium text-sm text-[#003c33]">Presensi Hari Ini Telah Selesai</div>
              <div class="font-mono text-[#616161] text-[11px]">
                Masuk: {todayAttendance.clock_in_time} • Keluar: {todayAttendance.clock_out_time}
              </div>
              <div class="text-xs text-[#003c33] font-mono">
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
        class="w-full bg-white border border-[#d9d9dd] hover:border-[#17171c] rounded-[22px] p-6 text-left transition-all cursor-pointer group shadow-none space-y-2.5"
      >
        <div class="flex justify-between items-center text-xs text-[#75758a]">
          <span>Sisa Kasbon Berjalan</span>
          <ChevronRight class="w-4 h-4 text-[#93939f] group-hover:text-[#212121] transition-transform" />
        </div>
        <div class="text-2xl font-medium font-mono text-[#212121]">Rp 150.000</div>
        <div class="text-xs text-[#93939f]">Klik untuk mengajukan pinjaman baru</div>
      </button>

      <!-- slip gaji card -->
      <button
        type="button"
        onclick={onOpenSlipModal}
        class="w-full bg-white border border-[#d9d9dd] hover:border-[#17171c] rounded-[22px] p-6 text-left transition-all cursor-pointer group shadow-none space-y-2.5"
      >
        <div class="flex justify-between items-center text-xs text-[#75758a]">
          <span>Estimasi Take Home Pay</span>
          <ChevronRight class="w-4 h-4 text-[#93939f] group-hover:text-[#212121] transition-transform" />
        </div>
        <div class="text-2xl font-medium font-mono text-[#003c33]">Rp 3.120.000</div>
        <div class="text-xs text-[#93939f]">Periode berjalan: Agustus 2026</div>
      </button>
    </div>
  </div>
</div>
