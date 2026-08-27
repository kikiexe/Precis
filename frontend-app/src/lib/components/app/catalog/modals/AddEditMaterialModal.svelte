<script lang="ts">
  import { X, ChevronDown, Layers, Plus } from 'lucide-svelte';
  import type { CategoryItem, RawMaterialUnit } from '../../../../types/app';
  import { DEFAULT_MIN_STOCK_ALERT } from '../../../../constants/defaults';

  interface Props {
    isOpen: boolean;
    categories: CategoryItem[];
    onClose: () => void;
    onSave: (material: {
      name: string;
      category_id: string;
      current_stock: number;
      min_stock_alert: number;
      unit: RawMaterialUnit;
    }) => void;
  }

  let { isOpen, categories = [], onClose, onSave }: Props = $props();

  let name = $state('');
  let category_id = $state('cat-dairy');
  let current_stock = $state(10);
  let min_stock_alert = $state(DEFAULT_MIN_STOCK_ALERT);
  let unit = $state<RawMaterialUnit>('liter');

  $effect(() => {
    if (isOpen) {
      name = '';
      category_id = categories.find((c) => c.type === 'RAW_MATERIAL')?.id || 'cat-dairy';
      current_stock = 10;
      min_stock_alert = DEFAULT_MIN_STOCK_ALERT;
      unit = 'liter';
    }
  });

  function handleSubmit() {
    if (!name.trim() || current_stock < 0) return;
    onSave({
      name: name.trim(),
      category_id,
      current_stock: Number(current_stock),
      min_stock_alert: Number(min_stock_alert),
      unit,
    });
    onClose();
  }
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#e5e5ea] rounded-3xl w-full max-w-lg p-6 sm:p-7 space-y-5 shadow-xl animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between pb-3 border-b border-[#f2f2f4]">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#f4f4f6] text-[#17171c] flex items-center justify-center border border-[#e5e5ea]">
            <Layers class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Tambah Bahan Baku Baru</h3>
            <p class="text-xs text-[#8e8e93]">Pantau persediaan stok dan ambang minimum</p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="p-2 text-[#8e8e93] hover:text-[#17171c] hover:bg-[#f4f4f6] rounded-xl cursor-pointer transition-all"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <div class="space-y-4 text-xs">
        <div class="space-y-1.5">
          <label for="material-name" class="block font-bold text-[#17171c]">Nama Bahan Baku</label>
          <input
            id="material-name"
            type="text"
            bind:value={name}
            placeholder="Contoh: Fresh Milk Diamond 1L, Sirup Vanilla"
            class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <label for="material-category" class="block font-bold text-[#17171c]">Kategori Bahan</label>
            <div class="relative">
              <select
                id="material-category"
                bind:value={category_id}
                class="appearance-none w-full border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-xl px-4 pr-10 py-2.5 bg-[#f8f8fa] hover:bg-white text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
              >
                {#each categories.filter((c) => c.type === 'RAW_MATERIAL') as cat}
                  <option value={cat.id}>{cat.name}</option>
                {/each}
              </select>
              <ChevronDown class="w-4 h-4 text-[#8e8e93] absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>
          </div>

          <div class="space-y-1.5">
            <label for="material-unit" class="block font-bold text-[#17171c]">Satuan Unit</label>
            <div class="relative">
              <select
                id="material-unit"
                bind:value={unit}
                class="appearance-none w-full border border-[#e5e5ea] hover:border-[#d1d1d6] rounded-xl px-4 pr-10 py-2.5 bg-[#f8f8fa] hover:bg-white text-xs text-[#17171c] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
              >
                <option value="liter">liter</option>
                <option value="ml">ml</option>
                <option value="kg">kg</option>
                <option value="gram">gram</option>
                <option value="pcs">pcs</option>
                <option value="kaleng">kaleng</option>
                <option value="pack">pack</option>
              </select>
              <ChevronDown class="w-4 h-4 text-[#8e8e93] absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <label for="material-stock" class="block font-bold text-[#17171c]">Stok Awal ({unit})</label>
            <input
              id="material-stock"
              type="number"
              min="0"
              bind:value={current_stock}
              class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl font-mono text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
            />
          </div>

          <div class="space-y-1.5">
            <label for="material-alert" class="block font-bold text-[#17171c]">Peringatan Minimum ({unit})</label>
            <input
              id="material-alert"
              type="number"
              min="0"
              bind:value={min_stock_alert}
              class="w-full px-4 py-2.5 bg-[#f8f8fa] hover:bg-white border border-[#e5e5ea] rounded-xl font-mono text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
            />
          </div>
        </div>
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
          disabled={!name.trim() || current_stock < 0}
          onclick={handleSubmit}
          class="flex-1 py-3 bg-[#17171c] hover:bg-black text-white text-xs font-semibold rounded-full cursor-pointer transition-all disabled:opacity-50 flex items-center justify-center gap-2 shadow-xs"
        >
          <Plus class="w-4 h-4" />
          <span>Simpan Bahan</span>
        </button>
      </div>
    </div>
  </div>
{/if}
