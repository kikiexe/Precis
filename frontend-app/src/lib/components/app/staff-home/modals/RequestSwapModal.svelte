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

  let { isOpen, myActiveShifts = [], eligibleColleagues = [], onClose, onSubmit }: Props = $props();

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
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="animate-in fade-in zoom-in-95 w-full max-w-lg space-y-5 rounded-3xl border border-[#e5e5ea] bg-white p-6 shadow-xl sm:p-7"
    >
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-3">
          <div
            class="flex size-10 items-center justify-center rounded-2xl border border-[#bfdbfe] bg-[#eff6ff] text-[#2563eb]"
          >
            <ArrowRightLeft class="size-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Permohonan Tukar Shift</h3>
            <p class="text-xs text-[#8e8e93]">Ajukan tukar shift ke rekan kerja satu outlet</p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="cursor-pointer rounded-xl p-2 text-[#8e8e93] transition-all hover:bg-[#f4f4f6] hover:text-[#17171c]"
        >
          <X class="size-5" />
        </button>
      </div>

      {#if swapErrorMessage}
        <div
          class="flex items-start gap-2 rounded-xl border border-[#fecaca] bg-[#fef2f2] p-3.5 text-xs font-medium text-[#991b1b]"
        >
          <AlertCircle class="mt-0.5 size-4 shrink-0" />
          <span>{swapErrorMessage}</span>
        </div>
      {/if}

      {#if swapSuccessMessage}
        <div
          class="flex items-start gap-2 rounded-xl border border-[#a7f3d0] bg-[#ecfdf5] p-3.5 text-xs font-semibold text-[#065f46]"
        >
          <Check class="mt-0.5 size-4 shrink-0" />
          <span>{swapSuccessMessage}</span>
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div class="space-y-1.5">
          <label for="modal-swap-shift-select" class="block font-bold text-[#17171c]"
            >Pilih Jadwal Shift Anda</label
          >
          {#if myActiveShifts.length === 0}
            <div
              class="rounded-2xl border border-[#e5e5ea] bg-[#f8f8fa] p-3.5 font-mono text-xs text-[#8e8e93]"
            >
              Anda tidak memiliki jadwal shift aktif pada roster saat ini.
            </div>
          {:else}
            <div class="relative">
              <select
                id="modal-swap-shift-select"
                bind:value={swapShiftAssignmentId}
                class="w-full cursor-pointer appearance-none rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 pr-10 font-mono text-xs text-[#17171c] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden"
              >
                <option value="">Pilih Shift yang Ingin Ditukar...</option>
                {#each myActiveShifts as s}
                  <option value={s.id}>
                    {s.date} &bull; {s.template?.name || 'Shift'} ({s.template?.expected_clock_in?.substring(
                      0,
                      5
                    )} - {s.template?.expected_clock_out?.substring(0, 5)})
                  </option>
                {/each}
              </select>
              <ChevronDown
                class="pointer-events-none absolute top-1/2 right-3.5 size-4 -translate-y-1/2 text-[#8e8e93]"
              />
            </div>
          {/if}
        </div>

        <div class="space-y-1.5">
          <label for="modal-swap-target-user" class="block font-bold text-[#17171c]"
            >Pilih Rekan Pengganti</label
          >
          <div class="relative">
            <select
              id="modal-swap-target-user"
              bind:value={swapTargetUserId}
              class="w-full cursor-pointer appearance-none rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 pr-10 font-mono text-xs text-[#17171c] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden"
            >
              <option value="">Pilih Rekan Kerja Pengganti...</option>
              {#each eligibleColleagues as col}
                <option value={col.id}>{col.name} ({col.job_title || col.role})</option>
              {/each}
            </select>
            <ChevronDown
              class="pointer-events-none absolute top-1/2 right-3.5 size-4 -translate-y-1/2 text-[#8e8e93]"
            />
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 cursor-pointer rounded-full border border-[#e5e5ea] py-3 text-xs font-semibold text-[#686873] transition-all hover:bg-[#f4f4f6]"
        >
          Batal
        </button>
        <button
          type="button"
          disabled={isSubmittingSwap || !swapShiftAssignmentId || !swapTargetUserId}
          onclick={handleConfirmSwap}
          class="flex-1 cursor-pointer rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
        >
          {isSubmittingSwap ? 'Mengajukan...' : 'Kirim Permohonan'}
        </button>
      </div>
    </div>
  </div>
{/if}
