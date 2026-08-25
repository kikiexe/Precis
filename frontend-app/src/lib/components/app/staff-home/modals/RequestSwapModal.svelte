<script lang="ts">
  import { ArrowRightLeft, X, AlertCircle, Check, ChevronDown } from 'lucide-svelte';
  import type { ShiftRosterItem, TeamMember } from '../../../../types/app';

  interface Props {
    isOpen: boolean;
    myActiveShifts: ShiftRosterItem[];
    eligibleColleagues: TeamMember[];
    onClose: () => void;
    onSubmit: (shiftAssignmentId: string, targetUserId: string) => Promise<void>;
  }

  let {
    isOpen,
    myActiveShifts = [],
    eligibleColleagues = [],
    onClose,
    onSubmit,
  }: Props = $props();

  let swapShiftAssignmentId = $state('');
  let swapTargetUserId = $state('');
  let isSubmittingSwap = $state(false);
  let swapErrorMessage = $state<string | null>(null);
  let swapSuccessMessage = $state<string | null>(null);

  $effect(() => {
    if (isOpen) {
      swapShiftAssignmentId = '';
      swapTargetUserId = '';
      swapErrorMessage = null;
      swapSuccessMessage = null;
    }
  });

  async function handleConfirmSwap() {
    if (!swapShiftAssignmentId || !swapTargetUserId) {
      swapErrorMessage = 'Pilih shift Anda dan rekan pengganti.';
      return;
    }
    isSubmittingSwap = true;
    swapErrorMessage = null;
    swapSuccessMessage = null;
    try {
      await onSubmit(swapShiftAssignmentId, swapTargetUserId);
      swapSuccessMessage = 'Permohonan tukar shift berhasil dikirimkan.';
      setTimeout(() => {
        onClose();
        swapShiftAssignmentId = '';
        swapTargetUserId = '';
        swapSuccessMessage = null;
      }, 1200);
    } catch (e: unknown) {
      swapErrorMessage = e instanceof Error ? e.message : 'Gagal mengajukan pertukaran shift.';
    } finally {
      isSubmittingSwap = false;
    }
  }
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 bg-[#17171c]/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] max-w-md w-full p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 font-sans">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <div class="flex items-center gap-2">
          <ArrowRightLeft class="w-4 h-4 text-[#1863dc]" />
          <h3 class="font-medium text-base text-[#212121]">Permohonan Tukar Shift</h3>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="text-[#93939f] hover:text-[#212121] cursor-pointer p-1"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      {#if swapErrorMessage}
        <div class="p-3 bg-[#ffefef] border border-[#e5484d]/30 rounded-xl text-[#e5484d] text-xs flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{swapErrorMessage}</span>
        </div>
      {/if}

      {#if swapSuccessMessage}
        <div class="p-3 bg-[#edfce9] border border-[#00875a]/30 rounded-xl text-[#00875a] text-xs flex items-start gap-2">
          <Check class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{swapSuccessMessage}</span>
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div>
          <label for="modal-swap-shift-select" class="block font-medium text-[#212121] mb-1.5">Pilih Jadwal Shift Anda</label>
          {#if myActiveShifts.length === 0}
            <div class="p-3 bg-[#eeece7]/40 text-[#75758a] text-xs font-mono rounded-xl border border-[#d9d9dd]">
              Anda tidak memiliki jadwal shift aktif pada roster saat ini.
            </div>
          {:else}
            <div class="relative">
              <select
                id="modal-swap-shift-select"
                bind:value={swapShiftAssignmentId}
                class="appearance-none w-full border border-[#d9d9dd] rounded-full px-3.5 pr-8 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7]/60 text-xs text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
              >
                <option value="">Pilih Shift yang Ingin Ditukar...</option>
                {#each myActiveShifts as s}
                  <option value={s.id}>
                    {s.date} &bull; {s.template?.name || 'Shift'} ({s.template?.expected_clock_in} - {s.template?.expected_clock_out})
                  </option>
                {/each}
              </select>
              <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>
          {/if}
        </div>

        <div>
          <label for="modal-swap-colleague-select" class="block font-medium text-[#212121] mb-1.5">Pilih Rekan Pengganti</label>
          <div class="relative">
            <select
              id="modal-swap-colleague-select"
              bind:value={swapTargetUserId}
              class="appearance-none w-full border border-[#d9d9dd] rounded-full px-3.5 pr-8 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7]/60 text-xs text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
            >
              <option value="">Pilih Rekan Kerja Pengganti...</option>
              {#each eligibleColleagues as col}
                <option value={col.id}>{col.name} ({col.role})</option>
              {/each}
            </select>
            <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>
      </div>

      <div class="flex gap-2.5 pt-3 border-t border-[#d9d9dd]">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-2.5 border border-[#d9d9dd] hover:bg-[#eeece7]/40 text-[#616161] text-xs font-medium rounded-full cursor-pointer transition-all"
        >
          Batal
        </button>
        <button
          type="button"
          disabled={isSubmittingSwap || !swapShiftAssignmentId || !swapTargetUserId}
          onclick={handleConfirmSwap}
          class="flex-1 py-2.5 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-full cursor-pointer transition-all disabled:opacity-50"
        >
          {isSubmittingSwap ? 'Mengajukan...' : 'Kirim Permohonan'}
        </button>
      </div>
    </div>
  </div>
{/if}
