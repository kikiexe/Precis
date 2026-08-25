<script lang="ts">
  import { Clock, X, ChevronDown } from 'lucide-svelte';
  import type { ShiftTemplateItem, TeamMember } from '../../../../types/app';

  interface Props {
    isOpen: boolean;
    staffMembers: TeamMember[];
    shiftTemplates: ShiftTemplateItem[];
    initialUserId?: string;
    initialDate?: string;
    initialTemplateId?: string;
    onClose: () => void;
    onAssign: (templateId: string, userId: string, date: string) => Promise<void>;
  }

  let {
    isOpen,
    staffMembers = [],
    shiftTemplates = [],
    initialUserId = '',
    initialDate = '',
    initialTemplateId = '',
    onClose,
    onAssign,
  }: Props = $props();

  let matrixAssignUserId = $state('');
  let matrixAssignDate = $state('');
  let matrixAssignTemplateId = $state('');
  let isSubmittingMatrixAssign = $state(false);
  let matrixAssignError = $state<string | null>(null);

  $effect(() => {
    if (isOpen) {
      matrixAssignUserId = initialUserId || staffMembers[0]?.id || '';
      matrixAssignDate = initialDate || '';
      matrixAssignTemplateId = initialTemplateId || shiftTemplates[0]?.id || '';
      matrixAssignError = null;
    }
  });

  async function handleConfirmMatrixAssign() {
    if (!matrixAssignTemplateId || !matrixAssignUserId || !matrixAssignDate) {
      matrixAssignError = 'Pilih template shift dan karyawan.';
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
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-3xl w-full max-w-md p-6 space-y-4 shadow-xl animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#e5e5e5] pb-3">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-[#eeece7] text-[#17171c] flex items-center justify-center">
            <Clock class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-[#17171c]">Penetapan Jadwal Shift</h3>
            <p class="text-[11px] text-[#75758a]">Tetapkan template pola shift untuk staf terpilih</p>
          </div>
        </div>
        <button type="button" onclick={onClose} class="text-[#75758a] hover:text-[#17171c] cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      {#if matrixAssignError}
        <div class="p-3 bg-[#fee2e2] border border-[#fecaca] text-[#991b1b] text-xs rounded-xl">
          {matrixAssignError}
        </div>
      {/if}

      <div class="space-y-3 text-xs">
        <div class="space-y-1">
          <label for="matrix-assign-user" class="font-medium text-[#17171c]">Karyawan / Staf</label>
          <div class="relative">
            <select
              id="matrix-assign-user"
              bind:value={matrixAssignUserId}
              class="appearance-none w-full border border-[#d9d9dd] rounded-full px-3.5 pr-8 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7]/60 text-xs text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
            >
              {#each staffMembers as member}
                <option value={member.id}>{member.name} ({member.job_title || member.role})</option>
              {/each}
            </select>
            <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>

        <div class="space-y-1">
          <label for="matrix-assign-date" class="font-medium text-[#17171c]">Tanggal Penugasan</label>
          <input
            id="matrix-assign-date"
            type="date"
            bind:value={matrixAssignDate}
            class="w-full px-3.5 py-2 bg-white border border-[#d9d9dd] rounded-full font-mono text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1">
          <label for="matrix-assign-template" class="font-medium text-[#17171c]">Template Pola Shift</label>
          <div class="relative">
            <select
              id="matrix-assign-template"
              bind:value={matrixAssignTemplateId}
              class="appearance-none w-full border border-[#d9d9dd] rounded-full px-3.5 pr-8 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7]/60 text-xs text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
            >
              {#if shiftTemplates.length === 0}
                <option value="" disabled>Belum ada template shift (Buat di Shift Template Builder)</option>
              {:else}
                {#each shiftTemplates as tpl}
                  <option value={tpl.id}>
                    {tpl.name} ({tpl.expected_clock_in} - {tpl.expected_clock_out} WIB)
                  </option>
                {/each}
              {/if}
            </select>
            <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>
      </div>

      <div class="pt-2 flex gap-2.5">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-2.5 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#f4f4f4] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleConfirmMatrixAssign}
          disabled={isSubmittingMatrixAssign || !matrixAssignTemplateId || !matrixAssignUserId || !matrixAssignDate}
          class="flex-1 py-2.5 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer disabled:opacity-50"
        >
          {isSubmittingMatrixAssign ? 'Menyimpan...' : 'Simpan Penugasan'}
        </button>
      </div>
    </div>
  </div>
{/if}
