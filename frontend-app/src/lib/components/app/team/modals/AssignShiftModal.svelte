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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl w-full max-w-lg p-6 sm:p-7 space-y-5 shadow-xl animate-in fade-in zoom-in-95">
      <!-- Modal Header -->
      <div class="flex items-center justify-between pb-3 border-b border-[#f2f2f4]">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#f4f4f6] text-[#17171c] flex items-center justify-center border border-[#e5e5ea]">
            <Clock class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">
              {initialAssignmentId ? 'Ubah Penugasan Shift' : 'Penetapan Jadwal Shift'}
            </h3>
            <p class="text-xs text-[#8e8e93]">
              {initialAssignmentId ? 'Ubah jam kerja atau hapus penugasan shift staf ini' : 'Tetapkan pola jam kerja untuk staf terpilih'}
            </p>
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

      {#if matrixAssignError}
        <div class="p-3.5 bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs rounded-xl font-medium">
          {matrixAssignError}
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div class="space-y-1.5">
          <label for="matrix-assign-user" class="font-bold text-[#17171c]">Pilih Karyawan / Staf</label>
          <div class="relative">
            <select
              id="matrix-assign-user"
              bind:value={matrixAssignUserId}
              disabled={Boolean(initialAssignmentId)}
              class="appearance-none w-full border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-xl px-4 pr-10 py-2.5 bg-[#f8f8fa] hover:bg-white text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden disabled:bg-[#f5f5f7] disabled:text-[#8e8e93] cursor-pointer transition-all shadow-2xs"
            >
              {#each staffMembers as member}
                <option value={member.user_id || member.id}>
                  {member.name} ({member.job_title || member.role})
                </option>
              {/each}
            </select>
            <ChevronDown class="w-4 h-4 text-[#8e8e93] absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>

        <div class="space-y-1.5">
          <label for="matrix-assign-date" class="font-bold text-[#17171c]">Tanggal Penugasan</label>
          <input
            id="matrix-assign-date"
            type="date"
            bind:value={matrixAssignDate}
            disabled={Boolean(initialAssignmentId)}
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-xl text-xs text-[#17171c] font-mono focus:border-[#17171c] focus:outline-hidden disabled:bg-[#f5f5f7] disabled:text-[#8e8e93] transition-all shadow-2xs"
          />
        </div>

        <div class="space-y-1.5">
          <label for="matrix-assign-template" class="font-bold text-[#17171c]">Template Pola Shift</label>
          <div class="relative">
            <select
              id="matrix-assign-template"
              bind:value={matrixAssignTemplateId}
              class="appearance-none w-full border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-xl px-4 pr-10 py-2.5 bg-[#f8f8fa] hover:bg-white text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
            >
              {#if shiftTemplates.length === 0}
                <option value="" disabled>Belum ada template shift</option>
              {:else}
                {#each shiftTemplates as tpl}
                  <option value={tpl.id}>
                    {tpl.name} ({tpl.expected_clock_in.substring(0, 5)} - {tpl.expected_clock_out.substring(0, 5)} WIB)
                  </option>
                {/each}
              {/if}
            </select>
            <ChevronDown class="w-4 h-4 text-[#8e8e93] absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>
      </div>

      <div class="pt-2 flex items-center gap-3">
        {#if initialAssignmentId && onDelete}
          <button
            type="button"
            onclick={handleDeleteShift}
            disabled={isDeletingShift || isSubmittingMatrixAssign}
            class="px-4 py-3 text-xs font-semibold text-[#e5484d] hover:bg-[#fef2f2] border border-[#fecaca] rounded-full cursor-pointer transition-all flex items-center justify-center gap-1.5 shrink-0"
            title="Hapus Penugasan Shift"
          >
            <Trash2 class="w-4 h-4" />
            <span>{isDeletingShift ? 'Menghapus...' : 'Hapus Shift (Off)'}</span>
          </button>
        {/if}

        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-3 text-xs font-semibold border border-[#e5e5ea] hover:bg-[#f4f4f6] rounded-full text-[#686873] cursor-pointer transition-all text-center"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleConfirmMatrixAssign}
          disabled={isSubmittingMatrixAssign || isDeletingShift || !matrixAssignTemplateId || !matrixAssignUserId || !matrixAssignDate}
          class="flex-1 py-3 text-xs font-semibold bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2 shadow-xs transition-all text-center"
        >
          {#if isSubmittingMatrixAssign}
            <span>Menyimpan...</span>
          {:else}
            <Check class="w-4 h-4" />
            <span>{initialAssignmentId ? 'Simpan Perubahan' : 'Simpan Penugasan'}</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
