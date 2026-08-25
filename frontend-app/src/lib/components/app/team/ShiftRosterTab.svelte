<script lang="ts">
  import { Calendar, Clock, ChevronDown, ArrowRightLeft, Check } from 'lucide-svelte';
  import type { ShiftRosterItem, ShiftTemplateItem, PendingSwapItem, TeamMember } from '../../../types/app';
  import AssignShiftModal from './modals/AssignShiftModal.svelte';

  interface Props {
    rosterShifts: ShiftRosterItem[];
    shiftTemplates: ShiftTemplateItem[];
    pendingSwaps: PendingSwapItem[];
    filteredStaffEmployees: TeamMember[];
    availableBranches: string[];
    selectedBranchFilter: string;
    onSelectBranchFilter: (branch: string) => void;
    onApproveSwap: (swapId: string) => void;
    onRejectSwap: (swapId: string) => void;
    onAssignShift?: (templateId: string, userId: string, date: string) => Promise<void>;
  }

  let {
    rosterShifts = [],
    shiftTemplates = [],
    pendingSwaps = [],
    filteredStaffEmployees = [],
    availableBranches = [],
    selectedBranchFilter = 'ALL',
    onSelectBranchFilter,
    onApproveSwap,
    onRejectSwap,
    onAssignShift,
  }: Props = $props();

  let isAssignModalOpen = $state(false);
  let modalInitialUserId = $state('');
  let modalInitialDate = $state('');
  let modalInitialTemplateId = $state('');

  let filteredRosterShifts = $derived(
    rosterShifts.filter((s) => {
      if (selectedBranchFilter === 'ALL') return true;
      return (
        s.template?.branch_id === selectedBranchFilter ||
        (s.template?.branch_name && s.template.branch_name.toLowerCase().includes(selectedBranchFilter.toLowerCase()))
      );
    })
  );

  let weekDays = $derived.by(() => {
    const now = new Date();
    const dayOfWeek = now.getDay();
    const diffToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
    const monday = new Date(now);
    monday.setDate(now.getDate() + diffToMonday);

    const days = [];
    for (let i = 0; i < 7; i++) {
      const d = new Date(monday);
      d.setDate(monday.getDate() + i);
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, '0');
      const dayStr = String(d.getDate()).padStart(2, '0');
      days.push({
        iso: `${y}-${m}-${dayStr}`,
        dayName: d.toLocaleDateString('id-ID', { weekday: 'short' }),
        dayDate: `${dayStr}/${m}`,
        isToday: d.toDateString() === now.toDateString(),
      });
    }
    return days;
  });

  function openAssignModal(userId: string, date: string, templateId?: string) {
    modalInitialUserId = userId;
    modalInitialDate = date;
    modalInitialTemplateId = templateId || shiftTemplates[0]?.id || '';
    isAssignModalOpen = true;
  }
</script>

<div class="space-y-6 font-sans">
  <!-- Section Header & Quick Assign/Template Controls -->
  <div class="bg-white border border-[#d9d9dd] rounded-3xl p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h2 class="text-base font-medium text-[#212121]">Visual Shift Planner &amp; Schedule Matrix</h2>
      <p class="text-xs text-[#75758a]">Matriks jadwal penugasan staf mingguan (Senin s/d Minggu)</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
      {#if availableBranches.length > 0}
        <div class="relative shrink-0 max-w-[170px] sm:max-w-xs">
          <select
            value={selectedBranchFilter}
            onchange={(e) => onSelectBranchFilter(e.currentTarget.value)}
            class="appearance-none px-3 pr-7 py-2 bg-[#eeece7]/50 hover:bg-[#eeece7] border border-[#d9d9dd] rounded-full text-xs text-[#212121] font-mono focus:outline-hidden cursor-pointer transition-all shadow-2xs truncate"
          >
            <option value="ALL">Semua Cabang</option>
            {#each availableBranches as branch}
              <option value={branch}>{branch}</option>
            {/each}
          </select>
          <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
        </div>
      {/if}

      <button
        type="button"
        onclick={() => {
          const todayIso = (weekDays.find((w) => w.isToday) || weekDays[0])?.iso || '';
          openAssignModal(filteredStaffEmployees[0]?.id || '', todayIso);
        }}
        class="px-4 py-2 bg-[#17171c] hover:bg-black text-white rounded-full text-xs font-medium flex items-center gap-1.5 cursor-pointer shadow-xs"
      >
        <Clock class="w-3.5 h-3.5" />
        <span>+ Tetapkan Shift</span>
      </button>
    </div>
  </div>

  <!-- Weekly Matrix Roster Board Table -->
  <div class="bg-white border border-[#d9d9dd] rounded-3xl overflow-hidden shadow-none">
    <div class="p-4 sm:p-5 border-b border-[#d9d9dd] flex items-center justify-between">
      <div class="flex items-center gap-2">
        <Calendar class="w-4 h-4 text-[#17171c]" />
        <h3 class="text-xs font-bold uppercase tracking-wider text-[#17171c]">Matriks Roster Mingguan</h3>
      </div>
      <span class="text-[10px] font-mono text-[#75758a]">Klik slot tanggal untuk atur penugasan</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-[#eeece7]/40 border-b border-[#d9d9dd] text-[#75758a] font-mono uppercase text-[10px]">
            <th class="py-3 px-4 min-w-[140px]">Nama Karyawan</th>
            {#each weekDays as day}
              <th class={`py-3 px-2 text-center min-w-[100px] ${day.isToday ? 'bg-[#17171c] text-white font-bold' : ''}`}>
                <div>{day.dayName}</div>
                <div class="text-[9px] font-normal opacity-80">{day.dayDate}</div>
              </th>
            {/each}
          </tr>
        </thead>
        <tbody class="divide-y divide-[#d9d9dd]">
          {#if filteredStaffEmployees.length === 0}
            <tr>
              <td colspan="8" class="py-10 text-center text-[#75758a]">
                Belum ada data shift untuk karyawan aktif di cabang terpilih
              </td>
            </tr>
          {:else}
            {#each filteredStaffEmployees as member}
              <tr class="hover:bg-[#eeece7]/20 transition-colors">
                <td class="py-3 px-4 font-medium text-[#17171c] bg-[#fafafa]/50 sticky left-0 z-10 border-r border-[#f2f2f2]">
                  <div class="truncate max-w-[130px]">{member.name}</div>
                  <div class="text-[10px] text-[#75758a] truncate font-normal">{member.job_title || 'Staf'}</div>
                </td>

                {#each weekDays as day}
                  {@const assigned = filteredRosterShifts.find((s) => s.date === day.iso && (s.assigned_user.id === member.id || s.actual_user?.id === member.id))}
                  <td class={`py-2 px-2 text-center align-middle ${day.isToday ? 'bg-[#eeece7]/30' : ''}`}>
                    {#if assigned}
                      <div class={`p-1.5 rounded-xl border text-[10px] space-y-0.5 ${
                        assigned.is_swap
                          ? 'bg-[#f1f5ff] border-[#1863dc]/30 text-[#1863dc]'
                          : 'bg-[#edfce9] border-[#00875a]/30 text-[#003c33]'
                      }`}>
                        <div class="font-semibold truncate">{assigned.template?.name || 'Shift'}</div>
                        <div class="font-mono text-[9px] text-[#616161]">
                          {assigned.template?.expected_clock_in || '07:00'}-{assigned.template?.expected_clock_out || '15:00'}
                        </div>
                        {#if assigned.is_swap}
                          <div class="text-[8px] font-mono font-bold uppercase">Tukar</div>
                        {/if}
                      </div>
                    {:else}
                      <button
                        type="button"
                        onclick={() => openAssignModal(member.id, day.iso)}
                        class="w-full py-2 border border-dashed border-[#d9d9dd] hover:border-[#17171c] hover:bg-white rounded-xl text-[10px] font-mono text-[#93939f] hover:text-[#17171c] cursor-pointer transition-all"
                        title="Klik untuk assign shift"
                      >
                        + Off
                      </button>
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

  <!-- Pending Swap Requests Queue -->
  <div class="space-y-3">
    <div class="flex items-center justify-between px-1">
      <h3 class="text-xs font-bold uppercase tracking-wider text-[#17171c] flex items-center gap-2">
        <ArrowRightLeft class="w-3.5 h-3.5" />
        <span>Antrean Otorisasi Tukar Shift ({pendingSwaps.length})</span>
      </h3>
    </div>

    {#if pendingSwaps.length === 0}
      <div class="bg-white border border-[#d9d9dd] rounded-2xl p-6 text-center text-[#75758a] text-xs">
        Tidak ada permohonan tukar shift yang sedang menunggu persetujuan.
      </div>
    {:else}
      <div class="space-y-3">
        {#each pendingSwaps as swap}
          <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:border-[#17171c] transition-all">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <ArrowRightLeft class="w-4 h-4 text-[#1863dc]" />
                <span class="font-bold text-xs text-[#17171c]">{swap.assigned_user.name} &rarr; {swap.actual_user.name}</span>
                <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-[#fff8e6] text-[#b45309] border border-[#fef3c7]">PENDING</span>
              </div>
              <div class="text-xs text-[#616161]">
                Shift: <strong>{swap.template?.name || 'Shift'}</strong> &bull; Tanggal: {swap.date}
              </div>
            </div>

            <div class="flex items-center gap-2">
              <button
                type="button"
                onclick={() => onApproveSwap(swap.id)}
                class="px-4 py-2 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-full flex items-center gap-1.5 cursor-pointer shadow-xs"
              >
                <Check class="w-3.5 h-3.5" />
                <span>Setujui</span>
              </button>
              <button
                type="button"
                onclick={() => onRejectSwap(swap.id)}
                class="px-4 py-2 bg-transparent hover:bg-[#ffefef] text-[#e5484d] text-xs font-medium rounded-full cursor-pointer"
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

<AssignShiftModal
  isOpen={isAssignModalOpen}
  staffMembers={filteredStaffEmployees}
  {shiftTemplates}
  initialUserId={modalInitialUserId}
  initialDate={modalInitialDate}
  initialTemplateId={modalInitialTemplateId}
  onClose={() => (isAssignModalOpen = false)}
  onAssign={async (templateId, userId, date) => {
    if (onAssignShift) {
      await onAssignShift(templateId, userId, date);
    }
  }}
/>
