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
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 font-sans backdrop-blur-xs"
  >
    <div
      class="animate-in fade-in zoom-in-95 w-full max-w-lg space-y-5 rounded-3xl border border-[#e5e5ea] bg-white p-6 shadow-xl sm:p-7"
    >
      <div class="flex items-center justify-between border-b border-[#f2f2f4] pb-3">
        <div class="flex items-center gap-3">
          <div
            class="flex size-10 items-center justify-center rounded-2xl border border-[#e5e5ea] bg-[#f4f4f6] text-[#17171c]"
          >
            <Layers class="size-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-[#17171c]">Tambah Bahan Baku Baru</h3>
            <p class="text-xs text-[#8e8e93]">Pantau persediaan stok dan ambang minimum</p>
          </div>
        </div>
        <button
          type="button"
          onclick={onClose}
          class="cursor-pointer rounded-xl p-2 text-[#8e8e93] transition-all hover:bg-[#f4f4f6] hover:text-[#17171c]"
        >
          <X class="size-5" />
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
            class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <label for="material-category" class="block font-bold text-[#17171c]"
              >Kategori Bahan</label
            >
            <div class="relative">
              <select
                id="material-category"
                bind:value={category_id}
                class="w-full cursor-pointer appearance-none rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 pr-10 text-xs text-[#17171c] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden"
              >
                {#each categories.filter((c) => c.type === 'RAW_MATERIAL') as cat}
                  <option value={cat.id}>{cat.name}</option>
                {/each}
              </select>
              <ChevronDown
                class="pointer-events-none absolute top-1/2 right-3.5 size-4 -translate-y-1/2 text-[#8e8e93]"
              />
            </div>
          </div>

          <div class="space-y-1.5">
            <label for="material-unit" class="block font-bold text-[#17171c]">Satuan Unit</label>
            <div class="relative">
              <select
                id="material-unit"
                bind:value={unit}
                class="w-full cursor-pointer appearance-none rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 pr-10 font-mono text-xs text-[#17171c] shadow-2xs transition-all hover:border-[#d1d1d6] hover:bg-white focus:border-[#17171c] focus:outline-hidden"
              >
                <option value="liter">liter</option>
                <option value="ml">ml</option>
                <option value="kg">kg</option>
                <option value="gram">gram</option>
                <option value="pcs">pcs</option>
                <option value="kaleng">kaleng</option>
                <option value="pack">pack</option>
              </select>
              <ChevronDown
                class="pointer-events-none absolute top-1/2 right-3.5 size-4 -translate-y-1/2 text-[#8e8e93]"
              />
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <label for="material-stock" class="block font-bold text-[#17171c]"
              >Stok Awal ({unit})</label
            >
            <input
              id="material-stock"
              type="number"
              min="0"
              bind:value={current_stock}
              class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 font-mono text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
            />
          </div>

          <div class="space-y-1.5">
            <label for="material-alert" class="block font-bold text-[#17171c]"
              >Peringatan Minimum ({unit})</label
            >
            <input
              id="material-alert"
              type="number"
              min="0"
              bind:value={min_stock_alert}
              class="w-full rounded-xl border border-[#e5e5ea] bg-[#f8f8fa] px-4 py-2.5 font-mono text-[#17171c] shadow-2xs transition-all hover:bg-white focus:border-[#17171c] focus:outline-hidden"
            />
          </div>
        </div>
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
          disabled={!name.trim() || current_stock < 0}
          onclick={handleSubmit}
          class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-full bg-[#17171c] py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black disabled:opacity-50"
        >
          <Plus class="size-4" />
          <span>Simpan Bahan</span>
        </button>
      </div>
    </div>
  </div>
{/if}
