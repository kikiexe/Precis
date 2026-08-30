<script lang="ts">
  import type { Product, Category, CartItem, OrderType, TaxSettings } from '../../../types/pos';
  import ProductCatalog from '../ProductCatalog.svelte';
  import OrderCart from '../OrderCart.svelte';

  interface Props {
    categories: Category[];
    products: Product[];
    selectedCategoryId: string;
    cartItems: CartItem[];
    discountPercent: number;
    discountNominal: number;
    orderType?: OrderType;
    customerName?: string;
    openBillsCount?: number;
    taxSettings?: TaxSettings | null;
    onSelectCategory: (id: string) => void;
    onAddToCart: (product: Product) => void;
    onUpdateQuantity: (productId: string, delta: number) => void;
    onUpdateNotes: (productId: string, notes: string) => void;
    onRemoveItem: (productId: string) => void;
    onClearCart: () => void;
    onSetDiscountPercent: (percent: number) => void;
    onSetDiscountNominal?: (nominal: number) => void;
    onSetOrderType: (type: OrderType) => void;
    onSetCustomerName: (name: string) => void;
    onSaveOpenBill?: () => void;
    onOpenBillsModal?: () => void;
    onOpenPaymentModal: () => void;
    onEditItemModifiers?: (item: CartItem) => void;
  }

  let {
    categories,
    products,
    selectedCategoryId,
    cartItems,
    discountPercent,
    discountNominal,
    orderType = 'DINE_IN',
    customerName = '',
    openBillsCount = 0,
    taxSettings = null,
    onSelectCategory,
    onAddToCart,
    onUpdateQuantity,
    onUpdateNotes,
    onRemoveItem,
    onClearCart,
    onSetDiscountPercent,
    onSetDiscountNominal,
    onSetOrderType,
    onSetCustomerName,
    onSaveOpenBill,
    onOpenBillsModal,
    onOpenPaymentModal,
    onEditItemModifiers,
  }: Props = $props();
</script>

<div class="flex h-full flex-1 flex-col overflow-hidden font-sans md:flex-row">
  <!-- Left Side: Product Catalog Component -->
  <ProductCatalog {categories} {products} {selectedCategoryId} {onSelectCategory} {onAddToCart} />

  <!-- Right Side: Order Cart Panel -->
  <OrderCart
    items={cartItems}
    {discountPercent}
    {discountNominal}
    {orderType}
    {customerName}
    {openBillsCount}
    {taxSettings}
    {onUpdateQuantity}
    {onUpdateNotes}
    {onRemoveItem}
    {onClearCart}
    {onSetDiscountPercent}
    {onSetDiscountNominal}
    {onSetOrderType}
    {onSetCustomerName}
    {onSaveOpenBill}
    {onOpenBillsModal}
    {onOpenPaymentModal}
    {onEditItemModifiers}
  />
</div>
