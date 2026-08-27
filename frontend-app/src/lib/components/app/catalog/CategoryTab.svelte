<script lang="ts">
  import { Package, Layers, Trash2 } from 'lucide-svelte';
  import type { CategoryItem } from '../../../types/app';

  interface Props {
    categories: CategoryItem[];
    menuItems?: unknown[];
    rawMaterials?: unknown[];
    onPromptDelete: (id: string, name: string) => void;
  }

  let { categories = [], onPromptDelete }: Props = $props();
</script>

<div class="space-y-6 font-sans">
  <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs">
    <div class="space-y-1">
      <h2 class="text-base sm:text-lg font-bold text-[#17171c]">Master Kategori Produk &amp; Bahan</h2>
      <p class="text-xs text-[#8e8e93]">
        Klasifikasi menu penjualan POS dan pengelompokan stok persediaan bahan baku outlet.
      </p>
    </div>
    <span class="px-3 py-1 rounded-full text-xs font-mono font-semibold bg-[#f4f4f6] text-[#17171c] self-start sm:self-auto">
      {categories.length} Kategori
    </span>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    {#each categories as cat}
      <div class="bg-white border border-[#e5e5ea] hover:border-[#17171c]/40 rounded-2xl p-4 sm:p-5 flex items-center justify-between gap-3 shadow-2xs hover:shadow-xs transition-all">
        <div class="flex items-center gap-3.5 min-w-0">
          <div class={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ${
            cat.type === 'MENU'
              ? 'bg-[#eff6ff] text-[#2563eb] border border-[#bfdbfe]'
              : 'bg-[#f4f4f6] text-[#17171c] border border-[#e5e5ea]'
          }`}>
            {#if cat.type === 'MENU'}
              <Package class="w-5 h-5" />
            {:else}
              <Layers class="w-5 h-5" />
            {/if}
          </div>
          <div class="truncate">
            <h4 class="font-bold text-sm text-[#17171c] truncate">{cat.name}</h4>
            <div class="text-[10.5px] text-[#8e8e93] font-mono truncate">{cat.id}</div>
          </div>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
          <div class="flex flex-col items-end gap-1 font-mono text-right">
            <span class={`text-[9.5px] font-semibold px-2 py-0.5 rounded-full ${
              cat.type === 'MENU'
                ? 'bg-[#eff6ff] text-[#2563eb]'
                : 'bg-[#f4f4f6] text-[#17171c]'
            }`}>
              {cat.type === 'MENU' ? 'Menu POS' : 'Bahan Baku'}
            </span>
            <span class="text-[11px] text-[#686873]">
              {cat.item_count} item
            </span>
          </div>

          <button
            type="button"
            onclick={() => onPromptDelete(cat.id, cat.name)}
            class="p-2 text-[#8e8e93] hover:text-[#e5484d] hover:bg-[#fef2f2] border border-[#e5e5ea] hover:border-[#fecaca] rounded-xl transition-all cursor-pointer"
            title="Hapus Kategori"
          >
            <Trash2 class="w-4 h-4" />
          </button>
        </div>
      </div>
    {/each}
  </div>
</div>
