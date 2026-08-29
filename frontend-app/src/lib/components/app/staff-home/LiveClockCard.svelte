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

<div
  class="flex items-center justify-between gap-4 rounded-2xl border border-[#27272a] bg-[#17171c] p-4 font-sans text-white shadow-sm sm:rounded-3xl sm:p-5"
>
  <div class="flex min-w-0 items-center gap-3">
    <div
      class={`size-2.5 shrink-0 rounded-full ${hasWorkspace ? 'bg-[#10b981] ring-4 ring-[#10b981]/20' : 'bg-[#a1a1aa]'}`}
    ></div>
    <div class="truncate">
      <div class="truncate text-sm font-bold text-white sm:text-base">
        {currentUser.name}
      </div>
      <div class="mt-0.5 flex items-center gap-1.5 font-mono text-xs text-[#a1a1aa]">
        {#if hasWorkspace}
          <span class="font-bold text-[#10b981]">&bull;</span>
          <span class="truncate">Presensi Geofence Aktif (Radius 50m)</span>
        {:else}
          <span class="truncate">Akun Belum Terhubung ke Workspace</span>
        {/if}
      </div>
    </div>
  </div>

  <div class="shrink-0 text-right font-mono">
    <div class="text-sm font-bold text-white sm:text-base">
      {liveTime} <span class="text-xs font-normal text-[#a1a1aa]">WIB</span>
    </div>
    <div class="mt-0.5 text-[11px] text-[#a1a1aa]">{todayDateStr}</div>
  </div>
</div>
