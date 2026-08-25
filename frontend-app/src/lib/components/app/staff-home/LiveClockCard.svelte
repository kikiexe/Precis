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

<div class="bg-[#17171c] text-white p-3.5 sm:p-4 rounded-2xl flex items-center justify-between gap-3 shadow-none font-sans">
  <div class="flex items-center gap-2.5 min-w-0">
    <div class={`w-2 h-2 rounded-full shrink-0 ${hasWorkspace ? 'bg-[#00875a]' : 'bg-[#93939f]'}`}></div>
    <div class="truncate">
      <div class="text-xs sm:text-sm font-medium text-white truncate">
        {currentUser.name}
      </div>
      <div class="text-[10px] text-[#93939f] font-mono flex items-center gap-1">
        {#if hasWorkspace}
          <span class="text-[#00875a]">&bull;</span>
          <span class="truncate">Presensi Geofence Aktif (Radius 50m)</span>
        {:else}
          <span class="truncate">Akun Belum Terhubung ke Workspace</span>
        {/if}
      </div>
    </div>
  </div>

  <div class="text-right shrink-0 font-mono">
    <div class="text-xs sm:text-sm font-medium text-white">{liveTime} <span class="text-[10px] text-[#93939f]">WIB</span></div>
    <div class="text-[10px] text-[#93939f]">{todayDateStr}</div>
  </div>
</div>
