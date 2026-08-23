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
  class={`bg-[#17171c] text-white border-r border-[#262626] flex flex-col justify-between select-none transition-all duration-200 shrink-0 relative z-20 font-sans ${
    isCollapsed ? 'w-16' : 'w-56'
  }`}
>
  <div>
    <!-- Brand Header -->
    <div class="h-14 border-b border-[#262626] flex items-center px-3.5 gap-3 overflow-hidden">
      <div class="w-7 h-7 bg-white text-[#17171c] flex items-center justify-center font-medium text-xs rounded-[8px] shrink-0">
        P
      </div>
      {#if !isCollapsed}
        <div class="truncate">
          <div class="font-medium text-xs tracking-tight text-white uppercase">PRÉCIS POS</div>
          <div class="text-[10px] font-mono text-[#93939f]">Kiosk Terminal</div>
        </div>
      {/if}
    </div>

    <!-- Navigation List -->
    <nav class="p-2.5 space-y-1">
      {#each navItems as item}
        {@const Icon = item.icon}
        <button
          type="button"
          onclick={() => onSelectPage(item.id)}
          title={item.label}
          class={`w-full flex items-center gap-3 px-3 py-2.5 text-xs transition-all cursor-pointer rounded-[12px] ${
            activePage === item.id
              ? 'bg-white/15 text-white font-medium'
              : 'text-[#93939f] hover:text-white hover:bg-white/10'
          } ${isCollapsed ? 'justify-center px-0' : ''}`}
        >
          <Icon class={`w-4 h-4 shrink-0 ${activePage === item.id ? 'text-[#edfce9]' : 'text-[#93939f]'}`} />
          {#if !isCollapsed}
            <span class="truncate">{item.label}</span>
          {/if}
        </button>
      {/each}
    </nav>
  </div>

  <!-- Bottom: Collapse Toggle & Branch Info -->
  <div class="p-2.5 border-t border-[#262626] space-y-2">
    {#if !isCollapsed}
      <div class="px-3 py-2 bg-white/5 rounded-[10px] text-[10px] font-mono text-[#93939f] flex items-center gap-2 truncate border border-white/5">
        <Store class="w-3.5 h-3.5 text-[#1863dc] shrink-0" />
        <span class="truncate">Outlet Sleman #01</span>
      </div>
    {/if}

    <button
      type="button"
      onclick={onToggleCollapse}
      class="w-full flex items-center justify-center py-2 bg-white/10 hover:bg-white/15 text-[#93939f] hover:text-white transition-all cursor-pointer text-xs rounded-full"
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
