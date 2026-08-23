<script lang="ts">
  import { ShieldAlert, X, Check, Lock, Mail, AlertCircle } from 'lucide-svelte';
  import { posService } from '../../services/pos-service';
  import type { MasterUnlockResult } from '../../types/pos';

  interface Props {
    isOpen: boolean;
    onClose: () => void;
    onUnlock: (result: MasterUnlockResult) => void;
  }

  let { isOpen = false, onClose, onUnlock }: Props = $props();

  let emailInput = $state('arief.owner@precis.test');
  let passwordInput = $state('');
  let isLoading = $state(false);
  let errorMessage = $state<string | null>(null);

  $effect(() => {
    if (isOpen) {
      passwordInput = '';
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
  <div class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4 select-none animate-in fade-in">
    <div class="bg-white border border-[#e0e0e0] max-w-sm w-full p-6 shadow-2xl animate-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3 mb-4">
        <div class="flex items-center gap-2 text-[#da1e28]">
          <ShieldAlert class="w-5 h-5" />
          <h2 class="text-sm font-bold text-[#161616]">Master Lock Kiosk</h2>
        </div>
        <button
          type="button"
          onclick={onClose}
          disabled={isLoading}
          class="text-[#8c8c8c] hover:text-[#161616] cursor-pointer"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <p class="text-xs text-[#525252] mb-4 leading-relaxed">
        Terminal ini terikat ke outlet ini. Masukkan email dan kata sandi akun Owner/Admin untuk membuka pengaturan sistem kasir.
      </p>

      {#if errorMessage}
        <div class="mb-3 p-2.5 bg-[#da1e28]/10 border-l-4 border-[#da1e28] text-xs text-[#da1e28] flex items-start gap-1.5">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <form onsubmit={handleVerify} class="space-y-3">
        <div>
          <label for="owner-email" class="block text-xs font-mono text-[#525252] mb-1">
            Email Owner/Admin:
          </label>
          <div class="relative">
            <input
              id="owner-email"
              type="email"
              bind:value={emailInput}
              required
              disabled={isLoading}
              class="w-full pl-8 pr-2.5 py-2 text-xs border border-[#e0e0e0] focus:border-[#0f62fe] focus:outline-hidden bg-[#f4f4f4] focus:bg-white transition-colors"
            />
            <Mail class="w-3.5 h-3.5 text-[#8c8c8c] absolute left-2.5 top-2.5" />
          </div>
        </div>

        <div>
          <label for="owner-password" class="block text-xs font-mono text-[#525252] mb-1">
            Kata Sandi Owner:
          </label>
          <div class="relative">
            <input
              id="owner-password"
              type="password"
              bind:value={passwordInput}
              placeholder="••••••••"
              required
              disabled={isLoading}
              class="w-full pl-8 pr-2.5 py-2 text-xs border border-[#e0e0e0] focus:border-[#0f62fe] focus:outline-hidden bg-[#f4f4f4] focus:bg-white transition-colors"
            />
            <Lock class="w-3.5 h-3.5 text-[#8c8c8c] absolute left-2.5 top-2.5" />
          </div>
        </div>

        <div class="pt-2 flex gap-2">
          <button
            type="button"
            onclick={onClose}
            disabled={isLoading}
            class="flex-1 py-2 bg-[#f4f4f4] text-[#525252] text-xs border border-[#e0e0e0] cursor-pointer"
          >
            Batal
          </button>
          <button
            type="submit"
            disabled={isLoading}
            class="flex-2 py-2 bg-[#161616] hover:bg-black text-white text-xs font-semibold flex items-center justify-center gap-1.5 cursor-pointer shadow-xs disabled:opacity-50"
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
