<script lang="ts">
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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-3xl w-full max-w-md p-6 space-y-4 shadow-2xl">
      <h3 class="text-base font-medium text-[#212121]">Ajukan Kasbon Karyawan</h3>
      {#if kasbonErrorMessage}
        <div class="p-3 bg-[#ffefef] text-[#e5484d] text-xs rounded-xl">{kasbonErrorMessage}</div>
      {/if}
      <div class="space-y-3 text-xs">
        <div class="space-y-1">
          <label for="kasbon-amount" class="block font-medium text-[#212121]">Nominal Pengajuan (IDR)</label>
          <input
            id="kasbon-amount"
            type="number"
            bind:value={requestAmount}
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>
        <div class="space-y-1">
          <label for="kasbon-purpose" class="block font-medium text-[#212121]">Keperluan / Alasan</label>
          <textarea
            id="kasbon-purpose"
            bind:value={requestPurpose}
            rows="2"
            placeholder="Keperluan mendesak..."
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-xl text-[#212121] focus:border-[#17171c] focus:outline-hidden resize-none"
          ></textarea>
        </div>
      </div>
      <div class="flex gap-3 pt-2">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#eeece7] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSendKasbon}
          disabled={isSubmittingKasbon}
          class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer disabled:opacity-50"
        >
          {isSubmittingKasbon ? 'Mengirim...' : 'Kirim Pengajuan'}
        </button>
      </div>
    </div>
  </div>
{/if}
