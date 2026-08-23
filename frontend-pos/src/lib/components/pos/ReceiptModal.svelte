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
  <div class="fixed inset-0 z-50 bg-[#17171c]/40 backdrop-blur-xs flex items-center justify-center p-4 font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] max-w-md w-full p-6 shadow-none flex flex-col max-h-[95vh]">
      <!-- Success Header -->
      <div class="text-center pb-4 border-b border-[#d9d9dd]">
        <div class="w-12 h-12 bg-[#edfce9] text-[#003c33] flex items-center justify-center mx-auto mb-2 rounded-full">
          <CheckCircle2 class="w-6 h-6" />
        </div>
        <h2 class="text-lg font-medium text-[#212121] tracking-tight">Transaksi Berhasil!</h2>
        <p class="text-xs font-mono text-[#75758a] mt-0.5">Nomor: {order.order_number}</p>
      </div>

      <!-- Thermal Paper Receipt Preview -->
      <div class="flex-1 overflow-y-auto my-4 p-3.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[16px] font-mono text-[11px] leading-relaxed">
        <div class="bg-white p-4 rounded-[12px] border border-[#d9d9dd] whitespace-pre font-mono text-xs text-[#212121] shadow-none">
          {receiptText}
        </div>
      </div>

      <!-- Actions -->
      <div class="space-y-2.5 pt-3 border-t border-[#d9d9dd]">
        <button
          type="button"
          disabled={isPrinting}
          onclick={handlePrint}
          class="w-full py-3 bg-[#eeece7]/40 hover:bg-[#eeece7] text-[#212121] border border-[#d9d9dd] rounded-full text-xs font-medium flex items-center justify-center gap-2 cursor-pointer transition-all shadow-none"
        >
          {#if isPrinting}
            <span>Mengirim ke Printer ESC/POS...</span>
          {:else if printSuccess}
            <Check class="w-4 h-4 text-[#003c33]" />
            <span>Struk Berhasil Dicetak</span>
          {:else}
            <Printer class="w-4 h-4 text-[#1863dc]" />
            <span>Cetak Struk Thermal (Bluetooth / USB)</span>
          {/if}
        </button>

        <button
          type="button"
          onclick={onCloseAndReset}
          class="w-full py-3 bg-[#17171c] hover:bg-[#000000] text-white rounded-full text-xs font-medium flex items-center justify-center gap-2 cursor-pointer transition-all shadow-none"
        >
          <span>Transaksi Baru</span>
          <ArrowRight class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
{/if}
