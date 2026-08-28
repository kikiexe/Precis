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

  let isSufficient = $derived(selectedMethod !== 'CASH' || cashTendered >= finalAmount);

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
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-[#17171c]/40 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="flex max-h-[90vh] w-full max-w-xl flex-col rounded-[22px] border border-[#d9d9dd] bg-white p-6 shadow-none"
    >
      <!-- Header -->
      <div class="mb-5 flex items-center justify-between border-b border-[#d9d9dd] pb-4">
        <div>
          <h2 class="text-xl font-medium tracking-tight text-[#212121]">Pilih Metode Pembayaran</h2>
          <p class="mt-0.5 font-mono text-xs text-[#616161]">
            Kasir: {activeCashier ? activeCashier.name : 'Tim Kasir Outlet'} • Total {formatCurrency(
              finalAmount
            )}
          </p>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="cursor-pointer p-1 text-[#93939f] hover:text-[#212121]"
        >
          <X class="h-5 w-5" />
        </button>
      </div>

      <!-- Payment Method Tabs -->
      <div class="mb-5 grid grid-cols-3 gap-2.5">
        <button
          type="button"
          onclick={() => (selectedMethod = 'CASH')}
          class={`flex cursor-pointer flex-col items-center gap-1.5 rounded-[16px] border px-3 py-3.5 text-center transition-all ${
            selectedMethod === 'CASH'
              ? 'border-[#17171c] bg-[#17171c] font-medium text-white shadow-none'
              : 'border-[#d9d9dd] bg-[#eeece7]/40 text-[#616161] hover:bg-[#eeece7] hover:text-[#212121]'
          }`}
        >
          <Banknote class="h-5 w-5" />
          <span class="text-xs">Uang Tunai (Cash)</span>
        </button>

        <button
          type="button"
          onclick={() => (selectedMethod = 'QRIS')}
          class={`flex cursor-pointer flex-col items-center gap-1.5 rounded-[16px] border px-3 py-3.5 text-center transition-all ${
            selectedMethod === 'QRIS'
              ? 'border-[#17171c] bg-[#17171c] font-medium text-white shadow-none'
              : 'border-[#d9d9dd] bg-[#eeece7]/40 text-[#616161] hover:bg-[#eeece7] hover:text-[#212121]'
          }`}
        >
          <QrCode class="h-5 w-5" />
          <span class="text-xs">QRIS Dinamis</span>
        </button>

        <button
          type="button"
          onclick={() => (selectedMethod = 'EDC')}
          class={`flex cursor-pointer flex-col items-center gap-1.5 rounded-[16px] border px-3 py-3.5 text-center transition-all ${
            selectedMethod === 'EDC'
              ? 'border-[#17171c] bg-[#17171c] font-medium text-white shadow-none'
              : 'border-[#d9d9dd] bg-[#eeece7]/40 text-[#616161] hover:bg-[#eeece7] hover:text-[#212121]'
          }`}
        >
          <CreditCard class="h-5 w-5" />
          <span class="text-xs">Mesin EDC (Kartu)</span>
        </button>
      </div>

      <!-- Payment Body Area -->
      <div class="mb-5 flex-1 overflow-y-auto">
        {#if selectedMethod === 'CASH'}
          <div class="space-y-4 rounded-[16px] border border-[#d9d9dd] bg-[#eeece7]/30 p-4">
            <!-- Cash Input -->
            <div>
              <label for="cash-input" class="mb-1.5 block font-mono text-xs text-[#616161]">
                Nominal Uang Diterima dari Pelanggan:
              </label>
              <div class="relative">
                <span
                  class="absolute top-1/2 left-3.5 -translate-y-1/2 font-mono text-base font-medium text-[#212121]"
                >
                  Rp
                </span>
                <input
                  id="cash-input"
                  type="number"
                  bind:value={cashTendered}
                  step="1000"
                  class="w-full rounded-[12px] border border-[#d9d9dd] bg-white py-3 pr-4 pl-12 font-mono text-lg font-medium text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
                />
              </div>
            </div>

            <!-- Denomination Shortcuts -->
            <div>
              <div class="mb-1.5 font-mono text-[11px] text-[#75758a]">Pilihan Nominal Cepat:</div>
              <div class="flex flex-wrap gap-2">
                {#each cashShortcuts as cut}
                  {@const val = cut.getValue(finalAmount)}
                  <button
                    type="button"
                    onclick={() => handleQuickCash(val)}
                    class="cursor-pointer rounded-full border border-[#d9d9dd] bg-white px-3.5 py-1.5 font-mono text-xs font-medium text-[#212121] transition-all hover:border-[#17171c]"
                  >
                    {cut.label}
                  </button>
                {/each}
              </div>
            </div>

            <!-- Kembalian / Change calculation -->
            <div class="flex items-center justify-between border-t border-[#d9d9dd] pt-3">
              <span class="text-sm font-medium text-[#616161]">Kembalian Pelanggan:</span>
              <span
                class={`font-mono text-xl font-medium ${cashTendered >= finalAmount ? 'text-[#003c33]' : 'text-[#b30000]'}`}
              >
                {formatCurrency(changeAmount)}
              </span>
            </div>

            {#if !isSufficient}
              <div
                class="flex items-center gap-2 rounded-[12px] bg-[#ffad9b]/15 p-3 text-xs font-medium text-[#b30000]"
              >
                <AlertTriangle class="h-4 w-4 shrink-0" />
                <span>Uang yang dimasukkan kurang {formatCurrency(finalAmount - cashTendered)}</span
                >
              </div>
            {/if}
          </div>
        {:else if selectedMethod === 'QRIS'}
          <div
            class="space-y-3 rounded-[16px] border border-[#d9d9dd] bg-[#eeece7]/30 p-6 text-center"
          >
            <div
              class="mx-auto flex h-44 w-44 flex-col items-center justify-center rounded-[16px] border border-[#d9d9dd] bg-white p-3 shadow-none"
            >
              <!-- QR Simulator -->
              <QrCode class="h-32 w-32 text-[#212121]" />
              <span class="mt-1 font-mono text-[10px] text-[#75758a]">QRIS NATIONAL STANDARDS</span>
            </div>
            <div class="font-mono text-xs text-[#616161]">
              Scan melalui BCA, GoPay, OVO, ShopeePay, atau DANA
            </div>
            <div class="font-mono text-lg font-medium text-[#17171c]">
              {formatCurrency(finalAmount)}
            </div>
          </div>
        {:else if selectedMethod === 'EDC' || selectedMethod === 'TRANSFER'}
          <div class="space-y-3 rounded-[16px] border border-[#d9d9dd] bg-[#eeece7]/30 p-5 text-xs">
            <div class="flex items-center gap-2.5">
              <div
                class="flex h-8 w-8 items-center justify-center rounded-xl bg-zinc-900 text-white"
              >
                <CreditCard class="h-4 w-4" />
              </div>
              <div>
                <div class="text-sm font-bold text-[#212121]">Pembayaran Mesin EDC</div>
                <div class="text-[11px] text-[#75758a]">Debit / Kartu Kredit / Contactless Tap</div>
              </div>
            </div>

            <div
              class="space-y-1.5 rounded-[12px] border border-[#d9d9dd] bg-white p-3.5 font-mono"
            >
              <div class="flex justify-between text-[#616161]">
                <span>Total Tagihan EDC:</span>
                <span class="text-base font-bold text-[#17171c]">{formatCurrency(finalAmount)}</span
                >
              </div>
              <div class="text-[11px] text-[#75758a]">
                Gesek, masukkan chip, atau tap kartu pelanggan pada terminal EDC kasir.
              </div>
            </div>

            <div
              class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-2.5 text-[11px] text-emerald-800"
            >
              <Check class="h-4 w-4 shrink-0 text-emerald-600" />
              <span
                >Pastikan slip transaksi EDC sudah tercetak Approved sebelum menyelesaikan pesanan.</span
              >
            </div>
          </div>
        {/if}
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-3 border-t border-[#d9d9dd] pt-4">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 cursor-pointer rounded-full border border-[#d9d9dd] bg-white py-3 text-xs font-medium text-[#616161] transition-all hover:bg-[#eeece7]/40"
        >
          Kembali
        </button>
        <button
          type="button"
          disabled={!isSufficient || isProcessing}
          onclick={handleProcessPayment}
          class={`flex flex-2 cursor-pointer items-center justify-center gap-2 rounded-full py-3 text-xs font-medium text-white shadow-none transition-all ${
            !isSufficient || isProcessing
              ? 'cursor-not-allowed bg-[#eeece7] text-[#93939f]'
              : 'bg-[#003c33] hover:bg-[#002822]'
          }`}
        >
          {#if isProcessing}
            <span>Memproses...</span>
          {:else}
            <Check class="h-4 w-4" />
            <span>Selesaikan Transaksi &amp; Cetak</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
