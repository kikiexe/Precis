<script lang="ts">
  import { Wallet, X, AlertCircle, Check } from 'lucide-svelte';
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
  <div class="fixed inset-0 z-50 bg-[#17171c]/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] max-w-md w-full p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 font-sans">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <div class="flex items-center gap-2">
          <Wallet class="w-4 h-4 text-[#00875a]" />
          <h3 class="font-medium text-base text-[#212121]">Pengajuan Kasbon Staf</h3>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="text-[#93939f] hover:text-[#212121] cursor-pointer p-1"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      {#if kasbonErrorMessage}
        <div class="p-3 bg-[#ffefef] border border-[#e5484d]/30 rounded-xl text-[#e5484d] text-xs flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{kasbonErrorMessage}</span>
        </div>
      {/if}

      {#if kasbonSuccessMessage}
        <div class="p-3 bg-[#edfce9] border border-[#00875a]/30 rounded-xl text-[#00875a] text-xs flex items-start gap-2">
          <Check class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{kasbonSuccessMessage}</span>
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div>
          <label for="modal-kasbon-amount" class="block font-medium text-[#212121] mb-1.5">Nominal Pinjaman Kasbon (IDR)</label>
          <input
            id="modal-kasbon-amount"
            type="number"
            bind:value={kasbonAmount}
            step="50000"
            min="50000"
            class="w-full border border-[#d9d9dd] rounded-xl p-2.5 bg-white font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
          <span class="text-[10px] text-[#75758a] mt-1 block">Otomatis dipotong pada slip gaji periode payroll berikutnya.</span>
        </div>

        <div>
          <label for="modal-kasbon-purpose" class="block font-medium text-[#212121] mb-1.5">Keperluan / Keterangan</label>
          <input
            id="modal-kasbon-purpose"
            type="text"
            bind:value={kasbonPurpose}
            placeholder="Contoh: Kebutuhan darurat medis / transportasi"
            class="w-full border border-[#d9d9dd] rounded-xl p-2.5 bg-white text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>
      </div>

      <div class="flex gap-2.5 pt-3 border-t border-[#d9d9dd]">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-2.5 border border-[#d9d9dd] hover:bg-[#eeece7]/40 text-[#616161] text-xs font-medium rounded-full cursor-pointer transition-all"
        >
          Batal
        </button>
        <button
          type="button"
          disabled={isSubmittingKasbon || kasbonAmount <= 0}
          onclick={handleConfirmKasbon}
          class="flex-1 py-2.5 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-full cursor-pointer transition-all disabled:opacity-50"
        >
          {isSubmittingKasbon ? 'Mengajukan...' : 'Ajukan Kasbon'}
        </button>
      </div>
    </div>
  </div>
{/if}
