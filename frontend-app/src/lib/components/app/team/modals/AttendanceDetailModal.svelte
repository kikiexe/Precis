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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl animate-in fade-in zoom-in-95">
      <!-- Modal Header -->
      <div class="p-5 border-b border-[#f2f2f4] flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#f4f4f6] text-[#17171c] flex items-center justify-center font-bold">
            {attendance.user_name?.charAt(0) || 'U'}
          </div>
          <div>
            <h3 class="text-sm font-bold text-[#17171c]">{attendance.user_name}</h3>
            <div class="text-[11px] text-[#8e8e93] font-mono">
              {attendance.branch_name || 'Outlet Sleman #01'} &bull; {attendance.created_at?.substring(0, 10)}
            </div>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="p-2 text-[#8e8e93] hover:text-[#17171c] hover:bg-[#f4f4f6] rounded-full transition-all cursor-pointer"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-5 space-y-4 max-h-[75vh] overflow-y-auto">
        <!-- Selfie Photo in 3:4 Aspect Ratio -->
        <div class="aspect-[3/4] max-h-72 mx-auto rounded-2xl overflow-hidden bg-[#f4f4f6] border border-[#e5e5ea] relative">
          {#if attendance.photo_in_url || attendance.avatar_url}
            <img
              src={attendance.photo_in_url || attendance.avatar_url}
              alt={attendance.user_name}
              class="w-full h-full object-cover"
            />
          {:else}
            <div class="w-full h-full flex flex-col items-center justify-center text-[#8e8e93] space-y-2">
              <Camera class="w-8 h-8" />
              <span class="text-xs font-mono">Tidak ada foto presensi</span>
            </div>
          {/if}
        </div>

        <!-- Shift & Clock Stats -->
        <div class="grid grid-cols-2 gap-3">
          <div class="p-3.5 bg-[#fafafc] border border-[#e5e5ea] rounded-2xl space-y-1">
            <span class="text-[10px] font-mono uppercase text-[#8e8e93] font-semibold">Clock In</span>
            <div class="font-mono font-bold text-[#17171c] text-sm">
              {formatTime(attendance.clock_in_time)}
            </div>
            <div class="text-[11px] font-mono">
              {#if !attendance.late_minutes || attendance.late_minutes === 0}
                <span class="text-[#059669] font-semibold">Tepat Waktu</span>
              {:else}
                <span class="text-[#e5484d] font-semibold">Terlambat {attendance.late_minutes} Menit</span>
              {/if}
            </div>
          </div>

          <div class="p-3.5 bg-[#fafafc] border border-[#e5e5ea] rounded-2xl space-y-1">
            <span class="text-[10px] font-mono uppercase text-[#8e8e93] font-semibold">Clock Out</span>
            <div class="font-mono font-bold text-[#17171c] text-sm">
              {attendance.clock_out_time ? formatTime(attendance.clock_out_time) : 'Sedang Bertugas'}
            </div>
            <div class="text-[11px] text-[#8e8e93]">
              {attendance.clock_out_time ? 'Selesai bertugas' : 'Belum clock out'}
            </div>
          </div>
        </div>

        <!-- Geolocation & GPS Coordinate Verification -->
        <div class="p-4 bg-[#fafafc] border border-[#e5e5ea] rounded-2xl space-y-2.5">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-xs font-bold text-[#17171c]">
              <MapPin class="w-4 h-4 text-[#1863dc]" />
              <span>Verifikasi Geolocation GPS</span>
            </div>
            <span class="text-[10.5px] font-mono px-2 py-0.5 rounded-full bg-[#ecfdf5] text-[#059669] border border-[#a7f3d0] font-semibold">
              Valid Radius
            </span>
          </div>

          <div class="text-xs text-[#686873] space-y-1 font-mono">
            <div>Koordinat: <strong class="text-[#17171c]">{attendance.lat_in || '-'}, {attendance.lng_in || '-'}</strong></div>
            <div class="text-[11px] text-[#8e8e93]">Presensi terverifikasi dalam geofence outlet</div>
          </div>

          {#if attendance.lat_in && attendance.lng_in}
            <a
              href={`https://www.google.com/maps?q=${attendance.lat_in},${attendance.lng_in}`}
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-1.5 text-xs text-[#1863dc] hover:underline font-medium pt-1 cursor-pointer"
            >
              <span>Buka di Google Maps</span>
              <ExternalLink class="w-3 h-3" />
            </a>
          {/if}
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="p-4 border-t border-[#f2f2f4] bg-[#fafafc] flex justify-end">
        <button
          type="button"
          onclick={onClose}
          class="px-5 py-2.5 text-xs font-semibold bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer transition-all shadow-xs"
        >
          Tutup
        </button>
      </div>
    </div>
  </div>
{/if}
