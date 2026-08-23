<script lang="ts">
  import { X, KeyRound, AlertCircle, CheckCircle2, Lock } from 'lucide-svelte';
  import { authService } from '../../services/auth-service';
  import { ApiError } from '../../services/api-client';

  interface Props {
    isOpen: boolean;
    initialEmail?: string;
    initialToken?: string;
    onClose: () => void;
    onSuccess: () => void;
  }

  let {
    isOpen,
    initialEmail = '',
    initialToken = '',
    onClose,
    onSuccess,
  }: Props = $props();

  let email = $state('');
  let token = $state('');
  let password = $state('');
  let passwordConfirmation = $state('');
  let isLoading = $state(false);
  let errorMessage = $state<string | null>(null);
  let successMessage = $state<string | null>(null);

  $effect(() => {
    if (isOpen) {
      email = initialEmail;
      token = initialToken;
    }
  });

  async function handleSubmit(event: SubmitEvent) {
    event.preventDefault();
    if (!email.trim() || !token.trim() || !password || !passwordConfirmation) {
      errorMessage = 'Seluruh kolom wajib diisi.';
      return;
    }

    if (password !== passwordConfirmation) {
      errorMessage = 'Konfirmasi kata sandi tidak cocok.';
      return;
    }

    if (password.length < 8) {
      errorMessage = 'Kata sandi minimal terdiri dari 8 karakter.';
      return;
    }

    isLoading = true;
    errorMessage = null;
    successMessage = null;

    try {
      const message = await authService.resetPassword(
        email.trim(),
        token.trim(),
        password,
        passwordConfirmation
      );
      successMessage = message;
      setTimeout(() => {
        onSuccess();
      }, 1500);
    } catch (err: unknown) {
      if (err instanceof ApiError) {
        errorMessage = err.message;
      } else if (err instanceof Error) {
        errorMessage = err.message;
      } else {
        errorMessage = 'Gagal mengatur ulang kata sandi.';
      }
    } finally {
      isLoading = false;
    }
  }

  function handleClose() {
    errorMessage = null;
    successMessage = null;
    password = '';
    passwordConfirmation = '';
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
          <KeyRound class="w-5 h-5" />
        </div>
        <h3 class="text-base font-bold text-[#161616] tracking-tight font-display">Atur Ulang Kata Sandi</h3>
        <p class="text-xs text-[#525252] mt-1 leading-relaxed">
          Masukkan token pemulihan yang dikirim via email beserta kata sandi baru Anda.
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
            <div class="font-bold">Pembaruan Berhasil</div>
            <div class="mt-0.5">{successMessage}</div>
          </div>
        </div>
      {/if}

      {#if !successMessage}
        <form onsubmit={handleSubmit} class="space-y-3.5">
          <div>
            <label for="reset-email" class="block text-xs font-semibold text-[#161616] mb-1">
              Alamat Email
            </label>
            <input
              id="reset-email"
              type="email"
              bind:value={email}
              required
              disabled={isLoading}
              class="w-full px-3 py-2 text-xs border border-[#e0e0e0] focus:border-[#0f62fe] focus:outline-hidden bg-[#f4f4f4] focus:bg-white transition-colors"
            />
          </div>

          <div>
            <label for="reset-token" class="block text-xs font-semibold text-[#161616] mb-1">
              Token Pemulihan (64 Karakter)
            </label>
            <input
              id="reset-token"
              type="text"
              bind:value={token}
              placeholder="Tempel token dari email..."
              required
              disabled={isLoading}
              class="w-full px-3 py-2 text-xs font-mono border border-[#e0e0e0] focus:border-[#0f62fe] focus:outline-hidden bg-[#f4f4f4] focus:bg-white transition-colors"
            />
          </div>

          <div>
            <label for="reset-password" class="block text-xs font-semibold text-[#161616] mb-1">
              Kata Sandi Baru (Min. 8 Karakter)
            </label>
            <div class="relative">
              <input
                id="reset-password"
                type="password"
                bind:value={password}
                required
                disabled={isLoading}
                class="w-full pl-3 pr-8 py-2 text-xs border border-[#e0e0e0] focus:border-[#0f62fe] focus:outline-hidden bg-[#f4f4f4] focus:bg-white transition-colors"
              />
              <Lock class="w-3.5 h-3.5 text-[#8c8c8c] absolute right-2.5 top-2.5" />
            </div>
          </div>

          <div>
            <label for="reset-password-confirmation" class="block text-xs font-semibold text-[#161616] mb-1">
              Konfirmasi Kata Sandi Baru
            </label>
            <div class="relative">
              <input
                id="reset-password-confirmation"
                type="password"
                bind:value={passwordConfirmation}
                required
                disabled={isLoading}
                class="w-full pl-3 pr-8 py-2 text-xs border border-[#e0e0e0] focus:border-[#0f62fe] focus:outline-hidden bg-[#f4f4f4] focus:bg-white transition-colors"
              />
              <Lock class="w-3.5 h-3.5 text-[#8c8c8c] absolute right-2.5 top-2.5" />
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3">
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
              class="px-4 py-2 text-xs font-bold text-white bg-[#0f62fe] hover:bg-[#0050e6] transition-colors cursor-pointer disabled:opacity-50"
            >
              {#if isLoading}
                <span>Menyimpan...</span>
              {:else}
                <span>Simpan Kata Sandi</span>
              {/if}
            </button>
          </div>
        </form>
      {/if}
    </div>
  </div>
{/if}
