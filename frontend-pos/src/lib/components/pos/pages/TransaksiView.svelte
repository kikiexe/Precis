<script lang="ts">
  import { Search, Printer, X, Receipt } from 'lucide-svelte';
  import type { OfflineOrder } from '../../../types/pos';
  import { formatCurrency } from '../../../services/printer-service';

  interface Props {
    orders: OfflineOrder[];
    onPrintOrder: (order: OfflineOrder) => void;
  }

  let { orders = [], onPrintOrder }: Props = $props();

  let searchQuery = $state('');
  let selectedFilter = $state<'ALL' | 'CASH' | 'QRIS' | 'TRANSFER' | 'PENDING'>('ALL');
  let selectedOrder = $state<OfflineOrder | null>(null);

  let filteredOrders = $derived(
    orders.filter((o) => {
      const matchSearch =
        searchQuery.trim() === '' ||
        o.order_number.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (o.customer_name && o.customer_name.toLowerCase().includes(searchQuery.toLowerCase()));

      let matchFilter = true;
      if (selectedFilter === 'PENDING') matchFilter = o.sync_status === 'PENDING';
      else if (selectedFilter !== 'ALL') matchFilter = o.payment_method === selectedFilter;

      return matchSearch && matchFilter;
    })
  );
</script>

<div class="flex-1 flex flex-col md:flex-row h-full bg-[#f4f4f4] overflow-hidden">
  <!-- Left: Transaction List Table -->
  <div class="flex-1 flex flex-col h-full overflow-hidden">
    <!-- Header & Search Controls -->
    <div class="bg-white border-b border-[#e0e0e0] p-4 space-y-3 shrink-0">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-base font-bold text-[#161616] font-display">Riwayat Transaksi POS</h2>
          <p class="text-xs text-[#525252] font-mono">Daftar struk penjualan tersimpan di database lokal & sinkronisasi</p>
        </div>
        <div class="text-xs font-mono text-[#525252]">
          Total: <span class="font-bold text-[#161616]">{filteredOrders.length}</span> Transaksi
        </div>
      </div>

      <!-- Search & Filters -->
      <div class="flex flex-wrap gap-2 items-center justify-between">
        <div class="relative flex-1 min-w-[200px]">
          <Search class="w-4 h-4 text-[#8c8c8c] absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            bind:value={searchQuery}
            placeholder="Cari nomor struk atau nama pelanggan..."
            class="w-full bg-[#f4f4f4] pl-9 pr-3 py-2 text-xs border border-[#e0e0e0] focus:border-[#0f62fe] focus:outline-none"
          />
        </div>

        <div class="flex gap-1 bg-[#f4f4f4] p-1 border border-[#e0e0e0]">
          {#each ['ALL', 'CASH', 'QRIS', 'TRANSFER', 'PENDING'] as flt}
            <button
              type="button"
              onclick={() => (selectedFilter = flt as typeof selectedFilter)}
              class={`px-3 py-1 text-xs font-mono transition-colors cursor-pointer ${
                selectedFilter === flt ? 'bg-[#0f62fe] text-white font-bold shadow-xs' : 'text-[#525252] hover:text-[#161616]'
              }`}
            >
              {flt === 'ALL' ? 'Semua' : flt}
            </button>
          {/each}
        </div>
      </div>
    </div>

    <!-- Table List -->
    <div class="flex-1 overflow-y-auto p-4">
      {#if filteredOrders.length === 0}
        <div class="h-64 flex flex-col items-center justify-center text-center text-[#8c8c8c]">
          <Receipt class="w-10 h-10 mb-2 opacity-30 text-[#8c8c8c]" />
          <p class="text-sm font-medium text-[#525252]">Belum ada transaksi</p>
          <p class="text-xs text-[#8c8c8c]">Transaksi penjualan yang selesai akan muncul di sini.</p>
        </div>
      {:else}
        <div class="bg-white border border-[#e0e0e0] overflow-x-auto shadow-xs">
          <table class="w-full text-xs text-left">
            <thead class="bg-[#f4f4f4] border-b border-[#e0e0e0] font-mono text-[11px] text-[#525252]">
              <tr>
                <th class="p-3">No. Struk</th>
                <th class="p-3">Waktu</th>
                <th class="p-3">Tipe / Meja</th>
                <th class="p-3">Kasir</th>
                <th class="p-3">Metode</th>
                <th class="p-3">Total</th>
                <th class="p-3">Sync Status</th>
                <th class="p-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#f4f4f4]">
              {#each filteredOrders as order (order.client_order_id)}
                <tr
                  class={`hover:bg-[#f4f4f4]/80 transition-colors cursor-pointer ${
                    selectedOrder?.client_order_id === order.client_order_id ? 'bg-[#0f62fe]/5' : ''
                  }`}
                  onclick={() => (selectedOrder = order)}
                >
                  <td class="p-3 font-mono font-bold text-[#0f62fe]">{order.order_number}</td>
                  <td class="p-3 font-mono text-[#525252]">
                    {new Date(order.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}
                  </td>
                  <td class="p-3 text-[#161616]">
                    <div>{order.order_type}</div>
                    {#if order.customer_name}
                      <div class="text-[10px] text-[#8c8c8c]">{order.customer_name}</div>
                    {/if}
                  </td>
                  <td class="p-3 text-[#525252]">{order.cashier_name}</td>
                  <td class="p-3 font-mono">
                    <span class="px-2 py-0.5 bg-[#f4f4f4] border border-[#e0e0e0] text-[10px] font-semibold">
                      {order.payment_method}
                    </span>
                  </td>
                  <td class="p-3 font-mono font-bold text-[#161616]">{formatCurrency(order.final_amount)}</td>
                  <td class="p-3">
                    <span class={`text-[10px] font-mono px-2 py-0.5 border ${
                      order.sync_status === 'SYNCED'
                        ? 'bg-[#24a148]/10 text-[#24a148] border-[#24a148]/30'
                        : 'bg-[#f1c21b]/10 text-[#b28900] border-[#f1c21b]/30'
                    }`}>
                      {order.sync_status}
                    </span>
                  </td>
                  <td class="p-3 text-right">
                    <button
                      type="button"
                      onclick={(e) => {
                        e.stopPropagation();
                        onPrintOrder(order);
                      }}
                      class="px-2.5 py-1 bg-[#f4f4f4] hover:bg-[#e0e0e0] border border-[#e0e0e0] text-xs font-mono text-[#161616] cursor-pointer"
                    >
                      Cetak
                    </button>
                  </td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>
      {/if}
    </div>
  </div>

  <!-- Right: Detail Drawer if Selected -->
  {#if selectedOrder}
    <div class="w-80 md:w-96 bg-white border-l border-[#e0e0e0] p-4 flex flex-col justify-between h-full shadow-lg">
      <div class="space-y-4 overflow-y-auto">
        <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3">
          <div>
            <h3 class="text-sm font-bold text-[#161616]">Detail Transaksi</h3>
            <p class="text-xs font-mono text-[#525252]">{selectedOrder.order_number}</p>
          </div>
          <button type="button" onclick={() => (selectedOrder = null)} class="text-[#8c8c8c] hover:text-[#161616] cursor-pointer">
            <X class="w-4 h-4" />
          </button>
        </div>

        <div class="space-y-1 text-xs font-mono bg-[#f4f4f4] p-3 border border-[#e0e0e0]">
          <div class="flex justify-between">
            <span class="text-[#525252]">Waktu:</span>
            <span>{new Date(selectedOrder.created_at).toLocaleString('id-ID')}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-[#525252]">Kasir Bertugas:</span>
            <span>{selectedOrder.cashier_name}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-[#525252]">Tipe Pesanan:</span>
            <span>{selectedOrder.order_type}</span>
          </div>
          {#if selectedOrder.customer_name}
            <div class="flex justify-between">
              <span class="text-[#525252]">Pelanggan:</span>
              <span>{selectedOrder.customer_name}</span>
            </div>
          {/if}
        </div>

        <!-- Item list -->
        <div class="space-y-2">
          <div class="text-xs font-bold text-[#161616]">Rincian Item:</div>
          <div class="space-y-1.5 divide-y divide-[#f4f4f4]">
            {#each selectedOrder.items as item}
              <div class="pt-1.5 text-xs flex justify-between">
                <div>
                  <div class="font-medium text-[#161616]">{item.product_name}</div>
                  <div class="text-[10px] text-[#8c8c8c] font-mono">
                    {item.quantity} x {formatCurrency(item.unit_price)}
                  </div>
                  {#if item.notes}
                    <div class="text-[10px] text-[#0f62fe]">*{item.notes}</div>
                  {/if}
                </div>
                <div class="font-mono font-bold text-[#161616]">{formatCurrency(item.subtotal)}</div>
              </div>
            {/each}
          </div>
        </div>

        <!-- Pricing calculation -->
        <div class="border-t border-[#e0e0e0] pt-3 space-y-1 text-xs font-mono">
          <div class="flex justify-between text-[#525252]">
            <span>Subtotal:</span>
            <span>{formatCurrency(selectedOrder.total_amount)}</span>
          </div>
          {#if selectedOrder.discount_amount > 0}
            <div class="flex justify-between text-[#da1e28]">
              <span>Diskon:</span>
              <span>-{formatCurrency(selectedOrder.discount_amount)}</span>
            </div>
          {/if}
          <div class="flex justify-between text-sm font-bold text-[#161616] pt-1 border-t border-[#e0e0e0]">
            <span>Total Bayar:</span>
            <span class="text-[#0f62fe]">{formatCurrency(selectedOrder.final_amount)}</span>
          </div>
        </div>
      </div>

      <!-- Action Button -->
      <div class="pt-4 border-t border-[#e0e0e0]">
        <button
          type="button"
          onclick={() => onPrintOrder(selectedOrder!)}
          class="w-full py-2.5 bg-[#161616] hover:bg-[#262626] text-white text-xs font-semibold flex items-center justify-center gap-2 cursor-pointer shadow-xs transition-colors"
        >
          <Printer class="w-4 h-4 text-[#0f62fe]" />
          <span>Cetak Ulang Struk Thermal</span>
        </button>
      </div>
    </div>
  {/if}
</div>
