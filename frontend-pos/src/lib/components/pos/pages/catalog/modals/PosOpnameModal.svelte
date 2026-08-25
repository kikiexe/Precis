<script lang="ts">
  import { X, SlidersHorizontal, ArrowRight } from 'lucide-svelte';
  import type { RawMaterial, StockAdjustmentReason } from '../../../../../types/pos';

  interface Props {
    material: (RawMaterial & { stock_previous_day?: number }) | null;
    onClose: () => void;
    onSave: (materialId: string, physicalCount: number, reason: StockAdjustmentReason, notes: string) => void;
  }

  let { material, onClose, onSave }: Props = $props();

  let physicalCountInput = $state(0);
  let selectedReason = $state<StockAdjustmentReason>('STOCK_TAKE');
  let adjustmentNotes = $state('');

  $effect(() => {
    if (material) {
      physicalCountInput = material.current_stock;
      selectedReason = 'STOCK_TAKE';
      adjustmentNotes = '';
    }
  });

  let variance = $derived(material ? physicalCountInput - material.current_stock : 0);

  function handleSubmit() {
    if (!material) return;
    onSave(material.id, physicalCountInput, selectedReason, adjustmentNotes);
    onClose();
  }
</script>

{#if material}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-3xl w-full max-w-md p-6 space-y-4 shadow-xl">
      <div class="flex items-center justify-between border-b border-[#e5e5e5] pb-3">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-[#eeece7] text-[#17171c] flex items-center justify-center">
            <SlidersHorizontal class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-[#17171c]">Audit Stock Opname Bar</h3>
            <p class="text-[11px] text-[#75758a] font-mono">{material.name}</p>
          </div>
        </div>
        <button type="button" onclick={onClose} class="text-[#75758a] hover:text-[#17171c] cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="space-y-3 text-xs">
        <div class="grid grid-cols-2 gap-3 p-3 bg-[#fafafa] rounded-2xl border border-[#e5e5e5]">
          <div>
            <div class="text-[10px] text-[#75758a] uppercase font-mono">Stok di Sistem</div>
            <div class="font-bold text-sm font-mono text-[#17171c] mt-0.5">
              {material.current_stock} {material.unit}
            </div>
          </div>
          <div>
            <div class="text-[10px] text-[#75758a] uppercase font-mono">Selisih Fisik</div>
            <div class={`font-bold text-sm font-mono mt-0.5 ${
              variance === 0 ? 'text-[#00875a]' : variance < 0 ? 'text-[#e5484d]' : 'text-[#1863dc]'
            }`}>
              {variance > 0 ? `+${variance}` : variance} {material.unit}
            </div>
          </div>
        </div>

        <div class="space-y-1">
          <label for="pos-physical-count" class="font-medium text-[#17171c]">Hasil Hitung Fisik Sebenarnya ({material.unit})</label>
          <input
            id="pos-physical-count"
            type="number"
            bind:value={physicalCountInput}
            step="any"
            class="w-full px-3.5 py-2 bg-white border border-[#d9d9dd] rounded-full font-mono text-[#17171c] focus:border-[#17171c] focus:outline-hidden text-center text-base font-bold"
          />
        </div>

        <div class="space-y-1">
          <label for="pos-adjustment-reason" class="font-medium text-[#17171c]">Alasan Rekonsiliasi Selisih</label>
          <select
            id="pos-adjustment-reason"
            bind:value={selectedReason}
            class="w-full px-3.5 py-2 bg-white border border-[#d9d9dd] rounded-full text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
          >
            <option value="STOCK_TAKE">Opname Harian / Closing Shift</option>
            <option value="WASTE">Tumpah / Waste Racikan Bar</option>
            <option value="DAMAGED">Basi / Rusak / Expired</option>
            <option value="RESTOCK">Penerimaan Stok Baru / Re-supply</option>
            <option value="CORRECTION">Koreksi Salah Input</option>
          </select>
        </div>

        <div class="space-y-1">
          <label for="pos-adjustment-notes" class="font-medium text-[#17171c]">Catatan Tambahan (Opsional)</label>
          <input
            id="pos-adjustment-notes"
            type="text"
            bind:value={adjustmentNotes}
            placeholder="Contoh: Susu basi sebelum expired date..."
            class="w-full px-3.5 py-2 bg-white border border-[#d9d9dd] rounded-full text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>
      </div>

      <div class="pt-2 flex gap-2.5">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#f4f4f4] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSubmit}
          class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full flex items-center justify-center gap-1 cursor-pointer"
        >
          <span>Konfirmasi Opname</span>
          <ArrowRight class="w-3.5 h-3.5" />
        </button>
      </div>
    </div>
  </div>
{/if}
