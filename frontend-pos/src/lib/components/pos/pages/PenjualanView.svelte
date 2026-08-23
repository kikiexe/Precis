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

<div class="flex-1 flex flex-col md:flex-row h-full overflow-hidden font-sans">
  <!-- Left Side: Catalog + Order Metadata Header -->
  <div class="flex-1 flex flex-col h-full overflow-hidden">
    <!-- Order Mode & Customer Bar -->
    <div class="bg-white border-b border-[#d9d9dd] px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 shrink-0">
      <!-- Order Type Radio Buttons -->
      <div class="flex items-center gap-1 bg-[#eeece7]/60 p-1 rounded-full border border-[#d9d9dd]">
        <button
          type="button"
          onclick={() => onSetOrderType('DINE_IN')}
          class={`px-3.5 py-1.5 text-xs font-medium rounded-full flex items-center gap-1.5 cursor-pointer transition-all ${
            orderType === 'DINE_IN' ? 'bg-[#17171c] text-white font-medium shadow-none' : 'text-[#616161] hover:text-[#212121]'
          }`}
        >
          <Utensils class="w-3.5 h-3.5" />
          <span>Dine In</span>
        </button>

        <button
          type="button"
          onclick={() => onSetOrderType('TAKE_AWAY')}
          class={`px-3.5 py-1.5 text-xs font-medium rounded-full flex items-center gap-1.5 cursor-pointer transition-all ${
            orderType === 'TAKE_AWAY' ? 'bg-[#17171c] text-white font-medium shadow-none' : 'text-[#616161] hover:text-[#212121]'
          }`}
        >
          <ShoppingBag class="w-3.5 h-3.5" />
          <span>Take Away</span>
        </button>

        <button
          type="button"
          onclick={() => onSetOrderType('DELIVERY')}
          class={`px-3.5 py-1.5 text-xs font-medium rounded-full flex items-center gap-1.5 cursor-pointer transition-all ${
            orderType === 'DELIVERY' ? 'bg-[#17171c] text-white font-medium shadow-none' : 'text-[#616161] hover:text-[#212121]'
          }`}
        >
          <Truck class="w-3.5 h-3.5" />
          <span>Delivery</span>
        </button>
      </div>

      <!-- Customer Name & Table Number Inputs -->
      <div class="flex items-center gap-2 text-xs">
        {#if orderType === 'DINE_IN'}
          <div class="flex items-center gap-1.5">
            <span class="text-[#75758a] font-medium">Meja:</span>
            <input
              type="text"
              value={tableNumber}
              oninput={(e) => onSetTableNumber((e.target as HTMLInputElement).value)}
              placeholder="e.g. 04"
              class="w-16 bg-[#eeece7]/40 border border-[#d9d9dd] rounded-full px-2.5 py-1 font-mono text-center font-medium text-[#212121] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>
        {/if}

        <div class="flex items-center gap-1.5">
          <span class="text-[#75758a] font-medium">Pelanggan:</span>
          <input
            type="text"
            value={customerName}
            oninput={(e) => onSetCustomerName((e.target as HTMLInputElement).value)}
            placeholder="Nama Pemesan (Opsional)"
            class="w-44 bg-[#eeece7]/40 border border-[#d9d9dd] rounded-full px-3 py-1 text-xs text-[#212121] placeholder-[#93939f] focus:border-[#17171c] focus:outline-hidden"
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
