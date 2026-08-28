<script lang="ts">
  import { Printer, CheckCircle2, ArrowRight, Check } from 'lucide-svelte';
  import type { OfflineOrder } from '../../types/pos';
  import { generateReceiptText, simulatePrint } from '../../services/printer-service';

  interface Props {
    isOpen: boolean;
    order: OfflineOrder | null;
    onCloseAndReset: () => void;
  }

  let { isOpen = false, order = null, onCloseAndReset }: Props = $props();

  let isPrinting = $state(false);
  let printSuccess = $state(false);

  let receiptText = $derived(order ? generateReceiptText(order) : '');

  async function handlePrint() {
    if (!order || isPrinting) return;
    isPrinting = true;
    printSuccess = false;
    await simulatePrint(order);
    isPrinting = false;
    printSuccess = true;
  }
</script>

{#if isOpen && order}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-[#17171c]/40 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="flex max-h-[95vh] w-full max-w-md flex-col rounded-[22px] border border-[#d9d9dd] bg-white p-6 shadow-none"
    >
      <!-- Success Header -->
      <div class="border-b border-[#d9d9dd] pb-4 text-center">
        <div
          class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-[#edfce9] text-[#003c33]"
        >
          <CheckCircle2 class="h-6 w-6" />
        </div>
        <h2 class="text-lg font-medium tracking-tight text-[#212121]">Transaksi Berhasil!</h2>
        <p class="mt-0.5 font-mono text-xs text-[#75758a]">Nomor: {order.order_number}</p>
      </div>

      <!-- Thermal Paper Receipt Preview -->
      <div
        class="my-4 flex-1 overflow-y-auto rounded-[16px] border border-[#d9d9dd] bg-[#eeece7]/30 p-3.5 font-mono text-[11px] leading-relaxed"
      >
        <div
          class="rounded-[12px] border border-[#d9d9dd] bg-white p-4 font-mono text-xs whitespace-pre text-[#212121] shadow-none"
        >
          {receiptText}
        </div>
      </div>

      <!-- Actions -->
      <div class="space-y-2.5 border-t border-[#d9d9dd] pt-3">
        <button
          type="button"
          disabled={isPrinting}
          onclick={handlePrint}
          class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-full border border-[#d9d9dd] bg-[#eeece7]/40 py-3 text-xs font-medium text-[#212121] shadow-none transition-all hover:bg-[#eeece7]"
        >
          {#if isPrinting}
            <span>Mengirim ke Printer ESC/POS...</span>
          {:else if printSuccess}
            <Check class="h-4 w-4 text-[#003c33]" />
            <span>Struk Berhasil Dicetak</span>
          {:else}
            <Printer class="h-4 w-4 text-[#1863dc]" />
            <span>Cetak Struk Thermal (Bluetooth / USB)</span>
          {/if}
        </button>

        <button
          type="button"
          onclick={onCloseAndReset}
          class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-3 text-xs font-medium text-white shadow-none transition-all hover:bg-[#000000]"
        >
          <span>Transaksi Baru</span>
          <ArrowRight class="h-4 w-4" />
        </button>
      </div>
    </div>
  </div>
{/if}
