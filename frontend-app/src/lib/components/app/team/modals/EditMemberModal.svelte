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
            <UserCheck class="h-5 w-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Atur Gaji &amp; Role Staf</h3>
            <p class="text-xs text-[#8e8e93]">Perbarui jabatan, kewenangan, dan kompensasi</p>
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

      {#if memberFormError}
        <div
          class="rounded-xl border border-[#fecaca] bg-[#fef2f2] p-3.5 text-xs font-medium text-[#991b1b]"
        >
          {memberFormError}
        </div>
      {/if}

      <!-- Selected Member Profile Snippet -->
      <div class="flex items-center gap-3 rounded-2xl border border-[#ececee] bg-[#f8f8fa] p-4">
        <div
          class="flex h-10 w-10 items-center justify-center rounded-xl border border-[#e5e5ea] bg-white text-sm font-bold text-[#17171c]"
        >
          {member.name.charAt(0).toUpperCase()}
        </div>
        <div class="min-w-0">
          <div class="truncate text-sm font-bold text-[#17171c]">{member.name}</div>
          <div class="truncate font-mono text-xs text-[#8e8e93]">{member.email}</div>
        </div>
      </div>

      <div class="space-y-4 text-xs">
        {#if member.role !== 'OWNER'}
          <div class="space-y-1.5">
            <label for="edit-member-job-title" class="font-bold text-[#17171c]"
              >Nama Jabatan / Posisi</label
            >
            <input
              id="edit-member-job-title"
              type="text"
              bind:value={editJobTitleInput}
              placeholder="Contoh: Barista, Kasir, Supervisor"
              class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-xs text-[#17171c] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden"
            />
          </div>

          <!-- Role Selector -->
          <div class="space-y-1.5">
            <label
              for="edit-member-role"
              class="flex items-center justify-between font-bold text-[#17171c]"
            >
              <span>Role (Hak Akses)</span>
              {#if roles.length > 0}
                <span class="text-[11px] font-normal text-[#8e8e93]"
                  >{roles.length} pilihan role</span
                >
              {/if}
            </label>
            <div class="relative">
              <select
                id="edit-member-role"
                value={editRoleIdInput || editRoleInput}
                onchange={(e) => handleRoleChange((e.target as HTMLSelectElement).value)}
                class="w-full cursor-pointer appearance-none rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 pr-10 text-xs text-[#17171c] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden"
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
              <ChevronDown
                class="pointer-events-none absolute top-1/2 right-3.5 h-4 w-4 -translate-y-1/2 text-[#8e8e93]"
              />
            </div>
          </div>
        {/if}

        <div class="space-y-1.5">
          <label for="edit-member-salary" class="font-bold text-[#17171c]">
            {member.role === 'OWNER'
              ? 'Gaji Pokok / Alokasi Pribadi (IDR)'
              : 'Gaji Pokok Bulanan (IDR)'}
          </label>
          <input
            id="edit-member-salary"
            type="number"
            min="0"
            step="50000"
            bind:value={editSalaryInput}
            class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 font-mono text-xs text-[#17171c] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          />
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
          onclick={handleEditMemberSubmit}
          disabled={isSubmittingEdit}
          class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
        >
          {#if isSubmittingEdit}
            <span>Menyimpan...</span>
          {:else}
            <Check class="h-4 w-4" />
            <span>Simpan Perubahan</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
