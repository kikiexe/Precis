<script lang="ts">
  import { Mail, X, ChevronDown, Send } from 'lucide-svelte';
  import { DEFAULT_BASE_SALARY_IDR } from '../../../../constants/defaults';
  import { invitationService } from '../../../../services/invitation-service';

  interface Props {
    isOpen: boolean;
    initialBranchId?: string | null;
    onClose: () => void;
    onSuccess: () => void;
  }

  let { isOpen, initialBranchId = null, onClose, onSuccess }: Props = $props();

  let isSubmittingMember = $state(false);
  let memberFormError = $state<string | null>(null);
  let memberFormSuccess = $state<string | null>(null);

  let form = $state({
    email: '',
    job_title: 'Barista',
    role: 'STAFF' as 'ADMIN' | 'MANAGER' | 'STAFF',
    base_salary: DEFAULT_BASE_SALARY_IDR,
    branch_id: null as string | null,
  });

  $effect(() => {
    if (isOpen) {
      form = {
        email: '',
        job_title: 'Barista',
        role: 'STAFF',
        base_salary: DEFAULT_BASE_SALARY_IDR,
        branch_id: initialBranchId,
      };
      memberFormError = null;
      memberFormSuccess = null;
    }
  });

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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-3xl w-full max-w-md p-6 space-y-4 shadow-xl animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#e5e5e5] pb-3">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-[#edfce9] text-[#003c33] flex items-center justify-center">
            <Mail class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-[#17171c]">Undang Karyawan Baru</h3>
            <p class="text-[11px] text-[#75758a]">Undangan resmi akan dikirim ke email calon staf</p>
          </div>
        </div>
        <button type="button" onclick={onClose} class="text-[#75758a] hover:text-[#17171c] cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      {#if memberFormError}
        <div class="p-3 bg-[#fee2e2] border border-[#fecaca] text-[#991b1b] text-xs rounded-xl">
          {memberFormError}
        </div>
      {/if}

      {#if memberFormSuccess}
        <div class="p-3 bg-[#edfce9] border border-[#bbf7d0] text-[#003c33] text-xs rounded-xl">
          {memberFormSuccess}
        </div>
      {/if}

      <div class="space-y-3 text-xs">
        <div class="space-y-1">
          <label for="new-member-email" class="font-medium text-[#17171c]">Alamat Email Calon Karyawan</label>
          <input
            id="new-member-email"
            type="email"
            bind:value={form.email}
            placeholder="nama.barista@gmail.com"
            class="w-full px-3 py-2 bg-white border border-[#d9d9dd] rounded-lg text-[#17171c] font-mono focus:border-[#17171c] focus:outline-hidden"
          />
          <span class="text-[10px] text-[#75758a]">Tautan konfirmasi pendaftaran berbatas waktu 7 hari akan dikirim ke inbox ini.</span>
        </div>

        <div class="space-y-1">
          <label for="new-member-job-title" class="font-medium text-[#17171c]">Nama Posisi / Jabatan Operasional</label>
          <input
            id="new-member-job-title"
            type="text"
            bind:value={form.job_title}
            placeholder="Contoh: Senior Barista, Head Cook, Kasir Utama"
            class="w-full px-3 py-2 bg-white border border-[#d9d9dd] rounded-lg text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1">
          <label for="new-member-role" class="font-medium text-[#17171c]">Tingkat Hak Akses Sistem (Peran)</label>
          <div class="relative">
            <select
              id="new-member-role"
              bind:value={form.role}
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
          <label for="new-member-salary" class="font-medium text-[#17171c]">Gaji Pokok Bulanan (IDR)</label>
          <input
            id="new-member-salary"
            type="number"
            min="0"
            step="50000"
            bind:value={form.base_salary}
            class="w-full px-3.5 py-2 bg-white border border-[#d9d9dd] rounded-full text-[#17171c] font-mono focus:border-[#17171c] focus:outline-hidden"
          />
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
          onclick={handleSendInvitationSubmit}
          disabled={isSubmittingMember}
          class="flex-1 py-2.5 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer disabled:opacity-50 flex items-center justify-center gap-1.5 shadow-none"
        >
          {#if isSubmittingMember}
            <span>Mengirim Undangan...</span>
          {:else}
            <Send class="w-3.5 h-3.5" />
            <span>Kirim Undangan</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
