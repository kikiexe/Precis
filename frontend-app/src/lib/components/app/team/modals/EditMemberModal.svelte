<script lang="ts">
  import { X, ChevronDown, UserCheck, Check } from 'lucide-svelte';
  import type { TeamMember, WorkspaceRole } from '../../../../types/app';
  import { teamService } from '../../../../services/team-service';

  interface Props {
    member: TeamMember | null;
    roles?: WorkspaceRole[];
    onClose: () => void;
    onSuccess: () => void;
  }

  let { member, roles = [], onClose, onSuccess }: Props = $props();

  let isSubmittingEdit = $state(false);
  let memberFormError = $state<string | null>(null);

  let editJobTitleInput = $state('Barista');
  let editRoleIdInput = $state<string | null>(null);
  let editRoleInput = $state('STAFF');
  let editSalaryInput = $state(3000000);

  $effect(() => {
    if (member) {
      editJobTitleInput = member.job_title || 'Staf';
      editRoleIdInput = member.role_id || null;
      editRoleInput = member.role;
      editSalaryInput = member.base_salary;
      memberFormError = null;
    }
  });

  function handleRoleChange(selectedId: string) {
    const r = roles.find((item) => item.id === selectedId);
    if (r) {
      editRoleIdInput = r.id;
      editRoleInput = r.name;
    } else {
      editRoleIdInput = null;
      editRoleInput = selectedId;
    }
  }

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
        role_id: member.role === 'OWNER' ? undefined : editRoleIdInput,
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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl w-full max-w-lg p-6 sm:p-7 space-y-5 shadow-xl animate-in fade-in zoom-in-95">
      <!-- Modal Header -->
      <div class="flex items-center justify-between pb-3 border-b border-[#f2f2f4]">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#f4f4f6] text-[#17171c] flex items-center justify-center border border-[#e5e5ea]">
            <UserCheck class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Atur Gaji &amp; Role Staf</h3>
            <p class="text-xs text-[#8e8e93]">Perbarui jabatan, kewenangan, dan kompensasi</p>
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

      {#if memberFormError}
        <div class="p-3.5 bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs rounded-xl font-medium">
          {memberFormError}
        </div>
      {/if}

      <!-- Selected Member Profile Snippet -->
      <div class="p-4 bg-[#f8f8fa] rounded-2xl border border-[#ececee] flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-white text-[#17171c] font-bold text-sm flex items-center justify-center border border-[#e5e5ea]">
          {member.name.charAt(0).toUpperCase()}
        </div>
        <div class="min-w-0">
          <div class="font-bold text-sm text-[#17171c] truncate">{member.name}</div>
          <div class="text-xs text-[#8e8e93] font-mono truncate">{member.email}</div>
        </div>
      </div>

      <div class="space-y-4 text-xs">
        {#if member.role !== 'OWNER'}
          <div class="space-y-1.5">
            <label for="edit-member-job-title" class="font-bold text-[#17171c]">Nama Jabatan / Posisi</label>
            <input
              id="edit-member-job-title"
              type="text"
              bind:value={editJobTitleInput}
              placeholder="Contoh: Barista, Kasir, Supervisor"
              class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-xl text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
            />
          </div>

          <!-- Role Selector -->
          <div class="space-y-1.5">
            <label for="edit-member-role" class="font-bold text-[#17171c] flex items-center justify-between">
              <span>Role (Hak Akses)</span>
              {#if roles.length > 0}
                <span class="text-[11px] text-[#8e8e93] font-normal">{roles.length} pilihan role</span>
              {/if}
            </label>
            <div class="relative">
              <select
                id="edit-member-role"
                value={editRoleIdInput || editRoleInput}
                onchange={(e) => handleRoleChange((e.target as HTMLSelectElement).value)}
                class="appearance-none w-full border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-xl px-4 pr-10 py-2.5 bg-[#f8f8fa] hover:bg-white text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
              >
                {#if roles.length > 0}
                  {#each roles as role}
                    <option value={role.id}>
                      {role.name} ({role.permissions.length} izin)
                    </option>
                  {/each}
                {:else}
                  <option value="STAFF">Karyawan / Staf Biasa</option>
                  <option value="MANAGER">Manajer Operasional</option>
                  <option value="ADMIN">Admin Operasional</option>
                {/if}
              </select>
              <ChevronDown class="w-4 h-4 text-[#8e8e93] absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>
          </div>
        {/if}

        <div class="space-y-1.5">
          <label for="edit-member-salary" class="font-bold text-[#17171c]">
            {member.role === 'OWNER' ? 'Gaji Pokok / Alokasi Pribadi (IDR)' : 'Gaji Pokok Bulanan (IDR)'}
          </label>
          <input
            id="edit-member-salary"
            type="number"
            min="0"
            step="50000"
            bind:value={editSalaryInput}
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-xl text-xs text-[#17171c] font-mono focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
          />
        </div>
      </div>

      <div class="pt-2 flex items-center gap-3">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-3 text-xs font-semibold border border-[#e5e5ea] hover:bg-[#f4f4f6] rounded-full text-[#686873] cursor-pointer transition-all"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleEditMemberSubmit}
          disabled={isSubmittingEdit}
          class="flex-1 py-3 text-xs font-semibold bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2 shadow-xs transition-all"
        >
          {#if isSubmittingEdit}
            <span>Menyimpan...</span>
          {:else}
            <Check class="w-4 h-4" />
            <span>Simpan Perubahan</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
