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

<div
  class="flex min-h-screen flex-col items-center justify-center bg-[#fafafc] p-4 font-sans select-none sm:p-6"
>
  <div
    class="w-full max-w-md rounded-3xl border border-[#e5e5ea] bg-white p-6 text-center shadow-sm sm:p-8"
  >
    <div
      class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-[#17171c] text-lg font-bold text-white shadow-xs"
    >
      P
    </div>

    {#if isLoading}
      <div class="space-y-3 py-8">
        <div
          class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-[#17171c] border-t-transparent"
        ></div>
        <p class="font-mono text-xs text-[#8e8e93]">Memverifikasi alamat email Anda...</p>
      </div>
    {:else if successMessage}
      <div
        class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl border border-[#a7f3d0] bg-[#ecfdf5] text-[#059669]"
      >
        <CheckCircle2 class="h-6 w-6" />
      </div>
      <h2 class="mb-1.5 text-base font-bold text-[#17171c]">Verifikasi Sukses</h2>
      <p class="mb-6 text-xs leading-relaxed text-[#686873]">{successMessage}</p>
      <button
        type="button"
        onclick={onCompleted}
        class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] px-5 py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
      >
        <span>Masuk ke Dashboard</span>
        <ArrowRight class="h-4 w-4" />
      </button>
    {:else}
      <div
        class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl border border-[#fecaca] bg-[#fef2f2] text-[#dc2626]"
      >
        <AlertCircle class="h-6 w-6" />
      </div>
      <h2 class="mb-1.5 text-base font-bold text-[#17171c]">Verifikasi Gagal</h2>
      <p class="mb-6 text-xs leading-relaxed text-[#686873]">{errorMessage}</p>
      <button
        type="button"
        onclick={onCompleted}
        class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] px-5 py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
      >
        <span>Kembali ke Halaman Login</span>
        <ArrowRight class="h-4 w-4" />
      </button>
    {/if}
  </div>

  <div class="mt-8 flex items-center gap-2 font-mono text-xs text-[#8e8e93]">
    <Shield class="h-4 w-4 text-[#8e8e93]" />
    <span>PRÉCIS Email Verification</span>
  </div>
</div>
