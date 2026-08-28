<script lang="ts">
  import { Clock, X, ChevronDown, Check, Trash2 } from 'lucide-svelte';
  import type { ShiftTemplateItem, TeamMember } from '../../../../types/app';

  interface Props {
    isOpen: boolean;
    staffMembers: TeamMember[];
    shiftTemplates: ShiftTemplateItem[];
    initialUserId?: string;
    initialDate?: string;
    initialTemplateId?: string;
    initialAssignmentId?: string;
    onClose: () => void;
    onAssign: (templateId: string, userId: string, date: string) => Promise<void>;
    onDelete?: (assignmentId: string) => Promise<void>;
  }

  let {
    isOpen,
    staffMembers = [],
    shiftTemplates = [],
    initialUserId = '',
    initialDate = '',
    initialTemplateId = '',
    initialAssignmentId = '',
    onClose,
    onAssign,
    onDelete,
  }: Props = $props();

  let matrixAssignUserId = $state('');
  let matrixAssignDate = $state('');
  let matrixAssignTemplateId = $state('');
  let isSubmittingMatrixAssign = $state(false);
  let isDeletingShift = $state(false);
  let matrixAssignError = $state<string | null>(null);

  $effect(() => {
    if (isOpen) {
      matrixAssignUserId = initialUserId || staffMembers[0]?.user_id || staffMembers[0]?.id || '';
      matrixAssignDate = initialDate || '';
      matrixAssignTemplateId = initialTemplateId || shiftTemplates[0]?.id || '';
      matrixAssignError = null;
    }
  });

  async function handleConfirmMatrixAssign() {
    if (!matrixAssignTemplateId || !matrixAssignUserId || !matrixAssignDate) {
      matrixAssignError = 'Pilih template shift, karyawan, dan tanggal penugasan.';
      return;
    }

    isSubmittingMatrixAssign = true;
    matrixAssignError = null;

    try {
      await onAssign(matrixAssignTemplateId, matrixAssignUserId, matrixAssignDate);
      onClose();
    } catch (e: unknown) {
      matrixAssignError = e instanceof Error ? e.message : 'Gagal menetapkan shift.';
    } finally {
      isSubmittingMatrixAssign = false;
    }
  }

  async function handleDeleteShift() {
    if (!initialAssignmentId || !onDelete) return;

    isDeletingShift = true;
    matrixAssignError = null;

    try {
      await onDelete(initialAssignmentId);
      onClose();
    } catch (e: unknown) {
      matrixAssignError = e instanceof Error ? e.message : 'Gagal menghapus shift.';
    } finally {
      isDeletingShift = false;
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
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-3">
          <div
            class="flex h-10 w-10 items-center justify-center rounded-2xl border border-[#e5e5ea] bg-[#f4f4f6] text-[#17171c]"
          >
            <Clock class="h-5 w-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">
              {initialAssignmentId ? 'Ubah Penugasan Shift' : 'Penetapan Jadwal Shift'}
            </h3>
            <p class="text-xs text-[#8e8e93]">
              {initialAssignmentId
                ? 'Ubah jam kerja atau hapus penugasan shift staf ini'
                : 'Tetapkan pola jam kerja untuk staf terpilih'}
            </p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="cursor-pointer rounded-xl p-2 text-[#8e8e93] transition-all hover:bg-[#f4f4f6] hover:text-[#17171c]"
        >
          <X class="h-5 w-5" />
        </button>
      </div>

      {#if matrixAssignError}
        <div
          class="rounded-xl border border-[#fecaca] bg-[#fef2f2] p-3.5 text-xs font-medium text-[#991b1b]"
        >
          {matrixAssignError}
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div class="space-y-1.5">
          <label for="matrix-assign-user" class="font-bold text-[#17171c]"
            >Pilih Karyawan / Staf</label
          >
          <div class="relative">
            <select
              id="matrix-assign-user"
              bind:value={matrixAssignUserId}
              disabled={Boolean(initialAssignmentId)}
              class="w-full cursor-pointer appearance-none rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 pr-10 text-xs text-[#17171c] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden disabled:bg-[#f5f5f7] disabled:text-[#8e8e93]"
            >
              {#each staffMembers as member}
                <option value={member.user_id || member.id}>
                  {member.name} ({member.job_title || member.role})
                </option>
              {/each}
            </select>
            <ChevronDown
              class="pointer-events-none absolute top-1/2 right-3.5 h-4 w-4 -translate-y-1/2 text-[#8e8e93]"
            />
          </div>
        </div>

        <div class="space-y-1.5">
          <label for="matrix-assign-date" class="font-bold text-[#17171c]">Tanggal Penugasan</label>
          <input
            id="matrix-assign-date"
            type="date"
            bind:value={matrixAssignDate}
            disabled={Boolean(initialAssignmentId)}
            class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 font-mono text-xs text-[#17171c] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden disabled:bg-[#f5f5f7] disabled:text-[#8e8e93]"
          />
        </div>

        <div class="space-y-1.5">
          <label for="matrix-assign-template" class="font-bold text-[#17171c]"
            >Template Pola Shift</label
          >
          <div class="relative">
            <select
              id="matrix-assign-template"
              bind:value={matrixAssignTemplateId}
              class="w-full cursor-pointer appearance-none rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 pr-10 text-xs text-[#17171c] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden"
            >
              {#if shiftTemplates.length === 0}
                <option value="" disabled>Belum ada template shift</option>
              {:else}
                {#each shiftTemplates as tpl}
                  <option value={tpl.id}>
                    {tpl.name} ({tpl.expected_clock_in.substring(0, 5)} - {tpl.expected_clock_out.substring(
                      0,
                      5
                    )} WIB)
                  </option>
                {/each}
              {/if}
            </select>
            <ChevronDown
              class="pointer-events-none absolute top-1/2 right-3.5 h-4 w-4 -translate-y-1/2 text-[#8e8e93]"
            />
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        {#if initialAssignmentId && onDelete}
          <button
            type="button"
            onclick={handleDeleteShift}
            disabled={isDeletingShift || isSubmittingMatrixAssign}
            class="flex shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-full border border-[#fecaca] px-4 py-3 text-xs font-semibold text-[#e5484d] transition-all hover:bg-[#fef2f2]"
            title="Hapus Penugasan Shift"
          >
            <Trash2 class="h-4 w-4" />
            <span>{isDeletingShift ? 'Menghapus...' : 'Hapus Shift (Off)'}</span>
          </button>
        {/if}

        <button
          type="button"
          onclick={onClose}
          class="flex-1 cursor-pointer rounded-full border border-[#e5e5ea] py-3 text-center text-xs font-semibold text-[#686873] transition-all hover:bg-[#f4f4f6]"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleConfirmMatrixAssign}
          disabled={isSubmittingMatrixAssign ||
            isDeletingShift ||
            !matrixAssignTemplateId ||
            !matrixAssignUserId ||
            !matrixAssignDate}
          class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-3 text-center text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
        >
          {#if isSubmittingMatrixAssign}
            <span>Menyimpan...</span>
          {:else}
            <Check class="h-4 w-4" />
            <span>{initialAssignmentId ? 'Simpan Perubahan' : 'Simpan Penugasan'}</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
