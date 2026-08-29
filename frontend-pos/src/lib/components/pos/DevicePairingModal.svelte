<script lang="ts">
  import { Tablet, Key, AlertCircle, ArrowRight, ShieldCheck } from 'lucide-svelte';
  import { posService } from '../../services/pos-service';
  import type { PosTerminalInfo } from '../../types/pos';

  interface Props {
    isOpen: boolean;
    onSuccess: (info: PosTerminalInfo) => void;
  }

  let { isOpen = false, onSuccess }: Props = $props();

  let deviceTokenInput = $state('');
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
</script>

{#if isOpen}
  <div
    class="animate-in fade-in fixed inset-0 z-50 flex items-center justify-center bg-[#17171c]/60 p-4 font-sans backdrop-blur-xs select-none"
  >
    <div
      class="animate-in zoom-in-95 w-full max-w-md rounded-[22px] border border-[#d9d9dd] bg-white p-6 shadow-none sm:p-8"
    >
      <div class="mb-5 flex items-center gap-3">
        <div
          class="flex size-10 items-center justify-center rounded-xl bg-[#17171c] font-medium text-white shadow-none"
        >
          <Tablet class="size-5" />
        </div>
        <div>
          <h2 class="text-base font-medium tracking-tight text-[#212121]">
            Pairing Terminal Kasir POS
          </h2>
          <p class="text-[11px] text-[#616161]">
            Otorisasi perangkat tablet ke outlet cabang workspace
          </p>
        </div>
      </div>

      <div
        class="mb-5 rounded-[14px] border border-[#d9d9dd] bg-[#eeece7]/40 p-3.5 text-xs leading-relaxed text-[#616161]"
      >
        Masukkan <strong class="text-[#212121]">Device Token</strong> yang diterbitkan oleh Owner/Admin
        bisnis Anda untuk mengikat tablet ini secara permanen ke satu outlet cabang.
      </div>

      {#if errorMessage}
        <div
          class="mb-4 flex items-start gap-2 rounded-xl border border-[#ffad9b] bg-[#ffad9b]/15 p-3 text-xs text-[#b30000]"
        >
          <AlertCircle class="mt-0.5 size-4 shrink-0" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <form onsubmit={handlePairDevice} class="space-y-4">
        <div>
          <label for="device-token" class="mb-1.5 block text-xs font-medium text-[#212121]">
            Device Token Terminal
          </label>
          <div class="relative">
            <input
              id="device-token"
              type="text"
              bind:value={deviceTokenInput}
              placeholder="e.g. tok-sleman-a89b"
              required
              disabled={isLoading}
              class="w-full rounded-xl border border-[#d9d9dd] bg-white py-2.5 pr-3 pl-9 font-mono text-xs text-[#212121] transition-all focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
            />
            <Key class="absolute top-3 left-3 size-4 text-[#93939f]" />
          </div>
        </div>

        <button
          type="submit"
          disabled={isLoading}
          class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-3 text-xs font-medium text-white shadow-none transition-all hover:bg-[#000000] disabled:opacity-50"
        >
          {#if isLoading}
            <span>Memverifikasi Token...</span>
          {:else}
            <span>Hubungkan Terminal (Pair Device)</span>
            <ArrowRight class="size-4" />
          {/if}
        </button>
      </form>

      <div
        class="mt-6 flex items-center justify-center gap-1.5 border-t border-[#d9d9dd] pt-4 font-mono text-[10px] text-[#75758a]"
      >
        <ShieldCheck class="size-3.5 text-[#003c33]" />
        <span>Terenkripsi SHA-256 &amp; Terisolasi Multi-Tenant</span>
      </div>
    </div>
  </div>
{/if}
