<script lang="ts">
  import { Trash2, Plus, Minus, Tag, CreditCard, ShoppingCart } from 'lucide-svelte';
  import type { CartItem } from '../../types/pos';
  import { formatCurrency } from '../../services/printer-service';

  interface Props {
    items: CartItem[];
    discountPercent: number;
    discountNominal: number;
    onUpdateQuantity: (productId: string, delta: number) => void;
    onUpdateNotes: (productId: string, notes: string) => void;
    onRemoveItem: (productId: string) => void;
    onClearCart: () => void;
    onSetDiscountPercent: (percent: number) => void;
    onOpenPaymentModal: () => void;
  }

  let {
    items = [],
    discountPercent = 0,
    discountNominal = 0,
    onUpdateQuantity,
    onUpdateNotes,
    onRemoveItem,
    onClearCart,
    onSetDiscountPercent,
    onOpenPaymentModal,
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

<aside class="w-72 sm:w-80 md:w-88 lg:w-96 bg-white border-l border-[#d9d9dd] flex flex-col h-full shrink-0 select-none shadow-none font-sans">
  <!-- Header: Cart Title & Items Count -->
  <div class="p-4 border-b border-[#d9d9dd] flex items-center justify-between bg-[#eeece7]/40">
    <div class="flex items-center gap-2">
      <ShoppingCart class="w-4 h-4 text-[#1863dc]" />
      <span class="font-medium text-sm text-[#212121]">Pesanan Baru</span>
      <span class="text-xs font-mono bg-[#eeece7] text-[#212121] px-2 py-0.5 rounded-full font-medium">
        {totalItemCount}
      </span>
    </div>

    {#if items.length > 0}
      <button
        type="button"
        onclick={onClearCart}
        class="text-xs font-mono text-[#b30000] hover:underline flex items-center gap-1 cursor-pointer"
      >
        <Trash2 class="w-3.5 h-3.5" />
        <span>Batal</span>
      </button>
    {/if}
  </div>

  <!-- Order Items List -->
  <div class="flex-1 overflow-y-auto p-3.5 space-y-3">
    {#if items.length === 0}
      <div class="h-64 flex flex-col items-center justify-center text-center text-[#93939f]">
        <ShoppingCart class="w-10 h-10 mb-2 opacity-30 text-[#93939f]" />
        <p class="text-sm font-medium text-[#212121]">Keranjang masih kosong</p>
        <p class="text-xs text-[#75758a] mt-0.5">Pilih menu dari katalog di sebelah kiri.</p>
      </div>
    {:else}
      {#each items as item (item.product.id)}
        <div class="bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[16px] p-3 space-y-2.5">
          <!-- Title & Subtotal -->
          <div class="flex items-start justify-between gap-2">
            <div class="flex-1">
              <div class="font-medium text-xs text-[#212121] leading-tight">
                {item.product.name}
              </div>
              <div class="font-mono text-[11px] text-[#75758a] mt-0.5">
                {formatCurrency(item.unit_price)}
              </div>
            </div>
            <div class="font-mono text-xs font-medium text-[#212121]">
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
              class="w-full text-[11px] bg-white border border-[#d9d9dd] rounded-[8px] px-2.5 py-1 text-[#212121] placeholder-[#93939f] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>

          <!-- Quantity Stepper -->
          <div class="flex items-center justify-between pt-1">
            <button
              type="button"
              onclick={() => onRemoveItem(item.product.id)}
              class="text-[#b30000] text-[11px] hover:underline flex items-center gap-1 cursor-pointer"
            >
              <Trash2 class="w-3 h-3" />
              <span>Hapus</span>
            </button>

            <div class="flex items-center gap-2">
              <button
                type="button"
                onclick={() => onUpdateQuantity(item.product.id, -1)}
                class="w-7 h-7 rounded-full bg-white border border-[#d9d9dd] hover:bg-[#eeece7] text-[#212121] flex items-center justify-center font-medium text-sm cursor-pointer transition-all active:scale-95"
              >
                <Minus class="w-3 h-3" />
              </button>
              <span class="font-mono text-xs font-medium w-6 text-center text-[#212121]">
                {item.quantity}
              </span>
              <button
                type="button"
                onclick={() => onUpdateQuantity(item.product.id, 1)}
                class="w-7 h-7 rounded-full bg-[#17171c] hover:bg-[#000000] text-white flex items-center justify-center font-medium text-sm cursor-pointer transition-all active:scale-95"
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
  <div class="border-t border-[#d9d9dd] bg-[#eeece7]/40 p-4 space-y-3.5 shrink-0">
    <!-- Quick Discount Selector -->
    <div>
      <div class="text-[11px] text-[#75758a] flex items-center gap-1.5 mb-1.5 font-medium">
        <Tag class="w-3 h-3 text-[#1863dc]" />
        <span>Diskon Promo</span>
      </div>
      <div class="grid grid-cols-4 gap-1.5">
        {#each [0, 5, 10, 20] as pct}
          <button
            type="button"
            onclick={() => onSetDiscountPercent(pct)}
            class={`py-1 text-[11px] font-mono rounded-full border transition-all cursor-pointer ${
              discountPercent === pct && discountNominal === 0
                ? 'bg-[#17171c] text-white border-[#17171c] font-medium shadow-none'
                : 'bg-white text-[#616161] border-[#d9d9dd] hover:bg-[#eeece7]'
            }`}
          >
            {pct === 0 ? '0%' : `${pct}%`}
          </button>
        {/each}
      </div>
    </div>

    <!-- Pricing Breakdown -->
    <div class="space-y-1.5 text-xs border-t border-[#d9d9dd] pt-2.5">
      <div class="flex justify-between text-[#616161]">
        <span>Subtotal</span>
        <span class="font-mono">{formatCurrency(subtotal)}</span>
      </div>
      {#if calculatedDiscount > 0}
        <div class="flex justify-between text-[#b30000]">
          <span>Diskon ({discountPercent > 0 ? `${discountPercent}%` : 'Nominal'})</span>
          <span class="font-mono">-{formatCurrency(calculatedDiscount)}</span>
        </div>
      {/if}
      <div class="flex justify-between text-base font-medium text-[#212121] pt-2 border-t border-[#d9d9dd]">
        <span>Total Bayar</span>
        <span class="font-mono text-[#17171c] font-semibold">{formatCurrency(finalTotal)}</span>
      </div>
    </div>

    <!-- Checkout Action Button -->
    <button
      type="button"
      disabled={items.length === 0}
      onclick={onOpenPaymentModal}
      class={`w-full py-3.5 text-xs font-medium rounded-full flex items-center justify-center gap-2 transition-all cursor-pointer shadow-none ${
        items.length === 0
          ? 'bg-[#eeece7] text-[#93939f] cursor-not-allowed'
          : 'bg-[#17171c] hover:bg-[#000000] text-white active:scale-[0.99]'
      }`}
    >
      <CreditCard class="w-4 h-4" />
      <span>Proses Bayar ({formatCurrency(finalTotal)})</span>
    </button>
  </div>
</aside>
