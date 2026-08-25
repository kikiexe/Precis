<script lang="ts">
  import { Package, Layers, Trash2 } from 'lucide-svelte';
  import type { CategoryItem } from '../../../types/app';

  interface Props {
    categories: CategoryItem[];
    onPromptDelete: (id: string, name: string) => void;
  }

  let { categories = [], onPromptDelete }: Props = $props();
</script>

<div class="space-y-3 font-sans">
  <div class="flex items-center justify-between pb-1">
    <div>
      <h2 class="text-sm font-medium text-[#212121]">Master Kategori Produk &amp; Bahan Baku</h2>
      <p class="text-[11px] text-[#75758a]">Pengelompokan menu POS dan klasifikasi stok bahan di bar</p>
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
    {#each categories as cat}
      <div class="bg-white border border-[#d9d9dd] rounded-2xl p-3.5 flex items-center justify-between gap-3 hover:border-[#17171c]/40 transition-all">
        <div class="flex items-center gap-3 min-w-0">
          <span class={`p-2 rounded-xl shrink-0 ${
            cat.type === 'MENU' ? 'bg-[#f1f5ff] text-[#1863dc]' : 'bg-[#eeece7] text-[#17171c]'
          }`}>
            {#if cat.type === 'MENU'}
              <Package class="w-4 h-4" />
            {:else}
              <Layers class="w-4 h-4" />
            {/if}
          </span>
          <div class="truncate">
            <div class="font-medium text-xs text-[#212121] truncate">{cat.name}</div>
            <div class="text-[10px] text-[#75758a] font-mono">{cat.id}</div>
          </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <div class="flex flex-col items-end gap-1 font-mono text-right">
            <span class={`text-[9px] font-medium px-2 py-0.5 rounded-full ${
              cat.type === 'MENU' ? 'bg-[#f1f5ff] text-[#1863dc]' : 'bg-[#eeece7] text-[#17171c]'
            }`}>
              {cat.type === 'MENU' ? 'Menu POS' : 'Bahan Baku'}
            </span>
            <span class="text-[10px] text-[#616161]">
              {cat.item_count} item
            </span>
          </div>

          <button
            type="button"
            onclick={() => onPromptDelete(cat.id, cat.name)}
            class="p-2 text-[#93939f] hover:text-[#e5484d] hover:bg-[#ffefef] rounded-xl transition-all cursor-pointer"
            title="Hapus Kategori"
          >
            <Trash2 class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    {/each}
  </div>
</div>
