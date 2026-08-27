<script lang="ts">
  import { onMount } from 'svelte';
  import {
    Users,
    Edit2,
    Trash2,
    Mail,
    Send,
    RotateCw,
    Clock,
    Search,
    Building2,
    XCircle,
  } from 'lucide-svelte';
  import type { TeamMember, WorkspaceInvitationItem, User } from '../../../types/app';
  import { invitationService } from '../../../services/invitation-service';
  import { teamService } from '../../../services/team-service';
  import { formatRupiah, formatDateTimeIndo } from '@precis/shared-utils';

  interface Props {
    currentUser?: User;
    filteredStaffEmployees: TeamMember[];
    onOpenAddModal: () => void;
    onOpenEditModal: (member: TeamMember) => void;
    onRefreshMembers?: () => void;
  }

  let {
    currentUser,
    filteredStaffEmployees = [],
    onOpenAddModal,
    onOpenEditModal,
    onRefreshMembers,
  }: Props = $props();

  let canManageMembers = $derived(
    currentUser?.role === 'OWNER' ||
    currentUser?.role === 'ADMIN' ||
    Boolean(currentUser?.permissions?.includes('members.manage'))
  );

  let canViewSalary = $derived(
    currentUser?.role === 'OWNER' ||
    currentUser?.role === 'ADMIN' ||
    Boolean(
      currentUser?.permissions?.includes('payroll.view') ||
      currentUser?.permissions?.includes('payroll.disburse') ||
      currentUser?.permissions?.includes('members.manage')
    )
  );

  let pendingInvitations = $state<WorkspaceInvitationItem[]>([]);
  let isLoadingInvitations = $state(false);
  let searchQuery = $state('');

  let totalEmployees = $derived(filteredStaffEmployees.length);
  let totalBasePayroll = $derived(
    filteredStaffEmployees.filter((m) => m.is_active).reduce((sum, m) => sum + m.base_salary, 0)
  );

  let displayedMembers = $derived(
    filteredStaffEmployees.filter((m) => {
      const q = searchQuery.toLowerCase().trim();
      if (!q) return true;
      return (
        m.name.toLowerCase().includes(q) ||
        m.email.toLowerCase().includes(q) ||
        (m.job_title && m.job_title.toLowerCase().includes(q)) ||
        (m.role_name && m.role_name.toLowerCase().includes(q)) ||
        (m.branch_name && m.branch_name.toLowerCase().includes(q))
      );
    })
  );

  onMount(() => {
    loadPendingInvitations();
  });

  export async function loadPendingInvitations() {
    isLoadingInvitations = true;
    try {
      pendingInvitations = await invitationService.getPendingInvitations();
    } catch {
      pendingInvitations = [];
    } finally {
      isLoadingInvitations = false;
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

<div class="space-y-6 font-sans">
  <!-- Clean KPI Header & Action Bar -->
  <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-2xs">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
      <!-- Metrics Grid -->
      <div class="grid grid-cols-2 sm:flex sm:items-center gap-6 sm:gap-10">
        <div>
          <span class="text-[11px] font-medium uppercase tracking-wider text-[#8e8e93]">Karyawan Aktif</span>
          <div class="text-xl sm:text-2xl font-bold font-mono text-[#17171c] mt-1 flex items-baseline gap-1.5">
            <span>{totalEmployees}</span>
            <span class="text-xs font-normal text-[#8e8e93] font-sans">Orang</span>
          </div>
        </div>

        {#if canViewSalary}
          <div class="sm:border-l sm:border-[#e5e5ea] sm:pl-8">
            <span class="text-[11px] font-medium uppercase tracking-wider text-[#8e8e93]">Total Gaji Pokok</span>
            <div class="text-xl sm:text-2xl font-bold font-mono text-[#17171c] mt-1">
              {formatRupiah(totalBasePayroll)}
            </div>
          </div>
        {/if}

        {#if pendingInvitations.length > 0 && canManageMembers}
          <div class="col-span-2 sm:col-span-1 sm:border-l sm:border-[#e5e5ea] sm:pl-8">
            <span class="text-[11px] font-medium uppercase tracking-wider text-[#d97706]">Undangan Pending</span>
            <div class="text-xl sm:text-2xl font-bold font-mono text-[#d97706] mt-1 flex items-baseline gap-1.5">
              <span>{pendingInvitations.length}</span>
              <span class="text-xs font-normal text-[#8e8e93] font-sans">Menunggu</span>
            </div>
          </div>
        {/if}
      </div>

      <!-- Action Button -->
      {#if canManageMembers}
        <button
          type="button"
          onclick={onOpenAddModal}
          class="px-5 py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full flex items-center justify-center gap-2 cursor-pointer transition-all shadow-xs self-stretch md:self-auto shrink-0"
        >
          <Mail class="w-4 h-4" />
          <span>+ Undang Karyawan</span>
        </button>
      {/if}
    </div>
  </div>

  <!-- Search & Filter Controls -->
  <div class="flex items-center justify-between gap-3">
    <div class="relative flex-1 max-w-md">
      <Search class="w-4 h-4 text-[#8e8e93] absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
      <input
        type="text"
        bind:value={searchQuery}
        placeholder="Cari nama, email, posisi, atau cabang..."
        class="w-full pl-10 pr-4 py-2.5 bg-white border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-full text-xs text-[#17171c] placeholder-[#8e8e93] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
      />
    </div>

    <span class="text-xs text-[#8e8e93] font-mono shrink-0">
      Menampilkan {displayedMembers.length} staf
    </span>
  </div>

  <!-- SECTION 1: KARYAWAN AKTIF GRID -->
  {#if displayedMembers.length === 0}
    <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-12 text-center space-y-3 shadow-2xs">
      <div class="w-12 h-12 rounded-2xl bg-[#f4f4f6] text-[#8e8e93] flex items-center justify-center mx-auto">
        <Users class="w-6 h-6" />
      </div>
      <div>
        <h3 class="text-sm font-bold text-[#17171c]">Tidak Ada Karyawan Ditemukan</h3>
        <p class="text-xs text-[#8e8e93] mt-1 max-w-sm mx-auto">
          {searchQuery
            ? `Tidak ada staf yang cocok dengan "${searchQuery}".`
            : 'Belum ada staf yang terdaftar di cabang ini.'}
        </p>
      </div>
      {#if !searchQuery}
        <button
          type="button"
          onclick={onOpenAddModal}
          class="px-4 py-2 bg-[#17171c] text-white text-xs font-semibold rounded-full inline-flex items-center gap-1.5 cursor-pointer shadow-xs"
        >
          <Mail class="w-3.5 h-3.5" />
          <span>Kirim Undangan Staf</span>
        </button>
      {/if}
    </div>
  {:else}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      {#each displayedMembers as member}
        <div class="bg-white border border-[#e5e5ea] hover:border-[#17171c]/40 rounded-2xl p-5 flex flex-col justify-between space-y-4 shadow-2xs hover:shadow-xs transition-all duration-200">
          <!-- Top Row: Avatar, Name & Role Badge -->
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-2.5">
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-11 h-11 rounded-2xl bg-[#f4f4f6] text-[#17171c] font-bold text-sm flex items-center justify-center shrink-0 border border-[#e5e5ea]">
                  {member.name.charAt(0).toUpperCase()}
                </div>
                <div class="min-w-0">
                  <h4 class="font-bold text-sm text-[#17171c] truncate leading-snug">{member.name}</h4>
                  <p class="text-xs font-medium text-[#686873] truncate">{member.job_title || 'Staf'}</p>
                </div>
              </div>

              <span class={`text-[10px] font-mono font-semibold px-2.5 py-1 rounded-full shrink-0 ${
                member.role === 'OWNER'
                  ? 'bg-[#17171c] text-white'
                  : 'bg-[#f4f4f6] text-[#17171c] border border-[#e5e5ea]'
              }`}>
                {member.role_name || member.role}
              </span>
            </div>

            <!-- Email and Branch metadata -->
            <div class="space-y-1 text-xs text-[#8e8e93]">
              <div class="truncate font-mono text-[11px] text-[#686873]">{member.email}</div>
              <div class="flex items-center gap-1 text-[11.5px] text-[#8e8e93]">
                <Building2 class="w-3.5 h-3.5 shrink-0 text-[#8e8e93]" />
                <span class="truncate">{member.branch_name || 'Outlet Utama'}</span>
              </div>
            </div>

            <!-- Clean Salary Display (No Nested Gray Box) -->
            {#if canViewSalary}
              <div class="pt-3 border-t border-[#f2f2f4] flex items-center justify-between">
                <span class="text-[11px] font-medium text-[#8e8e93]">Gaji Pokok:</span>
                <span class="font-mono font-bold text-xs sm:text-sm text-[#17171c]">
                  {member.role === 'OWNER' ? 'Pemilik Bisnis' : formatRupiah(member.base_salary)}
                </span>
              </div>
            {/if}
          </div>

          <!-- Bottom Action Buttons -->
          {#if canManageMembers}
            <div class="pt-2 flex items-center justify-end gap-2">
              <button
                type="button"
                onclick={() => onOpenEditModal(member)}
                class="px-3 py-1.5 text-xs font-medium bg-[#f8f8fa] hover:bg-[#eeece7] text-[#17171c] border border-[#e5e5ea] rounded-xl flex items-center gap-1.5 cursor-pointer transition-all"
              >
                <Edit2 class="w-3.5 h-3.5 text-[#686873]" />
                <span>Edit</span>
              </button>

              {#if member.role !== 'OWNER'}
                <button
                  type="button"
                  onclick={() => handleDeleteMember(member)}
                  class="p-2 text-[#8e8e93] hover:text-[#e5484d] hover:bg-[#fef2f2] border border-[#e5e5ea] hover:border-[#fecaca] rounded-xl transition-all cursor-pointer shrink-0"
                  title="Hapus Karyawan"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              {/if}
            </div>
          {/if}
        </div>
      {/each}
    </div>
  {/if}

  <!-- SECTION 2: MENUNGGU KONFIRMASI UNDANGAN -->
  {#if pendingInvitations.length > 0}
    <div class="space-y-3 pt-4 border-t border-[#e5e5ea]">
      <div class="flex items-center justify-between px-1">
        <h3 class="text-xs font-bold uppercase tracking-wider text-[#8e8e93] flex items-center gap-2">
          <Clock class="w-3.5 h-3.5 text-[#d97706]" />
          <span>Undangan Menunggu Konfirmasi ({pendingInvitations.length})</span>
        </h3>
        <button
          type="button"
          onclick={loadPendingInvitations}
          class="text-xs text-[#686873] hover:text-[#17171c] flex items-center gap-1 cursor-pointer"
        >
          <RotateCw class={`w-3 h-3 ${isLoadingInvitations ? 'animate-spin' : ''}`} />
          <span>Segarkan</span>
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
        {#each pendingInvitations as inv}
          <div class="bg-white border border-[#e5e5ea] rounded-2xl p-4 flex flex-col justify-between space-y-3 shadow-2xs">
            <div class="space-y-1.5">
              <div class="flex items-center justify-between gap-2">
                <span class="font-bold text-xs text-[#17171c] truncate">{inv.email}</span>
                <span class="px-2 py-0.5 rounded-full text-[9.5px] font-mono font-semibold bg-[#fffbeb] text-[#d97706] border border-[#fef3c7]">
                  PENDING
                </span>
              </div>
              <div class="text-[11px] text-[#686873] flex items-center gap-2">
                <span>Peran: <strong class="text-[#17171c]">{inv.role_name || inv.role}</strong></span>
                <span>&bull;</span>
                <span>Gaji: <strong class="font-mono text-[#17171c]">{formatRupiah(inv.base_salary)}</strong></span>
              </div>
              <div class="text-[10px] text-[#8e8e93] font-mono">
                Kedaluwarsa: {formatDateTimeIndo(inv.expires_at)}
              </div>
            </div>

            <div class="pt-2 border-t border-[#f2f2f4] flex items-center justify-end gap-2">
              <button
                type="button"
                onclick={() => handleResendInvitation(inv.id)}
                class="px-3 py-1.5 text-xs font-medium bg-[#f8f8fa] hover:bg-[#eeece7] text-[#17171c] border border-[#e5e5ea] rounded-xl flex items-center gap-1.5 cursor-pointer transition-all"
              >
                <Send class="w-3 h-3 text-[#1863dc]" />
                <span>Kirim Ulang</span>
              </button>

              <button
                type="button"
                onclick={() => handleCancelInvitation(inv.id)}
                class="p-1.5 text-[#8e8e93] hover:text-[#e5484d] hover:bg-[#fef2f2] border border-[#e5e5ea] rounded-xl transition-all cursor-pointer shrink-0"
                title="Batalkan Undangan"
              >
                <XCircle class="w-4 h-4" />
              </button>
            </div>
          </div>
        {/each}
      </div>
    </div>
  {/if}
</div>
