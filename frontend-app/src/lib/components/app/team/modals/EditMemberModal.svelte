<script lang="ts">
  import { X, ChevronDown } from 'lucide-svelte';
  import type { TeamMember } from '../../../../types/app';
  import { teamService } from '../../../../services/team-service';

  interface Props {
    member: TeamMember | null;
    onClose: () => void;
    onSuccess: () => void;
  }

  let { member, onClose, onSuccess }: Props = $props();

  let isSubmittingEdit = $state(false);
  let memberFormError = $state<string | null>(null);

  let editJobTitleInput = $state('Barista');
  let editRoleInput = $state<'ADMIN' | 'MANAGER' | 'STAFF'>('STAFF');
  let editSalaryInput = $state(3000000);

  $effect(() => {
    if (member) {
      editJobTitleInput = member.job_title || 'Staf';
      editRoleInput = (member.role === 'OWNER' ? 'ADMIN' : member.role) as 'ADMIN' | 'MANAGER' | 'STAFF';
      editSalaryInput = member.base_salary;
      memberFormError = null;
    }
  });

  async function handleEditMemberSubmit() {
    if (!member) return;
    if (!editJobTitleInput.trim()) {
      memberFormError = 'Nama jabatan / posisi karyawan wajib diisi.';
      return;
    }

    isSubmittingEdit = true;
    memberFormError = null;

    try {
      await teamService.updateMember(member.id, {
        job_title: editJobTitleInput.trim(),
        role: member.role === 'OWNER' ? undefined : editRoleInput,
        base_salary: Number(editSalaryInput),
      });

      onSuccess();
      onClose();
    } catch (err: unknown) {
      memberFormError = err instanceof Error ? err.message : 'Gagal memperbarui data karyawan.';
    } finally {
      isSubmittingEdit = false;
    }
  }
</script>

{#if member}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-3xl w-full max-w-md p-6 space-y-4 shadow-xl animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#e5e5e5] pb-3">
        <h3 class="text-sm font-semibold text-[#17171c]">Atur Gaji &amp; Jabatan Karyawan</h3>
        <button type="button" onclick={onClose} class="text-[#75758a] hover:text-[#17171c] cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      {#if memberFormError}
        <div class="p-3 bg-[#fee2e2] border border-[#fecaca] text-[#991b1b] text-xs rounded-xl">
          {memberFormError}
        </div>
      {/if}

      <div class="space-y-3 text-xs">
        <div class="p-3 bg-[#fafafa] rounded-xl border border-[#e5e5e5]">
          <div class="font-medium text-[#17171c]">{member.name}</div>
          <div class="text-[11px] text-[#75758a] font-mono">{member.email}</div>
        </div>

        {#if member.role !== 'OWNER'}
          <div class="space-y-1">
            <label for="edit-member-job-title" class="font-medium text-[#17171c]">Nama Jabatan / Posisi</label>
            <input
              id="edit-member-job-title"
              type="text"
              bind:value={editJobTitleInput}
              placeholder="Contoh: Store Manager, Head Barista, Kasir"
              class="w-full px-3.5 py-2 bg-white border border-[#d9d9dd] rounded-full text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>

          <div class="space-y-1">
            <label for="edit-member-role" class="font-medium text-[#17171c]">Tingkat Hak Akses Sistem</label>
            <div class="relative">
              <select
                id="edit-member-role"
                bind:value={editRoleInput}
                class="appearance-none w-full border border-[#d9d9dd] rounded-full px-3.5 pr-8 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7]/60 text-xs text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
              >
                <option value="STAFF">Staf Biasa (Presensi Selfie GPS &amp; Slip Gaji Mandiri)</option>
                <option value="MANAGER">Manager Cabang (Kelola Roster Shift, Approval Kasbon, &amp; Opname)</option>
                <option value="ADMIN">Admin Operasional (Akses Penuh Seluruh Cabang)</option>
              </select>
              <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>
          </div>

          <div class="space-y-1">
            <label for="edit-member-salary" class="font-medium text-[#17171c]">Gaji Pokok Bulanan (IDR)</label>
            <input
              id="edit-member-salary"
              type="number"
              min="0"
              step="50000"
              bind:value={editSalaryInput}
              class="w-full px-3.5 py-2 bg-white border border-[#d9d9dd] rounded-full font-mono text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>
        {/if}
      </div>

      <div class="pt-2 flex gap-2.5">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#f4f4f4] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleEditMemberSubmit}
          disabled={isSubmittingEdit}
          class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer disabled:opacity-50"
        >
          {isSubmittingEdit ? 'Menyimpan...' : 'Simpan Perubahan'}
        </button>
      </div>
    </div>
  </div>
{/if}
