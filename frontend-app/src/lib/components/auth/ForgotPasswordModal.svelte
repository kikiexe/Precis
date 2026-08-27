<script lang="ts">
  import { X, Mail, AlertCircle, CheckCircle2, ArrowRight } from 'lucide-svelte';
  import { authService } from '../../services/auth-service';
  import { ApiError } from '../../services/api-client';

  interface Props {
    isOpen: boolean;
    onClose: () => void;
  }

  let { isOpen, onClose }: Props = $props();

  let email = $state('');
  let isLoading = $state(false);
  let errorMessage = $state<string | null>(null);
  let successMessage = $state<string | null>(null);

  async function handleSubmit(event: SubmitEvent) {
    event.preventDefault();
    if (!email.trim()) {
      errorMessage = 'Silakan masukkan alamat email terdaftar.';
      return;
    }

    isLoading = true;
    errorMessage = null;
    successMessage = null;

    try {
      const message = await authService.forgotPassword(email.trim());
      successMessage = message;
    } catch (err: unknown) {
      if (err instanceof ApiError) {
        errorMessage = err.message;
      } else if (err instanceof Error) {
        errorMessage = err.message;
      } else {
        errorMessage = 'Terjadi kesalahan saat memproses permintaan.';
      }
    } finally {
      isLoading = false;
    }
  }

  function handleClose() {
    errorMessage = null;
    successMessage = null;
    email = '';
    onClose();
  }
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans animate-in fade-in duration-200">
    <div class="w-full max-w-md bg-white border border-[#e5e5ea] rounded-3xl p-6 sm:p-7 relative animate-in zoom-in-95 duration-200 shadow-xl">
      <button
        type="button"
        onclick={handleClose}
        class="absolute top-5 right-5 p-2 text-[#8e8e93] hover:text-[#17171c] hover:bg-[#f4f4f6] rounded-xl transition-colors cursor-pointer"
        aria-label="Tutup modal"
      >
        <X class="w-5 h-5" />
      </button>

      <div class="flex items-center gap-3 mb-5 pb-3 border-b border-[#f2f2f4]">
        <div class="w-10 h-10 bg-[#eff6ff] text-[#2563eb] rounded-2xl flex items-center justify-center border border-[#bfdbfe]">
          <Mail class="w-5 h-5" />
        </div>
        <div>
          <h3 class="text-base font-bold text-[#17171c] tracking-tight">Pemulihan Kata Sandi</h3>
          <p class="text-xs text-[#8e8e93]">Kirim instruksi reset ke email terdaftar</p>
        </div>
      </div>

      {#if errorMessage}
        <div class="mb-4 p-3.5 bg-[#fef2f2] border border-[#fecaca] rounded-2xl text-xs font-medium text-[#991b1b] flex items-start gap-2.5">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      {#if successMessage}
        <div class="mb-4 p-4 bg-[#ecfdf5] border border-[#a7f3d0] rounded-2xl text-xs text-[#065f46] flex items-start gap-2.5">
          <CheckCircle2 class="w-5 h-5 shrink-0 mt-0.5 text-[#059669]" />
          <div>
            <div class="font-bold">Permohonan Berhasil Dikirim</div>
            <div class="mt-1 text-xs text-[#065f46]">{successMessage}</div>
          </div>
        </div>
      {/if}

      {#if !successMessage}
        <form onsubmit={handleSubmit} class="space-y-4 text-xs">
          <div class="space-y-1.5">
            <label for="forgot-email" class="block font-bold text-[#17171c]">
              Alamat Email Terdaftar
            </label>
            <input
              id="forgot-email"
              type="email"
              bind:value={email}
              placeholder="nama@email.com"
              required
              class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
            />
          </div>

          <div class="flex items-center gap-3 pt-2">
            <button
              type="button"
              onclick={handleClose}
              class="flex-1 py-3 text-xs font-semibold border border-[#e5e5ea] hover:bg-[#f4f4f6] text-[#686873] rounded-full cursor-pointer transition-all"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={isLoading || !email.trim()}
              class="flex-1 py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full flex items-center justify-center gap-2 cursor-pointer transition-all disabled:opacity-50 shadow-xs"
            >
              {#if isLoading}
                <span>Mengirim...</span>
              {:else}
                <span>Kirim Tautan</span>
                <ArrowRight class="w-4 h-4" />
              {/if}
            </button>
          </div>
        </form>
      {:else}
        <div class="pt-2">
          <button
            type="button"
            onclick={handleClose}
            class="w-full py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full cursor-pointer transition-all shadow-xs"
          >
            Tutup
          </button>
        </div>
      {/if}
    </div>
  </div>
{/if}
