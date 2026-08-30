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
  import type { CartItem, OrderType, TaxSettings } from '../../types/pos';
  import { formatCurrency } from '../../services/printer-service';
  import { calculateCartTotals } from '../../services/pos-calculations';

  interface Props {
    items: CartItem[];
    discountPercent: number;
    discountNominal: number;
    orderType?: OrderType;
    customerName?: string;
    openBillsCount?: number;
    taxSettings?: TaxSettings | null;
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
    taxSettings = null,
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

  let cartCalc = $derived(
    calculateCartTotals(items, discountPercent, discountNominal, taxSettings)
  );

  let subtotal = $derived(cartCalc.totalAmount);
  let calculatedDiscount = $derived(cartCalc.discountAmount);
  let taxAmount = $derived(cartCalc.taxAmount);
  let taxName = $derived(cartCalc.taxName);
  let taxType = $derived(cartCalc.taxType);
  let finalTotal = $derived(cartCalc.finalAmount);

  let totalItemCount = $derived(items.reduce((sum, item) => sum + item.quantity, 0));

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

<aside
  class="flex h-full w-72 shrink-0 flex-col border-l border-zinc-200 bg-white font-sans shadow-none select-none sm:w-80 md:w-88 lg:w-96"
>
  <!-- Header: Cart Title, Count & Open Bills shortcut -->
  <div class="flex items-center justify-between border-b border-zinc-200 bg-zinc-50/70 px-3.5 py-3">
    <div class="flex items-center gap-2">
      <ShoppingCart class="size-4 text-zinc-900" />
      <span class="text-sm font-semibold text-zinc-900">Pesanan Baru</span>
      <span
        class="rounded-md bg-zinc-200/80 px-2 py-0.5 font-mono text-xs font-semibold text-zinc-800"
      >
        {totalItemCount}
      </span>
    </div>

    <div class="flex items-center gap-2">
      <!-- Open Bills Shortcut Button -->
      {#if onOpenBillsModal}
        <button
          type="button"
          onclick={onOpenBillsModal}
          class={`flex cursor-pointer items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-medium transition-all ${
            openBillsCount > 0
              ? 'border-amber-500/30 bg-amber-500/10 text-amber-800 hover:bg-amber-500/20'
              : 'border-zinc-200 bg-zinc-100 text-zinc-600 hover:bg-zinc-200/70'
          }`}
        >
          <Bookmark class="size-3" />
          <span>Open Bills</span>
          {#if openBillsCount > 0}
            <span
              class="flex size-4 items-center justify-center rounded-full bg-amber-600 font-mono text-[9px] font-bold text-white"
            >
              {openBillsCount}
            </span>
          {/if}
        </button>
      {/if}

      {#if items.length > 0}
        <button
          type="button"
          onclick={onClearCart}
          class="flex cursor-pointer items-center gap-1 text-xs font-medium text-red-600 transition-colors hover:text-red-700 hover:underline"
        >
          <Trash2 class="size-3.5" />
          <span>Batal</span>
        </button>
      {/if}
    </div>
  </div>

  <!-- Order Type (Compact Single-Selection Dropdown) & Customer Name Input -->
  <div class="relative shrink-0 border-b border-zinc-200 bg-white p-3">
    <div class="flex items-center gap-2">
      <!-- Selected Order Type Dropdown -->
      <div class="relative shrink-0">
        <button
          type="button"
          onclick={() => (isOrderTypeMenuOpen = !isOrderTypeMenuOpen)}
          class="active:scale-0.98 flex h-9 cursor-pointer items-center gap-2 rounded-lg border border-zinc-200/80 bg-zinc-100 px-3 text-xs font-medium text-zinc-800 transition-all hover:bg-zinc-200/70"
        >
          <selectedOrderTypeOption.icon class="size-3.5 text-zinc-700" />
          <span>{selectedOrderTypeOption.label}</span>
          <ChevronDown class="size-3.5 text-zinc-400" />
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

          <div
            class="absolute top-full left-0 z-30 mt-1.5 w-36 overflow-hidden rounded-xl border border-zinc-200 bg-white py-1 shadow-lg"
          >
            {#each orderTypeOptions as opt}
              <button
                type="button"
                onclick={() => {
                  onSetOrderType?.(opt.value);
                  isOrderTypeMenuOpen = false;
                }}
                class={`flex w-full cursor-pointer items-center justify-between px-3 py-2 text-left text-xs transition-colors ${
                  orderType === opt.value
                    ? 'bg-zinc-900 font-medium text-white'
                    : 'text-zinc-700 hover:bg-zinc-100'
                }`}
              >
                <div class="flex items-center gap-2">
                  <opt.icon class="size-3.5" />
                  <span>{opt.label}</span>
                </div>
                {#if orderType === opt.value}
                  <Check class="size-3.5 text-white" />
                {/if}
              </button>
            {/each}
          </div>
        {/if}
      </div>

      <!-- Customer Name Input -->
      <div class="relative flex-1">
        <User class="absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-zinc-400" />
        <input
          type="text"
          value={customerName}
          oninput={(e) => onSetCustomerName?.((e.target as HTMLInputElement).value)}
          placeholder="Nama Pemesan (Opsional)..."
          class="h-9 w-full rounded-lg border border-zinc-200/80 bg-zinc-50 pr-3 pl-8 text-xs text-zinc-900 placeholder-zinc-400 transition-all focus:border-zinc-900 focus:bg-white focus:ring-1 focus:ring-zinc-900 focus:outline-hidden"
        />
      </div>
    </div>
  </div>

  <!-- Order Items List: Unified Single Container with Swipe Gestures -->
  <div class="flex-1 space-y-2 overflow-y-auto p-3">
    {#if items.length === 0}
      <div class="flex h-64 flex-col items-center justify-center text-center text-zinc-400">
        <ShoppingCart class="mb-2 size-9 text-zinc-400 opacity-30" />
        <p class="text-sm font-medium text-zinc-800">Keranjang masih kosong</p>
        <p class="mt-0.5 text-xs text-zinc-500">Pilih menu dari katalog di sebelah kiri.</p>
      </div>
    {:else}
      <!-- Single Unified Container for all items -->
      <div
        class="divide-y divide-zinc-100 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xs"
      >
        {#each items as item (item.product.id)}
          {@const isItemSwiped = swipedItemId === item.product.id}
          {@const currentOffset = isItemSwiped ? swipeOffset : 0}

          <div class="group relative overflow-hidden bg-white select-none">
            <!-- Left Swipe Background Action: Delete -->
            <div
              class="absolute inset-y-0 right-0 flex w-18 items-center justify-center bg-red-600"
            >
              <button
                type="button"
                onclick={() => {
                  onRemoveItem(item.product.id);
                  resetSwipe();
                }}
                class="flex size-full cursor-pointer flex-col items-center justify-center gap-0.5 text-[10px] font-semibold text-white transition-all hover:bg-red-700 active:scale-95"
              >
                <Trash2 class="size-3.5" />
                <span>Hapus</span>
              </button>
            </div>

            <!-- Right Swipe Background Action: +1 Increment -->
            <div
              class="absolute inset-y-0 left-0 flex w-18 items-center justify-center bg-emerald-600"
            >
              <div
                class="flex flex-col items-center justify-center gap-0.5 text-[10px] font-semibold text-white"
              >
                <Plus class="size-3.5" />
                <span>+1 Menu</span>
              </div>
            </div>

            <!-- Foreground Item Row -->
            <div
              role="group"
              aria-label={item.product.name}
              class="relative space-y-1.5 bg-white p-3 transition-transform duration-100 ease-out"
              style="transform: translateX({currentOffset}px);"
              ontouchstart={(e) => handleTouchStart(e, item.product.id)}
              ontouchmove={(e) => handleTouchMove(e, item.product.id)}
              ontouchend={() => handleTouchEnd(item.product.id)}
              onpointerdown={(e) => handleTouchStart(e, item.product.id)}
              onpointermove={(e) => handleTouchMove(e, item.product.id)}
              onpointerup={() => handleTouchEnd(item.product.id)}
            >
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                  <div class="line-clamp-2 text-xs leading-snug font-medium text-zinc-900">
                    {item.product.name}
                  </div>
                  <div class="mt-0.5 font-mono text-[11px] text-zinc-500">
                    {formatCurrency(item.unit_price)}
                  </div>
                </div>

                <!-- Quantity Stepper & Row Subtotal -->
                <div class="flex shrink-0 items-center gap-2">
                  <div
                    class="flex items-center gap-1 rounded-lg border border-zinc-200/80 bg-zinc-100 p-0.5"
                  >
                    <button
                      type="button"
                      onclick={() => onUpdateQuantity(item.product.id, -1)}
                      class="flex size-5.5 cursor-pointer items-center justify-center rounded-md bg-white text-xs font-medium text-zinc-800 shadow-2xs transition-all hover:bg-zinc-200 active:scale-95"
                    >
                      <Minus class="size-2.5" />
                    </button>
                    <span class="w-5 text-center font-mono text-xs font-semibold text-zinc-900">
                      {item.quantity}
                    </span>
                    <button
                      type="button"
                      onclick={() => onUpdateQuantity(item.product.id, 1)}
                      class="flex size-5.5 cursor-pointer items-center justify-center rounded-md bg-zinc-900 text-xs font-medium text-white shadow-2xs transition-all hover:bg-black active:scale-95"
                    >
                      <Plus class="size-2.5" />
                    </button>
                  </div>

                  <div class="w-16 text-right font-mono text-xs font-semibold text-zinc-900">
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
                  oninput={(e) =>
                    onUpdateNotes(item.product.id, (e.target as HTMLInputElement).value)}
                  class="w-full rounded-md border border-zinc-200/70 bg-zinc-50 px-2.5 py-1 text-[11px] text-zinc-900 placeholder-zinc-400 transition-all focus:border-zinc-900 focus:bg-white focus:outline-hidden"
                />
              </div>
            </div>
          </div>
        {/each}
      </div>

      <!-- Quick swipe gesture hint -->
      <div class="pt-1 text-center font-mono text-[10px] text-zinc-400">
        💡 Geser kiri untuk hapus • Geser kanan untuk +1
      </div>
    {/if}
  </div>

  <!-- Bottom Panel: Dynamic Discounts, Summary & Checkout Button -->
  <div class="shrink-0 space-y-3 border-t border-zinc-200 bg-zinc-50/80 p-3.5">
    <!-- Dynamic Discount Input with % / Rp Mode Switch -->
    <div class="space-y-1.5">
      <div class="flex items-center justify-between text-xs">
        <span class="flex items-center gap-1.5 font-medium text-zinc-600">
          <Tag class="size-3.5 text-zinc-700" />
          <span>Diskon Pesanan</span>
        </span>
        {#if calculatedDiscount > 0}
          <span class="font-mono text-[11px] font-medium text-red-600">
            -{formatCurrency(calculatedDiscount)} ({discountMode === 'PERCENT'
              ? `${discountPercent}%`
              : 'Rp'})
          </span>
        {/if}
      </div>

      <!-- Input Bar with Switcher -->
      <div class="flex items-center gap-1.5">
        <div class="flex shrink-0 rounded-lg border border-zinc-300/70 bg-zinc-200/80 p-0.5">
          <button
            type="button"
            onclick={() => handleSwitchDiscountMode('PERCENT')}
            class={`cursor-pointer rounded-md px-2 py-1 font-mono text-[11px] font-bold transition-all ${
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
            class={`cursor-pointer rounded-md px-2 py-1 font-mono text-[11px] font-bold transition-all ${
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
            placeholder={discountMode === 'PERCENT'
              ? 'Ketik diskon persen (misal 15)...'
              : 'Ketik nominal diskon (misal 10000)...'}
            class="h-8 w-full rounded-lg border border-zinc-200 bg-white px-2.5 font-mono text-xs text-zinc-900 placeholder-zinc-400 transition-all focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:outline-hidden"
          />
          {#if (discountMode === 'PERCENT' && discountPercent > 0) || (discountMode === 'NOMINAL' && discountNominal > 0)}
            <button
              type="button"
              onclick={handleClearDiscount}
              class="absolute top-1/2 right-2 -translate-y-1/2 cursor-pointer p-0.5 font-mono text-xs text-zinc-400 hover:text-zinc-700"
            >
              ✕
            </button>
          {/if}
        </div>
      </div>
    </div>

    <!-- Pricing Breakdown -->
    <div class="space-y-1.5 border-t border-zinc-200 pt-2.5 text-xs">
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
      {#if taxAmount > 0}
        <div class="flex justify-between text-zinc-600">
          <span>
            {taxName} ({taxSettings?.tax_rate}%)
            {#if taxType === 'INCLUSIVE'}
              <span class="text-[10px] font-normal text-zinc-400">(Termasuk)</span>
            {/if}
          </span>
          <span class="font-mono">
            {taxType === 'EXCLUSIVE' ? '+' : ''}{formatCurrency(taxAmount)}
          </span>
        </div>
      {/if}
      <div
        class="flex justify-between border-t border-zinc-200 pt-1.5 text-base font-semibold text-zinc-900"
      >
        <span>Total Bayar</span>
        <span class="font-mono font-bold text-zinc-900">{formatCurrency(finalTotal)}</span>
      </div>
    </div>

    <!-- Action Buttons (Moka Style Grid) -->
    <div class="space-y-2 pt-0.5">
      <div class="grid grid-cols-2 gap-1.5">
        <button
          type="button"
          disabled={items.length === 0}
          onclick={onSaveOpenBill}
          class={`flex cursor-pointer items-center justify-center gap-1.5 rounded-lg border px-3 py-2.5 text-xs font-semibold transition-all ${
            items.length === 0
              ? 'cursor-not-allowed border-zinc-200 bg-zinc-100 text-zinc-400'
              : 'active:scale-0.99 border-zinc-300 bg-white text-zinc-800 shadow-2xs hover:bg-zinc-100'
          }`}
        >
          <Bookmark class="size-3.5 text-zinc-600" />
          <span>Simpan Bill</span>
        </button>

        <button
          type="button"
          disabled={items.length === 0}
          class={`flex cursor-pointer items-center justify-center gap-1.5 rounded-lg border px-3 py-2.5 text-xs font-semibold transition-all ${
            items.length === 0
              ? 'cursor-not-allowed border-zinc-200 bg-zinc-100 text-zinc-400'
              : 'active:scale-0.99 border-zinc-300 bg-white text-zinc-800 shadow-2xs hover:bg-zinc-100'
          }`}
        >
          <Printer class="size-3.5 text-zinc-600" />
          <span>Cetak Bill</span>
        </button>
      </div>

      <button
        type="button"
        disabled={items.length === 0}
        onclick={onOpenPaymentModal}
        class={`flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl py-3.5 text-xs font-bold shadow-sm transition-all ${
          items.length === 0
            ? 'cursor-not-allowed bg-zinc-200 text-zinc-400'
            : 'active:scale-0.99 bg-zinc-900 text-white hover:bg-black'
        }`}
      >
        <CreditCard class="size-4" />
        <span>Bayar {formatCurrency(finalTotal)}</span>
      </button>
    </div>
  </div>
</aside>
