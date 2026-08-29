<script lang="ts">
  import { Calendar, ChevronRight } from 'lucide-svelte';
  import type { ShiftRosterItem, User } from '../../../types/app';

  interface Props {
    currentUser: User;
    rosterShifts: ShiftRosterItem[];
    onNavigateShift: () => void;
  }

  let { currentUser, rosterShifts = [], onNavigateShift }: Props = $props();

  interface DaySchedule {
    dateIso: string;
    dayName: string;
    dayDate: string;
    isToday: boolean;
    isOff: boolean;
    shiftName: string;
    clockIn: string;
    clockOut: string;
    isSwap: boolean;
  }

  let next7DaysSchedule = $derived.by<DaySchedule[]>(() => {
    const list: DaySchedule[] = [];
    const base = new Date();

    for (let i = 0; i < 7; i++) {
      const targetDate = new Date(base);
      targetDate.setDate(base.getDate() + i);

      const y = targetDate.getFullYear();
      const m = String(targetDate.getMonth() + 1).padStart(2, '0');
      const d = String(targetDate.getDate()).padStart(2, '0');
      const isoStr = `${y}-${m}-${d}`;

      const matchedShift = rosterShifts.find(
        (s) =>
          s.date === isoStr &&
          (s.assigned_user.id === currentUser.id || s.actual_user?.id === currentUser.id)
      );

      const dayName = targetDate.toLocaleDateString('id-ID', { weekday: 'short' });
      const dayDate = targetDate.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });

      if (matchedShift) {
        list.push({
          dateIso: isoStr,
          dayName,
          dayDate,
          isToday: i === 0,
          isOff: false,
          shiftName: matchedShift.template?.name || 'Shift Reguler',
          clockIn: matchedShift.template?.expected_clock_in || '07:00',
          clockOut: matchedShift.template?.expected_clock_out || '15:00',
          isSwap: !!matchedShift.is_swap,
        });
      } else {
        list.push({
          dateIso: isoStr,
          dayName,
          dayDate,
          isToday: i === 0,
          isOff: true,
          shiftName: 'Libur',
          clockIn: '',
          clockOut: '',
          isSwap: false,
        });
      }
    }

    return list;
  });
</script>

<div
  class="space-y-4 rounded-2xl border border-[#e5e5ea] bg-white p-5 font-sans shadow-2xs sm:rounded-3xl sm:p-6"
>
  <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
    <div class="flex items-center gap-2.5">
      <Calendar class="size-4 text-[#17171c]" />
      <h3 class="text-sm font-bold text-[#17171c] sm:text-base">Jadwal Kerja 7 Hari ke Depan</h3>
    </div>
    <button
      type="button"
      onclick={onNavigateShift}
      class="flex cursor-pointer items-center gap-1 font-mono text-xs font-semibold text-[#2563eb] hover:underline"
    >
      <span>Lihat Roster</span>
      <ChevronRight class="size-3.5" />
    </button>
  </div>

  <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4 lg:grid-cols-7">
    {#each next7DaysSchedule as day}
      <div
        class={`flex flex-col justify-between space-y-2.5 rounded-2xl border p-3.5 text-xs transition-all ${
          day.isToday
            ? 'border-[#17171c] bg-[#17171c] text-white shadow-xs'
            : day.isOff
              ? 'border-[#e5e5ea] bg-[#fafafc] text-[#8e8e93]'
              : 'border-[#e5e5ea] bg-white text-[#17171c] shadow-2xs hover:border-[#17171c]/40'
        }`}
      >
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold">{day.dayName}</span>
          <span class={`font-mono text-[11px] ${day.isToday ? 'text-white/80' : 'text-[#8e8e93]'}`}
            >{day.dayDate}</span
          >
        </div>

        <div>
          {#if day.isOff}
            <span class="font-mono text-[11px] font-semibold text-[#8e8e93]">Libur / Off</span>
          {:else}
            <div class="truncate text-xs font-bold">{day.shiftName}</div>
            <div
              class={`mt-0.5 font-mono text-[10.5px] ${day.isToday ? 'text-white/80' : 'text-[#686873]'}`}
            >
              {day.clockIn.substring(0, 5)} - {day.clockOut.substring(0, 5)}
            </div>
            {#if day.isSwap}
              <span
                class="mt-1 inline-block rounded-full bg-[#f59e0b]/20 px-2 py-0.5 font-mono text-[9.5px] font-semibold text-[#d97706]"
              >
                Swap
              </span>
            {/if}
          {/if}
        </div>
      </div>
    {/each}
  </div>
</div>
