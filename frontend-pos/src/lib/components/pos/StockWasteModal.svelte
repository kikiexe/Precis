<script lang="ts">
  import {
    X,
    AlertTriangle,
    Trash2,
    Lock,
  } from 'lucide-svelte';
  import type { CashierUser, Product, StockWaste, StockWasteReason } from '../../types/pos';
  import { formatCurrency } from '../../services/printer-service';
  import { posService } from '../../services/pos-service';

  interface Props {
    isOpen: boolean;
    products?: Product[];
    cashiers?: CashierUser[];
    activeCashier?: CashierUser | null;
    onRecorded?: (waste: StockWaste) => void;
    onClose: () => void;
  }

  let {
    isOpen = false,
    products = [],
    cashiers = [],
    activeCashier = null,
    onRecorded,
    onClose,
  }: Props = $props();

  let selectedProductId = $state<string>('');
  let itemName = $state<string>('');
  let quantity = $state<number>(1);
  let unit = $state<string>('Pcs');
  let costPerUnit = $state<number>(0);
  let reason = $state<StockWasteReason>('SPOILED');
  let notes = $state<string>('');
  let cashierPin = $state<string>('');
  let isSubmitting = $state<boolean>(false);
  let errorMessage = $state<string | null>(null);

  // Daftar opsi alasan pencatatan stock waste
  const reasonOptions: { value: StockWasteReason; label: string; desc: string }[] = [
    { value: 'SPOILED', label: 'Basi / Rusak (Spoiled)', desc: 'Kualitas rasa/tekstur menurun' },
    { value: 'EXPIRED', label: 'Kedaluwarsa (Expired)', desc: 'Melewati tanggal masa simpan' },
    { value: 'ACCIDENT_SPILL', label: 'Tumpah / Pecah (Spill)', desc: 'Insiden jatuh saat operasional' },
    { value: 'BARISTA_MISTAKE', label: 'Salah Racik (Mistake)', desc: 'Salah takaran atau pesanan keliru' },
    { value: 'QC_REJECT', label: 'Gagal Standar Mutu (QC)', desc: 'Bahan baku tidak lolos kurasi' },
    { value: 'OTHER', label: 'Lainnya (Other)', desc: 'Alasan insidental lainnya' },
  ];

  // Hitung otomatis total estimasi kerugian
  let totalLoss = $derived(Math.max(0, Math.round((quantity || 0) * (costPerUnit || 0))));

  // Pilih produk dari katalog
  function handleSelectProduct(e: Event) {
    const target = e.target as HTMLSelectElement;
    const prodId = target.value;
    selectedProductId = prodId;

    if (prodId) {
      const prod = products.find((p) => p.id === prodId);
      if (prod) {
        itemName = prod.name;
        // Asumsi perkiraan harga modal (HPP) adalah 50% dari harga jual dasar
        if (costPerUnit === 0) {
          costPerUnit = Math.round(prod.base_price * 0.5);
        }
      }
    }
  }

  function handleResetForm() {
    selectedProductId = '';
    itemName = '';
    quantity = 1;
    unit = 'Pcs';
    costPerUnit = 0;
    reason = 'SPOILED';
    notes = '';
    cashierPin = '';
    errorMessage = null;
    isSubmitting = false;
  }

  function handleCloseModal() {
    handleResetForm();
    onClose();
  }

  async function handleSubmitWaste() {
    errorMessage = null;

    if (!itemName.trim()) {
      errorMessage = 'Nama item atau bahan yang rusak wajib diisi.';
      return;
    }

    if (!quantity || quantity <= 0) {
      errorMessage = 'Jumlah kuantitas harus lebih dari 0.';
      return;
    }

    if (costPerUnit < 0) {
      errorMessage = 'Estimasi harga modal per satuan tidak boleh negatif.';
      return;
    }

    // Validasi otorisasi kasir pencatat
    const effectiveCashier = activeCashier || (cashiers.length > 0 ? cashiers[0] : null);
    if (!effectiveCashier) {
      errorMessage = 'Kasir pencatat belum dipilih.';
      return;
    }

    isSubmitting = true;

    try {
      const createdWaste = await posService.createStockWaste({
        product_id: selectedProductId || null,
        item_name: itemName.trim(),
        quantity: Number(quantity),
        unit: unit.trim() || 'Pcs',
        cost_per_unit: Number(costPerUnit),
        reason,
        notes: notes.trim() || null,
        cashier_user_id: effectiveCashier.id,
        pin: cashierPin,
      });

      onRecorded?.(createdWaste);
      handleCloseModal();
    } catch (err: unknown) {
      errorMessage = err instanceof Error ? err.message : 'Gagal mencatat data stock waste.';
    } finally {
      isSubmitting = false;
    }
  }
</script>

{#if isOpen}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-[#17171c]/40 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="flex max-h-[95vh] w-full max-w-lg flex-col rounded-[22px] border border-[#d9d9dd] bg-white p-6 shadow-none"
    >
      <!-- Header -->
      <div class="mb-4 flex items-center justify-between border-b border-[#d9d9dd] pb-3">
        <div class="flex items-center gap-2.5">
          <div class="flex size-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
            <Trash2 class="size-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-zinc-900">Catat Kerugian (Stock Waste)</h3>
            <p class="text-xs text-zinc-500">Rekam bahan baku/menu yang terbuang atau rusak</p>
          </div>
        </div>
        <button
          type="button"
          onclick={handleCloseModal}
          class="cursor-pointer rounded-lg p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700"
        >
          <X class="size-5" />
        </button>
      </div>

      <!-- Pesan Peringatan / Kesalahan -->
      {#if errorMessage}
        <div class="mb-4 flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-700">
          <AlertTriangle class="size-4 shrink-0" />
          <span>{errorMessage}</span>
        </div>
      {/if}

      <!-- Form Body -->
      <div class="flex-1 space-y-3.5 overflow-y-auto pr-1">
        <!-- Opsi Ambil Dari Menu Katalog -->
        <div class="space-y-1">
          <label for="waste-product-picker" class="block text-xs font-semibold text-zinc-700">
            Pilih dari Menu Katalog (Opsional)
          </label>
          <select
            id="waste-product-picker"
            value={selectedProductId}
            onchange={handleSelectProduct}
            class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
          >
            <option value="">Pilih Menu (atau ketik manual di bawah)</option>
            {#each products as prod (prod.id)}
              <option value={prod.id}>{prod.name} - {formatCurrency(prod.base_price)}</option>
            {/each}
          </select>
        </div>

        <!-- Nama Item / Bahan -->
        <div class="space-y-1">
          <label for="waste-item-name" class="block text-xs font-semibold text-zinc-700">
            Nama Bahan / Menu <span class="text-red-500">*</span>
          </label>
          <input
            id="waste-item-name"
            type="text"
            bind:value={itemName}
            placeholder="Contoh: Fresh Milk Diamond 1L / Espresso Shot"
            class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
          />
        </div>

        <!-- Kuantitas & Satuan -->
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label for="waste-quantity" class="block text-xs font-semibold text-zinc-700">
              Jumlah Rusak <span class="text-red-500">*</span>
            </label>
            <input
              id="waste-quantity"
              type="number"
              min="0.01"
              step="any"
              bind:value={quantity}
              class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 font-mono text-xs text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
            />
          </div>

          <div class="space-y-1">
            <label for="waste-unit" class="block text-xs font-semibold text-zinc-700">
              Satuan Takaran
            </label>
            <input
              id="waste-unit"
              type="text"
              bind:value={unit}
              placeholder="Pcs, Liter, Gram, Shot"
              class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
            />
          </div>
        </div>

        <!-- Estimasi Harga Modal (HPP) Per Unit -->
        <div class="space-y-1">
          <label for="waste-cost" class="block text-xs font-semibold text-zinc-700">
            Estimasi HPP Satuan (Rp) <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <span class="absolute top-1/2 left-3 -translate-y-1/2 font-mono text-xs text-zinc-400">Rp</span>
            <input
              id="waste-cost"
              type="number"
              min="0"
              step="100"
              bind:value={costPerUnit}
              class="w-full rounded-xl border border-zinc-200 bg-zinc-50 py-2 pr-3 pl-9 font-mono text-xs text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
            />
          </div>
          <div class="flex items-center justify-between pt-0.5 text-[11px] text-zinc-500">
            <span>Total Kerugian:</span>
            <span class="font-mono font-bold text-amber-700">{formatCurrency(totalLoss)}</span>
          </div>
        </div>

        <!-- Alasan Kerusakan -->
        <div class="space-y-1">
          <label for="waste-reason" class="block text-xs font-semibold text-zinc-700">
            Alasan Kerusakan / Pembuangan <span class="text-red-500">*</span>
          </label>
          <select
            id="waste-reason"
            bind:value={reason}
            class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
          >
            {#each reasonOptions as opt (opt.value)}
              <option value={opt.value}>{opt.label} - {opt.desc}</option>
            {/each}
          </select>
        </div>

        <!-- Catatan Kronologi -->
        <div class="space-y-1">
          <label for="waste-notes" class="block text-xs font-semibold text-zinc-700">
            Catatan Kronologi (Opsional)
          </label>
          <textarea
            id="waste-notes"
            rows="2"
            bind:value={notes}
            placeholder="Jelaskan detail insiden (misal: botol sirup tersenggol saat jam sibuk)..."
            class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
          ></textarea>
        </div>

        <!-- PIN Kasir -->
        <div class="space-y-1">
          <label for="waste-pin" class="block text-xs font-semibold text-zinc-700">
            PIN Kasir Pencatat
          </label>
          <div class="relative">
            <span class="absolute top-1/2 left-3 -translate-y-1/2 text-zinc-400">
              <Lock class="size-3.5" />
            </span>
            <input
              id="waste-pin"
              type="password"
              maxlength="6"
              bind:value={cashierPin}
              placeholder="Ketik 4 digit PIN kasir..."
              class="w-full rounded-xl border border-zinc-200 bg-zinc-50 py-2 pr-3 pl-9 font-mono text-xs tracking-widest text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
            />
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="mt-4 flex items-center gap-2.5 border-t border-[#d9d9dd] pt-3">
        <button
          type="button"
          onclick={handleCloseModal}
          class="flex-1 cursor-pointer rounded-xl border border-zinc-200 bg-zinc-100 py-2.5 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-200"
        >
          Batal
        </button>

        <button
          type="button"
          disabled={isSubmitting}
          onclick={handleSubmitWaste}
          class="flex-1 cursor-pointer rounded-xl bg-zinc-900 py-2.5 text-xs font-bold text-white shadow-2xs transition-colors hover:bg-black disabled:opacity-50"
        >
          {#if isSubmitting}
            <span>Menyimpan...</span>
          {:else}
            <span>Catat Kerugian</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
