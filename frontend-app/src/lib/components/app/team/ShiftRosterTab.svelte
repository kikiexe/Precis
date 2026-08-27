<script lang="ts">
  import {
    Calendar,
    Clock,
    ArrowRightLeft,
    Check,
    Plus,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Search,
  } from 'lucide-svelte';
  import type { ShiftRosterItem, ShiftTemplateItem, PendingSwapItem, TeamMember } from '../../../types/app';
  import { shiftService } from '../../../services/shift-service';
  import AssignShiftModal from './modals/AssignShiftModal.svelte';

  interface Props {
    rosterShifts?: ShiftRosterItem[];
    shiftTemplates: ShiftTemplateItem[];
    pendingSwaps: PendingSwapItem[];
    filteredStaffEmployees: TeamMember[];
    selectedBranchFilter?: string;
    onApproveSwap: (swapId: string) => void;
    onRejectSwap: (swapId: string) => void;
    onAssignShift?: (templateId: string, userId: string, date: string) => Promise<void>;
    onDeleteShift?: (assignmentId: string) => Promise<void>;
  }

  let {
    rosterShifts: _initialRosterShifts = [],
    shiftTemplates = [],
    pendingSwaps = [],
    filteredStaffEmployees = [],
    selectedBranchFilter = 'ALL',
    onApproveSwap,
    onRejectSwap,
    onAssignShift,
    onDeleteShift,
  }: Props = $props();

  let isAssignModalOpen = $state(false);
  let modalInitialUserId = $state('');
  let modalInitialDate = $state('');
  let modalInitialTemplateId = $state('');
  let modalInitialAssignmentId = $state('');

  // Selected Day for Mobile View
  let selectedDayIndex = $state(0);

  // --- Filter Period State ---
  let viewPeriod = $state<'week' | 'month'>('week'); // 'week' (7 Hari) or 'month' (1 Bulan Penuh)
  let weekOffset = $state<number>(0);

  const now = new Date();
  let selectedYear = $state<number>(now.getFullYear());
  let selectedMonth = $state<number>(now.getMonth() + 1); // 1-12

  // 5 Years History (2021 to 2026)
  const availableYears = [2026, 2025, 2024, 2023, 2022, 2021];
  const availableMonths = [
    { value: 1, label: 'Januari' },
    { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' },
    { value: 4, label: 'April' },
    { value: 5, label: 'Mei' },
    { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' },
    { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' },
    { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' },
    { value: 12, label: 'Desember' },
  ];

  // Calculated start_date & end_date based on viewPeriod
  let startDate = $derived.by(() => {
    if (viewPeriod === 'week') {
      const base = new Date();
      const day = base.getDay();
      const diffToMonday = day === 0 ? -6 : 1 - day;
      const monday = new Date(base);
      monday.setDate(base.getDate() + diffToMonday + weekOffset * 7);
      const y = monday.getFullYear();
      const m = String(monday.getMonth() + 1).padStart(2, '0');
      const d = String(monday.getDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    } else {
      const y = selectedYear;
      const m = String(selectedMonth).padStart(2, '0');
      return `${y}-${m}-01`;
    }
  });

  let endDate = $derived.by(() => {
    if (viewPeriod === 'week') {
      const start = new Date(startDate + 'T00:00:00');
      const sunday = new Date(start);
      sunday.setDate(start.getDate() + 6);
      const y = sunday.getFullYear();
      const m = String(sunday.getMonth() + 1).padStart(2, '0');
      const d = String(sunday.getDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    } else {
      const y = selectedYear;
      const m = selectedMonth;
      const lastDay = new Date(y, m, 0).getDate();
      const mStr = String(m).padStart(2, '0');
      return `${y}-${mStr}-${String(lastDay).padStart(2, '0')}`;
    }
  });

  // Dynamic Shift Data Fetching
  let dynamicShifts = $state<ShiftRosterItem[]>([]);
  let isLoadingShifts = $state(false);

  async function loadShiftsForRange() {
    isLoadingShifts = true;
    try {
      const branchId = selectedBranchFilter !== 'ALL' ? selectedBranchFilter : undefined;
      const data = await shiftService.getRoster(branchId, startDate, endDate);
      dynamicShifts = data || [];
    } catch {
      // fallback
    } finally {
      isLoadingShifts = false;
    }
  }

  $effect(() => {
    if (startDate && endDate) {
      loadShiftsForRange();
    }
  });

  let filteredRosterShifts = $derived(
    dynamicShifts.filter((s) => {
      if (selectedBranchFilter === 'ALL') return true;
      return (
        s.template?.branch_id === selectedBranchFilter ||
        (s.template?.branch_name && s.template.branch_name.toLowerCase().includes(selectedBranchFilter.toLowerCase()))
      );
    })
  );

  // Computed 7 Days for the active week
  let weekDays = $derived.by(() => {
    const start = new Date(startDate + 'T00:00:00');
    const todayStr = new Date().toISOString().split('T')[0];

    const days = [];
    for (let i = 0; i < 7; i++) {
      const d = new Date(start);
      d.setDate(start.getDate() + i);
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, '0');
      const dayStr = String(d.getDate()).padStart(2, '0');
      const iso = `${y}-${m}-${dayStr}`;
      days.push({
        iso,
        dayName: d.toLocaleDateString('id-ID', { weekday: 'short' }),
        fullDayName: d.toLocaleDateString('id-ID', { weekday: 'long' }),
        dayDate: `${dayStr}/${m}`,
        fullDateFormatted: d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }),
        isToday: iso === todayStr,
      });
    }
    return days;
  });

  let activeMobileDay = $derived(weekDays[selectedDayIndex] || weekDays[0]);

  // Month View Staff Table Search
  let monthSearchQuery = $state('');
  let filteredMonthShifts = $derived.by(() => {
    let list = [...filteredRosterShifts];
    if (monthSearchQuery.trim()) {
      const q = monthSearchQuery.toLowerCase().trim();
      list = list.filter((s) => {
        const staffName = (s.assigned_user?.name || '').toLowerCase();
        const actualName = (s.actual_user?.name || '').toLowerCase();
        const tplName = (s.template?.name || '').toLowerCase();
        const dateStr = s.date || '';
        return (
          staffName.includes(q) ||
          actualName.includes(q) ||
          tplName.includes(q) ||
          dateStr.includes(q)
        );
      });
    }
    return list.sort((a, b) => a.date.localeCompare(b.date));
  });

  function formatDateIndo(dateStr: string): string {
    try {
      const d = new Date(dateStr + 'T00:00:00');
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
      return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
    } catch {
      return dateStr;
    }
  }

  function getDayNameIndo(dateStr: string): string {
    try {
      const d = new Date(dateStr + 'T00:00:00');
      const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
      return days[d.getDay()] || '';
    } catch {
      return '';
    }
  }

  function handlePrev() {
    if (viewPeriod === 'week') {
      weekOffset -= 1;
    } else {
      if (selectedMonth === 1) {
        selectedMonth = 12;
        selectedYear -= 1;
      } else {
        selectedMonth -= 1;
      }
    }
  }

  function handleNext() {
    if (viewPeriod === 'week') {
      weekOffset += 1;
    } else {
      if (selectedMonth === 12) {
        selectedMonth = 1;
        selectedYear += 1;
      } else {
        selectedMonth += 1;
      }
    }
  }

  function handleResetToday() {
    weekOffset = 0;
    const d = new Date();
    selectedYear = d.getFullYear();
    selectedMonth = d.getMonth() + 1;
  }

  let todayStr = $derived.by(() => {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  });

  function openAssignModal(userId: string, date: string, templateId?: string, assignmentId?: string) {
    modalInitialUserId = userId;
    modalInitialDate = date;
    modalInitialTemplateId = templateId || shiftTemplates[0]?.id || '';
    modalInitialAssignmentId = assignmentId || '';
    isAssignModalOpen = true;
  }
</script>

<div class="space-y-6 font-sans">
  <!-- Section Header & Quick Assign -->
  <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs">
    <div class="space-y-1">
      <div class="flex items-center gap-2 flex-wrap">
        <h2 class="text-base sm:text-lg font-bold text-[#17171c]">Jadwal &amp; Roster Shift</h2>
        <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-mono font-semibold bg-[#f4f4f6] text-[#17171c]">
          {viewPeriod === 'week' ? '7 Hari (Sen - Min)' : `${availableMonths.find(m => m.value === selectedMonth)?.label} ${selectedYear}`}
        </span>
      </div>
      <p class="text-xs text-[#8e8e93]">
        Kelola penugasan jam kerja staf dan otorisasi permohonan tukar shift.
      </p>
    </div>

    <button
      type="button"
      onclick={() => {
        const todayIso = (weekDays.find((w) => w.isToday) || weekDays[0])?.iso || '';
        openAssignModal(filteredStaffEmployees[0]?.user_id || filteredStaffEmployees[0]?.id || '', todayIso);
      }}
      class="px-5 py-2.5 bg-[#17171c] hover:bg-black text-white rounded-full text-xs font-semibold flex items-center justify-center gap-2 cursor-pointer shadow-xs transition-all self-stretch sm:self-auto shrink-0"
    >
      <Clock class="w-4 h-4" />
      <span>+ Tetapkan Shift</span>
    </button>
  </div>

  <!-- Interactive Date Range Filter Toolbar (7 Hari vs 1 Bulan Penuh - 5 Tahun History) -->
  <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4 sm:p-5 shadow-2xs space-y-3.5">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <!-- Mode Switcher: 7 Hari vs Bulan Penuh -->
      <div class="flex items-center gap-1 bg-[#f4f4f6] p-1 rounded-full border border-[#e5e5ea] self-start">
        <button
          type="button"
          onclick={() => {
            viewPeriod = 'week';
            weekOffset = 0;
          }}
          class={`px-3.5 py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer ${
            viewPeriod === 'week'
              ? 'bg-[#17171c] text-white font-bold shadow-xs'
              : 'text-[#686873] hover:text-[#17171c]'
          }`}
        >
          Pekan (7 Hari)
        </button>
        <button
          type="button"
          onclick={() => (viewPeriod = 'month')}
          class={`px-3.5 py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer ${
            viewPeriod === 'month'
              ? 'bg-[#17171c] text-white font-bold shadow-xs'
              : 'text-[#686873] hover:text-[#17171c]'
          }`}
        >
          Bulan (1 Bulan Penuh)
        </button>
      </div>

      <!-- Navigation & Selectors -->
      <div class="flex items-center flex-wrap gap-2.5">
        {#if viewPeriod === 'month'}
          <!-- Month Selector -->
          <div class="relative">
            <select
              bind:value={selectedMonth}
              class="appearance-none bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl px-3.5 pr-8 py-1.5 text-xs font-bold text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all cursor-pointer shadow-2xs"
            >
              {#each availableMonths as m}
                <option value={m.value}>{m.label}</option>
              {/each}
            </select>
            <ChevronDown class="w-3.5 h-3.5 text-[#8e8e93] absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>

          <!-- Year Selector (5 Years Seeder History) -->
          <div class="relative">
            <select
              bind:value={selectedYear}
              class="appearance-none bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl px-3.5 pr-8 py-1.5 text-xs font-mono font-bold text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all cursor-pointer shadow-2xs"
            >
              {#each availableYears as yr}
                <option value={yr}>Tahun {yr}</option>
              {/each}
            </select>
            <ChevronDown class="w-3.5 h-3.5 text-[#8e8e93] absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        {/if}

        <!-- Step Prev / Next Buttons -->
        <div class="flex items-center gap-1 bg-[#f8f8fa] p-0.5 rounded-xl border border-[#e5e5ea]">
          <button
            type="button"
            onclick={handlePrev}
            class="p-1.5 text-[#686873] hover:text-[#17171c] hover:bg-white rounded-lg transition-all cursor-pointer"
            title={viewPeriod === 'week' ? 'Pekan Sebelumnya' : 'Bulan Sebelumnya'}
          >
            <ChevronLeft class="w-4 h-4" />
          </button>

          <button
            type="button"
            onclick={handleResetToday}
            class="px-2.5 py-1 text-[11px] font-semibold text-[#17171c] hover:bg-white rounded-lg transition-all cursor-pointer"
          >
            {viewPeriod === 'week' ? 'Pekan Ini' : 'Bulan Ini'}
          </button>

          <button
            type="button"
            onclick={handleNext}
            class="p-1.5 text-[#686873] hover:text-[#17171c] hover:bg-white rounded-lg transition-all cursor-pointer"
            title={viewPeriod === 'week' ? 'Pekan Berikutnya' : 'Bulan Berikutnya'}
          >
            <ChevronRight class="w-4 h-4" />
          </button>
        </div>

        <!-- Formatted Date Range Pill -->
        <div class="px-3 py-1.5 bg-[#f4f4f6] text-[#17171c] border border-[#e5e5ea] rounded-xl text-xs font-mono font-semibold flex items-center gap-1.5">
          <Calendar class="w-3.5 h-3.5 text-[#8e8e93]" />
          <span>{formatDateIndo(startDate)} &mdash; {formatDateIndo(endDate)}</span>
        </div>
      </div>
    </div>
  </div>

  {#if isLoadingShifts}
    <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-12 text-center space-y-3 shadow-2xs animate-pulse">
      <div class="w-8 h-8 rounded-full bg-[#17171c]/20 mx-auto"></div>
      <div class="text-xs font-mono text-[#8e8e93]">Memuat data jadwal roster shift...</div>
    </div>
  {:else if viewPeriod === 'week'}
    <!-- ==================== VIEW 1: PEKAN (7 HARI MATRIX) ==================== -->
    <!-- MOBILE-FIRST VIEW (< 768px / md): Interactive Day Carousel -->
    <div class="block md:hidden space-y-4">
      <!-- Day Strip Carousel -->
      <div class="bg-white border border-[#e5e5ea] rounded-2xl p-2 shadow-2xs">
        <div class="flex items-center justify-between gap-1 overflow-x-auto no-scrollbar">
          {#each weekDays as day, idx}
            <button
              type="button"
              onclick={() => (selectedDayIndex = idx)}
              class={`flex-1 min-w-[50px] py-2 px-1.5 rounded-xl text-center transition-all cursor-pointer ${
                selectedDayIndex === idx
                  ? 'bg-[#17171c] text-white shadow-xs'
                  : day.isToday
                    ? 'bg-[#f4f4f6] text-[#17171c] font-semibold border border-[#d1d1d6]'
                    : 'text-[#686873] hover:bg-[#f4f4f6]'
              }`}
            >
              <div class="text-[10px] uppercase font-bold tracking-wider opacity-80">{day.dayName}</div>
              <div class="text-xs font-mono font-bold mt-0.5">{day.dayDate}</div>
              {#if day.isToday && selectedDayIndex !== idx}
                <div class="w-1 h-1 rounded-full bg-[#1863dc] mx-auto mt-1"></div>
              {/if}
            </button>
          {/each}
        </div>
      </div>

      <!-- Active Day Staff List Card -->
      <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4 space-y-3 shadow-2xs">
        <div class="flex items-center justify-between pb-3 border-b border-[#f2f2f4]">
          <div class="flex items-center gap-2">
            <Calendar class="w-4 h-4 text-[#17171c]" />
            <div>
              <h3 class="font-bold text-xs text-[#17171c]">{activeMobileDay.fullDayName}, {activeMobileDay.fullDateFormatted}</h3>
              {#if activeMobileDay.isToday}
                <span class="text-[10px] font-mono text-[#10b981] font-semibold">Hari Ini</span>
              {/if}
            </div>
          </div>

          <button
            type="button"
            onclick={() => {
              openAssignModal(filteredStaffEmployees[0]?.user_id || filteredStaffEmployees[0]?.id || '', activeMobileDay.iso);
            }}
            class="text-xs text-[#1863dc] font-medium hover:underline flex items-center gap-1 cursor-pointer"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Tambah</span>
          </button>
        </div>

        <!-- Staff shifts list for this active day -->
        {#if filteredStaffEmployees.length === 0}
          <p class="text-xs text-[#8e8e93] text-center py-6">Belum ada karyawan terdaftar.</p>
        {:else}
          <div class="space-y-2.5">
            {#each filteredStaffEmployees as staff}
              {@const staffUserId = staff.user_id || staff.id}
              {@const shift = filteredRosterShifts.find(
                (s) => (s.assigned_user?.id === staffUserId || s.assigned_user?.id === staff.id) && s.date === activeMobileDay.iso
              )}
              <div class="flex items-center justify-between p-3 rounded-xl bg-[#fbfbfa] border border-[#ececee] gap-2">
                <div class="min-w-0">
                  <div class="font-semibold text-xs text-[#17171c] truncate">{staff.name}</div>
                  <div class="text-[11px] text-[#8e8e93] truncate">{staff.job_title || 'Staf'}</div>
                </div>

                {#if shift && shift.template}
                  {#if activeMobileDay.iso >= todayStr}
                    <button
                      type="button"
                      onclick={() => openAssignModal(staffUserId, activeMobileDay.iso, shift.template?.id, shift.id)}
                      class="px-3 py-1.5 rounded-xl bg-[#17171c] text-white text-right cursor-pointer hover:bg-black transition-all shadow-2xs shrink-0"
                    >
                      <div class="font-bold text-[11px]">{shift.template.name}</div>
                      <div class="text-[9.5px] text-white/80 font-mono">
                        {shift.template.expected_clock_in?.substring(0, 5)} - {shift.template.expected_clock_out?.substring(0, 5)}
                      </div>
                    </button>
                  {:else}
                    <div class="px-3 py-1.5 rounded-xl bg-[#f4f4f6] border border-[#e5e5ea] text-right shrink-0">
                      <div class="font-bold text-[11px] text-[#17171c]">{shift.template.name}</div>
                      <div class="text-[9.5px] text-[#8e8e93] font-mono">
                        {shift.template.expected_clock_in?.substring(0, 5)} - {shift.template.expected_clock_out?.substring(0, 5)}
                      </div>
                    </div>
                  {/if}
                {:else}
                  {#if activeMobileDay.iso >= todayStr}
                    <button
                      type="button"
                      onclick={() => openAssignModal(staffUserId, activeMobileDay.iso)}
                      class="px-3 py-1.5 rounded-xl border border-dashed border-[#d1d1d6] hover:border-[#17171c] text-[#8e8e93] hover:text-[#17171c] text-xs font-mono transition-all cursor-pointer shrink-0"
                    >
                      + Libur / Set Shift
                    </button>
                  {:else}
                    <span class="text-xs font-mono text-[#a1a1aa] px-3 py-1.5">
                      Off
                    </span>
                  {/if}
                {/if}
              </div>
            {/each}
          </div>
        {/if}
      </div>
    </div>

    <!-- DESKTOP VIEW (>= 768px / md): Spacious Clean Weekly Grid Table -->
    <div class="hidden md:block bg-white border border-[#e5e5ea] rounded-3xl overflow-hidden shadow-2xs">
      <div class="p-5 border-b border-[#e5e5ea] flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <Calendar class="w-4 h-4 text-[#17171c]" />
          <h3 class="text-xs font-bold uppercase tracking-wider text-[#17171c]">Matriks Roster Mingguan</h3>
        </div>
        <span class="text-xs text-[#8e8e93] font-mono">Klik kotak jadwal untuk mengubah penugasan</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="border-b border-[#e5e5ea] bg-[#fafafc] font-mono text-[10.5px] text-[#686873]">
              <th class="py-3.5 px-5 font-bold uppercase min-w-[160px]">Nama Karyawan</th>
              {#each weekDays as day}
                <th class={`py-3.5 px-3 text-center min-w-[100px] ${day.isToday ? 'bg-[#17171c] text-white' : ''}`}>
                  <div class="font-bold">{day.dayName.toUpperCase()}</div>
                  <div class="text-[9.5px] opacity-80">{day.dayDate}</div>
                </th>
              {/each}
            </tr>
          </thead>
          <tbody class="divide-y divide-[#e5e5ea]">
            {#if filteredStaffEmployees.length === 0}
              <tr>
                <td colspan={8} class="py-10 text-center text-[#8e8e93] font-mono">
                  Belum ada staf yang terdaftar di cabang ini.
                </td>
              </tr>
            {:else}
              {#each filteredStaffEmployees as staff}
                {@const staffUserId = staff.user_id || staff.id}
                <tr class="hover:bg-[#fafafc] transition-colors">
                  <td class="py-4 px-5 font-medium text-[#17171c]">
                    <div class="font-bold text-xs">{staff.name}</div>
                    <div class="text-[11px] text-[#8e8e93] font-normal">{staff.job_title || 'Staf'}</div>
                  </td>

                  {#each weekDays as day}
                    {@const shift = filteredRosterShifts.find(
                      (s) => (s.assigned_user?.id === staffUserId || s.assigned_user?.id === staff.id) && s.date === day.iso
                    )}
                    <td class={`py-2.5 px-2 text-center align-middle ${day.isToday ? 'bg-[#fafafc]' : ''}`}>
                      {#if shift && shift.template}
                        {#if day.iso >= todayStr}
                          <button
                            type="button"
                            onclick={() => openAssignModal(staffUserId, day.iso, shift.template?.id, shift.id)}
                            class="w-full py-2 px-2.5 rounded-xl text-[10.5px] font-mono font-semibold bg-[#17171c] text-white hover:bg-black transition-all cursor-pointer block truncate text-center shadow-2xs"
                            title={`${shift.template.name} (${shift.template.expected_clock_in} - ${shift.template.expected_clock_out})`}
                          >
                            <div class="font-bold truncate">{shift.template.name}</div>
                            <div class="text-[9px] text-white/75 mt-0.5">
                              {shift.template.expected_clock_in?.substring(0, 5)} - {shift.template.expected_clock_out?.substring(0, 5)}
                            </div>
                          </button>
                        {:else}
                          <div
                            class="w-full py-2 px-2.5 rounded-xl text-[10.5px] font-mono font-semibold bg-[#f4f4f6] text-[#686873] border border-[#e5e5ea] block truncate text-center"
                            title={`${shift.template.name} (${shift.template.expected_clock_in} - ${shift.template.expected_clock_out}) - Selesai`}
                          >
                            <div class="font-semibold truncate text-[#17171c]">{shift.template.name}</div>
                            <div class="text-[9px] text-[#8e8e93] mt-0.5">
                              {shift.template.expected_clock_in?.substring(0, 5)} - {shift.template.expected_clock_out?.substring(0, 5)}
                            </div>
                          </div>
                        {/if}
                      {:else}
                        {#if day.iso >= todayStr}
                          <button
                            type="button"
                            onclick={() => openAssignModal(staffUserId, day.iso)}
                            class="w-full py-2.5 px-2 text-[10.5px] font-mono text-[#8e8e93] hover:text-[#17171c] hover:bg-[#f4f4f6] rounded-xl transition-all cursor-pointer border border-dashed border-[#dcdce4] block"
                          >
                            + Off
                          </button>
                        {:else}
                          <span class="text-[10.5px] font-mono text-[#a1a1aa] block text-center py-2.5">
                            Off
                          </span>
                        {/if}
                      {/if}
                    </td>
                  {/each}
                </tr>
              {/each}
            {/if}
          </tbody>
        </table>
      </div>
    </div>
  {:else}
    <!-- ==================== VIEW 2: BULAN (1 BULAN PENUH TABLE) ==================== -->
    <div class="bg-white border border-[#e5e5ea] rounded-3xl overflow-hidden shadow-2xs space-y-4">
      <div class="p-5 border-b border-[#e5e5ea] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
          <Calendar class="w-4 h-4 text-[#17171c]" />
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-[#17171c]">
              Daftar Penugasan Shift Bulanan ({filteredMonthShifts.length})
            </h3>
            <p class="text-[11px] text-[#8e8e93]">
              Periode {availableMonths.find(m => m.value === selectedMonth)?.label} {selectedYear}
            </p>
          </div>
        </div>

        <div class="relative min-w-0 sm:w-64">
          <Search class="w-3.5 h-3.5 text-[#8e8e93] absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            bind:value={monthSearchQuery}
            placeholder="Cari staf, shift, tanggal..."
            class="w-full pl-9 pr-4 py-1.5 text-xs bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-full text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
          />
        </div>
      </div>

      {#if filteredMonthShifts.length === 0}
        <div class="p-12 text-center text-[#8e8e93] text-xs font-mono">
          Tidak ada penugasan shift pada bulan {availableMonths.find(m => m.value === selectedMonth)?.label} {selectedYear}.
        </div>
      {:else}
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-[#f8f8fa] border-b border-[#e5e5ea] text-[#686873] font-mono text-[10.5px] uppercase tracking-wider">
                <th class="py-3 px-5 font-bold">Tanggal &amp; Hari</th>
                <th class="py-3 px-4 font-bold">Karyawan Bertugas</th>
                <th class="py-3 px-4 font-bold">Pola Shift &amp; Jam</th>
                <th class="py-3 px-4 font-bold">Status Penugasan</th>
                <th class="py-3 px-5 font-bold text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#f2f2f4]">
              {#each filteredMonthShifts as shift (shift.id)}
                <tr class="hover:bg-[#fafafc] transition-colors">
                  <td class="py-3 px-5 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                      <span class="font-mono font-bold text-[#17171c]">{shift.date}</span>
                      <span class="text-[11px] text-[#8e8e93]">({getDayNameIndo(shift.date)})</span>
                    </div>
                  </td>
                  <td class="py-3 px-4 whitespace-nowrap">
                    <div class="font-semibold text-[#17171c]">{shift.assigned_user?.name || 'Staf'}</div>
                    {#if shift.is_swap && shift.actual_user}
                      <div class="text-[10px] text-[#2563eb] font-mono">
                        Pengganti: {shift.actual_user.name}
                      </div>
                    {/if}
                  </td>
                  <td class="py-3 px-4 whitespace-nowrap">
                    <div class="font-bold text-[#17171c]">{shift.template?.name || 'Shift'}</div>
                    <div class="text-[10.5px] font-mono text-[#8e8e93]">
                      {shift.template?.expected_clock_in?.substring(0, 5)} - {shift.template?.expected_clock_out?.substring(0, 5)} WIB
                    </div>
                  </td>
                  <td class="py-3 px-4 whitespace-nowrap">
                    {#if shift.is_swap}
                      <span class={`text-[9.5px] font-mono px-2 py-0.5 rounded-full font-semibold ${
                        shift.swap_status === 'APPROVED'
                          ? 'bg-[#ecfdf5] text-[#059669] border border-[#a7f3d0]'
                          : shift.swap_status === 'PENDING'
                          ? 'bg-[#fffbeb] text-[#d97706] border border-[#fef3c7]'
                          : 'bg-[#fef2f2] text-[#dc2626] border border-[#fecaca]'
                      }`}>
                        Tukar: {shift.swap_status}
                      </span>
                    {:else}
                      <span class="text-[9.5px] font-mono bg-[#f4f4f6] text-[#686873] px-2 py-0.5 rounded-full font-semibold border border-[#e5e5ea]">
                        Reguler
                      </span>
                    {/if}
                  </td>
                  <td class="py-3 px-5 whitespace-nowrap text-right">
                    {#if shift.date >= todayStr}
                      <button
                        type="button"
                        onclick={() => openAssignModal(shift.assigned_user?.id || '', shift.date, shift.template?.id, shift.id)}
                        class="px-2.5 py-1 text-[11px] font-semibold text-[#17171c] bg-[#f4f4f6] hover:bg-[#e5e5ea] rounded-lg transition-all cursor-pointer border border-[#e5e5ea]"
                      >
                        Ubah Shift
                      </button>
                    {:else}
                      <span class="text-[11px] font-mono text-[#8e8e93]">-</span>
                    {/if}
                  </td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>
      {/if}
    </div>
  {/if}

  <!-- Pending Swap Requests Authorization Queue -->
  <div class="space-y-3 pt-2">
    <div class="flex items-center justify-between px-1">
      <h3 class="text-xs font-bold uppercase tracking-wider text-[#17171c] flex items-center gap-2">
        <ArrowRightLeft class="w-3.5 h-3.5 text-[#1863dc]" />
        <span>Antrean Otorisasi Tukar Shift ({pendingSwaps.length})</span>
      </h3>
    </div>

    {#if pendingSwaps.length === 0}
      <div class="bg-white border border-[#e5e5ea] rounded-2xl p-6 text-center text-[#8e8e93] text-xs font-mono shadow-2xs">
        Tidak ada permohonan tukar shift yang menunggu persetujuan.
      </div>
    {:else}
      <div class="space-y-3">
        {#each pendingSwaps as swap}
          <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs">
            <div class="space-y-1.5 text-xs">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-bold text-[#17171c]">{swap.assigned_user?.name}</span>
                <span class="text-[#8e8e93]">&rarr;</span>
                <span class="font-bold text-[#17171c]">{swap.actual_user?.name}</span>
                <span class="text-[9.5px] font-mono px-2.5 py-0.5 rounded-full bg-[#fffbeb] text-[#d97706] border border-[#fef3c7] font-semibold">
                  PENDING
                </span>
              </div>
              <div class="text-[11.5px] text-[#686873] font-mono">
                Shift: <strong class="text-[#17171c]">{swap.template?.name || 'Shift'}</strong> ({swap.template?.branch_name || 'Cabang'}) &bull; Tanggal: {swap.date}
              </div>
            </div>

            <div class="flex items-center gap-2 self-end sm:self-center">
              <button
                type="button"
                onclick={() => onApproveSwap(swap.id)}
                class="px-4 py-2 bg-[#17171c] hover:bg-black text-white rounded-full text-xs font-semibold flex items-center gap-1.5 cursor-pointer transition-all shadow-xs"
              >
                <Check class="w-3.5 h-3.5" />
                <span>Setujui</span>
              </button>

              <button
                type="button"
                onclick={() => onRejectSwap(swap.id)}
                class="px-4 py-2 text-[#e5484d] hover:bg-[#fef2f2] rounded-full text-xs font-semibold transition-colors cursor-pointer border border-transparent hover:border-[#fecaca]"
              >
                Tolak
              </button>
            </div>
          </div>
        {/each}
      </div>
    {/if}
  </div>
</div>

<!-- Modal: Assign Shift to Staff -->
<AssignShiftModal
  isOpen={isAssignModalOpen}
  initialUserId={modalInitialUserId}
  initialDate={modalInitialDate}
  initialTemplateId={modalInitialTemplateId}
  initialAssignmentId={modalInitialAssignmentId}
  {shiftTemplates}
  staffMembers={filteredStaffEmployees}
  onClose={() => (isAssignModalOpen = false)}
  onAssign={async (templateId, userId, date) => {
    if (onAssignShift) {
      await onAssignShift(templateId, userId, date);
    }
    isAssignModalOpen = false;
    await loadShiftsForRange();
  }}
  onDelete={async (assignmentId) => {
    if (onDeleteShift) {
      await onDeleteShift(assignmentId);
    }
    isAssignModalOpen = false;
    await loadShiftsForRange();
  }}
/>
