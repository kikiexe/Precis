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

<div class="space-y-6 max-w-6xl mx-auto p-4 sm:p-6 md:p-8 pb-24 lg:pb-8 font-sans">
  <!-- header atas -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h2 class="text-xl font-medium text-[#212121] tracking-tight">Jadwal Shift &amp; Manajemen Pertukaran</h2>
      <p class="text-xs text-[#616161] font-normal mt-0.5">Kalender roster penugasan kerja mingguan di {currentUser.branch_name}</p>
    </div>

    <div class="flex items-center gap-2.5 self-start sm:self-auto">
      {#if currentUser.role === 'OWNER' || currentUser.role === 'ADMIN'}
        <button
          type="button"
          onclick={() => {
            assignErrorMessage = null;
            isAssignModalOpen = true;
          }}
          class="bg-[#17171c] hover:bg-[#000000] text-white px-4 py-2.5 text-xs font-medium rounded-full flex items-center gap-1.5 cursor-pointer transition-all shadow-none"
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
        class="bg-[#eeece7]/50 hover:bg-[#eeece7] text-[#212121] border border-[#d9d9dd] px-4 py-2.5 text-xs font-medium rounded-full flex items-center gap-1.5 cursor-pointer transition-all"
      >
        <RefreshCw class="w-3.5 h-3.5 text-[#1863dc]" />
        <span>Ajukan Tukar Shift</span>
      </button>
    </div>
  </div>

  <!-- kalender roster shift -->
  {#if rosterShifts.length === 0}
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] p-12 text-center space-y-2.5 shadow-none">
      <Calendar class="w-8 h-8 text-[#93939f] mx-auto opacity-50" />
      <h3 class="text-sm font-medium text-[#212121]">Belum Ada Jadwal Shift Roster</h3>
      <p class="text-xs text-[#75758a]">Jadwal penugasan shift mingguan belum diterbitkan oleh Store Manager / Owner.</p>
    </div>
  {:else}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      {#each rosterShifts as shift}
        <div class="p-5 border bg-white border-[#d9d9dd] rounded-[20px] shadow-none flex flex-col justify-between h-48 text-xs space-y-3">
          <div>
            <div class="flex items-center justify-between border-b border-[#d9d9dd]/60 pb-2.5">
              <span class="font-medium text-[#212121] font-mono text-[11px]">{shift.date}</span>
              {#if shift.is_swap}
                <span class={`text-[10px] font-mono px-2.5 py-0.5 rounded-full ${
                  shift.swap_status === 'APPROVED'
                    ? 'bg-[#edfce9] text-[#003c33]'
                    : shift.swap_status === 'PENDING'
                    ? 'bg-[#eeece7] text-[#616161]'
                    : 'bg-[#ffad9b]/20 text-[#b30000]'
                }`}>
                  Tukar: {shift.swap_status}
                </span>
              {:else}
                <span class="text-[10px] font-mono bg-[#eeece7] text-[#616161] px-2.5 py-0.5 rounded-full">
                  Reguler
                </span>
              {/if}
            </div>

            <div class="mt-2.5 space-y-1">
              <div class="font-medium text-sm text-[#212121]">{shift.template?.name || 'Shift Kerja'}</div>
              <div class="flex items-center gap-1.5 text-xs text-[#75758a] font-mono">
                <Clock class="w-3.5 h-3.5 text-[#93939f]" />
                <span>{shift.template?.expected_clock_in || '07:00'} - {shift.template?.expected_clock_out || '15:00'} WIB</span>
              </div>
            </div>
          </div>

          <div class="pt-2.5 border-t border-[#d9d9dd]/60 flex flex-col gap-1">
            <div class="flex items-center justify-between text-[11px]">
              <span class="text-[#75758a]">Petugas:</span>
              <span class="font-medium text-[#212121] truncate max-w-35">{shift.assigned_user.name}</span>
            </div>
            {#if shift.is_swap && shift.actual_user}
              <div class="flex items-center justify-between text-[11px] text-[#1863dc]">
                <span>Pengganti:</span>
                <span class="font-medium truncate max-w-35">{shift.actual_user.name}</span>
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
  <div class="fixed inset-0 z-50 bg-[#17171c]/40 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] max-w-md w-full p-6 shadow-none space-y-5 animate-in fade-in zoom-in-95 font-sans">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <div class="flex items-center gap-2">
          <RefreshCw class="w-4 h-4 text-[#1863dc]" />
          <h3 class="font-medium text-base text-[#212121]">Permohonan Tukar Shift</h3>
        </div>
        <button
          type="button"
          onclick={() => (isSwapModalOpen = false)}
          class="text-[#93939f] hover:text-[#212121] cursor-pointer text-sm font-mono p-1"
        >
          ✕
        </button>
      </div>

      {#if swapErrorMessage}
        <div class="p-3 bg-[#ffad9b]/15 border border-[#ffad9b] rounded-[12px] text-[#b30000] text-xs flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{swapErrorMessage}</span>
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div>
          <label for="swap-shift-select" class="block font-medium text-[#212121] mb-1.5">Pilih Jadwal Shift Anda</label>
          {#if myShifts.length === 0}
            <div class="p-3 bg-[#eeece7]/40 text-[#75758a] text-xs font-mono rounded-[12px] border border-[#d9d9dd]">
              Anda tidak memiliki jadwal shift aktif pada roster saat ini.
            </div>
          {:else}
            <select
              id="swap-shift-select"
              bind:value={selectedShiftAssignmentId}
              class="w-full border border-[#d9d9dd] rounded-[12px] p-2.5 bg-white text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
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
          <label for="swap-target-user" class="block font-medium text-[#212121] mb-1.5">Pilih Rekan Pengganti (Staf Cabang)</label>
          <select
            id="swap-target-user"
            bind:value={targetUserId}
            class="w-full border border-[#d9d9dd] rounded-[12px] p-2.5 bg-white text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
          >
            <option value="">Pilih Rekan Kerja Pengganti...</option>
            {#each availableColleagues as col}
              <option value={col.id}>{col.name} ({col.role})</option>
            {/each}
          </select>
        </div>
      </div>

      <div class="flex gap-2.5 pt-3 border-t border-[#d9d9dd]">
        <button
          type="button"
          onclick={() => (isSwapModalOpen = false)}
          class="flex-1 py-2.5 border border-[#d9d9dd] hover:bg-[#eeece7]/40 text-[#616161] text-xs font-medium rounded-full cursor-pointer transition-all"
        >
          Batal
        </button>
        <button
          type="button"
          disabled={isSubmittingSwap || !selectedShiftAssignmentId || !targetUserId}
          onclick={handleConfirmSwap}
          class="flex-1 py-2.5 bg-[#17171c] hover:bg-[#000000] text-white text-xs font-medium rounded-full cursor-pointer transition-all disabled:opacity-50"
        >
          {isSubmittingSwap ? 'Mengajukan...' : 'Kirim Permohonan'}
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- modal penetapan shift oleh manager/owner -->
{#if isAssignModalOpen}
  <div class="fixed inset-0 z-50 bg-[#17171c]/40 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] max-w-md w-full p-6 shadow-none space-y-5 animate-in fade-in zoom-in-95 font-sans">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <div class="flex items-center gap-2">
          <Plus class="w-4 h-4 text-[#17171c]" />
          <h3 class="font-medium text-base text-[#212121]">Tetapkan Jadwal Shift</h3>
        </div>
        <button
          type="button"
          onclick={() => (isAssignModalOpen = false)}
          class="text-[#93939f] hover:text-[#212121] cursor-pointer text-sm font-mono p-1"
        >
          ✕
        </button>
      </div>

      {#if assignErrorMessage}
        <div class="p-3 bg-[#ffad9b]/15 border border-[#ffad9b] rounded-[12px] text-[#b30000] text-xs flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{assignErrorMessage}</span>
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div>
          <label for="assign-user-select" class="block font-medium text-[#212121] mb-1.5">Pilih Karyawan / Staf</label>
          <select
            id="assign-user-select"
            bind:value={assignUserId}
            class="w-full border border-[#d9d9dd] rounded-[12px] p-2.5 bg-white text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
          >
            <option value="">Pilih Karyawan...</option>
            {#each allUsers as u}
              <option value={u.id}>{u.name} ({u.role})</option>
            {/each}
          </select>
        </div>

        <div>
          <label for="assign-date-input" class="block font-medium text-[#212121] mb-1.5">Tanggal Shift (YYYY-MM-DD)</label>
          <input
            id="assign-date-input"
            type="date"
            bind:value={assignDate}
            class="w-full border border-[#d9d9dd] rounded-[12px] p-2.5 bg-white text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
          />
        </div>

        <div>
          <label for="assign-template-select" class="block font-medium text-[#212121] mb-1.5">Pilih Template Shift</label>
          <select
            id="assign-template-select"
            bind:value={assignTemplateId}
            class="w-full border border-[#d9d9dd] rounded-[12px] p-2.5 bg-white text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
          >
            <option value="">Pilih Template Shift...</option>
            <option value="template-pagi">Shift Pagi (07:00 - 15:00 WIB)</option>
            <option value="template-sore">Shift Sore (15:00 - 23:00 WIB)</option>
          </select>
        </div>
      </div>

      <div class="flex gap-2.5 pt-3 border-t border-[#d9d9dd]">
        <button
          type="button"
          onclick={() => (isAssignModalOpen = false)}
          class="flex-1 py-2.5 border border-[#d9d9dd] hover:bg-[#eeece7]/40 text-[#616161] text-xs font-medium rounded-full cursor-pointer transition-all"
        >
          Batal
        </button>
        <button
          type="button"
          disabled={isSubmittingAssign || !assignUserId || !assignDate || !assignTemplateId}
          onclick={handleConfirmAssign}
          class="flex-1 py-2.5 bg-[#17171c] hover:bg-[#000000] text-white text-xs font-medium rounded-full cursor-pointer transition-all disabled:opacity-50"
        >
          {isSubmittingAssign ? 'Menyimpan...' : 'Simpan Penugasan'}
        </button>
      </div>
    </div>
  </div>
{/if}
