<script lang="ts">
  import { Search, Calendar, CheckCircle2 } from 'lucide-svelte';
  import type { RawMaterial } from '../../../../types/pos';

  interface Props {
    rawMaterials: RawMaterial[];
    onOpenOpname: (mat: RawMaterial) => void;
  }

  let { rawMaterials = [], onOpenOpname }: Props = $props();

  let searchQuery = $state('');
  let selectedDate = $state(new Date().toISOString().substring(0, 10));

  let filteredMaterials = $derived(
    rawMaterials.filter((m) => {
      return (
        searchQuery.trim() === '' ||
        m.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (m.category_name && m.category_name.toLowerCase().includes(searchQuery.toLowerCase()))
      );
    })
  );
</script>

<div class="space-y-3 font-sans select-none">
  <!-- Excel Toolbar: Date Selection & Filter -->
  <div class="bg-white border border-zinc-200 rounded-xl p-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shadow-2xs">
    <div class="flex items-center gap-2 flex-1">
      <div class="relative flex-1 max-w-md">
        <Search class="w-4 h-4 text-zinc-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Cari bahan baku pada lembar rekap harian..."
          class="w-full pl-9 pr-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg text-xs text-zinc-900 placeholder-zinc-400 focus:bg-white focus:border-zinc-900 focus:outline-hidden transition-all"
        />
        {#if searchQuery}
          <button
            type="button"
            onclick={() => (searchQuery = '')}
            class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-mono text-zinc-400 hover:text-zinc-700 cursor-pointer"
          >
            ✕
          </button>
        {/if}
      </div>

      <!-- Date Filter -->
      <div class="flex items-center gap-2 px-3 py-1.5 bg-zinc-50 border border-zinc-200 rounded-lg">
        <Calendar class="w-3.5 h-3.5 text-zinc-500" />
        <input
          type="date"
          bind:value={selectedDate}
          class="bg-transparent text-xs font-mono font-medium text-zinc-800 focus:outline-hidden cursor-pointer"
        />
      </div>
    </div>

    <div class="text-xs font-mono text-zinc-500 self-center">
      Status: <span class="font-bold text-zinc-900">Rekonsiliasi Realtime</span>
    </div>
  </div>

  <!-- Excel Spreadsheet: Daily Reconciliation Sheet -->
  <div class="bg-white border border-zinc-200 rounded-xl overflow-hidden shadow-2xs">
    <div class="overflow-x-auto">
      <table class="w-full text-xs text-left border-collapse">
        <thead class="bg-zinc-100/80 border-b border-zinc-200 font-mono text-[11px] font-bold text-zinc-600 uppercase tracking-wider">
          <tr class="divide-x divide-zinc-200/80">
            <th class="py-3 px-3 w-12 text-center">No.</th>
            <th class="py-3 px-4">Bahan Baku</th>
            <th class="py-3 px-3 w-20 text-center">Satuan</th>
            <th class="py-3 px-4 w-28 text-right bg-zinc-50">Stok Awal (Kemarin)</th>
            <th class="py-3 px-4 w-28 text-right bg-emerald-50/50 text-emerald-800">Masuk / Restock (+)</th>
            <th class="py-3 px-4 w-28 text-right bg-red-50/50 text-red-800">Terpakai / Waste (-)</th>
            <th class="py-3 px-4 w-28 text-right bg-zinc-50">Ekspektasi Sistem</th>
            <th class="py-3 px-4 w-28 text-right font-bold text-zinc-900">Stok Fisik Aktual</th>
            <th class="py-3 px-4 w-24 text-right">Varian (+/-)</th>
            <th class="py-3 px-4 w-28 text-center">Aksi Opname</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200/70 font-mono">
          {#if filteredMaterials.length === 0}
            <tr>
              <td colspan="10" class="py-16 text-center text-zinc-400 font-sans">
                <p class="text-sm font-semibold text-zinc-800">Tidak ada data rekap bahan baku</p>
                <p class="text-xs text-zinc-500 mt-0.5">Ubah kata kunci pencarian atau tanggal audit.</p>
              </td>
            </tr>
          {:else}
            {#each filteredMaterials as mat, idx (mat.id)}
              {@const startStock = mat.stock_previous_day ?? mat.current_stock}
              {@const inStock = mat.stock_in_today ?? 0}
              {@const usedStock = mat.stock_used_today ?? 0}
              {@const expectedStock = startStock + inStock - usedStock}
              {@const variance = mat.current_stock - expectedStock}
              <tr class={`divide-x divide-zinc-200/60 transition-colors hover:bg-zinc-50/80 ${
                idx % 2 === 1 ? 'bg-zinc-50/30' : 'bg-white'
              }`}>
                <!-- No -->
                <td class="py-3 px-3 text-center text-zinc-400 text-[11px]">
                  {idx + 1}
                </td>

                <!-- Nama Bahan Baku -->
                <td class="py-3 px-4 font-sans font-semibold text-zinc-900">
                  <div class="flex items-center gap-2">
                    <span>{mat.name}</span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] bg-zinc-100 text-zinc-600 font-normal border border-zinc-200/50">
                      {mat.category_name || 'Bahan'}
                    </span>
                  </div>
                </td>

                <!-- Satuan -->
                <td class="py-3 px-3 text-center text-zinc-500 text-[11px]">
                  {mat.unit}
                </td>

                <!-- Stok Awal -->
                <td class="py-3 px-4 text-right text-zinc-700 bg-zinc-50/50">
                  {startStock}
                </td>

                <!-- Masuk / Restock -->
                <td class="py-3 px-4 text-right font-bold text-emerald-700 bg-emerald-50/30">
                  +{inStock}
                </td>

                <!-- Terpakai / Waste -->
                <td class="py-3 px-4 text-right font-bold text-red-700 bg-red-50/30">
                  -{usedStock}
                </td>

                <!-- Ekspektasi Sistem -->
                <td class="py-3 px-4 text-right font-bold text-zinc-800 bg-zinc-50/50">
                  {expectedStock}
                </td>

                <!-- Stok Fisik Aktual -->
                <td class="py-3 px-4 text-right font-bold text-zinc-900 text-xs">
                  {mat.current_stock}
                </td>

                <!-- Varian -->
                <td class={`py-3 px-4 text-right font-bold ${
                  variance === 0
                    ? 'text-emerald-600'
                    : variance < 0
                    ? 'text-red-600 bg-red-50/40'
                    : 'text-blue-600 bg-blue-50/40'
                }`}>
                  {variance > 0 ? `+${variance}` : variance}
                </td>

                <!-- Aksi Opname -->
                <td class="py-3 px-4 text-center font-sans">
                  <button
                    type="button"
                    onclick={() => onOpenOpname(mat)}
                    class="px-2.5 py-1 text-[11px] font-semibold bg-zinc-900 hover:bg-black text-white rounded-md cursor-pointer transition-all active:scale-95 shadow-2xs"
                  >
                    Rekonsiliasi
                  </button>
                </td>
              </tr>
            {/each}
          {/if}
        </tbody>
      </table>
    </div>

    <!-- Summary Footer -->
    <div class="bg-zinc-50 border-t border-zinc-200 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs font-mono text-zinc-600">
      <div class="flex items-center gap-5">
        <span class="flex items-center gap-1.5">
          <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600" />
          <span>Formula Rekonsiliasi: <code>Stok Awal + Masuk - Terpakai = Ekspektasi Sistem</code></span>
        </span>
      </div>

      <div class="flex items-center gap-4">
        <span>Total Komponen: <strong class="text-zinc-900">{filteredMaterials.length}</strong></span>
      </div>
    </div>
  </div>
</div>
