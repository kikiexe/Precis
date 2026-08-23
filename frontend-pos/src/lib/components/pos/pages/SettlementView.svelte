<script lang="ts">
  import { Calculator, DollarSign, Clock, Receipt } from 'lucide-svelte';
  import type { PosSession, CashierUser } from '../../../types/pos';
  import { formatCurrency } from '../../../services/printer-service';

  interface Props {
    activeSession: PosSession | null;
    activeCashier: CashierUser | null;
    closedSessions?: PosSession[];
    onOpenSessionModal: () => void;
  }

  let { activeSession, closedSessions = [], onOpenSessionModal }: Props = $props();
</script>

<div class="flex-1 bg-[#f4f4f4] p-6 overflow-y-auto space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-xl font-bold text-[#161616] font-display">Settlement Shift &amp; Tutup Kasir</h2>
      <p class="text-xs text-[#525252] font-mono">Rekonsiliasi total transaksi kasir dan perhitungan selisih fisik laci</p>
    </div>

    <button
      type="button"
      onclick={onOpenSessionModal}
      class="bg-[#0f62fe] hover:bg-[#0050e6] text-white px-4 py-2.5 text-xs font-semibold flex items-center gap-2 cursor-pointer shadow-xs transition-colors"
    >
      <Calculator class="w-4 h-4" />
      <span>{activeSession && activeSession.status === 'OPEN' ? 'Tutup Shift & Rekonsiliasi' : 'Buka Shift Baru'}</span>
    </button>
  </div>

  <!-- Active Session Overview Card -->
  {#if activeSession && activeSession.status === 'OPEN'}
    <div class="bg-white border border-[#e0e0e0] p-5 shadow-xs space-y-4">
      <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3">
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 rounded-full bg-[#24a148] animate-ping"></div>
          <span class="font-bold text-sm text-[#161616]">Sesi Kasir Sedang Berjalan</span>
        </div>
        <span class="text-xs font-mono text-[#525252]">
          Dibuka: {new Date(activeSession.opened_at).toLocaleTimeString('id-ID')} WIB
        </span>
      </div>

      <!-- Financial Metrics Grid -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="p-3.5 bg-[#f4f4f4] border border-[#e0e0e0]">
          <div class="text-[11px] font-mono text-[#525252]">Modal Awal Laci (Opening Cash)</div>
          <div class="text-base font-bold font-mono text-[#161616] mt-1">
            {formatCurrency(activeSession.opening_cash)}
          </div>
        </div>

        <div class="p-3.5 bg-[#f4f4f4] border border-[#e0e0e0]">
          <div class="text-[11px] font-mono text-[#525252]">Penjualan Uang Tunai</div>
          <div class="text-base font-bold font-mono text-[#24a148] mt-1">
            +{formatCurrency(activeSession.total_cash_sales)}
          </div>
        </div>

        <div class="p-3.5 bg-[#f4f4f4] border border-[#e0e0e0]">
          <div class="text-[11px] font-mono text-[#525252]">Penjualan Non-Tunai (QRIS/Trf)</div>
          <div class="text-base font-bold font-mono text-[#0f62fe] mt-1">
            +{formatCurrency(activeSession.total_qris_sales + activeSession.total_transfer_sales)}
          </div>
        </div>

        <div class="p-3.5 bg-[#161616] text-white border border-[#262626]">
          <div class="text-[11px] font-mono text-[#8c8c8c]">Ekspektasi Uang Tunai di Laci</div>
          <div class="text-base font-bold font-mono text-[#24a148] mt-1">
            {formatCurrency(activeSession.opening_cash + activeSession.total_cash_sales)}
          </div>
        </div>
      </div>

      <div class="pt-2 flex justify-between items-center text-xs font-mono text-[#525252]">
        <span>Total Pesanan Sesi Ini: <strong class="text-[#161616]">{activeSession.order_count} Transaksi</strong></span>
        <span>Kasir: <strong class="text-[#161616]">{activeSession.cashier_name}</strong></span>
      </div>
    </div>
  {:else}
    <div class="p-8 bg-white border border-[#e0e0e0] text-center space-y-3 shadow-xs">
      <div class="w-12 h-12 rounded-full bg-[#da1e28]/10 text-[#da1e28] flex items-center justify-center mx-auto">
        <Clock class="w-6 h-6" />
      </div>
      <div>
        <h3 class="text-base font-bold text-[#161616]">Kasir Sedang Tutup</h3>
        <p class="text-xs text-[#525252] mt-0.5">Buka sesi baru untuk memulai input modal awal dan mencatat penjualan.</p>
      </div>
      <button
        type="button"
        onclick={onOpenSessionModal}
        class="bg-[#0f62fe] hover:bg-[#0050e6] text-white px-5 py-2.5 text-xs font-semibold inline-flex items-center gap-1.5 cursor-pointer shadow-xs"
      >
        <DollarSign class="w-4 h-4" />
        <span>Buka Sesi Kasir Sekarang</span>
      </button>
    </div>
  {/if}

  <!-- Riwayat Settlement Sebelumnya -->
  <div class="space-y-3 pt-4 border-t border-[#e0e0e0]">
    <h3 class="text-xs font-bold font-mono text-[#525252] uppercase tracking-wider">
      Riwayat Settlement Shift Sebelumnya
    </h3>

    {#if closedSessions.length === 0}
      <div class="p-8 bg-white border border-[#e0e0e0] text-center text-xs text-[#8c8c8c]">
        <Receipt class="w-8 h-8 mx-auto mb-2 opacity-30 text-[#8c8c8c]" />
        <p class="font-medium text-[#525252]">Belum ada riwayat settlement shift</p>
        <p class="text-[11px] text-[#8c8c8c] mt-0.5">Setiap kali sesi kasir ditutup, rekapitulasi rekonsiliasi akan tercatat di sini.</p>
      </div>
    {:else}
      <div class="bg-white border border-[#e0e0e0] overflow-x-auto shadow-xs">
        <table class="w-full text-xs text-left">
          <thead class="bg-[#f4f4f4] border-b border-[#e0e0e0] font-mono text-[10px] text-[#525252]">
            <tr>
              <th class="p-3">Waktu Tutup Shift</th>
              <th class="p-3">Kasir</th>
              <th class="p-3">Modal Awal</th>
              <th class="p-3">Tunai</th>
              <th class="p-3">Non-Tunai</th>
              <th class="p-3">Uang Fisik Aktual</th>
              <th class="p-3 text-right">Status Selisih</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#f4f4f4]">
            {#each closedSessions as s}
              <tr>
                <td class="p-3">
                  <div class="font-bold text-[#161616]">{new Date(s.closed_at || s.opened_at).toLocaleDateString('id-ID')}</div>
                  <div class="text-[10px] text-[#8c8c8c] font-mono">{new Date(s.closed_at || s.opened_at).toLocaleTimeString('id-ID')} WIB</div>
                </td>
                <td class="p-3 font-medium text-[#161616]">{s.cashier_name}</td>
                <td class="p-3 font-mono">{formatCurrency(s.opening_cash)}</td>
                <td class="p-3 font-mono text-[#24a148]">+{formatCurrency(s.total_cash_sales)}</td>
                <td class="p-3 font-mono text-[#0f62fe]">+{formatCurrency(s.total_qris_sales + s.total_transfer_sales)}</td>
                <td class="p-3 font-mono font-bold text-[#161616]">{formatCurrency(s.closing_cash_actual || 0)}</td>
                <td class="p-3 text-right">
                  <span class={`text-[10px] font-mono px-2 py-0.5 border ${
                    s.discrepancy_amount === 0
                      ? 'bg-[#24a148]/10 text-[#24a148] border-[#24a148]/30'
                      : s.discrepancy_amount > 0
                      ? 'bg-[#0f62fe]/10 text-[#0f62fe] border-[#0f62fe]/30'
                      : 'bg-[#da1e28]/10 text-[#da1e28] border-[#da1e28]/30'
                  }`}>
                    {s.discrepancy_amount === 0 ? 'SEIMBANG (Rp 0)' : s.discrepancy_amount > 0 ? `Lebih (+${formatCurrency(s.discrepancy_amount)})` : `Kurang (${formatCurrency(s.discrepancy_amount)})`}
                  </span>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    {/if}
  </div>
</div>
