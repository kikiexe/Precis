<script lang="ts">
  import { Calendar } from 'lucide-svelte';
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

<div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 sm:p-5 space-y-3 shadow-none font-sans">
  <div class="flex items-center justify-between border-b border-[#f2f2f2] pb-2.5">
    <div class="flex items-center gap-2">
      <Calendar class="w-4 h-4 text-[#17171c]" />
      <h3 class="text-xs sm:text-sm font-medium text-[#212121]">Jadwal Kerja 7 Hari ke Depan</h3>
    </div>
    <button
      type="button"
      onclick={onNavigateShift}
      class="text-[10px] font-mono text-[#1863dc] hover:underline cursor-pointer"
    >
      Lihat Roster &rarr;
    </button>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2">
    {#each next7DaysSchedule as day}
      <div
        class={`rounded-xl p-2.5 flex flex-col justify-between border transition-all text-xs space-y-2 ${
          day.isToday
            ? 'bg-[#17171c] text-white border-[#17171c]'
            : day.isOff
            ? 'bg-[#fafafa] border-[#d9d9dd]/60 text-[#75758a]'
            : 'bg-white border-[#d9d9dd] hover:border-[#17171c]'
        }`}
      >
        <div class="flex items-center justify-between text-[10px] font-mono">
          <span class={`font-medium ${day.isToday ? 'text-[#00875a]' : ''}`}>{day.dayName}</span>
          <span class={day.isToday ? 'text-white/70' : 'text-[#93939f]'}>{day.dayDate}</span>
        </div>

        <div>
          {#if day.isOff}
            <div class="font-mono text-[11px] font-semibold text-[#93939f]">LIBUR (OFF)</div>
            <div class="text-[9px] text-[#93939f] font-mono mt-0.5">Tidak bertugas</div>
          {:else}
            <div class={`font-medium text-xs truncate ${day.isToday ? 'text-white' : 'text-[#212121]'}`}>
              {day.shiftName}
            </div>
            <div class={`text-[10px] font-mono mt-0.5 ${day.isToday ? 'text-white/80' : 'text-[#75758a]'}`}>
              {day.clockIn} - {day.clockOut}
            </div>
          {/if}
        </div>

        <div class="pt-1">
          {#if day.isToday}
            <span class="text-[8px] font-mono px-1.5 py-0.5 rounded-full bg-white/20 text-white font-medium">
              Hari Ini
            </span>
          {:else if day.isSwap}
            <span class="text-[8px] font-mono px-1.5 py-0.5 rounded-full bg-[#f1f5ff] text-[#1863dc]">
              Tukar
            </span>
          {:else if !day.isOff}
            <span class="text-[8px] font-mono px-1.5 py-0.5 rounded-full bg-[#eeece7] text-[#616161]">
              Reguler
            </span>
          {/if}
        </div>
      </div>
    {/each}
  </div>
</div>
