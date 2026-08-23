<script lang="ts">
  import { Lock, Mail, AlertCircle, ArrowRight, Shield, Sparkles } from 'lucide-svelte';
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

<div class="min-h-screen bg-[#f4f4f4] flex flex-col justify-center items-center p-4 select-none">
  <!-- logo dan judul merek -->
  <div class="w-full max-w-md mb-6 text-center">
    <div class="inline-flex items-center justify-center w-12 h-12 bg-[#0f62fe] text-white font-bold text-xl font-display mb-3 shadow-md">
      P
    </div>
    <h1 class="text-2xl font-bold text-[#161616] tracking-tight font-display">PRÉCIS PORTAL</h1>
    <p class="text-xs text-[#525252] mt-1">
      SaaS Presensi, Kasir POS & Payroll Multi-Tenant F&B
    </p>
  </div>

  <!-- login card -->
  <div class="w-full max-w-md bg-white border border-[#e0e0e0] shadow-sm p-6 sm:p-8">
    <div class="mb-6">
      <h2 class="text-lg font-bold text-[#161616] font-display">Masuk ke Akun</h2>
      <p class="text-xs text-[#525252] mt-0.5">
        Gunakan kredensial akun terdaftar untuk mengakses portal bisnis Anda.
      </p>
    </div>

    {#if errorMessage}
      <div class="mb-4 p-3 bg-[#da1e28]/10 border-l-4 border-[#da1e28] text-xs text-[#da1e28] flex items-start gap-2">
        <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
        <span>{errorMessage}</span>
      </div>
    {/if}

    <form onsubmit={handleLogin} class="space-y-4">
      <div>
        <label for="login-email" class="block text-xs font-semibold text-[#161616] mb-1.5">
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
            class="w-full pl-9 pr-3 py-2.5 text-xs border border-[#e0e0e0] focus:border-[#0f62fe] focus:outline-hidden bg-[#f4f4f4] focus:bg-white transition-colors"
          />
          <Mail class="w-4 h-4 text-[#8c8c8c] absolute left-3 top-3" />
        </div>
      </div>

      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label for="login-password" class="text-xs font-semibold text-[#161616]">
            Kata Sandi
          </label>
          <button
            type="button"
            onclick={() => (isForgotPasswordOpen = true)}
            class="text-[11px] text-[#0f62fe] hover:underline cursor-pointer"
          >
            Lupa kata sandi?
          </button>
        </div>
        <div class="relative">
          <input
            id="login-password"
            type="password"
            bind:value={password}
            placeholder="••••••••"
            required
            disabled={isLoading}
            class="w-full pl-9 pr-3 py-2.5 text-xs border border-[#e0e0e0] focus:border-[#0f62fe] focus:outline-hidden bg-[#f4f4f4] focus:bg-white transition-colors"
          />
          <Lock class="w-4 h-4 text-[#8c8c8c] absolute left-3 top-3" />
        </div>
      </div>

      <button
        type="submit"
        disabled={isLoading}
        class="w-full mt-2 py-2.5 px-4 bg-[#0f62fe] hover:bg-[#0050e6] text-white font-bold text-xs flex items-center justify-center gap-2 transition-colors cursor-pointer disabled:opacity-50"
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
    <div class="mt-6 pt-5 border-t border-[#e0e0e0]">
      <div class="flex items-center gap-1.5 text-[11px] font-mono text-[#8c8c8c] mb-2.5">
        <Sparkles class="w-3.5 h-3.5 text-[#0f62fe]" />
        <span>Kredensial Cepat (Pilot Seeder):</span>
      </div>
      <div class="grid grid-cols-3 gap-2">
        <button
          type="button"
          onclick={() => setDemoCredentials('arief.owner@precis.test')}
          class="p-2 text-left border border-[#e0e0e0] hover:border-[#161616] hover:bg-[#f4f4f4] transition-colors cursor-pointer"
        >
          <div class="text-[10px] font-bold text-[#161616]">Owner</div>
          <div class="text-[9px] text-[#8c8c8c] font-mono truncate">arief.owner</div>
        </button>
        <button
          type="button"
          onclick={() => setDemoCredentials('manager.sleman@precis.test')}
          class="p-2 text-left border border-[#e0e0e0] hover:border-[#0f62fe] hover:bg-[#f4f4f4] transition-colors cursor-pointer"
        >
          <div class="text-[10px] font-bold text-[#0f62fe]">Manager</div>
          <div class="text-[9px] text-[#8c8c8c] font-mono truncate">manager.sleman</div>
        </button>
        <button
          type="button"
          onclick={() => setDemoCredentials('staff1.sleman@precis.test')}
          class="p-2 text-left border border-[#e0e0e0] hover:border-[#24a148] hover:bg-[#f4f4f4] transition-colors cursor-pointer"
        >
          <div class="text-[10px] font-bold text-[#24a148]">Staf</div>
          <div class="text-[9px] text-[#8c8c8c] font-mono truncate">staff1.sleman</div>
        </button>
      </div>
    </div>
  </div>

  <!-- catatan keamanan di footer -->
  <div class="mt-6 flex items-center gap-2 text-[11px] text-[#8c8c8c] font-mono">
    <Shield class="w-3.5 h-3.5" />
    <span>Terkoneksi Aman via Laravel Sanctum & PostgreSQL 16</span>
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
