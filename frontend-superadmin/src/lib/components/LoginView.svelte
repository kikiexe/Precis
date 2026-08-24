<script lang="ts">
  import { ShieldCheck, Lock, Mail, AlertCircle, RefreshCw, Eye, EyeOff } from 'lucide-svelte';

  interface Props {
    onLogin: (email: string, password: string) => Promise<void>;
  }

  let { onLogin }: Props = $props();

  let email = $state('');
  let password = $state('');
  let showPassword = $state(false);
  let isSubmitting = $state(false);
  let errorMessage = $state<string | null>(null);

  async function handleSubmit(e: Event) {
    e.preventDefault();
    if (!email || !password) {
      errorMessage = 'Harap masukkan email dan kata sandi root Superadmin.';
      return;
    }

    isSubmitting = true;
    errorMessage = null;

    try {
      await onLogin(email, password);
    } catch (err: unknown) {
      if (err instanceof Error) {
        errorMessage = err.message;
      } else {
        errorMessage = 'Terjadi kesalahan saat memproses login Superadmin.';
      }
    } finally {
      isSubmitting = false;
    }
  }
</script>

<div class="min-h-screen bg-[#eeece7]/40 flex items-center justify-center p-4 font-sans">
  <div class="w-full max-w-md bg-white border border-[#d9d9dd] rounded-[22px] shadow-none p-8 sm:p-10">
    <!-- Header -->
    <div class="flex items-center space-x-3 mb-6">
      <img src="/logo.png" alt="Précis Logo" class="w-9 h-9 rounded-[10px] object-cover border border-[#d9d9dd]" />
      <div>
        <div class="flex items-center space-x-2">
          <span class="font-medium text-lg tracking-tight text-[#212121]">PRÉCIS</span>
          <span class="text-[10px] px-2 py-0.5 bg-[#17171c] text-white font-mono rounded-full font-medium">Root</span>
        </div>
        <p class="text-xs text-[#75758a]">Platform Superadmin Portal</p>
      </div>
    </div>

    <div class="mb-6">
      <h1 class="text-base font-medium text-[#212121] tracking-tight">Masuk ke Konsol Superadmin</h1>
      <p class="text-xs text-[#616161] mt-0.5 font-normal">
        Area terbatas untuk verifikasi pembayaran manual, pengawasan tenant, dan kontrol platform root.
      </p>
    </div>

    <!-- Error Alert -->
    {#if errorMessage}
      <div class="mb-5 p-3.5 bg-[#ffad9b]/15 border border-[#ffad9b] rounded-xl text-[#b30000] text-xs flex items-start space-x-2">
        <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
        <span>{errorMessage}</span>
      </div>
    {/if}

    <!-- Form -->
    <form onsubmit={handleSubmit} class="space-y-4 text-xs">
      <div>
        <label for="admin-email" class="block font-medium text-[#212121] text-[11px] mb-1.5">
          Email Superadmin
        </label>
        <div class="relative">
          <Mail class="w-4 h-4 text-[#93939f] absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            id="admin-email"
            type="email"
            bind:value={email}
            required
            placeholder="admin@gmail.com"
            class="w-full pl-10 pr-3.5 py-2.5 bg-white border border-[#d9d9dd] rounded-xl focus:outline-hidden focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 text-[#212121] font-mono transition-all"
          />
        </div>
      </div>

      <div>
        <label for="admin-password" class="block font-medium text-[#212121] text-[11px] mb-1.5">
          Kata Sandi Root
        </label>
        <div class="relative">
          <Lock class="w-4 h-4 text-[#93939f] absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            id="admin-password"
            type={showPassword ? 'text' : 'password'}
            bind:value={password}
            required
            placeholder="••••••••••••"
            class="w-full pl-10 pr-10 py-2.5 bg-white border border-[#d9d9dd] rounded-xl focus:outline-hidden focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 text-[#212121] transition-all"
          />
          <button
            type="button"
            tabindex="-1"
            onclick={() => (showPassword = !showPassword)}
            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#93939f] hover:text-[#212121] transition-colors p-1 cursor-pointer flex items-center justify-center"
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

      <div class="pt-2">
        <button
          type="submit"
          disabled={isSubmitting}
          class="w-full py-3 bg-[#17171c] hover:bg-[#000000] text-white font-medium text-xs rounded-full transition-all flex items-center justify-center space-x-2 cursor-pointer shadow-none disabled:opacity-50"
        >
          {#if isSubmitting}
            <RefreshCw class="w-4 h-4 animate-spin" />
            <span>Memverifikasi Sesi...</span>
          {:else}
            <ShieldCheck class="w-4 h-4" />
            <span>Otorisasi Masuk</span>
          {/if}
        </button>
      </div>
    </form>
  </div>
</div>
