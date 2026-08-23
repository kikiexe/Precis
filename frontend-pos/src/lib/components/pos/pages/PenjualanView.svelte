<script lang="ts">
  import { Utensils, ShoppingBag, Truck } from 'lucide-svelte';
  import type { Product, Category, CartItem, CashierUser, OrderType } from '../../../types/pos';
  import ProductCatalog from '../ProductCatalog.svelte';
  import OrderCart from '../OrderCart.svelte';

  interface Props {
    categories: Category[];
    products: Product[];
    selectedCategoryId: string;
    cartItems: CartItem[];
    activeCashier: CashierUser | null;
    discountPercent: number;
    discountNominal: number;
    orderType: OrderType;
    customerName: string;
    tableNumber: string;
    onSelectCategory: (id: string) => void;
    onAddToCart: (product: Product) => void;
    onUpdateQuantity: (productId: string, delta: number) => void;
    onUpdateNotes: (productId: string, notes: string) => void;
    onRemoveItem: (productId: string) => void;
    onClearCart: () => void;
    onSetDiscountPercent: (percent: number) => void;
    onSetOrderType: (type: OrderType) => void;
    onSetCustomerName: (name: string) => void;
    onSetTableNumber: (table: string) => void;
    onOpenPaymentModal: () => void;
    onOpenPinModal: () => void;
  }

  let {
    categories,
    products,
    selectedCategoryId,
    cartItems,
    activeCashier,
    discountPercent,
    discountNominal,
    orderType = 'DINE_IN',
    customerName = '',
    tableNumber = '',
    onSelectCategory,
    onAddToCart,
    onUpdateQuantity,
    onUpdateNotes,
    onRemoveItem,
    onClearCart,
    onSetDiscountPercent,
    onSetOrderType,
    onSetCustomerName,
    onSetTableNumber,
    onOpenPaymentModal,
    onOpenPinModal,
  }: Props = $props();
</script>

<div class="flex-1 flex flex-col md:flex-row h-full overflow-hidden">
  <!-- Left Side: Catalog + Order Metadata Header -->
  <div class="flex-1 flex flex-col h-full overflow-hidden">
    <!-- Order Mode & Customer Bar (Flux Style) -->
    <div class="bg-white border-b border-[#e0e0e0] px-4 py-2 flex flex-wrap items-center justify-between gap-2 shrink-0">
      <!-- Order Type Radio Buttons -->
      <div class="flex items-center gap-1 bg-[#f4f4f4] p-1 border border-[#e0e0e0]">
        <button
          type="button"
          onclick={() => onSetOrderType('DINE_IN')}
          class={`px-3 py-1.5 text-xs font-medium flex items-center gap-1.5 cursor-pointer transition-colors ${
            orderType === 'DINE_IN' ? 'bg-[#0f62fe] text-white font-semibold shadow-xs' : 'text-[#525252] hover:text-[#161616]'
          }`}
        >
          <Utensils class="w-3.5 h-3.5" />
          <span>Dine In</span>
        </button>

        <button
          type="button"
          onclick={() => onSetOrderType('TAKE_AWAY')}
          class={`px-3 py-1.5 text-xs font-medium flex items-center gap-1.5 cursor-pointer transition-colors ${
            orderType === 'TAKE_AWAY' ? 'bg-[#0f62fe] text-white font-semibold shadow-xs' : 'text-[#525252] hover:text-[#161616]'
          }`}
        >
          <ShoppingBag class="w-3.5 h-3.5" />
          <span>Take Away</span>
        </button>

        <button
          type="button"
          onclick={() => onSetOrderType('DELIVERY')}
          class={`px-3 py-1.5 text-xs font-medium flex items-center gap-1.5 cursor-pointer transition-colors ${
            orderType === 'DELIVERY' ? 'bg-[#0f62fe] text-white font-semibold shadow-xs' : 'text-[#525252] hover:text-[#161616]'
          }`}
        >
          <Truck class="w-3.5 h-3.5" />
          <span>Delivery</span>
        </button>
      </div>

      <!-- Customer Name & Table Number Inputs -->
      <div class="flex items-center gap-2 text-xs">
        {#if orderType === 'DINE_IN'}
          <div class="flex items-center gap-1">
            <span class="font-mono text-[#525252]">Meja:</span>
            <input
              type="text"
              value={tableNumber}
              oninput={(e) => onSetTableNumber((e.target as HTMLInputElement).value)}
              placeholder="e.g. 04"
              class="w-16 bg-[#f4f4f4] border border-[#e0e0e0] px-2 py-1 font-mono text-center font-bold focus:border-[#0f62fe] focus:outline-none"
            />
          </div>
        {/if}

        <div class="flex items-center gap-1">
          <span class="font-mono text-[#525252]">Pelanggan:</span>
          <input
            type="text"
            value={customerName}
            oninput={(e) => onSetCustomerName((e.target as HTMLInputElement).value)}
            placeholder="Nama Pemesan (Opsional)"
            class="w-40 bg-[#f4f4f4] border border-[#e0e0e0] px-2 py-1 focus:border-[#0f62fe] focus:outline-none"
          />
        </div>
      </div>
    </div>

    <!-- Product Catalog Component -->
    <ProductCatalog
      {categories}
      {products}
      {selectedCategoryId}
      {onSelectCategory}
      {onAddToCart}
    />
  </div>

  <!-- Right Side: Order Cart Panel -->
  <OrderCart
    items={cartItems}
    {activeCashier}
    {discountPercent}
    {discountNominal}
    {onUpdateQuantity}
    {onUpdateNotes}
    {onRemoveItem}
    {onClearCart}
    {onSetDiscountPercent}
    {onOpenPaymentModal}
    {onOpenPinModal}
  />
</div>
