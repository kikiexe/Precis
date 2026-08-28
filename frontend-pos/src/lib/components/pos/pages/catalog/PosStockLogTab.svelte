<script lang="ts">
  import { Search, History, Filter } from 'lucide-svelte';
  import type { StockAdjustmentLog, StockAdjustmentReason } from '../../../../types/pos';

  interface Props {
    logs: StockAdjustmentLog[];
  }

  let { logs = [] }: Props = $props();

  let searchQuery = $state('');
  let reasonFilter = $state<string>('ALL');

  const reasonLabels: Record<StockAdjustmentReason, { label: string; color: string }> = {
    STOCK_TAKE: { label: 'Opname Rutin', color: 'bg-zinc-100 text-zinc-800' },
    RESTOCK: { label: 'Penerimaan Stok', color: 'bg-emerald-100 text-emerald-800' },
    WASTE: { label: 'Waste Racikan', color: 'bg-amber-100 text-amber-900' },
    EXPIRED: { label: 'Kadaluarsa / Basi', color: 'bg-red-100 text-red-800' },
    DAMAGED: { label: 'Kemasan Rusak', color: 'bg-rose-100 text-rose-800' },
    OTHER: { label: 'Lainnya', color: 'bg-purple-100 text-purple-800' },
  };

  let filteredLogs = $derived(
    logs.filter((log) => {
      const matchSearch =
        searchQuery.trim() === '' ||
        log.raw_material_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        log.adjusted_by.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (log.notes && log.notes.toLowerCase().includes(searchQuery.toLowerCase()));

      const matchReason = reasonFilter === 'ALL' || log.reason === reasonFilter;

      return matchSearch && matchReason;
    })
  );
</script>

<div class="space-y-3 font-sans select-none">
  <!-- Excel Toolbar: Search & Reason Filter -->
  <div
    class="flex flex-col items-stretch justify-between gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-2xs sm:flex-row sm:items-center"
  >
    <div class="flex flex-1 items-center gap-2">
      <div class="relative max-w-md flex-1">
        <Search class="absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-zinc-400" />
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Cari log audit berdasarkan nama bahan, kasir, catatan..."
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

      <!-- Reason Filter -->
      <div class="flex items-center gap-1.5">
        <Filter class="h-3.5 w-3.5 text-zinc-400" />
        <select
          bind:value={reasonFilter}
          class="h-9 cursor-pointer rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-xs font-medium text-zinc-700 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
        >
          <option value="ALL">Semua Alasan Penyesuaian ({logs.length})</option>
          <option value="STOCK_TAKE">Opname Rutin</option>
          <option value="RESTOCK">Penerimaan Stok (+)</option>
          <option value="WASTE">Waste Racikan Bar (-)</option>
          <option value="EXPIRED">Kadaluarsa / Basi (-)</option>
          <option value="DAMAGED">Kemasan Rusak (-)</option>
          <option value="OTHER">Lainnya</option>
        </select>
      </div>
    </div>

    <div class="self-center font-mono text-xs text-zinc-500">
      Log Terdaftar: <strong class="text-zinc-900">{filteredLogs.length}</strong> Audit
    </div>
  </div>

  <!-- Excel Spreadsheet: Stock Adjustment Audit Trail -->
  <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xs">
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-left text-xs">
        <thead
          class="border-b border-zinc-200 bg-zinc-100/80 font-mono text-[11px] font-bold tracking-wider text-zinc-600 uppercase"
        >
          <tr class="divide-x divide-zinc-200/80">
            <th class="w-12 px-3 py-3 text-center">No.</th>
            <th class="w-40 px-4 py-3 text-center">Waktu &amp; Tanggal</th>
            <th class="px-4 py-3">Nama Bahan Baku</th>
            <th class="w-36 px-4 py-3 text-center">Alasan Mutasi</th>
            <th class="w-28 px-4 py-3 text-right">Stok Awal</th>
            <th class="w-28 px-4 py-3 text-right font-bold">Stok Baru</th>
            <th class="w-28 px-4 py-3 text-right">Selisih (+/-)</th>
            <th class="w-36 px-4 py-3">Petugas / Kasir</th>
            <th class="px-4 py-3">Catatan Audit</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200/70 font-mono">
          {#if filteredLogs.length === 0}
            <tr>
              <td colspan="9" class="py-16 text-center font-sans text-zinc-400">
                <History class="mx-auto mb-2 h-8 w-8 text-zinc-400 opacity-30" />
                <p class="text-sm font-semibold text-zinc-800">
                  Belum ada riwayat penyesuaian stok
                </p>
                <p class="mt-0.5 text-xs text-zinc-500">
                  Semua audit opname, barang masuk, dan waste akan dicatat otomatis di sini.
                </p>
              </td>
            </tr>
          {:else}
            {#each filteredLogs as log, idx (log.id)}
              {@const reasonCfg = reasonLabels[log.reason] || {
                label: log.reason,
                color: 'bg-zinc-100 text-zinc-800',
              }}
              <tr
                class={`divide-x divide-zinc-200/60 transition-colors hover:bg-zinc-50/80 ${
                  idx % 2 === 1 ? 'bg-zinc-50/30' : 'bg-white'
                }`}
              >
                <!-- No -->
                <td class="px-3 py-3 text-center text-[11px] text-zinc-400">
                  {idx + 1}
                </td>

                <!-- Timestamp -->
                <td class="px-4 py-3 text-center text-[11px] text-zinc-500">
                  {log.created_at}
                </td>

                <!-- Nama Bahan Baku -->
                <td class="px-4 py-3 font-sans font-semibold text-zinc-900">
                  {log.raw_material_name}
                </td>

                <!-- Alasan -->
                <td class="px-4 py-3 text-center font-sans">
                  <span
                    class={`inline-block rounded-full px-2.5 py-0.5 text-[10px] font-bold ${reasonCfg.color}`}
                  >
                    {reasonCfg.label}
                  </span>
                </td>

                <!-- Stok Awal -->
                <td class="px-4 py-3 text-right text-zinc-600">
                  {log.previous_stock}
                </td>

                <!-- Stok Baru -->
                <td class="px-4 py-3 text-right text-xs font-bold text-zinc-900">
                  {log.new_stock}
                </td>

                <!-- Selisih -->
                <td
                  class={`px-4 py-3 text-right font-bold ${
                    log.variance === 0
                      ? 'text-zinc-500'
                      : log.variance > 0
                        ? 'bg-emerald-50/40 text-emerald-600'
                        : 'bg-red-50/40 text-red-600'
                  }`}
                >
                  {log.variance > 0 ? `+${log.variance}` : log.variance}
                </td>

                <!-- Petugas -->
                <td class="px-4 py-3 font-sans font-medium text-zinc-800">
                  {log.adjusted_by}
                </td>

                <!-- Catatan -->
                <td class="px-4 py-3 font-sans text-xs text-zinc-600">
                  {log.notes || '-'}
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
      <div>
        Audit trail tidak dapat dihapus untuk menjamin akurasi mutasi barang &amp; pencegahan
        kerugian kasir
      </div>
      <div>
        Total Log: <strong class="text-zinc-900">{filteredLogs.length}</strong>
      </div>
    </div>
  </div>
</div>
