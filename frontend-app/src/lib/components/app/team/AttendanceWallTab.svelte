<script lang="ts">
  import { Camera, ChevronDown, Eye } from 'lucide-svelte';
  import type { AttendanceRecord } from '../../../types/app';
  import { formatDateTimeIndo } from '../../../utils/formatters';
  import AttendanceDetailModal from './modals/AttendanceDetailModal.svelte';

  interface Props {
    attendances: AttendanceRecord[];
    availableBranches: string[];
    selectedBranchFilter: string;
    onSelectBranchFilter: (branch: string) => void;
  }

  let {
    attendances = [],
    availableBranches = [],
    selectedBranchFilter = 'ALL',
    onSelectBranchFilter,
  }: Props = $props();

  let selectedAttendanceDetail = $state<AttendanceRecord | null>(null);

  let filteredAttendances = $derived(
    attendances.filter((att) => {
      if (selectedBranchFilter === 'ALL') return true;
      return att.branch_name && att.branch_name.toLowerCase().includes(selectedBranchFilter.toLowerCase());
    })
  );

  let onTimeCount = $derived(filteredAttendances.filter((a) => !a.late_minutes || a.late_minutes === 0).length);
  let lateCount = $derived(filteredAttendances.filter((a) => a.late_minutes && a.late_minutes > 0).length);

  function formatAttendanceTime(dateStr?: string) {
    if (!dateStr) return '-';
    if (dateStr.includes('T')) {
      return `${formatDateTimeIndo(dateStr)} WIB`;
    }
    return dateStr;
  }
</script>

<div class="space-y-4 font-sans">
  <!-- Dynamic Filter Bar -->
  <div class="bg-white border border-[#d9d9dd] rounded-2xl p-3 flex flex-wrap sm:flex-nowrap items-center justify-between gap-2.5 text-xs">
    <div class="flex items-center gap-3 font-mono shrink-0">
      <div class="flex items-center gap-1.5 font-medium text-[11px] text-[#00875a] whitespace-nowrap">
        <span class="w-2 h-2 rounded-full bg-[#00875a] shrink-0"></span>
        <span>Tepat: {onTimeCount}</span>
      </div>
      <div class="flex items-center gap-1.5 font-medium text-[11px] text-[#e5484d] whitespace-nowrap">
        <span class="w-2 h-2 rounded-full bg-[#e5484d] shrink-0"></span>
        <span>Telat: {lateCount}</span>
      </div>
    </div>

    {#if availableBranches.length > 0}
      <div class="relative shrink-0 max-w-[170px] sm:max-w-xs">
        <select
          value={selectedBranchFilter}
          onchange={(e) => onSelectBranchFilter(e.currentTarget.value)}
          class="appearance-none px-3 pr-7 py-1.5 bg-[#eeece7]/50 hover:bg-[#eeece7] border border-[#d9d9dd] rounded-full text-xs text-[#212121] font-mono focus:outline-hidden cursor-pointer transition-all shadow-2xs truncate"
        >
          <option value="ALL">Semua Cabang</option>
          {#each availableBranches as branch}
            <option value={branch}>{branch}</option>
          {/each}
        </select>
        <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
      </div>
    {/if}
  </div>

  <!-- Presensi Grid / Empty State -->
  {#if filteredAttendances.length === 0}
    <div class="bg-white border border-[#d9d9dd] rounded-3xl p-12 text-center text-[#93939f] space-y-2">
      <Camera class="w-8 h-8 mx-auto text-[#93939f] opacity-40" />
      <p class="text-xs font-medium text-[#17171c]">Belum ada aktivitas presensi selfie</p>
      <p class="text-[11px] text-[#75758a]">Foto selfie karyawan saat clock-in akan muncul di sini secara realtime.</p>
    </div>
  {:else}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
      {#each filteredAttendances as att}
        <button
          type="button"
          onclick={() => (selectedAttendanceDetail = att)}
          class="bg-white border border-[#d9d9dd] rounded-2xl overflow-hidden text-left group hover:border-[#17171c] hover:shadow-md transition-all cursor-pointer block w-full focus:outline-hidden"
        >
          <div class="aspect-4/3 bg-[#eeece7] relative overflow-hidden">
            {#if att.photo_in_url || att.avatar_url}
              <img src={att.photo_in_url || att.avatar_url} alt={att.user_name} class="w-full h-full object-cover group-hover:scale-105 transition-all" />
            {:else}
              <div class="w-full h-full flex items-center justify-center text-xs font-mono text-[#93939f]">Tanpa Foto</div>
            {/if}

            <!-- Status Badge: TEPAT WAKTU / TELAT X MNT -->
            <div class="absolute top-2 right-2 flex flex-col gap-1 items-end">
              {#if !att.late_minutes || att.late_minutes === 0}
                <span class="text-[10px] font-mono font-medium px-2 py-0.5 rounded-md bg-[#edfce9] text-[#003c33] border border-[#bbf7d0]">
                  TEPAT WAKTU
                </span>
              {:else}
                <span class="text-[10px] font-mono font-medium px-2 py-0.5 rounded-md bg-[#ffefef] text-[#e5484d] border border-[#fecaca]">
                  TELAT {att.late_minutes} MNT
                </span>
              {/if}
            </div>

            <!-- Hover Inspection Prompt -->
            <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
              <span class="px-2.5 py-1 bg-white/90 backdrop-blur-xs text-[#17171c] text-[10px] font-medium rounded-full shadow-xs flex items-center gap-1">
                <Eye class="w-3 h-3" />
                <span>Lihat Detail</span>
              </span>
            </div>

            <!-- GPS Watermark Overlay -->
            {#if att.lat_in && att.lng_in}
              <div class="absolute bottom-0 right-0 px-2 py-1 text-right">
                <div class="text-[8px] font-mono text-white drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)] leading-tight">
                  {formatAttendanceTime(att.clock_in_time)}
                </div>
                <div class="text-[7px] font-mono text-white/80 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)] leading-tight">
                  {Number(att.lat_in).toFixed(6)}, {Number(att.lng_in).toFixed(6)}
                </div>
              </div>
            {/if}
          </div>

          <div class="p-3">
            <div class="font-medium text-xs text-[#17171c] truncate">{att.user_name}</div>
            <div class="text-[11px] text-[#75758a] font-mono mt-0.5">{formatAttendanceTime(att.clock_in_time)}</div>
            <div class="text-[10px] text-[#93939f] truncate mt-1">{att.branch_name}</div>
          </div>
        </button>
      {/each}
    </div>
  {/if}
</div>

<AttendanceDetailModal
  attendance={selectedAttendanceDetail}
  onClose={() => (selectedAttendanceDetail = null)}
/>
