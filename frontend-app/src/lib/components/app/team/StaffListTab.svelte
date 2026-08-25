<script lang="ts">
  import { onMount } from 'svelte';
  import {
    Users,
    ChevronDown,
    Edit2,
    Trash2,
    Mail,
    Send,
    RotateCw,
    Clock,
  } from 'lucide-svelte';
  import type { TeamMember, WorkspaceInvitationItem } from '../../../types/app';
  import { invitationService } from '../../../services/invitation-service';
  import { teamService } from '../../../services/team-service';
  import { formatRupiah, formatDateTimeIndo } from '../../../utils/formatters';

  interface Props {
    filteredStaffEmployees: TeamMember[];
    availableBranches: string[];
    selectedBranchFilter: string;
    onSelectBranchFilter: (branch: string) => void;
    onOpenAddModal: () => void;
    onOpenEditModal: (member: TeamMember) => void;
    onRefreshMembers?: () => void;
  }

  let {
    filteredStaffEmployees = [],
    availableBranches = [],
    selectedBranchFilter = 'ALL',
    onSelectBranchFilter,
    onOpenAddModal,
    onOpenEditModal,
    onRefreshMembers,
  }: Props = $props();

  let pendingInvitations = $state<WorkspaceInvitationItem[]>([]);
  let isLoadingInvitations = $state(false);

  let totalEmployees = $derived(filteredStaffEmployees.length);
  let totalBasePayroll = $derived(
    filteredStaffEmployees.filter((m) => m.is_active).reduce((sum, m) => sum + m.base_salary, 0)
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
  <!-- Overview Metric & Invite Staff Button -->
  <div class="bg-white border border-[#d9d9dd] rounded-3xl p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex flex-wrap items-center gap-4 sm:gap-6">
      <div>
        <div class="text-[10px] font-mono uppercase text-[#75758a]">Karyawan Aktif</div>
        <div class="text-xl font-bold font-mono text-[#17171c] mt-0.5">{totalEmployees} Orang</div>
      </div>

      <div>
        <div class="text-[10px] font-mono uppercase text-[#75758a]">Total Gaji Pokok Tim</div>
        <div class="text-xl font-bold font-mono text-[#17171c] mt-0.5">{formatRupiah(totalBasePayroll)}</div>
      </div>

      {#if availableBranches.length > 0}
        <div class="relative pt-1 sm:pt-0">
          <select
            value={selectedBranchFilter}
            onchange={(e) => onSelectBranchFilter(e.currentTarget.value)}
            class="appearance-none px-3 pr-7 py-1.5 bg-[#eeece7]/50 hover:bg-[#eeece7] border border-[#d9d9dd] rounded-full text-xs text-[#212121] font-mono focus:outline-hidden cursor-pointer transition-all shadow-2xs"
          >
            <option value="ALL">Semua Cabang</option>
            {#each availableBranches as branch}
              <option value={branch}>{branch}</option>
            {/each}
          </select>
          <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
        </div>
      {/if}
    </div>

    <button
      type="button"
      onclick={onOpenAddModal}
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
        <span>Karyawan Aktif ({filteredStaffEmployees.length})</span>
      </h3>
    </div>

    {#if filteredStaffEmployees.length === 0}
      <div class="bg-white border border-[#d9d9dd] rounded-3xl p-8 text-center text-[#93939f] space-y-2">
        <Users class="w-8 h-8 mx-auto text-[#93939f] opacity-40" />
        <p class="text-xs font-medium text-[#17171c]">Belum ada karyawan aktif untuk cabang ini</p>
        <p class="text-[11px] text-[#75758a]">Kirim undangan email kepada calon staf untuk bergabung ke workspace atau pilih Semua Cabang.</p>
      </div>
    {:else}
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        {#each filteredStaffEmployees as member}
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
                    {member.role === 'OWNER' ? 'Pemilik Bisnis' : formatRupiah(member.base_salary)}
                  </div>
                </div>
              </div>
            </div>

            <!-- Action Controls -->
            <div class="pt-1 flex items-center justify-end gap-2 border-t border-[#f2f2f2]">
              <button
                type="button"
                onclick={() => onOpenEditModal(member)}
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

  <!-- SECTION 2: MENUNGGU KONFIRMASI UNDANGAN -->
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
                    <span>{inv.expires_at ? formatDateTimeIndo(inv.expires_at) : '-'}</span>
                  </div>
                </div>
              </div>
            </div>

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
