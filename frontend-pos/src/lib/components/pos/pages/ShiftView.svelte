<script lang="ts">
  import { Monitor, Clock, Banknote, ArrowRight, ShoppingBag, Plus } from 'lucide-svelte';
  import type { PosSession, CashierUser, OutletPurchase } from '../../../types/pos';
  import { formatCurrency } from '../../../services/printer-service';

  interface Props {
    activeSession: PosSession | null;
    activeCashier: CashierUser | null;
    purchases?: OutletPurchase[];
    onOpenSessionModal: () => void;
    onOpenPurchaseModal?: () => void;
    onGoToSettlement?: () => void;
  }

  let {
    activeSession,
    activeCashier,
    purchases = [],
    onOpenSessionModal,
    onOpenPurchaseModal,
    onGoToSettlement,
  }: Props = $props();

  let sessionPurchases = $derived(
    activeSession
      ? purchases.filter((p) => p.pos_session_id === activeSession?.id)
      : purchases
  );

  let totalCashPurchases = $derived(
    sessionPurchases
      .filter((p) => p.funding_source === 'CASH_DRAWER')
      .reduce((sum, p) => sum + Number(p.total_price), 0)
  );

  let totalReimbursePurchases = $derived(
    sessionPurchases
      .filter((p) => p.funding_source === 'EXTERNAL_REIMBURSE')
      .reduce((sum, p) => sum + Number(p.total_price), 0)
  );

  let expectedDrawerCash = $derived(
    activeSession
      ? Number(activeSession.opening_cash) + Number(activeSession.total_cash_sales) - totalCashPurchases
      : 0
  );
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

    <div class="flex items-center gap-2">
      {#if activeSession && activeSession.status === 'OPEN' && onOpenPurchaseModal}
        <button
          type="button"
          onclick={onOpenPurchaseModal}
          class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-zinc-300 bg-white px-3.5 py-2 text-xs font-bold text-zinc-800 shadow-2xs transition-all hover:bg-zinc-100"
        >
          <Plus class="size-4 text-zinc-600" />
          <span>Catat Belanja (Petty Cash)</span>
        </button>
      {/if}

      <button
        type="button"
        onclick={onOpenSessionModal}
        class={`active:scale-0.99 flex cursor-pointer items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold shadow-2xs transition-all ${
          activeSession && activeSession.status === 'OPEN'
            ? 'bg-red-600 text-white hover:bg-red-700'
            : 'bg-zinc-900 text-white hover:bg-black'
        }`}
      >
        <span>
          {activeSession && activeSession.status === 'OPEN'
            ? 'Tutup Shift Sekarang'
            : 'Buka Shift Baru'}
        </span>
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
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
            <div class="space-y-1 rounded-xl border border-zinc-200 bg-zinc-50 p-3.5">
              <div class="text-[11px] font-medium text-zinc-500">Penjualan Tunai Masuk</div>
              <div class="font-mono text-sm font-bold text-emerald-700">
                +{formatCurrency(activeSession.total_cash_sales)}
              </div>
            </div>

            <div class="space-y-1 rounded-xl border border-zinc-200 bg-zinc-50 p-3.5">
              <div class="text-[11px] font-medium text-zinc-500">Belanja Kas Laci (-)</div>
              <div class="font-mono text-sm font-bold text-amber-700">
                -{formatCurrency(totalCashPurchases)}
              </div>
              {#if totalReimbursePurchases > 0}
                <div class="text-[10px] text-zinc-500">
                  Reimburse: {formatCurrency(totalReimbursePurchases)}
                </div>
              {/if}
            </div>

            <div class="space-y-1 rounded-xl border border-zinc-200 bg-zinc-50 p-3.5">
              <div class="text-[11px] font-medium text-zinc-500">Non-Tunai (QRIS / EDC)</div>
              <div class="font-mono text-sm font-bold text-zinc-900">
                +{formatCurrency(
                  activeSession.total_qris_sales + activeSession.total_transfer_sales
                )}
              </div>
            </div>

            <div class="space-y-1 rounded-xl bg-zinc-900 p-3.5 text-white shadow-xs">
              <div class="text-[11px] font-medium text-zinc-400">Ekspektasi Kas di Laci</div>
              <div class="font-mono text-sm font-bold text-white">
                {formatCurrency(expectedDrawerCash)}
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

      <!-- Card 2: Petty Cash / Belanja Outlet List -->
      {#if activeSession && activeSession.status === 'OPEN'}
        <div class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xs">
          <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
            <div class="flex items-center gap-2">
              <ShoppingBag class="size-4 text-zinc-800" />
              <h3 class="text-sm font-bold text-zinc-900">
                Pengeluaran Kas Belanja Outlet (Shift Ini)
              </h3>
              <span class="rounded-md bg-zinc-100 px-2 py-0.5 font-mono text-xs font-semibold text-zinc-700">
                {sessionPurchases.length} Catatan
              </span>
            </div>

            {#if onOpenPurchaseModal}
              <button
                type="button"
                onclick={onOpenPurchaseModal}
                class="flex cursor-pointer items-center gap-1 text-xs font-bold text-zinc-900 hover:underline"
              >
                <Plus class="size-3.5" />
                <span>+ Catat Belanja</span>
              </button>
            {/if}
          </div>

          {#if sessionPurchases.length === 0}
            <div class="py-6 text-center text-xs text-zinc-400">
              Belum ada pengeluaran kas belanja yang dicatat pada shift aktif ini.
            </div>
          {:else}
            <div class="divide-y divide-zinc-100 overflow-hidden rounded-xl border border-zinc-200">
              {#each sessionPurchases as p (p.id)}
                <div class="flex items-center justify-between p-3 text-xs hover:bg-zinc-50/70">
                  <div class="space-y-0.5">
                    <div class="flex items-center gap-2">
                      <span class="font-bold text-zinc-900">{p.item_name}</span>
                      <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium text-zinc-600">
                        {p.quantity} {p.unit}
                      </span>
                      <span
                        class={`rounded px-1.5 py-0.5 text-[10px] font-bold ${
                          p.funding_source === 'CASH_DRAWER'
                            ? 'bg-amber-100 text-amber-900'
                            : 'bg-blue-100 text-blue-900'
                        }`}
                      >
                        {p.funding_source === 'CASH_DRAWER' ? 'Kas Laci' : 'Reimburse'}
                      </span>
                    </div>
                    <div class="text-[11px] text-zinc-500">
                      {p.category.replace(/_/g, ' ')} {p.notes ? `• ${p.notes}` : ''}
                    </div>
                  </div>

                  <div class="text-right">
                    <div class="font-mono text-xs font-bold text-zinc-900">
                      {formatCurrency(p.total_price)}
                    </div>
                    <div class="text-[10px] text-zinc-400">
                      {new Date(p.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}
                    </div>
                  </div>
                </div>
              {/each}
            </div>
          {/if}
        </div>
      {/if}
    </div>
  </div>
</div>
