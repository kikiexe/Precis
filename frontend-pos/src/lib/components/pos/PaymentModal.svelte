<script lang="ts">
  import { Banknote, QrCode, CreditCard, X, Check, AlertTriangle } from 'lucide-svelte';
  import type { PaymentMethod, CartItem, OfflineOrder, CashierUser } from '../../types/pos';
  import { formatCurrency } from '../../services/printer-service';

  interface Props {
    isOpen: boolean;
    totalAmount: number;
    discountAmount: number;
    finalAmount: number;
    items: CartItem[];
    activeCashier?: CashierUser | null;
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
    activeCashier = null,
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
      cashier_user_id: activeCashier?.id || 'cashier-outlet',
      cashier_name: activeCashier?.name || 'Tim Kasir Outlet',
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
  <div class="fixed inset-0 z-50 bg-[#17171c]/40 backdrop-blur-xs flex items-center justify-center p-4 font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] max-w-xl w-full p-6 shadow-none flex flex-col max-h-[90vh]">
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-4 mb-5">
        <div>
          <h2 class="text-xl font-medium text-[#212121] tracking-tight">Pilih Metode Pembayaran</h2>
          <p class="text-xs text-[#616161] font-mono mt-0.5">Kasir: {activeCashier ? activeCashier.name : 'Tim Kasir Outlet'} • Total {formatCurrency(finalAmount)}</p>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="text-[#93939f] hover:text-[#212121] p-1 cursor-pointer"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Payment Method Tabs -->
      <div class="grid grid-cols-3 gap-2.5 mb-5">
        <button
          type="button"
          onclick={() => (selectedMethod = 'CASH')}
          class={`py-3.5 px-3 rounded-[16px] border text-center flex flex-col items-center gap-1.5 transition-all cursor-pointer ${
            selectedMethod === 'CASH'
              ? 'bg-[#17171c] text-white border-[#17171c] font-medium shadow-none'
              : 'bg-[#eeece7]/40 text-[#616161] border-[#d9d9dd] hover:text-[#212121] hover:bg-[#eeece7]'
          }`}
        >
          <Banknote class="w-5 h-5" />
          <span class="text-xs">Uang Tunai (Cash)</span>
        </button>

        <button
          type="button"
          onclick={() => (selectedMethod = 'QRIS')}
          class={`py-3.5 px-3 rounded-[16px] border text-center flex flex-col items-center gap-1.5 transition-all cursor-pointer ${
            selectedMethod === 'QRIS'
              ? 'bg-[#17171c] text-white border-[#17171c] font-medium shadow-none'
              : 'bg-[#eeece7]/40 text-[#616161] border-[#d9d9dd] hover:text-[#212121] hover:bg-[#eeece7]'
          }`}
        >
          <QrCode class="w-5 h-5" />
          <span class="text-xs">QRIS Dinamis</span>
        </button>

        <button
          type="button"
          onclick={() => (selectedMethod = 'EDC')}
          class={`py-3.5 px-3 rounded-[16px] border text-center flex flex-col items-center gap-1.5 transition-all cursor-pointer ${
            selectedMethod === 'EDC'
              ? 'bg-[#17171c] text-white border-[#17171c] font-medium shadow-none'
              : 'bg-[#eeece7]/40 text-[#616161] border-[#d9d9dd] hover:text-[#212121] hover:bg-[#eeece7]'
          }`}
        >
          <CreditCard class="w-5 h-5" />
          <span class="text-xs">Mesin EDC (Kartu)</span>
        </button>
      </div>

      <!-- Payment Body Area -->
      <div class="flex-1 overflow-y-auto mb-5">
        {#if selectedMethod === 'CASH'}
          <div class="space-y-4 bg-[#eeece7]/30 p-4 rounded-[16px] border border-[#d9d9dd]">
            <!-- Cash Input -->
            <div>
              <label for="cash-input" class="block text-xs font-mono text-[#616161] mb-1.5">
                Nominal Uang Diterima dari Pelanggan:
              </label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-mono text-base font-medium text-[#212121]">
                  Rp
                </span>
                <input
                  id="cash-input"
                  type="number"
                  bind:value={cashTendered}
                  step="1000"
                  class="w-full bg-white border border-[#d9d9dd] rounded-[12px] pl-12 pr-4 py-3 text-lg font-mono font-medium text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
                />
              </div>
            </div>

            <!-- Denomination Shortcuts -->
            <div>
              <div class="text-[11px] font-mono text-[#75758a] mb-1.5">Pilihan Nominal Cepat:</div>
              <div class="flex flex-wrap gap-2">
                {#each cashShortcuts as cut}
                  {@const val = cut.getValue(finalAmount)}
                  <button
                    type="button"
                    onclick={() => handleQuickCash(val)}
                    class="px-3.5 py-1.5 bg-white border border-[#d9d9dd] hover:border-[#17171c] rounded-full text-xs font-mono font-medium text-[#212121] cursor-pointer transition-all"
                  >
                    {cut.label}
                  </button>
                {/each}
              </div>
            </div>

            <!-- Kembalian / Change calculation -->
            <div class="pt-3 border-t border-[#d9d9dd] flex items-center justify-between">
              <span class="text-sm font-medium text-[#616161]">Kembalian Pelanggan:</span>
              <span class={`font-mono text-xl font-medium ${cashTendered >= finalAmount ? 'text-[#003c33]' : 'text-[#b30000]'}`}>
                {formatCurrency(changeAmount)}
              </span>
            </div>

            {#if !isSufficient}
              <div class="flex items-center gap-2 text-xs text-[#b30000] font-medium bg-[#ffad9b]/15 p-3 rounded-[12px]">
                <AlertTriangle class="w-4 h-4 shrink-0" />
                <span>Uang yang dimasukkan kurang {formatCurrency(finalAmount - cashTendered)}</span>
              </div>
            {/if}
          </div>
        {:else if selectedMethod === 'QRIS'}
          <div class="text-center p-6 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[16px] space-y-3">
            <div class="w-44 h-44 bg-white p-3 border border-[#d9d9dd] rounded-[16px] mx-auto shadow-none flex flex-col items-center justify-center">
              <!-- QR Simulator -->
              <QrCode class="w-32 h-32 text-[#212121]" />
              <span class="text-[10px] font-mono text-[#75758a] mt-1">QRIS NATIONAL STANDARDS</span>
            </div>
            <div class="text-xs text-[#616161] font-mono">
              Scan melalui BCA, GoPay, OVO, ShopeePay, atau DANA
            </div>
            <div class="text-lg font-medium font-mono text-[#17171c]">
              {formatCurrency(finalAmount)}
            </div>
          </div>
        {:else if selectedMethod === 'EDC' || selectedMethod === 'TRANSFER'}
          <div class="p-5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[16px] space-y-3 text-xs">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-zinc-900 text-white flex items-center justify-center">
                <CreditCard class="w-4 h-4" />
              </div>
              <div>
                <div class="text-sm font-bold text-[#212121]">Pembayaran Mesin EDC</div>
                <div class="text-[11px] text-[#75758a]">Debit / Kartu Kredit / Contactless Tap</div>
              </div>
            </div>

            <div class="p-3.5 bg-white border border-[#d9d9dd] rounded-[12px] space-y-1.5 font-mono">
              <div class="flex justify-between text-[#616161]">
                <span>Total Tagihan EDC:</span>
                <span class="text-base font-bold text-[#17171c]">{formatCurrency(finalAmount)}</span>
              </div>
              <div class="text-[11px] text-[#75758a]">
                Gesek, masukkan chip, atau tap kartu pelanggan pada terminal EDC kasir.
              </div>
            </div>

            <div class="p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-[11px] flex items-center gap-2">
              <Check class="w-4 h-4 shrink-0 text-emerald-600" />
              <span>Pastikan slip transaksi EDC sudah tercetak Approved sebelum menyelesaikan pesanan.</span>
            </div>
          </div>
        {/if}
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-3 border-t border-[#d9d9dd] pt-4">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-3 bg-white hover:bg-[#eeece7]/40 text-[#616161] font-medium text-xs border border-[#d9d9dd] rounded-full cursor-pointer transition-all"
        >
          Kembali
        </button>
        <button
          type="button"
          disabled={!isSufficient || isProcessing}
          onclick={handleProcessPayment}
          class={`flex-2 py-3 text-white font-medium text-xs rounded-full flex items-center justify-center gap-2 cursor-pointer transition-all shadow-none ${
            !isSufficient || isProcessing
              ? 'bg-[#eeece7] text-[#93939f] cursor-not-allowed'
              : 'bg-[#003c33] hover:bg-[#002822]'
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
