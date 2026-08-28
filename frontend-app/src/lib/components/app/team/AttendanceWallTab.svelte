<script lang="ts">
  import { Camera, Eye } from 'lucide-svelte';
  import type { AttendanceRecord } from '../../../types/app';
  import AttendanceDetailModal from './modals/AttendanceDetailModal.svelte';

  interface Props {
    attendances: AttendanceRecord[];
    selectedBranchFilter?: string;
  }

  let { attendances = [], selectedBranchFilter = 'ALL' }: Props = $props();

  let selectedAttendanceDetail = $state<AttendanceRecord | null>(null);

  let filteredAttendances = $derived(
    attendances.filter((att) => {
      if (selectedBranchFilter === 'ALL') return true;
      return (
        att.branch_name &&
        att.branch_name.toLowerCase().includes(selectedBranchFilter.toLowerCase())
      );
    })
  );

  let onTimeCount = $derived(
    filteredAttendances.filter((a) => !a.late_minutes || a.late_minutes === 0).length
  );
  let lateCount = $derived(
    filteredAttendances.filter((a) => a.late_minutes && a.late_minutes > 0).length
  );
</script>

<div class="space-y-6 font-sans">
  <!-- Status Counter Header Strip -->
  <div
    class="flex flex-col justify-between gap-4 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs sm:flex-row sm:items-center sm:rounded-3xl sm:p-6"
  >
    <div class="space-y-1">
      <h2 class="text-base font-bold text-[#17171c] sm:text-lg">
        Wall of Faces &amp; Presensi Selfie
      </h2>
      <p class="text-xs text-[#8e8e93]">
        Verifikasi visual kehadiran staf saat clock-in dengan foto selfie dan deteksi lokasi.
      </p>
    </div>

    <div class="flex items-center gap-3">
      <div
        class="inline-flex items-center gap-3 rounded-full border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2 font-mono text-xs"
      >
        <div class="flex items-center gap-1.5 font-semibold text-[#059669]">
          <span class="h-2 w-2 rounded-full bg-[#059669]"></span>
          <span>Tepat Waktu: {onTimeCount}</span>
        </div>
        <span class="text-[#d1d1d6]">&bull;</span>
        <div class="flex items-center gap-1.5 font-semibold text-[#e5484d]">
          <span class="h-2 w-2 rounded-full bg-[#e5484d]"></span>
          <span>Terlambat: {lateCount}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Presensi Gallery Grid (3:4 Portrait Aspect Ratio) -->
  {#if filteredAttendances.length === 0}
    <div
      class="space-y-3 rounded-2xl border border-[#e5e5ea] bg-white p-12 text-center shadow-2xs sm:rounded-3xl"
    >
      <div
        class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f4f4f6] text-[#8e8e93]"
      >
        <Camera class="h-6 w-6" />
      </div>
      <div>
        <h3 class="text-sm font-bold text-[#17171c]">Belum Ada Presensi Hari Ini</h3>
        <p class="mx-auto mt-1 max-w-sm text-xs text-[#8e8e93]">
          Foto selfie karyawan saat clock-in akan muncul di sini secara realtime.
        </p>
      </div>
    </div>
  {:else}
    <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-5">
      {#each filteredAttendances as att}
        <button
          type="button"
          onclick={() => (selectedAttendanceDetail = att)}
          class="group block w-full cursor-pointer overflow-hidden rounded-2xl border border-[#e5e5ea] bg-white text-left shadow-2xs transition-all hover:border-[#17171c]/40 hover:shadow-md focus:outline-hidden"
        >
          <!-- 3:4 Aspect Ratio Photo Container with Clean Floating Pill -->
          <div class="relative aspect-[3/4] overflow-hidden bg-[#f4f4f6]">
            {#if att.photo_in_url || att.avatar_url}
              <img
                src={att.photo_in_url || att.avatar_url}
                alt={att.user_name}
                class="h-full w-full object-cover transition-all duration-300 group-hover:scale-105"
              />
            {:else}
              <div
                class="flex h-full w-full items-center justify-center font-mono text-xs text-[#8e8e93]"
              >
                Tanpa Foto
              </div>
            {/if}

            <!-- Status Badge -->
            <div class="absolute top-2.5 right-2.5">
              {#if !att.late_minutes || att.late_minutes === 0}
                <span
                  class="rounded-full border border-white/20 bg-black/60 px-2 py-0.5 font-mono text-[9.5px] font-semibold text-[#34d399] shadow-xs backdrop-blur-md"
                >
                  Tepat Waktu
                </span>
              {:else}
                <span
                  class="rounded-full border border-white/20 bg-black/60 px-2 py-0.5 font-mono text-[9.5px] font-semibold text-[#f87171] shadow-xs backdrop-blur-md"
                >
                  Telat {att.late_minutes}m
                </span>
              {/if}
            </div>

            <!-- Hover Preview Overlay -->
            <div
              class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity group-hover:opacity-100"
            >
              <span
                class="flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-xs font-semibold text-[#17171c] shadow-xs backdrop-blur-xs"
              >
                <Eye class="h-3.5 w-3.5" />
                <span>Lihat Detail</span>
              </span>
            </div>
          </div>

          <!-- Card Bottom Info -->
          <div class="space-y-1 bg-white p-3">
            <div class="truncate text-xs font-bold text-[#17171c]">{att.user_name}</div>
            <div class="flex items-center justify-between font-mono text-[11px] text-[#686873]">
              <span>{att.branch_name || 'Outlet'}</span>
              <span class="text-[#8e8e93]"
                >{att.clock_in_time ? att.clock_in_time.substring(11, 16) : '-'}</span
              >
            </div>
          </div>
        </button>
      {/each}
    </div>
  {/if}
</div>

<!-- Attendance Detail Modal -->
{#if selectedAttendanceDetail}
  <AttendanceDetailModal
    attendance={selectedAttendanceDetail}
    onClose={() => (selectedAttendanceDetail = null)}
  />
{/if}
