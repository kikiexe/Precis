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

  let {
    activeSession,
    activeCashier,
    closedSessions = $bindable([]),
    onGoToShift,
  }: Props = $props();

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

<div class="flex h-full flex-1 flex-col overflow-hidden bg-[#f4f6f9] font-sans select-none">
  <!-- Top Bar -->
  <div
    class="flex h-14 shrink-0 items-center justify-between border-b border-zinc-200 bg-white px-6 shadow-2xs"
  >
    <div class="flex items-center gap-3">
      <h1 class="text-base font-bold tracking-tight text-zinc-900">Settlement Penjualan</h1>
      <span class="font-mono text-xs text-zinc-400">|</span>
      <span class="text-xs font-medium text-zinc-500">Setoran &amp; Rekonsiliasi Shift Selesai</span
      >
    </div>

    <div class="font-mono text-xs text-zinc-500">
      Shift Ditutup: <strong class="text-zinc-900">{closedSessions.length}</strong> Shift
    </div>
  </div>

  <!-- Content Body -->
  <div class="flex-1 space-y-4 overflow-y-auto p-4 sm:p-6 lg:p-8">
    <div class="mx-auto w-full max-w-7xl space-y-4">
      <!-- Alert if Active Shift is still OPEN -->
      {#if activeSession && activeSession.status === 'OPEN'}
        <div
          class="flex flex-col items-start justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-2xs sm:flex-row sm:items-center"
        >
          <div class="flex items-start gap-3">
            <div
              class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-amber-200 bg-amber-100 text-amber-800"
            >
              <AlertTriangle class="h-4 w-4" />
            </div>
            <div>
              <div class="text-xs font-bold text-amber-900">
                Shift Saat Ini Masih Aktif Berjalan
              </div>
              <p class="mt-0.5 text-[11px] text-amber-800">
                Kasir <strong>{activeSession.cashier_name || activeCashier?.name}</strong> sedang aktif
                bertugas. Anda hanya dapat melakukan settlement pada shift yang sudah ditutup dan dihitung
                kas fisiknya.
              </p>
            </div>
          </div>
          {#if onGoToShift}
            <button
              type="button"
              onclick={onGoToShift}
              class="shrink-0 cursor-pointer rounded-xl bg-amber-800 px-3.5 py-2 text-xs font-semibold text-white shadow-2xs transition-all hover:bg-amber-900 active:scale-95"
            >
              Buka Manajemen Shift
            </button>
          {/if}
        </div>
      {/if}

      <!-- Toolbar: Search & Status Filter -->
      <div
        class="flex flex-col items-stretch justify-between gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-2xs sm:flex-row sm:items-center"
      >
        <div class="flex flex-1 items-center gap-2">
          <div class="relative max-w-md flex-1">
            <Search class="absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-zinc-400" />
            <input
              type="text"
              bind:value={searchQuery}
              placeholder="Cari kasir, catatan shift..."
              class="w-full rounded-lg border border-zinc-200 bg-zinc-50 py-2 pr-4 pl-9 text-xs text-zinc-900 placeholder-zinc-400 transition-all focus:border-zinc-900 focus:bg-white focus:outline-hidden"
            />
          </div>

          <!-- Status Filter -->
          <div class="flex items-center gap-1 rounded-lg bg-zinc-100 p-1">
            <button
              type="button"
              onclick={() => (filterStatus = 'ALL')}
              class={`cursor-pointer rounded-md px-3 py-1 text-xs font-semibold transition-all ${
                filterStatus === 'ALL'
                  ? 'bg-white text-zinc-900 shadow-2xs'
                  : 'text-zinc-600 hover:text-zinc-900'
              }`}
            >
              Semua ({closedSessions.length})
            </button>
            <button
              type="button"
              onclick={() => (filterStatus = 'UNSETTLED')}
              class={`cursor-pointer rounded-md px-3 py-1 text-xs font-semibold transition-all ${
                filterStatus === 'UNSETTLED'
                  ? 'bg-white text-amber-900 shadow-2xs'
                  : 'text-zinc-600 hover:text-zinc-900'
              }`}
            >
              Belum Settle ({closedSessions.filter((s) => !s.is_settled).length})
            </button>
            <button
              type="button"
              onclick={() => (filterStatus = 'SETTLED')}
              class={`cursor-pointer rounded-md px-3 py-1 text-xs font-semibold transition-all ${
                filterStatus === 'SETTLED'
                  ? 'bg-white text-emerald-900 shadow-2xs'
                  : 'text-zinc-600 hover:text-zinc-900'
              }`}
            >
              Sudah Settle ({closedSessions.filter((s) => s.is_settled).length})
            </button>
          </div>
        </div>
      </div>

      <!-- Closed Shifts Spreadsheet Table -->
      {#if filteredClosedSessions.length === 0}
        <div
          class="rounded-2xl border border-zinc-200 bg-white p-12 text-center text-zinc-400 shadow-2xs"
        >
          <History class="mx-auto mb-2 h-10 w-10 text-zinc-400 opacity-30" />
          <p class="text-sm font-semibold text-zinc-800">Tidak ada data shift selesai</p>
          <p class="mt-0.5 text-xs text-zinc-500">
            Shift yang ditutup akan otomatis masuk ke daftar ini untuk diselesaikan dan disetor.
          </p>
        </div>
      {:else}
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xs">
          <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
              <thead
                class="border-b border-zinc-200 bg-zinc-100/90 font-mono text-[11px] font-bold tracking-wider text-zinc-600 uppercase"
              >
                <tr class="divide-x divide-zinc-200/80">
                  <th class="w-12 p-3 text-center">No.</th>
                  <th class="w-40 p-3">Waktu Tutup</th>
                  <th class="w-36 p-3">Kasir Bertugas</th>
                  <th class="w-28 p-3 text-right">Modal Awal</th>
                  <th class="w-28 bg-emerald-50/40 p-3 text-right text-emerald-900">Tunai Masuk</th>
                  <th class="w-36 p-3 text-right">Non-Tunai (QRIS/EDC)</th>
                  <th class="w-32 p-3 text-right font-bold text-zinc-900">Kas Fisik Aktual</th>
                  <th class="w-32 p-3 text-center">Status Settle</th>
                  <th class="w-36 p-3 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-zinc-200/70 font-mono">
                {#each filteredClosedSessions as s, idx (s.id)}
                  {@const isSettled = s.is_settled ?? false}
                  <tr
                    class={`divide-x divide-zinc-200/60 transition-colors hover:bg-zinc-50/80 ${
                      idx % 2 === 1 ? 'bg-zinc-50/30' : 'bg-white'
                    }`}
                  >
                    <!-- No -->
                    <td class="p-3 text-center text-[11px] text-zinc-400">
                      {idx + 1}
                    </td>

                    <!-- Waktu Tutup -->
                    <td class="p-3">
                      <div class="font-medium text-zinc-900">
                        {new Date(s.closed_at || s.opened_at).toLocaleDateString('id-ID')}
                      </div>
                      <div class="text-[10px] text-zinc-500">
                        {new Date(s.closed_at || s.opened_at).toLocaleTimeString('id-ID')} WIB
                      </div>
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
                    <td class="bg-emerald-50/20 p-3 text-right font-bold text-emerald-700">
                      +{formatCurrency(s.total_cash_sales)}
                    </td>

                    <!-- Non Tunai -->
                    <td class="p-3 text-right font-bold text-zinc-900">
                      +{formatCurrency(s.total_qris_sales + s.total_transfer_sales)}
                    </td>

                    <!-- Kas Fisik -->
                    <td class="p-3 text-right text-xs font-bold text-zinc-900">
                      {formatCurrency(s.closing_cash_actual || 0)}
                    </td>

                    <!-- Status Settlement -->
                    <td class="p-3 text-center font-sans">
                      <span
                        class={`inline-block rounded-full px-2.5 py-0.5 text-[10px] font-bold ${
                          isSettled
                            ? 'border border-emerald-200 bg-emerald-100 text-emerald-800'
                            : 'border border-amber-200 bg-amber-100 text-amber-900'
                        }`}
                      >
                        {isSettled ? 'Sudah Disettle' : 'Belum Disettle'}
                      </span>
                      {#if isSettled && s.settled_at}
                        <div class="mt-0.5 font-mono text-[9px] text-zinc-400">
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
                          class="mx-auto flex cursor-pointer items-center gap-1 rounded-lg bg-zinc-900 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs transition-all hover:bg-black active:scale-95"
                        >
                          <DollarSign class="h-3.5 w-3.5" />
                          <span>Settle Penjualan</span>
                        </button>
                      {:else}
                        <span class="text-xs font-medium text-zinc-400">Terselesaikan</span>
                      {/if}
                    </td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>

          <!-- Summary Footer -->
          <div
            class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50 px-4 py-2.5 font-mono text-xs text-zinc-600"
          >
            <div>Settlement mengunci riwayat kasir dan mencatat pembukuan akhir penjualan</div>
            <div>
              Total Sesi Terdaftar: <strong class="text-zinc-900"
                >{filteredClosedSessions.length}</strong
              > Shift
            </div>
          </div>
        </div>
      {/if}
    </div>
  </div>
</div>

<!-- Modal Settlement Penjualan Shift -->
{#if selectedSessionToSettle}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 font-sans backdrop-blur-xs select-none"
  >
    <div
      class="animate-in zoom-in-95 w-full max-w-lg space-y-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl duration-150"
    >
      <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div
            class="flex h-9 w-9 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-100 text-zinc-900"
          >
            <DollarSign class="h-4 w-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-zinc-900">Settle Penjualan Shift Selesai</h3>
            <p class="font-mono text-[11px] text-zinc-500">
              Kasir: {selectedSessionToSettle.cashier_name}
            </p>
          </div>
        </div>
        <button
          type="button"
          onclick={() => (selectedSessionToSettle = null)}
          class="cursor-pointer p-1 text-zinc-400 hover:text-zinc-700"
        >
          <X class="h-4 w-4" />
        </button>
      </div>

      <!-- Financial Breakdown -->
      <div class="space-y-2.5 text-xs">
        <div class="space-y-2 rounded-xl border border-zinc-200 bg-zinc-50 p-3.5 font-mono">
          <div class="flex justify-between text-zinc-600">
            <span>Waktu Tutup Shift:</span>
            <span class="font-bold text-zinc-900"
              >{new Date(selectedSessionToSettle.closed_at || '').toLocaleString('id-ID')}</span
            >
          </div>
          <div class="flex justify-between text-zinc-600">
            <span>Total Penjualan Tunai:</span>
            <span class="font-bold text-emerald-700"
              >{formatCurrency(selectedSessionToSettle.total_cash_sales)}</span
            >
          </div>
          <div class="flex justify-between text-zinc-600">
            <span>Total Non-Tunai (QRIS/EDC):</span>
            <span class="font-bold text-zinc-900"
              >{formatCurrency(
                selectedSessionToSettle.total_qris_sales +
                  selectedSessionToSettle.total_transfer_sales
              )}</span
            >
          </div>
          <div class="flex justify-between text-zinc-600">
            <span>Uang Kas Fisik Laci:</span>
            <span class="font-bold text-zinc-900"
              >{formatCurrency(selectedSessionToSettle.closing_cash_actual || 0)}</span
            >
          </div>
          <div
            class="flex justify-between border-t border-zinc-200 pt-2 font-sans text-base font-bold text-zinc-900"
          >
            <span>Total Omzet Shift:</span>
            <span class="font-mono"
              >{formatCurrency(
                selectedSessionToSettle.total_cash_sales +
                  selectedSessionToSettle.total_qris_sales +
                  selectedSessionToSettle.total_transfer_sales
              )}</span
            >
          </div>
        </div>

        <div class="space-y-1">
          <label for="settlement-notes" class="block font-semibold text-zinc-900">
            Catatan / Nomor Referensi Setoran (Opsional)
          </label>
          <input
            id="settlement-notes"
            type="text"
            bind:value={settlementNotesInput}
            placeholder="Contoh: Disetor tunai ke Owner / Rekening BCA #1092"
            class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 text-xs text-zinc-900 placeholder-zinc-400 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
          />
        </div>
      </div>

      <div class="flex gap-2.5 pt-2">
        <button
          type="button"
          onclick={() => (selectedSessionToSettle = null)}
          class="flex-1 cursor-pointer rounded-xl border border-zinc-200 py-2.5 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-100"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleConfirmSettlement}
          class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-zinc-900 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black active:scale-[0.99]"
        >
          <span>Konfirmasi &amp; Selesaikan Settlement</span>
          <ArrowRight class="h-3.5 w-3.5" />
        </button>
      </div>
    </div>
  </div>
{/if}
