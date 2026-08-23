<script lang="ts">
  import { Trash2, Plus, Minus, Tag, CreditCard, ShoppingCart, AlertCircle } from 'lucide-svelte';
  import type { CartItem, CashierUser } from '../../types/pos';
  import { formatCurrency } from '../../services/printer-service';

  interface Props {
    items: CartItem[];
    activeCashier: CashierUser | null;
    discountPercent: number;
    discountNominal: number;
    onUpdateQuantity: (productId: string, delta: number) => void;
    onUpdateNotes: (productId: string, notes: string) => void;
    onRemoveItem: (productId: string) => void;
    onClearCart: () => void;
    onSetDiscountPercent: (percent: number) => void;
    onOpenPaymentModal: () => void;
    onOpenPinModal: () => void;
  }

  let {
    items = [],
    activeCashier,
    discountPercent = 0,
    discountNominal = 0,
    onUpdateQuantity,
    onUpdateNotes,
    onRemoveItem,
    onClearCart,
    onSetDiscountPercent,
    onOpenPaymentModal,
    onOpenPinModal,
  }: Props = $props();

  let subtotal = $derived(
    items.reduce((sum, item) => sum + item.unit_price * item.quantity, 0)
  );

  let calculatedDiscount = $derived(
    discountPercent > 0
      ? Math.round((subtotal * discountPercent) / 100)
      : discountNominal
  );

  let finalTotal = $derived(Math.max(0, subtotal - calculatedDiscount));

  let totalItemCount = $derived(
    items.reduce((sum, item) => sum + item.quantity, 0)
  );
</script>

<aside class="w-80 md:w-96 bg-white border-l border-[#e0e0e0] flex flex-col h-full shrink-0 select-none shadow-sm">
  <!-- Header: Cart Title & Cashier Info -->
  <div class="p-3.5 border-b border-[#e0e0e0] flex items-center justify-between bg-[#f4f4f4]">
    <div class="flex items-center gap-2">
      <ShoppingCart class="w-4 h-4 text-[#0f62fe]" />
      <span class="font-bold text-sm text-[#161616]">Pesanan Baru</span>
      <span class="text-xs font-mono bg-[#e0e0e0] text-[#161616] px-1.5 py-0.5 font-semibold">
        {totalItemCount}
      </span>
    </div>

    {#if items.length > 0}
      <button
        type="button"
        onclick={onClearCart}
        class="text-xs font-mono text-[#da1e28] hover:underline flex items-center gap-1 cursor-pointer"
      >
        <Trash2 class="w-3 h-3" />
        <span>Batal</span>
      </button>
    {/if}
  </div>

  <!-- Cashier Warning Banner if no cashier active -->
  {#if !activeCashier}
    <div class="p-3 bg-[#da1e28]/10 border-b border-[#da1e28]/20 flex items-center justify-between text-xs">
      <div class="flex items-center gap-1.5 text-[#da1e28]">
        <AlertCircle class="w-4 h-4 shrink-0" />
        <span>Kasir belum login PIN</span>
      </div>
      <button
        type="button"
        onclick={onOpenPinModal}
        class="bg-[#da1e28] text-white px-2 py-1 font-semibold text-[11px]"
      >
        Input PIN
      </button>
    </div>
  {/if}

  <!-- Order Items List -->
  <div class="flex-1 overflow-y-auto p-3 space-y-2.5">
    {#if items.length === 0}
      <div class="h-64 flex flex-col items-center justify-center text-center text-[#8c8c8c]">
        <ShoppingCart class="w-12 h-12 mb-2 opacity-30 text-[#8c8c8c]" />
        <p class="text-sm font-medium text-[#525252]">Keranjang masih kosong</p>
        <p class="text-xs text-[#8c8c8c] mt-0.5">Pilih menu dari katalog di sebelah kiri.</p>
      </div>
    {:else}
      {#each items as item (item.product.id)}
        <div class="bg-[#f4f4f4] border border-[#e0e0e0] p-2.5 space-y-2">
          <!-- Title & Subtotal -->
          <div class="flex items-start justify-between gap-2">
            <div class="flex-1">
              <div class="font-medium text-xs text-[#161616] leading-tight">
                {item.product.name}
              </div>
              <div class="font-mono text-[11px] text-[#525252] mt-0.5">
                {formatCurrency(item.unit_price)}
              </div>
            </div>
            <div class="font-mono text-xs font-bold text-[#161616]">
              {formatCurrency(item.unit_price * item.quantity)}
            </div>
          </div>

          <!-- Notes Input -->
          <div>
            <input
              type="text"
              value={item.notes}
              placeholder="Catatan (e.g. Less ice, no sugar)..."
              oninput={(e) => onUpdateNotes(item.product.id, (e.target as HTMLInputElement).value)}
              class="w-full text-[11px] bg-white border border-[#e0e0e0] px-2 py-1 focus:border-[#0f62fe] focus:outline-none"
            />
          </div>

          <!-- Quantity Stepper -->
          <div class="flex items-center justify-between pt-1">
            <button
              type="button"
              onclick={() => onRemoveItem(item.product.id)}
              class="text-[#da1e28] text-[11px] hover:underline flex items-center gap-1 cursor-pointer"
            >
              <Trash2 class="w-3 h-3" />
              <span>Hapus</span>
            </button>

            <div class="flex items-center gap-2">
              <button
                type="button"
                onclick={() => onUpdateQuantity(item.product.id, -1)}
                class="w-7 h-7 bg-white border border-[#e0e0e0] hover:bg-[#e0e0e0] text-[#161616] flex items-center justify-center font-bold text-sm cursor-pointer transition-colors active:scale-95"
              >
                <Minus class="w-3 h-3" />
              </button>
              <span class="font-mono text-xs font-bold w-6 text-center text-[#161616]">
                {item.quantity}
              </span>
              <button
                type="button"
                onclick={() => onUpdateQuantity(item.product.id, 1)}
                class="w-7 h-7 bg-[#0f62fe] hover:bg-[#0050e6] text-white flex items-center justify-center font-bold text-sm cursor-pointer transition-colors active:scale-95"
              >
                <Plus class="w-3 h-3" />
              </button>
            </div>
          </div>
        </div>
      {/each}
    {/if}
  </div>

  <!-- Bottom Panel: Discounts, Summary & Checkout Button -->
  <div class="border-t border-[#e0e0e0] bg-[#f4f4f4] p-3.5 space-y-3 shrink-0">
    <!-- Quick Discount Selector -->
    <div>
      <div class="text-[11px] font-mono text-[#525252] flex items-center gap-1 mb-1.5">
        <Tag class="w-3 h-3 text-[#0f62fe]" />
        <span>Diskon Promo</span>
      </div>
      <div class="grid grid-cols-4 gap-1.5">
        {#each [0, 5, 10, 20] as pct}
          <button
            type="button"
            onclick={() => onSetDiscountPercent(pct)}
            class={`py-1 text-[11px] font-mono border transition-colors cursor-pointer ${
              discountPercent === pct && discountNominal === 0
                ? 'bg-[#0f62fe] text-white border-[#0f62fe] font-bold'
                : 'bg-white text-[#525252] border-[#e0e0e0] hover:bg-[#e0e0e0]'
            }`}
          >
            {pct === 0 ? '0%' : `${pct}%`}
          </button>
        {/each}
      </div>
    </div>

    <!-- Pricing Breakdown -->
    <div class="space-y-1 text-xs border-t border-[#e0e0e0] pt-2">
      <div class="flex justify-between text-[#525252]">
        <span>Subtotal</span>
        <span class="font-mono">{formatCurrency(subtotal)}</span>
      </div>
      {#if calculatedDiscount > 0}
        <div class="flex justify-between text-[#da1e28]">
          <span>Diskon ({discountPercent > 0 ? `${discountPercent}%` : 'Nominal'})</span>
          <span class="font-mono">-{formatCurrency(calculatedDiscount)}</span>
        </div>
      {/if}
      <div class="flex justify-between text-base font-bold text-[#161616] pt-1.5 border-t border-[#e0e0e0]">
        <span>Total Bayar</span>
        <span class="font-mono text-[#0f62fe]">{formatCurrency(finalTotal)}</span>
      </div>
    </div>

    <!-- Checkout Action Button -->
    <button
      type="button"
      disabled={items.length === 0}
      onclick={onOpenPaymentModal}
      class={`w-full py-3.5 text-sm font-semibold flex items-center justify-center gap-2 transition-all cursor-pointer shadow-xs ${
        items.length === 0
          ? 'bg-[#e0e0e0] text-[#8c8c8c] cursor-not-allowed'
          : 'bg-[#0f62fe] hover:bg-[#0050e6] text-white active:scale-[0.99]'
      }`}
    >
      <CreditCard class="w-4 h-4" />
      <span>Proses Bayar ({formatCurrency(finalTotal)})</span>
    </button>
  </div>
</aside>
