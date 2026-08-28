<script lang="ts">
  import { Search, CheckCircle2, Trash2 } from 'lucide-svelte';
  import type { OpenBill } from '../../types/pos';
  import { formatCurrency } from '../../services/printer-service';

  interface Props {
    isOpen: boolean;
    openBills: OpenBill[];
    onClose: () => void;
    onRestoreBill: (billId: string) => void;
    onDeleteBill: (billId: string) => void;
  }

  let {
    isOpen = false,
    openBills = [],
    onClose,
    onRestoreBill,
    onDeleteBill,
  }: Props = $props();

  let activeTab = $state<'open' | 'canceled_bill' | 'canceled_product'>('open');
  let searchQuery = $state('');

  let filteredBills = $derived(
    openBills.filter((b) => {
      const q = searchQuery.trim().toLowerCase();
      if (!q) return true;
      return (
        b.order_number.toLowerCase().includes(q) ||
        b.customer_name.toLowerCase().includes(q) ||
        b.items.some((i) => i.product.name.toLowerCase().includes(q))
      );
    })
  );

  function formatTimeElapsed(iso: string): string {
    try {
      const diffMs = Date.now() - new Date(iso).getTime();
      const diffMins = Math.floor(diffMs / 60000);
      const hours = Math.floor(diffMins / 60);
      const mins = diffMins % 60;
      if (hours > 0) return `${hours} jam ${mins} menit`;
      return `${mins} menit`;
    } catch {
      return '-';
    }
  }
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 font-sans animate-in fade-in duration-150 select-none">
    <div class="bg-white rounded-2xl border border-zinc-200 shadow-2xl w-full max-w-4xl overflow-hidden flex flex-col max-h-[85vh] animate-in zoom-in-95 duration-150">
      <!-- Moka Style Header Bar -->
      <div class="h-14 px-5 border-b border-zinc-200 flex items-center justify-between bg-zinc-50/80">
        <button
          type="button"
          onclick={onClose}
          class="px-4 py-2 text-xs font-semibold text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-100 transition-colors cursor-pointer"
        >
          Tutup
        </button>

        <h2 class="text-base font-bold text-zinc-900 tracking-tight">Daftar Bill</h2>

        <button
          type="button"
          onclick={onClose}
          class="px-4 py-2 text-xs font-semibold text-white bg-zinc-900 hover:bg-black rounded-lg transition-colors cursor-pointer shadow-xs"
        >
          + Bill Baru
        </button>
      </div>

      <!-- Tabs (Open Bill | Pembatalan Bill | Pembatalan Produk) -->
      <div class="flex border-b border-zinc-200 bg-white px-5">
        <button
          type="button"
          onclick={() => (activeTab = 'open')}
          class={`py-3.5 px-6 text-xs font-bold transition-all border-b-2 cursor-pointer ${
            activeTab === 'open'
              ? 'border-zinc-900 text-zinc-900'
              : 'border-transparent text-zinc-500 hover:text-zinc-800'
          }`}
        >
          Open Bill ({openBills.length})
        </button>
        <button
          type="button"
          onclick={() => (activeTab = 'canceled_bill')}
          class={`py-3.5 px-6 text-xs font-bold transition-all border-b-2 cursor-pointer ${
            activeTab === 'canceled_bill'
              ? 'border-zinc-900 text-zinc-900'
              : 'border-transparent text-zinc-500 hover:text-zinc-800'
          }`}
        >
          Pembatalan Bill
        </button>
        <button
          type="button"
          onclick={() => (activeTab = 'canceled_product')}
          class={`py-3.5 px-6 text-xs font-bold transition-all border-b-2 cursor-pointer ${
            activeTab === 'canceled_product'
              ? 'border-zinc-900 text-zinc-900'
              : 'border-transparent text-zinc-500 hover:text-zinc-800'
          }`}
        >
          Pembatalan Produk
        </button>
      </div>

      <!-- Search Input -->
      <div class="p-4 border-b border-zinc-100 bg-white">
        <div class="relative">
          <input
            type="text"
            bind:value={searchQuery}
            placeholder="Cari Open Bill..."
            class="w-full h-10 bg-zinc-50 border border-zinc-200 rounded-xl pl-4 pr-10 text-xs text-zinc-900 placeholder-zinc-400 focus:bg-white focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:outline-hidden transition-all"
          />
          <Search class="w-4 h-4 text-zinc-400 absolute right-3.5 top-1/2 -translate-y-1/2" />
        </div>
      </div>

      <!-- Table Content -->
      <div class="flex-1 overflow-y-auto">
        {#if activeTab === 'open'}
          {#if filteredBills.length === 0}
            <div class="py-20 flex flex-col items-center justify-center text-center text-zinc-400">
              <p class="text-sm font-semibold text-zinc-800">Tidak ada open bill</p>
              <p class="text-xs text-zinc-500 mt-1">Gunakan tombol "Simpan Bill" di kasir untuk menahan pesanan.</p>
            </div>
          {:else}
            <table class="w-full text-xs text-left border-collapse">
              <thead class="bg-zinc-100/70 border-b border-zinc-200 text-[11px] font-bold text-zinc-600 uppercase tracking-wider">
                <tr>
                  <th class="py-3 px-5">Nama Bill</th>
                  <th class="py-3 px-5">Grup Meja / Tipe</th>
                  <th class="py-3 px-5">Pelayan / Kasir</th>
                  <th class="py-3 px-5">Waktu</th>
                  <th class="py-3 px-5 text-right">Total</th>
                  <th class="py-3 px-5 text-center">Sync</th>
                  <th class="py-3 px-5 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-zinc-100">
                {#each filteredBills as bill (bill.id)}
                  <tr
                    onclick={() => {
                      onRestoreBill(bill.id);
                      onClose();
                    }}
                    class="hover:bg-zinc-100/70 cursor-pointer transition-colors group"
                  >
                    <td class="py-3.5 px-5 font-semibold text-zinc-900">
                      <div class="flex items-center gap-2">
                        <span>{bill.customer_name}</span>
                        <span class="font-mono text-[10px] text-zinc-400">({bill.order_number})</span>
                      </div>
                      <div class="text-[11px] text-zinc-500 font-normal mt-0.5 line-clamp-1">
                        {bill.items.map((i) => `${i.quantity}x ${i.product.name}`).join(', ')}
                      </div>
                    </td>
                    <td class="py-3.5 px-5 text-zinc-700">
                      <span class="px-2 py-0.5 rounded-md text-[10.5px] font-medium bg-zinc-100 text-zinc-800">
                        {bill.order_type === 'TAKE_AWAY' ? 'Take Away' : bill.order_type === 'DELIVERY' ? 'Delivery' : 'Dine In'}
                      </span>
                    </td>
                    <td class="py-3.5 px-5 text-zinc-600">Kasir Outlet</td>
                    <td class="py-3.5 px-5 font-mono text-zinc-500 text-[11px]">
                      {formatTimeElapsed(bill.saved_at)}
                    </td>
                    <td class="py-3.5 px-5 font-mono font-bold text-zinc-900 text-right">
                      {formatCurrency(bill.final_total)}
                    </td>
                    <td class="py-3.5 px-5 text-center">
                      <CheckCircle2 class="w-4 h-4 text-emerald-600 inline-block" />
                    </td>
                    <td class="py-3.5 px-5 text-right">
                      <button
                        type="button"
                        onclick={(e) => {
                          e.stopPropagation();
                          onDeleteBill(bill.id);
                        }}
                        class="p-1.5 text-zinc-400 hover:text-red-600 rounded-md hover:bg-red-50 transition-colors cursor-pointer"
                        title="Hapus Bill"
                      >
                        <Trash2 class="w-4 h-4" />
                      </button>
                    </td>
                  </tr>
                {/each}
              </tbody>
            </table>
          {/if}
        {:else}
          <div class="py-20 flex flex-col items-center justify-center text-center text-zinc-400">
            <p class="text-sm font-semibold text-zinc-800">Tidak ada riwayat pembatalan</p>
            <p class="text-xs text-zinc-500 mt-1">Data pembatalan pesanan akan tercatat di sini.</p>
          </div>
        {/if}
      </div>
    </div>
  </div>
{/if}
