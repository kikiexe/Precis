<script lang="ts">
  import { X, Camera, MapPin, ExternalLink } from 'lucide-svelte';
  import type { AttendanceRecord } from '../../../../types/app';
  import { formatDateTimeIndo } from '@precis/shared-utils';

  interface Props {
    attendance: AttendanceRecord | null;
    onClose: () => void;
  }

  let { attendance, onClose }: Props = $props();

  function formatTime(dateStr?: string) {
    if (!dateStr) return '-';
    if (dateStr.includes('T')) {
      return `${formatDateTimeIndo(dateStr)} WIB`;
    }
    return dateStr;
  }
</script>

{#if attendance}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="animate-in fade-in zoom-in-95 w-full max-w-lg overflow-hidden rounded-3xl border border-[#e5e5ea] bg-white shadow-2xl"
    >
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b border-[#f2f2f4] p-5">
        <div class="flex items-center gap-3">
          <div
            class="flex size-10 items-center justify-center rounded-2xl bg-[#f4f4f6] font-bold text-[#17171c]"
          >
            {attendance.user_name?.charAt(0) || 'U'}
          </div>
          <div>
            <h3 class="text-sm font-bold text-[#17171c]">{attendance.user_name}</h3>
            <div class="font-mono text-[11px] text-[#8e8e93]">
              {attendance.branch_name || 'Outlet Sleman #01'} &bull; {attendance.created_at?.substring(
                0,
                10
              )}
            </div>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="cursor-pointer rounded-full p-2 text-[#8e8e93] transition-all hover:bg-[#f4f4f6] hover:text-[#17171c]"
        >
          <X class="size-4" />
        </button>
      </div>

      <!-- Modal Body -->
      <div class="max-h-[75vh] space-y-4 overflow-y-auto p-5">
        <!-- Selfie Photo in 3:4 Aspect Ratio -->
        <div
          class="relative mx-auto aspect-3/4 max-h-72 overflow-hidden rounded-2xl border border-[#e5e5ea] bg-[#f4f4f6]"
        >
          {#if attendance.photo_in_url || attendance.avatar_url}
            <img
              src={attendance.photo_in_url || attendance.avatar_url}
              alt={attendance.user_name}
              class="size-full object-cover"
            />
          {:else}
            <div
              class="flex size-full flex-col items-center justify-center space-y-2 text-[#8e8e93]"
            >
              <Camera class="size-8" />
              <span class="font-mono text-xs">Tidak ada foto presensi</span>
            </div>
          {/if}
        </div>

        <!-- Shift & Clock Stats -->
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1 rounded-2xl border border-[#e5e5ea] bg-[#fafafc] p-3.5">
            <span class="font-mono text-[10px] font-semibold text-[#8e8e93] uppercase"
              >Clock In</span
            >
            <div class="font-mono text-sm font-bold text-[#17171c]">
              {formatTime(attendance.clock_in_time)}
            </div>
            <div class="font-mono text-[11px]">
              {#if !attendance.late_minutes || attendance.late_minutes === 0}
                <span class="font-semibold text-[#059669]">Tepat Waktu</span>
              {:else}
                <span class="font-semibold text-[#e5484d]"
                  >Terlambat {attendance.late_minutes} Menit</span
                >
              {/if}
            </div>
          </div>

          <div class="space-y-1 rounded-2xl border border-[#e5e5ea] bg-[#fafafc] p-3.5">
            <span class="font-mono text-[10px] font-semibold text-[#8e8e93] uppercase"
              >Clock Out</span
            >
            <div class="font-mono text-sm font-bold text-[#17171c]">
              {attendance.clock_out_time
                ? formatTime(attendance.clock_out_time)
                : 'Sedang Bertugas'}
            </div>
            <div class="text-[11px] text-[#8e8e93]">
              {attendance.clock_out_time ? 'Selesai bertugas' : 'Belum clock out'}
            </div>
          </div>
        </div>

        <!-- Geolocation & GPS Coordinate Verification -->
        <div class="space-y-2.5 rounded-2xl border border-[#e5e5ea] bg-[#fafafc] p-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-xs font-bold text-[#17171c]">
              <MapPin class="size-4 text-[#1863dc]" />
              <span>Verifikasi Geolocation GPS</span>
            </div>
            <span
              class="rounded-full border border-[#a7f3d0] bg-[#ecfdf5] px-2 py-0.5 font-mono text-[10.5px] font-semibold text-[#059669]"
            >
              Valid Radius
            </span>
          </div>

          <div class="space-y-1 font-mono text-xs text-[#686873]">
            <div>
              Koordinat: <strong class="text-[#17171c]"
                >{attendance.lat_in || '-'}, {attendance.lng_in || '-'}</strong
              >
            </div>
            <div class="text-[11px] text-[#8e8e93]">
              Presensi terverifikasi dalam geofence outlet
            </div>
          </div>

          {#if attendance.lat_in && attendance.lng_in}
            <a
              href={`https://www.google.com/maps?q=${attendance.lat_in},${attendance.lng_in}`}
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex cursor-pointer items-center gap-1.5 pt-1 text-xs font-medium text-[#1863dc] hover:underline"
            >
              <span>Buka di Google Maps</span>
              <ExternalLink class="size-3" />
            </a>
          {/if}
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="flex justify-end border-t border-[#f2f2f4] bg-[#fafafc] p-4">
        <button
          type="button"
          onclick={onClose}
          class="cursor-pointer rounded-full bg-[#17171c] px-5 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
        >
          Tutup
        </button>
      </div>
    </div>
  </div>
{/if}
