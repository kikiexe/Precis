<script lang="ts">
  import { Banknote, QrCode, ArrowRightLeft, X, Check, AlertTriangle } from 'lucide-svelte';
  import type { PaymentMethod, CartItem, OfflineOrder, CashierUser } from '../../types/pos';
  import { formatCurrency } from '../../services/printer-service';

  interface Props {
    isOpen: boolean;
    totalAmount: number;
    discountAmount: number;
    finalAmount: number;
    items: CartItem[];
    activeCashier: CashierUser;
    branchId: string;
    workspaceId: string;
    activeSessionId: string;
    onClose: () => void;
    onCompleteOrder: (order: OfflineOrder) => void;
  }

  let {
    isOpen = false,
    totalAmount = 0,
    discountAmount = 0,
    finalAmount = 0,
    items = [],
    activeCashier,
    branchId = 'branch-sleman-01',
    workspaceId = 'ws-amore-01',
    activeSessionId = 'sess-active-01',
    onClose,
    onCompleteOrder,
  }: Props = $props();

  let selectedMethod = $state<PaymentMethod>('CASH');
  let cashTendered = $state<number>(0);
  let isProcessing = $state(false);

  $effect(() => {
    if (isOpen) {
      cashTendered = finalAmount;
      isProcessing = false;
    }
  });

  let changeAmount = $derived(
    selectedMethod === 'CASH' ? Math.max(0, cashTendered - finalAmount) : 0
  );

  let isSufficient = $derived(
    selectedMethod !== 'CASH' || cashTendered >= finalAmount
  );

  const cashShortcuts = [
    { label: 'Uang Pas', getValue: (tot: number) => tot },
    { label: 'Rp 20.000', getValue: () => 20000 },
    { label: 'Rp 50.000', getValue: () => 50000 },
    { label: 'Rp 100.000', getValue: () => 100000 },
    { label: 'Rp 200.000', getValue: () => 200000 },
  ];

  function handleQuickCash(val: number) {
    cashTendered = val;
  }

  function handleProcessPayment() {
    if (!isSufficient || isProcessing) return;
    isProcessing = true;

    const orderNumber = `ORD-${Date.now().toString().slice(-6)}`;
    const newOrder: OfflineOrder = {
      client_order_id: crypto.randomUUID(),
      order_number: orderNumber,
      workspace_id: workspaceId,
      branch_id: branchId,
      pos_session_id: activeSessionId,
      cashier_user_id: activeCashier.id,
      cashier_name: activeCashier.name,
      order_type: 'DINE_IN',
      total_amount: totalAmount,
      discount_amount: discountAmount,
      final_amount: finalAmount,
      payment_method: selectedMethod,
      cash_tendered: selectedMethod === 'CASH' ? cashTendered : undefined,
      change_amount: selectedMethod === 'CASH' ? changeAmount : undefined,
      items: items.map((i) => ({
        product_id: i.product.id,
        product_name: i.product.name,
        quantity: i.quantity,
        unit_price: i.unit_price,
        subtotal: i.unit_price * i.quantity,
        notes: i.notes || undefined,
      })),
      created_at: new Date().toISOString(),
      sync_status: 'PENDING',
    };

    setTimeout(() => {
      onCompleteOrder(newOrder);
      isProcessing = false;
    }, 400);
  }
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-[#e0e0e0] max-w-xl w-full p-6 shadow-2xl flex flex-col max-h-[90vh]">
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-4 mb-6">
        <div>
          <h2 class="text-xl font-bold text-[#161616] font-display">Pilih Metode Pembayaran</h2>
          <p class="text-xs text-[#525252] font-mono mt-0.5">Kasir: {activeCashier.name} • Total {formatCurrency(finalAmount)}</p>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="text-[#8c8c8c] hover:text-[#161616] p-1 cursor-pointer"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Payment Method Tabs -->
      <div class="grid grid-cols-3 gap-2 mb-6">
        <button
          type="button"
          onclick={() => (selectedMethod = 'CASH')}
          class={`py-3.5 px-3 border text-center flex flex-col items-center gap-1.5 transition-colors cursor-pointer ${
            selectedMethod === 'CASH'
              ? 'bg-[#0f62fe] text-white border-[#0f62fe] font-bold shadow-xs'
              : 'bg-[#f4f4f4] text-[#525252] border-[#e0e0e0] hover:text-[#161616]'
          }`}
        >
          <Banknote class="w-5 h-5" />
          <span class="text-xs">Uang Tunai (Cash)</span>
        </button>

        <button
          type="button"
          onclick={() => (selectedMethod = 'QRIS')}
          class={`py-3.5 px-3 border text-center flex flex-col items-center gap-1.5 transition-colors cursor-pointer ${
            selectedMethod === 'QRIS'
              ? 'bg-[#0f62fe] text-white border-[#0f62fe] font-bold shadow-xs'
              : 'bg-[#f4f4f4] text-[#525252] border-[#e0e0e0] hover:text-[#161616]'
          }`}
        >
          <QrCode class="w-5 h-5" />
          <span class="text-xs">QRIS Dinamis</span>
        </button>

        <button
          type="button"
          onclick={() => (selectedMethod = 'TRANSFER')}
          class={`py-3.5 px-3 border text-center flex flex-col items-center gap-1.5 transition-colors cursor-pointer ${
            selectedMethod === 'TRANSFER'
              ? 'bg-[#0f62fe] text-white border-[#0f62fe] font-bold shadow-xs'
              : 'bg-[#f4f4f4] text-[#525252] border-[#e0e0e0] hover:text-[#161616]'
          }`}
        >
          <ArrowRightLeft class="w-5 h-5" />
          <span class="text-xs">Transfer Bank</span>
        </button>
      </div>

      <!-- Payment Body Area -->
      <div class="flex-1 overflow-y-auto mb-6">
        {#if selectedMethod === 'CASH'}
          <div class="space-y-4 bg-[#f4f4f4] p-4 border border-[#e0e0e0]">
            <!-- Cash Input -->
            <div>
              <label for="cash-input" class="block text-xs font-mono text-[#525252] mb-1.5">
                Nominal Uang Diterima dari Pelanggan:
              </label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-mono text-base font-bold text-[#161616]">
                  Rp
                </span>
                <input
                  id="cash-input"
                  type="number"
                  bind:value={cashTendered}
                  step="1000"
                  class="w-full bg-white border border-[#e0e0e0] pl-12 pr-4 py-3 text-lg font-mono font-bold text-[#161616] focus:border-[#0f62fe] focus:outline-none"
                />
              </div>
            </div>

            <!-- Denomination Shortcuts -->
            <div>
              <div class="text-[11px] font-mono text-[#8c8c8c] mb-1.5">Pilihan Nominal Cepat:</div>
              <div class="flex flex-wrap gap-2">
                {#each cashShortcuts as cut}
                  {@const val = cut.getValue(finalAmount)}
                  <button
                    type="button"
                    onclick={() => handleQuickCash(val)}
                    class="px-3 py-1.5 bg-white border border-[#e0e0e0] hover:border-[#0f62fe] hover:bg-[#0f62fe]/5 text-xs font-mono font-medium text-[#161616] cursor-pointer transition-colors"
                  >
                    {cut.label}
                  </button>
                {/each}
              </div>
            </div>

            <!-- Kembalian / Change calculation -->
            <div class="pt-3 border-t border-[#e0e0e0] flex items-center justify-between">
              <span class="text-sm font-medium text-[#525252]">Kembalian Pelanggan:</span>
              <span class={`font-mono text-xl font-bold ${cashTendered >= finalAmount ? 'text-[#24a148]' : 'text-[#da1e28]'}`}>
                {formatCurrency(changeAmount)}
              </span>
            </div>

            {#if !isSufficient}
              <div class="flex items-center gap-2 text-xs text-[#da1e28] font-medium bg-[#da1e28]/10 p-2.5">
                <AlertTriangle class="w-4 h-4 shrink-0" />
                <span>Uang yang dimasukkan kurang {formatCurrency(finalAmount - cashTendered)}</span>
              </div>
            {/if}
          </div>
        {:else if selectedMethod === 'QRIS'}
          <div class="text-center p-6 bg-[#f4f4f4] border border-[#e0e0e0] space-y-3">
            <div class="w-44 h-44 bg-white p-3 border border-[#e0e0e0] mx-auto shadow-xs flex flex-col items-center justify-center">
              <!-- QR Simulator -->
              <QrCode class="w-32 h-32 text-[#161616]" />
              <span class="text-[10px] font-mono text-[#8c8c8c] mt-1">QRIS NATIONAL STANDARDS</span>
            </div>
            <div class="text-xs text-[#525252] font-mono">
              Scan melalui BCA, GoPay, OVO, ShopeePay, atau DANA
            </div>
            <div class="text-lg font-bold font-mono text-[#0f62fe]">
              {formatCurrency(finalAmount)}
            </div>
          </div>
        {:else if selectedMethod === 'TRANSFER'}
          <div class="p-4 bg-[#f4f4f4] border border-[#e0e0e0] space-y-2.5 text-xs font-mono">
            <div class="text-sm font-bold text-[#161616]">Rekening Outlet:</div>
            <div class="p-3 bg-white border border-[#e0e0e0] space-y-1">
              <div class="text-[#525252]">Bank Central Asia (BCA)</div>
              <div class="text-base font-bold text-[#161616]">8412-9900-1122</div>
              <div class="text-[#8c8c8c]">a.n. Précis Coffee Sleman</div>
            </div>
            <div class="text-[#525252]">
              Pastikan pelanggan menunjukkan bukti transfer sukses sebelum menyelesaikan transaksi.
            </div>
          </div>
        {/if}
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-3 border-t border-[#e0e0e0] pt-4">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-3 bg-[#f4f4f4] hover:bg-[#e0e0e0] text-[#525252] font-medium text-xs border border-[#e0e0e0] cursor-pointer"
        >
          Kembali
        </button>
        <button
          type="button"
          disabled={!isSufficient || isProcessing}
          onclick={handleProcessPayment}
          class={`flex-2 py-3 text-white font-medium text-xs flex items-center justify-center gap-2 cursor-pointer shadow-xs ${
            !isSufficient || isProcessing
              ? 'bg-[#e0e0e0] text-[#8c8c8c] cursor-not-allowed'
              : 'bg-[#24a148] hover:bg-[#1e8a3d]'
          }`}
        >
          {#if isProcessing}
            <span>Memproses...</span>
          {:else}
            <Check class="w-4 h-4" />
            <span>Selesaikan Transaksi &amp; Cetak</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
