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
    material ? inventoryService.getAdjustmentLogs().filter((l) => l.material_id === material.id) : []
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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-150">
      <!-- Modal Header -->
      <div class="px-6 py-5 border-b border-[#f2f2f4] flex items-center justify-between bg-[#fafafc]">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-10 h-10 rounded-2xl bg-[#f4f4f6] text-[#17171c] flex items-center justify-center border border-[#e5e5ea] shrink-0">
            <Layers class="w-5 h-5" />
          </div>
          <div class="min-w-0">
            <h3 class="text-base font-bold text-[#17171c] tracking-tight truncate">
              {material.name}
            </h3>
            <p class="text-xs text-[#8e8e93] font-mono truncate">Penyesuaian Stok &amp; Audit Opname</p>
          </div>
        </div>

        <button
          type="button"
          onclick={onClose}
          class="p-2 text-[#8e8e93] hover:text-[#17171c] hover:bg-[#f4f4f6] rounded-xl cursor-pointer transition-all shrink-0"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Segmented View Selector -->
      <div class="px-6 pt-3 border-b border-[#f2f2f4] flex gap-4">
        <button
          type="button"
          onclick={() => (activeView = 'ADJUST')}
          class={`pb-3 text-xs font-semibold border-b-2 transition-all cursor-pointer ${
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
          class={`pb-3 text-xs font-semibold border-b-2 transition-all cursor-pointer flex items-center gap-1.5 ${
            activeView === 'LOGS'
              ? 'border-[#17171c] text-[#17171c]'
              : 'border-transparent text-[#8e8e93] hover:text-[#17171c]'
          }`}
        >
          <History class="w-4 h-4" />
          <span>Riwayat Audit ({materialLogs.length})</span>
        </button>
      </div>

      {#if activeView === 'ADJUST'}
        <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
          <!-- Current vs Target Preview Card -->
          <div class="p-4 bg-[#f8f8fa] border border-[#e5e5ea] rounded-2xl flex items-center justify-between shadow-2xs">
            <div>
              <span class="text-[10px] font-mono uppercase text-[#8e8e93] font-semibold">Stok Sistem</span>
              <div class="text-xl font-bold font-mono text-[#17171c] mt-0.5">
                {material.current_stock} <span class="text-xs text-[#8e8e93] font-normal">{material.unit}</span>
              </div>
            </div>

            <ArrowRight class="w-5 h-5 text-[#8e8e93]" />

            <div class="text-right">
              <span class="text-[10px] font-mono uppercase text-[#8e8e93] font-semibold">Stok Akhir</span>
              <div class="text-xl font-bold font-mono text-[#17171c] mt-0.5">
                {previewNewStock} <span class="text-xs text-[#8e8e93] font-normal">{material.unit}</span>
              </div>
            </div>

            <div class="border-l border-[#e5e5ea] pl-4 text-right">
              <span class="text-[10px] font-mono uppercase text-[#8e8e93] font-semibold">Selisih</span>
              <div class={`text-sm font-mono font-bold mt-0.5 ${
                stockDiff > 0 ? 'text-[#059669]' : stockDiff < 0 ? 'text-[#e5484d]' : 'text-[#686873]'
              }`}>
                {stockDiff > 0 ? `+${stockDiff}` : stockDiff} {material.unit}
              </div>
            </div>
          </div>

          <!-- Mode Selector Tabs -->
          <div class="space-y-1.5">
            <span class="block text-xs font-bold text-[#17171c]">Metode Input</span>
            <div class="grid grid-cols-2 gap-2 bg-[#f4f4f6] p-1.5 rounded-2xl border border-[#e5e5ea]">
              <button
                type="button"
                onclick={() => (adjustmentMode = 'PHYSICAL_COUNT')}
                class={`py-2 text-xs font-semibold rounded-xl transition-all cursor-pointer ${
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
                class={`py-2 text-xs font-semibold rounded-xl transition-all cursor-pointer ${
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
              <label for="adj-physical-count" class="text-xs font-bold text-[#17171c] flex justify-between">
                <span>Jumlah Fisik Aktual ({material.unit})</span>
                <span class="text-[11px] text-[#8e8e93] font-normal">Hasil hitung fisik bar/gudang</span>
              </label>
              <input
                id="adj-physical-count"
                type="number"
                min="0"
                step="any"
                bind:value={physicalCountInput}
                class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-xs text-[#17171c] font-mono focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
              />
            </div>
          {:else}
            <div class="space-y-1.5">
              <label for="adj-delta" class="text-xs font-bold text-[#17171c] flex justify-between">
                <span>Perubahan Jumlah (+/-)</span>
                <span class="text-[11px] text-[#8e8e93] font-normal">Gunakan tanda minus (-) untuk pengurangan</span>
              </label>
              <input
                id="adj-delta"
                type="number"
                step="any"
                bind:value={deltaInput}
                class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-xs text-[#17171c] font-mono focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
              />
            </div>
          {/if}

          <!-- Reason Dropdown -->
          <div class="space-y-1.5">
            <label for="adj-reason" class="text-xs font-bold text-[#17171c] block">
              Alasan Penyesuaian
            </label>
            <div class="relative">
              <select
                id="adj-reason"
                bind:value={selectedReason}
                class="appearance-none w-full border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-xl px-4 pr-10 py-2.5 bg-[#f8f8fa] hover:bg-white text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
              >
                <option value="STOCK_TAKE">Opname Fisik / Rekonsiliasi Shift</option>
                <option value="DAMAGED">Barang Rusak / Pecah di Bar</option>
                <option value="EXPIRED">Bahan Kedaluwarsa</option>
                <option value="RESTOCK">Pembelian / Restock Masuk</option>
                <option value="WASTE">Waste / Kalibrasi Mesin Tumpah</option>
                <option value="OTHER">Alasan Lainnya</option>
              </select>
              <ChevronDown class="w-4 h-4 text-[#8e8e93] absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>
          </div>

          <!-- Notes -->
          <div class="space-y-1.5">
            <label for="adj-notes" class="text-xs font-bold text-[#17171c] block">
              Catatan Tambahan (Opsional)
            </label>
            <input
              id="adj-notes"
              type="text"
              bind:value={notesInput}
              placeholder="Contoh: Susu tumpah saat kalibrasi mesin steam"
              class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
            />
          </div>

          <div class="flex items-center gap-3 pt-2">
            <button
              type="button"
              onclick={onClose}
              class="flex-1 py-3 border border-[#e5e5ea] hover:bg-[#f4f4f6] text-[#686873] text-xs font-semibold rounded-full cursor-pointer transition-all"
            >
              Batal
            </button>
            <button
              type="button"
              onclick={handleSubmit}
              disabled={isSubmitting || (adjustmentMode === 'DELTA' && deltaInput === 0)}
              class="flex-1 py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full cursor-pointer transition-all disabled:opacity-50 flex items-center justify-center gap-2 shadow-xs"
            >
              <Check class="w-4 h-4" />
              <span>Simpan Penyesuaian</span>
            </button>
          </div>
        </div>
      {:else}
        <!-- Audit Logs View -->
        <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
          {#if materialLogs.length === 0}
            <div class="py-12 text-center text-[#8e8e93] text-xs">
              Belum ada riwayat penyesuaian untuk bahan ini.
            </div>
          {:else}
            <div class="space-y-3">
              {#each materialLogs as log}
                <div class="p-4 bg-[#f8f8fa] border border-[#e5e5ea] rounded-2xl space-y-2 text-xs">
                  <div class="flex items-center justify-between">
                    <span class="font-bold text-[#17171c]">{getReasonLabel(log.reason)}</span>
                    <span class="text-[11px] font-mono text-[#8e8e93]">{log.created_at}</span>
                  </div>
                  <div class="flex items-center gap-4 text-xs font-mono">
                    <div>Sebelum: <strong>{log.prev_stock}</strong></div>
                    <div>Sesudah: <strong>{log.new_stock}</strong></div>
                    <div class={log.adjusted_amount > 0 ? 'text-[#059669]' : 'text-[#e5484d]'}>
                      Selisih: {log.adjusted_amount > 0 ? `+${log.adjusted_amount}` : log.adjusted_amount}
                    </div>
                  </div>
                  {#if log.notes}
                    <p class="text-[11px] text-[#686873] bg-white p-2 rounded-xl border border-[#e5e5ea]">
                      "{log.notes}"
                    </p>
                  {/if}
                  <div class="text-[10px] text-[#8e8e93] font-mono">Petugas: {log.performed_by}</div>
                </div>
              {/each}
            </div>
          {/if}

          <div class="pt-2">
            <button
              type="button"
              onclick={onClose}
              class="w-full py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full cursor-pointer transition-all"
            >
              Tutup
            </button>
          </div>
        </div>
      {/if}
    </div>
  </div>
{/if}
