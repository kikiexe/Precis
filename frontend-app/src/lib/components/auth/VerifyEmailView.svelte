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

<div class="min-h-screen bg-[#fafafc] flex flex-col justify-center items-center p-4 sm:p-6 select-none font-sans">
  <div class="w-full max-w-md bg-white border border-[#e5e5ea] rounded-3xl p-6 sm:p-8 text-center shadow-sm">
    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-[#17171c] text-white font-bold text-lg mb-5 shadow-xs">
      P
    </div>

    {#if isLoading}
      <div class="py-8 space-y-3">
        <div class="w-8 h-8 border-2 border-[#17171c] border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-xs font-mono text-[#8e8e93]">Memverifikasi alamat email Anda...</p>
      </div>
    {:else if successMessage}
      <div class="w-12 h-12 bg-[#ecfdf5] text-[#059669] rounded-2xl flex items-center justify-center mx-auto mb-4 border border-[#a7f3d0]">
        <CheckCircle2 class="w-6 h-6" />
      </div>
      <h2 class="text-base font-bold text-[#17171c] mb-1.5">Verifikasi Sukses</h2>
      <p class="text-xs text-[#686873] mb-6 leading-relaxed">{successMessage}</p>
      <button
        type="button"
        onclick={onCompleted}
        class="w-full py-3 px-5 bg-[#17171c] hover:bg-black text-white font-semibold text-xs rounded-full flex items-center justify-center gap-2 transition-all cursor-pointer shadow-xs"
      >
        <span>Masuk ke Dashboard</span>
        <ArrowRight class="w-4 h-4" />
      </button>
    {:else}
      <div class="w-12 h-12 bg-[#fef2f2] text-[#dc2626] rounded-2xl flex items-center justify-center mx-auto mb-4 border border-[#fecaca]">
        <AlertCircle class="w-6 h-6" />
      </div>
      <h2 class="text-base font-bold text-[#17171c] mb-1.5">Verifikasi Gagal</h2>
      <p class="text-xs text-[#686873] mb-6 leading-relaxed">{errorMessage}</p>
      <button
        type="button"
        onclick={onCompleted}
        class="w-full py-3 px-5 bg-[#17171c] hover:bg-black text-white font-semibold text-xs rounded-full flex items-center justify-center gap-2 transition-all cursor-pointer shadow-xs"
      >
        <span>Kembali ke Halaman Login</span>
        <ArrowRight class="w-4 h-4" />
      </button>
    {/if}
  </div>

  <div class="mt-8 flex items-center gap-2 text-xs text-[#8e8e93] font-mono">
    <Shield class="w-4 h-4 text-[#8e8e93]" />
    <span>PRÉCIS Email Verification</span>
  </div>
</div>
