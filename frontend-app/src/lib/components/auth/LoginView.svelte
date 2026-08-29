<script lang="ts">
  import { Lock, Mail, AlertCircle, ArrowRight, Shield, Eye, EyeOff, User } from 'lucide-svelte';
  import { authService } from '../../services/auth-service';
  import { ApiError } from '../../services/api-client';
  import type { LoginResponseData } from '../../types/app';
  import ForgotPasswordModal from './ForgotPasswordModal.svelte';
  import ResetPasswordModal from './ResetPasswordModal.svelte';

  interface Props {
    onLoginSuccess: (data: LoginResponseData) => void;
  }

  let { onLoginSuccess }: Props = $props();

  let mode = $state<'login' | 'register'>('login');

  // Login form state
  let email = $state('');
  let password = $state('');

  // Register form state
  let regName = $state('');
  let regEmail = $state('');
  let regPassword = $state('');
  let registeredSuccessEmail = $state<string | null>(null);
  let isResending = $state(false);

  let showPassword = $state(false);
  let isLoading = $state(false);
  let errorMessage = $state<string | null>(null);
  let successMessage = $state<string | null>(null);

  let isForgotPasswordOpen = $state(false);
  let isResetPasswordOpen = $state(false);
  let resetEmail = $state('');
  let resetToken = $state('');

  $effect(() => {
    if (typeof window !== 'undefined') {
      const urlParams = new URLSearchParams(window.location.search);
      const tokenParam = urlParams.get('token');
      const emailParam = urlParams.get('email');
      const modeParam = urlParams.get('mode');

      if (tokenParam && emailParam) {
        resetToken = tokenParam;
        resetEmail = emailParam;
        isResetPasswordOpen = true;
      }

      if (modeParam === 'register') {
        mode = 'register';
      }
    }
  });

  async function handleLogin(event: SubmitEvent) {
    event.preventDefault();
    if (!email.trim() || !password) {
      errorMessage = 'Email dan kata sandi wajib diisi.';
      return;
    }

    isLoading = true;
    errorMessage = null;
    successMessage = null;

    try {
      const loginData = await authService.login(email.trim(), password);
      onLoginSuccess(loginData);
    } catch (err: unknown) {
      if (err instanceof ApiError) {
        errorMessage = err.message;
      } else if (err instanceof Error) {
        errorMessage = err.message;
      } else {
        errorMessage = 'Terjadi kesalahan saat masuk ke sistem.';
      }
    } finally {
      isLoading = false;
    }
  }

  async function handleRegister(event: SubmitEvent) {
    event.preventDefault();
    if (!regName.trim() || !regEmail.trim() || !regPassword) {
      errorMessage = 'Nama, email, dan kata sandi wajib diisi.';
      return;
    }

    if (regPassword.length < 6) {
      errorMessage = 'Kata sandi minimal berisi 6 karakter.';
      return;
    }

    isLoading = true;
    errorMessage = null;
    successMessage = null;

    try {
      const resp = await authService.register(regName.trim(), regEmail.trim(), regPassword);
      if (resp.email_verification_required) {
        registeredSuccessEmail = regEmail.trim();
        regName = '';
        regEmail = '';
        regPassword = '';
      } else {
        const loginData = await authService.login(regEmail.trim(), regPassword);
        onLoginSuccess(loginData);
      }
    } catch (err: unknown) {
      if (err instanceof ApiError) {
        errorMessage = err.message;
      } else if (err instanceof Error) {
        errorMessage = err.message;
      } else {
        errorMessage = 'Gagal mendaftarkan akun baru.';
      }
    } finally {
      isLoading = false;
    }
  }

  async function handleResendVerification() {
    if (!registeredSuccessEmail || isResending) return;
    isResending = true;
    errorMessage = null;
    successMessage = null;

    try {
      const msg = await authService.resendVerification(registeredSuccessEmail);
      successMessage = msg;
      setTimeout(() => (successMessage = null), 4000);
    } catch (err: unknown) {
      if (err instanceof ApiError) {
        errorMessage = err.message;
      } else if (err instanceof Error) {
        errorMessage = err.message;
      } else {
        errorMessage = 'Gagal mengirim ulang email verifikasi.';
      }
    } finally {
      isResending = false;
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
      <div class="size-3 rounded-full bg-[#17171c]"></div>
      <span class="text-sm font-bold tracking-tight text-[#17171c]">PRÉCIS</span>
      <span class="border-l border-[#e5e5ea] pl-2 font-mono text-[10.5px] text-[#8e8e93]"
        >Enterprise POS &amp; HR</span
      >
    </div>
    <h1 class="text-xl font-bold tracking-tight text-[#17171c] sm:text-2xl">
      Sistem Operasional Terintegrasi
    </h1>
    <p class="mx-auto max-w-sm text-xs text-[#8e8e93]">
      Kelola pesanan kasir POS, absensi geofence, katalog inventaris, dan payroll tim
    </p>
  </div>

  <!-- Auth Container Card -->
  <div class="w-full max-w-md rounded-3xl border border-[#e5e5ea] bg-white p-6 shadow-sm sm:p-8">
    {#if registeredSuccessEmail}
      <!-- Screen: Verifikasi Email Dikirim -->
      <div class="space-y-5 py-2 text-center">
        <div
          class="mx-auto flex size-12 items-center justify-center rounded-2xl border border-[#bfdbfe] bg-[#eff6ff] text-[#2563eb]"
        >
          <Mail class="size-6" />
        </div>

        <div class="space-y-2">
          <h2 class="text-base font-bold text-[#17171c]">Periksa Inbox Email Anda</h2>
          <p class="text-xs leading-relaxed text-[#686873]">
            Kami telah mengirimkan tautan verifikasi ke <strong class="text-[#17171c]"
              >{registeredSuccessEmail}</strong
            >.
          </p>
          <p class="text-[11px] text-[#8e8e93]">
            Silakan buka email Anda dan klik tombol verifikasi untuk mengaktifkan akun sebelum
            masuk.
          </p>
        </div>

        {#if successMessage}
          <div
            class="rounded-2xl border border-[#a7f3d0] bg-[#ecfdf5] p-3.5 text-xs font-semibold text-[#065f46]"
          >
            {successMessage}
          </div>
        {/if}

        {#if errorMessage}
          <div
            class="rounded-2xl border border-[#fecaca] bg-[#fef2f2] p-3.5 text-xs font-semibold text-[#991b1b]"
          >
            {errorMessage}
          </div>
        {/if}

        <div class="space-y-2.5 pt-2">
          <button
            type="button"
            onclick={() => {
              email = registeredSuccessEmail || '';
              registeredSuccessEmail = null;
              mode = 'login';
              errorMessage = null;
              successMessage = null;
            }}
            class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] px-5 py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
          >
            <span>Masuk ke Akun</span>
            <ArrowRight class="size-4" />
          </button>

          <button
            type="button"
            onclick={handleResendVerification}
            disabled={isResending}
            class="w-full cursor-pointer py-2 text-xs font-semibold text-[#686873] transition-colors hover:text-[#17171c] disabled:opacity-50"
          >
            {isResending ? 'Mengirim ulang...' : 'Belum menerima email? Kirim Ulang'}
          </button>
        </div>
      </div>
    {:else}
      <!-- Mode Switcher Tabs -->
      <div
        class="mb-6 grid grid-cols-2 gap-1.5 rounded-2xl border border-[#e5e5ea] bg-[#f4f4f6] p-1.5"
      >
        <button
          type="button"
          onclick={() => {
            mode = 'login';
            errorMessage = null;
          }}
          class={`cursor-pointer rounded-xl py-2 text-center text-xs font-semibold transition-all ${
            mode === 'login'
              ? 'bg-white text-[#17171c] shadow-xs'
              : 'text-[#8e8e93] hover:text-[#17171c]'
          }`}
        >
          Masuk Akun
        </button>

        <button
          type="button"
          onclick={() => {
            mode = 'register';
            errorMessage = null;
          }}
          class={`cursor-pointer rounded-xl py-2 text-center text-xs font-semibold transition-all ${
            mode === 'register'
              ? 'bg-white text-[#17171c] shadow-xs'
              : 'text-[#8e8e93] hover:text-[#17171c]'
          }`}
        >
          Daftar Akun
        </button>
      </div>

      {#if errorMessage}
        <div
          class="mb-5 flex items-start gap-2.5 rounded-2xl border border-[#fecaca] bg-[#fef2f2] p-3.5 text-xs font-semibold text-[#991b1b]"
        >
          <AlertCircle class="mt-0.5 size-4 shrink-0" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      {#if successMessage}
        <div
          class="mb-5 flex items-start gap-2.5 rounded-2xl border border-[#a7f3d0] bg-[#ecfdf5] p-3.5 text-xs font-semibold text-[#065f46]"
        >
          <span>{successMessage}</span>
        </div>
      {/if}

      {#if mode === 'login'}
        <!-- Form Login -->
        <form onsubmit={handleLogin} class="space-y-4 text-xs">
          <div class="space-y-1.5">
            <label for="login-email" class="block font-bold text-[#17171c]"> Email Akun </label>
            <div class="relative">
              <input
                id="login-email"
                type="email"
                bind:value={email}
                placeholder="nama@kedai.com"
                required
                disabled={isLoading}
                class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] py-2.5 pr-4 pl-10 text-xs text-[#17171c] placeholder-[#8e8e93] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
              />
              <Mail class="absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-[#8e8e93]" />
            </div>
          </div>

          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <label for="login-password" class="block font-bold text-[#17171c]">
                Kata Sandi
              </label>
              <button
                type="button"
                onclick={() => (isForgotPasswordOpen = true)}
                class="cursor-pointer text-xs font-semibold text-[#2563eb] hover:underline"
              >
                Lupa kata sandi?
              </button>
            </div>
            <div class="relative">
              <input
                id="login-password"
                type={showPassword ? 'text' : 'password'}
                bind:value={password}
                placeholder="••••••••"
                required
                disabled={isLoading}
                class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-10 py-2.5 text-xs text-[#17171c] placeholder-[#8e8e93] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
              />
              <Lock class="absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-[#8e8e93]" />
              <button
                type="button"
                tabindex="-1"
                onclick={() => (showPassword = !showPassword)}
                class="absolute top-1/2 right-3.5 flex -translate-y-1/2 cursor-pointer items-center justify-center p-1 text-[#8e8e93] hover:text-[#17171c]"
                aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
              >
                {#if showPassword}
                  <EyeOff class="size-4" />
                {:else}
                  <Eye class="size-4" />
                {/if}
              </button>
            </div>
          </div>

          <button
            type="submit"
            disabled={isLoading}
            class="mt-2 flex w-full cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] px-5 py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
          >
            {#if isLoading}
              <span>Memvalidasi...</span>
            {:else}
              <span>Masuk ke Portal</span>
              <ArrowRight class="size-4" />
            {/if}
          </button>
        </form>
      {:else}
        <!-- Form Register Akun Baru -->
        <form onsubmit={handleRegister} class="space-y-4 text-xs">
          <div class="space-y-1.5">
            <label for="reg-name" class="block font-bold text-[#17171c]"> Nama Lengkap </label>
            <div class="relative">
              <input
                id="reg-name"
                type="text"
                bind:value={regName}
                placeholder="Contoh: Budi Santoso"
                required
                disabled={isLoading}
                class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] py-2.5 pr-4 pl-10 text-xs text-[#17171c] placeholder-[#8e8e93] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
              />
              <User class="absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-[#8e8e93]" />
            </div>
          </div>

          <div class="space-y-1.5">
            <label for="reg-email" class="block font-bold text-[#17171c]"> Alamat Email </label>
            <div class="relative">
              <input
                id="reg-email"
                type="email"
                bind:value={regEmail}
                placeholder="nama@email.com"
                required
                disabled={isLoading}
                class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] py-2.5 pr-4 pl-10 text-xs text-[#17171c] placeholder-[#8e8e93] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
              />
              <Mail class="absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-[#8e8e93]" />
            </div>
          </div>

          <div class="space-y-1.5">
            <label for="reg-password" class="block font-bold text-[#17171c]"> Kata Sandi </label>
            <div class="relative">
              <input
                id="reg-password"
                type={showPassword ? 'text' : 'password'}
                bind:value={regPassword}
                placeholder="Minimal 6 karakter"
                required
                minlength="6"
                disabled={isLoading}
                class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-10 py-2.5 text-xs text-[#17171c] placeholder-[#8e8e93] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
              />
              <Lock class="absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-[#8e8e93]" />
              <button
                type="button"
                tabindex="-1"
                onclick={() => (showPassword = !showPassword)}
                class="absolute top-1/2 right-3.5 flex -translate-y-1/2 cursor-pointer items-center justify-center p-1 text-[#8e8e93] hover:text-[#17171c]"
                aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
              >
                {#if showPassword}
                  <EyeOff class="size-4" />
                {:else}
                  <Eye class="size-4" />
                {/if}
              </button>
            </div>
          </div>

          <div
            class="rounded-2xl border border-[#ececee] bg-[#f8f8fa] p-3.5 text-[11px] leading-relaxed text-[#686873]"
          >
            Akun personal Anda dapat digunakan untuk menerima undangan tim workspace atau membuat
            bisnis baru.
          </div>

          <button
            type="submit"
            disabled={isLoading}
            class="mt-2 flex w-full cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] px-5 py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
          >
            {#if isLoading}
              <span>Mendaftarkan Akun...</span>
            {:else}
              <span>Daftar Akun Baru</span>
              <ArrowRight class="size-4" />
            {/if}
          </button>
        </form>
      {/if}
    {/if}
  </div>

  <!-- Security Note Footer -->
  <div class="mt-8 flex items-center gap-2 font-mono text-xs text-[#8e8e93]">
    <Shield class="size-4 text-[#8e8e93]" />
    <span>Terkoneksi Aman via Laravel Sanctum &amp; Multi-Tenant Database</span>
  </div>
</div>

<!-- Modal Lupa Kata Sandi -->
<ForgotPasswordModal isOpen={isForgotPasswordOpen} onClose={() => (isForgotPasswordOpen = false)} />

<!-- Modal Atur Ulang Kata Sandi -->
<ResetPasswordModal
  isOpen={isResetPasswordOpen}
  initialEmail={resetEmail}
  initialToken={resetToken}
  onClose={() => (isResetPasswordOpen = false)}
  onSuccess={() => {
    isResetPasswordOpen = false;
    errorMessage = null;
  }}
/>
