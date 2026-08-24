<script lang="ts">
  import { onMount } from 'svelte';
  import {
    Users,
    Camera,
    Calendar,
    Wallet,
    Check,
    X,
    ChevronDown,
    Edit2,
    Trash2,
    Mail,
    Send,
    RotateCw,
    Clock,
    ArrowRightLeft,
  } from 'lucide-svelte';
  import type {
    AttendanceRecord,
    PendingSwapItem,
    CashAdvance,
    ShiftRosterItem,
    TeamMember,
    User,
    WorkspaceInvitationItem,
  } from '../../types/app';
  import { teamService } from '../../services/team-service';
  import { invitationService } from '../../services/invitation-service';

  interface Props {
    currentUser?: User;
    initialSubTab?: string;
    teamMembers?: TeamMember[];
    attendances?: AttendanceRecord[];
    rosterShifts?: ShiftRosterItem[];
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
    pendingSwaps = [],
    pendingKasbons = [],
    onApproveSwap,
    onRejectSwap,
    onApproveKasbon,
    onRejectKasbon,
    onRefreshMembers,
  }: Props = $props();

  let activeSubTab = $state<'staf' | 'presensi' | 'shift' | 'kasbon'>('staf');

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

  // State: Pending Invitations
  let pendingInvitations = $state<WorkspaceInvitationItem[]>([]);
  let isLoadingInvitations = $state(false);

  // Modal State: Undang Karyawan
  let isAddMemberModalOpen = $state(false);
  let isSubmittingMember = $state(false);
  let memberFormError = $state<string | null>(null);
  let memberFormSuccess = $state<string | null>(null);

  let newMemberForm = $state({
    email: '',
    job_title: 'Barista',
    role: 'STAFF' as 'ADMIN' | 'MANAGER' | 'STAFF',
    base_salary: 3000000,
    branch_id: null as string | null,
  });

  // Modal State: Edit Karyawan
  let editingMember = $state<TeamMember | null>(null);
  let editJobTitleInput = $state('Barista');
  let editRoleInput = $state<'ADMIN' | 'MANAGER' | 'STAFF'>('STAFF');
  let editSalaryInput = $state(3000000);
  let isSubmittingEdit = $state(false);

  // Filter Bar State
  let selectedBranchFilter = $state('ALL');

  onMount(() => {
    loadPendingInvitations();
  });

  async function loadPendingInvitations() {
    isLoadingInvitations = true;
    try {
      pendingInvitations = await invitationService.getPendingInvitations();
    } catch {
      pendingInvitations = [];
    } finally {
      isLoadingInvitations = false;
    }
  }

  function formatRp(num: number): string {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(num);
  }

  // Dynamic Branches extracted from members & attendances
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

  let filteredAttendances = $derived(
    attendances.filter((att) => {
      if (selectedBranchFilter === 'ALL') return true;
      return att.branch_name.toLowerCase().includes(selectedBranchFilter.toLowerCase());
    })
  );

  let onTimeCount = $derived(attendances.filter((a) => a.status === 'ON_TIME').length);
  let lateCount = $derived(attendances.filter((a) => a.status === 'LATE').length);

  let staffEmployees = $derived(teamMembers.filter((m) => m.role !== 'OWNER'));
  let totalEmployees = $derived(staffEmployees.length);
  let totalBasePayroll = $derived(
    staffEmployees.filter((m) => m.is_active).reduce((sum, m) => sum + m.base_salary, 0)
  );

  function formatAttendanceTime(dateStr?: string) {
    if (!dateStr) return '-';
    if (dateStr.includes('T')) {
      const d = new Date(dateStr);
      if (!isNaN(d.getTime())) {
        return (
          d.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
          }) + ' WIB'
        );
      }
    }
    return dateStr;
  }

  function formatExpiryDate(dateStr?: string) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (!isNaN(d.getTime())) {
      return d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    }
    return dateStr;
  }

  function openAddModal() {
    const initialBranchId =
      currentUser?.branch_id && !currentUser.branch_id.includes('default')
        ? currentUser.branch_id
        : null;

    newMemberForm = {
      email: '',
      job_title: 'Barista',
      role: 'STAFF',
      base_salary: 3000000,
      branch_id: initialBranchId,
    };
    memberFormError = null;
    memberFormSuccess = null;
    isAddMemberModalOpen = true;
  }

  async function handleSendInvitationSubmit() {
    if (!newMemberForm.email.trim()) {
      memberFormError = 'Alamat email calon karyawan wajib diisi.';
      return;
    }

    if (!newMemberForm.job_title.trim()) {
      memberFormError = 'Nama jabatan / posisi karyawan wajib diisi.';
      return;
    }

    isSubmittingMember = true;
    memberFormError = null;
    memberFormSuccess = null;

    try {
      await invitationService.inviteMember({
        email: newMemberForm.email.trim(),
        job_title: newMemberForm.job_title.trim(),
        role: newMemberForm.role,
        base_salary: Number(newMemberForm.base_salary),
        branch_id: newMemberForm.branch_id,
      });

      memberFormSuccess = `Email undangan berhasil dikirimkan ke ${newMemberForm.email.trim()}.`;
      await loadPendingInvitations();
      if (onRefreshMembers) onRefreshMembers();

      setTimeout(() => {
        isAddMemberModalOpen = false;
      }, 1200);
    } catch (err: unknown) {
      memberFormError = err instanceof Error ? err.message : 'Gagal mengirimkan email undangan.';
    } finally {
      isSubmittingMember = false;
    }
  }

  async function handleResendInvitation(id: string) {
    try {
      await invitationService.resendInvitation(id);
      alert('Email undangan berhasil dikirim ulang.');
      await loadPendingInvitations();
    } catch (err: unknown) {
      alert(err instanceof Error ? err.message : 'Gagal mengirim ulang email undangan.');
    }
  }

  async function handleCancelInvitation(id: string) {
    if (!confirm('Apakah Anda yakin ingin membatalkan undangan ini?')) {
      return;
    }

    try {
      await invitationService.cancelInvitation(id);
      await loadPendingInvitations();
    } catch (err: unknown) {
      alert(err instanceof Error ? err.message : 'Gagal membatalkan undangan.');
    }
  }

  function openEditModal(member: TeamMember) {
    editingMember = member;
    editJobTitleInput = member.job_title || 'Staf';
    editRoleInput = (member.role === 'OWNER' ? 'ADMIN' : member.role) as 'ADMIN' | 'MANAGER' | 'STAFF';
    editSalaryInput = member.base_salary;
    memberFormError = null;
    memberFormSuccess = null;
  }

  async function handleEditMemberSubmit() {
    if (!editingMember) return;
    if (!editJobTitleInput.trim()) {
      memberFormError = 'Nama jabatan / posisi karyawan wajib diisi.';
      return;
    }

    isSubmittingEdit = true;
    memberFormError = null;

    try {
      await teamService.updateMember(editingMember.id, {
        job_title: editJobTitleInput.trim(),
        role: editingMember.role === 'OWNER' ? undefined : editRoleInput,
        base_salary: Number(editSalaryInput),
      });

      if (onRefreshMembers) onRefreshMembers();
      editingMember = null;
    } catch (err: unknown) {
      memberFormError = err instanceof Error ? err.message : 'Gagal memperbarui data karyawan.';
    } finally {
      isSubmittingEdit = false;
    }
  }

  async function handleDeleteMember(member: TeamMember) {
    if (member.role === 'OWNER') {
      alert('Pemilik workspace tidak dapat dihapus.');
      return;
    }

    if (!confirm(`Hapus ${member.name} dari workspace ini?`)) {
      return;
    }

    try {
      await teamService.deleteMember(member.id);
      if (onRefreshMembers) onRefreshMembers();
    } catch (err: unknown) {
      alert(err instanceof Error ? err.message : 'Gagal menghapus karyawan.');
    }
  }
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

  <!-- SUBTAB 1: DAFTAR KARYAWAN & GAJI POKOK -->
  {#if activeSubTab === 'staf'}
    <div class="space-y-6">
      <!-- Overview Metric & Invite Staff Button -->
      <div class="bg-white border border-[#d9d9dd] rounded-3xl p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="grid grid-cols-2 sm:grid-cols-2 gap-4">
          <div>
            <div class="text-[10px] font-mono uppercase text-[#75758a]">Karyawan Aktif</div>
            <div class="text-xl font-bold font-mono text-[#17171c] mt-0.5">{totalEmployees} Orang</div>
          </div>

          <div>
            <div class="text-[10px] font-mono uppercase text-[#75758a]">Total Gaji Pokok Tim</div>
            <div class="text-xl font-bold font-mono text-[#17171c] mt-0.5">{formatRp(totalBasePayroll)}</div>
          </div>
        </div>

        <button
          type="button"
          onclick={openAddModal}
          class="px-5 py-2.5 bg-[#17171c] hover:bg-black text-white rounded-full text-xs font-medium flex items-center justify-center gap-2 cursor-pointer transition-all shadow-xs self-stretch sm:self-auto"
        >
          <Mail class="w-4 h-4" />
          <span>+ Undang Karyawan</span>
        </button>
      </div>

      <!-- SECTION 1: KARYAWAN AKTIF -->
      <div class="space-y-3">
        <div class="flex items-center justify-between px-1">
          <h3 class="text-xs font-bold uppercase tracking-wider text-[#17171c] flex items-center gap-2">
            <span>Karyawan Aktif ({staffEmployees.length})</span>
          </h3>
        </div>

        {#if staffEmployees.length === 0}
          <div class="bg-white border border-[#d9d9dd] rounded-3xl p-8 text-center text-[#93939f] space-y-2">
            <Users class="w-8 h-8 mx-auto text-[#93939f] opacity-40" />
            <p class="text-xs font-medium text-[#17171c]">Belum ada karyawan aktif</p>
            <p class="text-[11px] text-[#75758a]">Kirim undangan email kepada calon staf untuk bergabung ke workspace.</p>
          </div>
        {:else}
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
            {#each staffEmployees as member}
              <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 flex flex-col justify-between space-y-3 hover:border-[#17171c] transition-all">
                <div class="space-y-2">
                  <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                      <div class="w-9 h-9 rounded-full bg-[#eeece7] text-[#17171c] font-bold text-xs flex items-center justify-center shrink-0">
                        {member.name.charAt(0).toUpperCase()}
                      </div>
                      <div class="min-w-0">
                        <div class="font-medium text-sm text-[#17171c] truncate">{member.name}</div>
                        <div class="text-xs font-medium text-[#17171c]/80 truncate">{member.job_title || 'Staf'}</div>
                        <div class="text-[11px] text-[#75758a] font-mono truncate">{member.email}</div>
                      </div>
                    </div>

                    <span class={`text-[9px] font-mono font-semibold px-2 py-0.5 rounded-full shrink-0 ${
                      member.role === 'OWNER'
                        ? 'bg-[#17171c] text-white'
                        : member.role === 'MANAGER'
                        ? 'bg-[#e0edff] text-[#0052cc] border border-[#b3d4ff]'
                        : member.role === 'ADMIN'
                        ? 'bg-[#fdf2f8] text-[#be185d] border border-[#fbcfe8]'
                        : 'bg-[#eeece7] text-[#616161]'
                    }`}>
                      {member.role === 'MANAGER' ? 'MANAGER' : member.role === 'ADMIN' ? 'ADMIN' : member.role === 'OWNER' ? 'OWNER' : 'STAF'}
                    </span>
                  </div>

                  <div class="grid grid-cols-2 gap-2 py-2 px-3 bg-[#fafafa] border border-[#e5e5e5] rounded-xl text-xs">
                    <div>
                      <div class="text-[9px] text-[#75758a] uppercase font-mono">Penempatan</div>
                      <div class="font-medium text-[#17171c] truncate mt-0.5">{member.branch_name || 'Semua Cabang'}</div>
                    </div>

                    <div>
                      <div class="text-[9px] text-[#75758a] uppercase font-mono">Gaji Pokok</div>
                      <div class="font-mono font-bold text-[#17171c] truncate mt-0.5">
                        {member.role === 'OWNER' ? 'Pemilik Bisnis' : formatRp(member.base_salary)}
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Action Controls -->
                <div class="pt-1 flex items-center justify-end gap-2 border-t border-[#f2f2f2]">
                  <button
                    type="button"
                    onclick={() => openEditModal(member)}
                    class="px-3 py-1.5 text-xs font-medium border border-[#d9d9dd] hover:bg-[#f4f4f4] text-[#17171c] rounded-lg flex items-center gap-1.5 cursor-pointer"
                  >
                    <Edit2 class="w-3 h-3" />
                    <span>{member.role === 'OWNER' ? 'Atur PIN' : 'Edit Gaji & Posisi'}</span>
                  </button>

                  {#if member.role !== 'OWNER'}
                    <button
                      type="button"
                      onclick={() => handleDeleteMember(member)}
                      class="p-1.5 text-[#e5484d] hover:bg-[#fee2e2] rounded-lg transition-colors cursor-pointer"
                      title="Hapus Staf"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  {/if}
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </div>

      <!-- SECTION 2: MENUNGGU KONFIRMASI UNDANGAN (PENDING INVITATIONS) -->
      <div class="space-y-3 pt-2">
        <div class="flex items-center justify-between px-1">
          <h3 class="text-xs font-bold uppercase tracking-wider text-[#75758a] flex items-center gap-2">
            <span>Menunggu Konfirmasi Undangan ({pendingInvitations.length})</span>
          </h3>
          <button
            type="button"
            onclick={loadPendingInvitations}
            class="text-[11px] text-[#1863dc] hover:underline flex items-center gap-1 cursor-pointer font-mono"
          >
            <RotateCw class="w-3 h-3" />
            <span>Segarkan</span>
          </button>
        </div>

        {#if isLoadingInvitations}
          <div class="bg-white border border-[#d9d9dd] rounded-2xl p-6 text-center text-[#75758a] text-xs font-mono animate-pulse">
            Memuat daftar undangan pending...
          </div>
        {:else if pendingInvitations.length === 0}
          <div class="bg-white border border-[#d9d9dd] rounded-2xl p-6 text-center text-[#75758a] text-xs">
            Tidak ada undangan yang sedang pending. Seluruh calon staf telah menerima undangan atau belum dikirimi undangan baru.
          </div>
        {:else}
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
            {#each pendingInvitations as inv}
              <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 flex flex-col justify-between space-y-3 hover:border-[#17171c] transition-all">
                <div class="space-y-2.5">
                  <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                      <div class="w-9 h-9 rounded-full bg-[#edfce9] text-[#003c33] font-bold text-xs flex items-center justify-center shrink-0">
                        <Mail class="w-4 h-4" />
                      </div>
                      <div class="min-w-0">
                        <div class="font-medium text-sm text-[#17171c] truncate">{inv.email}</div>
                        <div class="text-xs font-medium text-[#17171c]/80 truncate">{inv.job_title}</div>
                      </div>
                    </div>

                    <span class="text-[9px] font-mono font-semibold px-2 py-0.5 rounded-full bg-[#fff8e6] text-[#b45309] border border-[#fef3c7] shrink-0">
                      MENUNGGU
                    </span>
                  </div>

                  <div class="grid grid-cols-2 gap-2 py-2 px-3 bg-[#fafafa] border border-[#e5e5e5] rounded-xl text-xs">
                    <div>
                      <div class="text-[9px] text-[#75758a] uppercase font-mono">Role &amp; Cabang</div>
                      <div class="font-medium text-[#17171c] truncate mt-0.5">{inv.role} &bull; {inv.branch_name}</div>
                    </div>

                    <div>
                      <div class="text-[9px] text-[#75758a] uppercase font-mono">Kedaluwarsa</div>
                      <div class="font-mono text-[11px] text-[#75758a] truncate mt-0.5 flex items-center gap-1">
                        <Clock class="w-3 h-3 shrink-0" />
                        <span>{formatExpiryDate(inv.expires_at)}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Invitation Actions -->
                <div class="pt-1 flex items-center justify-end gap-2 border-t border-[#f2f2f2]">
                  <button
                    type="button"
                    onclick={() => handleResendInvitation(inv.id)}
                    class="px-3 py-1.5 text-xs font-medium border border-[#d9d9dd] hover:bg-[#f4f4f4] text-[#17171c] rounded-lg flex items-center gap-1.5 cursor-pointer"
                  >
                    <Send class="w-3 h-3" />
                    <span>Kirim Ulang Email</span>
                  </button>

                  <button
                    type="button"
                    onclick={() => handleCancelInvitation(inv.id)}
                    class="px-3 py-1.5 text-xs font-medium text-[#e5484d] hover:bg-[#fee2e2] rounded-lg transition-colors cursor-pointer"
                  >
                    Batalkan
                  </button>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    </div>
  {/if}

  <!-- SUBTAB 2: WALL OF FACES PRESENSI -->
  {#if activeSubTab === 'presensi'}
    <div class="space-y-4">
      <!-- Dynamic Filter Bar -->
      <div class="bg-white border border-[#d9d9dd] rounded-2xl p-3 flex items-center justify-between gap-2 text-xs">
        <div class="flex items-center gap-3 font-mono">
          <div class="flex items-center gap-1.5 font-medium text-[11px] text-[#00875a]">
            <span class="w-2 h-2 rounded-full bg-[#00875a]"></span>
            <span>Tepat: {onTimeCount}</span>
          </div>
          <div class="flex items-center gap-1.5 font-medium text-[11px] text-[#e5484d]">
            <span class="w-2 h-2 rounded-full bg-[#e5484d]"></span>
            <span>Telat: {lateCount}</span>
          </div>
        </div>

        <div class="relative shrink-0">
          <select
            bind:value={selectedBranchFilter}
            class="appearance-none bg-[#eeece7]/80 hover:bg-[#eeece7] text-[#17171c] text-[11px] font-medium pl-2.5 pr-6 py-1 rounded-full border border-[#d9d9dd] cursor-pointer focus:outline-hidden transition-all shadow-2xs"
          >
            <option value="ALL">Semua Cabang</option>
            {#each availableBranches as branch}
              <option value={branch}>{branch}</option>
            {/each}
          </select>
          <ChevronDown class="w-3 h-3 text-[#75758a] absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" />
        </div>
      </div>

      <!-- Presensi Grid / Empty State -->
      {#if filteredAttendances.length === 0}
        <div class="bg-white border border-[#d9d9dd] rounded-3xl p-12 text-center text-[#93939f] space-y-2">
          <Camera class="w-8 h-8 mx-auto text-[#93939f] opacity-40" />
          <p class="text-xs font-medium text-[#17171c]">Belum ada aktivitas presensi selfie</p>
          <p class="text-[11px] text-[#75758a]">Foto selfie karyawan saat clock-in akan muncul di sini secara realtime.</p>
        </div>
      {:else}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
          {#each filteredAttendances as att}
            <div class="bg-white border border-[#d9d9dd] rounded-2xl overflow-hidden text-left group">
              <div class="aspect-4/3 bg-[#eeece7] relative overflow-hidden">
                {#if att.photo_in_url || att.avatar_url}
                  <img src={att.photo_in_url || att.avatar_url} alt={att.user_name} class="w-full h-full object-cover group-hover:scale-105 transition-all" />
                {:else}
                  <div class="w-full h-full flex items-center justify-center text-xs font-mono text-[#93939f]">Tanpa Foto</div>
                {/if}

                <div class="absolute top-2 right-2 flex flex-col gap-1 items-end">
                  <span class={`text-[10px] font-mono font-medium px-2 py-0.5 rounded-md ${
                    att.status === 'ON_TIME'
                      ? 'bg-[#edfce9] text-[#003c33] border border-[#bbf7d0]'
                      : 'bg-[#ffefef] text-[#e5484d] border border-[#fecaca]'
                  }`}>
                    {att.status === 'ON_TIME' ? 'ON TIME' : 'LATE'}
                  </span>
                </div>
              </div>

              <div class="p-3">
                <div class="font-medium text-xs text-[#17171c] truncate">{att.user_name}</div>
                <div class="text-[11px] text-[#75758a] font-mono mt-0.5">{formatAttendanceTime(att.clock_in_time)}</div>
                <div class="text-[10px] text-[#93939f] truncate mt-1">{att.branch_name}</div>
              </div>
            </div>
          {/each}
        </div>
      {/if}
    </div>
  {/if}

  <!-- SUBTAB 3: ROSTER SHIFT -->
  {#if activeSubTab === 'shift'}
    <div class="space-y-4">
      {#if pendingSwaps.length === 0}
        <div class="bg-white border border-[#d9d9dd] rounded-3xl p-12 text-center text-[#93939f] space-y-2">
          <Calendar class="w-8 h-8 mx-auto text-[#93939f] opacity-40" />
          <p class="text-xs font-medium text-[#17171c]">Tidak ada permohonan tukar shift pending</p>
          <p class="text-[11px] text-[#75758a]">Permohonan tukar shift antar staf yang membutuhkan persetujuan akan tampil di sini.</p>
        </div>
      {:else}
        <div class="space-y-3">
          {#each pendingSwaps as swap}
            <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <ArrowRightLeft class="w-4 h-4 text-[#17171c]" />
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
  {/if}

  <!-- SUBTAB 4: KASBON -->
  {#if activeSubTab === 'kasbon'}
    <div class="space-y-4">
      {#if pendingKasbons.length === 0}
        <div class="bg-white border border-[#d9d9dd] rounded-3xl p-12 text-center text-[#93939f] space-y-2">
          <Wallet class="w-8 h-8 mx-auto text-[#93939f] opacity-40" />
          <p class="text-xs font-medium text-[#17171c]">Tidak ada permohonan kasbon pending</p>
          <p class="text-[11px] text-[#75758a]">Permohonan pinjaman kasbon dari staf akan muncul di sini.</p>
        </div>
      {:else}
        <div class="space-y-3">
          {#each pendingKasbons as kasbon}
            <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-sm text-[#17171c] font-mono">{formatRp(kasbon.amount)}</span>
                  <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-[#fff8e6] text-[#b45309] border border-[#fef3c7]">PENDING</span>
                </div>
                <div class="text-xs text-[#616161]">
                  Pengajuan oleh <strong>{kasbon.user_name || 'Staf'}</strong> &bull; {kasbon.request_date}
                </div>
              </div>

              <div class="flex items-center gap-2">
                <button
                  type="button"
                  onclick={() => onApproveKasbon(kasbon.id)}
                  class="px-4 py-2 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-full flex items-center gap-1.5 cursor-pointer shadow-xs"
                >
                  <Check class="w-3.5 h-3.5" />
                  <span>Setujui</span>
                </button>
                <button
                  type="button"
                  onclick={() => onRejectKasbon(kasbon.id)}
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
  {/if}
</div>

<!-- Modal: Undang Karyawan Baru (Kirim Email Undangan) -->
{#if isAddMemberModalOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-3xl w-full max-w-md p-6 space-y-4 shadow-xl animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#e5e5e5] pb-3">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-[#edfce9] text-[#003c33] flex items-center justify-center">
            <Mail class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-[#17171c]">Undang Karyawan Baru</h3>
            <p class="text-[11px] text-[#75758a]">Undangan resmi akan dikirim ke email calon staf</p>
          </div>
        </div>
        <button type="button" onclick={() => (isAddMemberModalOpen = false)} class="text-[#75758a] hover:text-[#17171c] cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      {#if memberFormError}
        <div class="p-3 bg-[#fee2e2] border border-[#fecaca] text-[#991b1b] text-xs rounded-xl">
          {memberFormError}
        </div>
      {/if}

      {#if memberFormSuccess}
        <div class="p-3 bg-[#edfce9] border border-[#bbf7d0] text-[#003c33] text-xs rounded-xl">
          {memberFormSuccess}
        </div>
      {/if}

      <div class="space-y-3 text-xs">
        <div class="space-y-1">
          <label for="new-member-email" class="font-medium text-[#17171c]">Alamat Email Calon Karyawan</label>
          <input
            id="new-member-email"
            type="email"
            bind:value={newMemberForm.email}
            placeholder="nama.barista@gmail.com"
            class="w-full px-3 py-2 bg-white border border-[#d9d9dd] rounded-lg text-[#17171c] font-mono focus:border-[#17171c] focus:outline-hidden"
          />
          <span class="text-[10px] text-[#75758a]">Tautan konfirmasi pendaftaran berbatas waktu 7 hari akan dikirim ke inbox ini.</span>
        </div>

        <div class="space-y-1">
          <label for="new-member-job-title" class="font-medium text-[#17171c]">Nama Posisi / Jabatan Operasional</label>
          <input
            id="new-member-job-title"
            type="text"
            bind:value={newMemberForm.job_title}
            placeholder="Contoh: Senior Barista, Head Cook, Kasir Utama"
            class="w-full px-3 py-2 bg-white border border-[#d9d9dd] rounded-lg text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1">
          <label for="new-member-role" class="font-medium text-[#17171c]">Tingkat Hak Akses Sistem (Peran)</label>
          <select
            id="new-member-role"
            bind:value={newMemberForm.role}
            class="w-full px-3 py-2 bg-white border border-[#d9d9dd] rounded-lg text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
          >
            <option value="STAFF">Staf Biasa (Presensi Selfie GPS &amp; Slip Gaji Mandiri)</option>
            <option value="MANAGER">Manager Cabang (Kelola Roster Shift, Approval Kasbon, &amp; Opname)</option>
            <option value="ADMIN">Admin Operasional (Akses Penuh Seluruh Cabang)</option>
          </select>
        </div>

        <div class="space-y-1">
          <label for="new-member-salary" class="font-medium text-[#17171c]">Gaji Pokok Bulanan (IDR)</label>
          <input
            id="new-member-salary"
            type="number"
            min="0"
            step="50000"
            bind:value={newMemberForm.base_salary}
            class="w-full px-3 py-2 bg-white border border-[#d9d9dd] rounded-lg text-[#17171c] font-mono focus:border-[#17171c] focus:outline-hidden"
          />
        </div>
      </div>

      <div class="pt-2 flex gap-2.5">
        <button
          type="button"
          onclick={() => (isAddMemberModalOpen = false)}
          class="flex-1 py-2.5 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#f4f4f4] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSendInvitationSubmit}
          disabled={isSubmittingMember}
          class="flex-1 py-2.5 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer disabled:opacity-50 flex items-center justify-center gap-1.5 shadow-none"
        >
          {#if isSubmittingMember}
            <span>Mengirim Undangan...</span>
          {:else}
            <Send class="w-3.5 h-3.5" />
            <span>Kirim Undangan</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Modal: Edit Gaji & Posisi Karyawan -->
{#if editingMember}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-3xl w-full max-w-md p-6 space-y-4 shadow-xl animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#e5e5e5] pb-3">
        <h3 class="text-sm font-semibold text-[#17171c]">Atur Gaji &amp; Jabatan Karyawan</h3>
        <button type="button" onclick={() => (editingMember = null)} class="text-[#75758a] hover:text-[#17171c] cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      {#if memberFormError}
        <div class="p-3 bg-[#fee2e2] border border-[#fecaca] text-[#991b1b] text-xs rounded-xl">
          {memberFormError}
        </div>
      {/if}

      <div class="space-y-3 text-xs">
        <div class="p-3 bg-[#fafafa] rounded-xl border border-[#e5e5e5]">
          <div class="font-medium text-[#17171c]">{editingMember.name}</div>
          <div class="text-[11px] text-[#75758a] font-mono">{editingMember.email}</div>
        </div>

        {#if editingMember.role !== 'OWNER'}
          <div class="space-y-1">
            <label for="edit-member-job-title" class="font-medium text-[#17171c]">Nama Jabatan / Posisi</label>
            <input
              id="edit-member-job-title"
              type="text"
              bind:value={editJobTitleInput}
              placeholder="Contoh: Store Manager, Head Barista, Kasir"
              class="w-full px-3 py-2 bg-white border border-[#d9d9dd] rounded-lg text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>

          <div class="space-y-1">
            <label for="edit-member-role" class="font-medium text-[#17171c]">Tingkat Hak Akses Sistem</label>
            <select
              id="edit-member-role"
              bind:value={editRoleInput}
              class="w-full px-3 py-2 bg-white border border-[#d9d9dd] rounded-lg text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
            >
              <option value="STAFF">Staf Biasa (Presensi Selfie GPS &amp; Slip Gaji Mandiri)</option>
              <option value="MANAGER">Manager Cabang (Kelola Roster Shift, Approval Kasbon, &amp; Opname)</option>
              <option value="ADMIN">Admin Operasional (Akses Penuh Seluruh Cabang)</option>
            </select>
          </div>

          <div class="space-y-1">
            <label for="edit-member-salary" class="font-medium text-[#17171c]">Gaji Pokok Bulanan (IDR)</label>
            <input
              id="edit-member-salary"
              type="number"
              min="0"
              step="50000"
              bind:value={editSalaryInput}
              class="w-full px-3 py-2 bg-white border border-[#d9d9dd] rounded-lg font-mono text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>
        {/if}
      </div>

      <div class="pt-2 flex gap-2.5">
        <button
          type="button"
          onclick={() => (editingMember = null)}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-lg text-[#616161] hover:bg-[#f4f4f4] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleEditMemberSubmit}
          disabled={isSubmittingEdit}
          class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-lg cursor-pointer disabled:opacity-50"
        >
          {isSubmittingEdit ? 'Menyimpan...' : 'Simpan Perubahan'}
        </button>
      </div>
    </div>
  </div>
{/if}
