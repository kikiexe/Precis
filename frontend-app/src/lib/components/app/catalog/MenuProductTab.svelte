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
      const matchCat =
        selectedCategoryFilter === 'ALL' || item.category_id === selectedCategoryFilter;
      return matchSearch && matchCat;
    })
  );

  let menuCategories = $derived(categories.filter((c) => c.type === 'MENU'));
</script>

<div class="space-y-5 font-sans">
  <!-- Search & Filters Container -->
  <div
    class="space-y-3 rounded-2xl border border-[#e5e5ea] bg-white p-3.5 shadow-2xs sm:rounded-3xl sm:p-4 lg:flex lg:items-center lg:justify-between lg:gap-4 lg:space-y-0"
  >
    <!-- Search Input -->
    <div class="relative w-full shrink-0 lg:w-72 xl:w-80">
      <Search
        class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-[#8e8e93]"
      />
      <input
        type="text"
        bind:value={searchQuery}
        placeholder="Cari nama menu jualan..."
        class="w-full rounded-full border border-[#e5e5ea] bg-[#f8f8fa] py-2 pr-9 pl-10 text-xs text-[#17171c] placeholder-[#8e8e93] shadow-2xs transition-all hover:bg-[#f2f2f5] focus:border-[#17171c] focus:outline-hidden"
      />
      {#if searchQuery}
        <button
          type="button"
          onclick={() => (searchQuery = '')}
          class="absolute top-1/2 right-3 -translate-y-1/2 cursor-pointer rounded-full p-0.5 text-[#8e8e93] hover:text-[#17171c]"
          title="Hapus pencarian"
        >
          <X class="h-3.5 w-3.5" />
        </button>
      {/if}
    </div>

    <!-- Category Filter Capsules -->
    <div class="w-full min-w-0 overflow-hidden lg:flex-1">
      <div
        class="no-scrollbar flex w-full items-center gap-1.5 overflow-x-auto py-0.5 lg:justify-end"
      >
        <button
          type="button"
          onclick={() => (selectedCategoryFilter = 'ALL')}
          class={`shrink-0 cursor-pointer rounded-full px-4 py-2 text-xs font-medium transition-all ${
            selectedCategoryFilter === 'ALL'
              ? 'bg-[#17171c] font-semibold text-white shadow-xs'
              : 'border border-[#e5e5ea] bg-[#f4f4f6] text-[#686873] hover:bg-[#ececee] hover:text-[#17171c]'
          }`}
        >
          Semua ({menuItems.length})
        </button>
        {#each menuCategories as cat}
          {@const count = menuItems.filter((m) => m.category_id === cat.id).length}
          <button
            type="button"
            onclick={() => (selectedCategoryFilter = cat.id)}
            class={`shrink-0 cursor-pointer rounded-full px-4 py-2 text-xs font-medium whitespace-nowrap transition-all ${
              selectedCategoryFilter === cat.id
                ? 'bg-[#17171c] font-semibold text-white shadow-xs'
                : 'border border-[#e5e5ea] bg-[#f4f4f6] text-[#686873] hover:bg-[#ececee] hover:text-[#17171c]'
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
    <div
      class="rounded-3xl border border-[#e5e5ea] bg-white p-12 text-center text-[#8e8e93] shadow-2xs"
    >
      <p class="font-mono text-xs font-medium">Memuat data menu jualan...</p>
    </div>
  {:else if filteredMenuItems.length === 0}
    <div class="space-y-2 rounded-3xl border border-[#e5e5ea] bg-white p-12 text-center shadow-2xs">
      <p class="text-xs font-bold text-[#17171c]">Tidak ada menu yang sesuai</p>
      <p class="text-[11px] text-[#8e8e93]">
        {searchQuery
          ? `Tidak ditemukan menu dengan kata kunci "${searchQuery}".`
          : 'Belum ada menu di kategori ini.'}
      </p>
    </div>
  {:else}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {#each filteredMenuItems as item}
        <div
          class="group flex flex-col justify-between space-y-4 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs transition-all hover:border-[#17171c]/40 hover:shadow-xs"
        >
          <div class="space-y-2">
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-1">
                <span class="font-mono text-[10px] font-semibold text-[#8e8e93] uppercase">
                  {item.category_name ||
                    categories.find((c) => c.id === item.category_id)?.name ||
                    'Menu'}
                </span>
                <h4 class="text-sm leading-snug font-bold text-[#17171c] group-hover:text-black">
                  {item.name}
                </h4>
              </div>
              <button
                type="button"
                onclick={() => onPromptDelete(item.id, item.name)}
                class="shrink-0 cursor-pointer rounded-xl p-2 text-[#8e8e93] opacity-80 transition-all group-hover:opacity-100 hover:bg-[#fef2f2] hover:text-[#dc2626]"
                title="Hapus Menu"
              >
                <Trash2 class="h-4 w-4" />
              </button>
            </div>

            {#if item.description}
              <p class="line-clamp-2 text-xs leading-relaxed text-[#686873]">
                {item.description}
              </p>
            {/if}
          </div>

          <div class="flex items-center justify-between border-t border-[#f2f2f4] pt-3">
            <span class="font-mono text-sm font-bold text-[#17171c]">
              {formatRupiah(item.price)}
            </span>
            <button
              type="button"
              onclick={() => onToggleAvailability(item)}
              class={`cursor-pointer rounded-full px-3 py-1 text-xs font-semibold transition-all ${
                item.is_available
                  ? 'border border-[#a7f3d0] bg-[#ecfdf5] text-[#059669] hover:bg-[#d1fae5]'
                  : 'border border-[#fecaca] bg-[#fef2f2] text-[#dc2626] hover:bg-[#fee2e2]'
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
