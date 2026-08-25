<script lang="ts">
  import { X, ChevronDown } from 'lucide-svelte';
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
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[24px] w-full max-w-md p-6 space-y-4 shadow-2xl">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3">
        <h3 class="text-base font-medium text-[#212121]">Tambah Bahan Baku Baru</h3>
        <button type="button" onclick={onClose} class="p-1 text-[#616161] hover:text-[#212121] cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="space-y-3 text-xs">
        <div class="space-y-1">
          <label for="material-name" class="block font-medium text-[#212121]">Nama Bahan Baku</label>
          <input
            id="material-name"
            type="text"
            bind:value={name}
            placeholder="Contoh: Fresh Milk Diamond 1L"
            class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] text-[#212121] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label for="material-category" class="block font-medium text-[#212121]">Kategori Bahan</label>
            <div class="relative">
              <select
                id="material-category"
                bind:value={category_id}
                class="appearance-none w-full border border-[#d9d9dd] rounded-full px-3.5 pr-8 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7]/60 text-xs text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
              >
                {#each categories.filter((c) => c.type === 'RAW_MATERIAL') as cat}
                  <option value={cat.id}>{cat.name}</option>
                {/each}
              </select>
              <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>
          </div>

          <div class="space-y-1">
            <label for="material-unit" class="block font-medium text-[#212121]">Satuan Unit</label>
            <div class="relative">
              <select
                id="material-unit"
                bind:value={unit}
                class="appearance-none w-full border border-[#d9d9dd] rounded-full px-3.5 pr-8 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7]/60 text-xs text-[#212121] font-mono focus:border-[#17171c] focus:outline-hidden cursor-pointer transition-all shadow-2xs"
              >
                <option value="liter">liter</option>
                <option value="ml">ml</option>
                <option value="kg">kg</option>
                <option value="gram">gram</option>
                <option value="pcs">pcs</option>
                <option value="pack">pack</option>
                <option value="botol">botol</option>
              </select>
              <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label for="material-stock" class="block font-medium text-[#212121]">Stok Awal</label>
            <input
              id="material-stock"
              type="number"
              bind:value={current_stock}
              class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>

          <div class="space-y-1">
            <label for="material-min-stock" class="block font-medium text-[#212121]">Batas Minimum</label>
            <input
              id="material-min-stock"
              type="number"
              bind:value={min_stock_alert}
              class="w-full px-3.5 py-2 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[12px] font-mono text-[#212121] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>
        </div>
      </div>

      <div class="pt-2 flex gap-3">
        <button
          type="button"
          onclick={onClose}
          class="flex-1 py-2 text-xs font-medium border border-[#d9d9dd] rounded-full text-[#616161] hover:bg-[#eeece7] cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={handleSubmit}
          class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer"
        >
          Simpan Bahan
        </button>
      </div>
    </div>
  </div>
{/if}
