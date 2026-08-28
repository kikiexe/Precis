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

<div class="flex min-h-screen items-center justify-center bg-[#eeece7]/40 p-4 font-sans">
  <div
    class="w-full max-w-md rounded-[22px] border border-[#d9d9dd] bg-white p-8 shadow-none sm:p-10"
  >
    <!-- Header -->
    <div class="mb-6 flex items-center space-x-3">
      <img
        src="/logo.png"
        alt="Précis Logo"
        class="h-9 w-9 rounded-[10px] border border-[#d9d9dd] object-cover"
      />
      <div>
        <div class="flex items-center space-x-2">
          <span class="text-lg font-medium tracking-tight text-[#212121]">PRÉCIS</span>
          <span
            class="rounded-full bg-[#17171c] px-2 py-0.5 font-mono text-[10px] font-medium text-white"
            >Root</span
          >
        </div>
        <p class="text-xs text-[#75758a]">Platform Superadmin Portal</p>
      </div>
    </div>

    <div class="mb-6">
      <h1 class="text-base font-medium tracking-tight text-[#212121]">
        Masuk ke Konsol Superadmin
      </h1>
      <p class="mt-0.5 text-xs font-normal text-[#616161]">
        Area terbatas untuk verifikasi pembayaran manual, pengawasan tenant, dan kontrol platform
        root.
      </p>
    </div>

    <!-- Error Alert -->
    {#if errorMessage}
      <div
        class="mb-5 flex items-start space-x-2 rounded-xl border border-[#ffad9b] bg-[#ffad9b]/15 p-3.5 text-xs text-[#b30000]"
      >
        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
        <span>{errorMessage}</span>
      </div>
    {/if}

    <!-- Form -->
    <form onsubmit={handleSubmit} class="space-y-4 text-xs">
      <div>
        <label for="admin-email" class="mb-1.5 block text-[11px] font-medium text-[#212121]">
          Email Superadmin
        </label>
        <div class="relative">
          <Mail class="absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-[#93939f]" />
          <input
            id="admin-email"
            type="email"
            bind:value={email}
            required
            placeholder="admin@gmail.com"
            class="w-full rounded-xl border border-[#d9d9dd] bg-white py-2.5 pr-3.5 pl-10 font-mono text-[#212121] transition-all focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
          />
        </div>
      </div>

      <div>
        <label for="admin-password" class="mb-1.5 block text-[11px] font-medium text-[#212121]">
          Kata Sandi Root
        </label>
        <div class="relative">
          <Lock class="absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-[#93939f]" />
          <input
            id="admin-password"
            type={showPassword ? 'text' : 'password'}
            bind:value={password}
            required
            placeholder="••••••••••••"
            class="w-full rounded-xl border border-[#d9d9dd] bg-white py-2.5 pr-10 pl-10 text-[#212121] transition-all focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
          />
          <button
            type="button"
            tabindex="-1"
            onclick={() => (showPassword = !showPassword)}
            class="absolute top-1/2 right-3.5 flex -translate-y-1/2 cursor-pointer items-center justify-center p-1 text-[#93939f] transition-colors hover:text-[#212121]"
            aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
          >
            {#if showPassword}
              <EyeOff class="h-4 w-4" />
            {:else}
              <Eye class="h-4 w-4" />
            {/if}
          </button>
        </div>
      </div>

      <div class="pt-2">
        <button
          type="submit"
          disabled={isSubmitting}
          class="flex w-full cursor-pointer items-center justify-center space-x-2 rounded-full bg-[#17171c] py-3 text-xs font-medium text-white shadow-none transition-all hover:bg-[#000000] disabled:opacity-50"
        >
          {#if isSubmitting}
            <RefreshCw class="h-4 w-4 animate-spin" />
            <span>Memverifikasi Sesi...</span>
          {:else}
            <ShieldCheck class="h-4 w-4" />
            <span>Otorisasi Masuk</span>
          {/if}
        </button>
      </div>
    </form>
  </div>
</div>
