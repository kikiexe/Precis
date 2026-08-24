<script lang="ts">
  import { onMount } from 'svelte';
  import { CheckCircle2, AlertCircle, ArrowRight, Shield } from 'lucide-svelte';
  import { authService } from '../../services/auth-service';
  import { ApiError } from '../../services/api-client';

  interface Props {
    token: string;
    onCompleted: () => void;
  }

  let { token, onCompleted }: Props = $props();

  let isLoading = $state(true);
  let errorMessage = $state<string | null>(null);
  let successMessage = $state<string | null>(null);

  onMount(() => {
    handleVerify();
  });

  async function handleVerify() {
    isLoading = true;
    errorMessage = null;

    try {
      await authService.verifyEmail(token);
      successMessage = 'Alamat email Anda berhasil diverifikasi. Akun Anda telah aktif sepenuhnya.';
    } catch (err: unknown) {
      if (err instanceof ApiError) {
        errorMessage = err.message;
      } else if (err instanceof Error) {
        errorMessage = err.message;
      } else {
        errorMessage = 'Gagal memverifikasi email.';
      }
    } finally {
      isLoading = false;
    }
  }
</script>

<div class="min-h-screen bg-[#eeece7]/40 flex flex-col justify-center items-center p-4 sm:p-6 select-none font-sans">
  <div class="w-full max-w-md bg-white border border-[#d9d9dd] rounded-3xl p-6 sm:p-8 text-center">
    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-[#17171c] text-white font-bold text-lg mb-4">
      P
    </div>

    {#if isLoading}
      <div class="py-8 space-y-3">
        <div class="w-7 h-7 border-2 border-[#17171c] border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-xs font-mono text-[#75758a]">Memverifikasi alamat email Anda...</p>
      </div>
    {:else if successMessage}
      <div class="w-12 h-12 bg-[#edfce9] text-[#003c33] rounded-full flex items-center justify-center mx-auto mb-3">
        <CheckCircle2 class="w-6 h-6" />
      </div>
      <h2 class="text-base font-semibold text-[#212121] mb-2">Verifikasi Sukses</h2>
      <p class="text-xs text-[#616161] mb-6">{successMessage}</p>
      <button
        type="button"
        onclick={onCompleted}
        class="w-full py-3 px-5 bg-[#17171c] hover:bg-black text-white font-medium text-xs rounded-full flex items-center justify-center gap-2 transition-all cursor-pointer shadow-none"
      >
        <span>Masuk ke Dashboard</span>
        <ArrowRight class="w-4 h-4" />
      </button>
    {:else}
      <div class="w-12 h-12 bg-[#ffefef] text-[#e5484d] rounded-full flex items-center justify-center mx-auto mb-3">
        <AlertCircle class="w-6 h-6" />
      </div>
      <h2 class="text-base font-semibold text-[#212121] mb-2">Verifikasi Gagal</h2>
      <p class="text-xs text-[#616161] mb-6">{errorMessage}</p>
      <button
        type="button"
        onclick={onCompleted}
        class="w-full py-3 px-5 bg-[#17171c] hover:bg-black text-white font-medium text-xs rounded-full flex items-center justify-center gap-2 transition-all cursor-pointer shadow-none"
      >
        <span>Kembali ke Halaman Login</span>
        <ArrowRight class="w-4 h-4" />
      </button>
    {/if}
  </div>

  <div class="mt-6 flex items-center gap-2 text-[11px] text-[#75758a] font-mono">
    <Shield class="w-3.5 h-3.5" />
    <span>PRÉCIS Email Verification</span>
  </div>
</div>
