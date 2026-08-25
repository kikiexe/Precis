<script lang="ts">
  import { X, Camera, MapPin, ExternalLink } from 'lucide-svelte';
  import type { AttendanceRecord } from '../../../../types/app';
  import { formatDateTimeIndo } from '../../../../utils/formatters';

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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl animate-in fade-in zoom-in-95">
      <!-- Modal Header -->
      <div class="p-4 sm:p-5 border-b border-[#d9d9dd] flex items-center justify-between bg-[#fafafa]">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-full bg-[#17171c] text-white flex items-center justify-center font-bold text-xs">
            {attendance.user_name?.charAt(0) || 'U'}
          </div>
          <div>
            <h3 class="text-sm font-semibold text-[#17171c]">{attendance.user_name}</h3>
            <p class="text-[11px] text-[#75758a] font-mono">{attendance.branch_name || 'Cabang'}</p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="p-1.5 rounded-full hover:bg-[#eeece7] text-[#75758a] hover:text-[#17171c] transition-all cursor-pointer"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Photo & Metadata Body -->
      <div class="p-4 sm:p-5 space-y-4 max-h-[80vh] overflow-y-auto">
        <!-- Main High-Res Photo Container -->
        <div class="aspect-4/3 bg-[#eeece7] rounded-2xl overflow-hidden border border-[#d9d9dd] relative group">
          {#if attendance.photo_in_url || attendance.avatar_url}
            <img
              src={attendance.photo_in_url || attendance.avatar_url}
              alt={`Selfie ${attendance.user_name}`}
              class="w-full h-full object-cover"
            />
          {:else}
            <div class="w-full h-full flex flex-col items-center justify-center text-[#93939f] gap-2">
              <Camera class="w-10 h-10 opacity-40" />
              <span class="text-xs font-mono">Foto presensi tidak tersedia</span>
            </div>
          {/if}

          <!-- Status Badge Overlay -->
          <div class="absolute top-3 right-3">
            {#if !attendance.late_minutes || attendance.late_minutes === 0}
              <span class="text-xs font-mono font-medium px-3 py-1 rounded-full bg-[#edfce9] text-[#003c33] border border-[#bbf7d0] shadow-xs">
                TEPAT WAKTU
              </span>
            {:else}
              <span class="text-xs font-mono font-medium px-3 py-1 rounded-full bg-[#ffefef] text-[#e5484d] border border-[#fecaca] shadow-xs">
                TELAT {attendance.late_minutes} MENIT
              </span>
            {/if}
          </div>
        </div>

        <!-- Detail Metrics Strip -->
        <div class="grid grid-cols-2 gap-3 text-xs">
          <div class="p-3 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl space-y-0.5">
            <div class="text-[10px] font-mono uppercase text-[#75758a]">Waktu Masuk (Clock In)</div>
            <div class="font-mono font-medium text-[#17171c] text-sm">
              {formatTime(attendance.clock_in_time)}
            </div>
            <div class="text-[10px] text-[#75758a]">
              {attendance.shift_name ? `Shift: ${attendance.shift_name}` : 'Shift Reguler'}
            </div>
          </div>

          <div class="p-3 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl space-y-0.5">
            <div class="text-[10px] font-mono uppercase text-[#75758a]">Waktu Keluar (Clock Out)</div>
            <div class="font-mono font-medium text-[#17171c] text-sm">
              {attendance.clock_out_time ? formatTime(attendance.clock_out_time) : 'Sedang Bekerja'}
            </div>
            <div class="text-[10px] text-[#75758a]">
              {attendance.clock_out_time ? 'Selesai bertugas' : 'Belum clock out'}
            </div>
          </div>
        </div>

        <!-- Geolocation & GPS Coordinate Verification -->
        <div class="p-3.5 bg-[#fafafa] border border-[#d9d9dd] rounded-2xl space-y-2">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5 text-xs font-medium text-[#17171c]">
              <MapPin class="w-3.5 h-3.5 text-[#1863dc]" />
              <span>Verifikasi Geolocation GPS</span>
            </div>
            <span class="text-[10px] font-mono text-[#003c33] bg-[#edfce9] px-2 py-0.5 rounded-full border border-[#bbf7d0]">
              Radius Valid
            </span>
          </div>

          {#if attendance.lat_in && attendance.lng_in}
            <div class="flex items-center justify-between text-xs font-mono text-[#616161] pt-1">
              <span>Koordinat: {Number(attendance.lat_in).toFixed(6)}, {Number(attendance.lng_in).toFixed(6)}</span>
              <a
                href={`https://www.google.com/maps?q=${attendance.lat_in},${attendance.lng_in}`}
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1 text-[#1863dc] hover:underline font-sans text-xs"
              >
                <span>Buka Maps</span>
                <ExternalLink class="w-3 h-3" />
              </a>
            </div>
          {:else}
            <div class="text-xs text-[#75758a] font-mono">Koordinat GPS tidak terekam</div>
          {/if}
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="p-4 border-t border-[#d9d9dd] bg-[#fafafa] flex justify-end">
        <button
          type="button"
          onclick={onClose}
          class="px-5 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full transition-all cursor-pointer"
        >
          Tutup
        </button>
      </div>
    </div>
  </div>
{/if}
