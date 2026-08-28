<script lang="ts">
  import {
    Trash2,
    Plus,
    Minus,
    Tag,
    CreditCard,
    ShoppingCart,
    Utensils,
    ShoppingBag,
    Truck,
    User,
    ChevronDown,
    Check,
    Bookmark,
    Printer,
  } from 'lucide-svelte';
  import type { CartItem, OrderType } from '../../types/pos';
  import { formatCurrency } from '../../services/printer-service';

  interface Props {
    items: CartItem[];
    discountPercent: number;
    discountNominal: number;
    orderType?: OrderType;
    customerName?: string;
    openBillsCount?: number;
    onUpdateQuantity: (productId: string, delta: number) => void;
    onUpdateNotes: (productId: string, notes: string) => void;
    onRemoveItem: (productId: string) => void;
    onClearCart: () => void;
    onSetDiscountPercent: (percent: number) => void;
    onSetDiscountNominal?: (nominal: number) => void;
    onSetOrderType?: (type: OrderType) => void;
    onSetCustomerName?: (name: string) => void;
    onSaveOpenBill?: () => void;
    onOpenBillsModal?: () => void;
    onOpenPaymentModal: () => void;
  }

  let {
    items = [],
    discountPercent = 0,
    discountNominal = 0,
    orderType = 'DINE_IN',
    customerName = '',
    openBillsCount = 0,
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
  }: Props = $props();

  // Order type dropdown state
  let isOrderTypeMenuOpen = $state(false);

  const orderTypeOptions: { value: OrderType; label: string; icon: typeof Utensils }[] = [
    { value: 'DINE_IN', label: 'Dine In', icon: Utensils },
    { value: 'TAKE_AWAY', label: 'Take Away', icon: ShoppingBag },
    { value: 'DELIVERY', label: 'Delivery', icon: Truck },
  ];

  let selectedOrderTypeOption = $derived(
    orderTypeOptions.find((o) => o.value === orderType) || orderTypeOptions[0]
  );

  // Discount management state (% vs Rp)
  let discountMode = $state<'PERCENT' | 'NOMINAL'>('PERCENT');
  let discountInputValue = $state<string>('');

  // Keep input value in sync when discount props change
  $effect(() => {
    if (discountNominal > 0) {
      discountMode = 'NOMINAL';
      discountInputValue = discountNominal.toString();
    } else if (discountPercent > 0) {
      discountMode = 'PERCENT';
      discountInputValue = discountPercent.toString();
    } else {
      discountInputValue = '';
    }
  });

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

  // --- Interactive Swipe Gestures (Left to Delete, Right to +1) ---
  let swipedItemId = $state<string | null>(null);
  let swipeOffset = $state<number>(0);
  let touchStartX = 0;
  let touchStartY = 0;
  let isSwiping = false;

  function handleTouchStart(e: TouchEvent | PointerEvent, id: string) {
    const clientX = 'touches' in e ? e.touches[0].clientX : (e as PointerEvent).clientX;
    const clientY = 'touches' in e ? e.touches[0].clientY : (e as PointerEvent).clientY;
    touchStartX = clientX;
    touchStartY = clientY;
    isSwiping = false;
    if (swipedItemId !== id) {
      swipedItemId = id;
      swipeOffset = 0;
    }
  }

  function handleTouchMove(e: TouchEvent | PointerEvent, _id: string) {
    const clientX = 'touches' in e ? e.touches[0].clientX : (e as PointerEvent).clientX;
    const clientY = 'touches' in e ? e.touches[0].clientY : (e as PointerEvent).clientY;
    const deltaX = clientX - touchStartX;
    const deltaY = clientY - touchStartY;

    if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 8) {
      isSwiping = true;
      // Clamp between -75px (left swipe: delete) and +65px (right swipe: add)
      swipeOffset = Math.max(-75, Math.min(65, deltaX));
    }
  }

  function handleTouchEnd(id: string) {
    if (isSwiping) {
      if (swipeOffset <= -45) {
        // Keep revealed at -65px so the delete button can be tapped
        swipeOffset = -65;
      } else if (swipeOffset >= 45) {
        // Increment quantity on right swipe
        onUpdateQuantity(id, 1);
        swipeOffset = 0;
        swipedItemId = null;
      } else {
        swipeOffset = 0;
        swipedItemId = null;
      }
    }
  }

  function resetSwipe() {
    swipedItemId = null;
    swipeOffset = 0;
  }

  function handleSwitchDiscountMode(mode: 'PERCENT' | 'NOMINAL') {
    discountMode = mode;
    discountInputValue = '';
    onSetDiscountPercent(0);
    onSetDiscountNominal?.(0);
  }

  function handleDiscountInput(e: Event) {
    const rawVal = (e.target as HTMLInputElement).value.replace(/[^0-9]/g, '');
    discountInputValue = rawVal;
    const num = parseInt(rawVal, 10) || 0;

    if (discountMode === 'PERCENT') {
      const clamped = Math.min(100, Math.max(0, num));
      onSetDiscountPercent(clamped);
      onSetDiscountNominal?.(0);
    } else {
      const clamped = Math.min(subtotal, Math.max(0, num));
      onSetDiscountNominal?.(clamped);
      onSetDiscountPercent(0);
    }
  }

  function handleClearDiscount() {
    discountInputValue = '';
    onSetDiscountPercent(0);
    onSetDiscountNominal?.(0);
  }
</script>

<aside class="w-72 sm:w-80 md:w-88 lg:w-96 bg-white border-l border-zinc-200 flex flex-col h-full shrink-0 select-none shadow-none font-sans">
  <!-- Header: Cart Title, Count & Open Bills shortcut -->
  <div class="px-3.5 py-3 border-b border-zinc-200 flex items-center justify-between bg-zinc-50/70">
    <div class="flex items-center gap-2">
      <ShoppingCart class="w-4 h-4 text-zinc-900" />
      <span class="font-semibold text-sm text-zinc-900">Pesanan Baru</span>
      <span class="text-xs font-mono bg-zinc-200/80 text-zinc-800 px-2 py-0.5 rounded-md font-semibold">
        {totalItemCount}
      </span>
    </div>

    <div class="flex items-center gap-2">
      <!-- Open Bills Shortcut Button -->
      {#if onOpenBillsModal}
        <button
          type="button"
          onclick={onOpenBillsModal}
          class={`px-2.5 py-1 text-[11px] font-medium rounded-lg border flex items-center gap-1.5 cursor-pointer transition-all ${
            openBillsCount > 0
              ? 'bg-amber-500/10 border-amber-500/30 text-amber-800 hover:bg-amber-500/20'
              : 'bg-zinc-100 border-zinc-200 text-zinc-600 hover:bg-zinc-200/70'
          }`}
        >
          <Bookmark class="w-3 h-3" />
          <span>Open Bills</span>
          {#if openBillsCount > 0}
            <span class="w-4 h-4 rounded-full bg-amber-600 text-white font-mono text-[9px] font-bold flex items-center justify-center">
              {openBillsCount}
            </span>
          {/if}
        </button>
      {/if}

      {#if items.length > 0}
        <button
          type="button"
          onclick={onClearCart}
          class="text-xs font-medium text-red-600 hover:text-red-700 hover:underline flex items-center gap-1 cursor-pointer transition-colors"
        >
          <Trash2 class="w-3.5 h-3.5" />
          <span>Batal</span>
        </button>
      {/if}
    </div>
  </div>

  <!-- Order Type (Compact Single-Selection Dropdown) & Customer Name Input -->
  <div class="p-3 border-b border-zinc-200 bg-white shrink-0 relative">
    <div class="flex items-center gap-2">
      <!-- Selected Order Type Dropdown -->
      <div class="relative shrink-0">
        <button
          type="button"
          onclick={() => (isOrderTypeMenuOpen = !isOrderTypeMenuOpen)}
          class="h-9 px-3 bg-zinc-100 hover:bg-zinc-200/70 border border-zinc-200/80 rounded-lg text-xs font-medium text-zinc-800 flex items-center gap-2 cursor-pointer transition-all active:scale-[0.98]"
        >
          <selectedOrderTypeOption.icon class="w-3.5 h-3.5 text-zinc-700" />
          <span>{selectedOrderTypeOption.label}</span>
          <ChevronDown class="w-3.5 h-3.5 text-zinc-400" />
        </button>

        {#if isOrderTypeMenuOpen}
          <!-- Backdrop to close dropdown -->
          <button
            type="button"
            tabindex="-1"
            onclick={() => (isOrderTypeMenuOpen = false)}
            class="fixed inset-0 z-20 cursor-default bg-transparent"
            aria-label="Tutup menu"
          ></button>

          <div class="absolute left-0 top-full mt-1.5 w-36 bg-white border border-zinc-200 rounded-xl shadow-lg z-30 py-1 overflow-hidden">
            {#each orderTypeOptions as opt}
              <button
                type="button"
                onclick={() => {
                  onSetOrderType?.(opt.value);
                  isOrderTypeMenuOpen = false;
                }}
                class={`w-full px-3 py-2 text-xs flex items-center justify-between text-left transition-colors cursor-pointer ${
                  orderType === opt.value
                    ? 'bg-zinc-900 text-white font-medium'
                    : 'text-zinc-700 hover:bg-zinc-100'
                }`}
              >
                <div class="flex items-center gap-2">
                  <opt.icon class="w-3.5 h-3.5" />
                  <span>{opt.label}</span>
                </div>
                {#if orderType === opt.value}
                  <Check class="w-3.5 h-3.5 text-white" />
                {/if}
              </button>
            {/each}
          </div>
        {/if}
      </div>

      <!-- Customer Name Input -->
      <div class="relative flex-1">
        <User class="w-3.5 h-3.5 text-zinc-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
        <input
          type="text"
          value={customerName}
          oninput={(e) => onSetCustomerName?.((e.target as HTMLInputElement).value)}
          placeholder="Nama Pemesan (Opsional)..."
          class="w-full h-9 bg-zinc-50 border border-zinc-200/80 rounded-lg pl-8 pr-3 text-xs text-zinc-900 placeholder-zinc-400 focus:bg-white focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:outline-hidden transition-all"
        />
      </div>
    </div>
  </div>

  <!-- Order Items List: Unified Single Container with Swipe Gestures -->
  <div class="flex-1 overflow-y-auto p-3 space-y-2">
    {#if items.length === 0}
      <div class="h-64 flex flex-col items-center justify-center text-center text-zinc-400">
        <ShoppingCart class="w-9 h-9 mb-2 opacity-30 text-zinc-400" />
        <p class="text-sm font-medium text-zinc-800">Keranjang masih kosong</p>
        <p class="text-xs text-zinc-500 mt-0.5">Pilih menu dari katalog di sebelah kiri.</p>
      </div>
    {:else}
      <!-- Single Unified Container for all items -->
      <div class="bg-white border border-zinc-200 rounded-xl overflow-hidden divide-y divide-zinc-100 shadow-2xs">
        {#each items as item (item.product.id)}
          {@const isItemSwiped = swipedItemId === item.product.id}
          {@const currentOffset = isItemSwiped ? swipeOffset : 0}

          <div class="relative overflow-hidden group select-none bg-white">
            <!-- Left Swipe Background Action: Delete -->
            <div class="absolute inset-y-0 right-0 w-18 bg-red-600 flex items-center justify-center">
              <button
                type="button"
                onclick={() => {
                  onRemoveItem(item.product.id);
                  resetSwipe();
                }}
                class="w-full h-full flex flex-col items-center justify-center text-white text-[10px] font-semibold gap-0.5 cursor-pointer hover:bg-red-700 active:scale-95 transition-all"
              >
                <Trash2 class="w-3.5 h-3.5" />
                <span>Hapus</span>
              </button>
            </div>

            <!-- Right Swipe Background Action: +1 Increment -->
            <div class="absolute inset-y-0 left-0 w-18 bg-emerald-600 flex items-center justify-center">
              <div class="flex flex-col items-center justify-center text-white text-[10px] font-semibold gap-0.5">
                <Plus class="w-3.5 h-3.5" />
                <span>+1 Menu</span>
              </div>
            </div>

            <!-- Foreground Item Row -->
            <div
              role="group"
              aria-label={item.product.name}
              class="relative bg-white p-3 space-y-1.5 transition-transform duration-100 ease-out"
              style="transform: translateX({currentOffset}px);"
              ontouchstart={(e) => handleTouchStart(e, item.product.id)}
              ontouchmove={(e) => handleTouchMove(e, item.product.id)}
              ontouchend={() => handleTouchEnd(item.product.id)}
              onpointerdown={(e) => handleTouchStart(e, item.product.id)}
              onpointermove={(e) => handleTouchMove(e, item.product.id)}
              onpointerup={() => handleTouchEnd(item.product.id)}
            >
              <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-xs text-zinc-900 leading-snug line-clamp-2">
                    {item.product.name}
                  </div>
                  <div class="font-mono text-[11px] text-zinc-500 mt-0.5">
                    {formatCurrency(item.unit_price)}
                  </div>
                </div>

                <!-- Quantity Stepper & Row Subtotal -->
                <div class="flex items-center gap-2 shrink-0">
                  <div class="flex items-center gap-1 bg-zinc-100 rounded-lg p-0.5 border border-zinc-200/80">
                    <button
                      type="button"
                      onclick={() => onUpdateQuantity(item.product.id, -1)}
                      class="w-5.5 h-5.5 rounded-md bg-white hover:bg-zinc-200 text-zinc-800 flex items-center justify-center font-medium text-xs cursor-pointer transition-all active:scale-95 shadow-2xs"
                    >
                      <Minus class="w-2.5 h-2.5" />
                    </button>
                    <span class="font-mono text-xs font-semibold w-5 text-center text-zinc-900">
                      {item.quantity}
                    </span>
                    <button
                      type="button"
                      onclick={() => onUpdateQuantity(item.product.id, 1)}
                      class="w-5.5 h-5.5 rounded-md bg-zinc-900 hover:bg-black text-white flex items-center justify-center font-medium text-xs cursor-pointer transition-all active:scale-95 shadow-2xs"
                    >
                      <Plus class="w-2.5 h-2.5" />
                    </button>
                  </div>

                  <div class="font-mono text-xs font-semibold text-zinc-900 w-16 text-right">
                    {formatCurrency(item.unit_price * item.quantity)}
                  </div>
                </div>
              </div>

              <!-- Notes Input -->
              <div>
                <input
                  type="text"
                  value={item.notes}
                  placeholder="Catatan pesanan..."
                  oninput={(e) => onUpdateNotes(item.product.id, (e.target as HTMLInputElement).value)}
                  class="w-full text-[11px] bg-zinc-50 border border-zinc-200/70 rounded-md px-2.5 py-1 text-zinc-900 placeholder-zinc-400 focus:bg-white focus:border-zinc-900 focus:outline-hidden transition-all"
                />
              </div>
            </div>
          </div>
        {/each}
      </div>

      <!-- Quick swipe gesture hint -->
      <div class="text-[10px] font-mono text-zinc-400 text-center pt-1">
        💡 Geser kiri untuk hapus • Geser kanan untuk +1
      </div>
    {/if}
  </div>

  <!-- Bottom Panel: Dynamic Discounts, Summary & Checkout Button -->
  <div class="border-t border-zinc-200 bg-zinc-50/80 p-3.5 space-y-3 shrink-0">
    <!-- Dynamic Discount Input with % / Rp Mode Switch -->
    <div class="space-y-1.5">
      <div class="flex items-center justify-between text-xs">
        <span class="text-zinc-600 font-medium flex items-center gap-1.5">
          <Tag class="w-3.5 h-3.5 text-zinc-700" />
          <span>Diskon Pesanan</span>
        </span>
        {#if calculatedDiscount > 0}
          <span class="text-red-600 font-mono font-medium text-[11px]">
            -{formatCurrency(calculatedDiscount)} ({discountMode === 'PERCENT' ? `${discountPercent}%` : 'Rp'})
          </span>
        {/if}
      </div>

      <!-- Input Bar with Switcher -->
      <div class="flex items-center gap-1.5">
        <div class="flex bg-zinc-200/80 p-0.5 rounded-lg border border-zinc-300/70 shrink-0">
          <button
            type="button"
            onclick={() => handleSwitchDiscountMode('PERCENT')}
            class={`px-2 py-1 text-[11px] font-mono font-bold rounded-md transition-all cursor-pointer ${
              discountMode === 'PERCENT'
                ? 'bg-zinc-900 text-white shadow-xs'
                : 'text-zinc-600 hover:text-zinc-900'
            }`}
          >
            %
          </button>
          <button
            type="button"
            onclick={() => handleSwitchDiscountMode('NOMINAL')}
            class={`px-2 py-1 text-[11px] font-mono font-bold rounded-md transition-all cursor-pointer ${
              discountMode === 'NOMINAL'
                ? 'bg-zinc-900 text-white shadow-xs'
                : 'text-zinc-600 hover:text-zinc-900'
            }`}
          >
            Rp
          </button>
        </div>

        <div class="relative flex-1">
          <input
            type="text"
            inputmode="numeric"
            value={discountInputValue}
            oninput={handleDiscountInput}
            placeholder={discountMode === 'PERCENT' ? 'Ketik diskon persen (misal 15)...' : 'Ketik nominal diskon (misal 10000)...'}
            class="w-full h-8 bg-white border border-zinc-200 rounded-lg px-2.5 text-xs font-mono text-zinc-900 placeholder-zinc-400 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:outline-hidden transition-all"
          />
          {#if (discountMode === 'PERCENT' && discountPercent > 0) || (discountMode === 'NOMINAL' && discountNominal > 0)}
            <button
              type="button"
              onclick={handleClearDiscount}
              class="absolute right-2 top-1/2 -translate-y-1/2 text-xs font-mono text-zinc-400 hover:text-zinc-700 p-0.5 cursor-pointer"
            >
              ✕
            </button>
          {/if}
        </div>
      </div>
    </div>

    <!-- Pricing Breakdown -->
    <div class="space-y-1.5 text-xs border-t border-zinc-200 pt-2.5">
      <div class="flex justify-between text-zinc-500">
        <span>Subtotal</span>
        <span class="font-mono text-zinc-700">{formatCurrency(subtotal)}</span>
      </div>
      {#if calculatedDiscount > 0}
        <div class="flex justify-between text-red-600">
          <span>Diskon ({discountMode === 'PERCENT' ? `${discountPercent}%` : 'Nominal'})</span>
          <span class="font-mono">-{formatCurrency(calculatedDiscount)}</span>
        </div>
      {/if}
      <div class="flex justify-between text-base font-semibold text-zinc-900 pt-1.5 border-t border-zinc-200">
        <span>Total Bayar</span>
        <span class="font-mono text-zinc-900 font-bold">{formatCurrency(finalTotal)}</span>
      </div>
    </div>

    <!-- Action Buttons (Moka Style Grid) -->
    <div class="space-y-2 pt-0.5">
      <div class="grid grid-cols-2 gap-1.5">
        <button
          type="button"
          disabled={items.length === 0}
          onclick={onSaveOpenBill}
          class={`py-2.5 px-3 text-xs font-semibold rounded-lg flex items-center justify-center gap-1.5 transition-all cursor-pointer border ${
            items.length === 0
              ? 'bg-zinc-100 text-zinc-400 border-zinc-200 cursor-not-allowed'
              : 'bg-white hover:bg-zinc-100 text-zinc-800 border-zinc-300 shadow-2xs active:scale-[0.99]'
          }`}
        >
          <Bookmark class="w-3.5 h-3.5 text-zinc-600" />
          <span>Simpan Bill</span>
        </button>

        <button
          type="button"
          disabled={items.length === 0}
          class={`py-2.5 px-3 text-xs font-semibold rounded-lg flex items-center justify-center gap-1.5 transition-all cursor-pointer border ${
            items.length === 0
              ? 'bg-zinc-100 text-zinc-400 border-zinc-200 cursor-not-allowed'
              : 'bg-white hover:bg-zinc-100 text-zinc-800 border-zinc-300 shadow-2xs active:scale-[0.99]'
          }`}
        >
          <Printer class="w-3.5 h-3.5 text-zinc-600" />
          <span>Cetak Bill</span>
        </button>
      </div>

      <button
        type="button"
        disabled={items.length === 0}
        onclick={onOpenPaymentModal}
        class={`w-full py-3.5 text-xs font-bold rounded-xl flex items-center justify-center gap-2 transition-all cursor-pointer shadow-sm ${
          items.length === 0
            ? 'bg-zinc-200 text-zinc-400 cursor-not-allowed'
            : 'bg-zinc-900 hover:bg-black text-white active:scale-[0.99]'
        }`}
      >
        <CreditCard class="w-4 h-4" />
        <span>Bayar {formatCurrency(finalTotal)}</span>
      </button>
    </div>
  </div>
</aside>
