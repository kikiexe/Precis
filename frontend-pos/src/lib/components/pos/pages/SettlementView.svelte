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

<div class="flex-1 bg-[#eeece7]/30 p-4 sm:p-6 md:p-8 overflow-y-auto space-y-6 font-sans">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-xl font-medium text-[#212121] tracking-tight">Settlement Shift &amp; Tutup Kasir</h2>
      <p class="text-xs text-[#616161] font-normal mt-0.5">Rekonsiliasi total transaksi kasir dan perhitungan selisih fisik laci</p>
    </div>

    <button
      type="button"
      onclick={onOpenSessionModal}
      class="bg-[#17171c] hover:bg-[#000000] text-white px-5 py-2.5 text-xs font-medium rounded-full flex items-center gap-2 cursor-pointer transition-all shadow-none"
    >
      <Calculator class="w-4 h-4" />
      <span>{activeSession && activeSession.status === 'OPEN' ? 'Tutup Shift & Rekonsiliasi' : 'Buka Shift Baru'}</span>
    </button>
  </div>

  <!-- Active Session Overview Card -->
  {#if activeSession && activeSession.status === 'OPEN'}
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] p-6 shadow-none space-y-4">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <div class="flex items-center gap-2.5">
          <div class="w-2.5 h-2.5 rounded-full bg-[#003c33] animate-ping"></div>
          <span class="font-medium text-sm text-[#212121]">Sesi Kasir Sedang Berjalan</span>
        </div>
        <span class="text-xs font-mono text-[#75758a]">
          Dibuka: {new Date(activeSession.opened_at).toLocaleTimeString('id-ID')} WIB
        </span>
      </div>

      <!-- Financial Metrics Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5">
        <div class="p-4 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[16px]">
          <div class="text-[11px] text-[#75758a]">Modal Awal Laci (Opening Cash)</div>
          <div class="text-base font-medium font-mono text-[#212121] mt-1">
            {formatCurrency(activeSession.opening_cash)}
          </div>
        </div>

        <div class="p-4 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[16px]">
          <div class="text-[11px] text-[#75758a]">Penjualan Uang Tunai</div>
          <div class="text-base font-medium font-mono text-[#003c33] mt-1">
            +{formatCurrency(activeSession.total_cash_sales)}
          </div>
        </div>

        <div class="p-4 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[16px]">
          <div class="text-[11px] text-[#75758a]">Penjualan Non-Tunai (QRIS/Trf)</div>
          <div class="text-base font-medium font-mono text-[#1863dc] mt-1">
            +{formatCurrency(activeSession.total_qris_sales + activeSession.total_transfer_sales)}
          </div>
        </div>

        <div class="p-4 bg-[#17171c] text-white rounded-[16px] border border-[#17171c]">
          <div class="text-[11px] text-[#93939f]">Ekspektasi Uang Tunai di Laci</div>
          <div class="text-base font-medium font-mono text-[#edfce9] mt-1">
            {formatCurrency(activeSession.opening_cash + activeSession.total_cash_sales)}
          </div>
        </div>
      </div>

      <div class="pt-2 flex justify-between items-center text-xs font-mono text-[#616161]">
        <span>Total Pesanan Sesi Ini: <strong class="text-[#212121] font-medium">{activeSession.order_count} Transaksi</strong></span>
        <span>Kasir: <strong class="text-[#212121] font-medium">{activeSession.cashier_name}</strong></span>
      </div>
    </div>
  {:else}
    <div class="p-8 bg-white border border-[#d9d9dd] rounded-[22px] text-center space-y-3 shadow-none">
      <div class="w-12 h-12 rounded-full bg-[#ffad9b]/15 text-[#b30000] flex items-center justify-center mx-auto">
        <Clock class="w-6 h-6" />
      </div>
      <div>
        <h3 class="text-base font-medium text-[#212121]">Kasir Sedang Tutup</h3>
        <p class="text-xs text-[#75758a] mt-0.5">Buka sesi baru untuk memulai input modal awal dan mencatat penjualan.</p>
      </div>
      <button
        type="button"
        onclick={onOpenSessionModal}
        class="bg-[#17171c] hover:bg-[#000000] text-white px-5 py-2.5 text-xs font-medium rounded-full inline-flex items-center gap-1.5 cursor-pointer transition-all shadow-none"
      >
        <DollarSign class="w-4 h-4" />
        <span>Buka Sesi Kasir Sekarang</span>
      </button>
    </div>
  {/if}

  <!-- Riwayat Settlement Sebelumnya -->
  <div class="space-y-3 pt-4 border-t border-[#d9d9dd]">
    <h3 class="text-xs font-medium text-[#75758a] uppercase tracking-wider px-1">
      Riwayat Settlement Shift Sebelumnya
    </h3>

    {#if closedSessions.length === 0}
      <div class="p-8 bg-white border border-[#d9d9dd] rounded-[20px] text-center text-xs text-[#75758a]">
        <Receipt class="w-8 h-8 mx-auto mb-2 opacity-30 text-[#93939f]" />
        <p class="font-medium text-[#212121]">Belum ada riwayat settlement shift</p>
        <p class="text-[11px] text-[#75758a] mt-0.5">Setiap kali sesi kasir ditutup, rekapitulasi rekonsiliasi akan tercatat di sini.</p>
      </div>
    {:else}
      <div class="bg-white border border-[#d9d9dd] rounded-[20px] overflow-hidden shadow-none">
        <table class="w-full text-xs text-left border-collapse">
          <thead class="bg-[#eeece7]/50 border-b border-[#d9d9dd] font-mono text-[10px] text-[#616161]">
            <tr>
              <th class="p-3.5 font-medium">Waktu Tutup Shift</th>
              <th class="p-3.5 font-medium">Kasir</th>
              <th class="p-3.5 font-medium">Modal Awal</th>
              <th class="p-3.5 font-medium">Tunai</th>
              <th class="p-3.5 font-medium">Non-Tunai</th>
              <th class="p-3.5 font-medium">Uang Fisik Aktual</th>
              <th class="p-3.5 text-right font-medium">Status Selisih</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#d9d9dd]/60">
            {#each closedSessions as s}
              <tr class="hover:bg-[#eeece7]/20 transition-colors">
                <td class="p-3.5">
                  <div class="font-medium text-[#212121]">{new Date(s.closed_at || s.opened_at).toLocaleDateString('id-ID')}</div>
                  <div class="text-[10px] text-[#75758a] font-mono">{new Date(s.closed_at || s.opened_at).toLocaleTimeString('id-ID')} WIB</div>
                </td>
                <td class="p-3.5 font-medium text-[#212121]">{s.cashier_name}</td>
                <td class="p-3.5 font-mono">{formatCurrency(s.opening_cash)}</td>
                <td class="p-3.5 font-mono text-[#003c33]">+{formatCurrency(s.total_cash_sales)}</td>
                <td class="p-3.5 font-mono text-[#1863dc]">+{formatCurrency(s.total_qris_sales + s.total_transfer_sales)}</td>
                <td class="p-3.5 font-mono font-medium text-[#212121]">{formatCurrency(s.closing_cash_actual || 0)}</td>
                <td class="p-3.5 text-right">
                  <span class={`text-[10px] font-mono px-2.5 py-0.5 rounded-full font-medium ${
                    s.discrepancy_amount === 0
                      ? 'bg-[#edfce9] text-[#003c33]'
                      : s.discrepancy_amount > 0
                      ? 'bg-[#f1f5ff] text-[#1863dc]'
                      : 'bg-[#ffad9b]/20 text-[#b30000]'
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
