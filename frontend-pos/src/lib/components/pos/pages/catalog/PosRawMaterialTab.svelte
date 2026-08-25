<script lang="ts">
  import { Search, Plus, Edit2, SlidersHorizontal, Trash2 } from 'lucide-svelte';
  import type { RawMaterial } from '../../../../types/pos';

  interface Props {
    rawMaterials: Array<RawMaterial & { stock_previous_day?: number }>;
    onOpenAddMaterial: () => void;
    onOpenEditMaterial: (mat: RawMaterial & { stock_previous_day?: number }) => void;
    onOpenOpname: (mat: RawMaterial & { stock_previous_day?: number }) => void;
    onDeleteMaterial: (id: string) => void;
  }

  let {
    rawMaterials = [],
    onOpenAddMaterial,
    onOpenEditMaterial,
    onOpenOpname,
    onDeleteMaterial,
  }: Props = $props();

  let searchQuery = $state('');

  let filteredRawMaterials = $derived(
    rawMaterials.filter((m) => {
      return (
        searchQuery.trim() === '' ||
        m.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (m.category_name && m.category_name.toLowerCase().includes(searchQuery.toLowerCase()))
      );
    })
  );
</script>

<div class="space-y-4 font-sans">
  <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
    <div class="relative flex-1">
      <Search class="w-4 h-4 text-[#75758a] absolute left-3.5 top-1/2 -translate-y-1/2" />
      <input
        type="text"
        bind:value={searchQuery}
        placeholder="Cari bahan baku, susu, sirup, biji kopi..."
        class="w-full pl-10 pr-4 py-2 bg-white border border-[#d9d9dd] rounded-full text-xs text-[#17171c] focus:border-[#17171c] focus:outline-hidden"
      />
    </div>

    <button
      type="button"
      onclick={onOpenAddMaterial}
      class="px-4 py-2 bg-[#17171c] hover:bg-black text-white rounded-full text-xs font-medium flex items-center justify-center gap-1.5 shrink-0 cursor-pointer shadow-xs"
    >
      <Plus class="w-3.5 h-3.5" />
      <span>+ Bahan Baku Baru</span>
    </button>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
    {#each filteredRawMaterials as mat}
      {@const isLow = mat.current_stock <= mat.min_stock_alert}
      <div class={`bg-white border rounded-2xl p-4 flex flex-col justify-between space-y-3 transition-all ${
        isLow ? 'border-[#e5484d]/40 ring-1 ring-[#e5484d]/20' : 'border-[#d9d9dd] hover:border-[#17171c]'
      }`}>
        <div class="space-y-1">
          <div class="flex items-center justify-between gap-2">
            <span class="text-[10px] font-mono text-[#75758a] uppercase truncate">{mat.category_name || 'Bahan Baku'}</span>
            <span class={`text-[9px] font-mono font-medium px-2 py-0.5 rounded-full shrink-0 ${
              isLow ? 'bg-[#ffefef] text-[#e5484d]' : 'bg-[#edfce9] text-[#003c33]'
            }`}>
              {isLow ? `Menipis (< ${mat.min_stock_alert})` : 'Stok Aman'}
            </span>
          </div>

          <h3 class="text-sm font-medium text-[#212121] tracking-tight">{mat.name}</h3>
          <div class="text-[10px] text-[#75758a] font-mono">
            Audit: {mat.last_adjusted_at || '-'}
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2 py-2 px-2.5 bg-[#fbfbfb] border border-[#d9d9dd]/60 rounded-xl text-center">
          <div>
            <div class="text-[9px] text-[#75758a] uppercase font-mono">Stok Fisik</div>
            <div class={`text-xs sm:text-sm font-medium font-mono mt-0.5 ${isLow ? 'text-[#e5484d]' : 'text-[#17171c]'}`}>
              {mat.current_stock} <span class="text-[10px] font-normal text-[#75758a]">{mat.unit}</span>
            </div>
          </div>

          <div>
            <div class="text-[9px] text-[#75758a] uppercase font-mono">Batas Minimum</div>
            <div class="text-xs font-medium font-mono text-[#75758a] mt-0.5">
              {mat.min_stock_alert} <span class="text-[10px] font-normal">{mat.unit}</span>
            </div>
          </div>
        </div>

        <div class="pt-1 flex items-center gap-2">
          <button
            type="button"
            onclick={() => onOpenOpname(mat)}
            class="flex-1 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-xl transition-all cursor-pointer text-center flex items-center justify-center gap-1"
          >
            <SlidersHorizontal class="w-3.5 h-3.5" />
            <span>Audit Opname</span>
          </button>

          <button
            type="button"
            onclick={() => onOpenEditMaterial(mat)}
            class="p-2 text-[#75758a] hover:text-[#17171c] hover:bg-[#eeece7] border border-[#d9d9dd] rounded-xl transition-all cursor-pointer shrink-0"
            title="Edit Bahan"
          >
            <Edit2 class="w-3.5 h-3.5" />
          </button>

          <button
            type="button"
            onclick={() => onDeleteMaterial(mat.id)}
            class="p-2 text-[#93939f] hover:text-[#e5484d] hover:bg-[#ffefef] border border-[#d9d9dd] rounded-xl transition-all cursor-pointer shrink-0"
            title="Hapus Bahan"
          >
            <Trash2 class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    {/each}
  </div>
</div>
