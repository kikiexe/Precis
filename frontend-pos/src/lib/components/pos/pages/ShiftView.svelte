<script lang="ts">
  import { Monitor, Clock, Banknote, ArrowRight } from 'lucide-svelte';
  import type { PosSession, CashierUser } from '../../../types/pos';
  import { formatCurrency } from '../../../services/printer-service';

  interface Props {
    activeSession: PosSession | null;
    activeCashier: CashierUser | null;
    onOpenSessionModal: () => void;
    onGoToSettlement?: () => void;
  }

  let { activeSession, activeCashier, onOpenSessionModal, onGoToSettlement }: Props = $props();
</script>

<div class="flex-1 flex flex-col h-full bg-[#f4f6f9] overflow-hidden font-sans select-none">
  <!-- Top Bar -->
  <div class="h-14 bg-white border-b border-zinc-200 px-6 flex items-center justify-between shrink-0 shadow-2xs">
    <div class="flex items-center gap-3">
      <h1 class="text-base font-bold text-zinc-900 tracking-tight">Manajemen Shift Kasir</h1>
      <span class="text-xs font-mono text-zinc-400">|</span>
      <span class="text-xs text-zinc-500 font-medium">Laci Kas &amp; Operasional Kasir</span>
    </div>

    <div>
      <button
        type="button"
        onclick={onOpenSessionModal}
        class={`px-4 py-2 text-xs font-bold rounded-xl transition-all cursor-pointer shadow-2xs active:scale-[0.99] flex items-center gap-1.5 ${
          activeSession && activeSession.status === 'OPEN'
            ? 'bg-red-600 hover:bg-red-700 text-white'
            : 'bg-zinc-900 hover:bg-black text-white'
        }`}
      >
        <span>{activeSession && activeSession.status === 'OPEN' ? 'Tutup Shift Sekarang' : 'Buka Shift Baru'}</span>
      </button>
    </div>
  </div>

  <!-- Content Body -->
  <div class="flex-1 p-6 sm:p-8 overflow-y-auto">
    <div class="max-w-3xl mx-auto space-y-6">
      
      <!-- Card 1: Shift Status & Cashier Header -->
      <div class="bg-white rounded-2xl border border-zinc-200 p-6 shadow-2xs space-y-5">
        <div class="flex items-center justify-between border-b border-zinc-100 pb-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-zinc-900 text-white flex items-center justify-center shadow-xs">
              <Monitor class="w-6 h-6" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h2 class="text-base font-bold text-zinc-900">Shift Operasional Kasir</h2>
                <span class={`text-[10px] px-2.5 py-0.5 rounded-full font-mono font-bold ${
                  activeSession?.status === 'OPEN'
                    ? 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                    : 'bg-zinc-200 text-zinc-600'
                }`}>
                  {activeSession?.status === 'OPEN' ? 'SEDANG AKTIF' : 'SHIFT DITUTUP'}
                </span>
              </div>
              <p class="text-xs text-zinc-500 mt-0.5">
                {activeSession?.status === 'OPEN'
                  ? `Dibuka pada ${new Date(activeSession.opened_at).toLocaleTimeString('id-ID')} WIB oleh ${activeSession.cashier_name || activeCashier?.name}`
                  : 'Laci kasir saat ini terkunci. Buka shift baru untuk memulai penjualan.'}
              </p>
            </div>
          </div>
        </div>

        <!-- Opening Cash Row -->
        <div class="p-4 bg-zinc-50 rounded-xl border border-zinc-200 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Banknote class="w-4 h-4 text-zinc-600" />
            <span class="text-xs font-bold text-zinc-700">Saldo Tunai Awal (Modal Laci Kasir)</span>
          </div>
          <span class="font-mono text-sm font-bold text-zinc-900">
            {formatCurrency(activeSession?.opening_cash || 0)}
          </span>
        </div>

        {#if activeSession && activeSession.status === 'OPEN'}
          <!-- Live Financial Metrics Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="p-4 bg-zinc-50 border border-zinc-200 rounded-xl space-y-1">
              <div class="text-[11px] text-zinc-500 font-medium">Penjualan Tunai Masuk</div>
              <div class="text-base font-bold font-mono text-emerald-700">
                +{formatCurrency(activeSession.total_cash_sales)}
              </div>
            </div>

            <div class="p-4 bg-zinc-50 border border-zinc-200 rounded-xl space-y-1">
              <div class="text-[11px] text-zinc-500 font-medium">Non-Tunai (QRIS / EDC)</div>
              <div class="text-base font-bold font-mono text-zinc-900">
                +{formatCurrency(activeSession.total_qris_sales + activeSession.total_transfer_sales)}
              </div>
            </div>

            <div class="p-4 bg-zinc-900 text-white rounded-xl shadow-xs space-y-1">
              <div class="text-[11px] text-zinc-400 font-medium">Ekspektasi Kas Fisik di Laci</div>
              <div class="text-base font-bold font-mono text-white">
                {formatCurrency(activeSession.opening_cash + activeSession.total_cash_sales)}
              </div>
            </div>
          </div>

          <!-- Shift Rule Alert & Settlement Redirection -->
          <div class="p-4 bg-zinc-50 border border-zinc-200 rounded-xl flex items-center justify-between gap-4">
            <div class="space-y-0.5">
              <div class="text-xs font-bold text-zinc-900">Ingin melakukan Setoran / Settlement?</div>
              <p class="text-[11px] text-zinc-500">
                Tutup shift kasir terlebih dahulu untuk mengunci kas fisik dan memindahkan transaksi ke tab Settlement.
              </p>
            </div>
            {#if onGoToSettlement}
              <button
                type="button"
                onclick={onGoToSettlement}
                class="px-3.5 py-2 bg-white hover:bg-zinc-100 border border-zinc-200 text-zinc-800 text-xs font-semibold rounded-xl transition-all cursor-pointer shadow-2xs shrink-0 flex items-center gap-1"
              >
                <span>Lihat Tab Settlement</span>
                <ArrowRight class="w-3.5 h-3.5" />
              </button>
            {/if}
          </div>

          <div class="pt-2 flex justify-between items-center text-xs font-mono text-zinc-500 border-t border-zinc-100">
            <span>Total Transaksi Sesi: <strong class="text-zinc-900">{activeSession.order_count} Struk</strong></span>
            <span>Kasir Bertugas: <strong class="text-zinc-900">{activeSession.cashier_name || activeCashier?.name}</strong></span>
          </div>
        {:else}
          <div class="py-12 text-center text-zinc-400 space-y-2">
            <Clock class="w-10 h-10 mx-auto opacity-30 text-zinc-400" />
            <p class="text-sm font-semibold text-zinc-800">Kasir Sedang Tidak Aktif</p>
            <p class="text-xs text-zinc-500">Buka shift baru untuk mulai menerima transaksi di meja kasir.</p>
          </div>
        {/if}
      </div>
    </div>
  </div>
</div>
