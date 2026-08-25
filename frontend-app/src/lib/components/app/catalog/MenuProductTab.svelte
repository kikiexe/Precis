<script lang="ts">
  import { Search, Trash2 } from 'lucide-svelte';
  import type { CategoryItem, ProductMenuItem } from '../../../types/app';
  import { formatRupiah } from '../../../utils/formatters';

  interface Props {
    menuItems: ProductMenuItem[];
    categories: CategoryItem[];
    isLoading: boolean;
    onToggleAvailability: (item: ProductMenuItem) => Promise<void>;
    onPromptDelete: (id: string, name: string) => void;
  }

  let {
    menuItems = [],
    categories = [],
    isLoading = false,
    onToggleAvailability,
    onPromptDelete,
  }: Props = $props();

  let searchQuery = $state('');
  let selectedCategoryFilter = $state('ALL');

  let filteredMenuItems = $derived(
    menuItems.filter((item) => {
      const matchSearch =
        searchQuery.trim() === '' ||
        item.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (item.description && item.description.toLowerCase().includes(searchQuery.toLowerCase()));
      const matchCat = selectedCategoryFilter === 'ALL' || item.category_id === selectedCategoryFilter;
      return matchSearch && matchCat;
    })
  );
</script>

<div class="space-y-4 font-sans">
  <!-- Search & Filters -->
  <div class="bg-white border border-[#d9d9dd] rounded-[20px] p-3 sm:p-4 flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center justify-between">
    <div class="relative flex-1 min-w-0">
      <Search class="w-4 h-4 text-[#93939f] absolute left-3.5 top-1/2 -translate-y-1/2" />
      <input
        type="text"
        bind:value={searchQuery}
        placeholder="Cari nama menu jualan..."
        class="w-full bg-[#eeece7]/40 pl-10 pr-4 py-2 text-xs rounded-full border border-[#d9d9dd] placeholder-[#93939f] text-[#212121] focus:border-[#17171c] focus:outline-hidden transition-all"
      />
    </div>

    <div class="flex items-center gap-1 overflow-x-auto no-scrollbar bg-[#eeece7]/60 p-1 rounded-full border border-[#d9d9dd] shrink-0 max-w-full">
      <button
        type="button"
        onclick={() => (selectedCategoryFilter = 'ALL')}
        class={`px-3 py-1 text-xs rounded-full transition-all cursor-pointer shrink-0 ${
          selectedCategoryFilter === 'ALL'
            ? 'bg-[#17171c] text-white font-medium'
            : 'text-[#616161] hover:text-[#212121]'
        }`}
      >
        Semua
      </button>
      {#each categories.filter((c) => c.type === 'MENU') as cat}
        <button
          type="button"
          onclick={() => (selectedCategoryFilter = cat.id)}
          class={`px-3 py-1 text-xs rounded-full transition-all cursor-pointer shrink-0 ${
            selectedCategoryFilter === cat.id
              ? 'bg-[#17171c] text-white font-medium'
              : 'text-[#616161] hover:text-[#212121]'
          }`}
        >
          {cat.name}
        </button>
      {/each}
    </div>
  </div>

  <!-- Menu Items Square-Grid -->
  {#if isLoading}
    <div class="bg-white border border-[#d9d9dd] rounded-3xl p-12 text-center text-[#75758a]">
      <p class="text-xs font-medium">Memuat data menu jualan...</p>
    </div>
  {:else}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-4">
      {#each filteredMenuItems as item}
        <div class="bg-white border border-[#d9d9dd] rounded-2xl p-3 sm:p-4 flex flex-col justify-between hover:border-[#17171c] transition-all min-h-[160px] space-y-2">
          <div class="space-y-1">
            <div class="flex items-center justify-between gap-1">
              <span class="text-[9px] font-mono text-[#75758a] uppercase truncate">{item.category_name}</span>
              <div class="flex items-center gap-1.5">
                <span
                  class={`w-2 h-2 rounded-full shrink-0 ${item.is_available ? 'bg-[#00875a]' : 'bg-[#e5484d]'}`}
                  title={item.is_available ? 'Tersedia' : 'Habis'}
                ></span>
                <button
                  type="button"
                  onclick={() => onPromptDelete(item.id, item.name)}
                  class="text-[#93939f] hover:text-[#e5484d] p-0.5 rounded hover:bg-[#ffefef] transition-all cursor-pointer"
                  title="Hapus Menu"
                >
                  <Trash2 class="w-3 h-3" />
                </button>
              </div>
            </div>

            <h3 class="text-xs sm:text-sm font-medium text-[#212121] tracking-tight line-clamp-2">{item.name}</h3>
            {#if item.description}
              <p class="text-[10px] text-[#75758a] line-clamp-1">{item.description}</p>
            {/if}
          </div>

          <div class="pt-2 border-t border-[#f2f2f2] flex flex-col gap-1.5">
            <div class="text-xs sm:text-sm font-medium font-mono text-[#17171c]">
              {formatRupiah(item.price)}
            </div>

            <button
              type="button"
              onclick={() => onToggleAvailability(item)}
              class={`w-full py-1 text-[10px] sm:text-[11px] font-medium rounded-lg border transition-all cursor-pointer text-center ${
                item.is_available
                  ? 'border-[#d9d9dd] bg-[#fbfbfb] text-[#616161] hover:bg-[#ffefef] hover:text-[#e5484d] hover:border-[#e5484d]/30'
                  : 'border-[#003c33] bg-[#edfce9] text-[#003c33]'
              }`}
            >
              {item.is_available ? 'Set Habis' : 'Set Tersedia'}
            </button>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>
