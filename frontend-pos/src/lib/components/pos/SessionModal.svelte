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
  <div class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4 select-none animate-in fade-in">
    <div class="bg-white border border-[#e0e0e0] max-w-md w-full p-6 shadow-2xl animate-in zoom-in-95">
      <!-- header modal -->
      <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3 mb-4">
        <div class="flex items-center gap-2">
          <DollarSign class="w-4 h-4 text-[#0f62fe]" />
          <h2 class="text-sm font-bold text-[#161616]">
            {activeSession && activeSession.status === 'OPEN' ? 'Tutup Sesi Kasir & Rekonsiliasi' : 'Buka Sesi Kasir Baru'}
          </h2>
        </div>
        <button
          type="button"
          onclick={onClose}
          disabled={isLoading}
          class="text-[#8c8c8c] hover:text-[#161616] cursor-pointer"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      {#if errorMessage}
        <div class="mb-4 p-3 bg-[#da1e28]/10 border-l-4 border-[#da1e28] text-xs text-[#da1e28] flex items-start gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      {#if !activeSession || activeSession.status === 'CLOSED'}
        <!-- tampilan buka kasir -->
        <div class="space-y-4">
          <p class="text-xs text-[#525252]">
            Masukkan modal awal kasir (uang kembalian dalam laci kasir) sebelum memulai transaksi shift baru.
          </p>

          <div>
            <label for="opening-cash" class="block text-xs font-mono text-[#525252] mb-1.5">
              Modal Awal Laci Kasir (Rp):
            </label>
            <input
              id="opening-cash"
              type="number"
              bind:value={openingCashInput}
              step="10000"
              disabled={isLoading}
              class="w-full bg-[#f4f4f4] border border-[#e0e0e0] px-3 py-2.5 font-mono text-base font-bold text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
            />
          </div>

          <div class="flex gap-2">
            {#each [100000, 200000, 300000, 500000] as preset}
              <button
                type="button"
                disabled={isLoading}
                onclick={() => (openingCashInput = preset)}
                class="px-2.5 py-1 bg-[#f4f4f4] border border-[#e0e0e0] text-xs font-mono hover:bg-[#e0e0e0] cursor-pointer"
              >
                {formatCurrency(preset)}
              </button>
            {/each}
          </div>

          <div>
            <label for="open-notes" class="block text-xs font-mono text-[#525252] mb-1">
              Catatan Pembukaan (Opsional):
            </label>
            <input
              id="open-notes"
              type="text"
              bind:value={sessionNotes}
              placeholder="e.g. Shift pagi..."
              disabled={isLoading}
              class="w-full bg-[#f4f4f4] border border-[#e0e0e0] px-2.5 py-1.5 text-xs focus:border-[#0f62fe] focus:outline-hidden"
            />
          </div>

          <div class="pt-4 border-t border-[#e0e0e0] flex gap-2">
            <button
              type="button"
              onclick={onClose}
              disabled={isLoading}
              class="flex-1 py-2.5 bg-[#f4f4f4] text-[#525252] text-xs border border-[#e0e0e0] cursor-pointer"
            >
              Batal
            </button>
            <button
              type="button"
              onclick={handleStartSession}
              disabled={isLoading}
              class="flex-2 py-2.5 bg-[#0f62fe] hover:bg-[#0050e6] text-white text-xs font-semibold flex items-center justify-center gap-1.5 cursor-pointer shadow-xs disabled:opacity-50"
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
          <div class="p-3 bg-[#f4f4f4] border border-[#e0e0e0] space-y-2 text-xs">
            <div class="flex justify-between text-[#525252]">
              <span>ID Sesi Kasir:</span>
              <span class="font-mono text-[#161616] text-[11px] truncate max-w-50">{activeSession.id}</span>
            </div>
            <div class="flex justify-between text-[#525252]">
              <span>Modal Awal Kasir:</span>
              <span class="font-mono text-[#161616]">{formatCurrency(activeSession.opening_cash)}</span>
            </div>
            <div class="flex justify-between text-[#525252]">
              <span>Total Penjualan Tunai:</span>
              <span class="font-mono text-[#161616]">{formatCurrency(activeSession.total_cash_sales || 0)}</span>
            </div>
            <div class="flex justify-between text-[#525252]">
              <span>Total QRIS &amp; Transfer:</span>
              <span class="font-mono text-[#161616]">
                {formatCurrency((activeSession.total_qris_sales || 0) + (activeSession.total_transfer_sales || 0))}
              </span>
            </div>
            <div class="flex justify-between text-[#525252] pt-1.5 border-t border-[#e0e0e0] font-bold">
              <span>Ekspektasi Uang Tunai di Laci:</span>
              <span class="font-mono text-[#0f62fe]">{formatCurrency(expectedCash)}</span>
            </div>
          </div>

          <!-- input uang fisik aktual -->
          <div>
            <label for="closing-cash" class="block text-xs font-mono text-[#525252] mb-1.5">
              Hitung Uang Fisik Aktual di Laci (Rp):
            </label>
            <input
              id="closing-cash"
              type="number"
              bind:value={closingCashInput}
              step="1000"
              disabled={isLoading}
              class="w-full bg-white border border-[#e0e0e0] px-3 py-2.5 font-mono text-base font-bold text-[#161616] focus:border-[#0f62fe] focus:outline-hidden"
            />
          </div>

          <!-- indikator selisih rekonsiliasi -->
          <div class={`p-3 border flex items-center justify-between text-xs font-mono ${
            discrepancy === 0
              ? 'bg-[#24a148]/10 border-[#24a148]/30 text-[#24a148]'
              : discrepancy > 0
              ? 'bg-[#0f62fe]/10 border-[#0f62fe]/30 text-[#0f62fe]'
              : 'bg-[#da1e28]/10 border-[#da1e28]/30 text-[#da1e28]'
          }`}>
            <span>Selisih Kas (Discrepancy):</span>
            <span class="font-bold text-sm">
              {discrepancy === 0 ? 'SEIMBANG (Rp 0)' : discrepancy > 0 ? `Lebih (+${formatCurrency(discrepancy)})` : `Kurang (${formatCurrency(discrepancy)})`}
            </span>
          </div>

          <div>
            <label for="session-notes" class="block text-xs font-mono text-[#525252] mb-1">
              Catatan Rekonsiliasi (Opsional):
            </label>
            <input
              id="session-notes"
              type="text"
              bind:value={sessionNotes}
              placeholder="e.g. Uang kembalian 2.000 rusak..."
              disabled={isLoading}
              class="w-full bg-[#f4f4f4] border border-[#e0e0e0] px-2.5 py-1.5 text-xs focus:border-[#0f62fe] focus:outline-hidden"
            />
          </div>

          <div class="pt-3 border-t border-[#e0e0e0] flex gap-2">
            <button
              type="button"
              onclick={onClose}
              disabled={isLoading}
              class="flex-1 py-2.5 bg-[#f4f4f4] text-[#525252] text-xs border border-[#e0e0e0] cursor-pointer"
            >
              Batal
            </button>
            <button
              type="button"
              onclick={handleEndSession}
              disabled={isLoading}
              class="flex-2 py-2.5 bg-[#da1e28] hover:bg-[#ba1b23] text-white text-xs font-semibold flex items-center justify-center gap-1.5 cursor-pointer shadow-xs disabled:opacity-50"
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
