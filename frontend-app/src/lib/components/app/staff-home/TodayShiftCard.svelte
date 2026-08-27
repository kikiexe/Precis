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

<div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 space-y-5 shadow-2xs font-sans">
  <div class="flex items-center justify-between gap-3 border-b border-[#f2f2f4] pb-4">
    <div class="flex items-center gap-3 min-w-0 flex-1">
      <div class="w-10 h-10 rounded-2xl bg-[#f4f4f6] text-[#17171c] flex items-center justify-center border border-[#e5e5ea] shrink-0">
        <Clock class="w-5 h-5" />
      </div>
      <div class="min-w-0">
        <h3 class="text-sm sm:text-base font-bold text-[#17171c] truncate">
          {todayShift?.template?.name || 'Shift Middle Barista'}
        </h3>
        <p class="text-xs font-mono text-[#8e8e93] mt-0.5 truncate">
          {todayShift?.template?.expected_clock_in?.substring(0, 5) || '07:00'} - {todayShift?.template?.expected_clock_out?.substring(0, 5) || '15:00'} WIB
        </p>
      </div>
    </div>

    <div class="shrink-0">
      {#if !todayAttendance}
        <span class="inline-flex items-center gap-1.5 text-xs font-mono text-[#e5484d] bg-[#fef2f2] border border-[#fecaca] px-3 py-1 rounded-full font-semibold">
          <AlertCircle class="w-3.5 h-3.5" />
          <span>Belum Hadir</span>
        </span>
      {:else if !todayAttendance.clock_out_time}
        <span class="inline-flex items-center gap-1.5 text-xs font-mono text-[#2563eb] bg-[#eff6ff] border border-[#bfdbfe] px-3 py-1 rounded-full font-semibold">
          <CheckCircle2 class="w-3.5 h-3.5" />
          <span>Sedang Bekerja</span>
        </span>
      {:else}
        <span class="inline-flex items-center gap-1.5 text-xs font-mono text-[#059669] bg-[#ecfdf5] border border-[#a7f3d0] px-3 py-1 rounded-full font-semibold">
          <CheckCircle2 class="w-3.5 h-3.5" />
          <span>Selesai</span>
        </span>
      {/if}
    </div>
  </div>

  <!-- Countdown Bar -->
  {#if shiftCountdownText}
    <div class="flex items-center justify-between text-xs bg-[#f8f8fa] border border-[#ececee] px-4 py-3 rounded-2xl">
      <span class="text-[#686873] font-medium">Status Jadwal Shift:</span>
      <span class="font-mono font-bold text-[#17171c]">{shiftCountdownText}</span>
    </div>
  {/if}

  <!-- Shift Action CTA -->
  {#if !todayAttendance}
    <div class="space-y-3.5">
      <div class="text-xs text-[#686873] bg-[#f8f8fa] border border-[#ececee] p-3.5 rounded-2xl flex items-center justify-between">
        <span>Toleransi batas masuk: <strong class="text-[#17171c]">15 Menit</strong></span>
        <span class="font-mono text-[#e5484d] text-xs font-semibold">Denda: Rp 2.000/mnt</span>
      </div>

      <button
        type="button"
        onclick={onNavigatePresensi}
        class="w-full py-3.5 bg-[#17171c] hover:bg-black active:scale-[0.99] text-white font-semibold text-xs rounded-2xl flex items-center justify-center gap-2 cursor-pointer transition-all shadow-xs"
      >
        <Camera class="w-4 h-4" />
        <span>Buka Kamera Presensi Masuk</span>
        <ArrowRight class="w-4 h-4" />
      </button>
    </div>
  {:else if !todayAttendance.clock_out_time}
    <div class="space-y-3.5">
      <div class="p-3.5 bg-[#f8f8fa] border border-[#e5e5ea] rounded-2xl flex items-center gap-3.5">
        <div class="w-12 aspect-[3/4] rounded-xl overflow-hidden border border-[#e5e5ea] bg-[#17171c] shrink-0">
          <img
            src={todayAttendance.photo_in_url}
            alt="Selfie Presensi Masuk"
            class="w-full h-full object-cover"
          />
        </div>
        <div class="flex-1 text-xs min-w-0 space-y-0.5">
          <div class="font-bold text-[#17171c] truncate">Presensi Masuk Tercatat</div>
          <div class="font-mono text-[#8e8e93] text-[11px]">Masuk: {todayAttendance.clock_in_time} WIB</div>
          {#if todayAttendance.late_minutes && todayAttendance.late_minutes > 0}
            <div class="text-[#e5484d] font-mono font-semibold text-[11px]">
              Terlambat: {todayAttendance.late_minutes} menit
            </div>
          {/if}
        </div>
      </div>

      <button
        type="button"
        onclick={onNavigatePresensi}
        class="w-full py-3.5 bg-[#17171c] hover:bg-black active:scale-[0.99] text-white font-semibold text-xs rounded-2xl flex items-center justify-center gap-2 cursor-pointer transition-all shadow-xs"
      >
        <Camera class="w-4 h-4" />
        <span>Buka Kamera Presensi Pulang</span>
        <ArrowRight class="w-4 h-4" />
      </button>
    </div>
  {:else}
    <div class="p-4 bg-[#ecfdf5] border border-[#a7f3d0] rounded-2xl flex items-center gap-3">
      <CheckCircle2 class="w-5 h-5 text-[#059669] shrink-0" />
      <div class="text-xs text-[#065f46] space-y-0.5">
        <div class="font-bold">Presensi Hari Ini Lengkap</div>
        <div class="text-[11px] font-mono text-[#047857]">
          Masuk: {todayAttendance.clock_in_time} WIB &bull; Pulang: {todayAttendance.clock_out_time} WIB
        </div>
      </div>
    </div>
  {/if}
</div>
