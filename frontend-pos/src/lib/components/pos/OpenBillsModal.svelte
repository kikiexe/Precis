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

  let { isOpen = false, openBills = [], onClose, onRestoreBill, onDeleteBill }: Props = $props();

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
  <div
    class="animate-in fade-in fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 font-sans backdrop-blur-xs duration-150 select-none"
  >
    <div
      class="animate-in zoom-in-95 flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl duration-150"
    >
      <!-- Moka Style Header Bar -->
      <div
        class="flex h-14 items-center justify-between border-b border-zinc-200 bg-zinc-50/80 px-5"
      >
        <button
          type="button"
          onclick={onClose}
          class="cursor-pointer rounded-lg border border-zinc-300 bg-white px-4 py-2 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-100"
        >
          Tutup
        </button>

        <h2 class="text-base font-bold tracking-tight text-zinc-900">Daftar Bill</h2>

        <button
          type="button"
          onclick={onClose}
          class="cursor-pointer rounded-lg bg-zinc-900 px-4 py-2 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-black"
        >
          + Bill Baru
        </button>
      </div>

      <!-- Tabs (Open Bill | Pembatalan Bill | Pembatalan Produk) -->
      <div class="flex border-b border-zinc-200 bg-white px-5">
        <button
          type="button"
          onclick={() => (activeTab = 'open')}
          class={`cursor-pointer border-b-2 px-6 py-3.5 text-xs font-bold transition-all ${
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
          class={`cursor-pointer border-b-2 px-6 py-3.5 text-xs font-bold transition-all ${
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
          class={`cursor-pointer border-b-2 px-6 py-3.5 text-xs font-bold transition-all ${
            activeTab === 'canceled_product'
              ? 'border-zinc-900 text-zinc-900'
              : 'border-transparent text-zinc-500 hover:text-zinc-800'
          }`}
        >
          Pembatalan Produk
        </button>
      </div>

      <!-- Search Input -->
      <div class="border-b border-zinc-100 bg-white p-4">
        <div class="relative">
          <input
            type="text"
            bind:value={searchQuery}
            placeholder="Cari Open Bill..."
            class="h-10 w-full rounded-xl border border-zinc-200 bg-zinc-50 pr-10 pl-4 text-xs text-zinc-900 placeholder-zinc-400 transition-all focus:border-zinc-900 focus:bg-white focus:ring-1 focus:ring-zinc-900 focus:outline-hidden"
          />
          <Search class="absolute top-1/2 right-3.5 size-4 -translate-y-1/2 text-zinc-400" />
        </div>
      </div>

      <!-- Table Content -->
      <div class="flex-1 overflow-y-auto">
        {#if activeTab === 'open'}
          {#if filteredBills.length === 0}
            <div class="flex flex-col items-center justify-center py-20 text-center text-zinc-400">
              <p class="text-sm font-semibold text-zinc-800">Tidak ada open bill</p>
              <p class="mt-1 text-xs text-zinc-500">
                Gunakan tombol "Simpan Bill" di kasir untuk menahan pesanan.
              </p>
            </div>
          {:else}
            <table class="w-full border-collapse text-left text-xs">
              <thead
                class="border-b border-zinc-200 bg-zinc-100/70 text-[11px] font-bold tracking-wider text-zinc-600 uppercase"
              >
                <tr>
                  <th class="px-5 py-3">Nama Bill</th>
                  <th class="px-5 py-3">Grup Meja / Tipe</th>
                  <th class="px-5 py-3">Pelayan / Kasir</th>
                  <th class="px-5 py-3">Waktu</th>
                  <th class="px-5 py-3 text-right">Total</th>
                  <th class="px-5 py-3 text-center">Sync</th>
                  <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-zinc-100">
                {#each filteredBills as bill (bill.id)}
                  <tr
                    onclick={() => {
                      onRestoreBill(bill.id);
                      onClose();
                    }}
                    class="group cursor-pointer transition-colors hover:bg-zinc-100/70"
                  >
                    <td class="px-5 py-3.5 font-semibold text-zinc-900">
                      <div class="flex items-center gap-2">
                        <span>{bill.customer_name}</span>
                        <span class="font-mono text-[10px] text-zinc-400"
                          >({bill.order_number})</span
                        >
                      </div>
                      <div class="mt-0.5 line-clamp-1 text-[11px] font-normal text-zinc-500">
                        {bill.items.map((i) => `${i.quantity}x ${i.product.name}`).join(', ')}
                      </div>
                    </td>
                    <td class="px-5 py-3.5 text-zinc-700">
                      <span
                        class="rounded-md bg-zinc-100 px-2 py-0.5 text-[10.5px] font-medium text-zinc-800"
                      >
                        {bill.order_type === 'TAKE_AWAY'
                          ? 'Take Away'
                          : bill.order_type === 'DELIVERY'
                            ? 'Delivery'
                            : 'Dine In'}
                      </span>
                    </td>
                    <td class="px-5 py-3.5 text-zinc-600">Kasir Outlet</td>
                    <td class="px-5 py-3.5 font-mono text-[11px] text-zinc-500">
                      {formatTimeElapsed(bill.saved_at)}
                    </td>
                    <td class="px-5 py-3.5 text-right font-mono font-bold text-zinc-900">
                      {formatCurrency(bill.final_total)}
                    </td>
                    <td class="px-5 py-3.5 text-center">
                      <CheckCircle2 class="inline-block size-4 text-emerald-600" />
                    </td>
                    <td class="px-5 py-3.5 text-right">
                      <button
                        type="button"
                        onclick={(e) => {
                          e.stopPropagation();
                          onDeleteBill(bill.id);
                        }}
                        class="cursor-pointer rounded-md p-1.5 text-zinc-400 transition-colors hover:bg-red-50 hover:text-red-600"
                        title="Hapus Bill"
                      >
                        <Trash2 class="size-4" />
                      </button>
                    </td>
                  </tr>
                {/each}
              </tbody>
            </table>
          {/if}
        {:else}
          <div class="flex flex-col items-center justify-center py-20 text-center text-zinc-400">
            <p class="text-sm font-semibold text-zinc-800">Tidak ada riwayat pembatalan</p>
            <p class="mt-1 text-xs text-zinc-500">Data pembatalan pesanan akan tercatat di sini.</p>
          </div>
        {/if}
      </div>
    </div>
  </div>
{/if}
