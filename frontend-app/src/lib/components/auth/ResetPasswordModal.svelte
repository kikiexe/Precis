<script lang="ts">
  import { X, KeyRound, AlertCircle, CheckCircle2, Eye, EyeOff } from 'lucide-svelte';
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
  let showPassword = $state(false);
  let showPasswordConfirmation = $state(false);
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
    showPassword = false;
    showPasswordConfirmation = false;
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
          <KeyRound class="w-5 h-5" />
        </div>
        <div>
          <h3 class="text-base font-bold text-[#17171c] tracking-tight">Atur Ulang Kata Sandi</h3>
          <p class="text-xs text-[#8e8e93]">Perbarui kata sandi akun Anda</p>
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
            <div class="font-bold">Kata Sandi Berhasil Diperbarui</div>
            <div class="mt-1 text-xs text-[#065f46]">{successMessage}</div>
          </div>
        </div>
      {/if}

      {#if !successMessage}
        <form onsubmit={handleSubmit} class="space-y-4 text-xs">
          <div class="space-y-1.5">
            <label for="reset-email" class="block font-bold text-[#17171c]">
              Alamat Email
            </label>
            <input
              id="reset-email"
              type="email"
              bind:value={email}
              required
              class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-xs text-[#17171c] font-mono focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
            />
          </div>

          <div class="space-y-1.5">
            <label for="reset-token" class="block font-bold text-[#17171c]">
              Kode Token Pemulihan
            </label>
            <input
              id="reset-token"
              type="text"
              bind:value={token}
              required
              placeholder="Masukkan token dari email"
              class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-xs text-[#17171c] font-mono focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
            />
          </div>

          <div class="space-y-1.5">
            <label for="reset-pwd" class="block font-bold text-[#17171c]">
              Kata Sandi Baru
            </label>
            <div class="relative">
              <input
                id="reset-pwd"
                type={showPassword ? 'text' : 'password'}
                bind:value={password}
                required
                placeholder="Minimal 8 karakter"
                class="w-full px-4 pr-10 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
              />
              <button
                type="button"
                onclick={() => (showPassword = !showPassword)}
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#8e8e93] hover:text-[#17171c] p-1 cursor-pointer"
              >
                {#if showPassword}
                  <EyeOff class="w-4 h-4" />
                {:else}
                  <Eye class="w-4 h-4" />
                {/if}
              </button>
            </div>
          </div>

          <div class="space-y-1.5">
            <label for="reset-pwd-conf" class="block font-bold text-[#17171c]">
              Ulangi Kata Sandi Baru
            </label>
            <div class="relative">
              <input
                id="reset-pwd-conf"
                type={showPasswordConfirmation ? 'text' : 'password'}
                bind:value={passwordConfirmation}
                required
                placeholder="Ulangi kata sandi baru"
                class="w-full px-4 pr-10 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
              />
              <button
                type="button"
                onclick={() => (showPasswordConfirmation = !showPasswordConfirmation)}
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#8e8e93] hover:text-[#17171c] p-1 cursor-pointer"
              >
                {#if showPasswordConfirmation}
                  <EyeOff class="w-4 h-4" />
                {:else}
                  <Eye class="w-4 h-4" />
                {/if}
              </button>
            </div>
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
              disabled={isLoading || !password || !passwordConfirmation}
              class="flex-1 py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full flex items-center justify-center gap-2 cursor-pointer transition-all disabled:opacity-50 shadow-xs"
            >
              {#if isLoading}
                <span>Menyimpan...</span>
              {:else}
                <span>Perbarui Sandi</span>
              {/if}
            </button>
          </div>
        </form>
      {/if}
    </div>
  </div>
{/if}
