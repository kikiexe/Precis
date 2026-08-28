<script lang="ts">
  import {
    Search,
    Printer,
    Send,
    RotateCcw,
    CreditCard,
    QrCode,
    Banknote,
    Clock,
    Receipt,
  } from 'lucide-svelte';
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
          orderDate.getFullYear() === now.getFullYear() && orderDate.getMonth() === now.getMonth();
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

<div class="flex h-full flex-1 flex-col overflow-hidden bg-[#f4f6f9] font-sans select-none">
  <!-- Top Header -->
  <div
    class="flex h-14 shrink-0 items-center justify-between border-b border-zinc-200 bg-white px-6 shadow-2xs"
  >
    <h1 class="text-base font-bold tracking-tight text-zinc-900">Aktivitas Transaksi</h1>
    <div class="font-mono text-xs text-zinc-500">
      Total: <span class="font-bold text-zinc-900">{filteredOrders.length}</span> Transaksi Terfilter
    </div>
  </div>

  <!-- Split Screen: Left List, Right Detail -->
  <div class="flex flex-1 overflow-hidden">
    <!-- Left Column: Filter, Search & Transaction List -->
    <div
      class="flex h-full w-84 shrink-0 flex-col border-r border-zinc-200 bg-white sm:w-96 md:w-104"
    >
      <!-- Timeframe Filter Buttons -->
      <div class="space-y-2 border-b border-zinc-200 bg-zinc-50/50 p-3">
        <div class="grid grid-cols-4 gap-1">
          <button
            type="button"
            onclick={() => (timeframeFilter = 'TODAY')}
            class={`cursor-pointer rounded-lg border px-2 py-1.5 text-[11px] font-semibold transition-all ${
              timeframeFilter === 'TODAY'
                ? 'border-zinc-900 bg-zinc-900 text-white shadow-2xs'
                : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900'
            }`}
          >
            Hari Ini
          </button>

          <button
            type="button"
            onclick={() => (timeframeFilter = 'THIS_MONTH')}
            class={`cursor-pointer rounded-lg border px-2 py-1.5 text-[11px] font-semibold transition-all ${
              timeframeFilter === 'THIS_MONTH'
                ? 'border-zinc-900 bg-zinc-900 text-white shadow-2xs'
                : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900'
            }`}
          >
            Bulan Ini
          </button>

          <button
            type="button"
            onclick={() => (timeframeFilter = 'THIS_YEAR')}
            class={`cursor-pointer rounded-lg border px-2 py-1.5 text-[11px] font-semibold transition-all ${
              timeframeFilter === 'THIS_YEAR'
                ? 'border-zinc-900 bg-zinc-900 text-white shadow-2xs'
                : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900'
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
            class={`cursor-pointer rounded-lg border px-2 py-1.5 text-[11px] font-semibold transition-all ${
              timeframeFilter === 'CUSTOM'
                ? 'border-zinc-900 bg-zinc-900 text-white shadow-2xs'
                : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900'
            }`}
          >
            Kustom
          </button>
        </div>

        {#if timeframeFilter === 'CUSTOM'}
          <div class="grid grid-cols-2 gap-2 pt-1">
            <div>
              <label
                for="filter-start-date"
                class="mb-0.5 block text-[10px] font-semibold text-zinc-500"
              >
                Dari (Min: {earliestDate()})
              </label>
              <input
                id="filter-start-date"
                type="date"
                min={earliestDate()}
                max={todayStr}
                bind:value={customStartDate}
                class="w-full rounded-md border border-zinc-200 bg-white px-2 py-1 font-mono text-[11px] text-zinc-900 focus:border-zinc-900 focus:outline-hidden"
              />
            </div>
            <div>
              <label
                for="filter-end-date"
                class="mb-0.5 block text-[10px] font-semibold text-zinc-500"
              >
                Sampai (Max: {todayStr})
              </label>
              <input
                id="filter-end-date"
                type="date"
                min={customStartDate || earliestDate()}
                max={todayStr}
                bind:value={customEndDate}
                class="w-full rounded-md border border-zinc-200 bg-white px-2 py-1 font-mono text-[11px] text-zinc-900 focus:border-zinc-900 focus:outline-hidden"
              />
            </div>
          </div>
        {/if}

        <!-- Search Input -->
        <div class="relative pt-0.5">
          <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-zinc-400" />
          <input
            type="text"
            bind:value={searchQuery}
            placeholder="Cari struk, nama pelanggan, menu..."
            class="h-8.5 w-full rounded-lg border border-zinc-200 bg-white pr-3 pl-9 text-xs text-zinc-900 placeholder-zinc-400 transition-all focus:border-zinc-900 focus:outline-hidden"
          />
        </div>
      </div>

      <!-- Date Group Header -->
      <div
        class="flex items-center justify-between border-b border-zinc-200 bg-zinc-100/90 px-4 py-2 text-[11px] font-bold tracking-wider text-zinc-600 uppercase"
      >
        <span>{dateGroupLabel()}</span>
        <span class="font-mono font-normal text-zinc-400">({filteredOrders.length})</span>
      </div>

      <!-- List of Transactions -->
      <div class="flex-1 divide-y divide-zinc-100 overflow-y-auto">
        {#if filteredOrders.length === 0}
          <div class="flex flex-col items-center justify-center py-16 text-center text-zinc-400">
            <Receipt class="mb-2 h-9 w-9 text-zinc-400 opacity-30" />
            <p class="text-sm font-semibold text-zinc-800">Tidak ada transaksi</p>
            <p class="mt-0.5 text-xs text-zinc-500">
              Transaksi yang telah dibayar akan muncul di sini.
            </p>
          </div>
        {:else}
          {#each filteredOrders as order (order.client_order_id)}
            {@const isSelected = selectedOrderId === order.client_order_id}
            {@const PayIcon = getPaymentIcon(order.payment_method)}
            <button
              type="button"
              onclick={() => (selectedOrderId = order.client_order_id)}
              class={`flex w-full cursor-pointer items-start gap-3 p-4 text-left transition-colors ${
                isSelected
                  ? 'bg-zinc-900 text-white shadow-xs'
                  : 'bg-white text-zinc-900 hover:bg-zinc-50'
              }`}
            >
              <div
                class={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ${
                  isSelected ? 'bg-zinc-800 text-white' : 'bg-zinc-100 text-zinc-600'
                }`}
              >
                <PayIcon class="h-4.5 w-4.5" />
              </div>

              <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-1">
                  <span
                    class={`font-mono text-xs font-bold sm:text-sm ${isSelected ? 'text-white' : 'text-zinc-900'}`}
                  >
                    {formatCurrency(order.final_amount)}
                  </span>
                  <span
                    class={`font-mono text-[11px] ${isSelected ? 'text-zinc-400' : 'text-zinc-400'}`}
                  >
                    {formatTime(order.created_at)}
                  </span>
                </div>

                <div
                  class={`mt-0.5 truncate text-[11px] ${isSelected ? 'text-zinc-300' : 'text-zinc-500'}`}
                >
                  {order.items.map((i) => i.product_name).join(', ')}
                </div>
              </div>
            </button>
          {/each}
        {/if}
      </div>
    </div>

    <!-- Right Column: Detail View -->
    <div class="flex-1 overflow-y-auto bg-[#f4f6f9] p-6">
      {#if selectedOrder}
        <div class="mx-auto max-w-2xl space-y-6">
          <!-- Top Action Buttons (Kirim Struk | Cetak Ulang | Pilih Refund) -->
          <div class="grid grid-cols-3 gap-3">
            <button
              type="button"
              onclick={() => onPrintOrder(selectedOrder!)}
              class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-zinc-300 bg-white px-4 py-3 text-xs font-semibold text-zinc-800 shadow-2xs transition-all hover:bg-zinc-50 active:scale-[0.99]"
            >
              <Printer class="h-4 w-4 text-zinc-900" />
              <span>Cetak Struk</span>
            </button>

            <button
              type="button"
              class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-zinc-300 bg-white px-4 py-3 text-xs font-semibold text-zinc-800 shadow-2xs transition-all hover:bg-zinc-50 active:scale-[0.99]"
            >
              <Send class="h-4 w-4 text-zinc-600" />
              <span>Kirim Struk</span>
            </button>

            <button
              type="button"
              class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-zinc-300 bg-white px-4 py-3 text-xs font-semibold text-zinc-800 shadow-2xs transition-all hover:bg-zinc-50 active:scale-[0.99]"
            >
              <RotateCcw class="h-4 w-4 text-zinc-600" />
              <span>Pilih Refund</span>
            </button>
          </div>

          <!-- Section: Detail Transaksi -->
          <div class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div
              class="border-b border-zinc-100 pb-2 text-xs font-bold tracking-wider text-zinc-500 uppercase"
            >
              Detail Transaksi
            </div>

            <div class="space-y-3 text-xs">
              <div class="flex items-center justify-between">
                <span class="flex items-center gap-2 text-zinc-500">
                  <CreditCard class="h-4 w-4 text-zinc-400" />
                  <span>Metode Pembayaran</span>
                </span>
                <span class="rounded-md bg-zinc-100 px-2.5 py-1 font-bold text-zinc-900">
                  {selectedOrder.payment_method === 'TRANSFER'
                    ? 'EDC'
                    : selectedOrder.payment_method}
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-2 text-zinc-500">
                  <Receipt class="h-4 w-4 text-zinc-400" />
                  <span>Nomor Struk</span>
                </span>
                <span class="font-mono font-bold text-zinc-900">
                  {selectedOrder.order_number}
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-2 text-zinc-500">
                  <Clock class="h-4 w-4 text-zinc-400" />
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
          <div class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div
              class="border-b border-zinc-100 pb-2 text-xs font-bold tracking-wider text-zinc-500 uppercase"
            >
              Produk ({selectedOrder.items.reduce((s, i) => s + i.quantity, 0)} Item)
            </div>

            <div class="divide-y divide-zinc-100">
              {#each selectedOrder.items as item}
                <div class="flex items-start justify-between gap-3 py-3 text-xs">
                  <div>
                    <div class="font-bold text-zinc-900">{item.product_name}</div>
                    <div class="mt-0.5 font-mono text-[11px] text-zinc-500">
                      {item.quantity} x {formatCurrency(item.unit_price)}
                    </div>
                    {#if item.notes}
                      <div class="mt-0.5 text-[11px] text-zinc-600">Catatan: {item.notes}</div>
                    {/if}
                  </div>
                  <div class="font-mono font-bold text-zinc-900">
                    {formatCurrency(item.subtotal)}
                  </div>
                </div>
              {/each}
            </div>

            <!-- Total Breakdown -->
            <div class="space-y-1.5 border-t border-zinc-200 pt-3 text-xs">
              <div class="flex justify-between text-zinc-500">
                <span>Subtotal</span>
                <span class="font-mono text-zinc-700"
                  >{formatCurrency(selectedOrder.total_amount)}</span
                >
              </div>
              {#if selectedOrder.discount_amount > 0}
                <div class="flex justify-between text-red-600">
                  <span>Diskon</span>
                  <span class="font-mono">-{formatCurrency(selectedOrder.discount_amount)}</span>
                </div>
              {/if}
              <div
                class="flex justify-between border-t border-zinc-200 pt-2 text-base font-bold text-zinc-900"
              >
                <span>Total</span>
                <span class="font-mono font-bold text-zinc-900"
                  >{formatCurrency(selectedOrder.final_amount)}</span
                >
              </div>
            </div>
          </div>
        </div>
      {:else}
        <div class="flex h-full flex-col items-center justify-center text-center text-zinc-400">
          <Receipt class="mb-3 h-12 w-12 text-zinc-400 opacity-30" />
          <p class="text-base font-semibold text-zinc-800">Pilih Transaksi</p>
          <p class="mt-1 text-xs text-zinc-500">
            Klik salah satu transaksi dari daftar di sebelah kiri untuk melihat detail struk.
          </p>
        </div>
      {/if}
    </div>
  </div>
</div>
