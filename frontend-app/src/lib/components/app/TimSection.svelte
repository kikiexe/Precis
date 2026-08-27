<script lang="ts">
  import { onMount } from 'svelte';
  import { Users, Camera, Calendar, Wallet, ShieldCheck } from 'lucide-svelte';
  import type {
    AttendanceRecord,
    PendingSwapItem,
    CashAdvance,
    ShiftRosterItem,
    ShiftTemplateItem,
    TeamMember,
    User,
    WorkspaceRole,
    PermissionsCatalog,
  } from '../../types/app';
  import StaffListTab from './team/StaffListTab.svelte';
  import RoleListTab from './team/RoleListTab.svelte';
  import AttendanceWallTab from './team/AttendanceWallTab.svelte';
  import ShiftRosterTab from './team/ShiftRosterTab.svelte';
  import CashAdvanceApprovalTab from './team/CashAdvanceApprovalTab.svelte';
  import InviteMemberModal from './team/modals/InviteMemberModal.svelte';
  import EditMemberModal from './team/modals/EditMemberModal.svelte';
  import { roleService } from '../../services/role-service';

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
    onDeleteShift?: (assignmentId: string) => Promise<void>;
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
    onDeleteShift,
    onRefreshMembers,
  }: Props = $props();

  let activeSubTab = $state<'staf' | 'peran' | 'presensi' | 'shift' | 'kasbon'>('staf');

  // Dynamic Workspace Roles & Permissions Catalog
  let roles = $state<WorkspaceRole[]>([]);
  let permissionsCatalog = $state<PermissionsCatalog | null>(null);
  let isLoadingRoles = $state(false);

  // Modals state
  let isAddMemberModalOpen = $state(false);
  let editingMember = $state<TeamMember | null>(null);

  let staffListTabRef = $state<{ loadPendingInvitations: () => Promise<void> } | null>(null);

  async function loadRolesAndCatalog() {
    isLoadingRoles = true;
    try {
      const [fetchedRoles, fetchedCatalog] = await Promise.all([
        roleService.getRoles(),
        permissionsCatalog ? Promise.resolve(permissionsCatalog) : roleService.getPermissionsCatalog(),
      ]);
      roles = fetchedRoles;
      if (!permissionsCatalog) {
        permissionsCatalog = fetchedCatalog;
      }
    } catch {
      // Graceful fallback
    } finally {
      isLoadingRoles = false;
    }
  }

  onMount(() => {
    loadRolesAndCatalog();
  });

  let isOwnerOrAdmin = $derived(
    currentUser?.role === 'OWNER' ||
    currentUser?.role === 'ADMIN' ||
    Boolean(currentUser?.permissions?.includes('roles.manage'))
  );

  $effect(() => {
    if (initialSubTab === 'peran' && !isOwnerOrAdmin) {
      activeSubTab = 'staf';
    } else if (
      initialSubTab === 'staf' ||
      (initialSubTab === 'peran' && isOwnerOrAdmin) ||
      initialSubTab === 'shift' ||
      initialSubTab === 'kasbon' ||
      initialSubTab === 'presensi'
    ) {
      activeSubTab = initialSubTab as 'staf' | 'peran' | 'presensi' | 'shift' | 'kasbon';
    }
  });

  let staffEmployees = $derived(teamMembers.filter((m) => m.role !== 'OWNER'));

  const navTabs = $derived([
    { id: 'staf' as const, label: 'Anggota', count: staffEmployees.length, icon: Users },
    ...(isOwnerOrAdmin ? [{ id: 'peran' as const, label: 'Role', count: roles.length, icon: ShieldCheck }] : []),
    { id: 'presensi' as const, label: 'Presensi', count: attendances.length, icon: Camera },
    { id: 'shift' as const, label: 'Jadwal Shift', count: rosterShifts.length, icon: Calendar, badge: pendingSwaps.length > 0 },
    { id: 'kasbon' as const, label: 'Kasbon', count: pendingKasbons.length, icon: Wallet, highlightCount: pendingKasbons.length > 0 },
  ]);
</script>

<div class="space-y-6 font-sans pb-8">
  <!-- Top Airy Navigation Bar with Floating Segmented Control -->
  <div class="flex items-center justify-between gap-4 overflow-x-auto no-scrollbar py-1">
    <div class="inline-flex items-center gap-1.5 p-1.5 bg-white border border-[#e5e5ea] rounded-2xl shadow-2xs">
      {#each navTabs as tab}
        {@const Icon = tab.icon}
        {@const isActive = activeSubTab === tab.id}
        <button
          type="button"
          onclick={() => {
            activeSubTab = tab.id;
            if (tab.id === 'peran') loadRolesAndCatalog();
          }}
          class={`relative px-4 py-2 rounded-xl text-xs font-medium transition-all duration-200 cursor-pointer flex items-center gap-2 shrink-0 ${
            isActive
              ? 'bg-[#17171c] text-white shadow-xs font-semibold'
              : 'text-[#686873] hover:text-[#17171c] hover:bg-[#f4f4f6]'
          }`}
        >
          <Icon class={`w-4 h-4 ${isActive ? 'text-white' : 'text-[#8e8e93]'}`} />
          <span class="whitespace-nowrap">{tab.label}</span>
          
          {#if tab.count !== undefined && tab.count > 0}
            <span
              class={`px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold ${
                isActive
                  ? 'bg-white/20 text-white'
                  : tab.highlightCount
                    ? 'bg-[#e5484d] text-white'
                    : 'bg-[#eeece7] text-[#616161]'
              }`}
            >
              {tab.count}
            </span>
          {/if}

          {#if tab.badge && !isActive}
            <span class="w-2 h-2 rounded-full bg-[#e5484d] absolute top-1.5 right-1.5"></span>
          {/if}
        </button>
      {/each}
    </div>
  </div>

  <!-- SUBTAB VIEWS WITH BREATHABLE PADDING & AIRY LAYOUT -->
  <div class="min-w-0 animate-in fade-in duration-200">
    {#if activeSubTab === 'staf'}
      <StaffListTab
        bind:this={staffListTabRef}
        {currentUser}
        filteredStaffEmployees={staffEmployees}
        onOpenAddModal={() => {
          loadRolesAndCatalog();
          isAddMemberModalOpen = true;
        }}
        onOpenEditModal={(m) => {
          loadRolesAndCatalog();
          editingMember = m;
        }}
        {onRefreshMembers}
      />
    {:else if activeSubTab === 'peran'}
      <RoleListTab
        {roles}
        catalog={permissionsCatalog}
        isLoading={isLoadingRoles}
        onRefresh={async () => {
          await loadRolesAndCatalog();
          if (onRefreshMembers) onRefreshMembers();
        }}
      />
    {:else if activeSubTab === 'presensi'}
      <AttendanceWallTab
        {attendances}
      />
    {:else if activeSubTab === 'shift'}
      <ShiftRosterTab
        {rosterShifts}
        {shiftTemplates}
        {pendingSwaps}
        filteredStaffEmployees={staffEmployees}
        {onApproveSwap}
        {onRejectSwap}
        {onAssignShift}
        {onDeleteShift}
      />
    {:else if activeSubTab === 'kasbon'}
      <CashAdvanceApprovalTab
        {pendingKasbons}
        {onApproveKasbon}
        {onRejectKasbon}
      />
    {/if}
  </div>
</div>

<!-- Modal: Invite Member -->
<InviteMemberModal
  isOpen={isAddMemberModalOpen}
  {roles}
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
  {roles}
  onClose={() => (editingMember = null)}
  onSuccess={() => {
    if (onRefreshMembers) onRefreshMembers();
  }}
/>
