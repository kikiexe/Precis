<script lang="ts">
  import { ShoppingBag, Receipt, Calculator, Package, UserCircle, ChevronLeft, ChevronRight, Store } from 'lucide-svelte';

  interface Props {
    activePage: 'penjualan' | 'transaksi' | 'settlement' | 'produk' | 'profil';
    isCollapsed: boolean;
    onSelectPage: (page: 'penjualan' | 'transaksi' | 'settlement' | 'produk' | 'profil') => void;
    onToggleCollapse: () => void;
  }

  let { activePage = 'penjualan', isCollapsed = false, onSelectPage, onToggleCollapse }: Props = $props();

  const navItems = [
    { id: 'penjualan' as const, label: 'Penjualan', icon: ShoppingBag },
    { id: 'transaksi' as const, label: 'Transaksi', icon: Receipt },
    { id: 'settlement' as const, label: 'Settlement', icon: Calculator },
    { id: 'produk' as const, label: 'Produk', icon: Package },
    { id: 'profil' as const, label: 'Profil & Kiosk', icon: UserCircle },
  ];
</script>

<aside
  class={`bg-[#161616] text-white border-r border-[#262626] flex flex-col justify-between select-none transition-all duration-200 shrink-0 relative z-20 ${
    isCollapsed ? 'w-16' : 'w-56'
  }`}
>
  <div>
    <!-- Brand Header -->
    <div class="h-14 border-b border-[#262626] flex items-center px-3 gap-3 overflow-hidden">
      <div class="w-8 h-8 bg-[#0f62fe] text-white flex items-center justify-center font-bold text-sm shrink-0">
        P
      </div>
      {#if !isCollapsed}
        <div class="truncate">
          <div class="font-bold text-xs tracking-tight text-white uppercase font-display">PRÉCIS POS</div>
          <div class="text-[10px] font-mono text-[#8c8c8c]">Kiosk Terminal</div>
        </div>
      {/if}
    </div>

    <!-- Navigation List (Carbon Style Nav Tabs) -->
    <nav class="p-2 space-y-1">
      {#each navItems as item}
        {@const Icon = item.icon}
        <button
          type="button"
          onclick={() => onSelectPage(item.id)}
          title={item.label}
          class={`w-full flex items-center gap-3 px-3 py-3 text-xs transition-colors cursor-pointer border-l-2 ${
            activePage === item.id
              ? 'bg-[#262626] text-white border-[#0f62fe] font-semibold'
              : 'text-[#c6c6c6] hover:text-white hover:bg-[#262626]/50 border-transparent'
          } ${isCollapsed ? 'justify-center px-0' : ''}`}
        >
          <Icon class="w-4 h-4 shrink-0 text-[#0f62fe]" />
          {#if !isCollapsed}
            <span class="truncate">{item.label}</span>
          {/if}
        </button>
      {/each}
    </nav>
  </div>

  <!-- Bottom: Collapse Toggle & Branch Info -->
  <div class="p-2 border-t border-[#262626] space-y-2">
    {#if !isCollapsed}
      <div class="px-2 py-1.5 bg-[#262626] text-[10px] font-mono text-[#8c8c8c] flex items-center gap-1.5 truncate">
        <Store class="w-3 h-3 text-[#0f62fe] shrink-0" />
        <span class="truncate">Outlet Sleman #01</span>
      </div>
    {/if}

    <button
      type="button"
      onclick={onToggleCollapse}
      class="w-full flex items-center justify-center py-2 bg-[#262626] hover:bg-[#393939] text-[#8c8c8c] hover:text-white transition-colors cursor-pointer text-xs"
      aria-label="Toggle Sidebar"
    >
      {#if isCollapsed}
        <ChevronRight class="w-4 h-4" />
      {:else}
        <div class="flex items-center gap-1.5 text-[11px] font-mono">
          <ChevronLeft class="w-3.5 h-3.5" />
          <span>Sembunyikan</span>
        </div>
      {/if}
    </button>
  </div>
</aside>
