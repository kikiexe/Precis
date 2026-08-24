<script lang="ts">
  import { X, Check, ArrowRight, History } from 'lucide-svelte';
  import type { RawMaterialItem, StockAdjustmentReason } from '../../types/app';
  import { inventoryService } from '../../services/inventory-service';

  interface Props {
    material: RawMaterialItem | null;
    currentUserName: string;
    onClose: () => void;
    onSuccess: (updated: RawMaterialItem) => void;
  }

  let { material, currentUserName, onClose, onSuccess }: Props = $props();

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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] w-full max-w-lg overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-150">
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-[#d9d9dd] flex items-center justify-between bg-[#eeece7]/30">
        <div>
          <div class="text-[10px] font-mono uppercase tracking-wider text-[#75758a]">Penyesuaian Stok Bahan Baku</div>
          <h3 class="text-base font-medium text-[#212121] tracking-tight truncate max-w-xs sm:max-w-md mt-0.5">
            {material.name}
          </h3>
        </div>

        <button
          type="button"
          onclick={onClose}
          class="w-8 h-8 rounded-full border border-[#d9d9dd] bg-white hover:bg-[#eeece7] flex items-center justify-center text-[#616161] hover:text-[#212121] transition-all cursor-pointer"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Segmented View Selector -->
      <div class="px-6 pt-4 border-b border-[#d9d9dd] flex gap-2">
        <button
          type="button"
          onclick={() => (activeView = 'ADJUST')}
          class={`pb-2.5 text-xs font-medium border-b-2 transition-all cursor-pointer ${
            activeView === 'ADJUST'
              ? 'border-[#17171c] text-[#17171c]'
              : 'border-transparent text-[#75758a] hover:text-[#212121]'
          }`}
        >
          Form Penyesuaian
        </button>
        <button
          type="button"
          onclick={() => (activeView = 'LOGS')}
          class={`pb-2.5 text-xs font-medium border-b-2 transition-all cursor-pointer flex items-center gap-1.5 ${
            activeView === 'LOGS'
              ? 'border-[#17171c] text-[#17171c]'
              : 'border-transparent text-[#75758a] hover:text-[#212121]'
          }`}
        >
          <History class="w-3.5 h-3.5" />
          <span>Riwayat Audit ({materialLogs.length})</span>
        </button>
      </div>

      {#if activeView === 'ADJUST'}
        <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
          <!-- Current vs Target Preview Card -->
          <div class="p-4 bg-[#eeece7]/40 border border-[#d9d9dd] rounded-[16px] flex items-center justify-between">
            <div>
              <div class="text-[10px] font-mono uppercase text-[#75758a]">Stok Sistem</div>
              <div class="text-xl font-medium text-[#212121] mt-0.5">
                {material.current_stock} <span class="text-xs text-[#75758a] font-normal">{material.unit}</span>
              </div>
            </div>

            <ArrowRight class="w-5 h-5 text-[#93939f]" />

            <div class="text-right">
              <div class="text-[10px] font-mono uppercase text-[#75758a]">Stok Akhir</div>
              <div class="text-xl font-medium text-[#17171c] mt-0.5">
                {previewNewStock} <span class="text-xs text-[#75758a] font-normal">{material.unit}</span>
              </div>
            </div>

            <div class="border-l border-[#d9d9dd] pl-4 text-right">
              <div class="text-[10px] font-mono uppercase text-[#75758a]">Selisih</div>
              <div class={`text-sm font-mono font-medium mt-0.5 ${
                stockDiff > 0 ? 'text-[#003c33]' : stockDiff < 0 ? 'text-[#e5484d]' : 'text-[#616161]'
              }`}>
                {stockDiff > 0 ? `+${stockDiff}` : stockDiff} {material.unit}
              </div>
            </div>
          </div>

          <!-- Mode Selector Tabs -->
          <div class="space-y-1.5">
            <span class="block text-xs font-medium text-[#212121]">Metode Input</span>
            <div class="grid grid-cols-2 gap-2 bg-[#eeece7]/50 p-1 rounded-[12px] border border-[#d9d9dd]">
              <button
                type="button"
                onclick={() => (adjustmentMode = 'PHYSICAL_COUNT')}
                class={`py-1.5 text-xs font-medium rounded-[8px] transition-all cursor-pointer ${
                  adjustmentMode === 'PHYSICAL_COUNT'
                    ? 'bg-white text-[#17171c] shadow-xs'
                    : 'text-[#75758a] hover:text-[#212121]'
                }`}
              >
                Hitung Fisik Riil (Opname)
              </button>
              <button
                type="button"
                onclick={() => (adjustmentMode = 'DELTA')}
                class={`py-1.5 text-xs font-medium rounded-[8px] transition-all cursor-pointer ${
                  adjustmentMode === 'DELTA'
                    ? 'bg-white text-[#17171c] shadow-xs'
                    : 'text-[#75758a] hover:text-[#212121]'
                }`}
              >
                Tambah / Kurang (+/-)
              </button>
            </div>
          </div>

          <!-- Input Fields based on mode -->
          {#if adjustmentMode === 'PHYSICAL_COUNT'}
            <div class="space-y-1.5">
              <label for="adj-physical-count" class="text-xs font-medium text-[#212121] flex justify-between">
                <span>Jumlah Fisik Aktual ({material.unit})</span>
                <span class="text-[10px] text-[#75758a] font-normal">Hasil hitung di bar/gudang</span>
              </label>
              <input
                id="adj-physical-count"
                type="number"
                min="0"
                step="any"
                bind:value={physicalCountInput}
                class="w-full px-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-sm text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden transition-all"
              />
            </div>
          {:else}
            <div class="space-y-1.5">
              <label for="adj-delta" class="text-xs font-medium text-[#212121] flex justify-between">
                <span>Perubahan Jumlah (+/-)</span>
                <span class="text-[10px] text-[#75758a] font-normal">Gunakan tanda minus (-) untuk pengurangan</span>
              </label>
              <input
                id="adj-delta"
                type="number"
                step="any"
                bind:value={deltaInput}
                class="w-full px-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-sm text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden transition-all"
              />
            </div>
          {/if}

          <!-- Reason Standard Dropdown -->
          <div class="space-y-1.5">
            <label for="adj-reason" class="block text-xs font-medium text-[#212121]">Alasan Penyesuaian</label>
            <select
              id="adj-reason"
              bind:value={selectedReason}
              class="w-full px-3.5 py-2.5 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-xs text-[#212121] focus:border-[#17171c] focus:outline-hidden transition-all cursor-pointer"
            >
              <option value="STOCK_TAKE">STOCK_TAKE: Opname Fisik / Rekonsiliasi Shift</option>
              <option value="RESTOCK">RESTOCK: Pembelian / Bahan Masuk Baru</option>
              <option value="DAMAGED">DAMAGED: Rusak / Pecah di Bar</option>
              <option value="EXPIRED">EXPIRED: Bahan Kedaluwarsa</option>
              <option value="WASTE">WASTE: Kalibrasi / Tumpah</option>
              <option value="OTHER">OTHER: Alasan Lainnya (Wajib Catatan)</option>
            </select>
          </div>

          <!-- Notes Textarea -->
          <div class="space-y-1.5">
            <label for="adj-notes" class="block text-xs font-medium text-[#212121]">Catatan Operasional (Opsional)</label>
            <textarea
              id="adj-notes"
              bind:value={notesInput}
              rows="2"
              placeholder="Contoh: Kalibrasi grinder pagi 2 shot, atau botol retak..."
              class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-xs text-[#212121] placeholder-[#93939f] focus:border-[#17171c] focus:outline-hidden transition-all resize-none"
            ></textarea>
          </div>

          <!-- Action Buttons -->
          <div class="pt-2 flex gap-3">
            <button
              type="button"
              onclick={onClose}
              class="flex-1 px-4 py-2.5 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:text-[#212121] hover:bg-[#eeece7] transition-all cursor-pointer"
            >
              Batal
            </button>
            <button
              type="button"
              onclick={handleSubmit}
              disabled={isSubmitting}
              class="flex-1 px-4 py-2.5 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full transition-all cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2"
            >
              <Check class="w-3.5 h-3.5" />
              <span>Simpan Penyesuaian</span>
            </button>
          </div>
        </div>
      {:else}
        <!-- Audit Logs Tab -->
        <div class="p-6 max-h-[75vh] overflow-y-auto space-y-3">
          {#if materialLogs.length === 0}
            <div class="py-12 text-center text-xs text-[#75758a]">
              Belum ada riwayat penyesuaian untuk bahan ini.
            </div>
          {:else}
            {#each materialLogs as log}
              <div class="p-3 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[14px] space-y-1 text-xs">
                <div class="flex items-center justify-between">
                  <span class="font-medium text-[#212121]">{getReasonLabel(log.reason)}</span>
                  <span class="font-mono text-[10px] text-[#75758a]">{log.created_at}</span>
                </div>
                <div class="flex items-center gap-2 text-[#616161] font-mono text-[11px]">
                  <span>{log.prev_stock} {material.unit}</span>
                  <ArrowRight class="w-3 h-3 text-[#93939f]" />
                  <span class="font-medium text-[#17171c]">{log.new_stock} {material.unit}</span>
                  <span class={`font-medium ${log.adjusted_amount >= 0 ? 'text-[#003c33]' : 'text-[#e5484d]'}`}>
                    ({log.adjusted_amount >= 0 ? `+${log.adjusted_amount}` : log.adjusted_amount})
                  </span>
                </div>
                {#if log.notes && log.notes !== '-'}
                  <div class="text-[11px] text-[#75758a] italic pt-0.5">"{log.notes}"</div>
                {/if}
                <div class="text-[10px] text-[#93939f] pt-1">Oleh: {log.performed_by}</div>
              </div>
            {/each}
          {/if}
        </div>
      {/if}
    </div>
  </div>
{/if}
