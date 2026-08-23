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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#17171c]/40 backdrop-blur-xs animate-in fade-in duration-200">
    <div class="w-full max-w-md bg-white border border-[#d9d9dd] rounded-[22px] p-6 relative animate-in zoom-in-95 duration-200 font-sans shadow-none">
      <button
        type="button"
        onclick={handleClose}
        class="absolute top-5 right-5 text-[#93939f] hover:text-[#212121] transition-colors p-1 cursor-pointer"
        aria-label="Tutup modal"
      >
        <X class="w-5 h-5" />
      </button>

      <div class="mb-5">
        <div class="w-10 h-10 bg-[#eeece7] text-[#17171c] rounded-[12px] flex items-center justify-center mb-3.5">
          <Mail class="w-5 h-5" />
        </div>
        <h3 class="text-base font-medium text-[#212121] tracking-tight">Pemulihan Kata Sandi</h3>
        <p class="text-xs text-[#616161] mt-1 leading-relaxed font-normal">
          Masukkan alamat email akun Anda. Kami akan mengirimkan tautan pemulihan untuk mengatur ulang kata sandi.
        </p>
      </div>

      {#if errorMessage}
        <div class="mb-4 p-3.5 bg-[#ffad9b]/15 border border-[#ffad9b] rounded-[12px] text-xs text-[#b30000] flex items-start gap-2.5">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      {#if successMessage}
        <div class="mb-4 p-3.5 bg-[#edfce9] border border-[#edfce9] rounded-[12px] text-xs text-[#003c33] flex items-start gap-2.5">
          <CheckCircle2 class="w-4 h-4 shrink-0 mt-0.5 text-[#003c33]" />
          <div>
            <div class="font-medium">Permohonan Berhasil</div>
            <div class="mt-0.5 text-xs text-[#616161]">{successMessage}</div>
          </div>
        </div>
      {/if}

      {#if !successMessage}
        <form onsubmit={handleSubmit} class="space-y-4">
          <div>
            <label for="forgot-email" class="block text-xs font-medium text-[#212121] mb-1.5">
              Alamat Email Terdaftar
            </label>
            <input
              id="forgot-email"
              type="email"
              bind:value={email}
              placeholder="nama@perusahaan.com"
              required
              disabled={isLoading}
              class="w-full px-3.5 py-2.5 text-xs text-[#212121] placeholder-[#93939f] border border-[#d9d9dd] rounded-[12px] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden bg-white transition-all"
            />
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-2">
            <button
              type="button"
              onclick={handleClose}
              disabled={isLoading}
              class="px-4 py-2.5 text-xs font-medium text-[#616161] hover:text-[#212121] transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={isLoading}
              class="px-5 py-2.5 text-xs font-medium text-white bg-[#17171c] hover:bg-[#000000] rounded-full transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            >
              {#if isLoading}
                <span>Mengirim...</span>
              {:else}
                <span>Kirim Tautan</span>
                <ArrowRight class="w-3.5 h-3.5" />
              {/if}
            </button>
          </div>
        </form>
      {:else}
        <div class="pt-2 flex justify-end">
          <button
            type="button"
            onclick={handleClose}
            class="px-5 py-2.5 text-xs font-medium text-white bg-[#17171c] hover:bg-[#000000] rounded-full transition-all cursor-pointer"
          >
            Selesai
          </button>
        </div>
      {/if}
    </div>
  </div>
{/if}
