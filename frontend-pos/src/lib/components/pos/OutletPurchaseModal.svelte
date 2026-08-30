<script lang="ts">
  import { X, Check, AlertCircle, ShoppingBag, DollarSign, Receipt, Lock } from 'lucide-svelte';
  import type { CashierUser, PosSession, PurchaseCategory, FundingSource, OutletPurchase } from '../../types/pos';
  import { posService } from '../../services/pos-service';
  import { formatCurrency } from '../../services/printer-service';

  interface Props {
    isOpen: boolean;
    activeSession: PosSession | null;
    activeCashier: CashierUser | null;
    cashiers: CashierUser[];
    onClose: () => void;
    onPurchaseCreated: (purchase: OutletPurchase) => void;
  }

  let {
    isOpen = false,
    activeSession = null,
    activeCashier = null,
    cashiers = [],
    onClose,
    onPurchaseCreated,
  }: Props = $props();

  let itemName = $state('');
  let category = $state<PurchaseCategory>('OPERASIONAL_TOKO');
  let fundingSource = $state<FundingSource>('CASH_DRAWER');
  let quantity = $state<number>(1);
  let unit = $state('Pcs');
  let unitPrice = $state<number>(0);
  let totalPriceOverride = $state<number | null>(null);
  let notes = $state('');
  let selectedCashierId = $state('');
  let cashierPin = $state('');

  let errorMessage = $state<string | null>(null);
  let isSubmitting = $state(false);

  const categoryOptions: { value: PurchaseCategory; label: string; desc: string }[] = [
    { value: 'BAHAN_BAKU_DARURAT', label: 'Bahan Baku Darurat', desc: 'Es batu, susu, kopi, gas' },
    { value: 'OPERASIONAL_TOKO', label: 'Operasional Toko', desc: 'Plastik cup, sedotan, lakban' },
    { value: 'KEBERSIHAN', label: 'Kebersihan', desc: 'Sabun cuci, deterjen, plastik sampah' },
    { value: 'UTILITAS', label: 'Utilitas', desc: 'Token listrik darurat, air galon' },
    { value: 'LAINNYA', label: 'Lainnya', desc: 'Pengeluaran darurat lainnya' },
  ];

  const commonUnits = ['Pcs', 'Pack', 'Kg', 'Liter', 'Galon', 'Tabung', 'Box', 'Ikat'];

  $effect(() => {
    if (isOpen) {
      itemName = '';
      category = 'OPERASIONAL_TOKO';
      fundingSource = 'CASH_DRAWER';
      quantity = 1;
      unit = 'Pcs';
      unitPrice = 0;
      totalPriceOverride = null;
      notes = '';
      selectedCashierId = activeCashier?.id || (cashiers.length > 0 ? cashiers[0].id : '');
      cashierPin = '';
      errorMessage = null;
      isSubmitting = false;
    }
  });

  let calculatedTotal = $derived(
    totalPriceOverride !== null ? totalPriceOverride : Math.round(quantity * unitPrice)
  );

  async function handleSubmit() {
    if (!itemName.trim()) {
      errorMessage = 'Nama item pengeluaran belanja wajib diisi.';
      return;
    }

    if (quantity <= 0) {
      errorMessage = 'Jumlah kuantitas belanja harus lebih dari 0.';
      return;
    }

    if (calculatedTotal <= 0) {
      errorMessage = 'Total nominal belanja harus lebih besar dari Rp 0.';
      return;
    }

    if (!selectedCashierId) {
      errorMessage = 'Pilih kasir yang bertanggung jawab atas pengeluaran ini.';
      return;
    }

    try {
      isSubmitting = true;
      errorMessage = null;

      const purchase = await posService.createOutletPurchase({
        item_name: itemName.trim(),
        unit: unit.trim() || 'Pcs',
        quantity,
        unit_price: unitPrice,
        total_price: calculatedTotal,
        category,
        funding_source: fundingSource,
        pos_session_id: activeSession?.id || null,
        cashier_user_id: selectedCashierId,
        pin: cashierPin.trim() || undefined,
        notes: notes.trim() || undefined,
      });

      onPurchaseCreated(purchase);
      onClose();
    } catch (err: unknown) {
      errorMessage = err instanceof Error ? err.message : 'Gagal mencatat pengeluaran belanja.';
    } finally {
      isSubmitting = false;
    }
  }
</script>

{#if isOpen}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-[#17171c]/40 p-4 font-sans backdrop-blur-xs select-none"
  >
    <div
      class="animate-in fade-in zoom-in-95 flex max-h-[92vh] w-full max-w-lg flex-col rounded-[22px] border border-[#d9d9dd] bg-white shadow-2xl"
    >
      <!-- Header Modal -->
      <div class="flex items-center justify-between border-b border-[#d9d9dd] p-5 pb-4">
        <div class="flex items-center gap-2.5">
          <div class="flex size-9 items-center justify-center rounded-xl bg-zinc-900 text-white">
            <ShoppingBag class="size-5" />
          </div>
          <div>
            <h2 class="text-sm font-bold text-[#212121]">Catat Belanja Outlet (Petty Cash)</h2>
            <p class="font-mono text-xs text-zinc-500">
              Pengeluaran operasional darurat kasir
            </p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="cursor-pointer p-1 text-[#93939f] hover:text-[#212121]"
        >
          <X class="size-5" />
        </button>
      </div>

      {#if errorMessage}
        <div
          class="mx-5 mt-4 flex items-center gap-2 rounded-xl border border-[#ffad9b] bg-[#ffad9b]/15 p-3 text-xs text-[#b30000]"
        >
          <AlertCircle class="size-4 shrink-0" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <!-- Form Inputs Container -->
      <div class="flex-1 space-y-4 overflow-y-auto p-5">
        <!-- Nama Barang / Pengeluaran -->
        <div class="space-y-1.5">
          <label for="item-name" class="block text-xs font-bold text-zinc-900">
            Nama Barang / Keperluan: <span class="text-red-500">*</span>
          </label>
          <input
            id="item-name"
            type="text"
            bind:value={itemName}
            placeholder="Contoh: Es Batu Kristal 10kg, Galon Aqua, Gas LPG..."
            class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3.5 py-2.5 text-xs text-zinc-900 placeholder-zinc-400 focus:border-zinc-900 focus:outline-hidden"
          />
        </div>

        <!-- Sumber Dana (Kas Laci vs Reimburse) -->
        <div class="space-y-1.5">
          <span class="block text-xs font-bold text-zinc-900">
            Sumber Dana Pembayaran: <span class="text-red-500">*</span>
          </span>
          <div class="grid grid-cols-2 gap-2">
            <button
              type="button"
              onclick={() => (fundingSource = 'CASH_DRAWER')}
              class={`flex cursor-pointer flex-col rounded-xl border p-3 text-left transition-all ${
                fundingSource === 'CASH_DRAWER'
                  ? 'border-zinc-900 bg-zinc-900 text-white shadow-xs'
                  : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:bg-zinc-100'
              }`}
            >
              <div class="flex items-center gap-1.5">
                <DollarSign class="size-4" />
                <span class="text-xs font-bold">Kas Laci Kasir</span>
              </div>
              <span
                class={`mt-1 text-[10px] ${
                  fundingSource === 'CASH_DRAWER' ? 'text-zinc-300' : 'text-zinc-500'
                }`}
              >
                Memotong kas fisik saat tutup shift
              </span>
            </button>

            <button
              type="button"
              onclick={() => (fundingSource = 'EXTERNAL_REIMBURSE')}
              class={`flex cursor-pointer flex-col rounded-xl border p-3 text-left transition-all ${
                fundingSource === 'EXTERNAL_REIMBURSE'
                  ? 'border-zinc-900 bg-zinc-900 text-white shadow-xs'
                  : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:bg-zinc-100'
              }`}
            >
              <div class="flex items-center gap-1.5">
                <Receipt class="size-4" />
                <span class="text-xs font-bold">Reimburse Staf</span>
              </div>
              <span
                class={`mt-1 text-[10px] ${
                  fundingSource === 'EXTERNAL_REIMBURSE' ? 'text-zinc-300' : 'text-zinc-500'
                }`}
              >
                Dana pribadi kasir / klaim nanti
              </span>
            </button>
          </div>
        </div>

        <!-- Kategori Pengeluaran -->
        <div class="space-y-1.5">
          <label for="purchase-category" class="block text-xs font-bold text-zinc-900">
            Kategori Belanja:
          </label>
          <select
            id="purchase-category"
            bind:value={category}
            class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3.5 py-2.5 text-xs text-zinc-900 focus:border-zinc-900 focus:outline-hidden"
          >
            {#each categoryOptions as cat}
              <option value={cat.value}>{cat.label} ({cat.desc})</option>
            {/each}
          </select>
        </div>

        <!-- Kuantitas, Satuan, dan Harga Per Satuan -->
        <div class="grid grid-cols-3 gap-2">
          <div class="space-y-1.5">
            <label for="purchase-qty" class="block text-xs font-bold text-zinc-900">
              Jumlah:
            </label>
            <input
              id="purchase-qty"
              type="number"
              min="0.1"
              step="any"
              bind:value={quantity}
              class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3 py-2.5 font-mono text-xs text-zinc-900 focus:border-zinc-900 focus:outline-hidden"
            />
          </div>

          <div class="space-y-1.5">
            <label for="purchase-unit" class="block text-xs font-bold text-zinc-900">
              Satuan:
            </label>
            <input
              id="purchase-unit"
              type="text"
              bind:value={unit}
              list="unit-suggestions"
              placeholder="Pcs"
              class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3 py-2.5 text-xs text-zinc-900 focus:border-zinc-900 focus:outline-hidden"
            />
            <datalist id="unit-suggestions">
              {#each commonUnits as u}
                <option value={u}></option>
              {/each}
            </datalist>
          </div>

          <div class="space-y-1.5">
            <label for="purchase-price" class="block text-xs font-bold text-zinc-900">
              Harga Satuan:
            </label>
            <input
              id="purchase-price"
              type="number"
              min="0"
              step="500"
              bind:value={unitPrice}
              class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3 py-2.5 font-mono text-xs text-zinc-900 focus:border-zinc-900 focus:outline-hidden"
            />
          </div>
        </div>

        <!-- Total Nominal Pengeluaran -->
        <div class="flex items-center justify-between rounded-xl border border-zinc-200 bg-zinc-100/70 p-3">
          <span class="text-xs font-bold text-zinc-700">Total Pengeluaran:</span>
          <span class="font-mono text-base font-bold text-zinc-900">
            {formatCurrency(calculatedTotal)}
          </span>
        </div>

        <!-- Kasir Penanggung Jawab & PIN Otorisasi -->
        <div class="grid grid-cols-2 gap-2 pt-1">
          <div class="space-y-1.5">
            <label for="cashier-select" class="block text-xs font-bold text-zinc-900">
              Kasir Bertugas:
            </label>
            <select
              id="cashier-select"
              bind:value={selectedCashierId}
              class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3 py-2.5 text-xs text-zinc-900 focus:border-zinc-900 focus:outline-hidden"
            >
              {#each cashiers as c}
                <option value={c.id}>{c.name} ({c.role})</option>
              {/each}
            </select>
          </div>

          <div class="space-y-1.5">
            <label for="cashier-pin" class="block text-xs font-bold text-zinc-900">
              PIN Kasir (Opsional):
            </label>
            <div class="relative">
              <Lock class="absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-zinc-400" />
              <input
                id="cashier-pin"
                type="password"
                maxlength="6"
                bind:value={cashierPin}
                placeholder="4-6 digit..."
                class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 py-2.5 pr-3 pl-8.5 font-mono text-xs text-zinc-900 focus:border-zinc-900 focus:outline-hidden"
              />
            </div>
          </div>
        </div>

        <!-- Catatan Pembelian / Lokasi -->
        <div class="space-y-1.5">
          <label for="purchase-notes" class="block text-xs font-bold text-zinc-900">
            Catatan / Lokasi Pembelian (Opsional):
          </label>
          <input
            id="purchase-notes"
            type="text"
            bind:value={notes}
            placeholder="Contoh: Beli di warung sebelah, toko plastik Jaya..."
            class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3.5 py-2.5 text-xs text-zinc-900 placeholder-zinc-400 focus:border-zinc-900 focus:outline-hidden"
          />
        </div>
      </div>

      <!-- Footer Modal -->
      <div class="flex items-center justify-end gap-2 border-t border-zinc-200 bg-zinc-50/90 p-4">
        <button
          type="button"
          onclick={onClose}
          disabled={isSubmitting}
          class="cursor-pointer rounded-full border border-zinc-300 bg-white px-5 py-2 text-xs font-semibold text-zinc-700 transition-all hover:bg-zinc-100 disabled:opacity-50"
        >
          Batal
        </button>

        <button
          type="button"
          onclick={handleSubmit}
          disabled={isSubmitting}
          class="flex cursor-pointer items-center gap-1.5 rounded-full bg-zinc-900 px-6 py-2 text-xs font-bold text-white shadow-sm transition-all hover:bg-black disabled:opacity-50"
        >
          {#if isSubmitting}
            <span>Menyimpan...</span>
          {:else}
            <Check class="size-4" />
            <span>Simpan Belanja ({formatCurrency(calculatedTotal)})</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
