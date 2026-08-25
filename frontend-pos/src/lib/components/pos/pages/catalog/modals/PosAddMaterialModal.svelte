<script lang="ts">
  import { X } from 'lucide-svelte';
  import type { RawMaterial } from '../../../../../types/pos';

  interface Props {
    isOpen: boolean;
    editingMaterial: (RawMaterial & { stock_previous_day?: number }) | null;
    rawMaterialCategories: Array<{ id: string; name: string }>;
    onClose: () => void;
    onSave: (materialData: {
      name: string;
      category_id: string;
      category_name: string;
      current_stock: number;
      min_stock_alert: number;
      unit: string;
    }) => void;
  }

  let {
    isOpen,
    editingMaterial,
    rawMaterialCategories = [],
    onClose,
    onSave,
  }: Props = $props();

  let name = $state('');
  let category_id = $state('cat-dairy');
  let current_stock = $state(10);
  let min_stock_alert = $state(5);
  let unit = $state('liter');

  $effect(() => {
    if (isOpen) {
      if (editingMaterial) {
        name = editingMaterial.name;
        category_id = editingMaterial.category_id;
        current_stock = editingMaterial.current_stock;
        min_stock_alert = editingMaterial.min_stock_alert;
        unit = editingMaterial.unit;
      } else {
        name = '';
        category_id = 'cat-dairy';
        current_stock = 10;
        min_stock_alert = 5;
        unit = 'liter';
      }
    }
  });

  function handleSubmit() {
    if (!name.trim() || current_stock < 0) return;
    const catObj = rawMaterialCategories.find((c) => c.id === category_id);
    const categoryName = catObj ? catObj.name : 'Bahan Baku';

    onSave({
      name: name.trim(),
      category_id,
      category_name: categoryName,
      current_stock: Number(current_stock),
      min_stock_alert: Number(min_stock_alert),
      unit,
    });
    onClose();
  }
</script>

{#if isOpen}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-3xl w-full max-w-md p-6 space-y-4 shadow-xl">
      <div class="flex items-center justify-between border-b border-[#e5e5e5] pb-3">
        <h3 class="text-sm font-semibold text-[#17171c]">
          {editingMaterial ? 'Edit Bahan Baku' : 'Tambah Bahan Baku Baru'}
        </h3>
        <button type="button" onclick={onClose} class="text-[#75758a] hover:text-[#17171c] cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="space-y-3 text-xs">
        <div class="space-y-1">
          <label for="pos-material-name" class="font-medium text-[#17171c]">Nama Bahan Baku / Item</label>
          <input
            id="pos-material-name"
            type="text"
            bind:value={name}
            placeholder="Contoh: Fresh Milk Diamond 1L"
            class="w-full px-3.5 py-2 bg-white border border-[#d9d9dd] rounded-full text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label for="pos-material-cat" class="font-medium text-[#17171c]">Kategori</label>
            <select
              id="pos-material-cat"
              bind:value={category_id}
              class="w-full px-3.5 py-2 bg-white border border-[#d9d9dd] rounded-full text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
            >
              {#each rawMaterialCategories as cat}
                <option value={cat.id}>{cat.name}</option>
              {/each}
            </select>
          </div>

          <div class="space-y-1">
            <label for="pos-material-unit" class="font-medium text-[#17171c]">Satuan Unit</label>
            <select
              id="pos-material-unit"
              bind:value={unit}
              class="w-full px-3.5 py-2 bg-white border border-[#d9d9dd] rounded-full text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
            >
              <option value="liter">liter</option>
              <option value="ml">ml</option>
              <option value="kg">kg</option>
              <option value="gram">gram</option>
              <option value="pcs">pcs</option>
              <option value="pack">pack</option>
              <option value="botol">botol</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label for="pos-material-stock" class="font-medium text-[#17171c]">Stok Fisik Saat Ini</label>
            <input
              id="pos-material-stock"
              type="number"
              bind:value={current_stock}
              class="w-full px-3.5 py-2 bg-white border border-[#d9d9dd] rounded-full font-mono text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>

          <div class="space-y-1">
            <label for="pos-material-min-alert" class="font-medium text-[#17171c]">Batas Peringatan Minimum</label>
            <input
              id="pos-material-min-alert"
              type="number"
              bind:value={min_stock_alert}
              class="w-full px-3.5 py-2 bg-white border border-[#d9d9dd] rounded-full font-mono text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
            />
          </div>
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
          class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full cursor-pointer"
        >
          {editingMaterial ? 'Perbarui Bahan' : 'Simpan Bahan'}
        </button>
      </div>
    </div>
  </div>
{/if}
