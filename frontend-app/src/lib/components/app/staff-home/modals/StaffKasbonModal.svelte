<script lang="ts">
  import { Wallet, X, AlertCircle, Check, Send } from 'lucide-svelte';
  import { DEFAULT_KASBON_AMOUNT_IDR } from '../../../../constants/defaults';

  interface Props {
    isOpen: boolean;
    onClose: () => void;
    onSubmit: (amount: number, purpose?: string) => Promise<void>;
  }

  let { isOpen, onClose, onSubmit }: Props = $props();

  let kasbonAmount = $state(DEFAULT_KASBON_AMOUNT_IDR);
  let kasbonPurpose = $state('');
  let isSubmittingKasbon = $state(false);
  let kasbonErrorMessage = $state<string | null>(null);
  let kasbonSuccessMessage = $state<string | null>(null);

  $effect(() => {
    if (isOpen) {
      kasbonAmount = DEFAULT_KASBON_AMOUNT_IDR;
      kasbonPurpose = '';
      kasbonErrorMessage = null;
      kasbonSuccessMessage = null;
    }
  });

  async function handleConfirmKasbon() {
    if (kasbonAmount <= 0) {
      kasbonErrorMessage = 'Masukkan nominal kasbon yang valid.';
      return;
    }
    isSubmittingKasbon = true;
    kasbonErrorMessage = null;
    kasbonSuccessMessage = null;
    try {
      await onSubmit(kasbonAmount, kasbonPurpose);
      kasbonSuccessMessage = 'Permohonan kasbon berhasil diajukan.';
      setTimeout(() => {
        onClose();
        kasbonPurpose = '';
        kasbonSuccessMessage = null;
      }, 1200);
    } catch (e: unknown) {
      kasbonErrorMessage = e instanceof Error ? e.message : 'Gagal mengajukan kasbon.';
    } finally {
      isSubmittingKasbon = false;
    }
  }
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4 font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-xl space-y-5 animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#ecfdf5] text-[#059669] flex items-center justify-center border border-[#a7f3d0]">
            <Wallet class="w-5 h-5" />
          </div>
          <div>
            <h3 class="font-bold text-base text-[#17171c]">Pengajuan Kasbon Staf</h3>
            <p class="text-xs text-[#8e8e93]">Pengajuan pinjaman dipotong pada slip gaji bulanan</p>
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
        <div class="p-3.5 bg-[#fef2f2] border border-[#fecaca] rounded-xl text-[#991b1b] text-xs font-medium flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{kasbonErrorMessage}</span>
        </div>
      {/if}

      {#if kasbonSuccessMessage}
        <div class="p-3.5 bg-[#ecfdf5] border border-[#a7f3d0] rounded-xl text-[#065f46] text-xs font-semibold flex items-start gap-2">
          <Check class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{kasbonSuccessMessage}</span>
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div class="space-y-1.5">
          <label for="modal-kasbon-amount" class="block font-bold text-[#17171c]">Nominal Pinjaman Kasbon (IDR)</label>
          <input
            id="modal-kasbon-amount"
            type="number"
            bind:value={kasbonAmount}
            step="50000"
            min="50000"
            class="w-full border border-[#e5e5ea] rounded-xl px-4 py-2.5 bg-[#f8f8fa] hover:bg-white font-mono text-sm text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
          />
          <span class="text-[11px] text-[#8e8e93] block">Otomatis dipotong pada slip gaji periode payroll berikutnya.</span>
        </div>

        <div class="space-y-1.5">
          <label for="modal-kasbon-purpose" class="block font-bold text-[#17171c]">Keperluan / Keterangan</label>
          <input
            id="modal-kasbon-purpose"
            type="text"
            bind:value={kasbonPurpose}
            placeholder="Contoh: Kebutuhan medis darurat, servis motor"
            class="w-full border border-[#e5e5ea] rounded-xl px-4 py-2.5 bg-[#f8f8fa] hover:bg-white text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
          />
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
          disabled={isSubmittingKasbon || kasbonAmount <= 0}
          onclick={handleConfirmKasbon}
          class="flex-1 py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full cursor-pointer transition-all disabled:opacity-50 flex items-center justify-center gap-2 shadow-xs"
        >
          {#if isSubmittingKasbon}
            <span>Mengajukan...</span>
          {:else}
            <Send class="w-4 h-4" />
            <span>Kirim Pengajuan</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
