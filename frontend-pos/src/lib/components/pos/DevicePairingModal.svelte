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
  <div class="fixed inset-0 z-50 bg-[#17171c]/60 backdrop-blur-xs flex items-center justify-center p-4 select-none animate-in fade-in font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] max-w-md w-full p-6 sm:p-8 shadow-none animate-in zoom-in-95">
      <div class="flex items-center gap-3 mb-5">
        <div class="w-10 h-10 bg-[#17171c] text-white flex items-center justify-center font-medium rounded-[12px] shadow-none">
          <Tablet class="w-5 h-5" />
        </div>
        <div>
          <h2 class="text-base font-medium text-[#212121] tracking-tight">Pairing Terminal Kasir POS</h2>
          <p class="text-[11px] text-[#616161]">
            Otorisasi perangkat tablet ke outlet cabang workspace
          </p>
        </div>
      </div>

      <div class="p-3.5 bg-[#eeece7]/40 border border-[#d9d9dd] rounded-[14px] mb-5 text-xs text-[#616161] leading-relaxed">
        Masukkan <strong class="text-[#212121]">Device Token</strong> yang diterbitkan oleh Owner/Admin bisnis Anda untuk mengikat tablet ini secara permanen ke satu outlet cabang.
      </div>

      {#if errorMessage}
        <div class="mb-4 p-3 bg-[#ffad9b]/15 border border-[#ffad9b] rounded-[12px] text-xs text-[#b30000] flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <form onsubmit={handlePairDevice} class="space-y-4">
        <div>
          <label for="device-token" class="block text-xs font-medium text-[#212121] mb-1.5">
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
              class="w-full pl-9 pr-3 py-2.5 text-xs font-mono rounded-[12px] border border-[#d9d9dd] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden bg-white text-[#212121] transition-all"
            />
            <Key class="w-4 h-4 text-[#93939f] absolute left-3 top-3" />
          </div>
        </div>

        <button
          type="submit"
          disabled={isLoading}
          class="w-full py-3 bg-[#17171c] hover:bg-[#000000] text-white font-medium text-xs rounded-full flex items-center justify-center gap-2 transition-all cursor-pointer shadow-none disabled:opacity-50"
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
      <div class="mt-6 pt-4 border-t border-[#d9d9dd]">
        <div class="flex items-center gap-1.5 text-[11px] font-mono text-[#75758a] mb-2">
          <Sparkles class="w-3.5 h-3.5 text-[#1863dc]" />
          <span>Token Pilot Seeder:</span>
        </div>
        <div class="flex gap-2">
          <button
            type="button"
            onclick={() => setPresetToken('sleman_pos_token_01')}
            class="flex-1 p-2.5 text-left border border-[#d9d9dd] rounded-[14px] hover:border-[#17171c] bg-[#eeece7]/30 hover:bg-[#eeece7]/60 transition-all cursor-pointer text-xs"
          >
            <div class="font-medium text-[11px] text-[#212121]">Outlet Sleman #01</div>
            <div class="text-[9px] font-mono text-[#75758a]">sleman_pos_token_01</div>
          </button>
        </div>
      </div>

      <div class="mt-5 flex items-center justify-center gap-1.5 text-[10px] text-[#75758a] font-mono">
        <ShieldCheck class="w-3.5 h-3.5 text-[#003c33]" />
        <span>Terenkripsi SHA-256 &amp; Terisolasi Multi-Tenant</span>
      </div>
    </div>
  </div>
{/if}
