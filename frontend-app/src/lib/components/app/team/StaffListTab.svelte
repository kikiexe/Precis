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
  <div class="rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs sm:rounded-3xl sm:p-6">
    <div class="flex flex-col justify-between gap-5 md:flex-row md:items-center">
      <!-- Metrics Grid -->
      <div class="grid grid-cols-2 gap-6 sm:flex sm:items-center sm:gap-10">
        <div>
          <span class="text-[11px] font-medium tracking-wider text-[#8e8e93] uppercase"
            >Karyawan Aktif</span
          >
          <div
            class="mt-1 flex items-baseline gap-1.5 font-mono text-xl font-bold text-[#17171c] sm:text-2xl"
          >
            <span>{totalEmployees}</span>
            <span class="font-sans text-xs font-normal text-[#8e8e93]">Orang</span>
          </div>
        </div>

        {#if canViewSalary}
          <div class="sm:border-l sm:border-[#e5e5ea] sm:pl-8">
            <span class="text-[11px] font-medium tracking-wider text-[#8e8e93] uppercase"
              >Total Gaji Pokok</span
            >
            <div class="mt-1 font-mono text-xl font-bold text-[#17171c] sm:text-2xl">
              {formatRupiah(totalBasePayroll)}
            </div>
          </div>
        {/if}

        {#if pendingInvitations.length > 0 && canManageMembers}
          <div class="col-span-2 sm:col-span-1 sm:border-l sm:border-[#e5e5ea] sm:pl-8">
            <span class="text-[11px] font-medium tracking-wider text-[#d97706] uppercase"
              >Undangan Pending</span
            >
            <div
              class="mt-1 flex items-baseline gap-1.5 font-mono text-xl font-bold text-[#d97706] sm:text-2xl"
            >
              <span>{pendingInvitations.length}</span>
              <span class="font-sans text-xs font-normal text-[#8e8e93]">Menunggu</span>
            </div>
          </div>
        {/if}
      </div>

      <!-- Action Button -->
      {#if canManageMembers}
        <button
          type="button"
          onclick={onOpenAddModal}
          class="flex shrink-0 cursor-pointer items-center justify-center gap-2 self-stretch rounded-full bg-[#17171c] px-5 py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black md:self-auto"
        >
          <Mail class="h-4 w-4" />
          <span>+ Undang Karyawan</span>
        </button>
      {/if}
    </div>
  </div>

  <!-- Search & Filter Controls -->
  <div class="flex items-center justify-between gap-3">
    <div class="relative max-w-md flex-1">
      <Search
        class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-[#8e8e93]"
      />
      <input
        type="text"
        bind:value={searchQuery}
        placeholder="Cari nama, email, posisi, atau cabang..."
        class="w-full rounded-full border border-[#e5e5ea] bg-white py-2.5 pr-4 pl-10 text-xs text-[#17171c] placeholder-[#8e8e93] shadow-2xs transition-all hover:border-[#d1d1d6] focus:border-[#17171c] focus:outline-hidden"
      />
    </div>

    <span class="shrink-0 font-mono text-xs text-[#8e8e93]">
      Menampilkan {displayedMembers.length} staf
    </span>
  </div>

  <!-- SECTION 1: KARYAWAN AKTIF GRID -->
  {#if displayedMembers.length === 0}
    <div
      class="space-y-3 rounded-2xl border border-[#e5e5ea] bg-white p-12 text-center shadow-2xs sm:rounded-3xl"
    >
      <div
        class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f4f4f6] text-[#8e8e93]"
      >
        <Users class="h-6 w-6" />
      </div>
      <div>
        <h3 class="text-sm font-bold text-[#17171c]">Tidak Ada Karyawan Ditemukan</h3>
        <p class="mx-auto mt-1 max-w-sm text-xs text-[#8e8e93]">
          {searchQuery
            ? `Tidak ada staf yang cocok dengan "${searchQuery}".`
            : 'Belum ada staf yang terdaftar di cabang ini.'}
        </p>
      </div>
      {#if !searchQuery}
        <button
          type="button"
          onclick={onOpenAddModal}
          class="inline-flex cursor-pointer items-center gap-1.5 rounded-full bg-[#17171c] px-4 py-2 text-xs font-semibold text-white shadow-xs"
        >
          <Mail class="h-3.5 w-3.5" />
          <span>Kirim Undangan Staf</span>
        </button>
      {/if}
    </div>
  {:else}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      {#each displayedMembers as member}
        <div
          class="flex flex-col justify-between space-y-4 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs transition-all duration-200 hover:border-[#17171c]/40 hover:shadow-xs"
        >
          <!-- Top Row: Avatar, Name & Role Badge -->
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-2.5">
              <div class="flex min-w-0 items-center gap-3">
                <div
                  class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-[#e5e5ea] bg-[#f4f4f6] text-sm font-bold text-[#17171c]"
                >
                  {member.name.charAt(0).toUpperCase()}
                </div>
                <div class="min-w-0">
                  <h4 class="truncate text-sm leading-snug font-bold text-[#17171c]">
                    {member.name}
                  </h4>
                  <p class="truncate text-xs font-medium text-[#686873]">
                    {member.job_title || 'Staf'}
                  </p>
                </div>
              </div>

              <span
                class={`shrink-0 rounded-full px-2.5 py-1 font-mono text-[10px] font-semibold ${
                  member.role === 'OWNER'
                    ? 'bg-[#17171c] text-white'
                    : 'border border-[#e5e5ea] bg-[#f4f4f6] text-[#17171c]'
                }`}
              >
                {member.role_name || member.role}
              </span>
            </div>

            <!-- Email and Branch metadata -->
            <div class="space-y-1 text-xs text-[#8e8e93]">
              <div class="truncate font-mono text-[11px] text-[#686873]">{member.email}</div>
              <div class="flex items-center gap-1 text-[11.5px] text-[#8e8e93]">
                <Building2 class="h-3.5 w-3.5 shrink-0 text-[#8e8e93]" />
                <span class="truncate">{member.branch_name || 'Outlet Utama'}</span>
              </div>
            </div>

            <!-- Clean Salary Display (No Nested Gray Box) -->
            {#if canViewSalary}
              <div class="flex items-center justify-between border-t border-[#f2f2f4] pt-3">
                <span class="text-[11px] font-medium text-[#8e8e93]">Gaji Pokok:</span>
                <span class="font-mono text-xs font-bold text-[#17171c] sm:text-sm">
                  {member.role === 'OWNER' ? 'Pemilik Bisnis' : formatRupiah(member.base_salary)}
                </span>
              </div>
            {/if}
          </div>

          <!-- Bottom Action Buttons -->
          {#if canManageMembers}
            <div class="flex items-center justify-end gap-2 pt-2">
              <button
                type="button"
                onclick={() => onOpenEditModal(member)}
                class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-3 py-1.5 text-xs font-medium text-[#17171c] transition-all hover:bg-[#eeece7]"
              >
                <Edit2 class="h-3.5 w-3.5 text-[#686873]" />
                <span>Edit</span>
              </button>

              {#if member.role !== 'OWNER'}
                <button
                  type="button"
                  onclick={() => handleDeleteMember(member)}
                  class="shrink-0 cursor-pointer rounded-xl border border-[#e5e5ea] p-2 text-[#8e8e93] transition-all hover:border-[#fecaca] hover:bg-[#fef2f2] hover:text-[#e5484d]"
                  title="Hapus Karyawan"
                >
                  <Trash2 class="h-3.5 w-3.5" />
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
    <div class="space-y-3 border-t border-[#e5e5ea] pt-4">
      <div class="flex items-center justify-between px-1">
        <h3
          class="flex items-center gap-2 text-xs font-bold tracking-wider text-[#8e8e93] uppercase"
        >
          <Clock class="h-3.5 w-3.5 text-[#d97706]" />
          <span>Undangan Menunggu Konfirmasi ({pendingInvitations.length})</span>
        </h3>
        <button
          type="button"
          onclick={loadPendingInvitations}
          class="flex cursor-pointer items-center gap-1 text-xs text-[#686873] hover:text-[#17171c]"
        >
          <RotateCw class={`h-3 w-3 ${isLoadingInvitations ? 'animate-spin' : ''}`} />
          <span>Segarkan</span>
        </button>
      </div>

      <div class="grid grid-cols-1 gap-3.5 md:grid-cols-2">
        {#each pendingInvitations as inv}
          <div
            class="flex flex-col justify-between space-y-3 rounded-2xl border border-[#e5e5ea] bg-white p-4 shadow-2xs"
          >
            <div class="space-y-1.5">
              <div class="flex items-center justify-between gap-2">
                <span class="truncate text-xs font-bold text-[#17171c]">{inv.email}</span>
                <span
                  class="rounded-full border border-[#fef3c7] bg-[#fffbeb] px-2 py-0.5 font-mono text-[9.5px] font-semibold text-[#d97706]"
                >
                  PENDING
                </span>
              </div>
              <div class="flex items-center gap-2 text-[11px] text-[#686873]">
                <span
                  >Peran: <strong class="text-[#17171c]">{inv.role_name || inv.role}</strong></span
                >
                <span>&bull;</span>
                <span
                  >Gaji: <strong class="font-mono text-[#17171c]"
                    >{formatRupiah(inv.base_salary)}</strong
                  ></span
                >
              </div>
              <div class="font-mono text-[10px] text-[#8e8e93]">
                Kedaluwarsa: {formatDateTimeIndo(inv.expires_at)}
              </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-[#f2f2f4] pt-2">
              <button
                type="button"
                onclick={() => handleResendInvitation(inv.id)}
                class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-3 py-1.5 text-xs font-medium text-[#17171c] transition-all hover:bg-[#eeece7]"
              >
                <Send class="h-3 w-3 text-[#1863dc]" />
                <span>Kirim Ulang</span>
              </button>

              <button
                type="button"
                onclick={() => handleCancelInvitation(inv.id)}
                class="shrink-0 cursor-pointer rounded-xl border border-[#e5e5ea] p-1.5 text-[#8e8e93] transition-all hover:bg-[#fef2f2] hover:text-[#e5484d]"
                title="Batalkan Undangan"
              >
                <XCircle class="h-4 w-4" />
              </button>
            </div>
          </div>
        {/each}
      </div>
    </div>
  {/if}
</div>
