<script lang="ts">
  import { DollarSign, X, Check, AlertCircle } from 'lucide-svelte';
  import type { PosSession, CloseSessionResponse } from '../../types/pos';
  import { formatCurrency } from '../../services/printer-service';
  import { posService } from '../../services/pos-service';

  interface Props {
    isOpen: boolean;
    activeSession: PosSession | null;
    cashierUserId?: string;
    onClose: () => void;
    onSessionOpened: (session: PosSession) => void;
    onSessionClosed: (closedData: CloseSessionResponse) => void;
  }

  let {
    isOpen = false,
    activeSession,
    cashierUserId = 'usr-active-01',
    onClose,
    onSessionOpened,
    onSessionClosed,
  }: Props = $props();

  let openingCashInput = $state(200000);
  let closingCashInput = $state(0);
  let sessionNotes = $state('');
  let isLoading = $state(false);
  let errorMessage = $state<string | null>(null);

  $effect(() => {
    if (isOpen) {
      errorMessage = null;
      if (activeSession && activeSession.status === 'OPEN') {
        closingCashInput = (activeSession.opening_cash || 0) + (activeSession.total_cash_sales || 0);
      }
    }
  });

  let expectedCash = $derived(
    activeSession ? (activeSession.opening_cash || 0) + (activeSession.total_cash_sales || 0) : 0
  );

  let discrepancy = $derived(
    activeSession ? closingCashInput - expectedCash : 0
  );

  async function handleStartSession() {
    isLoading = true;
    errorMessage = null;

    try {
      const session = await posService.openSession(cashierUserId, openingCashInput, sessionNotes);
      onSessionOpened(session);
      onClose();
    } catch (err: unknown) {
      if (err instanceof Error) {
        errorMessage = err.message;
      } else {
        errorMessage = 'Gagal membuka sesi kasir.';
      }
    } finally {
      isLoading = false;
    }
  }

  async function handleEndSession() {
    if (!activeSession) return;
    isLoading = true;
    errorMessage = null;

    try {
      const closedData = await posService.closeSession(
        activeSession.id,
        closingCashInput,
        cashierUserId,
        sessionNotes
      );
      onSessionClosed(closedData);
      onClose();
    } catch (err: unknown) {
      if (err instanceof Error) {
        errorMessage = err.message;
      } else {
        errorMessage = 'Gagal menutup sesi kasir.';
      }
    } finally {
      isLoading = false;
    }
  }
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 bg-[#17171c]/40 backdrop-blur-xs flex items-center justify-center p-4 select-none font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] max-w-md w-full p-6 shadow-none animate-in fade-in zoom-in-95">
      <!-- header modal -->
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5 mb-4">
        <div class="flex items-center gap-2">
          <DollarSign class="w-4 h-4 text-[#1863dc]" />
          <h2 class="text-sm font-medium text-[#212121]">
            {activeSession && activeSession.status === 'OPEN' ? 'Tutup Sesi Kasir & Rekonsiliasi' : 'Buka Sesi Kasir Baru'}
          </h2>
        </div>
        <button
          type="button"
          onclick={onClose}
          disabled={isLoading}
          class="text-[#93939f] hover:text-[#212121] cursor-pointer p-1"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      {#if errorMessage}
        <div class="mb-4 p-3 bg-[#ffad9b]/15 border border-[#ffad9b] rounded-xl text-xs text-[#b30000] flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      {#if !activeSession || activeSession.status === 'CLOSED'}
        <!-- tampilan buka kasir -->
        <div class="space-y-4">
          <p class="text-xs text-[#616161]">
            Masukkan modal awal kasir (uang kembalian dalam laci kasir) sebelum memulai transaksi shift baru.
          </p>

          <div>
            <label for="opening-cash" class="block text-xs font-medium text-[#616161] mb-1.5">
              Modal Awal Laci Kasir (Rp):
            </label>
            <input
              id="opening-cash"
              type="number"
              bind:value={openingCashInput}
              step="10000"
              disabled={isLoading}
              class="w-full bg-white border border-[#d9d9dd] rounded-xl px-3.5 py-2.5 font-mono text-base font-medium text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
            />
          </div>

          <div class="flex flex-wrap gap-2">
            {#each [100000, 200000, 300000, 500000] as preset}
              <button
                type="button"
                disabled={isLoading}
                onclick={() => (openingCashInput = preset)}
                class="px-3 py-1 bg-white border border-[#d9d9dd] hover:border-[#17171c] rounded-full text-xs font-mono text-[#212121] cursor-pointer transition-all"
              >
                {formatCurrency(preset)}
              </button>
            {/each}
          </div>

          <div>
            <label for="open-notes" class="block text-xs font-medium text-[#616161] mb-1">
              Catatan Pembukaan (Opsional):
            </label>
            <input
              id="open-notes"
              type="text"
              bind:value={sessionNotes}
              placeholder="e.g. Shift pagi..."
              disabled={isLoading}
              class="w-full bg-white border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs text-[#212121] placeholder-[#93939f] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
            />
          </div>

          <div class="pt-3 border-t border-[#d9d9dd] flex gap-2.5">
            <button
              type="button"
              onclick={onClose}
              disabled={isLoading}
              class="flex-1 py-2.5 bg-white text-[#616161] hover:bg-[#eeece7]/40 text-xs font-medium border border-[#d9d9dd] rounded-full cursor-pointer transition-all"
            >
              Batal
            </button>
            <button
              type="button"
              onclick={handleStartSession}
              disabled={isLoading}
              class="flex-2 py-2.5 bg-[#17171c] hover:bg-[#000000] text-white text-xs font-medium rounded-full flex items-center justify-center gap-1.5 cursor-pointer transition-all shadow-none disabled:opacity-50"
            >
              {#if isLoading}
                <span>Membuka Sesi...</span>
              {:else}
                <Check class="w-4 h-4" />
                <span>Buka Sesi ({formatCurrency(openingCashInput)})</span>
              {/if}
            </button>
          </div>
        </div>
      {:else}
        <!-- tampilan tutup kasir dan rekonsiliasi -->
        <div class="space-y-4">
          <!-- metrik sesi aktif -->
          <div class="p-4 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-2xl space-y-2 text-xs">
            <div class="flex justify-between text-[#616161]">
              <span>ID Sesi Kasir:</span>
              <span class="font-mono text-[#212121] text-[11px] truncate max-w-50">{activeSession.id}</span>
            </div>
            <div class="flex justify-between text-[#616161]">
              <span>Modal Awal Kasir:</span>
              <span class="font-mono text-[#212121]">{formatCurrency(activeSession.opening_cash)}</span>
            </div>
            <div class="flex justify-between text-[#616161]">
              <span>Total Penjualan Tunai:</span>
              <span class="font-mono text-[#212121]">{formatCurrency(activeSession.total_cash_sales || 0)}</span>
            </div>
            <div class="flex justify-between text-[#616161]">
              <span>Total QRIS &amp; Transfer:</span>
              <span class="font-mono text-[#212121]">
                {formatCurrency((activeSession.total_qris_sales || 0) + (activeSession.total_transfer_sales || 0))}
              </span>
            </div>
            <div class="flex justify-between text-[#212121] pt-2 border-t border-[#d9d9dd] font-medium">
              <span>Ekspektasi Uang Tunai di Laci:</span>
              <span class="font-mono text-[#1863dc] font-semibold">{formatCurrency(expectedCash)}</span>
            </div>
          </div>

          <!-- input uang fisik aktual -->
          <div>
            <label for="closing-cash" class="block text-xs font-medium text-[#616161] mb-1.5">
              Hitung Uang Fisik Aktual di Laci (Rp):
            </label>
            <input
              id="closing-cash"
              type="number"
              bind:value={closingCashInput}
              step="1000"
              disabled={isLoading}
              class="w-full bg-white border border-[#d9d9dd] rounded-xl px-3.5 py-2.5 font-mono text-base font-medium text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
            />
          </div>

          <!-- indikator selisih rekonsiliasi -->
          <div class={`p-3.5 rounded-xl border flex items-center justify-between text-xs font-mono ${
            discrepancy === 0
              ? 'bg-[#edfce9] border-[#edfce9] text-[#003c33]'
              : discrepancy > 0
              ? 'bg-[#f1f5ff] border-[#f1f5ff] text-[#1863dc]'
              : 'bg-[#ffad9b]/15 border-[#ffad9b] text-[#b30000]'
          }`}>
            <span>Selisih Kas (Discrepancy):</span>
            <span class="font-medium text-sm">
              {discrepancy === 0 ? 'SEIMBANG (Rp 0)' : discrepancy > 0 ? `Lebih (+${formatCurrency(discrepancy)})` : `Kurang (${formatCurrency(discrepancy)})`}
            </span>
          </div>

          <div>
            <label for="session-notes" class="block text-xs font-medium text-[#616161] mb-1">
              Catatan Rekonsiliasi (Opsional):
            </label>
            <input
              id="session-notes"
              type="text"
              bind:value={sessionNotes}
              placeholder="e.g. Uang kembalian 2.000 rusak..."
              disabled={isLoading}
              class="w-full bg-white border border-[#d9d9dd] rounded-xl px-3 py-2 text-xs text-[#212121] placeholder-[#93939f] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
            />
          </div>

          <div class="pt-3 border-t border-[#d9d9dd] flex gap-2.5">
            <button
              type="button"
              onclick={onClose}
              disabled={isLoading}
              class="flex-1 py-2.5 bg-white text-[#616161] hover:bg-[#eeece7]/40 text-xs font-medium border border-[#d9d9dd] rounded-full cursor-pointer transition-all"
            >
              Batal
            </button>
            <button
              type="button"
              onclick={handleEndSession}
              disabled={isLoading}
              class="flex-2 py-2.5 bg-[#b30000] hover:bg-[#800000] text-white text-xs font-medium rounded-full flex items-center justify-center gap-1.5 cursor-pointer transition-all shadow-none disabled:opacity-50"
            >
              {#if isLoading}
                <span>Menutup Sesi...</span>
              {:else}
                <span>Tutup Shift &amp; Rekonsiliasi</span>
              {/if}
            </button>
          </div>
        </div>
      {/if}
    </div>
  </div>
{/if}
