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
  <div class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4 font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-xl space-y-5 animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#eff6ff] text-[#2563eb] flex items-center justify-center border border-[#bfdbfe]">
            <ArrowRightLeft class="w-5 h-5" />
          </div>
          <div>
            <h3 class="font-bold text-base text-[#17171c]">Permohonan Tukar Shift</h3>
            <p class="text-xs text-[#8e8e93]">Ajukan tukar shift ke rekan kerja satu outlet</p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="p-2 text-[#8e8e93] hover:text-[#17171c] hover:bg-[#f4f4f6] rounded-xl cursor-pointer transition-all"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      {#if swapErrorMessage}
        <div class="p-3.5 bg-[#fef2f2] border border-[#fecaca] rounded-xl text-[#991b1b] text-xs font-medium flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{swapErrorMessage}</span>
        </div>
      {/if}

      {#if swapSuccessMessage}
        <div class="p-3.5 bg-[#ecfdf5] border border-[#a7f3d0] rounded-xl text-[#065f46] text-xs font-semibold flex items-start gap-2">
          <Check class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{swapSuccessMessage}</span>
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div class="space-y-1.5">
          <label for="modal-swap-shift-select" class="block font-bold text-[#17171c]">Pilih Jadwal Shift Anda</label>
          {#if myActiveShifts.length === 0}
            <div class="p-3.5 bg-[#f8f8fa] text-[#8e8e93] text-xs font-mono rounded-2xl border border-[#e5e5ea]">
              Anda tidak memiliki jadwal shift aktif pada roster saat ini.
            </div>
          {:else}
            <div class="relative">
              <select
                id="modal-swap-shift-select"
                bind:value={swapShiftAssignmentId}
                class="appearance-none w-full border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-xl px-4 pr-10 py-2.5 bg-[#f8f8fa] hover:bg-white text-xs text-[#17171c] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
              >
                <option value="">Pilih Shift yang Ingin Ditukar...</option>
                {#each myActiveShifts as s}
                  <option value={s.id}>
                    {s.date} &bull; {s.template?.name || 'Shift'} ({s.template?.expected_clock_in?.substring(0, 5)} - {s.template?.expected_clock_out?.substring(0, 5)})
                  </option>
                {/each}
              </select>
              <ChevronDown class="w-4 h-4 text-[#8e8e93] absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>
          {/if}
        </div>

        <div class="space-y-1.5">
          <label for="modal-swap-target-user" class="block font-bold text-[#17171c]">Pilih Rekan Pengganti</label>
          <div class="relative">
            <select
              id="modal-swap-target-user"
              bind:value={swapTargetUserId}
              class="appearance-none w-full border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-xl px-4 pr-10 py-2.5 bg-[#f8f8fa] hover:bg-white text-xs text-[#17171c] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
            >
              <option value="">Pilih Rekan Kerja Pengganti...</option>
              {#each eligibleColleagues as col}
                <option value={col.id}>{col.name} ({col.job_title || col.role})</option>
              {/each}
            </select>
            <ChevronDown class="w-4 h-4 text-[#8e8e93] absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-3 border border-[#e5e5ea] hover:bg-[#f4f4f6] text-[#686873] text-xs font-semibold rounded-full cursor-pointer transition-all"
        >
          Batal
        </button>
        <button
          type="button"
          disabled={isSubmittingSwap || !swapShiftAssignmentId || !swapTargetUserId}
          onclick={handleConfirmSwap}
          class="flex-1 py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full cursor-pointer transition-all disabled:opacity-50 shadow-xs"
        >
          {isSubmittingSwap ? 'Mengajukan...' : 'Kirim Permohonan'}
        </button>
      </div>
    </div>
  </div>
{/if}
