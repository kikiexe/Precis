<script lang="ts">
  import { Search, Trash2, X } from 'lucide-svelte';
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

  let menuCategories = $derived(categories.filter((c) => c.type === 'MENU'));
</script>

<div class="space-y-5 font-sans">
  <!-- Search & Filters Container -->
  <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-3.5 sm:p-4 space-y-3 lg:space-y-0 lg:flex lg:items-center lg:justify-between lg:gap-4 shadow-2xs">
    <!-- Search Input -->
    <div class="relative w-full lg:w-72 xl:w-80 shrink-0">
      <Search class="w-4 h-4 text-[#8e8e93] absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
      <input
        type="text"
        bind:value={searchQuery}
        placeholder="Cari nama menu jualan..."
        class="w-full bg-[#f8f8fa] hover:bg-[#f2f2f5] pl-10 pr-9 py-2 text-xs rounded-full border border-[#e5e5ea] placeholder-[#8e8e93] text-[#17171c] focus:border-[#17171c] focus:outline-hidden transition-all shadow-2xs"
      />
      {#if searchQuery}
        <button
          type="button"
          onclick={() => (searchQuery = '')}
          class="absolute right-3 top-1/2 -translate-y-1/2 text-[#8e8e93] hover:text-[#17171c] p-0.5 rounded-full cursor-pointer"
          title="Hapus pencarian"
        >
          <X class="w-3.5 h-3.5" />
        </button>
      {/if}
    </div>

    <!-- Category Filter Capsules -->
    <div class="w-full lg:flex-1 min-w-0 overflow-hidden">
      <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5 w-full lg:justify-end">
        <button
          type="button"
          onclick={() => (selectedCategoryFilter = 'ALL')}
          class={`px-4 py-2 text-xs rounded-full transition-all cursor-pointer shrink-0 font-medium ${
            selectedCategoryFilter === 'ALL'
              ? 'bg-[#17171c] text-white shadow-xs font-semibold'
              : 'bg-[#f4f4f6] text-[#686873] hover:text-[#17171c] hover:bg-[#ececee] border border-[#e5e5ea]'
          }`}
        >
          Semua ({menuItems.length})
        </button>
        {#each menuCategories as cat}
          {@const count = menuItems.filter((m) => m.category_id === cat.id).length}
          <button
            type="button"
            onclick={() => (selectedCategoryFilter = cat.id)}
            class={`px-4 py-2 text-xs rounded-full transition-all cursor-pointer shrink-0 font-medium whitespace-nowrap ${
              selectedCategoryFilter === cat.id
                ? 'bg-[#17171c] text-white shadow-xs font-semibold'
                : 'bg-[#f4f4f6] text-[#686873] hover:text-[#17171c] hover:bg-[#ececee] border border-[#e5e5ea]'
            }`}
          >
            {cat.name} ({count})
          </button>
        {/each}
      </div>
    </div>
  </div>

  <!-- Menu Items Grid -->
  {#if isLoading}
    <div class="bg-white border border-[#e5e5ea] rounded-3xl p-12 text-center text-[#8e8e93] shadow-2xs">
      <p class="text-xs font-medium font-mono">Memuat data menu jualan...</p>
    </div>
  {:else if filteredMenuItems.length === 0}
    <div class="bg-white border border-[#e5e5ea] rounded-3xl p-12 text-center space-y-2 shadow-2xs">
      <p class="text-xs font-bold text-[#17171c]">Tidak ada menu yang sesuai</p>
      <p class="text-[11px] text-[#8e8e93]">
        {searchQuery ? `Tidak ditemukan menu dengan kata kunci "${searchQuery}".` : 'Belum ada menu di kategori ini.'}
      </p>
    </div>
  {:else}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      {#each filteredMenuItems as item}
        <div class="bg-white border border-[#e5e5ea] hover:border-[#17171c]/40 rounded-2xl p-5 shadow-2xs hover:shadow-xs transition-all flex flex-col justify-between group space-y-4">
          <div class="space-y-2">
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-1">
                <span class="text-[10px] font-mono uppercase font-semibold text-[#8e8e93]">
                  {item.category_name || categories.find((c) => c.id === item.category_id)?.name || 'Menu'}
                </span>
                <h4 class="font-bold text-sm text-[#17171c] group-hover:text-black leading-snug">
                  {item.name}
                </h4>
              </div>
              <button
                type="button"
                onclick={() => onPromptDelete(item.id, item.name)}
                class="p-2 text-[#8e8e93] hover:text-[#dc2626] hover:bg-[#fef2f2] rounded-xl transition-all cursor-pointer opacity-80 group-hover:opacity-100 shrink-0"
                title="Hapus Menu"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>

            {#if item.description}
              <p class="text-xs text-[#686873] line-clamp-2 leading-relaxed">
                {item.description}
              </p>
            {/if}
          </div>

          <div class="pt-3 border-t border-[#f2f2f4] flex items-center justify-between">
            <span class="font-mono font-bold text-sm text-[#17171c]">
              {formatRupiah(item.price)}
            </span>
            <button
              type="button"
              onclick={() => onToggleAvailability(item)}
              class={`px-3 py-1 rounded-full text-xs font-semibold cursor-pointer transition-all ${
                item.is_available
                  ? 'bg-[#ecfdf5] text-[#059669] border border-[#a7f3d0] hover:bg-[#d1fae5]'
                  : 'bg-[#fef2f2] text-[#dc2626] border border-[#fecaca] hover:bg-[#fee2e2]'
              }`}
            >
              {item.is_available ? 'Tersedia' : 'Habis'}
            </button>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>
