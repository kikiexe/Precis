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
  <div class="fixed inset-0 z-50 bg-[#17171c]/60 backdrop-blur-xs flex items-center justify-center p-4 select-none animate-in fade-in font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] max-w-sm w-full p-6 shadow-none animate-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5 mb-4">
        <div class="flex items-center gap-2 text-[#b30000]">
          <ShieldAlert class="w-5 h-5" />
          <h2 class="text-sm font-medium text-[#212121]">Master Lock Kiosk</h2>
        </div>
        <button
          type="button"
          onclick={onClose}
          disabled={isLoading}
          class="text-[#93939f] hover:text-[#212121] cursor-pointer p-1"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <p class="text-xs text-[#616161] mb-4 leading-relaxed font-normal">
        Terminal ini terikat ke outlet ini. Masukkan email dan kata sandi akun Owner/Admin untuk membuka pengaturan sistem kasir.
      </p>

      {#if errorMessage}
        <div class="mb-3.5 p-3 bg-[#ffad9b]/15 border border-[#ffad9b] rounded-[12px] text-xs text-[#b30000] flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <form onsubmit={handleVerify} class="space-y-3.5">
        <div>
          <label for="owner-email" class="block text-xs font-medium text-[#212121] mb-1.5">
            Email Owner/Admin:
          </label>
          <div class="relative">
            <input
              id="owner-email"
              type="email"
              bind:value={emailInput}
              required
              disabled={isLoading}
              class="w-full pl-9 pr-3 py-2 text-xs border border-[#d9d9dd] rounded-[12px] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden bg-white text-[#212121] transition-all"
            />
            <Mail class="w-3.5 h-3.5 text-[#93939f] absolute left-3 top-2.5" />
          </div>
        </div>

        <div>
          <label for="owner-password" class="block text-xs font-medium text-[#212121] mb-1.5">
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
              class="w-full pl-9 pr-9 py-2 text-xs border border-[#d9d9dd] rounded-[12px] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden bg-white text-[#212121] transition-all"
            />
            <Lock class="w-3.5 h-3.5 text-[#93939f] absolute left-3 top-1/2 -translate-y-1/2" />
            <button
              type="button"
              tabindex="-1"
              onclick={() => (showPassword = !showPassword)}
              class="absolute right-3 top-1/2 -translate-y-1/2 text-[#93939f] hover:text-[#212121] transition-colors p-1 cursor-pointer flex items-center justify-center"
              aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
            >
              {#if showPassword}
                <EyeOff class="w-4 h-4" />
              {:else}
                <Eye class="w-4 h-4" />
              {/if}
            </button>
          </div>
        </div>

        <div class="pt-3 flex gap-2.5 border-t border-[#d9d9dd]">
          <button
            type="button"
            onclick={onClose}
            disabled={isLoading}
            class="flex-1 py-2.5 bg-white text-[#616161] hover:bg-[#eeece7]/40 text-xs font-medium border border-[#d9d9dd] rounded-full cursor-pointer transition-all"
          >
            Batal
          </button>
          <button
            type="submit"
            disabled={isLoading}
            class="flex-2 py-2.5 bg-[#17171c] hover:bg-[#000000] text-white text-xs font-medium rounded-full flex items-center justify-center gap-1.5 cursor-pointer transition-all shadow-none disabled:opacity-50"
          >
            {#if isLoading}
              <span>Memverifikasi...</span>
            {:else}
              <Check class="w-4 h-4" />
              <span>Buka Otorisasi</span>
            {/if}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}
