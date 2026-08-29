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

<div class="flex h-full flex-1 flex-col overflow-hidden bg-[#f4f6f9] font-sans select-none">
  <!-- Top Bar -->
  <div
    class="flex h-14 shrink-0 items-center justify-between border-b border-zinc-200 bg-white px-6 shadow-2xs"
  >
    <div class="flex items-center gap-3">
      <h1 class="text-base font-bold tracking-tight text-zinc-900">Manajemen Shift Kasir</h1>
      <span class="font-mono text-xs text-zinc-400">|</span>
      <span class="text-xs font-medium text-zinc-500">Laci Kas &amp; Operasional Kasir</span>
    </div>

    <div>
      <button
        type="button"
        onclick={onOpenSessionModal}
        class={`active:scale-0.99 flex cursor-pointer items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold shadow-2xs transition-all ${
          activeSession && activeSession.status === 'OPEN'
            ? 'bg-red-600 text-white hover:bg-red-700'
            : 'bg-zinc-900 text-white hover:bg-black'
        }`}
      >
        <span
          >{activeSession && activeSession.status === 'OPEN'
            ? 'Tutup Shift Sekarang'
            : 'Buka Shift Baru'}</span
        >
      </button>
    </div>
  </div>

  <!-- Content Body -->
  <div class="flex-1 overflow-y-auto p-6 sm:p-8">
    <div class="mx-auto max-w-3xl space-y-6">
      <!-- Card 1: Shift Status & Cashier Header -->
      <div class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xs">
        <div class="flex items-center justify-between border-b border-zinc-100 pb-4">
          <div class="flex items-center gap-3">
            <div
              class="flex size-12 items-center justify-center rounded-2xl bg-zinc-900 text-white shadow-xs"
            >
              <Monitor class="size-6" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h2 class="text-base font-bold text-zinc-900">Shift Operasional Kasir</h2>
                <span
                  class={`rounded-full px-2.5 py-0.5 font-mono text-[10px] font-bold ${
                    activeSession?.status === 'OPEN'
                      ? 'border border-emerald-200 bg-emerald-100 text-emerald-800'
                      : 'bg-zinc-200 text-zinc-600'
                  }`}
                >
                  {activeSession?.status === 'OPEN' ? 'SEDANG AKTIF' : 'SHIFT DITUTUP'}
                </span>
              </div>
              <p class="mt-0.5 text-xs text-zinc-500">
                {activeSession?.status === 'OPEN'
                  ? `Dibuka pada ${new Date(activeSession.opened_at).toLocaleTimeString('id-ID')} WIB oleh ${activeSession.cashier_name || activeCashier?.name}`
                  : 'Laci kasir saat ini terkunci. Buka shift baru untuk memulai penjualan.'}
              </p>
            </div>
          </div>
        </div>

        <!-- Opening Cash Row -->
        <div
          class="flex items-center justify-between rounded-xl border border-zinc-200 bg-zinc-50 p-4"
        >
          <div class="flex items-center gap-2">
            <Banknote class="size-4 text-zinc-600" />
            <span class="text-xs font-bold text-zinc-700">Saldo Tunai Awal (Modal Laci Kasir)</span>
          </div>
          <span class="font-mono text-sm font-bold text-zinc-900">
            {formatCurrency(activeSession?.opening_cash || 0)}
          </span>
        </div>

        {#if activeSession && activeSession.status === 'OPEN'}
          <!-- Live Financial Metrics Grid -->
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="space-y-1 rounded-xl border border-zinc-200 bg-zinc-50 p-4">
              <div class="text-[11px] font-medium text-zinc-500">Penjualan Tunai Masuk</div>
              <div class="font-mono text-base font-bold text-emerald-700">
                +{formatCurrency(activeSession.total_cash_sales)}
              </div>
            </div>

            <div class="space-y-1 rounded-xl border border-zinc-200 bg-zinc-50 p-4">
              <div class="text-[11px] font-medium text-zinc-500">Non-Tunai (QRIS / EDC)</div>
              <div class="font-mono text-base font-bold text-zinc-900">
                +{formatCurrency(
                  activeSession.total_qris_sales + activeSession.total_transfer_sales
                )}
              </div>
            </div>

            <div class="space-y-1 rounded-xl bg-zinc-900 p-4 text-white shadow-xs">
              <div class="text-[11px] font-medium text-zinc-400">Ekspektasi Kas Fisik di Laci</div>
              <div class="font-mono text-base font-bold text-white">
                {formatCurrency(activeSession.opening_cash + activeSession.total_cash_sales)}
              </div>
            </div>
          </div>

          <!-- Shift Rule Alert & Settlement Redirection -->
          <div
            class="flex items-center justify-between gap-4 rounded-xl border border-zinc-200 bg-zinc-50 p-4"
          >
            <div class="space-y-0.5">
              <div class="text-xs font-bold text-zinc-900">
                Ingin melakukan Setoran / Settlement?
              </div>
              <p class="text-[11px] text-zinc-500">
                Tutup shift kasir terlebih dahulu untuk mengunci kas fisik dan memindahkan transaksi
                ke tab Settlement.
              </p>
            </div>
            {#if onGoToSettlement}
              <button
                type="button"
                onclick={onGoToSettlement}
                class="flex shrink-0 cursor-pointer items-center gap-1 rounded-xl border border-zinc-200 bg-white px-3.5 py-2 text-xs font-semibold text-zinc-800 shadow-2xs transition-all hover:bg-zinc-100"
              >
                <span>Lihat Tab Settlement</span>
                <ArrowRight class="size-3.5" />
              </button>
            {/if}
          </div>

          <div
            class="flex items-center justify-between border-t border-zinc-100 pt-2 font-mono text-xs text-zinc-500"
          >
            <span
              >Total Transaksi Sesi: <strong class="text-zinc-900"
                >{activeSession.order_count} Struk</strong
              ></span
            >
            <span
              >Kasir Bertugas: <strong class="text-zinc-900"
                >{activeSession.cashier_name || activeCashier?.name}</strong
              ></span
            >
          </div>
        {:else}
          <div class="space-y-2 py-12 text-center text-zinc-400">
            <Clock class="mx-auto size-10 text-zinc-400 opacity-30" />
            <p class="text-sm font-semibold text-zinc-800">Kasir Sedang Tidak Aktif</p>
            <p class="text-xs text-zinc-500">
              Buka shift baru untuk mulai menerima transaksi di meja kasir.
            </p>
          </div>
        {/if}
      </div>
    </div>
  </div>
</div>
