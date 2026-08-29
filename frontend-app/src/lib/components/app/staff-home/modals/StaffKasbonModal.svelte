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
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="animate-in fade-in zoom-in-95 w-full max-w-lg space-y-5 rounded-3xl border border-[#e5e5ea] bg-white p-6 shadow-xl sm:p-7"
    >
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-3">
          <div
            class="flex size-10 items-center justify-center rounded-2xl border border-[#a7f3d0] bg-[#ecfdf5] text-[#059669]"
          >
            <Wallet class="size-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Pengajuan Kasbon Staf</h3>
            <p class="text-xs text-[#8e8e93]">Pengajuan pinjaman dipotong pada slip gaji bulanan</p>
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
          class="flex items-start gap-2 rounded-xl border border-[#fecaca] bg-[#fef2f2] p-3.5 text-xs font-medium text-[#991b1b]"
        >
          <AlertCircle class="mt-0.5 size-4 shrink-0" />
          <span>{kasbonErrorMessage}</span>
        </div>
      {/if}

      {#if kasbonSuccessMessage}
        <div
          class="flex items-start gap-2 rounded-xl border border-[#a7f3d0] bg-[#ecfdf5] p-3.5 text-xs font-semibold text-[#065f46]"
        >
          <Check class="mt-0.5 size-4 shrink-0" />
          <span>{kasbonSuccessMessage}</span>
        </div>
      {/if}

      <div class="space-y-4 text-xs">
        <div class="space-y-1.5">
          <label for="modal-kasbon-amount" class="block font-bold text-[#17171c]"
            >Nominal Pinjaman Kasbon (IDR)</label
          >
          <input
            id="modal-kasbon-amount"
            type="number"
            bind:value={kasbonAmount}
            step="50000"
            min="50000"
            class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 font-mono text-sm text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          />
          <span class="block text-[11px] text-[#8e8e93]"
            >Otomatis dipotong pada slip gaji periode payroll berikutnya.</span
          >
        </div>

        <div class="space-y-1.5">
          <label for="modal-kasbon-purpose" class="block font-bold text-[#17171c]"
            >Keperluan / Keterangan</label
          >
          <input
            id="modal-kasbon-purpose"
            type="text"
            bind:value={kasbonPurpose}
            placeholder="Contoh: Kebutuhan medis darurat, servis motor"
            class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-xs text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          />
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
          disabled={isSubmittingKasbon || kasbonAmount <= 0}
          onclick={handleConfirmKasbon}
          class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
        >
          {#if isSubmittingKasbon}
            <span>Mengajukan...</span>
          {:else}
            <Send class="size-4" />
            <span>Kirim Pengajuan</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
