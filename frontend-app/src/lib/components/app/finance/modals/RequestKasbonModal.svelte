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
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="animate-in fade-in zoom-in-95 w-full max-w-lg space-y-5 rounded-3xl border border-[#e5e5ea] bg-white p-6 shadow-xl sm:p-7"
    >
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-3">
          <div
            class="flex size-10 items-center justify-center rounded-2xl border border-[#bfdbfe] bg-[#eff6ff] text-[#2563eb]"
          >
            <Wallet class="size-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Ajukan Kasbon Karyawan</h3>
            <p class="text-xs text-[#8e8e93]">
              Pengajuan pinjaman dipotong pada slip gaji berjalan
            </p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="cursor-pointer rounded-xl p-2 text-[#8e8e93] transition-all hover:bg-[#f4f4f6] hover:text-[#17171c]"
        >
          <X class="size-5" />
        </button>
      </div>

      {#if kasbonErrorMessage}
        <div
          class="rounded-xl border border-[#fecaca] bg-[#fef2f2] p-3.5 text-xs font-medium text-[#991b1b]"
        >
          {kasbonErrorMessage}
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div class="space-y-1.5">
          <label for="kasbon-amount" class="block font-bold text-[#17171c]"
            >Nominal Pengajuan (IDR)</label
          >
          <input
            id="kasbon-amount"
            type="number"
            step="50000"
            bind:value={requestAmount}
            class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 font-mono text-sm text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="space-y-1.5">
          <label for="kasbon-purpose" class="block font-bold text-[#17171c]"
            >Keperluan / Alasan Pinjaman</label
          >
          <textarea
            id="kasbon-purpose"
            bind:value={requestPurpose}
            rows="3"
            placeholder="Jelaskan kebutuhan mendesak..."
            class="w-full resize-none rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-xs text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          ></textarea>
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 cursor-pointer rounded-full border border-[#e5e5ea] py-3 text-xs font-semibold text-[#686873] transition-all hover:bg-[#f4f4f6]"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSendKasbon}
          disabled={isSubmittingKasbon || requestAmount <= 0}
          class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
        >
          {#if isSubmittingKasbon}
            <span>Mengirim...</span>
          {:else}
            <Send class="size-4" />
            <span>Kirim Pengajuan</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
