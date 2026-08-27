<script lang="ts">
  import type { User } from '../../../types/app';

  interface Props {
    currentUser: User;
    hasWorkspace: boolean;
    liveTime: string;
    todayDateStr: string;
  }

  let { currentUser, hasWorkspace, liveTime, todayDateStr }: Props = $props();
</script>

<div class="bg-[#17171c] text-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl flex items-center justify-between gap-4 shadow-sm font-sans border border-[#27272a]">
  <div class="flex items-center gap-3 min-w-0">
    <div class={`w-2.5 h-2.5 rounded-full shrink-0 ${hasWorkspace ? 'bg-[#10b981] ring-4 ring-[#10b981]/20' : 'bg-[#a1a1aa]'}`}></div>
    <div class="truncate">
      <div class="text-sm sm:text-base font-bold text-white truncate">
        {currentUser.name}
      </div>
      <div class="text-xs text-[#a1a1aa] font-mono flex items-center gap-1.5 mt-0.5">
        {#if hasWorkspace}
          <span class="text-[#10b981] font-bold">&bull;</span>
          <span class="truncate">Presensi Geofence Aktif (Radius 50m)</span>
        {:else}
          <span class="truncate">Akun Belum Terhubung ke Workspace</span>
        {/if}
      </div>
    </div>
  </div>

  <div class="text-right shrink-0 font-mono">
    <div class="text-sm sm:text-base font-bold text-white">{liveTime} <span class="text-xs text-[#a1a1aa] font-normal">WIB</span></div>
    <div class="text-[11px] text-[#a1a1aa] mt-0.5">{todayDateStr}</div>
  </div>
</div>
