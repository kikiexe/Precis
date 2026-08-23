<script lang="ts">
  import { Lock, Mail, AlertCircle, ArrowRight, Shield, Sparkles, Eye, EyeOff } from 'lucide-svelte';
  import { authService } from '../../services/auth-service';
  import { ApiError } from '../../services/api-client';
  import type { LoginResponseData } from '../../types/app';
  import ForgotPasswordModal from './ForgotPasswordModal.svelte';
  import ResetPasswordModal from './ResetPasswordModal.svelte';

  interface Props {
    onLoginSuccess: (data: LoginResponseData) => void;
  }

  let { onLoginSuccess }: Props = $props();

  let email = $state('arief.owner@precis.test');
  let password = $state('password123');
  let showPassword = $state(false);
  let isLoading = $state(false);
  let errorMessage = $state<string | null>(null);

  let isForgotPasswordOpen = $state(false);
  let isResetPasswordOpen = $state(false);
  let resetEmail = $state('');
  let resetToken = $state('');

  // periksa parameter url jika diarahkan dari tautan reset kata sandi
  $effect(() => {
    if (typeof window !== 'undefined') {
      const urlParams = new URLSearchParams(window.location.search);
      const tokenParam = urlParams.get('token');
      const emailParam = urlParams.get('email');

      if (tokenParam && emailParam) {
        resetToken = tokenParam;
        resetEmail = emailParam;
        isResetPasswordOpen = true;
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

  function setDemoCredentials(demoEmail: string) {
    email = demoEmail;
    password = 'password123';
    errorMessage = null;
  }
</script>

<div class="min-h-screen bg-[#eeece7]/40 flex flex-col justify-center items-center p-4 sm:p-6 select-none font-sans">
  <!-- logo dan judul merek -->
  <div class="w-full max-w-md mb-8 text-center">
    <div class="inline-flex items-center justify-center w-12 h-12 bg-[#17171c] text-white font-medium text-lg rounded-[14px] mb-4 border border-[#d9d9dd]">
      P
    </div>
    <h1 class="text-2xl font-medium text-[#212121] tracking-tight">PRÉCIS PORTAL</h1>
    <p class="text-xs text-[#616161] mt-1.5 font-normal">
      SaaS Presensi, Kasir POS &amp; Payroll Multi-Tenant F&amp;B
    </p>
  </div>

  <!-- login card -->
  <div class="w-full max-w-md bg-white border border-[#d9d9dd] rounded-[22px] p-6 sm:p-8">
    <div class="mb-6">
      <h2 class="text-lg font-medium text-[#212121]">Masuk ke Akun</h2>
      <p class="text-xs text-[#616161] mt-1">
        Gunakan kredensial akun terdaftar untuk mengakses portal bisnis Anda.
      </p>
    </div>

    {#if errorMessage}
      <div class="mb-5 p-3.5 bg-[#ffad9b]/15 border border-[#ffad9b] rounded-[12px] text-xs text-[#b30000] flex items-start gap-2.5">
        <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
        <span>{errorMessage}</span>
      </div>
    {/if}

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
            placeholder="nama@perusahaan.com"
            required
            disabled={isLoading}
            class="w-full pl-9 pr-3 py-2.5 text-xs text-[#212121] placeholder-[#93939f] border border-[#d9d9dd] rounded-[12px] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden bg-white transition-all"
          />
          <Mail class="w-4 h-4 text-[#93939f] absolute left-3 top-3" />
        </div>
      </div>

      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label for="login-password" class="text-xs font-medium text-[#212121]">
            Kata Sandi
          </label>
          <button
            type="button"
            onclick={() => (isForgotPasswordOpen = true)}
            class="text-[11px] text-[#1863dc] hover:underline cursor-pointer font-medium"
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
            class="w-full pl-9 pr-10 py-2.5 text-xs text-[#212121] placeholder-[#93939f] border border-[#d9d9dd] rounded-[12px] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden bg-white transition-all"
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
        class="w-full mt-2 py-3 px-5 bg-[#17171c] hover:bg-[#000000] text-white font-medium text-xs rounded-full flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-50"
      >
        {#if isLoading}
          <span>Memvalidasi...</span>
        {:else}
          <span>Masuk ke Portal</span>
          <ArrowRight class="w-4 h-4" />
        {/if}
      </button>
    </form>

    <!-- pengalih persona cepat untuk demonstrasi dan pengujian -->
    <div class="mt-6 pt-5 border-t border-[#d9d9dd]">
      <div class="flex items-center gap-1.5 text-[11px] font-mono text-[#75758a] mb-3">
        <Sparkles class="w-3.5 h-3.5 text-[#1863dc]" />
        <span>Kredensial Cepat (Pilot Seeder):</span>
      </div>
      <div class="grid grid-cols-3 gap-2">
        <button
          type="button"
          onclick={() => setDemoCredentials('arief.owner@precis.test')}
          class="p-2.5 text-left border border-[#d9d9dd] rounded-[10px] bg-[#eeece7]/30 hover:bg-[#eeece7] hover:border-[#17171c] transition-all cursor-pointer"
        >
          <div class="text-[11px] font-medium text-[#212121]">Owner</div>
          <div class="text-[10px] text-[#75758a] font-mono truncate">arief.owner</div>
        </button>
        <button
          type="button"
          onclick={() => setDemoCredentials('manager.sleman@precis.test')}
          class="p-2.5 text-left border border-[#d9d9dd] rounded-[10px] bg-[#eeece7]/30 hover:bg-[#eeece7] hover:border-[#17171c] transition-all cursor-pointer"
        >
          <div class="text-[11px] font-medium text-[#1863dc]">Manager</div>
          <div class="text-[10px] text-[#75758a] font-mono truncate">manager.sleman</div>
        </button>
        <button
          type="button"
          onclick={() => setDemoCredentials('staff1.sleman@precis.test')}
          class="p-2.5 text-left border border-[#d9d9dd] rounded-[10px] bg-[#eeece7]/30 hover:bg-[#eeece7] hover:border-[#17171c] transition-all cursor-pointer"
        >
          <div class="text-[11px] font-medium text-[#003c33]">Staf</div>
          <div class="text-[10px] text-[#75758a] font-mono truncate">staff1.sleman</div>
        </button>
      </div>
    </div>
  </div>

  <!-- catatan keamanan di footer -->
  <div class="mt-8 flex items-center gap-2 text-[11px] text-[#75758a] font-mono">
    <Shield class="w-3.5 h-3.5" />
    <span>Terkoneksi Aman via Laravel Sanctum &amp; PostgreSQL 16</span>
  </div>
</div>

<!-- modal lupa kata sandi -->
<ForgotPasswordModal
  isOpen={isForgotPasswordOpen}
  onClose={() => (isForgotPasswordOpen = false)}
/>

<!-- modal atur ulang kata sandi -->
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
