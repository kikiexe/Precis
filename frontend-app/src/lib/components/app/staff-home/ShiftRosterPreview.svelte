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

<div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 space-y-4 shadow-2xs font-sans">
  <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
    <div class="flex items-center gap-2.5">
      <Calendar class="w-4 h-4 text-[#17171c]" />
      <h3 class="text-sm sm:text-base font-bold text-[#17171c]">Jadwal Kerja 7 Hari ke Depan</h3>
    </div>
    <button
      type="button"
      onclick={onNavigateShift}
      class="text-xs font-mono font-semibold text-[#2563eb] hover:underline cursor-pointer flex items-center gap-1"
    >
      <span>Lihat Roster</span>
      <ChevronRight class="w-3.5 h-3.5" />
    </button>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5">
    {#each next7DaysSchedule as day}
      <div
        class={`rounded-2xl p-3.5 flex flex-col justify-between border transition-all text-xs space-y-2.5 ${
          day.isToday
            ? 'bg-[#17171c] text-white border-[#17171c] shadow-xs'
            : day.isOff
            ? 'bg-[#fafafc] border-[#e5e5ea] text-[#8e8e93]'
            : 'bg-white border-[#e5e5ea] hover:border-[#17171c]/40 text-[#17171c] shadow-2xs'
        }`}
      >
        <div class="flex items-center justify-between">
          <span class="font-bold text-xs">{day.dayName}</span>
          <span class={`font-mono text-[11px] ${day.isToday ? 'text-white/80' : 'text-[#8e8e93]'}`}>{day.dayDate}</span>
        </div>

        <div>
          {#if day.isOff}
            <span class="font-mono text-[11px] font-semibold text-[#8e8e93]">Libur / Off</span>
          {:else}
            <div class="font-bold text-xs truncate">{day.shiftName}</div>
            <div class={`font-mono text-[10.5px] mt-0.5 ${day.isToday ? 'text-white/80' : 'text-[#686873]'}`}>
              {day.clockIn.substring(0, 5)} - {day.clockOut.substring(0, 5)}
            </div>
            {#if day.isSwap}
              <span class="inline-block mt-1 text-[9.5px] font-mono px-2 py-0.5 rounded-full bg-[#f59e0b]/20 text-[#d97706] font-semibold">
                Swap
              </span>
            {/if}
          {/if}
        </div>
      </div>
    {/each}
  </div>
</div>
