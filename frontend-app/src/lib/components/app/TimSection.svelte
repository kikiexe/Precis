<script lang="ts">
  import { Users, Camera, Calendar, Wallet } from 'lucide-svelte';
  import type {
    AttendanceRecord,
    PendingSwapItem,
    CashAdvance,
    ShiftRosterItem,
    ShiftTemplateItem,
    TeamMember,
    User,
  } from '../../types/app';
  import StaffListTab from './team/StaffListTab.svelte';
  import AttendanceWallTab from './team/AttendanceWallTab.svelte';
  import ShiftRosterTab from './team/ShiftRosterTab.svelte';
  import CashAdvanceApprovalTab from './team/CashAdvanceApprovalTab.svelte';
  import InviteMemberModal from './team/modals/InviteMemberModal.svelte';
  import EditMemberModal from './team/modals/EditMemberModal.svelte';

  interface Props {
    currentUser?: User;
    initialSubTab?: string;
    teamMembers?: TeamMember[];
    attendances?: AttendanceRecord[];
    rosterShifts?: ShiftRosterItem[];
    shiftTemplates?: ShiftTemplateItem[];
    pendingSwaps?: PendingSwapItem[];
    pendingKasbons?: CashAdvance[];
    onApproveSwap: (swapId: string) => void;
    onRejectSwap: (swapId: string) => void;
    onApproveKasbon: (kasbonId: string) => void;
    onRejectKasbon: (kasbonId: string) => void;
    onAssignShift?: (templateId: string, userId: string, date: string) => Promise<void>;
    onRefreshMembers?: () => void;
  }

  let {
    currentUser,
    initialSubTab = 'staf',
    teamMembers = [],
    attendances = [],
    rosterShifts = [],
    shiftTemplates = [],
    pendingSwaps = [],
    pendingKasbons = [],
    onApproveSwap,
    onRejectSwap,
    onApproveKasbon,
    onRejectKasbon,
    onAssignShift,
    onRefreshMembers,
  }: Props = $props();

  let activeSubTab = $state<'staf' | 'presensi' | 'shift' | 'kasbon'>('staf');
  let selectedBranchFilter = $state('ALL');

  // Modals state
  let isAddMemberModalOpen = $state(false);
  let editingMember = $state<TeamMember | null>(null);

  let staffListTabRef = $state<{ loadPendingInvitations: () => Promise<void> } | null>(null);

  $effect(() => {
    if (
      initialSubTab === 'staf' ||
      initialSubTab === 'shift' ||
      initialSubTab === 'kasbon' ||
      initialSubTab === 'presensi'
    ) {
      activeSubTab = initialSubTab as 'staf' | 'presensi' | 'shift' | 'kasbon';
    }
  });

  let availableBranches = $derived.by(() => {
    const branches = new Set<string>();
    teamMembers.forEach((m) => {
      if (m.branch_name && m.branch_name !== 'Semua Cabang') {
        branches.add(m.branch_name);
      }
    });
    attendances.forEach((a) => {
      if (a.branch_name) branches.add(a.branch_name);
    });
    if (currentUser?.branch_name) {
      branches.add(currentUser.branch_name);
    }
    return Array.from(branches);
  });

  let filteredMembers = $derived(
    teamMembers.filter((m) => {
      if (selectedBranchFilter === 'ALL') return true;
      return (
        m.branch_id === selectedBranchFilter ||
        (m.branch_name && m.branch_name.toLowerCase().includes(selectedBranchFilter.toLowerCase()))
      );
    })
  );

  let filteredStaffEmployees = $derived(
    filteredMembers.filter((m) => m.role !== 'OWNER')
  );

  let staffEmployees = $derived(teamMembers.filter((m) => m.role !== 'OWNER'));
</script>

<div class="space-y-4 sm:space-y-6 font-sans pb-4">
  <!-- Top Segmented Navigation Wrapper -->
  <div class="bg-white border border-[#d9d9dd] rounded-3xl p-2 sm:p-2.5 flex items-center justify-between gap-2 overflow-x-auto no-scrollbar">
    <div class="flex items-center gap-1.5 w-full sm:w-auto bg-[#eeece7]/40 sm:bg-transparent p-1 sm:p-0 rounded-full">
      <!-- SUBTAB 1: DAFTAR KARYAWAN -->
      <button
        type="button"
        title={`Anggota (${staffEmployees.length})`}
        onclick={() => (activeSubTab = 'staf')}
        class={`text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-2 ${
          activeSubTab === 'staf'
            ? 'flex-1 sm:flex-initial bg-[#17171c] text-white shadow-xs px-4 py-2 sm:px-5 sm:py-2.5'
            : 'text-[#616161] hover:text-[#212121] hover:bg-white/80 w-9 h-9 sm:w-auto sm:h-auto sm:p-2.5 shrink-0'
        }`}
      >
        <Users class="w-4 h-4 shrink-0" />
        {#if activeSubTab === 'staf'}
          <span class="whitespace-nowrap truncate">Anggota ({staffEmployees.length})</span>
        {/if}
      </button>

      <!-- SUBTAB 2: WALL OF FACES PRESENSI -->
      <button
        type="button"
        title={`Presensi (${attendances.length})`}
        onclick={() => (activeSubTab = 'presensi')}
        class={`text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-2 ${
          activeSubTab === 'presensi'
            ? 'flex-1 sm:flex-initial bg-[#17171c] text-white shadow-xs px-4 py-2 sm:px-5 sm:py-2.5'
            : 'text-[#616161] hover:text-[#212121] hover:bg-white/80 w-9 h-9 sm:w-auto sm:h-auto sm:p-2.5 shrink-0'
        }`}
      >
        <Camera class="w-4 h-4 shrink-0" />
        {#if activeSubTab === 'presensi'}
          <span class="whitespace-nowrap truncate">Presensi ({attendances.length})</span>
        {/if}
      </button>

      <!-- SUBTAB 3: ROSTER SHIFT -->
      <button
        type="button"
        title={`Shift (${rosterShifts.length})`}
        onclick={() => (activeSubTab = 'shift')}
        class={`text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-2 relative ${
          activeSubTab === 'shift'
            ? 'flex-1 sm:flex-initial bg-[#17171c] text-white shadow-xs px-4 py-2 sm:px-5 sm:py-2.5'
            : 'text-[#616161] hover:text-[#212121] hover:bg-white/80 w-9 h-9 sm:w-auto sm:h-auto sm:p-2.5 shrink-0'
        }`}
      >
        <Calendar class="w-4 h-4 shrink-0" />
        {#if activeSubTab === 'shift'}
          <span class="whitespace-nowrap truncate">Shift ({rosterShifts.length})</span>
        {/if}
        {#if pendingSwaps.length > 0}
          <span class="w-2 h-2 rounded-full bg-[#e5484d] animate-ping absolute top-1 right-1"></span>
        {/if}
      </button>

      <!-- SUBTAB 4: KASBON -->
      <button
        type="button"
        title="Kasbon"
        onclick={() => (activeSubTab = 'kasbon')}
        class={`text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-2 relative ${
          activeSubTab === 'kasbon'
            ? 'flex-1 sm:flex-initial bg-[#17171c] text-white shadow-xs px-4 py-2 sm:px-5 sm:py-2.5'
            : 'text-[#616161] hover:text-[#212121] hover:bg-white/80 w-9 h-9 sm:w-auto sm:h-auto sm:p-2.5 shrink-0'
        }`}
      >
        <Wallet class="w-4 h-4 shrink-0" />
        {#if activeSubTab === 'kasbon'}
          <span class="whitespace-nowrap truncate">Kasbon</span>
          {#if pendingKasbons.length > 0}
            <span class="px-2 py-0.5 rounded-full bg-[#e5484d] text-white text-[10px] font-mono font-medium">
              {pendingKasbons.length}
            </span>
          {/if}
        {:else if pendingKasbons.length > 0}
          <span class="w-2 h-2 rounded-full bg-[#e5484d] absolute top-1 right-1"></span>
        {/if}
      </button>
    </div>
  </div>

  <!-- SUBTAB VIEWS -->
  {#if activeSubTab === 'staf'}
    <StaffListTab
      bind:this={staffListTabRef}
      {filteredStaffEmployees}
      {availableBranches}
      {selectedBranchFilter}
      onSelectBranchFilter={(b) => (selectedBranchFilter = b)}
      onOpenAddModal={() => (isAddMemberModalOpen = true)}
      onOpenEditModal={(m) => (editingMember = m)}
      {onRefreshMembers}
    />
  {:else if activeSubTab === 'presensi'}
    <AttendanceWallTab
      {attendances}
      {availableBranches}
      {selectedBranchFilter}
      onSelectBranchFilter={(b) => (selectedBranchFilter = b)}
    />
  {:else if activeSubTab === 'shift'}
    <ShiftRosterTab
      {rosterShifts}
      {shiftTemplates}
      {pendingSwaps}
      {filteredStaffEmployees}
      {availableBranches}
      {selectedBranchFilter}
      onSelectBranchFilter={(b) => (selectedBranchFilter = b)}
      {onApproveSwap}
      {onRejectSwap}
      {onAssignShift}
    />
  {:else if activeSubTab === 'kasbon'}
    <CashAdvanceApprovalTab
      {pendingKasbons}
      {availableBranches}
      {selectedBranchFilter}
      onSelectBranchFilter={(b) => (selectedBranchFilter = b)}
      {onApproveKasbon}
      {onRejectKasbon}
    />
  {/if}
</div>

<!-- Modal: Invite Member -->
<InviteMemberModal
  isOpen={isAddMemberModalOpen}
  initialBranchId={currentUser?.branch_id && !currentUser.branch_id.includes('default') ? currentUser.branch_id : null}
  onClose={() => (isAddMemberModalOpen = false)}
  onSuccess={() => {
    staffListTabRef?.loadPendingInvitations();
    if (onRefreshMembers) onRefreshMembers();
  }}
/>

<!-- Modal: Edit Member -->
<EditMemberModal
  member={editingMember}
  onClose={() => (editingMember = null)}
  onSuccess={() => {
    if (onRefreshMembers) onRefreshMembers();
  }}
/>
