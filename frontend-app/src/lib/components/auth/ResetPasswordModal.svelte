<script lang="ts">
  import { X, KeyRound, AlertCircle, CheckCircle2, Lock, Eye, EyeOff } from 'lucide-svelte';
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
        <div class="w-10 h-10 bg-[#eeece7] text-[#17171c] rounded-xl flex items-center justify-center mb-3.5">
          <KeyRound class="w-5 h-5" />
        </div>
        <h3 class="text-base font-medium text-[#212121] tracking-tight">Atur Ulang Kata Sandi</h3>
        <p class="text-xs text-[#616161] mt-1 leading-relaxed font-normal">
          Masukkan token pemulihan yang dikirim via email beserta kata sandi baru Anda.
        </p>
      </div>

      {#if errorMessage}
        <div class="mb-4 p-3.5 bg-[#ffad9b]/15 border border-[#ffad9b] rounded-xl text-xs text-[#b30000] flex items-start gap-2.5">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      {#if successMessage}
        <div class="mb-4 p-3.5 bg-[#edfce9] border border-[#edfce9] rounded-xl text-xs text-[#003c33] flex items-start gap-2.5">
          <CheckCircle2 class="w-4 h-4 shrink-0 mt-0.5 text-[#003c33]" />
          <div>
            <div class="font-medium">Pembaruan Berhasil</div>
            <div class="mt-0.5 text-xs text-[#616161]">{successMessage}</div>
          </div>
        </div>
      {/if}

      {#if !successMessage}
        <form onsubmit={handleSubmit} class="space-y-3.5">
          <div>
            <label for="reset-email" class="block text-xs font-medium text-[#212121] mb-1.5">
              Alamat Email
            </label>
            <input
              id="reset-email"
              type="email"
              bind:value={email}
              required
              disabled={isLoading}
              class="w-full px-3.5 py-2.5 text-xs text-[#212121] placeholder-[#93939f] border border-[#d9d9dd] rounded-xl focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden bg-white transition-all"
            />
          </div>

          <div>
            <label for="reset-token" class="block text-xs font-medium text-[#212121] mb-1.5">
              Token Pemulihan (64 Karakter)
            </label>
            <input
              id="reset-token"
              type="text"
              bind:value={token}
              placeholder="Tempel token dari email..."
              required
              disabled={isLoading}
              class="w-full px-3.5 py-2.5 text-xs font-mono text-[#212121] placeholder-[#93939f] border border-[#d9d9dd] rounded-xl focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden bg-white transition-all"
            />
          </div>

          <div>
            <label for="reset-password" class="block text-xs font-medium text-[#212121] mb-1.5">
              Kata Sandi Baru (Min. 8 Karakter)
            </label>
            <div class="relative">
              <input
                id="reset-password"
                type={showPassword ? 'text' : 'password'}
                bind:value={password}
                required
                disabled={isLoading}
                class="w-full pl-9 pr-10 py-2.5 text-xs text-[#212121] placeholder-[#93939f] border border-[#d9d9dd] rounded-xl focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden bg-white transition-all"
              />
              <Lock class="w-4 h-4 text-[#93939f] absolute left-3 top-1/2 -translate-y-1/2" />
              <button
                type="button"
                tabindex="-1"
                onclick={() => (showPassword = !showPassword)}
                class="absolute right-3 top-1/2 -translate-y-1/2 text-[#93939f] hover:text-[#212121] transition-colors p-1 cursor-pointer flex items-center justify-center"
                aria-label={showPassword ? 'Sembunyikan kata sandi baru' : 'Tampilkan kata sandi baru'}
              >
                {#if showPassword}
                  <EyeOff class="w-4 h-4" />
                {:else}
                  <Eye class="w-4 h-4" />
                {/if}
              </button>
            </div>
          </div>

          <div>
            <label for="reset-password-confirmation" class="block text-xs font-medium text-[#212121] mb-1.5">
              Konfirmasi Kata Sandi Baru
            </label>
            <div class="relative">
              <input
                id="reset-password-confirmation"
                type={showPasswordConfirmation ? 'text' : 'password'}
                bind:value={passwordConfirmation}
                required
                disabled={isLoading}
                class="w-full pl-9 pr-10 py-2.5 text-xs text-[#212121] placeholder-[#93939f] border border-[#d9d9dd] rounded-xl focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden bg-white transition-all"
              />
              <Lock class="w-4 h-4 text-[#93939f] absolute left-3 top-1/2 -translate-y-1/2" />
              <button
                type="button"
                tabindex="-1"
                onclick={() => (showPasswordConfirmation = !showPasswordConfirmation)}
                class="absolute right-3 top-1/2 -translate-y-1/2 text-[#93939f] hover:text-[#212121] transition-colors p-1 cursor-pointer flex items-center justify-center"
                aria-label={showPasswordConfirmation ? 'Sembunyikan konfirmasi kata sandi' : 'Tampilkan konfirmasi kata sandi'}
              >
                {#if showPasswordConfirmation}
                  <EyeOff class="w-4 h-4" />
                {:else}
                  <Eye class="w-4 h-4" />
                {/if}
              </button>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-3">
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
              class="px-5 py-2.5 text-xs font-medium text-white bg-[#17171c] hover:bg-[#000000] rounded-full transition-all cursor-pointer disabled:opacity-50"
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
