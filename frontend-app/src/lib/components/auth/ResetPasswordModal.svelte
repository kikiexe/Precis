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

  let { isOpen, initialEmail = '', initialToken = '', onClose, onSuccess }: Props = $props();

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
  <div
    class="animate-in fade-in fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 font-sans backdrop-blur-xs duration-200"
  >
    <div
      class="animate-in zoom-in-95 relative w-full max-w-md rounded-3xl border border-[#e5e5ea] bg-white p-6 shadow-xl duration-200 sm:p-7"
    >
      <button
        type="button"
        onclick={handleClose}
        class="absolute top-5 right-5 cursor-pointer rounded-xl p-2 text-[#8e8e93] transition-colors hover:bg-[#f4f4f6] hover:text-[#17171c]"
        aria-label="Tutup modal"
      >
        <X class="h-5 w-5" />
      </button>

      <div class="mb-5 flex items-center gap-3 border-b border-[#f2f2f4] pb-3">
        <div
          class="flex h-10 w-10 items-center justify-center rounded-2xl border border-[#bfdbfe] bg-[#eff6ff] text-[#2563eb]"
        >
          <KeyRound class="h-5 w-5" />
        </div>
        <div>
          <h3 class="text-base font-bold tracking-tight text-[#17171c]">Atur Ulang Kata Sandi</h3>
          <p class="text-xs text-[#8e8e93]">Perbarui kata sandi akun Anda</p>
        </div>
      </div>

      {#if errorMessage}
        <div
          class="mb-4 flex items-start gap-2.5 rounded-2xl border border-[#fecaca] bg-[#fef2f2] p-3.5 text-xs font-medium text-[#991b1b]"
        >
          <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      {#if successMessage}
        <div
          class="mb-4 flex items-start gap-2.5 rounded-2xl border border-[#a7f3d0] bg-[#ecfdf5] p-4 text-xs text-[#065f46]"
        >
          <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-[#059669]" />
          <div>
            <div class="font-bold">Kata Sandi Berhasil Diperbarui</div>
            <div class="mt-1 text-xs text-[#065f46]">{successMessage}</div>
          </div>
        </div>
      {/if}

      {#if !successMessage}
        <form onsubmit={handleSubmit} class="space-y-4 text-xs">
          <div class="space-y-1.5">
            <label for="reset-email" class="block font-bold text-[#17171c]"> Alamat Email </label>
            <input
              id="reset-email"
              type="email"
              bind:value={email}
              required
              class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 font-mono text-xs text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
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
              class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 font-mono text-xs text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
            />
          </div>

          <div class="space-y-1.5">
            <label for="reset-pwd" class="block font-bold text-[#17171c]"> Kata Sandi Baru </label>
            <div class="relative">
              <input
                id="reset-pwd"
                type={showPassword ? 'text' : 'password'}
                bind:value={password}
                required
                placeholder="Minimal 8 karakter"
                class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 pr-10 text-xs text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
              />
              <button
                type="button"
                onclick={() => (showPassword = !showPassword)}
                class="absolute top-1/2 right-3.5 -translate-y-1/2 cursor-pointer p-1 text-[#8e8e93] hover:text-[#17171c]"
              >
                {#if showPassword}
                  <EyeOff class="h-4 w-4" />
                {:else}
                  <Eye class="h-4 w-4" />
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
                class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 pr-10 text-xs text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
              />
              <button
                type="button"
                onclick={() => (showPasswordConfirmation = !showPasswordConfirmation)}
                class="absolute top-1/2 right-3.5 -translate-y-1/2 cursor-pointer p-1 text-[#8e8e93] hover:text-[#17171c]"
              >
                {#if showPasswordConfirmation}
                  <EyeOff class="h-4 w-4" />
                {:else}
                  <Eye class="h-4 w-4" />
                {/if}
              </button>
            </div>
          </div>

          <div class="flex items-center gap-3 pt-2">
            <button
              type="button"
              onclick={handleClose}
              class="flex-1 cursor-pointer rounded-full border border-[#e5e5ea] py-3 text-xs font-semibold text-[#686873] transition-all hover:bg-[#f4f4f6]"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={isLoading || !password || !passwordConfirmation}
              class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
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
