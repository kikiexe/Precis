<script lang="ts">
  import { Mail, X, ChevronDown, Send } from 'lucide-svelte';
  import { DEFAULT_BASE_SALARY_IDR } from '../../../../constants/defaults';
  import { invitationService } from '../../../../services/invitation-service';
  import type { WorkspaceRole } from '../../../../types/app';

  interface Props {
    isOpen: boolean;
    roles?: WorkspaceRole[];
    initialBranchId?: string | null;
    onClose: () => void;
    onSuccess: () => void;
  }

  let { isOpen, roles = [], initialBranchId = null, onClose, onSuccess }: Props = $props();

  let isSubmittingMember = $state(false);
  let memberFormError = $state<string | null>(null);
  let memberFormSuccess = $state<string | null>(null);

  let form = $state({
    email: '',
    job_title: 'Barista',
    role_id: null as string | null,
    role: 'STAFF',
    base_salary: DEFAULT_BASE_SALARY_IDR,
    branch_id: null as string | null,
  });

  $effect(() => {
    if (isOpen) {
      const defaultRole =
        roles.find(
          (r) =>
            r.name.toLowerCase().includes('barista') || r.name.toLowerCase().includes('karyawan')
        ) || roles[0];
      form = {
        email: '',
        job_title: 'Barista',
        role_id: defaultRole ? defaultRole.id : null,
        role: defaultRole ? defaultRole.name : 'STAFF',
        base_salary: DEFAULT_BASE_SALARY_IDR,
        branch_id: initialBranchId,
      };
      memberFormError = null;
      memberFormSuccess = null;
    }
  });

  function handleRoleChange(selectedId: string) {
    const r = roles.find((item) => item.id === selectedId);
    if (r) {
      form.role_id = r.id;
      form.role = r.name;
    } else {
      form.role_id = null;
      form.role = selectedId;
    }
  }

  async function handleSendInvitationSubmit() {
    if (!form.email.trim()) {
      memberFormError = 'Alamat email calon karyawan wajib diisi.';
      return;
    }

    if (!form.job_title.trim()) {
      memberFormError = 'Nama jabatan / posisi karyawan wajib diisi.';
      return;
    }

    isSubmittingMember = true;
    memberFormError = null;
    memberFormSuccess = null;

    try {
      await invitationService.inviteMember({
        email: form.email.trim(),
        job_title: form.job_title.trim(),
        role: form.role,
        role_id: form.role_id,
        base_salary: Number(form.base_salary),
        branch_id: form.branch_id,
      });

      memberFormSuccess = `Email undangan berhasil dikirimkan ke ${form.email.trim()}.`;
      onSuccess();

      setTimeout(() => {
        onClose();
      }, 1200);
    } catch (err: unknown) {
      memberFormError = err instanceof Error ? err.message : 'Gagal mengirimkan email undangan.';
    } finally {
      isSubmittingMember = false;
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
            class="flex size-10 items-center justify-center rounded-2xl border border-[#e5e5ea] bg-[#f4f4f6] text-[#17171c]"
          >
            <Mail class="size-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Undang Karyawan Baru</h3>
            <p class="text-xs text-[#8e8e93]">Tautan konfirmasi akan dikirim ke email calon staf</p>
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

      {#if memberFormError}
        <div
          class="rounded-xl border border-[#fecaca] bg-[#fef2f2] p-3.5 text-xs font-medium text-[#991b1b]"
        >
          {memberFormError}
        </div>
      {/if}

      {#if memberFormSuccess}
        <div
          class="rounded-xl border border-[#a7f3d0] bg-[#ecfdf5] p-3.5 text-xs font-medium text-[#065f46]"
        >
          {memberFormSuccess}
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div class="space-y-1.5">
          <label for="new-member-email" class="font-bold text-[#17171c]"
            >Alamat Email Calon Staf</label
          >
          <input
            id="new-member-email"
            type="email"
            bind:value={form.email}
            placeholder="nama.barista@gmail.com"
            class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 font-mono text-xs text-[#17171c] placeholder-[#8e8e93] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1.5">
          <label for="new-member-job-title" class="font-bold text-[#17171c]"
            >Jabatan / Posisi Operasional</label
          >
          <input
            id="new-member-job-title"
            type="text"
            bind:value={form.job_title}
            placeholder="Contoh: Senior Barista, Head Cook, Kasir Utama"
            class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-xs text-[#17171c] placeholder-[#8e8e93] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <!-- Role Selector -->
        <div class="space-y-1.5">
          <label
            for="new-member-role"
            class="flex items-center justify-between font-bold text-[#17171c]"
          >
            <span>Role (Hak Akses)</span>
            {#if roles.length > 0}
              <span class="text-[11px] font-normal text-[#8e8e93]">{roles.length} pilihan role</span
              >
            {/if}
          </label>
          <div class="relative">
            <select
              id="new-member-role"
              value={form.role_id || form.role}
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
              class="pointer-events-none absolute top-1/2 right-3.5 size-4 -translate-y-1/2 text-[#8e8e93]"
            />
          </div>
        </div>

        <div class="space-y-1.5">
          <label for="new-member-salary" class="font-bold text-[#17171c]"
            >Gaji Pokok Bulanan (IDR)</label
          >
          <input
            id="new-member-salary"
            type="number"
            min="0"
            step="50000"
            bind:value={form.base_salary}
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
          onclick={handleSendInvitationSubmit}
          disabled={isSubmittingMember}
          class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
        >
          {#if isSubmittingMember}
            <span>Mengirim Undangan...</span>
          {:else}
            <Send class="size-4" />
            <span>Kirim Undangan</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
