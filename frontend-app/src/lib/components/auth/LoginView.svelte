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

<div class="min-h-screen bg-[#fafafc] flex flex-col justify-center items-center p-4 sm:p-6 font-sans select-none">
  <!-- Brand Header -->
  <div class="mb-8 text-center space-y-2">
    <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-2xl bg-white border border-[#e5e5ea] shadow-2xs mb-2">
      <div class="w-3 h-3 rounded-full bg-[#17171c]"></div>
      <span class="text-sm font-bold tracking-tight text-[#17171c]">PRÉCIS</span>
      <span class="text-[10.5px] font-mono text-[#8e8e93] border-l border-[#e5e5ea] pl-2">Enterprise POS &amp; HR</span>
    </div>
    <h1 class="text-xl sm:text-2xl font-bold text-[#17171c] tracking-tight">
      Sistem Operasional Terintegrasi
    </h1>
    <p class="text-xs text-[#8e8e93] max-w-sm mx-auto">
      Kelola pesanan kasir POS, absensi geofence, katalog inventaris, dan payroll tim
    </p>
  </div>

  <!-- Auth Container Card -->
  <div class="w-full max-w-md bg-white border border-[#e5e5ea] rounded-3xl p-6 sm:p-8 shadow-sm">
    {#if registeredSuccessEmail}
      <!-- Screen: Verifikasi Email Dikirim -->
      <div class="space-y-5 text-center py-2">
        <div class="w-12 h-12 bg-[#eff6ff] text-[#2563eb] rounded-2xl flex items-center justify-center mx-auto border border-[#bfdbfe]">
          <Mail class="w-6 h-6" />
        </div>

        <div class="space-y-2">
          <h2 class="text-base font-bold text-[#17171c]">Periksa Inbox Email Anda</h2>
          <p class="text-xs text-[#686873] leading-relaxed">
            Kami telah mengirimkan tautan verifikasi ke <strong class="text-[#17171c]">{registeredSuccessEmail}</strong>.
          </p>
          <p class="text-[11px] text-[#8e8e93]">
            Silakan buka email Anda dan klik tombol verifikasi untuk mengaktifkan akun sebelum masuk.
          </p>
        </div>

        {#if successMessage}
          <div class="p-3.5 bg-[#ecfdf5] border border-[#a7f3d0] text-[#065f46] text-xs font-semibold rounded-2xl">
            {successMessage}
          </div>
        {/if}

        {#if errorMessage}
          <div class="p-3.5 bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs font-semibold rounded-2xl">
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
            class="w-full py-3 px-5 bg-[#17171c] hover:bg-black text-white font-semibold text-xs rounded-full flex items-center justify-center gap-2 transition-all cursor-pointer shadow-xs"
          >
            <span>Masuk ke Akun</span>
            <ArrowRight class="w-4 h-4" />
          </button>

          <button
            type="button"
            onclick={handleResendVerification}
            disabled={isResending}
            class="w-full py-2 text-xs text-[#686873] hover:text-[#17171c] font-semibold transition-colors cursor-pointer disabled:opacity-50"
          >
            {isResending ? 'Mengirim ulang...' : 'Belum menerima email? Kirim Ulang'}
          </button>
        </div>
      </div>
    {:else}
      <!-- Mode Switcher Tabs -->
      <div class="grid grid-cols-2 gap-1.5 bg-[#f4f4f6] p-1.5 rounded-2xl border border-[#e5e5ea] mb-6">
        <button
          type="button"
          onclick={() => {
            mode = 'login';
            errorMessage = null;
          }}
          class={`py-2 text-xs font-semibold rounded-xl transition-all cursor-pointer text-center ${
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
          class={`py-2 text-xs font-semibold rounded-xl transition-all cursor-pointer text-center ${
            mode === 'register'
              ? 'bg-white text-[#17171c] shadow-xs'
              : 'text-[#8e8e93] hover:text-[#17171c]'
          }`}
        >
          Daftar Akun
        </button>
      </div>

      {#if errorMessage}
        <div class="mb-5 p-3.5 bg-[#fef2f2] border border-[#fecaca] rounded-2xl text-xs font-semibold text-[#991b1b] flex items-start gap-2.5">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      {#if successMessage}
        <div class="mb-5 p-3.5 bg-[#ecfdf5] border border-[#a7f3d0] rounded-2xl text-xs font-semibold text-[#065f46] flex items-start gap-2.5">
          <span>{successMessage}</span>
        </div>
      {/if}

      {#if mode === 'login'}
        <!-- Form Login -->
        <form onsubmit={handleLogin} class="space-y-4 text-xs">
          <div class="space-y-1.5">
            <label for="login-email" class="block font-bold text-[#17171c]">
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
                class="w-full pl-10 pr-4 py-2.5 text-xs text-[#17171c] placeholder-[#8e8e93] border border-[#e5e5ea] rounded-xl focus:border-[#17171c] focus:outline-hidden bg-[#f8f8fa] hover:bg-white transition-all shadow-2xs"
              />
              <Mail class="w-4 h-4 text-[#8e8e93] absolute left-3.5 top-1/2 -translate-y-1/2" />
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
                class="text-xs text-[#2563eb] hover:underline cursor-pointer font-semibold"
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
                class="w-full pl-10 pr-10 py-2.5 text-xs text-[#17171c] placeholder-[#8e8e93] border border-[#e5e5ea] rounded-xl focus:border-[#17171c] focus:outline-hidden bg-[#f8f8fa] hover:bg-white transition-all shadow-2xs"
              />
              <Lock class="w-4 h-4 text-[#8e8e93] absolute left-3.5 top-1/2 -translate-y-1/2" />
              <button
                type="button"
                tabindex="-1"
                onclick={() => (showPassword = !showPassword)}
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#8e8e93] hover:text-[#17171c] p-1 cursor-pointer flex items-center justify-center"
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
            class="w-full mt-2 py-3 px-5 bg-[#17171c] hover:bg-black text-white font-semibold text-xs rounded-full flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-50 shadow-xs"
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
        <!-- Form Register Akun Baru -->
        <form onsubmit={handleRegister} class="space-y-4 text-xs">
          <div class="space-y-1.5">
            <label for="reg-name" class="block font-bold text-[#17171c]">
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
                class="w-full pl-10 pr-4 py-2.5 text-xs text-[#17171c] placeholder-[#8e8e93] border border-[#e5e5ea] rounded-xl focus:border-[#17171c] focus:outline-hidden bg-[#f8f8fa] hover:bg-white transition-all shadow-2xs"
              />
              <User class="w-4 h-4 text-[#8e8e93] absolute left-3.5 top-1/2 -translate-y-1/2" />
            </div>
          </div>

          <div class="space-y-1.5">
            <label for="reg-email" class="block font-bold text-[#17171c]">
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
                class="w-full pl-10 pr-4 py-2.5 text-xs text-[#17171c] placeholder-[#8e8e93] border border-[#e5e5ea] rounded-xl focus:border-[#17171c] focus:outline-hidden bg-[#f8f8fa] hover:bg-white transition-all shadow-2xs"
              />
              <Mail class="w-4 h-4 text-[#8e8e93] absolute left-3.5 top-1/2 -translate-y-1/2" />
            </div>
          </div>

          <div class="space-y-1.5">
            <label for="reg-password" class="block font-bold text-[#17171c]">
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
                class="w-full pl-10 pr-10 py-2.5 text-xs text-[#17171c] placeholder-[#8e8e93] border border-[#e5e5ea] rounded-xl focus:border-[#17171c] focus:outline-hidden bg-[#f8f8fa] hover:bg-white transition-all shadow-2xs"
              />
              <Lock class="w-4 h-4 text-[#8e8e93] absolute left-3.5 top-1/2 -translate-y-1/2" />
              <button
                type="button"
                tabindex="-1"
                onclick={() => (showPassword = !showPassword)}
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#8e8e93] hover:text-[#17171c] p-1 cursor-pointer flex items-center justify-center"
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

          <div class="p-3.5 bg-[#f8f8fa] rounded-2xl border border-[#ececee] text-[11px] text-[#686873] leading-relaxed">
            Akun personal Anda dapat digunakan untuk menerima undangan tim workspace atau membuat bisnis baru.
          </div>

          <button
            type="submit"
            disabled={isLoading}
            class="w-full mt-2 py-3 px-5 bg-[#17171c] hover:bg-black text-white font-semibold text-xs rounded-full flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-50 shadow-xs"
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
  <div class="mt-8 flex items-center gap-2 text-xs text-[#8e8e93] font-mono">
    <Shield class="w-4 h-4 text-[#8e8e93]" />
    <span>Terkoneksi Aman via Laravel Sanctum &amp; Multi-Tenant Database</span>
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
