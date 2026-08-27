<script lang="ts">
  import { DollarSign, X, Check, AlertCircle, ChevronDown } from 'lucide-svelte';
  import type { PosSession, CloseSessionResponse, CashierUser } from '../../types/pos';
  import { formatCurrency } from '../../services/printer-service';
  import { posService } from '../../services/pos-service';

  interface Props {
    isOpen: boolean;
    activeSession: PosSession | null;
    cashierUserId?: string;
    cashiers?: CashierUser[];
    activeCashier?: CashierUser | null;
    onClose: () => void;
    onSessionOpened: (session: PosSession) => void;
    onSessionClosed: (closedData: CloseSessionResponse) => void;
  }

  let {
    isOpen = false,
    activeSession,
    cashierUserId,
    cashiers = [],
    activeCashier = null,
    onClose,
    onSessionOpened,
    onSessionClosed,
  }: Props = $props();

  let selectedCashierId = $state<string>('');
  let openingCashInput = $state(200000);
  let closingCashInput = $state(0);
  let sessionNotes = $state('');
  let isLoading = $state(false);
  let errorMessage = $state<string | null>(null);

  $effect(() => {
    if (isOpen) {
      errorMessage = null;
      selectedCashierId =
        cashierUserId && cashierUserId !== 'team-outlet' && cashierUserId !== 'usr-active-01'
          ? cashierUserId
          : activeCashier?.id && activeCashier.id !== 'team-outlet'
          ? activeCashier.id
          : cashiers[0]?.id || '';

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
    if (!selectedCashierId) {
      errorMessage = 'Pilih operator kasir yang bertugas terlebih dahulu.';
      return;
    }

    isLoading = true;
    errorMessage = null;

    try {
      const session = await posService.openSession(selectedCashierId, openingCashInput, sessionNotes);
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
        selectedCashierId || cashierUserId,
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

          {#if cashiers.length > 0}
            <div>
              <label for="cashier-select" class="block text-xs font-medium text-[#616161] mb-1.5">
                Operator Kasir Bertugas:
              </label>
              <div class="relative">
                <select
                  id="cashier-select"
                  bind:value={selectedCashierId}
                  class="w-full bg-[#f8f8fa] hover:bg-white border border-[#d9d9dd] rounded-xl px-3.5 pr-9 py-2.5 text-xs font-semibold text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all cursor-pointer appearance-none"
                >
                  {#each cashiers as c}
                    <option value={c.id}>{c.name} ({c.role})</option>
                  {/each}
                </select>
                <ChevronDown class="w-4 h-4 text-[#8e8e93] absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
              </div>
            </div>
          {/if}

          <div>
            <label for="opening-cash" class="block text-xs font-medium text-[#616161] mb-1.5">
              Modal Awal Laci Kasir (Rp):
            </label>
            <input
              id="opening-cash"
              type="number"
              min="0"
              step="10000"
              bind:value={openingCashInput}
              class="w-full bg-[#eeece7]/40 border border-[#d9d9dd] rounded-xl px-4 py-2.5 font-mono text-sm font-medium text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
            />
          </div>

          <!-- tombol nominal cepat -->
          <div class="flex flex-wrap gap-2">
            {#each [100000, 200000, 300000, 500000] as preset}
              <button
                type="button"
                onclick={() => (openingCashInput = preset)}
                class="px-3 py-1 bg-[#eeece7]/60 hover:bg-[#eeece7] border border-[#d9d9dd] rounded-full text-xs font-mono text-[#212121] cursor-pointer transition-all"
              >
                Rp {preset.toLocaleString('id-ID')}
              </button>
            {/each}
          </div>

          <div>
            <label for="opening-notes" class="block text-xs font-medium text-[#616161] mb-1.5">
              Catatan Pembukaan (Opsional):
            </label>
            <input
              id="opening-notes"
              type="text"
              bind:value={sessionNotes}
              placeholder="Contoh: Shift Pagi Barista Siti"
              class="w-full bg-[#eeece7]/40 border border-[#d9d9dd] rounded-xl px-3.5 py-2 text-xs text-[#212121] placeholder-[#93939f] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>

          <div class="flex gap-3 pt-2">
            <button
              type="button"
              onclick={onClose}
              disabled={isLoading}
              class="flex-1 py-2.5 border border-[#d9d9dd] text-[#616161] hover:bg-[#eeece7]/40 rounded-full text-xs font-medium transition-all cursor-pointer"
            >
              Batal
            </button>
            <button
              type="button"
              onclick={handleStartSession}
              disabled={isLoading || openingCashInput < 0}
              class="flex-1 py-2.5 bg-[#17171c] hover:bg-[#000000] text-white rounded-full text-xs font-medium transition-all cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50"
            >
              {#if isLoading}
                <span>Membuka...</span>
              {:else}
                <Check class="w-3.5 h-3.5" />
                <span>Buka Sesi ({formatCurrency(openingCashInput)})</span>
              {/if}
            </button>
          </div>
        </div>
      {:else}
        <!-- tampilan tutup kasir -->
        <div class="space-y-4">
          <div class="bg-[#eeece7]/40 border border-[#d9d9dd] rounded-xl p-3.5 space-y-2 text-xs">
            <div class="flex justify-between">
              <span class="text-[#616161]">Modal Awal:</span>
              <span class="font-mono font-medium text-[#212121]">{formatCurrency(activeSession.opening_cash)}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-[#616161]">Penjualan Tunai (Sistem):</span>
              <span class="font-mono font-medium text-[#003c33]">+{formatCurrency(activeSession.total_cash_sales)}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-[#616161]">Penjualan Non-Tunai (QRIS/Transfer):</span>
              <span class="font-mono font-medium text-[#1863dc]">+{formatCurrency(activeSession.total_qris_sales + activeSession.total_transfer_sales)}</span>
            </div>
            <div class="pt-2 border-t border-[#d9d9dd] flex justify-between font-medium">
              <span class="text-[#212121]">Total Kas Fisik Seharusnya:</span>
              <span class="font-mono text-[#212121]">{formatCurrency(expectedCash)}</span>
            </div>
          </div>

          <div>
            <label for="closing-cash" class="block text-xs font-medium text-[#616161] mb-1.5">
              Hitung Uang Kas Fisik Aktual di Laci (Rp):
            </label>
            <input
              id="closing-cash"
              type="number"
              min="0"
              step="1000"
              bind:value={closingCashInput}
              class="w-full bg-[#eeece7]/40 border border-[#d9d9dd] rounded-xl px-4 py-2.5 font-mono text-sm font-medium text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
            />
          </div>

          <!-- indikator selisih laci -->
          <div class={`p-3 rounded-xl border text-xs flex items-center justify-between font-mono ${
            discrepancy === 0
              ? 'bg-[#edfce9]/60 border-[#edfce9] text-[#003c33]'
              : discrepancy > 0
              ? 'bg-[#f1f5ff] border-[#d4e2ff] text-[#1863dc]'
              : 'bg-[#ffad9b]/15 border-[#ffad9b] text-[#b30000]'
          }`}>
            <span>Status Selisih Kas:</span>
            <span class="font-medium">
              {discrepancy === 0 ? 'SEIMBANG (Rp 0)' : discrepancy > 0 ? `LEBIH (+${formatCurrency(discrepancy)})` : `KURANG (${formatCurrency(discrepancy)})`}
            </span>
          </div>

          <div>
            <label for="closing-notes" class="block text-xs font-medium text-[#616161] mb-1.5">
              Catatan Rekonsiliasi &amp; Penutupan (Opsional):
            </label>
            <input
              id="closing-notes"
              type="text"
              bind:value={sessionNotes}
              placeholder="Keterangan jika terdapat selisih uang kas..."
              class="w-full bg-[#eeece7]/40 border border-[#d9d9dd] rounded-xl px-3.5 py-2 text-xs text-[#212121] placeholder-[#93939f] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>

          <div class="flex gap-3 pt-2">
            <button
              type="button"
              onclick={onClose}
              disabled={isLoading}
              class="flex-1 py-2.5 border border-[#d9d9dd] text-[#616161] hover:bg-[#eeece7]/40 rounded-full text-xs font-medium transition-all cursor-pointer"
            >
              Batal
            </button>
            <button
              type="button"
              onclick={handleEndSession}
              disabled={isLoading}
              class="flex-1 py-2.5 bg-[#b30000] hover:bg-[#800000] text-white rounded-full text-xs font-medium transition-all cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50"
            >
              {#if isLoading}
                <span>Menutup...</span>
              {:else}
                <Check class="w-3.5 h-3.5" />
                <span>Tutup Shift &amp; Settlement</span>
              {/if}
            </button>
          </div>
        </div>
      {/if}
    </div>
  </div>
{/if}
