<script lang="ts">
  import {
    Clock,
    Camera,
    AlertCircle,
    CheckCircle2,
    ArrowRight,
  } from 'lucide-svelte';
  import type { AttendanceRecord, ShiftRosterItem } from '../../../types/app';

  interface Props {
    todayShift: ShiftRosterItem | null;
    todayAttendance: AttendanceRecord | null;
    shiftCountdownText: string;
    onNavigatePresensi: () => void;
  }

  let {
    todayShift,
    todayAttendance,
    shiftCountdownText,
    onNavigatePresensi,
  }: Props = $props();
</script>

<div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 sm:p-5 space-y-4 shadow-none font-sans">
  <div class="flex items-center justify-between gap-2 border-b border-[#f2f2f2] pb-3">
    <div class="flex items-center gap-2.5">
      <div class="w-8 h-8 rounded-xl bg-[#17171c] text-white flex items-center justify-center">
        <Clock class="w-4 h-4" />
      </div>
      <div>
        <h2 class="text-xs sm:text-sm font-medium text-[#212121]">
          {todayShift?.template?.name || 'Shift Middle Barista'}
        </h2>
        <p class="text-[10px] font-mono text-[#75758a]">
          {todayShift?.template?.expected_clock_in || '07:00'} - {todayShift?.template?.expected_clock_out || '15:00'} WIB
        </p>
      </div>
    </div>

    <div>
      {#if !todayAttendance}
        <span class="inline-flex items-center gap-1 text-[10px] font-mono text-[#e5484d] bg-[#ffefef] px-2.5 py-1 rounded-full font-medium">
          <AlertCircle class="w-3 h-3" />
          <span>Belum Hadir</span>
        </span>
      {:else if !todayAttendance.clock_out_time}
        <span class="inline-flex items-center gap-1 text-[10px] font-mono text-[#1863dc] bg-[#f1f5ff] px-2.5 py-1 rounded-full font-medium">
          <CheckCircle2 class="w-3 h-3" />
          <span>Sedang Bekerja</span>
        </span>
      {:else}
        <span class="inline-flex items-center gap-1 text-[10px] font-mono text-[#00875a] bg-[#edfce9] px-2.5 py-1 rounded-full font-medium">
          <CheckCircle2 class="w-3 h-3" />
          <span>Selesai</span>
        </span>
      {/if}
    </div>
  </div>

  <!-- Countdown Bar -->
  {#if shiftCountdownText}
    <div class="flex items-center justify-between text-[11px] bg-[#fbfbfb] border border-[#d9d9dd]/60 px-3 py-2 rounded-xl">
      <span class="text-[#616161]">Waktu Shift:</span>
      <span class="font-mono font-medium text-[#17171c]">{shiftCountdownText}</span>
    </div>
  {/if}

  <!-- Shift Action CTA -->
  {#if !todayAttendance}
    <div class="space-y-3">
      <div class="text-[11px] text-[#616161] bg-[#eeece7]/30 border border-[#d9d9dd]/60 p-2.5 rounded-xl flex items-center justify-between">
        <span>Toleransi batas masuk: <strong>15 Menit</strong></span>
        <span class="font-mono text-[#e5484d] text-[10px]">Denda: Rp 2.000/mnt</span>
      </div>

      <button
        type="button"
        onclick={onNavigatePresensi}
        class="w-full py-3 bg-[#17171c] hover:bg-black active:scale-[0.99] text-white font-medium text-xs rounded-xl flex items-center justify-center gap-2 cursor-pointer transition-all shadow-none"
      >
        <Camera class="w-4 h-4" />
        <span>Buka Kamera Presensi Masuk</span>
        <ArrowRight class="w-3.5 h-3.5" />
      </button>
    </div>
  {:else if !todayAttendance.clock_out_time}
    <div class="space-y-3">
      <div class="p-3 bg-[#fbfbfb] border border-[#d9d9dd] rounded-xl flex items-center gap-3">
        <div class="w-12 aspect-3/4 rounded-lg overflow-hidden border border-[#d9d9dd] bg-[#17171c] shrink-0">
          <img
            src={todayAttendance.photo_in_url}
            alt="Selfie Presensi Masuk"
            class="w-full h-full object-cover"
          />
        </div>
        <div class="flex-1 text-xs min-w-0 space-y-0.5">
          <div class="font-medium text-[#212121] truncate">Presensi Masuk Tercatat</div>
          <div class="font-mono text-[#75758a] text-[10px]">Masuk: {todayAttendance.clock_in_time} WIB</div>
          <div class={`text-[10px] font-mono font-medium ${todayAttendance.status === 'ON_TIME' ? 'text-[#00875a]' : 'text-[#e5484d]'}`}>
            {todayAttendance.status === 'ON_TIME' ? 'Status: Tepat Waktu' : `Terlambat ${todayAttendance.late_minutes} menit`}
          </div>
        </div>
      </div>

      <button
        type="button"
        onclick={onNavigatePresensi}
        class="w-full py-3 bg-[#e5484d] hover:bg-[#c93b40] active:scale-[0.99] text-white font-medium text-xs rounded-xl flex items-center justify-center gap-2 cursor-pointer transition-all shadow-none"
      >
        <Camera class="w-4 h-4" />
        <span>Buka Kamera Presensi Keluar (Clock-Out)</span>
        <ArrowRight class="w-3.5 h-3.5" />
      </button>
    </div>
  {:else}
    <div class="p-3 bg-[#edfce9] border border-[#00875a]/20 rounded-xl flex items-center gap-3">
      <div class="w-12 aspect-3/4 rounded-lg overflow-hidden border border-[#00875a]/30 bg-[#17171c] shrink-0">
        <img
          src={todayAttendance.photo_out_url || todayAttendance.photo_in_url}
          alt="Selfie Presensi"
          class="w-full h-full object-cover"
        />
      </div>
      <div class="flex-1 text-xs min-w-0 space-y-0.5">
        <div class="font-medium text-[#003c33] truncate">Presensi Hari Ini Selesai</div>
        <div class="font-mono text-[#616161] text-[10px]">
          Masuk: {todayAttendance.clock_in_time} &bull; Keluar: {todayAttendance.clock_out_time}
        </div>
        <div class="text-[10px] font-mono text-[#00875a]">
          {todayAttendance.overtime_minutes ? `Lembur: ${todayAttendance.overtime_minutes} menit` : 'Jam kerja terpenuhi'}
        </div>
      </div>
    </div>
  {/if}
</div>
