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
  <div
    class="flex flex-col items-stretch justify-between gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-2xs sm:flex-row sm:items-center"
  >
    <div class="flex flex-1 items-center gap-2">
      <div class="relative max-w-md flex-1">
        <Search class="absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-zinc-400" />
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Cari bahan baku pada lembar rekap harian..."
          class="w-full rounded-lg border border-zinc-200 bg-zinc-50 py-2 pr-4 pl-9 text-xs text-zinc-900 placeholder-zinc-400 transition-all focus:border-zinc-900 focus:bg-white focus:outline-hidden"
        />
        {#if searchQuery}
          <button
            type="button"
            onclick={() => (searchQuery = '')}
            class="absolute top-1/2 right-3 -translate-y-1/2 cursor-pointer font-mono text-xs text-zinc-400 hover:text-zinc-700"
          >
            ✕
          </button>
        {/if}
      </div>

      <!-- Date Filter -->
      <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-1.5">
        <Calendar class="h-3.5 w-3.5 text-zinc-500" />
        <input
          type="date"
          bind:value={selectedDate}
          class="cursor-pointer bg-transparent font-mono text-xs font-medium text-zinc-800 focus:outline-hidden"
        />
      </div>
    </div>

    <div class="self-center font-mono text-xs text-zinc-500">
      Status: <span class="font-bold text-zinc-900">Rekonsiliasi Realtime</span>
    </div>
  </div>

  <!-- Excel Spreadsheet: Daily Reconciliation Sheet -->
  <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xs">
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-left text-xs">
        <thead
          class="border-b border-zinc-200 bg-zinc-100/80 font-mono text-[11px] font-bold tracking-wider text-zinc-600 uppercase"
        >
          <tr class="divide-x divide-zinc-200/80">
            <th class="w-12 px-3 py-3 text-center">No.</th>
            <th class="px-4 py-3">Bahan Baku</th>
            <th class="w-20 px-3 py-3 text-center">Satuan</th>
            <th class="w-28 bg-zinc-50 px-4 py-3 text-right">Stok Awal (Kemarin)</th>
            <th class="w-28 bg-emerald-50/50 px-4 py-3 text-right text-emerald-800"
              >Masuk / Restock (+)</th
            >
            <th class="w-28 bg-red-50/50 px-4 py-3 text-right text-red-800">Terpakai / Waste (-)</th
            >
            <th class="w-28 bg-zinc-50 px-4 py-3 text-right">Ekspektasi Sistem</th>
            <th class="w-28 px-4 py-3 text-right font-bold text-zinc-900">Stok Fisik Aktual</th>
            <th class="w-24 px-4 py-3 text-right">Varian (+/-)</th>
            <th class="w-28 px-4 py-3 text-center">Aksi Opname</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200/70 font-mono">
          {#if filteredMaterials.length === 0}
            <tr>
              <td colspan="10" class="py-16 text-center font-sans text-zinc-400">
                <p class="text-sm font-semibold text-zinc-800">Tidak ada data rekap bahan baku</p>
                <p class="mt-0.5 text-xs text-zinc-500">
                  Ubah kata kunci pencarian atau tanggal audit.
                </p>
              </td>
            </tr>
          {:else}
            {#each filteredMaterials as mat, idx (mat.id)}
              {@const startStock = mat.stock_previous_day ?? mat.current_stock}
              {@const inStock = mat.stock_in_today ?? 0}
              {@const usedStock = mat.stock_used_today ?? 0}
              {@const expectedStock = startStock + inStock - usedStock}
              {@const variance = mat.current_stock - expectedStock}
              <tr
                class={`divide-x divide-zinc-200/60 transition-colors hover:bg-zinc-50/80 ${
                  idx % 2 === 1 ? 'bg-zinc-50/30' : 'bg-white'
                }`}
              >
                <!-- No -->
                <td class="px-3 py-3 text-center text-[11px] text-zinc-400">
                  {idx + 1}
                </td>

                <!-- Nama Bahan Baku -->
                <td class="px-4 py-3 font-sans font-semibold text-zinc-900">
                  <div class="flex items-center gap-2">
                    <span>{mat.name}</span>
                    <span
                      class="rounded border border-zinc-200/50 bg-zinc-100 px-1.5 py-0.5 text-[10px] font-normal text-zinc-600"
                    >
                      {mat.category_name || 'Bahan'}
                    </span>
                  </div>
                </td>

                <!-- Satuan -->
                <td class="px-3 py-3 text-center text-[11px] text-zinc-500">
                  {mat.unit}
                </td>

                <!-- Stok Awal -->
                <td class="bg-zinc-50/50 px-4 py-3 text-right text-zinc-700">
                  {startStock}
                </td>

                <!-- Masuk / Restock -->
                <td class="bg-emerald-50/30 px-4 py-3 text-right font-bold text-emerald-700">
                  +{inStock}
                </td>

                <!-- Terpakai / Waste -->
                <td class="bg-red-50/30 px-4 py-3 text-right font-bold text-red-700">
                  -{usedStock}
                </td>

                <!-- Ekspektasi Sistem -->
                <td class="bg-zinc-50/50 px-4 py-3 text-right font-bold text-zinc-800">
                  {expectedStock}
                </td>

                <!-- Stok Fisik Aktual -->
                <td class="px-4 py-3 text-right text-xs font-bold text-zinc-900">
                  {mat.current_stock}
                </td>

                <!-- Varian -->
                <td
                  class={`px-4 py-3 text-right font-bold ${
                    variance === 0
                      ? 'text-emerald-600'
                      : variance < 0
                        ? 'bg-red-50/40 text-red-600'
                        : 'bg-blue-50/40 text-blue-600'
                  }`}
                >
                  {variance > 0 ? `+${variance}` : variance}
                </td>

                <!-- Aksi Opname -->
                <td class="px-4 py-3 text-center font-sans">
                  <button
                    type="button"
                    onclick={() => onOpenOpname(mat)}
                    class="cursor-pointer rounded-md bg-zinc-900 px-2.5 py-1 text-[11px] font-semibold text-white shadow-2xs transition-all hover:bg-black active:scale-95"
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
    <div
      class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50 px-4 py-2.5 font-mono text-xs text-zinc-600"
    >
      <div class="flex items-center gap-5">
        <span class="flex items-center gap-1.5">
          <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600" />
          <span
            >Formula Rekonsiliasi: <code>Stok Awal + Masuk - Terpakai = Ekspektasi Sistem</code
            ></span
          >
        </span>
      </div>

      <div class="flex items-center gap-4">
        <span
          >Total Komponen: <strong class="text-zinc-900">{filteredMaterials.length}</strong></span
        >
      </div>
    </div>
  </div>
</div>
