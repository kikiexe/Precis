<script lang="ts">
  import { X, Check, ArrowRight, History, ChevronDown, Layers } from 'lucide-svelte';
  import type { RawMaterialItem, StockAdjustmentReason } from '../../types/app';
  import { inventoryService } from '../../services/inventory-service';

  interface Props {
    material: RawMaterialItem | null;
    currentUserName?: string;
    onClose: () => void;
    onSuccess: (updated: RawMaterialItem) => void;
  }

  let { material, currentUserName = 'Owner', onClose, onSuccess }: Props = $props();

  let adjustmentMode = $state<'PHYSICAL_COUNT' | 'DELTA'>('PHYSICAL_COUNT');
  let physicalCountInput = $state<number>(0);
  let deltaInput = $state<number>(0);
  let selectedReason = $state<StockAdjustmentReason>('STOCK_TAKE');
  let notesInput = $state('');
  let isSubmitting = $state(false);
  let activeView = $state<'ADJUST' | 'LOGS'>('ADJUST');

  $effect(() => {
    physicalCountInput = material?.current_stock ?? 0;
  });

  let materialLogs = $derived(
    material
      ? inventoryService.getAdjustmentLogs().filter((l) => l.material_id === material.id)
      : []
  );

  let previewNewStock = $derived.by(() => {
    if (!material) return 0;
    if (adjustmentMode === 'PHYSICAL_COUNT') {
      return Math.max(0, physicalCountInput);
    }
    return Math.max(0, material.current_stock + deltaInput);
  });

  let stockDiff = $derived.by(() => {
    if (!material) return 0;
    return previewNewStock - material.current_stock;
  });

  function getReasonLabel(reason: StockAdjustmentReason) {
    switch (reason) {
      case 'STOCK_TAKE':
        return 'Opname Fisik / Rekonsiliasi Shift';
      case 'DAMAGED':
        return 'Barang Rusak / Pecah di Bar';
      case 'EXPIRED':
        return 'Bahan Kedaluwarsa';
      case 'RESTOCK':
        return 'Pembelian / Restock Masuk';
      case 'WASTE':
        return 'Waste / Kalibrasi Mesin Tumpah';
      case 'OTHER':
        return 'Alasan Lainnya';
    }
  }

  function handleSubmit() {
    if (!material || isSubmitting) return;

    isSubmitting = true;
    const result = inventoryService.adjustStock({
      material_id: material.id,
      new_stock: adjustmentMode === 'PHYSICAL_COUNT' ? physicalCountInput : undefined,
      delta_stock: adjustmentMode === 'DELTA' ? deltaInput : undefined,
      reason: selectedReason,
      notes: notesInput.trim() || undefined,
      performed_by: currentUserName,
    });

    isSubmitting = false;
    if (result) {
      onSuccess(result.material);
    }
  }
</script>

{#if material}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="animate-in fade-in zoom-in-95 w-full max-w-lg overflow-hidden rounded-3xl border border-[#e5e5ea] bg-white shadow-2xl duration-150"
    >
      <!-- Modal Header -->
      <div
        class="flex items-center justify-between border-b border-[#f2f2f4] bg-[#fafafc] px-6 py-5"
      >
        <div class="flex min-w-0 items-center gap-3">
          <div
            class="flex size-10 shrink-0 items-center justify-center rounded-2xl border border-[#e5e5ea] bg-[#f4f4f6] text-[#17171c]"
          >
            <Layers class="size-5" />
          </div>
          <div class="min-w-0">
            <h3 class="truncate text-base font-bold tracking-tight text-[#17171c]">
              {material.name}
            </h3>
            <p class="truncate font-mono text-xs text-[#8e8e93]">
              Penyesuaian Stok &amp; Audit Opname
            </p>
          </div>
        </div>

        <button
          type="button"
          onclick={onClose}
          class="shrink-0 cursor-pointer rounded-xl p-2 text-[#8e8e93] transition-all hover:bg-[#f4f4f6] hover:text-[#17171c]"
        >
          <X class="size-5" />
        </button>
      </div>

      <!-- Segmented View Selector -->
      <div class="flex gap-4 border-b border-[#f2f2f4] px-6 pt-3">
        <button
          type="button"
          onclick={() => (activeView = 'ADJUST')}
          class={`cursor-pointer border-b-2 pb-3 text-xs font-semibold transition-all ${
            activeView === 'ADJUST'
              ? 'border-[#17171c] text-[#17171c]'
              : 'border-transparent text-[#8e8e93] hover:text-[#17171c]'
          }`}
        >
          Form Penyesuaian
        </button>
        <button
          type="button"
          onclick={() => (activeView = 'LOGS')}
          class={`flex cursor-pointer items-center gap-1.5 border-b-2 pb-3 text-xs font-semibold transition-all ${
            activeView === 'LOGS'
              ? 'border-[#17171c] text-[#17171c]'
              : 'border-transparent text-[#8e8e93] hover:text-[#17171c]'
          }`}
        >
          <History class="size-4" />
          <span>Riwayat Audit ({materialLogs.length})</span>
        </button>
      </div>

      {#if activeView === 'ADJUST'}
        <div class="max-h-[75vh] space-y-5 overflow-y-auto p-6">
          <!-- Current vs Target Preview Card -->
          <div
            class="flex items-center justify-between rounded-2xl border border-[#e5e5ea] bg-[#f8f8fa] p-4 shadow-2xs"
          >
            <div>
              <span class="font-mono text-[10px] font-semibold text-[#8e8e93] uppercase"
                >Stok Sistem</span
              >
              <div class="mt-0.5 font-mono text-xl font-bold text-[#17171c]">
                {material.current_stock}
                <span class="text-xs font-normal text-[#8e8e93]">{material.unit}</span>
              </div>
            </div>

            <ArrowRight class="size-5 text-[#8e8e93]" />

            <div class="text-right">
              <span class="font-mono text-[10px] font-semibold text-[#8e8e93] uppercase"
                >Stok Akhir</span
              >
              <div class="mt-0.5 font-mono text-xl font-bold text-[#17171c]">
                {previewNewStock}
                <span class="text-xs font-normal text-[#8e8e93]">{material.unit}</span>
              </div>
            </div>

            <div class="border-l border-[#e5e5ea] pl-4 text-right">
              <span class="font-mono text-[10px] font-semibold text-[#8e8e93] uppercase"
                >Selisih</span
              >
              <div
                class={`mt-0.5 font-mono text-sm font-bold ${
                  stockDiff > 0
                    ? 'text-[#059669]'
                    : stockDiff < 0
                      ? 'text-[#e5484d]'
                      : 'text-[#686873]'
                }`}
              >
                {stockDiff > 0 ? `+${stockDiff}` : stockDiff}
                {material.unit}
              </div>
            </div>
          </div>

          <!-- Mode Selector Tabs -->
          <div class="space-y-1.5">
            <span class="block text-xs font-bold text-[#17171c]">Metode Input</span>
            <div
              class="grid grid-cols-2 gap-2 rounded-2xl border border-[#e5e5ea] bg-[#f4f4f6] p-1.5"
            >
              <button
                type="button"
                onclick={() => (adjustmentMode = 'PHYSICAL_COUNT')}
                class={`cursor-pointer rounded-xl py-2 text-xs font-semibold transition-all ${
                  adjustmentMode === 'PHYSICAL_COUNT'
                    ? 'bg-white text-[#17171c] shadow-xs'
                    : 'text-[#686873] hover:text-[#17171c]'
                }`}
              >
                Hitung Fisik (Opname)
              </button>
              <button
                type="button"
                onclick={() => (adjustmentMode = 'DELTA')}
                class={`cursor-pointer rounded-xl py-2 text-xs font-semibold transition-all ${
                  adjustmentMode === 'DELTA'
                    ? 'bg-white text-[#17171c] shadow-xs'
                    : 'text-[#686873] hover:text-[#17171c]'
                }`}
              >
                Tambah / Kurang (+/-)
              </button>
            </div>
          </div>

          <!-- Input Fields based on mode -->
          {#if adjustmentMode === 'PHYSICAL_COUNT'}
            <div class="space-y-1.5">
              <label
                for="adj-physical-count"
                class="flex justify-between text-xs font-bold text-[#17171c]"
              >
                <span>Jumlah Fisik Aktual ({material.unit})</span>
                <span class="text-[11px] font-normal text-[#8e8e93]"
                  >Hasil hitung fisik bar/gudang</span
                >
              </label>
              <input
                id="adj-physical-count"
                type="number"
                min="0"
                step="any"
                bind:value={physicalCountInput}
                class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 font-mono text-xs text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
              />
            </div>
          {:else}
            <div class="space-y-1.5">
              <label for="adj-delta" class="flex justify-between text-xs font-bold text-[#17171c]">
                <span>Perubahan Jumlah (+/-)</span>
                <span class="text-[11px] font-normal text-[#8e8e93]"
                  >Gunakan tanda minus (-) untuk pengurangan</span
                >
              </label>
              <input
                id="adj-delta"
                type="number"
                step="any"
                bind:value={deltaInput}
                class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 font-mono text-xs text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
              />
            </div>
          {/if}

          <!-- Reason Dropdown -->
          <div class="space-y-1.5">
            <label for="adj-reason" class="block text-xs font-bold text-[#17171c]">
              Alasan Penyesuaian
            </label>
            <div class="relative">
              <select
                id="adj-reason"
                bind:value={selectedReason}
                class="w-full cursor-pointer appearance-none rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 pr-10 text-xs text-[#17171c] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden"
              >
                <option value="STOCK_TAKE">Opname Fisik / Rekonsiliasi Shift</option>
                <option value="DAMAGED">Barang Rusak / Pecah di Bar</option>
                <option value="EXPIRED">Bahan Kedaluwarsa</option>
                <option value="RESTOCK">Pembelian / Restock Masuk</option>
                <option value="WASTE">Waste / Kalibrasi Mesin Tumpah</option>
                <option value="OTHER">Alasan Lainnya</option>
              </select>
              <ChevronDown
                class="pointer-events-none absolute top-1/2 right-3.5 size-4 -translate-y-1/2 text-[#8e8e93]"
              />
            </div>
          </div>

          <!-- Notes -->
          <div class="space-y-1.5">
            <label for="adj-notes" class="block text-xs font-bold text-[#17171c]">
              Catatan Tambahan (Opsional)
            </label>
            <input
              id="adj-notes"
              type="text"
              bind:value={notesInput}
              placeholder="Contoh: Susu tumpah saat kalibrasi mesin steam"
              class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-xs text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
            />
          </div>

          <div class="flex items-center gap-3 pt-2">
            <button
              type="button"
              onclick={onClose}
              class="flex-1 cursor-pointer rounded-full border border-[#e5e5ea] py-3 text-xs font-semibold text-[#686873] transition-all hover:bg-[#f4f4f6]"
            >
              Batal
            </button>
            <button
              type="button"
              onclick={handleSubmit}
              disabled={isSubmitting || (adjustmentMode === 'DELTA' && deltaInput === 0)}
              class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
            >
              <Check class="size-4" />
              <span>Simpan Penyesuaian</span>
            </button>
          </div>
        </div>
      {:else}
        <!-- Audit Logs View -->
        <div class="max-h-[75vh] space-y-4 overflow-y-auto p-6">
          {#if materialLogs.length === 0}
            <div class="py-12 text-center text-xs text-[#8e8e93]">
              Belum ada riwayat penyesuaian untuk bahan ini.
            </div>
          {:else}
            <div class="space-y-3">
              {#each materialLogs as log}
                <div class="space-y-2 rounded-2xl border border-[#e5e5ea] bg-[#f8f8fa] p-4 text-xs">
                  <div class="flex items-center justify-between">
                    <span class="font-bold text-[#17171c]">{getReasonLabel(log.reason)}</span>
                    <span class="font-mono text-[11px] text-[#8e8e93]">{log.created_at}</span>
                  </div>
                  <div class="flex items-center gap-4 font-mono text-xs">
                    <div>Sebelum: <strong>{log.prev_stock}</strong></div>
                    <div>Sesudah: <strong>{log.new_stock}</strong></div>
                    <div class={log.adjusted_amount > 0 ? 'text-[#059669]' : 'text-[#e5484d]'}>
                      Selisih: {log.adjusted_amount > 0
                        ? `+${log.adjusted_amount}`
                        : log.adjusted_amount}
                    </div>
                  </div>
                  {#if log.notes}
                    <p
                      class="rounded-xl border border-[#e5e5ea] bg-white p-2 text-[11px] text-[#686873]"
                    >
                      "{log.notes}"
                    </p>
                  {/if}
                  <div class="font-mono text-[10px] text-[#8e8e93]">
                    Petugas: {log.performed_by}
                  </div>
                </div>
              {/each}
            </div>
          {/if}

          <div class="pt-2">
            <button
              type="button"
              onclick={onClose}
              class="w-full cursor-pointer rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white transition-all hover:bg-black"
            >
              Tutup
            </button>
          </div>
        </div>
      {/if}
    </div>
  </div>
{/if}
