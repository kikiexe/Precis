<script lang="ts">
  import { Wallet, X, Send } from 'lucide-svelte';
  import { DEFAULT_KASBON_AMOUNT_IDR } from '../../../../constants/defaults';

  interface Props {
    isOpen: boolean;
    onClose: () => void;
    onSubmit: (amount: number, purpose?: string) => Promise<void> | void;
  }

  let { isOpen, onClose, onSubmit }: Props = $props();

  let requestAmount = $state(DEFAULT_KASBON_AMOUNT_IDR);
  let requestPurpose = $state('');
  let isSubmittingKasbon = $state(false);
  let kasbonErrorMessage = $state<string | null>(null);

  $effect(() => {
    if (isOpen) {
      requestAmount = DEFAULT_KASBON_AMOUNT_IDR;
      requestPurpose = '';
      kasbonErrorMessage = null;
    }
  });

  async function handleSendKasbon() {
    if (requestAmount <= 0) {
      kasbonErrorMessage = 'Masukkan nominal kasbon yang valid.';
      return;
    }
    isSubmittingKasbon = true;
    kasbonErrorMessage = null;
    try {
      await onSubmit(requestAmount, requestPurpose);
      onClose();
      requestPurpose = '';
    } catch (e: unknown) {
      kasbonErrorMessage = e instanceof Error ? e.message : 'Gagal mengajukan kasbon.';
    } finally {
      isSubmittingKasbon = false;
    }
  }
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl w-full max-w-lg p-6 sm:p-7 space-y-5 shadow-xl animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between pb-3 border-b border-[#f2f2f4]">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#eff6ff] text-[#2563eb] flex items-center justify-center border border-[#bfdbfe]">
            <Wallet class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Ajukan Kasbon Karyawan</h3>
            <p class="text-xs text-[#8e8e93]">Pengajuan pinjaman dipotong pada slip gaji berjalan</p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="p-2 text-[#8e8e93] hover:text-[#17171c] hover:bg-[#f4f4f6] rounded-xl cursor-pointer transition-all"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      {#if kasbonErrorMessage}
        <div class="p-3.5 bg-[#fef2f2] text-[#991b1b] text-xs font-medium rounded-xl border border-[#fecaca]">
          {kasbonErrorMessage}
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div class="space-y-1.5">
          <label for="kasbon-amount" class="block font-bold text-[#17171c]">Nominal Pengajuan (IDR)</label>
          <input
            id="kasbon-amount"
            type="number"
            step="50000"
            bind:value={requestAmount}
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl font-mono text-sm text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
          />
        </div>

        <div class="space-y-1.5">
          <label for="kasbon-purpose" class="block font-bold text-[#17171c]">Keperluan / Alasan Pinjaman</label>
          <textarea
            id="kasbon-purpose"
            bind:value={requestPurpose}
            rows="3"
            placeholder="Jelaskan kebutuhan mendesak..."
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden resize-none transition-all shadow-2xs"
          ></textarea>
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-3 text-xs font-semibold border border-[#e5e5ea] hover:bg-[#f4f4f6] rounded-full text-[#686873] cursor-pointer transition-all"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSendKasbon}
          disabled={isSubmittingKasbon || requestAmount <= 0}
          class="flex-1 py-3 text-xs font-semibold bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer transition-all disabled:opacity-50 flex items-center justify-center gap-2 shadow-xs"
        >
          {#if isSubmittingKasbon}
            <span>Mengirim...</span>
          {:else}
            <Send class="w-4 h-4" />
            <span>Kirim Pengajuan</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
