<script lang="ts">
  import { X, SlidersHorizontal, ArrowRight, AlertTriangle } from 'lucide-svelte';
  import type { RawMaterial, StockAdjustmentReason } from '../../../../../types/pos';

  interface Props {
    material: (RawMaterial & { stock_previous_day?: number }) | null;
    onClose: () => void;
    onSave: (
      materialId: string,
      physicalCount: number,
      reason: StockAdjustmentReason,
      notes: string
    ) => void;
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
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 font-sans backdrop-blur-xs select-none"
  >
    <div
      class="animate-in zoom-in-95 w-full max-w-md space-y-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl duration-150"
    >
      <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div
            class="flex size-9 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-100 text-zinc-900"
          >
            <SlidersHorizontal class="size-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-zinc-900">Audit Stock Opname Bar</h3>
            <p class="font-mono text-[11px] text-zinc-500">{material.name}</p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="cursor-pointer p-1 text-zinc-400 hover:text-zinc-700"
        >
          <X class="size-4" />
        </button>
      </div>

      <div class="space-y-3.5 text-xs">
        <div class="grid grid-cols-2 gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-3.5">
          <div>
            <div class="font-mono text-[10px] text-zinc-500 uppercase">Stok di Sistem</div>
            <div class="mt-0.5 font-mono text-sm font-bold text-zinc-900">
              {material.current_stock}
              {material.unit}
            </div>
          </div>
          <div>
            <div class="font-mono text-[10px] text-zinc-500 uppercase">Selisih Fisik</div>
            <div
              class={`mt-0.5 font-mono text-sm font-bold ${
                variance === 0
                  ? 'text-emerald-600'
                  : variance < 0
                    ? 'text-red-600'
                    : 'text-blue-600'
              }`}
            >
              {variance > 0 ? `+${variance}` : variance}
              {material.unit}
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
            class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2.5 text-center font-mono text-lg font-bold text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
          />
        </div>

        <div class="space-y-1">
          <label for="pos-adjustment-reason" class="font-semibold text-zinc-900">
            Alasan Penyesuaian Stok (Reason)
          </label>
          <select
            id="pos-adjustment-reason"
            bind:value={selectedReason}
            class="w-full cursor-pointer rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2.5 text-xs font-medium text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
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
            placeholder={selectedReason === 'OTHER'
              ? 'Tuliskan alasan detail penyesuaian...'
              : 'Contoh: Susu basi sebelum expired date...'}
            class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 text-xs text-zinc-900 placeholder-zinc-400 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
          />
        </div>

        {#if errorMessage}
          <div
            class="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 p-2.5 text-xs text-red-700"
          >
            <AlertTriangle class="size-4 shrink-0" />
            <span>{errorMessage}</span>
          </div>
        {/if}
      </div>

      <div class="flex gap-2.5 pt-2">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 cursor-pointer rounded-xl border border-zinc-200 py-2.5 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-100"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSubmit}
          class="active:scale-0.99 flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-zinc-900 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
        >
          <span>Simpan &amp; Catat Audit</span>
          <ArrowRight class="size-3.5" />
        </button>
      </div>
    </div>
  </div>
{/if}
