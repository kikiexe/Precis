<script lang="ts">
  import { onMount } from 'svelte';
  import { db } from './lib/db/pos-db';
  import { posApiClient } from './lib/services/api-client';
  import { posService } from './lib/services/pos-service';
  import { syncEngine } from './lib/services/sync-engine';
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
  } from './lib/types/pos';
  import PosSidebar from './lib/components/pos/PosSidebar.svelte';
  import PosHeader from './lib/components/pos/PosHeader.svelte';
  import PenjualanView from './lib/components/pos/pages/PenjualanView.svelte';
  import TransaksiView from './lib/components/pos/pages/TransaksiView.svelte';
  import SettlementView from './lib/components/pos/pages/SettlementView.svelte';
  import ProdukView from './lib/components/pos/pages/ProdukView.svelte';
  import ProfilView from './lib/components/pos/pages/ProfilView.svelte';
  import PaymentModal from './lib/components/pos/PaymentModal.svelte';
  import ReceiptModal from './lib/components/pos/ReceiptModal.svelte';
  import CashierPinModal from './lib/components/pos/CashierPinModal.svelte';
  import SessionModal from './lib/components/pos/SessionModal.svelte';
  import MasterLockModal from './lib/components/pos/MasterLockModal.svelte';
  import DevicePairingModal from './lib/components/pos/DevicePairingModal.svelte';

  // state navigasi halaman
  let activePage = $state<'penjualan' | 'transaksi' | 'settlement' | 'produk' | 'profil'>('penjualan');
  let isSidebarCollapsed = $state(false);

  // state data terminal dan master
  let terminalInfo = $state<PosTerminalInfo | null>(null);
  let categories = $state<Category[]>([]);
  let products = $state<Product[]>([]);
  let cashiers = $state<CashierUser[]>([]);
  let allOrders = $state<OfflineOrder[]>([]);
  let closedSessions = $state<PosSession[]>([]);

  // state sesi kasir aktif dan staf
  let activeCashier = $state<CashierUser | null>(null);
  let activeSession = $state<PosSession | null>(null);

  // state status jaringan dan sinkronisasi
  let selectedCategoryId = $state('cat-all');
  let isOnline = $state(true);
  let isSyncing = $state(false);
  let pendingSyncCount = $state(0);

  // state keranjang dan pesanan
  let cartItems = $state<CartItem[]>([]);
  let discountPercent = $state(0);
  let discountNominal = $state(0);
  let orderType = $state<OrderType>('DINE_IN');
  let customerName = $state('');
  let tableNumber = $state('');

  // visibilitas modal
  let isPaymentModalOpen = $state(false);
  let isReceiptModalOpen = $state(false);
  let isPinModalOpen = $state(false);
  let isSessionModalOpen = $state(false);
  let isMasterLockModalOpen = $state(false);
  let isPairingModalOpen = $state(false);

  // pesanan terakhir untuk cetak struk
  let lastCompletedOrder = $state<OfflineOrder | null>(null);

  // perhitungan total belanja
  let subtotalAmount = $derived(
    cartItems.reduce((sum, item) => sum + item.unit_price * item.quantity, 0)
  );

  let calculatedDiscountAmount = $derived(
    discountPercent > 0
      ? Math.round((subtotalAmount * discountPercent) / 100)
      : discountNominal
  );

  let finalPayableAmount = $derived(
    Math.max(0, subtotalAmount - calculatedDiscountAmount)
  );

  onMount(() => {
    // daftarkan interceptor token tidak sah untuk membuka modal pairing
    const unsubscribeUnauthorized = posApiClient.onDeviceUnauthorized(() => {
      isPairingModalOpen = true;
    });

    // inisialisasi listener engine sinkronisasi antrean offline
    const cleanupAutoSync = syncEngine.initAutoSync();
    const unsubscribeSync = syncEngine.subscribe((status) => {
      isSyncing = status.isSyncing;
      pendingSyncCount = status.pendingCount;
      if (!status.isSyncing) {
        loadDbData();
      }
    });

    initTerminalAndDb();

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

    const deviceToken = posApiClient.getDeviceToken();
    if (!deviceToken) {
      isPairingModalOpen = true;
      return;
    }

    try {
      terminalInfo = await posService.getTerminalInfo();
      // sinkronkan katalog terbaru ke IndexedDB secara background
      await posService.syncCatalogToLocalDb();
      await loadDbData();
    } catch {
      isPairingModalOpen = true;
    }
  }

  async function loadDbData() {
    try {
      products = await db.products.toArray();
      categories = await db.categories.toArray();
      cashiers = await db.cashiers.toArray();
      allOrders = await db.orders.reverse().toArray();

      const openSess = await db.sessions.where('status').equals('OPEN').first();
      if (openSess) {
        activeSession = openSess;
      }

      closedSessions = await db.sessions.where('status').equals('CLOSED').reverse().toArray();

      const pendingOrders = await syncEngine.getPendingCount();
      pendingSyncCount = pendingOrders;
    } catch (e) {
      console.warn('Gagal memuat data dari IndexedDB:', e);
    }
  }

  function handlePairingSuccess(info: PosTerminalInfo) {
    terminalInfo = info;
    isPairingModalOpen = false;
    posService.syncCatalogToLocalDb().then(() => loadDbData());
  }

  // fungsi keranjang belanja
  function handleAddToCart(product: Product) {
    const existingIndex = cartItems.findIndex((i) => i.product.id === product.id);
    if (existingIndex > -1) {
      cartItems[existingIndex].quantity += 1;
    } else {
      cartItems.push({
        product,
        quantity: 1,
        unit_price: product.base_price,
        notes: '',
      });
    }
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
    tableNumber = '';
  }

  // fungsi transaksi penjualan
  async function handleCompleteOrder(order: OfflineOrder) {
    order.order_type = orderType;
    order.customer_name = customerName || undefined;
    order.table_number = orderType === 'DINE_IN' ? tableNumber : undefined;

    try {
      await db.orders.add(order);
      allOrders = [order, ...allOrders];
    } catch (e) {
      console.warn('Gagal menyimpan pesanan ke Dexie:', e);
    }

    if (activeSession) {
      if (order.payment_method === 'CASH') {
        activeSession.total_cash_sales = (activeSession.total_cash_sales || 0) + order.final_amount;
      } else if (order.payment_method === 'QRIS') {
        activeSession.total_qris_sales = (activeSession.total_qris_sales || 0) + order.final_amount;
      } else {
        activeSession.total_transfer_sales = (activeSession.total_transfer_sales || 0) + order.final_amount;
      }
      activeSession.order_count = (activeSession.order_count || 0) + 1;
      await db.sessions.put(activeSession);
    }

    lastCompletedOrder = order;
    isPaymentModalOpen = false;
    isReceiptModalOpen = true;

    // trigger sinkronisasi antrean via sync engine
    syncEngine.syncPendingOrders();
  }

  function handleCloseReceiptAndReset() {
    isReceiptModalOpen = false;
    lastCompletedOrder = null;
    handleClearCart();
  }

  function handleSessionOpened(session: PosSession) {
    activeSession = session;
    loadDbData();
  }

  function handleSessionClosed(_closedData: CloseSessionResponse) {
    activeSession = null;
    loadDbData();
  }

  // fungsi master produk
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

  async function handleClearLocalCache() {
    await db.delete();
    window.location.reload();
  }

  function handlePrintOrderDirect(order: OfflineOrder) {
    lastCompletedOrder = order;
    isReceiptModalOpen = true;
  }
</script>

<div class="h-screen w-screen bg-[#f4f4f4] flex flex-row overflow-hidden font-sans select-none">
  <!-- navigasi sidebar kiosk -->
  <PosSidebar
    {activePage}
    isCollapsed={isSidebarCollapsed}
    onSelectPage={(page) => (activePage = page)}
    onToggleCollapse={() => (isSidebarCollapsed = !isSidebarCollapsed)}
  />

  <!-- area workspace -->
  <div class="flex-1 flex flex-col h-full overflow-hidden">
    <!-- baris header utama -->
    <PosHeader
      branchName={terminalInfo?.branch_name || terminalInfo?.terminal_name || 'Outlet Sleman #01'}
      {isOnline}
      {isSyncing}
      {pendingSyncCount}
      {activeCashier}
      {activeSession}
      onOpenPinModal={() => (isPinModalOpen = true)}
      onOpenSessionModal={() => (isSessionModalOpen = true)}
      onOpenMasterLockModal={() => (isMasterLockModalOpen = true)}
      onSyncNow={() => syncEngine.syncPendingOrders()}
    />

    <!-- area tampilan aktif -->
    <main class="flex-1 flex overflow-hidden">
      {#if activePage === 'penjualan'}
        <PenjualanView
          {categories}
          {products}
          {cartItems}
          {selectedCategoryId}
          {activeCashier}
          {discountPercent}
          {discountNominal}
          {orderType}
          {customerName}
          {tableNumber}
          onSelectCategory={(id: string) => (selectedCategoryId = id)}
          onAddToCart={handleAddToCart}
          onUpdateQuantity={handleUpdateQuantity}
          onUpdateNotes={handleUpdateNotes}
          onRemoveItem={handleRemoveItem}
          onClearCart={handleClearCart}
          onSetOrderType={(type: OrderType) => (orderType = type)}
          onSetCustomerName={(name: string) => (customerName = name)}
          onSetTableNumber={(table: string) => (tableNumber = table)}
          onSetDiscountPercent={(percent: number) => {
            discountPercent = percent;
            discountNominal = 0;
          }}
          onOpenPaymentModal={() => {
            if (!activeCashier) {
              isPinModalOpen = true;
            } else {
              isPaymentModalOpen = true;
            }
          }}
          onOpenPinModal={() => (isPinModalOpen = true)}
        />
      {:else if activePage === 'transaksi'}
        <TransaksiView
          orders={allOrders}
          onPrintOrder={handlePrintOrderDirect}
        />
      {:else if activePage === 'settlement'}
        <SettlementView
          {activeSession}
          {activeCashier}
          closedSessions={closedSessions}
          onOpenSessionModal={() => (isSessionModalOpen = true)}
        />
      {:else if activePage === 'produk'}
        <ProdukView
          {products}
          {categories}
          onToggleProductActive={handleToggleProductActive}
          onAddNewProduct={handleAddNewProduct}
        />
      {:else if activePage === 'profil'}
        <ProfilView
          {activeCashier}
          {cashiers}
          totalOrdersCount={allOrders.length}
          totalProductsCount={products.length}
          onOpenPinModal={() => (isPinModalOpen = true)}
          onOpenMasterLockModal={() => (isMasterLockModalOpen = true)}
          onClearLocalCache={handleClearLocalCache}
        />
      {/if}
    </main>
  </div>
</div>

<!-- modal pembayaran -->
{#if isPaymentModalOpen && activeCashier}
  <PaymentModal
    isOpen={isPaymentModalOpen}
    totalAmount={subtotalAmount}
    discountAmount={calculatedDiscountAmount}
    finalAmount={finalPayableAmount}
    items={cartItems}
    activeCashier={activeCashier}
    branchId={terminalInfo?.branch_id || 'branch-sleman-01'}
    workspaceId={terminalInfo?.workspace_id || 'ws-amore-01'}
    activeSessionId={activeSession?.id || 'sess-active-01'}
    onClose={() => (isPaymentModalOpen = false)}
    onCompleteOrder={handleCompleteOrder}
  />
{/if}

<!-- modal struk cetak -->
<ReceiptModal
  isOpen={isReceiptModalOpen}
  order={lastCompletedOrder}
  onCloseAndReset={handleCloseReceiptAndReset}
/>

<!-- modal otorisasi pin kasir -->
<CashierPinModal
  isOpen={isPinModalOpen}
  {cashiers}
  {activeCashier}
  onClose={() => (isPinModalOpen = false)}
  onSelectCashier={(cashier: CashierUser) => {
    activeCashier = cashier;
    isPinModalOpen = false;
  }}
/>

<!-- modal sesi kasir buka / tutup -->
<SessionModal
  isOpen={isSessionModalOpen}
  {activeSession}
  cashierUserId={activeCashier?.id || 'usr-pilot-01'}
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
<DevicePairingModal
  isOpen={isPairingModalOpen}
  onSuccess={handlePairingSuccess}
/>
