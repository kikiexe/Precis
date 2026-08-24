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

  // periksa parameter url jika diarahkan dari tautan reset kata sandi atau mode daftar
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
      await authService.register(
        regName.trim(),
        regEmail.trim(),
        regPassword
      );
      registeredSuccessEmail = regEmail.trim();
      regName = '';
      regEmail = '';
      regPassword = '';
    } catch (err: unknown) {
      if (err instanceof ApiError) {
        errorMessage = err.message;
      } else if (err instanceof Error) {
        errorMessage = err.message;
      } else {
        errorMessage = 'Terjadi kesalahan saat melakukan registrasi akun.';
      }
    } finally {
      isLoading = false;
    }
  }

  async function handleResendVerification() {
    if (!registeredSuccessEmail) return;
    isResending = true;
    errorMessage = null;
    successMessage = null;
    try {
      const msg = await authService.resendVerification(registeredSuccessEmail);
      successMessage = msg;
    } catch (err: unknown) {
      errorMessage = err instanceof Error ? err.message : 'Gagal mengirim ulang email verifikasi.';
    } finally {
      isResending = false;
    }
  }
</script>

<div class="min-h-screen bg-[#eeece7]/40 flex flex-col justify-center items-center p-4 sm:p-6 select-none font-sans">
  <!-- Logo & Title -->
  <div class="w-full max-w-md mb-6 text-center">
    <img src="/logo.png" alt="Précis Logo" class="inline-block w-12 h-12 rounded-[14px] mb-3 border border-[#d9d9dd] object-cover" />
    <h1 class="text-2xl font-medium text-[#212121] tracking-tight">PRÉCIS PORTAL</h1>
    <p class="text-xs text-[#616161] mt-1 font-normal">
      SaaS Presensi, Kasir POS &amp; Payroll Multi-Tenant F&amp;B
    </p>
  </div>

  <!-- Auth Container Card -->
  <div class="w-full max-w-md bg-white border border-[#d9d9dd] rounded-3xl p-6 sm:p-8 shadow-none">
    {#if registeredSuccessEmail}
      <!-- Screen: Verifikasi Email Telah Dikirim -->
      <div class="space-y-5 text-center py-2">
        <div class="w-12 h-12 bg-[#edfce9] text-[#003c33] rounded-full flex items-center justify-center mx-auto">
          <Mail class="w-6 h-6" />
        </div>

        <div class="space-y-2">
          <h2 class="text-base font-semibold text-[#17171c]">Periksa Inbox Email Anda</h2>
          <p class="text-xs text-[#616161] leading-relaxed">
            Kami telah mengirimkan tautan verifikasi ke <strong class="text-[#17171c] font-medium">{registeredSuccessEmail}</strong>.
          </p>
          <p class="text-[11px] text-[#75758a]">
            Silakan buka email Anda dan klik tombol verifikasi untuk mengaktifkan akun sebelum masuk.
          </p>
        </div>

        {#if successMessage}
          <div class="p-3 bg-[#edfce9] border border-[#00875a]/20 text-[#003c33] text-xs rounded-xl">
            {successMessage}
          </div>
        {/if}

        {#if errorMessage}
          <div class="p-3 bg-[#ffefef] border border-[#e5484d]/20 text-[#e5484d] text-xs rounded-xl">
            {errorMessage}
          </div>
        {/if}

        <div class="space-y-2 pt-2">
          <button
            type="button"
            onclick={() => {
              email = registeredSuccessEmail || '';
              registeredSuccessEmail = null;
              mode = 'login';
              errorMessage = null;
              successMessage = null;
            }}
            class="w-full py-3 px-5 bg-[#17171c] hover:bg-black text-white font-medium text-xs rounded-full flex items-center justify-center gap-2 transition-all cursor-pointer shadow-none"
          >
            <span>Masuk ke Akun</span>
            <ArrowRight class="w-4 h-4" />
          </button>

          <button
            type="button"
            onclick={handleResendVerification}
            disabled={isResending}
            class="w-full py-2 text-xs text-[#616161] hover:text-[#17171c] font-medium transition-colors cursor-pointer disabled:opacity-50"
          >
            {isResending ? 'Mengirim ulang...' : 'Belum menerima email? Kirim Ulang'}
          </button>
        </div>
      </div>
    {:else}
      <!-- Mode Switcher Tabs -->
      <div class="grid grid-cols-2 gap-1 bg-[#eeece7]/60 p-1 rounded-full border border-[#d9d9dd] mb-6">
        <button
          type="button"
          onclick={() => {
            mode = 'login';
            errorMessage = null;
          }}
          class={`py-2 text-xs font-medium rounded-full transition-all cursor-pointer text-center ${
            mode === 'login'
              ? 'bg-[#17171c] text-white shadow-none'
              : 'text-[#616161] hover:text-[#212121]'
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
          class={`py-2 text-xs font-medium rounded-full transition-all cursor-pointer text-center flex items-center justify-center gap-1.5 ${
            mode === 'register'
              ? 'bg-[#17171c] text-white shadow-none'
              : 'text-[#616161] hover:text-[#212121]'
          }`}
        >
          <span>Daftar Akun</span>
        </button>
      </div>

      {#if errorMessage}
        <div class="mb-5 p-3.5 bg-[#ffefef] border border-[#ffefef] rounded-[14px] text-xs text-[#e5484d] flex items-start gap-2.5">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      {#if successMessage}
        <div class="mb-5 p-3.5 bg-[#edfce9] border border-[#edfce9] rounded-[14px] text-xs text-[#003c33] flex items-start gap-2.5">
          <span>{successMessage}</span>
        </div>
      {/if}

      {#if mode === 'login'}
        <!-- Form Login -->
        <form onsubmit={handleLogin} class="space-y-4">
        <div>
          <label for="login-email" class="block text-xs font-medium text-[#212121] mb-1.5">
            Email Akun
          </label>
          <div class="relative">
            <input
              id="login-email"
              type="email"
              bind:value={email}
              placeholder="nama@kedai.com"
              required
              disabled={isLoading}
              class="w-full pl-9 pr-3.5 py-2.5 text-xs text-[#212121] placeholder-[#93939f] border border-[#d9d9dd] rounded-xl focus:border-[#17171c] focus:outline-hidden bg-white transition-all"
            />
            <Mail class="w-4 h-4 text-[#93939f] absolute left-3 top-1/2 -translate-y-1/2" />
          </div>
        </div>

        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label for="login-password" class="block text-xs font-medium text-[#212121]">
              Kata Sandi
            </label>
            <button
              type="button"
              onclick={() => (isForgotPasswordOpen = true)}
              class="text-[11px] text-[#1863dc] hover:underline cursor-pointer"
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
              class="w-full pl-9 pr-10 py-2.5 text-xs text-[#212121] placeholder-[#93939f] border border-[#d9d9dd] rounded-xl focus:border-[#17171c] focus:outline-hidden bg-white transition-all"
            />
            <Lock class="w-4 h-4 text-[#93939f] absolute left-3 top-1/2 -translate-y-1/2" />
            <button
              type="button"
              tabindex="-1"
              onclick={() => (showPassword = !showPassword)}
              class="absolute right-3 top-1/2 -translate-y-1/2 text-[#93939f] hover:text-[#212121] transition-colors p-1 cursor-pointer flex items-center justify-center"
              aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
            >
              {#if showPassword}
                <EyeOff class="w-4 h-4" />
              {:else}
                <Eye class="w-4 h-4" />
              {/if}
            </button>
          </div>
        </div>

        <button
          type="submit"
          disabled={isLoading}
          class="w-full mt-2 py-3 px-5 bg-[#17171c] hover:bg-black text-white font-medium text-xs rounded-full flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-50 shadow-none"
        >
          {#if isLoading}
            <span>Memvalidasi...</span>
          {:else}
            <span>Masuk ke Portal</span>
            <ArrowRight class="w-4 h-4" />
          {/if}
        </button>
      </form>
    {:else}
      <!-- Form Register Akun Baru (General) -->
      <form onsubmit={handleRegister} class="space-y-3.5">
        <div>
          <label for="reg-name" class="block text-xs font-medium text-[#212121] mb-1">
            Nama Lengkap
          </label>
          <div class="relative">
            <input
              id="reg-name"
              type="text"
              bind:value={regName}
              placeholder="Contoh: Budi Santoso"
              required
              disabled={isLoading}
              class="w-full pl-9 pr-3.5 py-2.5 text-xs text-[#212121] placeholder-[#93939f] border border-[#d9d9dd] rounded-xl focus:border-[#17171c] focus:outline-hidden bg-white transition-all"
            />
            <User class="w-4 h-4 text-[#93939f] absolute left-3 top-1/2 -translate-y-1/2" />
          </div>
        </div>

        <div>
          <label for="reg-email" class="block text-xs font-medium text-[#212121] mb-1">
            Alamat Email
          </label>
          <div class="relative">
            <input
              id="reg-email"
              type="email"
              bind:value={regEmail}
              placeholder="nama@email.com"
              required
              disabled={isLoading}
              class="w-full pl-9 pr-3.5 py-2.5 text-xs text-[#212121] placeholder-[#93939f] border border-[#d9d9dd] rounded-xl focus:border-[#17171c] focus:outline-hidden bg-white transition-all"
            />
            <Mail class="w-4 h-4 text-[#93939f] absolute left-3 top-1/2 -translate-y-1/2" />
          </div>
        </div>

        <div>
          <label for="reg-password" class="block text-xs font-medium text-[#212121] mb-1">
            Kata Sandi
          </label>
          <div class="relative">
            <input
              id="reg-password"
              type={showPassword ? 'text' : 'password'}
              bind:value={regPassword}
              placeholder="Minimal 6 karakter"
              required
              minlength="6"
              disabled={isLoading}
              class="w-full pl-9 pr-10 py-2.5 text-xs text-[#212121] placeholder-[#93939f] border border-[#d9d9dd] rounded-xl focus:border-[#17171c] focus:outline-hidden bg-white transition-all"
            />
            <Lock class="w-4 h-4 text-[#93939f] absolute left-3 top-1/2 -translate-y-1/2" />
            <button
              type="button"
              tabindex="-1"
              onclick={() => (showPassword = !showPassword)}
              class="absolute right-3 top-1/2 -translate-y-1/2 text-[#93939f] hover:text-[#212121] transition-colors p-1 cursor-pointer flex items-center justify-center"
              aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
            >
              {#if showPassword}
                <EyeOff class="w-4 h-4" />
              {:else}
                <Eye class="w-4 h-4" />
              {/if}
            </button>
          </div>
        </div>

        <div class="p-3 bg-[#eeece7]/50 rounded-xl border border-[#d9d9dd] text-[11px] text-[#616161] leading-relaxed">
          Akun personal Anda dapat digunakan untuk menerima undangan tim workspace atau membuat bisnis baru.
        </div>

        <button
          type="submit"
          disabled={isLoading}
          class="w-full mt-2 py-3 px-5 bg-[#17171c] hover:bg-black text-white font-medium text-xs rounded-full flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-50 shadow-none"
        >
          {#if isLoading}
            <span>Mendaftarkan Akun...</span>
          {:else}
            <span>Daftar Akun Baru</span>
            <ArrowRight class="w-4 h-4" />
          {/if}
        </button>
      </form>
    {/if}
    {/if}
  </div>

  <!-- Security Note Footer -->
  <div class="mt-6 flex items-center gap-2 text-[11px] text-[#75758a] font-mono">
    <Shield class="w-3.5 h-3.5" />
    <span>Terkoneksi Aman via Laravel Sanctum &amp; SQLite/PostgreSQL 16</span>
  </div>
</div>

<!-- Modal Lupa Kata Sandi -->
<ForgotPasswordModal
  isOpen={isForgotPasswordOpen}
  onClose={() => (isForgotPasswordOpen = false)}
/>

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
