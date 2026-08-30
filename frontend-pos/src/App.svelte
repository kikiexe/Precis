<script lang="ts">
  import { onMount } from 'svelte';
  import { db } from './lib/db/pos-db';
  import { posApiClient } from './lib/services/api-client';
  import { posService } from './lib/services/pos-service';
  import { syncEngine } from './lib/services/sync-engine';
  import { preloadAndCacheImage } from './lib/services/image-cache';
  import { calculateCartTotals } from './lib/services/pos-calculations';
  import type {
    Product,
    Category,
    CartItem,
    PosSession,
    OfflineOrder,
    CashierUser,
    OrderType,
    PosTerminalInfo,
    CloseSessionResponse,
    OpenBill,
    PosPage,
    StockWaste,
    AddonCategory,
    SelectedModifier,
    OutletPurchase,
  } from './lib/types/pos';
  import PosSidebar from './lib/components/pos/PosSidebar.svelte';
  import PenjualanView from './lib/components/pos/pages/PenjualanView.svelte';
  import TransaksiView from './lib/components/pos/pages/TransaksiView.svelte';
  import ShiftView from './lib/components/pos/pages/ShiftView.svelte';
  import SettlementView from './lib/components/pos/pages/SettlementView.svelte';
  import MenuView from './lib/components/pos/pages/MenuView.svelte';
  import InventoriView from './lib/components/pos/pages/InventoriView.svelte';
  import ProfilView from './lib/components/pos/pages/ProfilView.svelte';
  import PaymentModal from './lib/components/pos/PaymentModal.svelte';
  import ReceiptModal from './lib/components/pos/ReceiptModal.svelte';
  import SessionModal from './lib/components/pos/SessionModal.svelte';
  import MasterLockModal from './lib/components/pos/MasterLockModal.svelte';
  import DevicePairingModal from './lib/components/pos/DevicePairingModal.svelte';
  import OpenBillsModal from './lib/components/pos/OpenBillsModal.svelte';
  import ModifierModal from './lib/components/pos/ModifierModal.svelte';
  import OutletPurchaseModal from './lib/components/pos/OutletPurchaseModal.svelte';

  // state navigasi halaman
  let activePage = $state<PosPage>('penjualan');
  let isSidebarCollapsed = $state(false);

  // state data terminal dan master
  let terminalInfo = $state<PosTerminalInfo | null>(null);
  let categories = $state<Category[]>([]);
  let products = $state<Product[]>([]);
  let addonCategories = $state<AddonCategory[]>([]);
  let purchases = $state<OutletPurchase[]>([]);
  let cashiers = $state<CashierUser[]>([]);
  let allOrders = $state<OfflineOrder[]>([]);
  let closedSessions = $state<PosSession[]>([]);
  let stockWastes = $state<StockWaste[]>([]);

  // state modal modifier / add-on & belanja
  let isModifierModalOpen = $state(false);
  let modifierProduct = $state<Product | null>(null);
  let editingCartItem = $state<CartItem | null>(null);
  let isPurchaseModalOpen = $state(false);

  // state staf / shift operator bersama
  let activeCashier = $state<CashierUser>({
    id: 'team-outlet',
    name: 'Tim Operasional Bar & Kasir',
    role: 'STAFF',
    pin: '',
  });
  let activeSession = $state<PosSession | null>(null);

  // state status jaringan
  let selectedCategoryId = $state('cat-all');
  let isOnline = $state(true);

  // state keranjang dan pesanan
  let cartItems = $state<CartItem[]>([]);
  let discountPercent = $state(0);
  let discountNominal = $state(0);
  let orderType = $state<OrderType>('DINE_IN');
  let customerName = $state('');

  // visibilitas modal
  let isPaymentModalOpen = $state(false);
  let isReceiptModalOpen = $state(false);
  let isSessionModalOpen = $state(false);
  let isMasterLockModalOpen = $state(false);
  let isPairingModalOpen = $state(false);
  let isOpenBillsModalOpen = $state(false);

  // open bills (pesanan tertahan / bayar nanti)
  let openBills = $state<OpenBill[]>([]);

  // pesanan terakhir untuk cetak struk
  let lastCompletedOrder = $state<OfflineOrder | null>(null);

  // perhitungan total belanja dan pajak dinamis
  let cartCalc = $derived(
    calculateCartTotals(cartItems, discountPercent, discountNominal, terminalInfo?.tax_settings)
  );

  let subtotalAmount = $derived(cartCalc.totalAmount);
  let calculatedDiscountAmount = $derived(cartCalc.discountAmount);
  let taxAmount = $derived(cartCalc.taxAmount);
  let taxName = $derived(cartCalc.taxName);
  let taxType = $derived(cartCalc.taxType);
  let taxRate = $derived(cartCalc.taxRate);
  let finalPayableAmount = $derived(cartCalc.finalAmount);

  onMount(() => {
    // daftarkan interceptor token tidak sah untuk membuka modal pairing
    const unsubscribeUnauthorized = posApiClient.onDeviceUnauthorized(() => {
      isPairingModalOpen = true;
    });

    // inisialisasi listener engine sinkronisasi antrean offline
    const cleanupAutoSync = syncEngine.initAutoSync();
    const unsubscribeSync = syncEngine.subscribe((status) => {
      if (!status.isSyncing) {
        loadDbData();
      }
    });

    initTerminalAndDb();
    loadOpenBills();

    // baca perubahan status koneksi internet
    const updateOnline = () => {
      isOnline = navigator.onLine;
      if (isOnline) {
        syncEngine.syncPendingOrders();
      }
    };
    window.addEventListener('online', updateOnline);
    window.addEventListener('offline', updateOnline);
    isOnline = navigator.onLine;

    return () => {
      unsubscribeUnauthorized();
      cleanupAutoSync();
      unsubscribeSync();
      window.removeEventListener('online', updateOnline);
      window.removeEventListener('offline', updateOnline);
    };
  });

  async function initTerminalAndDb() {
    await loadDbData();

    // 1. Muat snapshot terminalInfo terakhir dari cache lokal sebagai fallback offline
    const cachedTerminalRaw =
      typeof window !== 'undefined' ? localStorage.getItem('precis_pos_terminal_info') : null;
    if (cachedTerminalRaw) {
      try {
        const parsed = JSON.parse(cachedTerminalRaw) as PosTerminalInfo;
        terminalInfo = parsed;
        if (parsed.cashiers && parsed.cashiers.length > 0) {
          cashiers = parsed.cashiers;
          if (!activeCashier.id || activeCashier.id === 'team-outlet') {
            activeCashier = parsed.cashiers[0];
          }
        }
      } catch {
        // Fallback silent failure
      }
    }

    const deviceToken = posApiClient.getDeviceToken();
    if (!deviceToken) {
      isPairingModalOpen = true;
      return;
    }

    try {
      const freshInfo = await posService.getTerminalInfo();
      terminalInfo = freshInfo;
      if (typeof window !== 'undefined') {
        localStorage.setItem('precis_pos_terminal_info', JSON.stringify(freshInfo));
      }
      if (freshInfo.qris_image_url) {
        preloadAndCacheImage(freshInfo.qris_image_url);
      }
      if (freshInfo?.cashiers && freshInfo.cashiers.length > 0) {
        await db.cashiers.bulkPut(freshInfo.cashiers);
        cashiers = freshInfo.cashiers;
        if (!activeCashier.id || activeCashier.id === 'team-outlet') {
          activeCashier = freshInfo.cashiers[0];
        }
      }
      // sinkronkan katalog terbaru ke IndexedDB secara background
      await posService.syncCatalogToLocalDb();
      await posService.syncAddonsToLocalDb();
      await posService.syncPurchasesToLocalDb();
      await posService.syncStockWastesToLocalDb();
      // sinkronkan riwayat transaksi server ke IndexedDB
      await posService.syncRecentOrdersToLocalDb();
      await loadDbData();
    } catch {
      // Jika request server gagal (offline), jangan lempar modal pairing jika snapshot lokal ada
      if (!terminalInfo) {
        isPairingModalOpen = true;
      }
    }
  }

  async function loadDbData() {
    try {
      products = await db.products.toArray();
      categories = await db.categories.toArray();
      addonCategories = await db.addonCategories.toArray();
      purchases = await db.purchases.reverse().toArray();
      cashiers = await db.cashiers.toArray();
      if (cashiers.length > 0 && (!activeCashier.id || activeCashier.id === 'team-outlet')) {
        activeCashier = cashiers[0];
      }
      allOrders = await db.orders.reverse().toArray();
      stockWastes = await posService.getStockWastes();

      const openSess = await db.sessions.where('status').equals('OPEN').first();
      activeSession = openSess || null;
      closedSessions = await db.sessions.where('status').equals('CLOSED').reverse().toArray();
    } catch (e) {
      console.warn('Gagal memuat data dari IndexedDB:', e);
    }
  }

  async function handlePairingSuccess(info: PosTerminalInfo) {
    terminalInfo = info;
    if (typeof window !== 'undefined') {
      localStorage.setItem('precis_pos_terminal_info', JSON.stringify(info));
    }
    if (info.qris_image_url) {
      preloadAndCacheImage(info.qris_image_url);
    }
    isPairingModalOpen = false;
    if (info.cashiers && info.cashiers.length > 0) {
      await db.cashiers.bulkPut(info.cashiers);
      cashiers = info.cashiers || [];
      if (cashiers.length > 0) {
        activeCashier = cashiers[0];
      }
    }
    await posService.syncCatalogToLocalDb();
    await posService.syncAddonsToLocalDb();
    await posService.syncPurchasesToLocalDb();
    await posService.syncRecentOrdersToLocalDb();
    await loadDbData();
  }

  function handlePurchaseCreated(newPurchase: OutletPurchase) {
    purchases = [newPurchase, ...purchases];
    loadDbData();
  }

  // fungsi keranjang belanja
  function handleAddToCart(product: Product) {
    const hasModifiers =
      (product.addon_category_ids && product.addon_category_ids.length > 0) ||
      addonCategories.some((cat) => cat.product_ids && cat.product_ids.includes(product.id));

    if (hasModifiers) {
      modifierProduct = product;
      editingCartItem = null;
      isModifierModalOpen = true;
    } else {
      const existingIndex = cartItems.findIndex(
        (i) => i.product.id === product.id && (!i.modifiers || i.modifiers.length === 0)
      );
      if (existingIndex > -1) {
        cartItems[existingIndex].quantity += 1;
      } else {
        cartItems.push({
          product,
          quantity: 1,
          unit_price: product.base_price,
          notes: '',
          modifiers: [],
        });
      }
    }
  }

  function handleSaveModifierItem(saved: {
    product: Product;
    quantity: number;
    unit_price: number;
    notes: string;
    modifiers: SelectedModifier[];
  }) {
    if (editingCartItem) {
      const idx = cartItems.findIndex((i) => i === editingCartItem);
      if (idx > -1) {
        cartItems[idx] = {
          product: saved.product,
          quantity: saved.quantity,
          unit_price: saved.unit_price,
          notes: saved.notes,
          modifiers: saved.modifiers,
        };
      }
      editingCartItem = null;
    } else {
      cartItems.push({
        product: saved.product,
        quantity: saved.quantity,
        unit_price: saved.unit_price,
        notes: saved.notes,
        modifiers: saved.modifiers,
      });
    }
  }

  function handleEditItemModifiers(item: CartItem) {
    modifierProduct = item.product;
    editingCartItem = item;
    isModifierModalOpen = true;
  }
  function handleUpdateQuantity(productId: string, delta: number) {
    const index = cartItems.findIndex((i) => i.product.id === productId);
    if (index > -1) {
      const newQty = cartItems[index].quantity + delta;
      if (newQty <= 0) {
        cartItems.splice(index, 1);
      } else {
        cartItems[index].quantity = newQty;
      }
    }
  }

  function handleUpdateNotes(productId: string, notes: string) {
    const index = cartItems.findIndex((i) => i.product.id === productId);
    if (index > -1) {
      cartItems[index].notes = notes;
    }
  }

  function handleRemoveItem(productId: string) {
    cartItems = cartItems.filter((i) => i.product.id !== productId);
  }

  function handleClearCart() {
    cartItems = [];
    discountPercent = 0;
    discountNominal = 0;
    customerName = '';
  }

  // fungsi manajemen open bills (simpan pesanan untuk bayar nanti)
  function loadOpenBills() {
    if (typeof window !== 'undefined') {
      try {
        const raw = localStorage.getItem('precis_pos_open_bills');
        if (raw) {
          openBills = JSON.parse(raw);
        }
      } catch (e) {
        console.warn('Gagal memuat open bills dari localStorage:', e);
      }
    }
  }

  function saveOpenBillsToStorage(bills: OpenBill[]) {
    openBills = bills;
    if (typeof window !== 'undefined') {
      try {
        localStorage.setItem('precis_pos_open_bills', JSON.stringify(bills));
      } catch (e) {
        console.warn('Gagal menyimpan open bills ke localStorage:', e);
      }
    }
  }

  function handleSaveOpenBill() {
    if (cartItems.length === 0) return;

    const newBill: OpenBill = {
      id: 'bill-' + Date.now() + '-' + Math.random().toString(36).substring(2, 6),
      order_number: `BILL-${String(openBills.length + 1).padStart(3, '0')}`,
      order_type: orderType,
      customer_name: customerName.trim() || 'Tamu #' + (openBills.length + 1),
      items: [...cartItems],
      discount_percent: discountPercent,
      discount_nominal: discountNominal,
      subtotal: subtotalAmount,
      final_total: finalPayableAmount,
      saved_at: new Date().toISOString(),
    };

    saveOpenBillsToStorage([newBill, ...openBills]);
    handleClearCart();
  }

  function handleRestoreOpenBill(billId: string) {
    const bill = openBills.find((b) => b.id === billId);
    if (!bill) return;

    cartItems = [...bill.items];
    orderType = bill.order_type;
    customerName = bill.customer_name;
    discountPercent = bill.discount_percent;
    discountNominal = bill.discount_nominal;

    // Hapus bill yang telah dimuat dari open bills
    saveOpenBillsToStorage(openBills.filter((b) => b.id !== billId));
  }

  function handleDeleteOpenBill(billId: string) {
    saveOpenBillsToStorage(openBills.filter((b) => b.id !== billId));
  }

  // fungsi transaksi penjualan
  async function handleCompleteOrder(order: OfflineOrder) {
    order.order_type = orderType;
    order.customer_name = customerName || undefined;
    order.table_number = undefined;

    try {
      await db.orders.add(order);
      allOrders = [order, ...allOrders];
    } catch (e) {
      console.warn('Gagal menyimpan pesanan ke Dexie:', e);
    }

    if (activeSession) {
      const updatedSess = { ...activeSession };
      updatedSess.order_count += 1;
      if (order.payment_method === 'CASH') {
        updatedSess.total_cash_sales += order.final_amount;
      } else if (order.payment_method === 'QRIS') {
        updatedSess.total_qris_sales += order.final_amount;
      } else if (order.payment_method === 'EDC' || order.payment_method === 'TRANSFER') {
        updatedSess.total_edc_sales = (updatedSess.total_edc_sales || 0) + order.final_amount;
        updatedSess.total_transfer_sales += order.final_amount;
      }
      await db.sessions.put(updatedSess);
      activeSession = updatedSess;
    }

    lastCompletedOrder = order;
    handleClearCart();
    isPaymentModalOpen = false;
    isReceiptModalOpen = true;

    // Picu sinkronisasi ke server jika terhubung
    if (navigator.onLine) {
      syncEngine.syncPendingOrders();
    }
  }

  function handleCloseReceiptAndReset() {
    isReceiptModalOpen = false;
    lastCompletedOrder = null;
  }

  // fungsi manajemen sesi kasir
  function handleSessionOpened(session: PosSession) {
    activeSession = session;
    isSessionModalOpen = false;
  }

  function handleSessionClosed(_closedData: CloseSessionResponse) {
    activeSession = null;
    loadDbData();
    isSessionModalOpen = false;
  }

  // fungsi produk lokal
  async function handleToggleProductActive(productId: string) {
    const prod = products.find((p) => p.id === productId);
    if (prod) {
      prod.is_active = !prod.is_active;
      await db.products.put(prod);
      products = [...products];
    }
  }

  async function handleAddNewProduct(newProd: Product) {
    await db.products.add(newProd);
    products = [newProd, ...products];
  }

  async function handleUpdateProduct(updatedProd: Product) {
    await db.products.put(updatedProd);
    products = products.map((p) => (p.id === updatedProd.id ? updatedProd : p));
  }

  async function handleDeleteProduct(productId: string) {
    await db.products.delete(productId);
    products = products.filter((p) => p.id !== productId);
  }

  async function handleUpdateCategories(updatedCategories: Category[]) {
    await db.categories.clear();
    await db.categories.bulkAdd(updatedCategories);
    categories = updatedCategories;
  }

  async function handleClearLocalCache() {
    await db.delete();
    window.location.reload();
  }

  function handlePrintOrderDirect(order: OfflineOrder) {
    lastCompletedOrder = order;
    isReceiptModalOpen = true;
  }
</script>

<div class="flex h-screen w-screen flex-row overflow-hidden bg-[#f4f4f4] font-sans select-none">
  <!-- navigasi sidebar kiosk -->
  <PosSidebar
    {activePage}
    isCollapsed={isSidebarCollapsed}
    onSelectPage={(page) => (activePage = page)}
    onToggleCollapse={() => (isSidebarCollapsed = !isSidebarCollapsed)}
  />

  <!-- area workspace utama -->
  <div class="flex h-full flex-1 flex-col overflow-hidden">
    <!-- area tampilan aktif -->
    <main class="flex flex-1 overflow-hidden">
      {#if activePage === 'penjualan'}
        <PenjualanView
          {categories}
          {products}
          {cartItems}
          {selectedCategoryId}
          {discountPercent}
          {discountNominal}
          {orderType}
          {customerName}
          openBillsCount={openBills.length}
          taxSettings={terminalInfo?.tax_settings}
          onSelectCategory={(id: string) => (selectedCategoryId = id)}
          onAddToCart={handleAddToCart}
          onUpdateQuantity={handleUpdateQuantity}
          onUpdateNotes={handleUpdateNotes}
          onRemoveItem={handleRemoveItem}
          onClearCart={handleClearCart}
          onSetOrderType={(type: OrderType) => (orderType = type)}
          onSetCustomerName={(name: string) => (customerName = name)}
          onSetDiscountPercent={(percent: number) => {
            discountPercent = percent;
            discountNominal = 0;
          }}
          onSetDiscountNominal={(nominal: number) => {
            discountNominal = nominal;
            discountPercent = 0;
          }}
          onSaveOpenBill={handleSaveOpenBill}
          onOpenBillsModal={() => (isOpenBillsModalOpen = true)}
          onOpenPaymentModal={() => (isPaymentModalOpen = true)}
          onEditItemModifiers={handleEditItemModifiers}
        />
      {:else if activePage === 'transaksi'}
        <TransaksiView
          orders={allOrders}
          {cashiers}
          {activeSession}
          onPrintOrder={handlePrintOrderDirect}
          onOrderUpdated={loadDbData}
        />
      {:else if activePage === 'shift'}
        <ShiftView
          {activeSession}
          {activeCashier}
          {purchases}
          onOpenSessionModal={() => (isSessionModalOpen = true)}
          onOpenPurchaseModal={() => (isPurchaseModalOpen = true)}
          onGoToSettlement={() => (activePage = 'settlement')}
        />
      {:else if activePage === 'settlement'}
        <SettlementView
          {activeSession}
          {activeCashier}
          {closedSessions}
          onGoToShift={() => (activePage = 'shift')}
        />
      {:else if activePage === 'menu'}
        <MenuView
          {products}
          {categories}
          onToggleProductActive={handleToggleProductActive}
          onAddNewProduct={handleAddNewProduct}
          onUpdateProduct={handleUpdateProduct}
          onDeleteProduct={handleDeleteProduct}
          onUpdateCategories={handleUpdateCategories}
        />
      {:else if activePage === 'inventori'}
        <InventoriView
          {products}
          {cashiers}
          {activeCashier}
          {stockWastes}
          onRecordWaste={(w) => {
            stockWastes = [w, ...stockWastes];
          }}
        />
      {:else if activePage === 'profil'}
        <ProfilView
          {activeCashier}
          {cashiers}
          totalOrdersCount={allOrders.length}
          totalProductsCount={products.length}
          onOpenMasterLockModal={() => (isMasterLockModalOpen = true)}
          onClearLocalCache={handleClearLocalCache}
        />
      {/if}
    </main>
  </div>
</div>

<!-- modal pembayaran -->
{#if isPaymentModalOpen}
  <PaymentModal
    isOpen={isPaymentModalOpen}
    totalAmount={subtotalAmount}
    discountAmount={calculatedDiscountAmount}
    finalAmount={finalPayableAmount}
    {taxName}
    {taxRate}
    {taxType}
    {taxAmount}
    items={cartItems}
    {activeCashier}
    branchId={terminalInfo?.branch_id || ''}
    workspaceId={terminalInfo?.workspace_id || ''}
    activeSessionId={activeSession?.id || ''}
    qrisImageUrl={terminalInfo?.qris_image_url}
    onClose={() => (isPaymentModalOpen = false)}
    onCompleteOrder={handleCompleteOrder}
  />
{/if}

<!-- modal modifier / add-on menu -->
{#if isModifierModalOpen && modifierProduct}
  <ModifierModal
    isOpen={isModifierModalOpen}
    product={modifierProduct}
    {addonCategories}
    initialModifiers={editingCartItem?.modifiers || []}
    initialNotes={editingCartItem?.notes || ''}
    initialQuantity={editingCartItem?.quantity || 1}
    onClose={() => {
      isModifierModalOpen = false;
      modifierProduct = null;
      editingCartItem = null;
    }}
    onAddToCart={handleSaveModifierItem}
  />
{/if}

<!-- modal belanja outlet petty cash -->
{#if isPurchaseModalOpen}
  <OutletPurchaseModal
    isOpen={isPurchaseModalOpen}
    {activeSession}
    {activeCashier}
    {cashiers}
    onClose={() => (isPurchaseModalOpen = false)}
    onPurchaseCreated={handlePurchaseCreated}
  />
{/if}

<!-- modal struk cetak -->
<ReceiptModal
  isOpen={isReceiptModalOpen}
  order={lastCompletedOrder}
  onCloseAndReset={handleCloseReceiptAndReset}
/>

<!-- modal sesi kasir buka / tutup -->
<SessionModal
  isOpen={isSessionModalOpen}
  {activeSession}
  cashierUserId={activeCashier?.id}
  {cashiers}
  {activeCashier}
  {purchases}
  onClose={() => (isSessionModalOpen = false)}
  onSessionOpened={handleSessionOpened}
  onSessionClosed={handleSessionClosed}
/>

<!-- modal master lock kiosk -->
<MasterLockModal
  isOpen={isMasterLockModalOpen}
  onClose={() => (isMasterLockModalOpen = false)}
  onUnlock={() => {
    isMasterLockModalOpen = false;
  }}
/>

<!-- modal pairing perangkat tablet -->
<DevicePairingModal isOpen={isPairingModalOpen} onSuccess={handlePairingSuccess} />

<!-- modal daftar open bills / pesanan tertahan -->
<OpenBillsModal
  isOpen={isOpenBillsModalOpen}
  {openBills}
  onClose={() => (isOpenBillsModalOpen = false)}
  onRestoreBill={handleRestoreOpenBill}
  onDeleteBill={handleDeleteOpenBill}
/>
