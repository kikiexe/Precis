<script lang="ts">
  import { onMount } from 'svelte';
  import { CheckCircle2, AlertCircle, ArrowRight, Shield, Building2 } from 'lucide-svelte';
  import { invitationService } from '../../services/invitation-service';
  import { apiClient, ApiError } from '../../services/api-client';
  import type { PublicInvitationDetails } from '../../types/app';

  interface Props {
    token: string;
    onAccepted: () => void;
    onRejected: () => void;
  }

  let { token, onAccepted, onRejected }: Props = $props();

  let invitation = $state<PublicInvitationDetails | null>(null);
  let isLoading = $state(true);
  let isSubmitting = $state(false);
  let errorMessage = $state<string | null>(null);
  let successMessage = $state<string | null>(null);

  // Form fields for registration if not logged in
  let newName = $state('');
  let newPassword = $state('');

  onMount(() => {
    loadInvitationDetails();
  });

  async function loadInvitationDetails() {
    isLoading = true;
    errorMessage = null;

    try {
      invitation = await invitationService.getInvitationByToken(token);
    } catch (err: unknown) {
      if (err instanceof ApiError) {
        errorMessage = err.message;
      } else if (err instanceof Error) {
        errorMessage = err.message;
      } else {
        errorMessage = 'Gagal memuat informasi undangan.';
      }
    } finally {
      isLoading = false;
    }
  }

  async function handleAccept(event?: SubmitEvent) {
    if (event) event.preventDefault();
    if (!invitation) return;

    isSubmitting = true;
    errorMessage = null;

    try {
      const result = await invitationService.acceptInvitation(
        token,
        newName.trim() || undefined,
        newPassword || undefined
      );

      if (result.token) {
        apiClient.setToken(result.token);
      }

      successMessage = 'Undangan berhasil diterima! Anda telah bergabung ke dalam workspace.';
      setTimeout(() => {
        onAccepted();
      }, 1200);
    } catch (err: unknown) {
      if (err instanceof ApiError) {
        errorMessage = err.message;
      } else if (err instanceof Error) {
        errorMessage = err.message;
      } else {
        errorMessage = 'Terjadi kesalahan saat menerima undangan.';
      }
    } finally {
      isSubmitting = false;
    }
  }

  async function handleReject() {
    if (!confirm('Apakah Anda yakin ingin menolak undangan bergabung ini?')) {
      return;
    }

    isSubmitting = true;
    errorMessage = null;

    try {
      await invitationService.rejectInvitation(token);
      onRejected();
    } catch (err: unknown) {
      if (err instanceof ApiError) {
        errorMessage = err.message;
      } else {
        errorMessage = 'Gagal menolak undangan.';
      }
    } finally {
      isSubmitting = false;
    }
  }
</script>

<div class="min-h-screen bg-[#fafafc] flex flex-col justify-center items-center p-4 sm:p-6 font-sans select-none">
  <!-- Brand Header -->
  <div class="mb-8 text-center space-y-2">
    <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-2xl bg-white border border-[#e5e5ea] shadow-2xs mb-2">
      <div class="w-3 h-3 rounded-full bg-[#17171c]"></div>
      <span class="text-sm font-bold tracking-tight text-[#17171c]">PRÉCIS</span>
      <span class="text-[10.5px] font-mono text-[#8e8e93] border-l border-[#e5e5ea] pl-2">Enterprise POS &amp; HR</span>
    </div>
    <h1 class="text-xl sm:text-2xl font-bold text-[#17171c] tracking-tight">
      Undangan Bergabung Tim
    </h1>
    <p class="text-xs text-[#8e8e93] max-w-sm mx-auto">
      Konfirmasi akses ke sistem operasional outlet dan absensi tim
    </p>
  </div>

  <div class="w-full max-w-md bg-white border border-[#e5e5ea] rounded-3xl p-6 sm:p-8 shadow-sm">
    {#if isLoading}
      <div class="py-12 text-center space-y-3">
        <div class="w-8 h-8 border-2 border-[#17171c] border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-xs font-mono text-[#8e8e93]">Memeriksa validitas token undangan...</p>
      </div>
    {:else if errorMessage && !invitation}
      <div class="space-y-4 text-center py-4">
        <div class="w-12 h-12 bg-[#fef2f2] text-[#dc2626] rounded-2xl flex items-center justify-center mx-auto border border-[#fecaca]">
          <AlertCircle class="w-6 h-6" />
        </div>
        <div class="space-y-1">
          <h2 class="text-base font-bold text-[#17171c]">Undangan Tidak Valid</h2>
          <p class="text-xs text-[#686873]">{errorMessage}</p>
        </div>
        <button
          type="button"
          onclick={onRejected}
          class="w-full py-3 bg-[#17171c] hover:bg-black text-white font-semibold text-xs rounded-full transition-all cursor-pointer shadow-xs"
        >
          Kembali ke Halaman Login
        </button>
      </div>
    {:else if successMessage}
      <div class="space-y-4 text-center py-4">
        <div class="w-12 h-12 bg-[#ecfdf5] text-[#059669] rounded-2xl flex items-center justify-center mx-auto border border-[#a7f3d0]">
          <CheckCircle2 class="w-6 h-6" />
        </div>
        <div class="space-y-1">
          <h2 class="text-base font-bold text-[#17171c]">Selamat Bergabung!</h2>
          <p class="text-xs text-[#065f46]">{successMessage}</p>
        </div>
      </div>
    {:else if invitation}
      <div class="space-y-5">
        <!-- Info Workspace & Role Card -->
        <div class="p-4 bg-[#f8f8fa] border border-[#e5e5ea] rounded-2xl space-y-3 shadow-2xs">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#17171c] text-white flex items-center justify-center font-bold text-sm">
              <Building2 class="w-5 h-5" />
            </div>
            <div>
              <span class="text-[10px] font-mono uppercase text-[#8e8e93] font-semibold">Workspace Tujuan</span>
              <h3 class="text-sm font-bold text-[#17171c]">{invitation.workspace_name}</h3>
            </div>
          </div>

          <div class="pt-2 border-t border-[#ececee] grid grid-cols-2 gap-2 text-xs">
            <div>
              <span class="text-[10.5px] text-[#8e8e93]">Peran Akses:</span>
              <div class="font-bold text-[#17171c]">{invitation.role}</div>
            </div>
            <div>
              <span class="text-[10.5px] text-[#8e8e93]">Penempatan:</span>
              <div class="font-bold text-[#17171c]">{invitation.branch_name || 'Seluruh Outlet'}</div>
            </div>
          </div>
        </div>

        {#if errorMessage}
          <div class="p-3.5 bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs font-semibold rounded-2xl flex items-start gap-2">
            <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
            <span>{errorMessage}</span>
          </div>
        {/if}

        {#if invitation.user_exists}
          <!-- User Sudah Punya Akun -->
          <div class="space-y-4 text-xs">
            <div class="p-3.5 bg-[#eff6ff] rounded-2xl border border-[#bfdbfe] text-xs text-[#1e40af] leading-relaxed">
              Email Anda <strong>{invitation.email}</strong> telah terdaftar. Tekan tombol di bawah untuk langsung menerima peran.
            </div>

            <div class="flex items-center gap-3 pt-2">
              <button
                type="button"
                onclick={handleReject}
                disabled={isSubmitting}
                class="flex-1 py-3 text-xs font-semibold border border-[#e5e5ea] hover:bg-[#f4f4f6] text-[#686873] rounded-full cursor-pointer transition-all disabled:opacity-50"
              >
                Tolak
              </button>
              <button
                type="button"
                onclick={() => handleAccept()}
                disabled={isSubmitting}
                class="flex-1 py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full flex items-center justify-center gap-2 cursor-pointer transition-all disabled:opacity-50 shadow-xs"
              >
                {#if isSubmitting}
                  <span>Memproses...</span>
                {:else}
                  <span>Terima &amp; Gabung</span>
                  <ArrowRight class="w-4 h-4" />
                {/if}
              </button>
            </div>
          </div>
        {:else}
          <!-- User Belum Punya Akun, Form Registrasi Sekaligus -->
          <form onsubmit={handleAccept} class="space-y-4 text-xs">
            <div class="p-3 bg-[#f8f8fa] rounded-2xl border border-[#ececee] text-[11px] text-[#686873]">
              Lengkapi nama dan buat kata sandi baru untuk akun Anda ({invitation.email}):
            </div>

            <div class="space-y-1.5">
              <label for="inv-name" class="block font-bold text-[#17171c]">Nama Lengkap</label>
              <input
                id="inv-name"
                type="text"
                bind:value={newName}
                placeholder="Contoh: Budi Santoso"
                required
                class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
              />
            </div>

            <div class="space-y-1.5">
              <label for="inv-password" class="block font-bold text-[#17171c]">Kata Sandi Baru</label>
              <input
                id="inv-password"
                type="password"
                bind:value={newPassword}
                placeholder="Minimal 6 karakter"
                required
                minlength="6"
                class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
              />
            </div>

            <div class="flex items-center gap-3 pt-2">
              <button
                type="button"
                onclick={handleReject}
                disabled={isSubmitting}
                class="flex-1 py-3 text-xs font-semibold border border-[#e5e5ea] hover:bg-[#f4f4f6] text-[#686873] rounded-full cursor-pointer transition-all disabled:opacity-50"
              >
                Tolak
              </button>
              <button
                type="submit"
                disabled={isSubmitting || !newName.trim() || !newPassword}
                class="flex-1 py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full flex items-center justify-center gap-2 cursor-pointer transition-all disabled:opacity-50 shadow-xs"
              >
                {#if isSubmitting}
                  <span>Membuat Akun...</span>
                {:else}
                  <span>Buat Akun &amp; Gabung</span>
                  <ArrowRight class="w-4 h-4" />
                {/if}
              </button>
            </div>
          </form>
        {/if}
      </div>
    {/if}
  </div>

  <div class="mt-8 flex items-center gap-2 text-xs text-[#8e8e93] font-mono">
    <Shield class="w-4 h-4 text-[#8e8e93]" />
    <span>PRÉCIS Secure Team Invitation</span>
  </div>
</div>
