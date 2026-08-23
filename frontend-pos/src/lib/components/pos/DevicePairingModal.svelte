<script lang="ts">
  import { Tablet, Key, AlertCircle, ArrowRight, ShieldCheck, Sparkles } from 'lucide-svelte';
  import { posService } from '../../services/pos-service';
  import type { PosTerminalInfo } from '../../types/pos';

  interface Props {
    isOpen: boolean;
    onSuccess: (info: PosTerminalInfo) => void;
  }

  let { isOpen = false, onSuccess }: Props = $props();

  let deviceTokenInput = $state('sleman_pos_token_01');
  let isLoading = $state(false);
  let errorMessage = $state<string | null>(null);

  async function handlePairDevice(event: SubmitEvent) {
    event.preventDefault();
    if (!deviceTokenInput.trim()) {
      errorMessage = 'Device token wajib diisi.';
      return;
    }

    isLoading = true;
    errorMessage = null;

    try {
      const terminalInfo = await posService.pairDevice(deviceTokenInput.trim());
      onSuccess(terminalInfo);
    } catch (err: unknown) {
      if (err instanceof Error) {
        errorMessage = err.message;
      } else {
        errorMessage = 'Gagal melakukan pairing terminal POS.';
      }
    } finally {
      isLoading = false;
    }
  }

  function setPresetToken(token: string) {
    deviceTokenInput = token;
    errorMessage = null;
  }
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-4 select-none animate-in fade-in">
    <div class="bg-white border border-[#e0e0e0] max-w-md w-full p-6 sm:p-8 shadow-2xl animate-in zoom-in-95">
      <div class="flex items-center gap-3 mb-5">
        <div class="w-10 h-10 bg-[#0f62fe] text-white flex items-center justify-center font-bold shadow-xs">
          <Tablet class="w-5 h-5" />
        </div>
        <div>
          <h2 class="text-base font-bold text-[#161616] font-display">Pairing Terminal Kasir POS</h2>
          <p class="text-[11px] text-[#525252]">
            Otorisasi perangkat tablet ke outlet cabang workspace
          </p>
        </div>
      </div>

      <div class="p-3 bg-[#f4f4f4] border-l-4 border-[#0f62fe] mb-5 text-xs text-[#525252] leading-relaxed">
        Masukkan <strong>Device Token</strong> yang diterbitkan oleh Owner/Admin bisnis Anda untuk mengikat tablet ini secara permanen ke satu outlet cabang.
      </div>

      {#if errorMessage}
        <div class="mb-4 p-3 bg-[#da1e28]/10 border-l-4 border-[#da1e28] text-xs text-[#da1e28] flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <form onsubmit={handlePairDevice} class="space-y-4">
        <div>
          <label for="device-token" class="block text-xs font-semibold text-[#161616] mb-1.5">
            Device Token Terminal
          </label>
          <div class="relative">
            <input
              id="device-token"
              type="text"
              bind:value={deviceTokenInput}
              placeholder="e.g. sleman_pos_token_01"
              required
              disabled={isLoading}
              class="w-full pl-9 pr-3 py-2.5 text-xs font-mono border border-[#e0e0e0] focus:border-[#0f62fe] focus:outline-hidden bg-[#f4f4f4] focus:bg-white transition-colors"
            />
            <Key class="w-4 h-4 text-[#8c8c8c] absolute left-3 top-3" />
          </div>
        </div>

        <button
          type="submit"
          disabled={isLoading}
          class="w-full py-2.5 bg-[#0f62fe] hover:bg-[#0050e6] text-white font-bold text-xs flex items-center justify-center gap-2 transition-colors cursor-pointer disabled:opacity-50"
        >
          {#if isLoading}
            <span>Memverifikasi Token...</span>
          {:else}
            <span>Hubungkan Terminal (Pair Device)</span>
            <ArrowRight class="w-4 h-4" />
          {/if}
        </button>
      </form>

      <!-- preset token demo -->
      <div class="mt-6 pt-4 border-t border-[#e0e0e0]">
        <div class="flex items-center gap-1.5 text-[11px] font-mono text-[#8c8c8c] mb-2">
          <Sparkles class="w-3.5 h-3.5 text-[#0f62fe]" />
          <span>Token Pilot Seeder:</span>
        </div>
        <div class="flex gap-2">
          <button
            type="button"
            onclick={() => setPresetToken('sleman_pos_token_01')}
            class="flex-1 p-2 text-left border border-[#e0e0e0] hover:border-[#0f62fe] hover:bg-[#f4f4f4] transition-colors cursor-pointer text-xs"
          >
            <div class="font-bold text-[11px] text-[#161616]">Outlet Sleman #01</div>
            <div class="text-[9px] font-mono text-[#8c8c8c]">sleman_pos_token_01</div>
          </button>
        </div>
      </div>

      <div class="mt-5 flex items-center justify-center gap-1.5 text-[10px] text-[#8c8c8c] font-mono">
        <ShieldCheck class="w-3.5 h-3.5 text-[#24a148]" />
        <span>Terenkripsi SHA-256 & Terisolasi Multi-Tenant</span>
      </div>
    </div>
  </div>
{/if}
