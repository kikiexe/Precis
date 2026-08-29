<script lang="ts">
  import { ShieldAlert, X, Check, Lock, Mail, AlertCircle, Eye, EyeOff } from 'lucide-svelte';
  import { posService } from '../../services/pos-service';
  import type { MasterUnlockResult } from '../../types/pos';

  interface Props {
    isOpen: boolean;
    onClose: () => void;
    onUnlock: (result: MasterUnlockResult) => void;
  }

  let { isOpen = false, onClose, onUnlock }: Props = $props();

  let emailInput = $state('');
  let passwordInput = $state('');
  let showPassword = $state(false);
  let isLoading = $state(false);
  let errorMessage = $state<string | null>(null);

  $effect(() => {
    if (isOpen) {
      passwordInput = '';
      showPassword = false;
      errorMessage = null;
    }
  });

  async function handleVerify(event?: SubmitEvent) {
    if (event) event.preventDefault();
    if (!emailInput.trim() || !passwordInput) {
      errorMessage = 'Email dan kata sandi Owner wajib diisi.';
      return;
    }

    isLoading = true;
    errorMessage = null;

    try {
      const result = await posService.masterUnlock(emailInput.trim(), passwordInput);
      onUnlock(result);
      onClose();
    } catch (err: unknown) {
      if (err instanceof Error) {
        errorMessage = err.message;
      } else {
        errorMessage = 'Kata sandi master salah atau akun bukan Owner/Admin outlet ini.';
      }
      passwordInput = '';
    } finally {
      isLoading = false;
    }
  }
</script>

{#if isOpen}
  <div
    class="animate-in fade-in fixed inset-0 z-50 flex items-center justify-center bg-[#17171c]/60 p-4 font-sans backdrop-blur-xs select-none"
  >
    <div
      class="animate-in zoom-in-95 w-full max-w-sm rounded-[22px] border border-[#d9d9dd] bg-white p-6 shadow-none"
    >
      <div class="mb-4 flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <div class="flex items-center gap-2 text-[#b30000]">
          <ShieldAlert class="size-5" />
          <h2 class="text-sm font-medium text-[#212121]">Master Lock Kiosk</h2>
        </div>
        <button
          type="button"
          onclick={onClose}
          disabled={isLoading}
          class="cursor-pointer p-1 text-[#93939f] hover:text-[#212121]"
        >
          <X class="size-4" />
        </button>
      </div>

      <p class="mb-4 text-xs leading-relaxed font-normal text-[#616161]">
        Terminal ini terikat ke outlet ini. Masukkan email dan kata sandi akun Owner/Admin untuk
        membuka pengaturan sistem kasir.
      </p>

      {#if errorMessage}
        <div
          class="mb-3.5 flex items-start gap-2 rounded-xl border border-[#ffad9b] bg-[#ffad9b]/15 p-3 text-xs text-[#b30000]"
        >
          <AlertCircle class="mt-0.5 size-4 shrink-0" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <form onsubmit={handleVerify} class="space-y-3.5">
        <div>
          <label for="owner-email" class="mb-1.5 block text-xs font-medium text-[#212121]">
            Email Owner/Admin:
          </label>
          <div class="relative">
            <input
              id="owner-email"
              type="email"
              bind:value={emailInput}
              required
              disabled={isLoading}
              class="w-full rounded-xl border border-[#d9d9dd] bg-white py-2 pr-3 pl-9 text-xs text-[#212121] transition-all focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
            />
            <Mail class="absolute top-2.5 left-3 size-3.5 text-[#93939f]" />
          </div>
        </div>

        <div>
          <label for="owner-password" class="mb-1.5 block text-xs font-medium text-[#212121]">
            Kata Sandi Owner:
          </label>
          <div class="relative">
            <input
              id="owner-password"
              type={showPassword ? 'text' : 'password'}
              bind:value={passwordInput}
              placeholder="••••••••"
              required
              disabled={isLoading}
              class="w-full rounded-xl border border-[#d9d9dd] bg-white px-9 py-2 text-xs text-[#212121] transition-all focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
            />
            <Lock class="absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-[#93939f]" />
            <button
              type="button"
              tabindex="-1"
              onclick={() => (showPassword = !showPassword)}
              class="absolute top-1/2 right-3 flex -translate-y-1/2 cursor-pointer items-center justify-center p-1 text-[#93939f] transition-colors hover:text-[#212121]"
              aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
            >
              {#if showPassword}
                <EyeOff class="size-4" />
              {:else}
                <Eye class="size-4" />
              {/if}
            </button>
          </div>
        </div>

        <div class="flex gap-2.5 border-t border-[#d9d9dd] pt-3">
          <button
            type="button"
            onclick={onClose}
            disabled={isLoading}
            class="flex-1 cursor-pointer rounded-full border border-[#d9d9dd] bg-white py-2.5 text-xs font-medium text-[#616161] transition-all hover:bg-[#eeece7]/40"
          >
            Batal
          </button>
          <button
            type="submit"
            disabled={isLoading}
            class="flex flex-2 cursor-pointer items-center justify-center gap-1.5 rounded-full bg-[#17171c] py-2.5 text-xs font-medium text-white shadow-none transition-all hover:bg-[#000000] disabled:opacity-50"
          >
            {#if isLoading}
              <span>Memverifikasi...</span>
            {:else}
              <Check class="size-4" />
              <span>Buka Otorisasi</span>
            {/if}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}
