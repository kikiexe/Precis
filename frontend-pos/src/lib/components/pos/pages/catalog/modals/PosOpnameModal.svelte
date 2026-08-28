<script lang="ts">
  import { X, SlidersHorizontal, ArrowRight, AlertTriangle } from 'lucide-svelte';
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
  let errorMessage = $state('');

  $effect(() => {
    if (material) {
      physicalCountInput = material.current_stock;
      selectedReason = 'STOCK_TAKE';
      adjustmentNotes = '';
      errorMessage = '';
    }
  });

  let variance = $derived(material ? physicalCountInput - material.current_stock : 0);

  function handleSubmit() {
    if (!material) return;
    errorMessage = '';

    if (selectedReason === 'OTHER' && !adjustmentNotes.trim()) {
      errorMessage = 'Catatan wajib diisi jika memilih alasan Lainnya (other).';
      return;
    }

    onSave(material.id, physicalCountInput, selectedReason, adjustmentNotes);
    onClose();
  }
</script>

{#if material}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans select-none">
    <div class="bg-white border border-zinc-200 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-2xl animate-in zoom-in-95 duration-150">
      <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-zinc-100 text-zinc-900 flex items-center justify-center border border-zinc-200">
            <SlidersHorizontal class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-zinc-900">Audit Stock Opname Bar</h3>
            <p class="text-[11px] text-zinc-500 font-mono">{material.name}</p>
          </div>
        </div>
        <button type="button" onclick={onClose} class="text-zinc-400 hover:text-zinc-700 cursor-pointer p-1">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="space-y-3.5 text-xs">
        <div class="grid grid-cols-2 gap-3 p-3.5 bg-zinc-50 rounded-xl border border-zinc-200">
          <div>
            <div class="text-[10px] text-zinc-500 uppercase font-mono">Stok di Sistem</div>
            <div class="font-bold text-sm font-mono text-zinc-900 mt-0.5">
              {material.current_stock} {material.unit}
            </div>
          </div>
          <div>
            <div class="text-[10px] text-zinc-500 uppercase font-mono">Selisih Fisik</div>
            <div class={`font-bold text-sm font-mono mt-0.5 ${
              variance === 0 ? 'text-emerald-600' : variance < 0 ? 'text-red-600' : 'text-blue-600'
            }`}>
              {variance > 0 ? `+${variance}` : variance} {material.unit}
            </div>
          </div>
        </div>

        <div class="space-y-1">
          <label for="pos-physical-count" class="font-semibold text-zinc-900">
            Hasil Hitung Fisik Aktual ({material.unit})
          </label>
          <input
            id="pos-physical-count"
            type="number"
            bind:value={physicalCountInput}
            step="any"
            class="w-full px-3.5 py-2.5 bg-zinc-50 border border-zinc-200 rounded-xl font-mono text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden text-center text-lg font-bold"
          />
        </div>

        <div class="space-y-1">
          <label for="pos-adjustment-reason" class="font-semibold text-zinc-900">
            Alasan Penyesuaian Stok (Reason)
          </label>
          <select
            id="pos-adjustment-reason"
            bind:value={selectedReason}
            class="w-full px-3.5 py-2.5 bg-zinc-50 border border-zinc-200 rounded-xl text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden text-xs font-medium cursor-pointer"
          >
            <option value="STOCK_TAKE">Opname Rutin / Closing Shift (Stock Take)</option>
            <option value="RESTOCK">Penerimaan Barang Baru / Re-supply (Restock)</option>
            <option value="WASTE">Tumpah / Waste Racikan Bar (Waste)</option>
            <option value="EXPIRED">Basi / Kadaluarsa (Expired)</option>
            <option value="DAMAGED">Kemasan Bocor / Pecah / Rusak (Damaged)</option>
            <option value="OTHER">Lainnya / Penyesuaian Manual (Other)</option>
          </select>
        </div>

        <div class="space-y-1">
          <label for="pos-adjustment-notes" class="font-semibold text-zinc-900">
            Catatan / Keterangan {selectedReason === 'OTHER' ? '(Wajib)' : '(Opsional)'}
          </label>
          <input
            id="pos-adjustment-notes"
            type="text"
            bind:value={adjustmentNotes}
            placeholder={selectedReason === 'OTHER' ? 'Tuliskan alasan detail penyesuaian...' : 'Contoh: Susu basi sebelum expired date...'}
            class="w-full px-3.5 py-2 bg-zinc-50 border border-zinc-200 rounded-xl text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden text-xs placeholder-zinc-400"
          />
        </div>

        {#if errorMessage}
          <div class="p-2.5 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs flex items-center gap-2">
            <AlertTriangle class="w-4 h-4 shrink-0" />
            <span>{errorMessage}</span>
          </div>
        {/if}
      </div>

      <div class="pt-2 flex gap-2.5">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-2.5 text-xs font-semibold border border-zinc-200 rounded-xl text-zinc-700 hover:bg-zinc-100 cursor-pointer transition-colors"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSubmit}
          class="flex-1 py-2.5 text-xs font-semibold bg-zinc-900 hover:bg-black text-white rounded-xl flex items-center justify-center gap-1.5 cursor-pointer shadow-xs transition-all active:scale-[0.99]"
        >
          <span>Simpan &amp; Catat Audit</span>
          <ArrowRight class="w-3.5 h-3.5" />
        </button>
      </div>
    </div>
  </div>
{/if}

