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
  <div class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-[#e0e0e0] max-w-md w-full p-6 shadow-2xl flex flex-col max-h-[95vh]">
      <!-- Success Header -->
      <div class="text-center pb-4 border-b border-[#e0e0e0]">
        <div class="w-12 h-12 bg-[#24a148]/10 text-[#24a148] flex items-center justify-center mx-auto mb-2 rounded-full">
          <CheckCircle2 class="w-7 h-7" />
        </div>
        <h2 class="text-lg font-bold text-[#161616] font-display">Transaksi Berhasil!</h2>
        <p class="text-xs font-mono text-[#525252] mt-0.5">Nomor: {order.order_number}</p>
      </div>

      <!-- Thermal Paper Receipt Preview -->
      <div class="flex-1 overflow-y-auto my-4 p-4 bg-[#f4f4f4] border border-[#e0e0e0] font-mono text-[11px] leading-relaxed shadow-inner">
        <div class="bg-white p-4 border border-[#e0e0e0] whitespace-pre font-mono text-xs text-[#161616] shadow-xs">
          {receiptText}
        </div>
      </div>

      <!-- Actions -->
      <div class="space-y-2 pt-2 border-t border-[#e0e0e0]">
        <button
          type="button"
          disabled={isPrinting}
          onclick={handlePrint}
          class="w-full py-3 bg-[#161616] hover:bg-[#262626] text-white text-xs font-semibold flex items-center justify-center gap-2 cursor-pointer transition-colors"
        >
          {#if isPrinting}
            <span>Mengirim ke Printer ESC/POS...</span>
          {:else if printSuccess}
            <Check class="w-4 h-4 text-[#24a148]" />
            <span>Struk Berhasil Dicetak</span>
          {:else}
            <Printer class="w-4 h-4 text-[#0f62fe]" />
            <span>Cetak Struk Thermal (Bluetooth / USB)</span>
          {/if}
        </button>

        <button
          type="button"
          onclick={onCloseAndReset}
          class="w-full py-3 bg-[#0f62fe] hover:bg-[#0050e6] text-white text-xs font-semibold flex items-center justify-center gap-2 cursor-pointer transition-colors shadow-xs"
        >
          <span>Transaksi Baru</span>
          <ArrowRight class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
{/if}
