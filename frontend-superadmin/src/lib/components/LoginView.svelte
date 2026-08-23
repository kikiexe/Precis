<script lang="ts">
  import { ShieldCheck, Lock, Mail, AlertCircle, RefreshCw } from 'lucide-svelte';

  interface Props {
    onLogin: (email: string, password: string) => Promise<void>;
  }

  let { onLogin }: Props = $props();

  let email = $state('root@precis.com');
  let password = $state('PrecisAdmin2026!');
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

<div class="min-h-screen bg-[#161616] flex items-center justify-center p-4">
  <div class="w-full max-w-md bg-white border border-[#393939] shadow-2xl p-8">
    <!-- Header -->
    <div class="flex items-center space-x-3 mb-6">
      <div class="w-10 h-10 bg-[#0f62fe] flex items-center justify-center font-bold text-white text-xl tracking-wider">
        P
      </div>
      <div>
        <div class="flex items-center space-x-2">
          <span class="font-bold text-xl tracking-tight text-[#161616]">PRÉCIS</span>
          <span class="text-[10px] px-2 py-0.5 bg-[#da1e28] text-white font-mono uppercase font-bold">Root</span>
        </div>
        <p class="text-xs text-[#525252]">Platform Superadmin Portal</p>
      </div>
    </div>

    <div class="mb-6">
      <h1 class="text-base font-bold text-[#161616]">Masuk ke Konsol Superadmin</h1>
      <p class="text-xs text-[#525252] mt-0.5">
        Area terbatas untuk verifikasi pembayaran manual, pengawasan tenant, dan kontrol platform root.
      </p>
    </div>

    <!-- Error Alert -->
    {#if errorMessage}
      <div class="mb-5 p-3 bg-[#ffebee] border-l-4 border-[#da1e28] text-[#da1e28] text-xs flex items-start space-x-2">
        <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
        <span>{errorMessage}</span>
      </div>
    {/if}

    <!-- Form -->
    <form onsubmit={handleSubmit} class="space-y-4 text-xs">
      <div>
        <label for="admin-email" class="block font-semibold uppercase text-[#161616] text-[11px] mb-1">
          Email Superadmin
        </label>
        <div class="relative">
          <Mail class="w-4 h-4 text-[#8c8c8c] absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            id="admin-email"
            type="email"
            bind:value={email}
            required
            placeholder="root@precis.com"
            class="w-full pl-9 pr-3 py-2 bg-[#f4f4f4] border border-[#e0e0e0] focus:outline-none focus:border-[#0f62fe] focus:bg-white text-[#161616] font-mono"
          />
        </div>
      </div>

      <div>
        <label for="admin-password" class="block font-semibold uppercase text-[#161616] text-[11px] mb-1">
          Kata Sandi Root
        </label>
        <div class="relative">
          <Lock class="w-4 h-4 text-[#8c8c8c] absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            id="admin-password"
            type="password"
            bind:value={password}
            required
            placeholder="••••••••••••"
            class="w-full pl-9 pr-3 py-2 bg-[#f4f4f4] border border-[#e0e0e0] focus:outline-none focus:border-[#0f62fe] focus:bg-white text-[#161616]"
          />
        </div>
      </div>

      <div class="pt-2">
        <button
          type="submit"
          disabled={isSubmitting}
          class="w-full py-2.5 bg-[#0f62fe] hover:bg-[#0050e6] text-white font-bold text-xs uppercase tracking-wider transition-colors flex items-center justify-center space-x-2 disabled:opacity-50"
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

    <div class="mt-6 pt-4 border-t border-[#f4f4f4] text-center text-[11px] text-[#8c8c8c]">
      Default Seeder: <code class="bg-[#f4f4f4] px-1.5 py-0.5 text-[#161616]">root@precis.com / PrecisAdmin2026!</code>
    </div>
  </div>
</div>
