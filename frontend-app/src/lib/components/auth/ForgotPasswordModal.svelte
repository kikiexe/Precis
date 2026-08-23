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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
    <div class="w-full max-w-md bg-white border border-[#e0e0e0] shadow-2xl p-6 relative animate-in zoom-in-95 duration-200">
      <button
        type="button"
        onclick={handleClose}
        class="absolute top-4 right-4 text-[#8c8c8c] hover:text-[#161616] transition-colors p-1"
        aria-label="Tutup modal"
      >
        <X class="w-5 h-5" />
      </button>

      <div class="mb-5">
        <div class="w-10 h-10 bg-[#0f62fe]/10 text-[#0f62fe] flex items-center justify-center mb-3">
          <Mail class="w-5 h-5" />
        </div>
        <h3 class="text-base font-bold text-[#161616] tracking-tight font-display">Pemulihan Kata Sandi</h3>
        <p class="text-xs text-[#525252] mt-1 leading-relaxed">
          Masukkan alamat email akun Anda. Kami akan mengirimkan tautan pemulihan untuk mengatur ulang kata sandi.
        </p>
      </div>

      {#if errorMessage}
        <div class="mb-4 p-3 bg-[#da1e28]/10 border-l-4 border-[#da1e28] text-xs text-[#da1e28] flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      {#if successMessage}
        <div class="mb-4 p-3 bg-[#24a148]/10 border-l-4 border-[#24a148] text-xs text-[#24a148] flex items-start gap-2">
          <CheckCircle2 class="w-4 h-4 shrink-0 mt-0.5" />
          <div>
            <div class="font-bold">Permohonan Berhasil</div>
            <div class="mt-0.5">{successMessage}</div>
          </div>
        </div>
      {/if}

      {#if !successMessage}
        <form onsubmit={handleSubmit} class="space-y-4">
          <div>
            <label for="forgot-email" class="block text-xs font-semibold text-[#161616] mb-1.5">
              Alamat Email Terdaftar
            </label>
            <input
              id="forgot-email"
              type="email"
              bind:value={email}
              placeholder="nama@perusahaan.com"
              required
              disabled={isLoading}
              class="w-full px-3 py-2 text-xs border border-[#e0e0e0] focus:border-[#0f62fe] focus:outline-hidden bg-[#f4f4f4] focus:bg-white transition-colors"
            />
          </div>

          <div class="flex items-center justify-end gap-2 pt-2">
            <button
              type="button"
              onclick={handleClose}
              disabled={isLoading}
              class="px-4 py-2 text-xs font-semibold text-[#525252] hover:bg-[#f4f4f4] transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={isLoading}
              class="px-4 py-2 text-xs font-bold text-white bg-[#0f62fe] hover:bg-[#0050e6] transition-colors flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
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
            class="px-4 py-2 text-xs font-bold text-white bg-[#161616] hover:bg-black transition-colors cursor-pointer"
          >
            Selesai
          </button>
        </div>
      {/if}
    </div>
  </div>
{/if}
