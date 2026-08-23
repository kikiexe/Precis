<script lang="ts">
  import { RefreshCw, Calendar, Plus, Clock, AlertCircle } from 'lucide-svelte';
  import type { User, ShiftRosterItem } from '../../types/app';

  interface Props {
    currentUser: User;
    allUsers?: User[];
    rosterShifts?: ShiftRosterItem[];
    onSubmitSwap: (shiftAssignmentId: string, targetUserId: string) => Promise<void>;
    onAssignShift?: (shiftTemplateId: string, assignedUserId: string, date: string) => Promise<void>;
  }

  let {
    currentUser,
    allUsers = [],
    rosterShifts = [],
    onSubmitSwap,
    onAssignShift,
  }: Props = $props();

  let isSwapModalOpen = $state(false);
  let isAssignModalOpen = $state(false);

  // form swap
  let selectedShiftAssignmentId = $state('');
  let targetUserId = $state('');
  let isSubmittingSwap = $state(false);
  let swapErrorMessage = $state<string | null>(null);

  // form assign
  let assignUserId = $state('');
  let assignDate = $state('');
  let assignTemplateId = $state('');
  let isSubmittingAssign = $state(false);
  let assignErrorMessage = $state<string | null>(null);

  let myShifts = $derived(
    rosterShifts.filter(
      (s) => s.assigned_user.id === currentUser.id || s.actual_user?.id === currentUser.id
    )
  );

  let availableColleagues = $derived(
    allUsers.filter((u) => u.id !== currentUser.id)
  );

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
    } catch (err: unknown) {
      swapErrorMessage = err instanceof Error ? err.message : 'Gagal mengajukan pertukaran shift.';
    } finally {
      isSubmittingSwap = false;
    }
  }

  async function handleConfirmAssign() {
    if (!onAssignShift) return;
    if (!assignTemplateId || !assignUserId || !assignDate) {
      assignErrorMessage = 'Lengkapi seluruh data penetapan shift.';
      return;
    }

    isSubmittingAssign = true;
    assignErrorMessage = null;

    try {
      await onAssignShift(assignTemplateId, assignUserId, assignDate);
      isAssignModalOpen = false;
      assignUserId = '';
      assignDate = '';
      assignTemplateId = '';
    } catch (err: unknown) {
      assignErrorMessage = err instanceof Error ? err.message : 'Gagal menetapkan shift.';
    } finally {
      isSubmittingAssign = false;
    }
  }
</script>

<div class="space-y-6 max-w-6xl mx-auto p-4 md:p-8 pb-24 lg:pb-8">
  <!-- header atas -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h2 class="text-xl font-bold text-[#161616] font-display">Jadwal Shift &amp; Manajemen Pertukaran</h2>
      <p class="text-xs text-[#525252] font-mono">Kalender roster penugasan kerja mingguan di {currentUser.branch_name}</p>
    </div>

    <div class="flex items-center gap-2 self-start sm:self-auto">
      {#if currentUser.role === 'OWNER' || currentUser.role === 'ADMIN'}
        <button
          type="button"
          onclick={() => {
            assignErrorMessage = null;
            isAssignModalOpen = true;
          }}
          class="bg-[#161616] hover:bg-[#262626] text-white px-3.5 py-2 text-xs font-semibold flex items-center gap-1.5 cursor-pointer shadow-xs transition-colors"
        >
          <Plus class="w-4 h-4" />
          <span>Tetapkan Shift</span>
        </button>
      {/if}

      <button
        type="button"
        onclick={() => {
          swapErrorMessage = null;
          isSwapModalOpen = true;
        }}
        class="bg-[#0f62fe] hover:bg-[#0050e6] text-white px-3.5 py-2 text-xs font-semibold flex items-center gap-1.5 cursor-pointer shadow-xs transition-colors"
      >
        <RefreshCw class="w-4 h-4" />
        <span>Ajukan Tukar Shift</span>
      </button>
    </div>
  </div>

  <!-- kalender roster shift -->
  {#if rosterShifts.length === 0}
    <div class="bg-white border border-[#e0e0e0] p-12 text-center space-y-2 shadow-xs">
      <Calendar class="w-8 h-8 text-[#8c8c8c] mx-auto opacity-40" />
      <h3 class="text-sm font-bold text-[#161616]">Belum Ada Jadwal Shift Roster</h3>
      <p class="text-xs text-[#8c8c8c]">Jadwal penugasan shift mingguan belum diterbitkan oleh Store Manager / Owner.</p>
    </div>
  {:else}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      {#each rosterShifts as shift}
        <div class="p-4 border bg-white border-[#e0e0e0] shadow-xs flex flex-col justify-between h-44 text-xs space-y-2">
          <div>
            <div class="flex items-center justify-between border-b border-[#f4f4f4] pb-2">
              <span class="font-bold text-[#161616] font-mono">{shift.date}</span>
              {#if shift.is_swap}
                <span class={`text-[10px] font-mono px-2 py-0.5 border ${
                  shift.swap_status === 'APPROVED'
                    ? 'bg-[#24a148]/10 border-[#24a148]/30 text-[#24a148]'
                    : shift.swap_status === 'PENDING'
                    ? 'bg-[#f1c21b]/10 border-[#f1c21b]/30 text-[#8a6d00]'
                    : 'bg-[#da1e28]/10 border-[#da1e28]/30 text-[#da1e28]'
                }`}>
                  Tukar: {shift.swap_status}
                </span>
              {:else}
                <span class="text-[10px] font-mono bg-[#f4f4f4] text-[#525252] px-2 py-0.5 border border-[#e0e0e0]">
                  Reguler
                </span>
              {/if}
            </div>

            <div class="mt-2 space-y-1">
              <div class="font-bold text-sm text-[#0f62fe]">{shift.template?.name || 'Shift Kerja'}</div>
              <div class="flex items-center gap-1.5 text-xs text-[#525252] font-mono">
                <Clock class="w-3.5 h-3.5 text-[#8c8c8c]" />
                <span>{shift.template?.expected_clock_in || '07:00'} - {shift.template?.expected_clock_out || '15:00'} WIB</span>
              </div>
            </div>
          </div>

          <div class="pt-2 border-t border-[#f4f4f4] flex flex-col gap-0.5">
            <div class="flex items-center justify-between text-[11px]">
              <span class="text-[#8c8c8c]">Petugas:</span>
              <span class="font-semibold text-[#161616] truncate max-w-35">{shift.assigned_user.name}</span>
            </div>
            {#if shift.is_swap && shift.actual_user}
              <div class="flex items-center justify-between text-[11px] text-[#0f62fe]">
                <span>Pengganti:</span>
                <span class="font-semibold truncate max-w-35">{shift.actual_user.name}</span>
              </div>
            {/if}
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>

<!-- modal pengajuan tukar shift staf -->
{#if isSwapModalOpen}
  <div class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
    <div class="bg-white border border-[#e0e0e0] max-w-md w-full p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3">
        <div class="flex items-center gap-2">
          <RefreshCw class="w-5 h-5 text-[#0f62fe]" />
          <h3 class="font-bold text-base text-[#161616]">Permohonan Tukar Shift</h3>
        </div>
        <button
          type="button"
          onclick={() => (isSwapModalOpen = false)}
          class="text-[#8c8c8c] hover:text-[#161616] cursor-pointer text-sm font-mono"
        >
          ✕
        </button>
      </div>

      {#if swapErrorMessage}
        <div class="p-3 bg-[#da1e28]/10 border border-[#da1e28]/30 text-[#da1e28] text-xs font-mono flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{swapErrorMessage}</span>
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div>
          <label for="swap-shift-select" class="block font-medium text-[#161616] mb-1">Pilih Jadwal Shift Anda</label>
          {#if myShifts.length === 0}
            <div class="p-3 bg-[#f4f4f4] text-[#8c8c8c] text-xs font-mono border border-[#e0e0e0]">
              Anda tidak memiliki jadwal shift aktif pada roster saat ini.
            </div>
          {:else}
            <select
              id="swap-shift-select"
              bind:value={selectedShiftAssignmentId}
              class="w-full border border-[#8c8c8c] p-2.5 bg-white text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
            >
              <option value="">Pilih Shift yang Ingin Ditukar...</option>
              {#each myShifts as s}
                <option value={s.id}>
                  {s.date} • {s.template?.name || 'Shift'} ({s.template?.expected_clock_in} - {s.template?.expected_clock_out})
                </option>
              {/each}
            </select>
          {/if}
        </div>

        <div>
          <label for="swap-target-user" class="block font-medium text-[#161616] mb-1">Pilih Rekan Pengganti (Staf Cabang)</label>
          <select
            id="swap-target-user"
            bind:value={targetUserId}
            class="w-full border border-[#8c8c8c] p-2.5 bg-white text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
          >
            <option value="">Pilih Rekan Kerja Pengganti...</option>
            {#each availableColleagues as col}
              <option value={col.id}>{col.name} ({col.role})</option>
            {/each}
          </select>
        </div>
      </div>

      <div class="flex gap-2 pt-2 border-t border-[#e0e0e0]">
        <button
          type="button"
          onclick={() => (isSwapModalOpen = false)}
          class="flex-1 py-2.5 border border-[#8c8c8c] hover:bg-[#f4f4f4] text-[#161616] text-xs font-semibold cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          disabled={isSubmittingSwap || !selectedShiftAssignmentId || !targetUserId}
          onclick={handleConfirmSwap}
          class="flex-1 py-2.5 bg-[#0f62fe] hover:bg-[#0050e6] text-white text-xs font-semibold cursor-pointer disabled:opacity-50"
        >
          {isSubmittingSwap ? 'Mengajukan...' : 'Kirim Permohonan'}
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- modal penetapan shift oleh manager/owner -->
{#if isAssignModalOpen}
  <div class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
    <div class="bg-white border border-[#e0e0e0] max-w-md w-full p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3">
        <div class="flex items-center gap-2">
          <Plus class="w-5 h-5 text-[#0f62fe]" />
          <h3 class="font-bold text-base text-[#161616]">Tetapkan Jadwal Shift</h3>
        </div>
        <button
          type="button"
          onclick={() => (isAssignModalOpen = false)}
          class="text-[#8c8c8c] hover:text-[#161616] cursor-pointer text-sm font-mono"
        >
          ✕
        </button>
      </div>

      {#if assignErrorMessage}
        <div class="p-3 bg-[#da1e28]/10 border border-[#da1e28]/30 text-[#da1e28] text-xs font-mono flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{assignErrorMessage}</span>
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div>
          <label for="assign-user-select" class="block font-medium text-[#161616] mb-1">Pilih Karyawan / Staf</label>
          <select
            id="assign-user-select"
            bind:value={assignUserId}
            class="w-full border border-[#8c8c8c] p-2.5 bg-white text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
          >
            <option value="">Pilih Karyawan...</option>
            {#each allUsers as u}
              <option value={u.id}>{u.name} ({u.role})</option>
            {/each}
          </select>
        </div>

        <div>
          <label for="assign-date-input" class="block font-medium text-[#161616] mb-1">Tanggal Shift (YYYY-MM-DD)</label>
          <input
            id="assign-date-input"
            type="date"
            bind:value={assignDate}
            class="w-full border border-[#8c8c8c] p-2.5 bg-white text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
          />
        </div>

        <div>
          <label for="assign-template-select" class="block font-medium text-[#161616] mb-1">Pilih Template Shift</label>
          <select
            id="assign-template-select"
            bind:value={assignTemplateId}
            class="w-full border border-[#8c8c8c] p-2.5 bg-white text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
          >
            <option value="">Pilih Template Shift...</option>
            <option value="template-pagi">Shift Pagi (07:00 - 15:00 WIB)</option>
            <option value="template-sore">Shift Sore (15:00 - 23:00 WIB)</option>
          </select>
        </div>
      </div>

      <div class="flex gap-2 pt-2 border-t border-[#e0e0e0]">
        <button
          type="button"
          onclick={() => (isAssignModalOpen = false)}
          class="flex-1 py-2.5 border border-[#8c8c8c] hover:bg-[#f4f4f4] text-[#161616] text-xs font-semibold cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          disabled={isSubmittingAssign || !assignUserId || !assignDate || !assignTemplateId}
          onclick={handleConfirmAssign}
          class="flex-1 py-2.5 bg-[#161616] hover:bg-[#262626] text-white text-xs font-semibold cursor-pointer disabled:opacity-50"
        >
          {isSubmittingAssign ? 'Menyimpan...' : 'Simpan Penugasan'}
        </button>
      </div>
    </div>
  </div>
{/if}
