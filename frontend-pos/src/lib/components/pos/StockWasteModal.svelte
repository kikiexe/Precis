<script lang="ts">
  import type { CashierUser, Product, StockWaste, StockWasteReason } from '../../types/pos';
  import { posService } from '../../services/pos-service';

  export let isOpen = false;
  export let activeCashier: CashierUser | null = null;
  export let cashiers: CashierUser[] = [];
  export let products: Product[] = [];
  export let onRecorded: (waste: StockWaste) => void = () => {};
  export let onClose: () => void = () => {};

  let selectedProductId = '';
  let itemName = '';
  let quantity = 1;
  let unit = 'Pcs';
  let costPerUnit = 0;
  let reason: StockWasteReason = 'SPOILED';
  let notes = '';
  let cashierId = activeCashier?.id || (cashiers[0]?.id ?? '');
  let pin = '';

  let isSubmitting = false;
  let errorMessage = '';

  const reasons: Array<{ value: StockWasteReason; label: string; desc: string }> = [
    { value: 'SPOILED', label: 'Basi / Rusak Kulkas', desc: 'Bahan basi karena kulkas mati / suhu salah' },
    { value: 'EXPIRED', label: 'Kedaluwarsa (Expired)', desc: 'Bahan melewati batas tanggal konsumsi' },
    { value: 'ACCIDENT_SPILL', label: 'Tumpah / Pecah', desc: 'Insiden tersenggol atau wadah pecah' },
    { value: 'BARISTA_MISTAKE', label: 'Salah Buat / Salah Resep', desc: 'Kesalahan takaran atau salah racik cup' },
    { value: 'QC_REJECT', label: 'Reject QC / Cacat Mutu', desc: 'Rasa / aroma tidak lolos uji standar' },
    { value: 'OTHER', label: 'Lainnya', desc: 'Penyebab kerusakan operasional lainnya' }
  ];

  $: totalLossCost = Math.round(Number(quantity || 0) * Number(costPerUnit || 0));

  function handleProductSelect(e: Event) {
    const target = e.target as HTMLSelectElement;
    const prodId = target.value;
    selectedProductId = prodId;

    if (prodId) {
      const found = products.find((p) => p.id === prodId);
      if (found) {
        itemName = found.name;
        // perkiraan harga modal standar (jika ada perkiraan 60% harga jual atau base_price)
        if (costPerUnit === 0) {
          costPerUnit = Math.round(found.base_price * 0.5);
        }
      }
    }
  }

  function formatRupiah(amount: number): string {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(amount);
  }

  async function handleSubmit() {
    errorMessage = '';

    if (!itemName.trim()) {
      errorMessage = 'Nama item atau bahan baku yang rusak wajib diisi.';
      return;
    }

    if (!quantity || quantity <= 0) {
      errorMessage = 'Jumlah bahan terbuang harus lebih dari 0.';
      return;
    }

    if (costPerUnit < 0) {
      errorMessage = 'Biaya modal per unit tidak boleh bernilai negatif.';
      return;
    }

    const reportingCashier = cashiers.find((c) => c.id === cashierId) || activeCashier;
    if (!reportingCashier) {
      errorMessage = 'Pilih kasir yang melaporkan insiden waste.';
      return;
    }

    isSubmitting = true;

    try {
      // catat riwayat waste ke server dan sinkronkan ke local indexeddb
      const result = await posService.createStockWaste({
        product_id: selectedProductId || null,
        item_name: itemName.trim(),
        quantity: Number(quantity),
        unit: unit.trim() || 'Pcs',
        cost_per_unit: Number(costPerUnit),
        total_loss_cost: totalLossCost,
        reason,
        notes: notes.trim() || undefined,
        cashier_user_id: reportingCashier.id,
        pin: pin || undefined
      });

      onRecorded(result);
      handleClose();
    } catch (err: unknown) {
      errorMessage = err instanceof Error ? err.message : 'Gagal mencatat stock waste.';
    } finally {
      isSubmitting = false;
    }
  }

  function handleClose() {
    selectedProductId = '';
    itemName = '';
    quantity = 1;
    unit = 'Pcs';
    costPerUnit = 0;
    reason = 'SPOILED';
    notes = '';
    pin = '';
    errorMessage = '';
    onClose();
  }
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
    <div class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl">
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-slate-800 bg-slate-900/60 px-6 py-4">
        <div class="flex items-center gap-2.5">
          <div class="flex size-8 items-center justify-center rounded-lg bg-rose-500/10 text-sm font-bold text-rose-400">
            !
          </div>
          <div>
            <h3 class="text-base font-semibold text-slate-100">Catat Stock Waste / Kerugian</h3>
            <p class="text-xs text-slate-400">Pencatatan bahan baku tumpah, basi, atau salah buat</p>
          </div>
        </div>
        <button
          type="button"
          on:click={handleClose}
          class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-800 hover:text-slate-200"
        >
          ✕
        </button>
      </div>

      <!-- Form Body -->
      <form on:submit|preventDefault={handleSubmit} class="flex-1 space-y-4 overflow-y-auto p-6">
        {#if errorMessage}
          <div class="rounded-xl border border-rose-500/20 bg-rose-500/10 p-3 text-xs leading-relaxed text-rose-300">
            {errorMessage}
          </div>
        {/if}

        <!-- Pilihan Dari Menu / Produk Katalog -->
        {#if products.length > 0}
          <div>
            <label for="waste-product-select" class="mb-1.5 block text-xs font-medium text-slate-300">
              Pilih dari Katalog Menu (Opsional)
            </label>
            <select
              id="waste-product-select"
              value={selectedProductId}
              on:change={handleProductSelect}
              class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-xs text-slate-200 transition outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/30"
            >
              <option value="">-- Bahan / Menu Lainnya (Ketik Manual) --</option>
              {#each products as prod}
                <option value={prod.id}>{prod.name} ({formatRupiah(prod.base_price)})</option>
              {/each}
            </select>
          </div>
        {/if}

        <!-- Nama Bahan / Item Waste -->
        <div>
          <label for="waste-item-name" class="mb-1.5 block text-xs font-medium text-slate-300">
            Nama Bahan / Produk Terbuang <span class="text-rose-400">*</span>
          </label>
          <input
            id="waste-item-name"
            type="text"
            bind:value={itemName}
            placeholder="Contoh: Fresh Milk Diamond 1L / Sirup Caramel"
            required
            class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-xs text-slate-200 transition outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/30"
          />
        </div>

        <!-- Alasan Kerusakan / Pembuangan -->
        <div>
          <label for="waste-reason-select" class="mb-1.5 block text-xs font-medium text-slate-300">
            Alasan Pembuangan (Reason) <span class="text-rose-400">*</span>
          </label>
          <select
            id="waste-reason-select"
            bind:value={reason}
            class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-xs text-slate-200 transition outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/30"
          >
            {#each reasons as r}
              <option value={r.value}>{r.label} - {r.desc}</option>
            {/each}
          </select>
        </div>

        <!-- Quantity & Satuan -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label for="waste-qty" class="mb-1.5 block text-xs font-medium text-slate-300">
              Jumlah Rusak <span class="text-rose-400">*</span>
            </label>
            <input
              id="waste-qty"
              type="number"
              step="any"
              min="0.01"
              bind:value={quantity}
              required
              class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-xs text-slate-200 transition outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/30"
            />
          </div>
          <div>
            <label for="waste-unit" class="mb-1.5 block text-xs font-medium text-slate-300">
              Satuan <span class="text-rose-400">*</span>
            </label>
            <input
              id="waste-unit"
              type="text"
              bind:value={unit}
              placeholder="Liter / Botol / Gram / Cup"
              required
              class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-xs text-slate-200 transition outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/30"
            />
          </div>
        </div>

        <!-- Estimasi Modal Satuan & Total Kerugian -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label for="waste-cost-unit" class="mb-1.5 block text-xs font-medium text-slate-300">
              Modal / Unit (Rp)
            </label>
            <input
              id="waste-cost-unit"
              type="number"
              min="0"
              step="500"
              bind:value={costPerUnit}
              class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-xs text-slate-200 transition outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/30"
            />
          </div>
          <div>
            <label for="waste-total-loss" class="mb-1.5 block text-xs font-medium text-slate-300">
              Total Kerugian
            </label>
            <div
              id="waste-total-loss"
              class="flex w-full items-center rounded-xl border border-rose-500/20 bg-rose-500/10 px-3.5 py-2.5 text-xs font-bold text-rose-300"
            >
              {formatRupiah(totalLossCost)}
            </div>
          </div>
        </div>

        <!-- Catatan Insiden -->
        <div>
          <label for="waste-notes" class="mb-1.5 block text-xs font-medium text-slate-300">
            Kronologi / Catatan Insiden
          </label>
          <textarea
            id="waste-notes"
            bind:value={notes}
            rows="2"
            placeholder="Jelaskan secara singkat penyebab kerusakan..."
            class="w-full resize-none rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2 text-xs text-slate-200 transition outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/30"
          ></textarea>
        </div>

        <!-- Kasir & PIN Otorisasi -->
        <div class="grid grid-cols-2 gap-3 border-t border-slate-800 pt-2">
          <div>
            <label for="waste-cashier" class="mb-1.5 block text-xs font-medium text-slate-300">
              Kasir Pelapor
            </label>
            <select
              id="waste-cashier"
              bind:value={cashierId}
              class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3 py-2 text-xs text-slate-200 outline-none focus:ring-2 focus:ring-rose-500/30"
            >
              {#each cashiers as c}
                <option value={c.id}>{c.name}</option>
              {/each}
            </select>
          </div>
          <div>
            <label for="waste-pin" class="mb-1.5 block text-xs font-medium text-slate-300">
              PIN Kasir (Opsional)
            </label>
            <input
              id="waste-pin"
              type="password"
              maxlength="6"
              bind:value={pin}
              placeholder="••••"
              class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3 py-2 text-xs tracking-widest text-slate-200 outline-none focus:ring-2 focus:ring-rose-500/30"
            />
          </div>
        </div>

        <!-- Tombol Aksi -->
        <div class="flex items-center justify-end gap-3 border-t border-slate-800 pt-3">
          <button
            type="button"
            on:click={handleClose}
            disabled={isSubmitting}
            class="rounded-xl px-4 py-2 text-xs font-medium text-slate-400 transition hover:bg-slate-800 hover:text-slate-200"
          >
            Batal
          </button>
          <button
            type="submit"
            disabled={isSubmitting}
            class="rounded-xl bg-rose-600 px-5 py-2 text-xs font-semibold text-white shadow-lg shadow-rose-600/20 transition hover:bg-rose-500 active:scale-95 disabled:opacity-50"
          >
            {isSubmitting ? 'Menyimpan...' : 'Simpan Kerugian'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}
