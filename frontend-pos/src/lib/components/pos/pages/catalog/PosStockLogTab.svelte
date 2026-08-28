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
  <div class="bg-white border border-zinc-200 rounded-xl p-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shadow-2xs">
    <div class="flex items-center gap-2 flex-1">
      <div class="relative flex-1 max-w-md">
        <Search class="w-4 h-4 text-zinc-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Cari log audit berdasarkan nama bahan, kasir, catatan..."
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

      <!-- Reason Filter -->
      <div class="flex items-center gap-1.5">
        <Filter class="w-3.5 h-3.5 text-zinc-400" />
        <select
          bind:value={reasonFilter}
          class="h-9 px-3 bg-zinc-50 border border-zinc-200 rounded-lg text-xs font-medium text-zinc-700 cursor-pointer focus:bg-white focus:border-zinc-900 focus:outline-hidden"
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

    <div class="text-xs font-mono text-zinc-500 self-center">
      Log Terdaftar: <strong class="text-zinc-900">{filteredLogs.length}</strong> Audit
    </div>
  </div>

  <!-- Excel Spreadsheet: Stock Adjustment Audit Trail -->
  <div class="bg-white border border-zinc-200 rounded-xl overflow-hidden shadow-2xs">
    <div class="overflow-x-auto">
      <table class="w-full text-xs text-left border-collapse">
        <thead class="bg-zinc-100/80 border-b border-zinc-200 font-mono text-[11px] font-bold text-zinc-600 uppercase tracking-wider">
          <tr class="divide-x divide-zinc-200/80">
            <th class="py-3 px-3 w-12 text-center">No.</th>
            <th class="py-3 px-4 w-40 text-center">Waktu &amp; Tanggal</th>
            <th class="py-3 px-4">Nama Bahan Baku</th>
            <th class="py-3 px-4 w-36 text-center">Alasan Mutasi</th>
            <th class="py-3 px-4 w-28 text-right">Stok Awal</th>
            <th class="py-3 px-4 w-28 text-right font-bold">Stok Baru</th>
            <th class="py-3 px-4 w-28 text-right">Selisih (+/-)</th>
            <th class="py-3 px-4 w-36">Petugas / Kasir</th>
            <th class="py-3 px-4">Catatan Audit</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200/70 font-mono">
          {#if filteredLogs.length === 0}
            <tr>
              <td colspan="9" class="py-16 text-center text-zinc-400 font-sans">
                <History class="w-8 h-8 mx-auto opacity-30 text-zinc-400 mb-2" />
                <p class="text-sm font-semibold text-zinc-800">Belum ada riwayat penyesuaian stok</p>
                <p class="text-xs text-zinc-500 mt-0.5">Semua audit opname, barang masuk, dan waste akan dicatat otomatis di sini.</p>
              </td>
            </tr>
          {:else}
            {#each filteredLogs as log, idx (log.id)}
              {@const reasonCfg = reasonLabels[log.reason] || { label: log.reason, color: 'bg-zinc-100 text-zinc-800' }}
              <tr class={`divide-x divide-zinc-200/60 transition-colors hover:bg-zinc-50/80 ${
                idx % 2 === 1 ? 'bg-zinc-50/30' : 'bg-white'
              }`}>
                <!-- No -->
                <td class="py-3 px-3 text-center text-zinc-400 text-[11px]">
                  {idx + 1}
                </td>

                <!-- Timestamp -->
                <td class="py-3 px-4 text-center text-zinc-500 text-[11px]">
                  {log.created_at}
                </td>

                <!-- Nama Bahan Baku -->
                <td class="py-3 px-4 font-sans font-semibold text-zinc-900">
                  {log.raw_material_name}
                </td>

                <!-- Alasan -->
                <td class="py-3 px-4 text-center font-sans">
                  <span class={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold ${reasonCfg.color}`}>
                    {reasonCfg.label}
                  </span>
                </td>

                <!-- Stok Awal -->
                <td class="py-3 px-4 text-right text-zinc-600">
                  {log.previous_stock}
                </td>

                <!-- Stok Baru -->
                <td class="py-3 px-4 text-right font-bold text-zinc-900 text-xs">
                  {log.new_stock}
                </td>

                <!-- Selisih -->
                <td class={`py-3 px-4 text-right font-bold ${
                  log.variance === 0
                    ? 'text-zinc-500'
                    : log.variance > 0
                    ? 'text-emerald-600 bg-emerald-50/40'
                    : 'text-red-600 bg-red-50/40'
                }`}>
                  {log.variance > 0 ? `+${log.variance}` : log.variance}
                </td>

                <!-- Petugas -->
                <td class="py-3 px-4 font-sans text-zinc-800 font-medium">
                  {log.adjusted_by}
                </td>

                <!-- Catatan -->
                <td class="py-3 px-4 font-sans text-zinc-600 text-xs">
                  {log.notes || '-'}
                </td>
              </tr>
            {/each}
          {/if}
        </tbody>
      </table>
    </div>

    <!-- Summary Footer -->
    <div class="bg-zinc-50 border-t border-zinc-200 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs font-mono text-zinc-600">
      <div>
        Audit trail tidak dapat dihapus untuk menjamin akurasi mutasi barang &amp; pencegahan kerugian kasir
      </div>
      <div>
        Total Log: <strong class="text-zinc-900">{filteredLogs.length}</strong>
      </div>
    </div>
  </div>
</div>
