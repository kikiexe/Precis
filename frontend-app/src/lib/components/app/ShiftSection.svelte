<script lang="ts">
  import {
    Calendar,
    RefreshCw,
    Plus,
    Layers,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Search,
    AlertCircle,
    X,
  } from 'lucide-svelte';
  import type { User, ShiftRosterItem, ShiftTemplateItem } from '../../types/app';
  import { apiClient } from '../../services/api-client';
  import { shiftService } from '../../services/shift-service';
  import AssignShiftModal from './team/modals/AssignShiftModal.svelte';

  export interface ShiftStaffUser {
    id: string;
    user_id?: string;
    name: string;
    role: string;
    email?: string;
    job_title?: string;
    branch_id?: string | null;
    branch_name?: string | null;
  }

  interface Props {
    currentUser: User;
    allUsers?: ShiftStaffUser[];
    rosterShifts?: ShiftRosterItem[];
    shiftTemplates?: ShiftTemplateItem[];
    onSubmitSwap: (shiftAssignmentId: string, targetUserId: string) => Promise<void>;
    onAssignShift?: (shiftTemplateId: string, assignedUserId: string, date: string) => Promise<void>;
    onRefreshTemplates?: () => Promise<void>;
  }

  let {
    currentUser,
    allUsers = [],
    rosterShifts: _initialRosterShifts = [],
    shiftTemplates = [],
    onSubmitSwap,
    onAssignShift,
    onRefreshTemplates,
  }: Props = $props();

  let isSwapModalOpen = $state(false);
  let isAssignModalOpen = $state(false);
  let isTemplateBuilderOpen = $state(false);

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

  let todayStr = $derived.by(() => {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  });

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
      const branchId = currentUser.branch_id || undefined;
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

  // Unique staff members list for matrix view
  let staffList = $derived.by(() => {
    if (allUsers.length > 0) return allUsers;
    const map = new Map<string, ShiftStaffUser>();
    for (const s of dynamicShifts) {
      if (s.assigned_user?.id && !map.has(s.assigned_user.id)) {
        map.set(s.assigned_user.id, {
          id: s.assigned_user.id,
          user_id: s.assigned_user.id,
          name: s.assigned_user.name || 'Staf',
          role: 'STAFF',
          email: '',
          job_title: 'Staf Outlet',
        });
      }
    }
    if (!map.has(currentUser.id)) {
      map.set(currentUser.id, {
        id: currentUser.id,
        user_id: currentUser.id,
        name: currentUser.name || 'Anda',
        role: currentUser.role,
        email: currentUser.email || '',
        job_title: currentUser.job_title || 'Staf Outlet',
      });
    }
    return Array.from(map.values());
  });

  // Computed 7 Days for the active week
  let weekDays = $derived.by(() => {
    const start = new Date(startDate + 'T00:00:00');

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
    let list = [...dynamicShifts];
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

  let myUpcomingShifts = $derived(
    dynamicShifts.filter(
      (s) =>
        (s.assigned_user?.id === currentUser.id || s.actual_user?.id === currentUser.id) &&
        s.date >= todayStr
    )
  );

  let availableColleagues = $derived(
    staffList.filter((u) => u.id !== currentUser.id && u.user_id !== currentUser.id)
  );

  // Form Swap State
  let selectedShiftAssignmentId = $state('');
  let targetUserId = $state('');
  let isSubmittingSwap = $state(false);
  let swapErrorMessage = $state<string | null>(null);

  // Shift Template Builder State
  let templateName = $state('');
  let templateClockIn = $state('07:00');
  let templateClockOut = $state('15:00');
  let templateLateTolerance = $state(15);
  let templateMinOvertime = $state(60);
  let isSubmittingTemplate = $state(false);
  let templateError = $state<string | null>(null);
  let templateSuccess = $state<string | null>(null);

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

  function openSwapForShift(shiftId: string) {
    selectedShiftAssignmentId = shiftId;
    targetUserId = '';
    swapErrorMessage = null;
    isSwapModalOpen = true;
  }

  function openAssignModal(userId: string, date: string, templateId?: string, assignmentId?: string) {
    modalInitialUserId = userId;
    modalInitialDate = date;
    modalInitialTemplateId = templateId || shiftTemplates[0]?.id || '';
    modalInitialAssignmentId = assignmentId || '';
    isAssignModalOpen = true;
  }

  async function handleConfirmSwap() {
    if (!selectedShiftAssignmentId || !targetUserId) {
      swapErrorMessage = 'Pilih shift Anda dan rekan kerja pengganti.';
      return;
    }

    isSubmittingSwap = true;
    swapErrorMessage = null;

    try {
      await onSubmitSwap(selectedShiftAssignmentId, targetUserId);
      isSwapModalOpen = false;
      selectedShiftAssignmentId = '';
      targetUserId = '';
      await loadShiftsForRange();
    } catch (err: unknown) {
      swapErrorMessage = err instanceof Error ? err.message : 'Gagal mengajukan pertukaran shift.';
    } finally {
      isSubmittingSwap = false;
    }
  }

  async function handleCreateTemplate() {
    if (!templateName.trim() || !templateClockIn || !templateClockOut) {
      templateError = 'Nama shift, jam masuk, dan jam pulang wajib diisi.';
      return;
    }

    isSubmittingTemplate = true;
    templateError = null;
    templateSuccess = null;

    try {
      await apiClient.post('/shifts/templates', {
        name: templateName.trim(),
        expected_clock_in: templateClockIn,
        expected_clock_out: templateClockOut,
        late_tolerance_minutes: templateLateTolerance,
        minimum_overtime_minutes: templateMinOvertime,
      });
      templateSuccess = `Template "${templateName}" berhasil dibuat.`;
      templateName = '';
      templateClockIn = '07:00';
      templateClockOut = '15:00';
      templateLateTolerance = 15;
      templateMinOvertime = 60;
      if (onRefreshTemplates) {
        await onRefreshTemplates();
      }
    } catch (err: unknown) {
      templateError = err instanceof Error ? err.message : 'Gagal membuat template shift.';
    } finally {
      isSubmittingTemplate = false;
    }
  }
</script>

<div class="space-y-6 max-w-7xl mx-auto font-sans pb-8">
  <!-- Section Header & Quick Actions -->
  <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs">
    <div class="space-y-1">
      <div class="flex items-center gap-2 flex-wrap">
        <h2 class="text-base sm:text-lg font-bold text-[#17171c]">Jadwal &amp; Roster Shift</h2>
        <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-mono font-semibold bg-[#f4f4f6] text-[#17171c]">
          {viewPeriod === 'week' ? '7 Hari (Sen - Min)' : `${availableMonths.find(m => m.value === selectedMonth)?.label} ${selectedYear}`}
        </span>
      </div>
      <p class="text-xs text-[#8e8e93]">
        Penugasan jam kerja staf dan rotasi karyawan di {currentUser.branch_name || 'Outlet Sleman'}
      </p>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      {#if currentUser.role === 'OWNER' || currentUser.role === 'ADMIN' || currentUser.role === 'MANAGER'}
        <button
          type="button"
          onclick={() => {
            templateError = null;
            templateSuccess = null;
            isTemplateBuilderOpen = !isTemplateBuilderOpen;
          }}
          class={`px-4 py-2 text-xs font-semibold rounded-full flex items-center gap-1.5 cursor-pointer transition-all ${
            isTemplateBuilderOpen
              ? 'bg-[#1863dc] text-white shadow-xs'
              : 'bg-white hover:bg-[#f4f4f6] border border-[#e5e5ea] text-[#17171c]'
          }`}
        >
          <Layers class="w-4 h-4" />
          <span>Template Shift</span>
        </button>

        <button
          type="button"
          onclick={() => {
            const todayIso = (weekDays.find((w) => w.isToday) || weekDays[0])?.iso || '';
            openAssignModal(staffList[0]?.id || '', todayIso);
          }}
          class="bg-[#17171c] hover:bg-black text-white px-4 py-2 text-xs font-semibold rounded-full flex items-center gap-1.5 cursor-pointer transition-all shadow-xs"
        >
          <Plus class="w-4 h-4" />
          <span>+ Tetapkan Shift</span>
        </button>
      {/if}

      <button
        type="button"
        onclick={() => {
          swapErrorMessage = null;
          selectedShiftAssignmentId = '';
          targetUserId = '';
          isSwapModalOpen = true;
        }}
        class="bg-[#f4f4f6] hover:bg-[#ececee] text-[#17171c] border border-[#e5e5ea] px-4 py-2 text-xs font-semibold rounded-full flex items-center gap-1.5 cursor-pointer transition-all"
      >
        <RefreshCw class="w-3.5 h-3.5" />
        <span>Tukar Shift</span>
      </button>
    </div>
  </div>

  <!-- Shift Template Builder Form (Collapsible) -->
  {#if isTemplateBuilderOpen}
    <div class="bg-white border border-[#2563eb]/20 rounded-2xl sm:rounded-3xl p-5 sm:p-6 space-y-5 shadow-xs animate-in fade-in slide-in-from-top-2">
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-[#eff6ff] text-[#2563eb] flex items-center justify-center">
            <Layers class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-[#17171c]">Shift Template Builder</h3>
            <p class="text-xs text-[#8e8e93]">Buat pola jam kerja baru untuk outlet</p>
          </div>
        </div>
        <span class="text-[10.5px] font-mono font-semibold px-2 py-0.5 rounded-full bg-[#eff6ff] text-[#2563eb]">
          POST /shifts/templates
        </span>
      </div>

      {#if templateError}
        <div class="p-3.5 bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs rounded-xl flex items-start gap-2 font-medium">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{templateError}</span>
        </div>
      {/if}

      {#if templateSuccess}
        <div class="p-3.5 bg-[#ecfdf5] border border-[#a7f3d0] text-[#065f46] text-xs rounded-xl font-medium">
          {templateSuccess}
        </div>
      {/if}

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
        <div class="sm:col-span-2 space-y-1.5">
          <label for="tpl-name" class="block font-bold text-[#17171c]">Nama Pola Shift</label>
          <input
            id="tpl-name"
            type="text"
            bind:value={templateName}
            placeholder="Contoh: Middle Barista, Shift Malam, Weekend Open"
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all"
          />
        </div>

        <div class="space-y-1.5">
          <label for="tpl-clock-in" class="block font-bold text-[#17171c]">Jam Masuk (Clock In)</label>
          <input
            id="tpl-clock-in"
            type="time"
            bind:value={templateClockIn}
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl font-mono text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all"
          />
        </div>

        <div class="space-y-1.5">
          <label for="tpl-clock-out" class="block font-bold text-[#17171c]">Jam Pulang (Clock Out)</label>
          <input
            id="tpl-clock-out"
            type="time"
            bind:value={templateClockOut}
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl font-mono text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all"
          />
        </div>

        <div class="space-y-1.5">
          <label for="tpl-late-tolerance" class="block font-bold text-[#17171c]">
            Batas Toleransi Telat ({templateLateTolerance} menit)
          </label>
          <input
            id="tpl-late-tolerance"
            type="range"
            min="0"
            max="60"
            step="5"
            bind:value={templateLateTolerance}
            class="w-full accent-[#17171c] cursor-pointer"
          />
          <span class="text-[11px] text-[#8e8e93]">Keterlambatan di atas nilai ini akan dihitung denda potongan gaji</span>
        </div>

        <div class="space-y-1.5">
          <label for="tpl-min-overtime" class="block font-bold text-[#17171c]">
            Syarat Minimum Lembur ({templateMinOvertime} menit)
          </label>
          <input
            id="tpl-min-overtime"
            type="range"
            min="15"
            max="120"
            step="15"
            bind:value={templateMinOvertime}
            class="w-full accent-[#17171c] cursor-pointer"
          />
          <span class="text-[11px] text-[#8e8e93]">Kelebihan jam kerja di bawah batas ini tidak dihitung upah lembur</span>
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2 border-t border-[#f2f2f4]">
        <button
          type="button"
          onclick={() => (isTemplateBuilderOpen = false)}
          class="px-4 py-2.5 border border-[#e5e5ea] hover:bg-[#f4f4f6] text-[#686873] text-xs font-semibold rounded-full cursor-pointer transition-all"
        >
          Tutup
        </button>
        <button
          type="button"
          disabled={isSubmittingTemplate || !templateName.trim()}
          onclick={handleCreateTemplate}
          class="px-5 py-2.5 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full cursor-pointer transition-all disabled:opacity-50 flex items-center gap-1.5 shadow-xs"
        >
          <Plus class="w-4 h-4" />
          <span>{isSubmittingTemplate ? 'Menyimpan...' : 'Buat Template Shift'}</span>
        </button>
      </div>
    </div>
  {/if}

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
    <!-- MOBILE VIEW (< 768px / md): Interactive Day Carousel -->
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
        </div>

        <!-- Staff shifts list for this active day -->
        {#if staffList.length === 0}
          <p class="text-xs text-[#8e8e93] text-center py-6">Belum ada karyawan terdaftar.</p>
        {:else}
          <div class="space-y-2.5">
            {#each staffList as staff}
              {@const staffUserId = staff.user_id || staff.id}
              {@const isMe = staffUserId === currentUser.id}
              {@const shift = dynamicShifts.find(
                (s) => (s.assigned_user?.id === staffUserId || s.assigned_user?.id === staff.id) && s.date === activeMobileDay.iso
              )}
              <div class="flex items-center justify-between p-3 rounded-xl border gap-2 {isMe ? 'bg-[#eff6ff]/40 border-[#bfdbfe]' : 'bg-[#fbfbfa] border-[#ececee]'}">
                <div class="min-w-0">
                  <div class="font-semibold text-xs text-[#17171c] truncate flex items-center gap-1.5">
                    <span>{staff.name}</span>
                    {#if isMe}
                      <span class="px-1.5 py-0.2 rounded-md bg-[#eff6ff] text-[#2563eb] text-[9.5px] font-mono font-bold border border-[#bfdbfe]">
                        Anda
                      </span>
                    {/if}
                  </div>
                  <div class="text-[11px] text-[#8e8e93] truncate">{staff.job_title || 'Staf'}</div>
                </div>

                {#if shift && shift.template}
                  {#if isMe && activeMobileDay.iso >= todayStr}
                    <button
                      type="button"
                      onclick={() => openSwapForShift(shift.id)}
                      class="px-3 py-1.5 rounded-xl bg-[#17171c] text-white text-right cursor-pointer hover:bg-black transition-all shadow-2xs shrink-0"
                      title="Klik untuk ajukan tukar shift ini"
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
                  <span class="text-xs font-mono text-[#a1a1aa] px-3 py-1.5">
                    Off
                  </span>
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
        <span class="text-xs text-[#8e8e93] font-mono">Klik shift Anda untuk mengajukan pertukaran jadwal</span>
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
            {#if staffList.length === 0}
              <tr>
                <td colspan={8} class="py-10 text-center text-[#8e8e93] font-mono">
                  Belum ada staf yang terdaftar di cabang ini.
                </td>
              </tr>
            {:else}
              {#each staffList as staff}
                {@const staffUserId = staff.user_id || staff.id}
                {@const isMe = staffUserId === currentUser.id}
                <tr class="hover:bg-[#fafafc] transition-colors {isMe ? 'bg-[#eff6ff]/25' : ''}">
                  <td class="py-4 px-5 font-medium text-[#17171c]">
                    <div class="font-bold text-xs flex items-center gap-1.5">
                      <span>{staff.name}</span>
                      {#if isMe}
                        <span class="px-1.5 py-0.2 rounded-md bg-[#eff6ff] text-[#2563eb] text-[9.5px] font-mono font-bold border border-[#bfdbfe]">
                          Anda
                        </span>
                      {/if}
                    </div>
                    <div class="text-[11px] text-[#8e8e93] font-normal">{staff.job_title || 'Staf'}</div>
                  </td>

                  {#each weekDays as day}
                    {@const shift = dynamicShifts.find(
                      (s) => (s.assigned_user?.id === staffUserId || s.assigned_user?.id === staff.id) && s.date === day.iso
                    )}
                    <td class={`py-2.5 px-2 text-center align-middle ${day.isToday ? 'bg-[#fafafc]' : ''}`}>
                      {#if shift && shift.template}
                        {#if isMe && day.iso >= todayStr}
                          <button
                            type="button"
                            onclick={() => openSwapForShift(shift.id)}
                            class="w-full py-2 px-2.5 rounded-xl text-[10.5px] font-mono font-semibold bg-[#17171c] text-white hover:bg-black transition-all cursor-pointer block truncate text-center shadow-2xs"
                            title={`Shift Anda: ${shift.template.name} (${shift.template.expected_clock_in} - ${shift.template.expected_clock_out}) - Klik untuk tukar`}
                          >
                            <div class="font-bold truncate">{shift.template.name}</div>
                            <div class="text-[9px] text-white/75 mt-0.5">
                              {shift.template.expected_clock_in?.substring(0, 5)} - {shift.template.expected_clock_out?.substring(0, 5)}
                            </div>
                          </button>
                        {:else}
                          <div
                            class="w-full py-2 px-2.5 rounded-xl text-[10.5px] font-mono font-semibold bg-[#f4f4f6] text-[#686873] border border-[#e5e5ea] block truncate text-center"
                            title={`${shift.template.name} (${shift.template.expected_clock_in} - ${shift.template.expected_clock_out})`}
                          >
                            <div class="font-semibold truncate text-[#17171c]">{shift.template.name}</div>
                            <div class="text-[9px] text-[#8e8e93] mt-0.5">
                              {shift.template.expected_clock_in?.substring(0, 5)} - {shift.template.expected_clock_out?.substring(0, 5)}
                            </div>
                          </div>
                        {/if}
                      {:else}
                        <span class="text-[10.5px] font-mono text-[#a1a1aa] block text-center py-2.5">
                          Off
                        </span>
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
                {@const isMe = shift.assigned_user?.id === currentUser.id || shift.actual_user?.id === currentUser.id}
                <tr class="hover:bg-[#fafafc] transition-colors {isMe ? 'bg-[#eff6ff]/30' : ''}">
                  <td class="py-3 px-5 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                      <span class="font-mono font-bold text-[#17171c]">{shift.date}</span>
                      <span class="text-[11px] text-[#8e8e93]">({getDayNameIndo(shift.date)})</span>
                    </div>
                  </td>
                  <td class="py-3 px-4 whitespace-nowrap">
                    <div class="font-semibold text-[#17171c] flex items-center gap-1.5">
                      <span>{shift.assigned_user?.name || 'Staf'}</span>
                      {#if isMe}
                        <span class="px-1.5 py-0.2 rounded-md bg-[#eff6ff] text-[#2563eb] text-[9.5px] font-mono font-bold border border-[#bfdbfe]">
                          Anda
                        </span>
                      {/if}
                    </div>
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
                    {#if isMe && shift.date >= todayStr && (!shift.is_swap || shift.swap_status === 'REJECTED')}
                      <button
                        type="button"
                        onclick={() => openSwapForShift(shift.id)}
                        class="px-2.5 py-1 text-[11px] font-semibold text-[#17171c] bg-[#f4f4f6] hover:bg-[#e5e5ea] rounded-lg transition-all cursor-pointer border border-[#e5e5ea] inline-flex items-center gap-1 shadow-2xs"
                      >
                        <RefreshCw class="w-3 h-3 text-[#2563eb]" />
                        <span>Ajukan Tukar</span>
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
</div>

<!-- Modal Permohonan Tukar Shift -->
{#if isSwapModalOpen}
  <div class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4 font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-xl space-y-5 animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#eff6ff] text-[#2563eb] flex items-center justify-center border border-[#bfdbfe]">
            <RefreshCw class="w-5 h-5" />
          </div>
          <div>
            <h3 class="font-bold text-base text-[#17171c]">Permohonan Tukar Shift</h3>
            <p class="text-xs text-[#8e8e93]">Ajukan penggantian jadwal dengan rekan kerja</p>
          </div>
        </div>
        <button
          type="button"
          onclick={() => (isSwapModalOpen = false)}
          class="p-2 text-[#8e8e93] hover:text-[#17171c] hover:bg-[#f4f4f6] rounded-xl cursor-pointer transition-all"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      {#if swapErrorMessage}
        <div class="p-3.5 bg-[#fef2f2] border border-[#fecaca] rounded-xl text-[#991b1b] text-xs font-medium flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{swapErrorMessage}</span>
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div class="space-y-1.5">
          <label for="swap-shift-select" class="block font-bold text-[#17171c]">Pilih Jadwal Shift Anda</label>
          {#if myUpcomingShifts.length === 0}
            <div class="p-3.5 bg-[#f8f8fa] text-[#8e8e93] text-xs font-mono rounded-2xl border border-[#e5e5ea]">
              Anda tidak memiliki jadwal shift mendatang aktif pada periode ini.
            </div>
          {:else}
            <div class="relative">
              <select
                id="swap-shift-select"
                bind:value={selectedShiftAssignmentId}
                class="appearance-none w-full border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-xl px-4 pr-10 py-2.5 bg-[#f8f8fa] hover:bg-white text-xs text-[#17171c] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
              >
                <option value="">Pilih Shift yang Ingin Ditukar...</option>
                {#each myUpcomingShifts as s}
                  <option value={s.id}>
                    {s.date} &bull; {s.template?.name || 'Shift'} ({s.template?.expected_clock_in?.substring(0, 5)} - {s.template?.expected_clock_out?.substring(0, 5)})
                  </option>
                {/each}
              </select>
              <ChevronDown class="w-4 h-4 text-[#8e8e93] absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>
          {/if}
        </div>

        <div class="space-y-1.5">
          <label for="swap-target-user" class="block font-bold text-[#17171c]">Pilih Rekan Pengganti (Staf Cabang)</label>
          <div class="relative">
            <select
              id="swap-target-user"
              bind:value={targetUserId}
              class="appearance-none w-full border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-xl px-4 pr-10 py-2.5 bg-[#f8f8fa] hover:bg-white text-xs text-[#17171c] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
            >
              <option value="">Pilih Rekan Kerja Pengganti...</option>
              {#each availableColleagues as col}
                <option value={col.id}>{col.name} ({col.role || 'Staf'})</option>
              {/each}
            </select>
            <ChevronDown class="w-4 h-4 text-[#8e8e93] absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button
          type="button"
          onclick={() => (isSwapModalOpen = false)}
          class="flex-1 py-3 border border-[#e5e5ea] hover:bg-[#f4f4f6] text-[#686873] text-xs font-semibold rounded-full cursor-pointer transition-all"
        >
          Batal
        </button>
        <button
          type="button"
          disabled={isSubmittingSwap || !selectedShiftAssignmentId || !targetUserId}
          onclick={handleConfirmSwap}
          class="flex-1 py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full cursor-pointer transition-all disabled:opacity-50 shadow-xs"
        >
          {isSubmittingSwap ? 'Mengajukan...' : 'Kirim Permohonan'}
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Modal Penetapan Shift (For Admin/Manager in Staff View if applicable) -->
{#if isAssignModalOpen}
  <AssignShiftModal
    isOpen={isAssignModalOpen}
    initialUserId={modalInitialUserId}
    initialDate={modalInitialDate}
    initialTemplateId={modalInitialTemplateId}
    initialAssignmentId={modalInitialAssignmentId}
    {shiftTemplates}
    staffMembers={staffList.map((s) => ({
      id: s.id,
      user_id: s.user_id || s.id,
      name: s.name,
      email: s.email || '',
      job_title: s.job_title || 'Staf',
      role: s.role,
      branch_id: s.branch_id || null,
      branch_name: s.branch_name || '',
      base_salary: 0,
      is_active: true,
    }))}
    onClose={() => (isAssignModalOpen = false)}
    onAssign={async (templateId, userId, date) => {
      if (onAssignShift) {
        await onAssignShift(templateId, userId, date);
      }
      isAssignModalOpen = false;
      await loadShiftsForRange();
    }}
  />
{/if}
