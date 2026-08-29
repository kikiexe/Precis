<script lang="ts">
  import { Clock, Camera, AlertCircle, CheckCircle2, ArrowRight } from 'lucide-svelte';
  import type { AttendanceRecord, ShiftRosterItem } from '../../../types/app';

  interface Props {
    todayShift: ShiftRosterItem | null;
    todayAttendance: AttendanceRecord | null;
    shiftCountdownText: string;
    onNavigatePresensi: () => void;
  }

  let { todayShift, todayAttendance, shiftCountdownText, onNavigatePresensi }: Props = $props();
</script>

<div
  class="space-y-5 rounded-2xl border border-[#e5e5ea] bg-white p-5 font-sans shadow-2xs sm:rounded-3xl sm:p-6"
>
  <div class="flex items-center justify-between gap-3 border-b border-[#f2f2f4] pb-4">
    <div class="flex min-w-0 flex-1 items-center gap-3">
      <div
        class="flex size-10 shrink-0 items-center justify-center rounded-2xl border border-[#e5e5ea] bg-[#f4f4f6] text-[#17171c]"
      >
        <Clock class="size-5" />
      </div>
      <div class="min-w-0">
        <h3 class="truncate text-sm font-bold text-[#17171c] sm:text-base">
          {todayShift?.template?.name || 'Shift Middle Barista'}
        </h3>
        <p class="mt-0.5 truncate font-mono text-xs text-[#8e8e93]">
          {todayShift?.template?.expected_clock_in?.substring(0, 5) || '07:00'} - {todayShift?.template?.expected_clock_out?.substring(
            0,
            5
          ) || '15:00'} WIB
        </p>
      </div>
    </div>

    <div class="shrink-0">
      {#if !todayAttendance}
        <span
          class="inline-flex items-center gap-1.5 rounded-full border border-[#fecaca] bg-[#fef2f2] px-3 py-1 font-mono text-xs font-semibold text-[#e5484d]"
        >
          <AlertCircle class="size-3.5" />
          <span>Belum Hadir</span>
        </span>
      {:else if !todayAttendance.clock_out_time}
        <span
          class="inline-flex items-center gap-1.5 rounded-full border border-[#bfdbfe] bg-[#eff6ff] px-3 py-1 font-mono text-xs font-semibold text-[#2563eb]"
        >
          <CheckCircle2 class="size-3.5" />
          <span>Sedang Bekerja</span>
        </span>
      {:else}
        <span
          class="inline-flex items-center gap-1.5 rounded-full border border-[#a7f3d0] bg-[#ecfdf5] px-3 py-1 font-mono text-xs font-semibold text-[#059669]"
        >
          <CheckCircle2 class="size-3.5" />
          <span>Selesai</span>
        </span>
      {/if}
    </div>
  </div>

  <!-- Countdown Bar -->
  {#if shiftCountdownText}
    <div
      class="flex items-center justify-between rounded-2xl border border-[#ececee] bg-[#f8f8fa] px-4 py-3 text-xs"
    >
      <span class="font-medium text-[#686873]">Status Jadwal Shift:</span>
      <span class="font-mono font-bold text-[#17171c]">{shiftCountdownText}</span>
    </div>
  {/if}

  <!-- Shift Action CTA -->
  {#if !todayAttendance}
    <div class="space-y-3.5">
      <div
        class="flex items-center justify-between rounded-2xl border border-[#ececee] bg-[#f8f8fa] p-3.5 text-xs text-[#686873]"
      >
        <span>Toleransi batas masuk: <strong class="text-[#17171c]">15 Menit</strong></span>
        <span class="font-mono text-xs font-semibold text-[#e5484d]">Denda: Rp 2.000/mnt</span>
      </div>

      <button
        type="button"
        onclick={onNavigatePresensi}
        class="active:scale-0.99 flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl bg-[#17171c] py-3.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
      >
        <Camera class="size-4" />
        <span>Buka Kamera Presensi Masuk</span>
        <ArrowRight class="size-4" />
      </button>
    </div>
  {:else if !todayAttendance.clock_out_time}
    <div class="space-y-3.5">
      <div class="flex items-center gap-3.5 rounded-2xl border border-[#e5e5ea] bg-[#f8f8fa] p-3.5">
        <div
          class="aspect-3/4 w-12 shrink-0 overflow-hidden rounded-xl border border-[#e5e5ea] bg-[#17171c]"
        >
          <img
            src={todayAttendance.photo_in_url}
            alt="Selfie Presensi Masuk"
            class="size-full object-cover"
          />
        </div>
        <div class="min-w-0 flex-1 space-y-0.5 text-xs">
          <div class="truncate font-bold text-[#17171c]">Presensi Masuk Tercatat</div>
          <div class="font-mono text-[11px] text-[#8e8e93]">
            Masuk: {todayAttendance.clock_in_time} WIB
          </div>
          {#if todayAttendance.late_minutes && todayAttendance.late_minutes > 0}
            <div class="font-mono text-[11px] font-semibold text-[#e5484d]">
              Terlambat: {todayAttendance.late_minutes} menit
            </div>
          {/if}
        </div>
      </div>

      <button
        type="button"
        onclick={onNavigatePresensi}
        class="active:scale-0.99 flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl bg-[#17171c] py-3.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
      >
        <Camera class="size-4" />
        <span>Buka Kamera Presensi Pulang</span>
        <ArrowRight class="size-4" />
      </button>
    </div>
  {:else}
    <div class="flex items-center gap-3 rounded-2xl border border-[#a7f3d0] bg-[#ecfdf5] p-4">
      <CheckCircle2 class="size-5 shrink-0 text-[#059669]" />
      <div class="space-y-0.5 text-xs text-[#065f46]">
        <div class="font-bold">Presensi Hari Ini Lengkap</div>
        <div class="font-mono text-[11px] text-[#047857]">
          Masuk: {todayAttendance.clock_in_time} WIB &bull; Pulang: {todayAttendance.clock_out_time} WIB
        </div>
      </div>
    </div>
  {/if}
</div>
