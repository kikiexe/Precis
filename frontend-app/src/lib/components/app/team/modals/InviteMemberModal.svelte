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
      const defaultRole = roles.find((r) => r.name.toLowerCase().includes('barista') || r.name.toLowerCase().includes('karyawan')) || roles[0];
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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl w-full max-w-lg p-6 sm:p-7 space-y-5 shadow-xl animate-in fade-in zoom-in-95">
      <!-- Modal Header -->
      <div class="flex items-center justify-between pb-3 border-b border-[#f2f2f4]">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#f4f4f6] text-[#17171c] flex items-center justify-center border border-[#e5e5ea]">
            <Mail class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Undang Karyawan Baru</h3>
            <p class="text-xs text-[#8e8e93]">Tautan konfirmasi akan dikirim ke email calon staf</p>
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

      {#if memberFormSuccess}
        <div class="p-3.5 bg-[#ecfdf5] border border-[#a7f3d0] text-[#065f46] text-xs rounded-xl font-medium">
          {memberFormSuccess}
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div class="space-y-1.5">
          <label for="new-member-email" class="font-bold text-[#17171c]">Alamat Email Calon Staf</label>
          <input
            id="new-member-email"
            type="email"
            bind:value={form.email}
            placeholder="nama.barista@gmail.com"
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-xl text-xs text-[#17171c] font-mono placeholder-[#8e8e93] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
          />
        </div>

        <div class="space-y-1.5">
          <label for="new-member-job-title" class="font-bold text-[#17171c]">Jabatan / Posisi Operasional</label>
          <input
            id="new-member-job-title"
            type="text"
            bind:value={form.job_title}
            placeholder="Contoh: Senior Barista, Head Cook, Kasir Utama"
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-xl text-xs text-[#17171c] placeholder-[#8e8e93] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
          />
        </div>

        <!-- Role Selector -->
        <div class="space-y-1.5">
          <label for="new-member-role" class="font-bold text-[#17171c] flex items-center justify-between">
            <span>Role (Hak Akses)</span>
            {#if roles.length > 0}
              <span class="text-[11px] text-[#8e8e93] font-normal">{roles.length} pilihan role</span>
            {/if}
          </label>
          <div class="relative">
            <select
              id="new-member-role"
              value={form.role_id || form.role}
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

        <div class="space-y-1.5">
          <label for="new-member-salary" class="font-bold text-[#17171c]">Gaji Pokok Bulanan (IDR)</label>
          <input
            id="new-member-salary"
            type="number"
            min="0"
            step="50000"
            bind:value={form.base_salary}
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
          onclick={handleSendInvitationSubmit}
          disabled={isSubmittingMember}
          class="flex-1 py-3 text-xs font-semibold bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2 shadow-xs transition-all"
        >
          {#if isSubmittingMember}
            <span>Mengirim Undangan...</span>
          {:else}
            <Send class="w-4 h-4" />
            <span>Kirim Undangan</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
