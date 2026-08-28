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
        closingCashInput =
          (activeSession.opening_cash || 0) + (activeSession.total_cash_sales || 0);
      }
    }
  });

  let expectedCash = $derived(
    activeSession ? (activeSession.opening_cash || 0) + (activeSession.total_cash_sales || 0) : 0
  );

  let discrepancy = $derived(activeSession ? closingCashInput - expectedCash : 0);

  async function handleStartSession() {
    if (!selectedCashierId) {
      errorMessage = 'Pilih operator kasir yang bertugas terlebih dahulu.';
      return;
    }

    isLoading = true;
    errorMessage = null;

    try {
      const session = await posService.openSession(
        selectedCashierId,
        openingCashInput,
        sessionNotes
      );
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
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-[#17171c]/40 p-4 font-sans backdrop-blur-xs select-none"
  >
    <div
      class="animate-in fade-in zoom-in-95 w-full max-w-md rounded-[22px] border border-[#d9d9dd] bg-white p-6 shadow-none"
    >
      <!-- header modal -->
      <div class="mb-4 flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <div class="flex items-center gap-2">
          <DollarSign class="h-4 w-4 text-[#1863dc]" />
          <h2 class="text-sm font-medium text-[#212121]">
            {activeSession && activeSession.status === 'OPEN'
              ? 'Tutup Sesi Kasir & Rekonsiliasi'
              : 'Buka Sesi Kasir Baru'}
          </h2>
        </div>
        <button
          type="button"
          onclick={onClose}
          disabled={isLoading}
          class="cursor-pointer p-1 text-[#93939f] hover:text-[#212121]"
        >
          <X class="h-4 w-4" />
        </button>
      </div>

      {#if errorMessage}
        <div
          class="mb-4 flex items-start gap-2 rounded-xl border border-[#ffad9b] bg-[#ffad9b]/15 p-3 text-xs text-[#b30000]"
        >
          <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      {#if !activeSession || activeSession.status === 'CLOSED'}
        <!-- tampilan buka kasir -->
        <div class="space-y-4">
          <p class="text-xs text-[#616161]">
            Masukkan modal awal kasir (uang kembalian dalam laci kasir) sebelum memulai transaksi
            shift baru.
          </p>

          {#if cashiers.length > 0}
            <div>
              <label for="cashier-select" class="mb-1.5 block text-xs font-medium text-[#616161]">
                Operator Kasir Bertugas:
              </label>
              <div class="relative">
                <select
                  id="cashier-select"
                  bind:value={selectedCashierId}
                  class="w-full cursor-pointer appearance-none rounded-xl border border-[#d9d9dd] bg-[#f8f8fa] px-3.5 py-2.5 pr-9 text-xs font-semibold text-[#17171c] transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
                >
                  {#each cashiers as c}
                    <option value={c.id}>{c.name} ({c.role})</option>
                  {/each}
                </select>
                <ChevronDown
                  class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-[#8e8e93]"
                />
              </div>
            </div>
          {/if}

          <div>
            <label for="opening-cash" class="mb-1.5 block text-xs font-medium text-[#616161]">
              Modal Awal Laci Kasir (Rp):
            </label>
            <input
              id="opening-cash"
              type="number"
              min="0"
              step="10000"
              bind:value={openingCashInput}
              class="w-full rounded-xl border border-[#d9d9dd] bg-[#eeece7]/40 px-4 py-2.5 font-mono text-sm font-medium text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
            />
          </div>

          <!-- tombol nominal cepat -->
          <div class="flex flex-wrap gap-2">
            {#each [100000, 200000, 300000, 500000] as preset}
              <button
                type="button"
                onclick={() => (openingCashInput = preset)}
                class="cursor-pointer rounded-full border border-[#d9d9dd] bg-[#eeece7]/60 px-3 py-1 font-mono text-xs text-[#212121] transition-all hover:bg-[#eeece7]"
              >
                Rp {preset.toLocaleString('id-ID')}
              </button>
            {/each}
          </div>

          <div>
            <label for="opening-notes" class="mb-1.5 block text-xs font-medium text-[#616161]">
              Catatan Pembukaan (Opsional):
            </label>
            <input
              id="opening-notes"
              type="text"
              bind:value={sessionNotes}
              placeholder="Contoh: Shift Pagi Barista Siti"
              class="w-full rounded-xl border border-[#d9d9dd] bg-[#eeece7]/40 px-3.5 py-2 text-xs text-[#212121] placeholder-[#93939f] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>

          <div class="flex gap-3 pt-2">
            <button
              type="button"
              onclick={onClose}
              disabled={isLoading}
              class="flex-1 cursor-pointer rounded-full border border-[#d9d9dd] py-2.5 text-xs font-medium text-[#616161] transition-all hover:bg-[#eeece7]/40"
            >
              Batal
            </button>
            <button
              type="button"
              onclick={handleStartSession}
              disabled={isLoading || openingCashInput < 0}
              class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-full bg-[#17171c] py-2.5 text-xs font-medium text-white transition-all hover:bg-[#000000] disabled:opacity-50"
            >
              {#if isLoading}
                <span>Membuka...</span>
              {:else}
                <Check class="h-3.5 w-3.5" />
                <span>Buka Sesi ({formatCurrency(openingCashInput)})</span>
              {/if}
            </button>
          </div>
        </div>
      {:else}
        <!-- tampilan tutup kasir -->
        <div class="space-y-4">
          <div class="space-y-2 rounded-xl border border-[#d9d9dd] bg-[#eeece7]/40 p-3.5 text-xs">
            <div class="flex justify-between">
              <span class="text-[#616161]">Modal Awal:</span>
              <span class="font-mono font-medium text-[#212121]"
                >{formatCurrency(activeSession.opening_cash)}</span
              >
            </div>
            <div class="flex justify-between">
              <span class="text-[#616161]">Penjualan Tunai (Sistem):</span>
              <span class="font-mono font-medium text-[#003c33]"
                >+{formatCurrency(activeSession.total_cash_sales)}</span
              >
            </div>
            <div class="flex justify-between">
              <span class="text-[#616161]">Penjualan Non-Tunai (QRIS / EDC):</span>
              <span class="font-mono font-medium text-[#1863dc]"
                >+{formatCurrency(
                  activeSession.total_qris_sales + activeSession.total_transfer_sales
                )}</span
              >
            </div>
            <div class="flex justify-between border-t border-[#d9d9dd] pt-2 font-medium">
              <span class="text-[#212121]">Total Kas Fisik Seharusnya:</span>
              <span class="font-mono text-[#212121]">{formatCurrency(expectedCash)}</span>
            </div>
          </div>

          <div>
            <label for="closing-cash" class="mb-1.5 block text-xs font-medium text-[#616161]">
              Hitung Uang Kas Fisik Aktual di Laci (Rp):
            </label>
            <input
              id="closing-cash"
              type="number"
              min="0"
              step="1000"
              bind:value={closingCashInput}
              class="w-full rounded-xl border border-[#d9d9dd] bg-[#eeece7]/40 px-4 py-2.5 font-mono text-sm font-medium text-[#212121] focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 focus:outline-hidden"
            />
          </div>

          <!-- indikator selisih laci -->
          <div
            class={`flex items-center justify-between rounded-xl border p-3 font-mono text-xs ${
              discrepancy === 0
                ? 'border-[#edfce9] bg-[#edfce9]/60 text-[#003c33]'
                : discrepancy > 0
                  ? 'border-[#d4e2ff] bg-[#f1f5ff] text-[#1863dc]'
                  : 'border-[#ffad9b] bg-[#ffad9b]/15 text-[#b30000]'
            }`}
          >
            <span>Status Selisih Kas:</span>
            <span class="font-medium">
              {discrepancy === 0
                ? 'SEIMBANG (Rp 0)'
                : discrepancy > 0
                  ? `LEBIH (+${formatCurrency(discrepancy)})`
                  : `KURANG (${formatCurrency(discrepancy)})`}
            </span>
          </div>

          <div>
            <label for="closing-notes" class="mb-1.5 block text-xs font-medium text-[#616161]">
              Catatan Rekonsiliasi &amp; Penutupan (Opsional):
            </label>
            <input
              id="closing-notes"
              type="text"
              bind:value={sessionNotes}
              placeholder="Keterangan jika terdapat selisih uang kas..."
              class="w-full rounded-xl border border-[#d9d9dd] bg-[#eeece7]/40 px-3.5 py-2 text-xs text-[#212121] placeholder-[#93939f] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>

          <div class="flex gap-3 pt-2">
            <button
              type="button"
              onclick={onClose}
              disabled={isLoading}
              class="flex-1 cursor-pointer rounded-full border border-[#d9d9dd] py-2.5 text-xs font-medium text-[#616161] transition-all hover:bg-[#eeece7]/40"
            >
              Batal
            </button>
            <button
              type="button"
              onclick={handleEndSession}
              disabled={isLoading}
              class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-full bg-[#b30000] py-2.5 text-xs font-medium text-white transition-all hover:bg-[#800000] disabled:opacity-50"
            >
              {#if isLoading}
                <span>Menutup...</span>
              {:else}
                <Check class="h-3.5 w-3.5" />
                <span>Tutup Shift &amp; Settlement</span>
              {/if}
            </button>
          </div>
        </div>
      {/if}
    </div>
  </div>
{/if}
