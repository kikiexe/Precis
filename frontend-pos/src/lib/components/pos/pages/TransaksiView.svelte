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

<div class="flex-1 flex flex-col md:flex-row h-full bg-[#eeece7]/30 overflow-hidden font-sans">
  <!-- Left: Transaction List Table -->
  <div class="flex-1 flex flex-col h-full overflow-hidden">
    <!-- Header & Search Controls -->
    <div class="bg-white border-b border-[#d9d9dd] p-4 space-y-3 shrink-0">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-base font-medium text-[#212121] tracking-tight">Riwayat Transaksi POS</h2>
          <p class="text-xs text-[#616161] font-normal mt-0.5">Daftar struk penjualan tersimpan di database lokal &amp; sinkronisasi</p>
        </div>
        <div class="text-xs font-mono text-[#75758a]">
          Total: <span class="font-medium text-[#212121]">{filteredOrders.length}</span> Transaksi
        </div>
      </div>

      <!-- Search & Filters -->
      <div class="flex flex-wrap gap-2.5 items-center justify-between">
        <div class="relative flex-1 min-w-[200px]">
          <Search class="w-4 h-4 text-[#93939f] absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            bind:value={searchQuery}
            placeholder="Cari nomor struk atau nama pelanggan..."
            class="w-full bg-[#eeece7]/40 pl-10 pr-4 py-2 text-xs rounded-full border border-[#d9d9dd] placeholder-[#93939f] text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden transition-all"
          />
        </div>

        <div class="flex gap-1 bg-[#eeece7]/60 p-1 rounded-full border border-[#d9d9dd]">
          {#each ['ALL', 'CASH', 'QRIS', 'TRANSFER', 'PENDING'] as flt}
            <button
              type="button"
              onclick={() => (selectedFilter = flt as typeof selectedFilter)}
              class={`px-3.5 py-1 text-xs font-mono rounded-full transition-all cursor-pointer ${
                selectedFilter === flt ? 'bg-[#17171c] text-white font-medium shadow-none' : 'text-[#616161] hover:text-[#212121]'
              }`}
            >
              {flt === 'ALL' ? 'Semua' : flt}
            </button>
          {/each}
        </div>
      </div>
    </div>

    <!-- Table List -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-5">
      {#if filteredOrders.length === 0}
        <div class="h-64 flex flex-col items-center justify-center text-center text-[#93939f]">
          <Receipt class="w-10 h-10 mb-2 opacity-30 text-[#93939f]" />
          <p class="text-sm font-medium text-[#212121]">Belum ada transaksi</p>
          <p class="text-xs text-[#75758a]">Transaksi penjualan yang selesai akan muncul di sini.</p>
        </div>
      {:else}
        <div class="bg-white border border-[#d9d9dd] rounded-[20px] overflow-hidden shadow-none">
          <table class="w-full text-xs text-left border-collapse">
            <thead class="bg-[#eeece7]/50 border-b border-[#d9d9dd] font-mono text-[11px] text-[#616161]">
              <tr>
                <th class="p-3.5 font-medium">No. Struk</th>
                <th class="p-3.5 font-medium">Waktu</th>
                <th class="p-3.5 font-medium">Tipe / Meja</th>
                <th class="p-3.5 font-medium">Kasir</th>
                <th class="p-3.5 font-medium">Metode</th>
                <th class="p-3.5 font-medium">Total</th>
                <th class="p-3.5 font-medium">Sync Status</th>
                <th class="p-3.5 text-right font-medium">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#d9d9dd]/60">
              {#each filteredOrders as order (order.client_order_id)}
                <tr
                  class={`hover:bg-[#eeece7]/20 transition-colors cursor-pointer ${
                    selectedOrder?.client_order_id === order.client_order_id ? 'bg-[#eeece7]/50 font-medium' : ''
                  }`}
                  onclick={() => (selectedOrder = order)}
                >
                  <td class="p-3.5 font-mono font-medium text-[#1863dc]">{order.order_number}</td>
                  <td class="p-3.5 font-mono text-[#75758a]">
                    {new Date(order.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}
                  </td>
                  <td class="p-3.5 text-[#212121]">
                    <div>{order.order_type}</div>
                    {#if order.customer_name}
                      <div class="text-[10px] text-[#75758a] font-normal">{order.customer_name}</div>
                    {/if}
                  </td>
                  <td class="p-3.5 text-[#616161]">{order.cashier_name}</td>
                  <td class="p-3.5 font-mono">
                    <span class="px-2.5 py-0.5 bg-[#eeece7] text-[#212121] rounded-full text-[10px] font-medium">
                      {order.payment_method}
                    </span>
                  </td>
                  <td class="p-3.5 font-mono font-medium text-[#212121]">{formatCurrency(order.final_amount)}</td>
                  <td class="p-3.5">
                    <span class={`text-[10px] font-mono px-2.5 py-0.5 rounded-full font-medium ${
                      order.sync_status === 'SYNCED'
                        ? 'bg-[#edfce9] text-[#003c33]'
                        : 'bg-[#eeece7] text-[#616161]'
                    }`}>
                      {order.sync_status}
                    </span>
                  </td>
                  <td class="p-3.5 text-right">
                    <button
                      type="button"
                      onclick={(e) => {
                        e.stopPropagation();
                        onPrintOrder(order);
                      }}
                      class="px-3 py-1 bg-white hover:bg-[#eeece7] border border-[#d9d9dd] rounded-full text-xs font-mono text-[#212121] cursor-pointer transition-all"
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
    <div class="w-80 md:w-96 bg-white border-l border-[#d9d9dd] p-5 flex flex-col justify-between h-full shadow-none font-sans">
      <div class="space-y-4 overflow-y-auto">
        <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
          <div>
            <h3 class="text-sm font-medium text-[#212121]">Detail Transaksi</h3>
            <p class="text-xs font-mono text-[#75758a] mt-0.5">{selectedOrder.order_number}</p>
          </div>
          <button type="button" onclick={() => (selectedOrder = null)} class="text-[#93939f] hover:text-[#212121] cursor-pointer p-1">
            <X class="w-4 h-4" />
          </button>
        </div>

        <div class="space-y-1.5 text-xs font-mono bg-[#eeece7]/30 p-3.5 rounded-[16px] border border-[#d9d9dd]">
          <div class="flex justify-between">
            <span class="text-[#75758a]">Waktu:</span>
            <span class="text-[#212121]">{new Date(selectedOrder.created_at).toLocaleString('id-ID')}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-[#75758a]">Kasir Bertugas:</span>
            <span class="text-[#212121]">{selectedOrder.cashier_name}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-[#75758a]">Tipe Pesanan:</span>
            <span class="text-[#212121]">{selectedOrder.order_type}</span>
          </div>
          {#if selectedOrder.customer_name}
            <div class="flex justify-between">
              <span class="text-[#75758a]">Pelanggan:</span>
              <span class="text-[#212121]">{selectedOrder.customer_name}</span>
            </div>
          {/if}
        </div>

        <!-- Item list -->
        <div class="space-y-2">
          <div class="text-xs font-medium text-[#212121]">Rincian Item:</div>
          <div class="space-y-2 divide-y divide-[#d9d9dd]/60">
            {#each selectedOrder.items as item}
              <div class="pt-2 text-xs flex justify-between">
                <div>
                  <div class="font-medium text-[#212121]">{item.product_name}</div>
                  <div class="text-[10px] text-[#75758a] font-mono">
                    {item.quantity} x {formatCurrency(item.unit_price)}
                  </div>
                  {#if item.notes}
                    <div class="text-[10px] text-[#1863dc]">*{item.notes}</div>
                  {/if}
                </div>
                <div class="font-mono font-medium text-[#212121]">{formatCurrency(item.subtotal)}</div>
              </div>
            {/each}
          </div>
        </div>

        <!-- Pricing calculation -->
        <div class="border-t border-[#d9d9dd] pt-3.5 space-y-1.5 text-xs font-mono">
          <div class="flex justify-between text-[#616161]">
            <span>Subtotal:</span>
            <span>{formatCurrency(selectedOrder.total_amount)}</span>
          </div>
          {#if selectedOrder.discount_amount > 0}
            <div class="flex justify-between text-[#b30000]">
              <span>Diskon:</span>
              <span>-{formatCurrency(selectedOrder.discount_amount)}</span>
            </div>
          {/if}
          <div class="flex justify-between text-sm font-medium text-[#212121] pt-2 border-t border-[#d9d9dd]">
            <span>Total Bayar:</span>
            <span class="text-[#17171c] font-semibold">{formatCurrency(selectedOrder.final_amount)}</span>
          </div>
        </div>
      </div>

      <!-- Action Button -->
      <div class="pt-4 border-t border-[#d9d9dd]">
        <button
          type="button"
          onclick={() => onPrintOrder(selectedOrder!)}
          class="w-full py-3 bg-[#17171c] hover:bg-[#000000] text-white text-xs font-medium rounded-full flex items-center justify-center gap-2 cursor-pointer shadow-none transition-all"
        >
          <Printer class="w-4 h-4 text-[#1863dc]" />
          <span>Cetak Ulang Struk Thermal</span>
        </button>
      </div>
    </div>
  {/if}
</div>
