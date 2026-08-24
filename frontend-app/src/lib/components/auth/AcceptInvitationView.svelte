<script lang="ts">
  import { onMount } from 'svelte';
  import { CheckCircle2, AlertCircle, User, Lock, ArrowRight, Shield, Sparkles } from 'lucide-svelte';
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
  let showPassword = $state(false);

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

<div class="min-h-screen bg-[#eeece7]/40 flex flex-col justify-center items-center p-4 sm:p-6 select-none font-sans">
  <!-- Brand Header -->
  <div class="w-full max-w-md mb-6 text-center">
    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-[#17171c] text-white font-bold text-lg mb-3">
      P
    </div>
    <h1 class="text-2xl font-medium text-[#212121] tracking-tight">PRÉCIS WORKSPACE</h1>
    <p class="text-xs text-[#616161] mt-1 font-normal">
      Konfirmasi Undangan Bergabung Tim
    </p>
  </div>

  <!-- Invitation Card -->
  <div class="w-full max-w-md bg-white border border-[#d9d9dd] rounded-3xl p-6 sm:p-8 shadow-none">
    {#if isLoading}
      <div class="py-12 text-center space-y-3">
        <div class="w-7 h-7 border-2 border-[#17171c] border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-xs font-mono text-[#75758a]">Memverifikasi tautan undangan...</p>
      </div>
    {:else if errorMessage && !invitation}
      <div class="text-center py-6">
        <div class="w-12 h-12 bg-[#ffefef] text-[#e5484d] rounded-full flex items-center justify-center mx-auto mb-3">
          <AlertCircle class="w-6 h-6" />
        </div>
        <h2 class="text-sm font-semibold text-[#212121] mb-1">Undangan Tidak Valid</h2>
        <p class="text-xs text-[#616161] mb-6">{errorMessage}</p>
        <button
          type="button"
          onclick={onRejected}
          class="py-2.5 px-6 bg-[#17171c] text-white text-xs font-medium rounded-full cursor-pointer hover:bg-black transition-all"
        >
          Kembali ke Halaman Utama
        </button>
      </div>
    {:else if invitation}
      {#if successMessage}
        <div class="mb-5 p-4 bg-[#edfce9] border border-[#c7f3be] rounded-2xl text-xs text-[#003c33] flex items-center gap-3">
          <CheckCircle2 class="w-5 h-5 shrink-0" />
          <span>{successMessage}</span>
        </div>
      {/if}

      {#if errorMessage}
        <div class="mb-5 p-3.5 bg-[#ffefef] border border-[#ffefef] rounded-2xl text-xs text-[#e5484d] flex items-start gap-2.5">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <div class="mb-6">
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#edfce9] text-[#003c33] rounded-full text-[11px] font-medium border border-[#c7f3be] mb-3">
          <Sparkles class="w-3 h-3" />
          <span>Undangan Tim</span>
        </div>
        <h2 class="text-lg font-semibold text-[#212121] tracking-tight">
          {invitation.workspace_name}
        </h2>
        <p class="text-xs text-[#616161] mt-1">
          Diundang oleh <strong>{invitation.invited_by_name}</strong>
        </p>
      </div>

      <!-- Detail Box -->
      <div class="bg-[#fafafa] border border-[#d9d9dd] rounded-2xl p-4 mb-6 space-y-2.5 text-xs">
        <div class="flex justify-between items-center py-1 border-b border-[#eeece7]">
          <span class="text-[#75758a]">Alamat Email:</span>
          <span class="font-medium text-[#212121] font-mono">{invitation.email}</span>
        </div>
        <div class="flex justify-between items-center py-1 border-b border-[#eeece7]">
          <span class="text-[#75758a]">Posisi / Jabatan:</span>
          <span class="font-semibold text-[#212121]">{invitation.job_title}</span>
        </div>
        <div class="flex justify-between items-center py-1 border-b border-[#eeece7]">
          <span class="text-[#75758a]">Hak Akses (Role):</span>
          <span class="font-semibold text-[#003c33] px-2 py-0.5 bg-[#edfce9] rounded-md">{invitation.role}</span>
        </div>
        <div class="flex justify-between items-center py-1">
          <span class="text-[#75758a]">Penempatan:</span>
          <span class="font-medium text-[#212121]">{invitation.branch_name}</span>
        </div>
      </div>

      <!-- Form Buat Password jika Akun Baru -->
      <form onsubmit={handleAccept} class="space-y-4">
        <div>
          <label for="inv-name" class="block text-xs font-medium text-[#212121] mb-1.5">
            Nama Lengkap Anda
          </label>
          <div class="relative">
            <input
              id="inv-name"
              type="text"
              bind:value={newName}
              placeholder="Nama lengkap Anda"
              disabled={isSubmitting}
              class="w-full pl-9 pr-3.5 py-2.5 text-xs text-[#212121] placeholder-[#93939f] border border-[#d9d9dd] rounded-xl focus:border-[#17171c] focus:outline-hidden bg-white"
            />
            <User class="w-4 h-4 text-[#93939f] absolute left-3 top-1/2 -translate-y-1/2" />
          </div>
        </div>

        <div>
          <label for="inv-password" class="block text-xs font-medium text-[#212121] mb-1.5">
            Kata Sandi Akun
          </label>
          <div class="relative">
            <input
              id="inv-password"
              type={showPassword ? 'text' : 'password'}
              bind:value={newPassword}
              placeholder="Minimal 6 karakter"
              minlength="6"
              disabled={isSubmitting}
              class="w-full pl-9 pr-10 py-2.5 text-xs text-[#212121] placeholder-[#93939f] border border-[#d9d9dd] rounded-xl focus:border-[#17171c] focus:outline-hidden bg-white"
            />
            <Lock class="w-4 h-4 text-[#93939f] absolute left-3 top-1/2 -translate-y-1/2" />
          </div>
          <p class="text-[10px] text-[#75758a] mt-1">Kosongkan jika Anda sudah memiliki kata sandi terdaftar.</p>
        </div>

        <div class="pt-2 space-y-2">
          <button
            type="submit"
            disabled={isSubmitting}
            class="w-full py-3 px-5 bg-[#17171c] hover:bg-black text-white font-medium text-xs rounded-full flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-50 shadow-none"
          >
            {#if isSubmitting}
              <span>Memproses...</span>
            {:else}
              <span>Terima Undangan &amp; Bergabung</span>
              <ArrowRight class="w-4 h-4" />
            {/if}
          </button>

          <button
            type="button"
            onclick={handleReject}
            disabled={isSubmitting}
            class="w-full py-2.5 px-4 bg-transparent hover:bg-[#fafafa] text-[#75758a] hover:text-[#b30000] font-medium text-xs rounded-full transition-all cursor-pointer"
          >
            Tolak Undangan Ini
          </button>
        </div>
      </form>
    {/if}
  </div>

  <!-- Security Footer -->
  <div class="mt-6 flex items-center gap-2 text-[11px] text-[#75758a] font-mono">
    <Shield class="w-3.5 h-3.5" />
    <span>PRÉCIS Workspace Invitation System</span>
  </div>
</div>
