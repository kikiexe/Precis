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

<div
  class="flex min-h-screen flex-col items-center justify-center bg-[#fafafc] p-4 font-sans select-none sm:p-6"
>
  <!-- Brand Header -->
  <div class="mb-8 space-y-2 text-center">
    <div
      class="mb-2 inline-flex items-center gap-2.5 rounded-2xl border border-[#e5e5ea] bg-white px-4 py-2 shadow-2xs"
    >
      <div class="h-3 w-3 rounded-full bg-[#17171c]"></div>
      <span class="text-sm font-bold tracking-tight text-[#17171c]">PRÉCIS</span>
      <span class="border-l border-[#e5e5ea] pl-2 font-mono text-[10.5px] text-[#8e8e93]"
        >Enterprise POS &amp; HR</span
      >
    </div>
    <h1 class="text-xl font-bold tracking-tight text-[#17171c] sm:text-2xl">
      Undangan Bergabung Tim
    </h1>
    <p class="mx-auto max-w-sm text-xs text-[#8e8e93]">
      Konfirmasi akses ke sistem operasional outlet dan absensi tim
    </p>
  </div>

  <div class="w-full max-w-md rounded-3xl border border-[#e5e5ea] bg-white p-6 shadow-sm sm:p-8">
    {#if isLoading}
      <div class="space-y-3 py-12 text-center">
        <div
          class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-[#17171c] border-t-transparent"
        ></div>
        <p class="font-mono text-xs text-[#8e8e93]">Memeriksa validitas token undangan...</p>
      </div>
    {:else if errorMessage && !invitation}
      <div class="space-y-4 py-4 text-center">
        <div
          class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl border border-[#fecaca] bg-[#fef2f2] text-[#dc2626]"
        >
          <AlertCircle class="h-6 w-6" />
        </div>
        <div class="space-y-1">
          <h2 class="text-base font-bold text-[#17171c]">Undangan Tidak Valid</h2>
          <p class="text-xs text-[#686873]">{errorMessage}</p>
        </div>
        <button
          type="button"
          onclick={onRejected}
          class="w-full cursor-pointer rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
        >
          Kembali ke Halaman Login
        </button>
      </div>
    {:else if successMessage}
      <div class="space-y-4 py-4 text-center">
        <div
          class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl border border-[#a7f3d0] bg-[#ecfdf5] text-[#059669]"
        >
          <CheckCircle2 class="h-6 w-6" />
        </div>
        <div class="space-y-1">
          <h2 class="text-base font-bold text-[#17171c]">Selamat Bergabung!</h2>
          <p class="text-xs text-[#065f46]">{successMessage}</p>
        </div>
      </div>
    {:else if invitation}
      <div class="space-y-5">
        <!-- Info Workspace & Role Card -->
        <div class="space-y-3 rounded-2xl border border-[#e5e5ea] bg-[#f8f8fa] p-4 shadow-2xs">
          <div class="flex items-center gap-3">
            <div
              class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#17171c] text-sm font-bold text-white"
            >
              <Building2 class="h-5 w-5" />
            </div>
            <div>
              <span class="font-mono text-[10px] font-semibold text-[#8e8e93] uppercase"
                >Workspace Tujuan</span
              >
              <h3 class="text-sm font-bold text-[#17171c]">{invitation.workspace_name}</h3>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2 border-t border-[#ececee] pt-2 text-xs">
            <div>
              <span class="text-[10.5px] text-[#8e8e93]">Peran Akses:</span>
              <div class="font-bold text-[#17171c]">{invitation.role}</div>
            </div>
            <div>
              <span class="text-[10.5px] text-[#8e8e93]">Penempatan:</span>
              <div class="font-bold text-[#17171c]">
                {invitation.branch_name || 'Seluruh Outlet'}
              </div>
            </div>
          </div>
        </div>

        {#if errorMessage}
          <div
            class="flex items-start gap-2 rounded-2xl border border-[#fecaca] bg-[#fef2f2] p-3.5 text-xs font-semibold text-[#991b1b]"
          >
            <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
            <span>{errorMessage}</span>
          </div>
        {/if}

        {#if invitation.user_exists}
          <!-- User Sudah Punya Akun -->
          <div class="space-y-4 text-xs">
            <div
              class="rounded-2xl border border-[#bfdbfe] bg-[#eff6ff] p-3.5 text-xs leading-relaxed text-[#1e40af]"
            >
              Email Anda <strong>{invitation.email}</strong> telah terdaftar. Tekan tombol di bawah untuk
              langsung menerima peran.
            </div>

            <div class="flex items-center gap-3 pt-2">
              <button
                type="button"
                onclick={handleReject}
                disabled={isSubmitting}
                class="flex-1 cursor-pointer rounded-full border border-[#e5e5ea] py-3 text-xs font-semibold text-[#686873] transition-all hover:bg-[#f4f4f6] disabled:opacity-50"
              >
                Tolak
              </button>
              <button
                type="button"
                onclick={() => handleAccept()}
                disabled={isSubmitting}
                class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
              >
                {#if isSubmitting}
                  <span>Memproses...</span>
                {:else}
                  <span>Terima &amp; Gabung</span>
                  <ArrowRight class="h-4 w-4" />
                {/if}
              </button>
            </div>
          </div>
        {:else}
          <!-- User Belum Punya Akun, Form Registrasi Sekaligus -->
          <form onsubmit={handleAccept} class="space-y-4 text-xs">
            <div
              class="rounded-2xl border border-[#ececee] bg-[#f8f8fa] p-3 text-[11px] text-[#686873]"
            >
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
                class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-xs text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
              />
            </div>

            <div class="space-y-1.5">
              <label for="inv-password" class="block font-bold text-[#17171c]"
                >Kata Sandi Baru</label
              >
              <input
                id="inv-password"
                type="password"
                bind:value={newPassword}
                placeholder="Minimal 6 karakter"
                required
                minlength="6"
                class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-xs text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
              />
            </div>

            <div class="flex items-center gap-3 pt-2">
              <button
                type="button"
                onclick={handleReject}
                disabled={isSubmitting}
                class="flex-1 cursor-pointer rounded-full border border-[#e5e5ea] py-3 text-xs font-semibold text-[#686873] transition-all hover:bg-[#f4f4f6] disabled:opacity-50"
              >
                Tolak
              </button>
              <button
                type="submit"
                disabled={isSubmitting || !newName.trim() || !newPassword}
                class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
              >
                {#if isSubmitting}
                  <span>Membuat Akun...</span>
                {:else}
                  <span>Buat Akun &amp; Gabung</span>
                  <ArrowRight class="h-4 w-4" />
                {/if}
              </button>
            </div>
          </form>
        {/if}
      </div>
    {/if}
  </div>

  <div class="mt-8 flex items-center gap-2 font-mono text-xs text-[#8e8e93]">
    <Shield class="h-4 w-4 text-[#8e8e93]" />
    <span>PRÉCIS Secure Team Invitation</span>
  </div>
</div>
