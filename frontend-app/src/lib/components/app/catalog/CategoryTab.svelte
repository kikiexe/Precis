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
  <div
    class="flex flex-col justify-between gap-4 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs sm:flex-row sm:items-center sm:rounded-3xl sm:p-6"
  >
    <div class="space-y-1">
      <h2 class="text-base font-bold text-[#17171c] sm:text-lg">
        Master Kategori Produk &amp; Bahan
      </h2>
      <p class="text-xs text-[#8e8e93]">
        Klasifikasi menu penjualan POS dan pengelompokan stok persediaan bahan baku outlet.
      </p>
    </div>
    <span
      class="self-start rounded-full bg-[#f4f4f6] px-3 py-1 font-mono text-xs font-semibold text-[#17171c] sm:self-auto"
    >
      {categories.length} Kategori
    </span>
  </div>

  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    {#each categories as cat}
      <div
        class="flex items-center justify-between gap-3 rounded-2xl border border-[#e5e5ea] bg-white p-4 shadow-2xs transition-all hover:border-[#17171c]/40 hover:shadow-xs sm:p-5"
      >
        <div class="flex min-w-0 items-center gap-3.5">
          <div
            class={`flex size-10 shrink-0 items-center justify-center rounded-xl ${
              cat.type === 'MENU'
                ? 'border border-[#bfdbfe] bg-[#eff6ff] text-[#2563eb]'
                : 'border border-[#e5e5ea] bg-[#f4f4f6] text-[#17171c]'
            }`}
          >
            {#if cat.type === 'MENU'}
              <Package class="size-5" />
            {:else}
              <Layers class="size-5" />
            {/if}
          </div>
          <div class="truncate">
            <h4 class="truncate text-sm font-bold text-[#17171c]">{cat.name}</h4>
            <div class="truncate font-mono text-[10.5px] text-[#8e8e93]">{cat.id}</div>
          </div>
        </div>

        <div class="flex shrink-0 items-center gap-2.5">
          <div class="flex flex-col items-end gap-1 text-right font-mono">
            <span
              class={`rounded-full px-2 py-0.5 text-[9.5px] font-semibold ${
                cat.type === 'MENU' ? 'bg-[#eff6ff] text-[#2563eb]' : 'bg-[#f4f4f6] text-[#17171c]'
              }`}
            >
              {cat.type === 'MENU' ? 'Menu POS' : 'Bahan Baku'}
            </span>
            <span class="text-[11px] text-[#686873]">
              {cat.item_count} item
            </span>
          </div>

          <button
            type="button"
            onclick={() => onPromptDelete(cat.id, cat.name)}
            class="cursor-pointer rounded-xl border border-[#e5e5ea] p-2 text-[#8e8e93] transition-all hover:border-[#fecaca] hover:bg-[#fef2f2] hover:text-[#e5484d]"
            title="Hapus Kategori"
          >
            <Trash2 class="size-4" />
          </button>
        </div>
      </div>
    {/each}
  </div>
</div>
