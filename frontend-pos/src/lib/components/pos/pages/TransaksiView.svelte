<script lang="ts">
  import { Search, Printer, Send, RotateCcw, CreditCard, QrCode, Banknote, Clock, Receipt } from 'lucide-svelte';
  import type { OfflineOrder } from '../../../types/pos';
  import { formatCurrency } from '../../../services/printer-service';

  interface Props {
    orders: OfflineOrder[];
    onPrintOrder: (order: OfflineOrder) => void;
  }

  let { orders = [], onPrintOrder }: Props = $props();

  let searchQuery = $state('');
  let selectedOrderId = $state<string | null>(null);
  let timeframeFilter = $state<'TODAY' | 'THIS_MONTH' | 'THIS_YEAR' | 'CUSTOM'>('TODAY');

  const todayStr = new Date().toISOString().substring(0, 10);
  
  let earliestDate = $derived(() => {
    if (orders && orders.length > 0) {
      const sorted = [...orders]
        .map((o) => (o.created_at ? o.created_at.substring(0, 10) : ''))
        .filter((d) => d.length === 10)
        .sort();
      if (sorted.length > 0) return sorted[0];
    }
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
  });

  let customStartDate = $state(todayStr);
  let customEndDate = $state(todayStr);

  $effect(() => {
    const minDate = earliestDate();
    if (customStartDate < minDate) {
      customStartDate = minDate;
    }
    if (customEndDate > todayStr) {
      customEndDate = todayStr;
    }
  });

  let filteredOrders = $derived(
    orders.filter((o) => {
      // 1. Timeframe Filter
      const orderDate = new Date(o.created_at);
      const now = new Date();

      let matchTimeframe = true;
      if (timeframeFilter === 'TODAY') {
        matchTimeframe =
          orderDate.getFullYear() === now.getFullYear() &&
          orderDate.getMonth() === now.getMonth() &&
          orderDate.getDate() === now.getDate();
      } else if (timeframeFilter === 'THIS_MONTH') {
        matchTimeframe =
          orderDate.getFullYear() === now.getFullYear() &&
          orderDate.getMonth() === now.getMonth();
      } else if (timeframeFilter === 'THIS_YEAR') {
        matchTimeframe = orderDate.getFullYear() === now.getFullYear();
      } else if (timeframeFilter === 'CUSTOM') {
        const orderDateStr = orderDate.toISOString().substring(0, 10);
        matchTimeframe = orderDateStr >= customStartDate && orderDateStr <= customEndDate;
      }

      if (!matchTimeframe) return false;

      // 2. Search Query Filter
      const q = searchQuery.trim().toLowerCase();
      if (!q) return true;
      return (
        o.order_number.toLowerCase().includes(q) ||
        (o.customer_name && o.customer_name.toLowerCase().includes(q)) ||
        o.items.some((i) => i.product_name.toLowerCase().includes(q))
      );
    })
  );

  let dateGroupLabel = $derived(() => {
    const now = new Date();
    if (timeframeFilter === 'TODAY') return 'Hari Ini';
    if (timeframeFilter === 'THIS_MONTH') {
      return `Bulan Ini (${now.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })})`;
    }
    if (timeframeFilter === 'THIS_YEAR') {
      return `Tahun Ini (${now.getFullYear()})`;
    }
    return `Kustom (${customStartDate} s/d ${customEndDate})`;
  });

  // Default to first order if available
  $effect(() => {
    if (filteredOrders.length > 0) {
      const exists = filteredOrders.some((o) => o.client_order_id === selectedOrderId);
      if (!exists) {
        selectedOrderId = filteredOrders[0].client_order_id;
      }
    } else {
      selectedOrderId = null;
    }
  });

  let selectedOrder = $derived(
    filteredOrders.find((o) => o.client_order_id === selectedOrderId) || filteredOrders[0] || null
  );

  function formatTime(iso: string): string {
    try {
      const date = new Date(iso);
      return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    } catch {
      return '';
    }
  }

  function formatFullDate(iso: string): string {
    try {
      const date = new Date(iso);
      return (
        date.toLocaleDateString('id-ID', {
          day: 'numeric',
          month: 'long',
          year: 'numeric',
        }) + ` pada ${formatTime(iso)}`
      );
    } catch {
      return '';
    }
  }

  function getPaymentIcon(method: string) {
    if (method === 'QRIS') return QrCode;
    if (method === 'CASH') return Banknote;
    return CreditCard;
  }
</script>

<div class="flex-1 flex flex-col h-full bg-[#f4f6f9] overflow-hidden font-sans select-none">
  <!-- Top Header -->
  <div class="h-14 bg-white border-b border-zinc-200 px-6 flex items-center justify-between shrink-0 shadow-2xs">
    <h1 class="text-base font-bold text-zinc-900 tracking-tight">Aktivitas Transaksi</h1>
    <div class="text-xs font-mono text-zinc-500">
      Total: <span class="font-bold text-zinc-900">{filteredOrders.length}</span> Transaksi Terfilter
    </div>
  </div>

  <!-- Split Screen: Left List, Right Detail -->
  <div class="flex-1 flex overflow-hidden">
    <!-- Left Column: Filter, Search & Transaction List -->
    <div class="w-84 sm:w-96 md:w-104 bg-white border-r border-zinc-200 flex flex-col h-full shrink-0">
      
      <!-- Timeframe Filter Buttons -->
      <div class="p-3 border-b border-zinc-200 bg-zinc-50/50 space-y-2">
        <div class="grid grid-cols-4 gap-1">
          <button
            type="button"
            onclick={() => (timeframeFilter = 'TODAY')}
            class={`py-1.5 px-2 rounded-lg text-[11px] font-semibold transition-all cursor-pointer border ${
              timeframeFilter === 'TODAY'
                ? 'bg-zinc-900 text-white border-zinc-900 shadow-2xs'
                : 'bg-white text-zinc-600 hover:text-zinc-900 border-zinc-200 hover:bg-zinc-100'
            }`}
          >
            Hari Ini
          </button>

          <button
            type="button"
            onclick={() => (timeframeFilter = 'THIS_MONTH')}
            class={`py-1.5 px-2 rounded-lg text-[11px] font-semibold transition-all cursor-pointer border ${
              timeframeFilter === 'THIS_MONTH'
                ? 'bg-zinc-900 text-white border-zinc-900 shadow-2xs'
                : 'bg-white text-zinc-600 hover:text-zinc-900 border-zinc-200 hover:bg-zinc-100'
            }`}
          >
            Bulan Ini
          </button>

          <button
            type="button"
            onclick={() => (timeframeFilter = 'THIS_YEAR')}
            class={`py-1.5 px-2 rounded-lg text-[11px] font-semibold transition-all cursor-pointer border ${
              timeframeFilter === 'THIS_YEAR'
                ? 'bg-zinc-900 text-white border-zinc-900 shadow-2xs'
                : 'bg-white text-zinc-600 hover:text-zinc-900 border-zinc-200 hover:bg-zinc-100'
            }`}
          >
            Tahun Ini
          </button>

          <button
            type="button"
            onclick={() => {
              timeframeFilter = 'CUSTOM';
              customStartDate = earliestDate();
              customEndDate = todayStr;
            }}
            class={`py-1.5 px-2 rounded-lg text-[11px] font-semibold transition-all cursor-pointer border ${
              timeframeFilter === 'CUSTOM'
                ? 'bg-zinc-900 text-white border-zinc-900 shadow-2xs'
                : 'bg-white text-zinc-600 hover:text-zinc-900 border-zinc-200 hover:bg-zinc-100'
            }`}
          >
            Kustom
          </button>
        </div>

        {#if timeframeFilter === 'CUSTOM'}
          <div class="grid grid-cols-2 gap-2 pt-1">
            <div>
              <label for="filter-start-date" class="text-[10px] font-semibold text-zinc-500 block mb-0.5">
                Dari (Min: {earliestDate()})
              </label>
              <input
                id="filter-start-date"
                type="date"
                min={earliestDate()}
                max={todayStr}
                bind:value={customStartDate}
                class="w-full px-2 py-1 bg-white border border-zinc-200 rounded-md text-[11px] font-mono text-zinc-900 focus:outline-hidden focus:border-zinc-900"
              />
            </div>
            <div>
              <label for="filter-end-date" class="text-[10px] font-semibold text-zinc-500 block mb-0.5">
                Sampai (Max: {todayStr})
              </label>
              <input
                id="filter-end-date"
                type="date"
                min={customStartDate || earliestDate()}
                max={todayStr}
                bind:value={customEndDate}
                class="w-full px-2 py-1 bg-white border border-zinc-200 rounded-md text-[11px] font-mono text-zinc-900 focus:outline-hidden focus:border-zinc-900"
              />
            </div>
          </div>
        {/if}

        <!-- Search Input -->
        <div class="relative pt-0.5">
          <Search class="w-4 h-4 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            bind:value={searchQuery}
            placeholder="Cari struk, nama pelanggan, menu..."
            class="w-full h-8.5 bg-white border border-zinc-200 rounded-lg pl-9 pr-3 text-xs text-zinc-900 placeholder-zinc-400 focus:border-zinc-900 focus:outline-hidden transition-all"
          />
        </div>
      </div>

      <!-- Date Group Header -->
      <div class="px-4 py-2 bg-zinc-100/90 border-b border-zinc-200 text-[11px] font-bold text-zinc-600 tracking-wider uppercase flex items-center justify-between">
        <span>{dateGroupLabel()}</span>
        <span class="font-mono text-zinc-400 font-normal">({filteredOrders.length})</span>
      </div>

      <!-- List of Transactions -->
      <div class="flex-1 overflow-y-auto divide-y divide-zinc-100">
        {#if filteredOrders.length === 0}
          <div class="py-16 flex flex-col items-center justify-center text-center text-zinc-400">
            <Receipt class="w-9 h-9 mb-2 opacity-30 text-zinc-400" />
            <p class="text-sm font-semibold text-zinc-800">Tidak ada transaksi</p>
            <p class="text-xs text-zinc-500 mt-0.5">Transaksi yang telah dibayar akan muncul di sini.</p>
          </div>
        {:else}
          {#each filteredOrders as order (order.client_order_id)}
            {@const isSelected = selectedOrderId === order.client_order_id}
            {@const PayIcon = getPaymentIcon(order.payment_method)}
            <button
              type="button"
              onclick={() => (selectedOrderId = order.client_order_id)}
              class={`w-full p-4 text-left transition-colors cursor-pointer flex items-start gap-3 ${
                isSelected
                  ? 'bg-zinc-900 text-white shadow-xs'
                  : 'hover:bg-zinc-50 text-zinc-900 bg-white'
              }`}
            >
              <div class={`w-9 h-9 rounded-lg flex items-center justify-center shrink-0 ${
                isSelected ? 'bg-zinc-800 text-white' : 'bg-zinc-100 text-zinc-600'
              }`}>
                <PayIcon class="w-4.5 h-4.5" />
              </div>

              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-1">
                  <span class={`font-mono font-bold text-xs sm:text-sm ${isSelected ? 'text-white' : 'text-zinc-900'}`}>
                    {formatCurrency(order.final_amount)}
                  </span>
                  <span class={`font-mono text-[11px] ${isSelected ? 'text-zinc-400' : 'text-zinc-400'}`}>
                    {formatTime(order.created_at)}
                  </span>
                </div>

                <div class={`text-[11px] mt-0.5 truncate ${isSelected ? 'text-zinc-300' : 'text-zinc-500'}`}>
                  {order.items.map((i) => i.product_name).join(', ')}
                </div>
              </div>
            </button>
          {/each}
        {/if}
      </div>
    </div>

    <!-- Right Column: Detail View -->
    <div class="flex-1 bg-[#f4f6f9] p-6 overflow-y-auto">
      {#if selectedOrder}
        <div class="max-w-2xl mx-auto space-y-6">
          <!-- Top Action Buttons (Kirim Struk | Cetak Ulang | Pilih Refund) -->
          <div class="grid grid-cols-3 gap-3">
            <button
              type="button"
              onclick={() => onPrintOrder(selectedOrder!)}
              class="py-3 px-4 bg-white hover:bg-zinc-50 border border-zinc-300 text-zinc-800 font-semibold text-xs rounded-xl flex items-center justify-center gap-2 cursor-pointer shadow-2xs transition-all active:scale-[0.99]"
            >
              <Printer class="w-4 h-4 text-zinc-900" />
              <span>Cetak Struk</span>
            </button>

            <button
              type="button"
              class="py-3 px-4 bg-white hover:bg-zinc-50 border border-zinc-300 text-zinc-800 font-semibold text-xs rounded-xl flex items-center justify-center gap-2 cursor-pointer shadow-2xs transition-all active:scale-[0.99]"
            >
              <Send class="w-4 h-4 text-zinc-600" />
              <span>Kirim Struk</span>
            </button>

            <button
              type="button"
              class="py-3 px-4 bg-white hover:bg-zinc-50 border border-zinc-300 text-zinc-800 font-semibold text-xs rounded-xl flex items-center justify-center gap-2 cursor-pointer shadow-2xs transition-all active:scale-[0.99]"
            >
              <RotateCcw class="w-4 h-4 text-zinc-600" />
              <span>Pilih Refund</span>
            </button>
          </div>

          <!-- Section: Detail Transaksi -->
          <div class="bg-white rounded-2xl border border-zinc-200 p-5 shadow-sm space-y-4">
            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider border-b border-zinc-100 pb-2">
              Detail Transaksi
            </div>

            <div class="space-y-3 text-xs">
              <div class="flex items-center justify-between">
                <span class="text-zinc-500 flex items-center gap-2">
                  <CreditCard class="w-4 h-4 text-zinc-400" />
                  <span>Metode Pembayaran</span>
                </span>
                <span class="font-bold text-zinc-900 bg-zinc-100 px-2.5 py-1 rounded-md">
                  {selectedOrder.payment_method === 'TRANSFER' ? 'EDC' : selectedOrder.payment_method}
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-zinc-500 flex items-center gap-2">
                  <Receipt class="w-4 h-4 text-zinc-400" />
                  <span>Nomor Struk</span>
                </span>
                <span class="font-mono font-bold text-zinc-900">
                  {selectedOrder.order_number}
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-zinc-500 flex items-center gap-2">
                  <Clock class="w-4 h-4 text-zinc-400" />
                  <span>Waktu Pembelian</span>
                </span>
                <span class="font-medium text-zinc-800">
                  {formatFullDate(selectedOrder.created_at)}
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-zinc-500">Kasir Bertugas</span>
                <span class="font-medium text-zinc-800">{selectedOrder.cashier_name}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-zinc-500">Tipe Pesanan</span>
                <span class="font-medium text-zinc-800">{selectedOrder.order_type}</span>
              </div>

              {#if selectedOrder.customer_name}
                <div class="flex items-center justify-between">
                  <span class="text-zinc-500">Nama Pelanggan</span>
                  <span class="font-medium text-zinc-800">{selectedOrder.customer_name}</span>
                </div>
              {/if}
            </div>
          </div>

          <!-- Section: Produk -->
          <div class="bg-white rounded-2xl border border-zinc-200 p-5 shadow-sm space-y-4">
            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider border-b border-zinc-100 pb-2">
              Produk ({selectedOrder.items.reduce((s, i) => s + i.quantity, 0)} Item)
            </div>

            <div class="divide-y divide-zinc-100">
              {#each selectedOrder.items as item}
                <div class="py-3 flex items-start justify-between gap-3 text-xs">
                  <div>
                    <div class="font-bold text-zinc-900">{item.product_name}</div>
                    <div class="text-zinc-500 font-mono text-[11px] mt-0.5">
                      {item.quantity} x {formatCurrency(item.unit_price)}
                    </div>
                    {#if item.notes}
                      <div class="text-[11px] text-zinc-600 mt-0.5">Catatan: {item.notes}</div>
                    {/if}
                  </div>
                  <div class="font-mono font-bold text-zinc-900">
                    {formatCurrency(item.subtotal)}
                  </div>
                </div>
              {/each}
            </div>

            <!-- Total Breakdown -->
            <div class="border-t border-zinc-200 pt-3 space-y-1.5 text-xs">
              <div class="flex justify-between text-zinc-500">
                <span>Subtotal</span>
                <span class="font-mono text-zinc-700">{formatCurrency(selectedOrder.total_amount)}</span>
              </div>
              {#if selectedOrder.discount_amount > 0}
                <div class="flex justify-between text-red-600">
                  <span>Diskon</span>
                  <span class="font-mono">-{formatCurrency(selectedOrder.discount_amount)}</span>
                </div>
              {/if}
              <div class="flex justify-between text-base font-bold text-zinc-900 pt-2 border-t border-zinc-200">
                <span>Total</span>
                <span class="font-mono text-zinc-900 font-bold">{formatCurrency(selectedOrder.final_amount)}</span>
              </div>
            </div>
          </div>
        </div>
      {:else}
        <div class="h-full flex flex-col items-center justify-center text-center text-zinc-400">
          <Receipt class="w-12 h-12 mb-3 opacity-30 text-zinc-400" />
          <p class="text-base font-semibold text-zinc-800">Pilih Transaksi</p>
          <p class="text-xs text-zinc-500 mt-1">Klik salah satu transaksi dari daftar di sebelah kiri untuk melihat detail struk.</p>
        </div>
      {/if}
    </div>
  </div>
</div>
