<script lang="ts">
  import { DollarSign, History, AlertTriangle, ArrowRight, X, Search } from 'lucide-svelte';
  import type { PosSession, CashierUser } from '../../../types/pos';
  import { formatCurrency } from '../../../services/printer-service';

  interface Props {
    activeSession: PosSession | null;
    activeCashier: CashierUser | null;
    closedSessions?: PosSession[];
    onGoToShift?: () => void;
  }

  let { activeSession, activeCashier, closedSessions = $bindable([]), onGoToShift }: Props = $props();

  let searchQuery = $state('');
  let filterStatus = $state<'ALL' | 'UNSETTLED' | 'SETTLED'>('ALL');
  let selectedSessionToSettle = $state<PosSession | null>(null);
  let settlementNotesInput = $state('');

  let filteredClosedSessions = $derived(
    closedSessions.filter((s) => {
      const matchSearch =
        searchQuery.trim() === '' ||
        (s.cashier_name && s.cashier_name.toLowerCase().includes(searchQuery.toLowerCase())) ||
        (s.notes && s.notes.toLowerCase().includes(searchQuery.toLowerCase()));

      const isSettled = s.is_settled ?? false;
      const matchStatus =
        filterStatus === 'ALL' ||
        (filterStatus === 'UNSETTLED' && !isSettled) ||
        (filterStatus === 'SETTLED' && isSettled);

      return matchSearch && matchStatus;
    })
  );

  function handleOpenSettleModal(session: PosSession) {
    selectedSessionToSettle = session;
    settlementNotesInput = '';
  }

  function handleConfirmSettlement() {
    if (!selectedSessionToSettle) return;
    const nowStr = new Date().toISOString().replace('T', ' ').substring(0, 16);

    closedSessions = closedSessions.map((s) => {
      if (s.id === selectedSessionToSettle?.id) {
        return {
          ...s,
          is_settled: true,
          settled_at: nowStr,
          settled_by: activeCashier?.name || s.cashier_name || 'Owner/Manager',
          settlement_notes: settlementNotesInput.trim() || undefined,
        };
      }
      return s;
    });

    selectedSessionToSettle = null;
  }
</script>

<div class="flex-1 flex flex-col h-full bg-[#f4f6f9] overflow-hidden font-sans select-none">
  <!-- Top Bar -->
  <div class="h-14 bg-white border-b border-zinc-200 px-6 flex items-center justify-between shrink-0 shadow-2xs">
    <div class="flex items-center gap-3">
      <h1 class="text-base font-bold text-zinc-900 tracking-tight">Settlement Penjualan</h1>
      <span class="text-xs font-mono text-zinc-400">|</span>
      <span class="text-xs text-zinc-500 font-medium">Setoran &amp; Rekonsiliasi Shift Selesai</span>
    </div>

    <div class="text-xs font-mono text-zinc-500">
      Shift Ditutup: <strong class="text-zinc-900">{closedSessions.length}</strong> Shift
    </div>
  </div>

  <!-- Content Body -->
  <div class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto space-y-4">
    <div class="w-full max-w-7xl mx-auto space-y-4">
      
      <!-- Alert if Active Shift is still OPEN -->
      {#if activeSession && activeSession.status === 'OPEN'}
        <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs">
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-amber-100 border border-amber-200 text-amber-800 flex items-center justify-center shrink-0 mt-0.5">
              <AlertTriangle class="w-4 h-4" />
            </div>
            <div>
              <div class="text-xs font-bold text-amber-900">Shift Saat Ini Masih Aktif Berjalan</div>
              <p class="text-[11px] text-amber-800 mt-0.5">
                Kasir <strong>{activeSession.cashier_name || activeCashier?.name}</strong> sedang aktif bertugas. Anda hanya dapat melakukan settlement pada shift yang sudah ditutup dan dihitung kas fisiknya.
              </p>
            </div>
          </div>
          {#if onGoToShift}
            <button
              type="button"
              onclick={onGoToShift}
              class="px-3.5 py-2 bg-amber-800 hover:bg-amber-900 text-white rounded-xl text-xs font-semibold shrink-0 cursor-pointer transition-all active:scale-95 shadow-2xs"
            >
              Buka Manajemen Shift
            </button>
          {/if}
        </div>
      {/if}

      <!-- Toolbar: Search & Status Filter -->
      <div class="bg-white border border-zinc-200 rounded-xl p-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shadow-2xs">
        <div class="flex items-center gap-2 flex-1">
          <div class="relative flex-1 max-w-md">
            <Search class="w-4 h-4 text-zinc-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              bind:value={searchQuery}
              placeholder="Cari kasir, catatan shift..."
              class="w-full pl-9 pr-4 py-2 bg-zinc-50 border border-zinc-200 rounded-lg text-xs text-zinc-900 placeholder-zinc-400 focus:bg-white focus:border-zinc-900 focus:outline-hidden transition-all"
            />
          </div>

          <!-- Status Filter -->
          <div class="flex items-center gap-1 bg-zinc-100 p-1 rounded-lg">
            <button
              type="button"
              onclick={() => (filterStatus = 'ALL')}
              class={`px-3 py-1 rounded-md text-xs font-semibold transition-all cursor-pointer ${
                filterStatus === 'ALL' ? 'bg-white text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900'
              }`}
            >
              Semua ({closedSessions.length})
            </button>
            <button
              type="button"
              onclick={() => (filterStatus = 'UNSETTLED')}
              class={`px-3 py-1 rounded-md text-xs font-semibold transition-all cursor-pointer ${
                filterStatus === 'UNSETTLED' ? 'bg-white text-amber-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900'
              }`}
            >
              Belum Settle ({closedSessions.filter((s) => !s.is_settled).length})
            </button>
            <button
              type="button"
              onclick={() => (filterStatus = 'SETTLED')}
              class={`px-3 py-1 rounded-md text-xs font-semibold transition-all cursor-pointer ${
                filterStatus === 'SETTLED' ? 'bg-white text-emerald-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900'
              }`}
            >
              Sudah Settle ({closedSessions.filter((s) => s.is_settled).length})
            </button>
          </div>
        </div>
      </div>

      <!-- Closed Shifts Spreadsheet Table -->
      {#if filteredClosedSessions.length === 0}
        <div class="bg-white rounded-2xl border border-zinc-200 p-12 text-center text-zinc-400 shadow-2xs">
          <History class="w-10 h-10 mx-auto opacity-30 text-zinc-400 mb-2" />
          <p class="text-sm font-semibold text-zinc-800">Tidak ada data shift selesai</p>
          <p class="text-xs text-zinc-500 mt-0.5">Shift yang ditutup akan otomatis masuk ke daftar ini untuk diselesaikan dan disetor.</p>
        </div>
      {:else}
        <div class="bg-white border border-zinc-200 rounded-xl overflow-hidden shadow-2xs">
          <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
              <thead class="bg-zinc-100/90 border-b border-zinc-200 font-mono text-[11px] font-bold text-zinc-600 uppercase tracking-wider">
                <tr class="divide-x divide-zinc-200/80">
                  <th class="p-3 w-12 text-center">No.</th>
                  <th class="p-3 w-40">Waktu Tutup</th>
                  <th class="p-3 w-36">Kasir Bertugas</th>
                  <th class="p-3 w-28 text-right">Modal Awal</th>
                  <th class="p-3 w-28 text-right bg-emerald-50/40 text-emerald-900">Tunai Masuk</th>
                  <th class="p-3 w-36 text-right">Non-Tunai (QRIS/EDC)</th>
                  <th class="p-3 w-32 text-right font-bold text-zinc-900">Kas Fisik Aktual</th>
                  <th class="p-3 w-32 text-center">Status Settle</th>
                  <th class="p-3 w-36 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-zinc-200/70 font-mono">
                {#each filteredClosedSessions as s, idx (s.id)}
                  {@const isSettled = s.is_settled ?? false}
                  <tr class={`divide-x divide-zinc-200/60 transition-colors hover:bg-zinc-50/80 ${
                    idx % 2 === 1 ? 'bg-zinc-50/30' : 'bg-white'
                  }`}>
                    <!-- No -->
                    <td class="p-3 text-center text-zinc-400 text-[11px]">
                      {idx + 1}
                    </td>

                    <!-- Waktu Tutup -->
                    <td class="p-3">
                      <div class="font-medium text-zinc-900">{new Date(s.closed_at || s.opened_at).toLocaleDateString('id-ID')}</div>
                      <div class="text-[10px] text-zinc-500">{new Date(s.closed_at || s.opened_at).toLocaleTimeString('id-ID')} WIB</div>
                    </td>

                    <!-- Kasir -->
                    <td class="p-3 font-sans font-medium text-zinc-900">
                      {s.cashier_name}
                    </td>

                    <!-- Modal Awal -->
                    <td class="p-3 text-right text-zinc-600">
                      {formatCurrency(s.opening_cash)}
                    </td>

                    <!-- Tunai -->
                    <td class="p-3 text-right font-bold text-emerald-700 bg-emerald-50/20">
                      +{formatCurrency(s.total_cash_sales)}
                    </td>

                    <!-- Non Tunai -->
                    <td class="p-3 text-right font-bold text-zinc-900">
                      +{formatCurrency(s.total_qris_sales + s.total_transfer_sales)}
                    </td>

                    <!-- Kas Fisik -->
                    <td class="p-3 text-right font-bold text-zinc-900 text-xs">
                      {formatCurrency(s.closing_cash_actual || 0)}
                    </td>

                    <!-- Status Settlement -->
                    <td class="p-3 text-center font-sans">
                      <span class={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold ${
                        isSettled
                          ? 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                          : 'bg-amber-100 text-amber-900 border border-amber-200'
                      }`}>
                        {isSettled ? 'Sudah Disettle' : 'Belum Disettle'}
                      </span>
                      {#if isSettled && s.settled_at}
                        <div class="text-[9px] font-mono text-zinc-400 mt-0.5">
                          {s.settled_at}
                        </div>
                      {/if}
                    </td>

                    <!-- Aksi -->
                    <td class="p-3 text-center font-sans">
                      {#if !isSettled}
                        <button
                          type="button"
                          onclick={() => handleOpenSettleModal(s)}
                          class="px-3 py-1.5 bg-zinc-900 hover:bg-black text-white rounded-lg text-xs font-semibold transition-all cursor-pointer shadow-2xs active:scale-95 flex items-center gap-1 mx-auto"
                        >
                          <DollarSign class="w-3.5 h-3.5" />
                          <span>Settle Penjualan</span>
                        </button>
                      {:else}
                        <span class="text-xs text-zinc-400 font-medium">Terselesaikan</span>
                      {/if}
                    </td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>

          <!-- Summary Footer -->
          <div class="bg-zinc-50 border-t border-zinc-200 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs font-mono text-zinc-600">
            <div>
              Settlement mengunci riwayat kasir dan mencatat pembukuan akhir penjualan
            </div>
            <div>
              Total Sesi Terdaftar: <strong class="text-zinc-900">{filteredClosedSessions.length}</strong> Shift
            </div>
          </div>
        </div>
      {/if}
    </div>
  </div>
</div>

<!-- Modal Settlement Penjualan Shift -->
{#if selectedSessionToSettle}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans select-none">
    <div class="bg-white border border-zinc-200 rounded-2xl w-full max-w-lg p-6 space-y-4 shadow-2xl animate-in zoom-in-95 duration-150">
      <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-zinc-100 text-zinc-900 flex items-center justify-center border border-zinc-200">
            <DollarSign class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-zinc-900">Settle Penjualan Shift Selesai</h3>
            <p class="text-[11px] text-zinc-500 font-mono">Kasir: {selectedSessionToSettle.cashier_name}</p>
          </div>
        </div>
        <button type="button" onclick={() => (selectedSessionToSettle = null)} class="text-zinc-400 hover:text-zinc-700 cursor-pointer p-1">
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Financial Breakdown -->
      <div class="space-y-2.5 text-xs">
        <div class="p-3.5 bg-zinc-50 border border-zinc-200 rounded-xl space-y-2 font-mono">
          <div class="flex justify-between text-zinc-600">
            <span>Waktu Tutup Shift:</span>
            <span class="font-bold text-zinc-900">{new Date(selectedSessionToSettle.closed_at || '').toLocaleString('id-ID')}</span>
          </div>
          <div class="flex justify-between text-zinc-600">
            <span>Total Penjualan Tunai:</span>
            <span class="font-bold text-emerald-700">{formatCurrency(selectedSessionToSettle.total_cash_sales)}</span>
          </div>
          <div class="flex justify-between text-zinc-600">
            <span>Total Non-Tunai (QRIS/EDC):</span>
            <span class="font-bold text-zinc-900">{formatCurrency(selectedSessionToSettle.total_qris_sales + selectedSessionToSettle.total_transfer_sales)}</span>
          </div>
          <div class="flex justify-between text-zinc-600">
            <span>Uang Kas Fisik Laci:</span>
            <span class="font-bold text-zinc-900">{formatCurrency(selectedSessionToSettle.closing_cash_actual || 0)}</span>
          </div>
          <div class="flex justify-between text-base font-bold text-zinc-900 pt-2 border-t border-zinc-200 font-sans">
            <span>Total Omzet Shift:</span>
            <span class="font-mono">{formatCurrency(selectedSessionToSettle.total_cash_sales + selectedSessionToSettle.total_qris_sales + selectedSessionToSettle.total_transfer_sales)}</span>
          </div>
        </div>

        <div class="space-y-1">
          <label for="settlement-notes" class="font-semibold text-zinc-900 block">
            Catatan / Nomor Referensi Setoran (Opsional)
          </label>
          <input
            id="settlement-notes"
            type="text"
            bind:value={settlementNotesInput}
            placeholder="Contoh: Disetor tunai ke Owner / Rekening BCA #1092"
            class="w-full px-3.5 py-2 bg-zinc-50 border border-zinc-200 rounded-xl text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden text-xs placeholder-zinc-400"
          />
        </div>
      </div>

      <div class="pt-2 flex gap-2.5">
        <button
          type="button"
          onclick={() => (selectedSessionToSettle = null)}
          class="flex-1 py-2.5 text-xs font-semibold border border-zinc-200 rounded-xl text-zinc-700 hover:bg-zinc-100 cursor-pointer transition-colors"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleConfirmSettlement}
          class="flex-1 py-2.5 text-xs font-semibold bg-zinc-900 hover:bg-black text-white rounded-xl flex items-center justify-center gap-1.5 cursor-pointer shadow-xs transition-all active:scale-[0.99]"
        >
          <span>Konfirmasi &amp; Selesaikan Settlement</span>
          <ArrowRight class="w-3.5 h-3.5" />
        </button>
      </div>
    </div>
  </div>
{/if}
