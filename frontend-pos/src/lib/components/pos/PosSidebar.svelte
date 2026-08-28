<script lang="ts">
  import {
    LayoutGrid,
    Clock3,
    Coins,
    DollarSign,
    Boxes,
    UtensilsCrossed,
    Settings,
    ChevronLeft,
    ChevronRight,
    Store,
  } from 'lucide-svelte';
  import type { PosPage } from '../../types/pos';

  interface Props {
    activePage: PosPage;
    isCollapsed: boolean;
    onSelectPage: (page: PosPage) => void;
    onToggleCollapse: () => void;
  }

  let { activePage = 'penjualan', isCollapsed = false, onSelectPage, onToggleCollapse }: Props = $props();

  const navItems = [
    { id: 'penjualan' as const, label: 'POINT OF SALE', icon: LayoutGrid },
    { id: 'transaksi' as const, label: 'AKTIVITAS', icon: Clock3 },
    { id: 'shift' as const, label: 'SHIFT KASIR', icon: Coins },
    { id: 'settlement' as const, label: 'SETTLEMENT', icon: DollarSign },
    { id: 'menu' as const, label: 'MENU JUALAN', icon: UtensilsCrossed },
    { id: 'inventori' as const, label: 'INVENTORI', icon: Boxes },
    { id: 'profil' as const, label: 'PENGATURAN', icon: Settings },
  ];
</script>

<aside
  class={`bg-[#121215] text-white flex flex-col justify-between select-none transition-all duration-200 shrink-0 relative z-20 font-sans border-r border-zinc-800/80 shadow-md ${
    isCollapsed ? 'w-16' : 'w-56'
  }`}
>
  <div>
    <!-- Brand Header -->
    <div class="h-14 border-b border-zinc-800 flex items-center px-4 gap-3 overflow-hidden bg-[#18181d]">
      <div class="w-8 h-8 rounded-lg bg-zinc-800 flex items-center justify-center border border-zinc-700 shrink-0 font-bold text-sm tracking-wider text-white">
        P
      </div>
      {#if !isCollapsed}
        <div class="truncate">
          <div class="font-bold text-xs tracking-wider text-white uppercase">PRÉCIS POS</div>
          <div class="text-[10px] font-mono text-zinc-400">Kiosk System</div>
        </div>
      {/if}
    </div>

    <!-- Navigation List (Moka Layout in Dark) -->
    <nav class="p-2 space-y-1">
      {#each navItems as item}
        {@const Icon = item.icon}
        <button
          type="button"
          onclick={() => onSelectPage(item.id)}
          title={item.label}
          class={`w-full flex items-center gap-3 px-3.5 py-3 text-xs font-semibold tracking-wide transition-all cursor-pointer rounded-lg ${
            activePage === item.id
              ? 'bg-zinc-800 text-white shadow-xs border border-zinc-700'
              : 'text-zinc-400 hover:text-white hover:bg-zinc-800/50'
          } ${isCollapsed ? 'justify-center px-0' : ''}`}
        >
          <Icon class={`w-4.5 h-4.5 shrink-0 ${activePage === item.id ? 'text-white' : 'text-zinc-400'}`} />
          {#if !isCollapsed}
            <span class="truncate">{item.label}</span>
          {/if}
        </button>
      {/each}
    </nav>
  </div>

  <!-- Bottom: Branch & Collapse Toggle -->
  <div class="p-2 border-t border-zinc-800 bg-[#18181d] space-y-2">
    {#if !isCollapsed}
      <div class="px-3 py-2 bg-zinc-800/80 rounded-lg text-[11px] font-medium text-zinc-200 flex items-center gap-2 truncate border border-zinc-700/60">
        <Store class="w-3.5 h-3.5 text-zinc-400 shrink-0" />
        <span class="truncate">Précis Coffee - Outlet 01</span>
      </div>
    {/if}

    <button
      type="button"
      onclick={onToggleCollapse}
      class="w-full flex items-center justify-center py-2 bg-zinc-800/60 hover:bg-zinc-700/80 text-zinc-400 hover:text-white transition-all cursor-pointer text-xs rounded-lg font-medium"
      aria-label="Toggle Sidebar"
    >
      {#if isCollapsed}
        <ChevronRight class="w-4 h-4" />
      {:else}
        <div class="flex items-center gap-1.5 text-[11px]">
          <ChevronLeft class="w-3.5 h-3.5" />
          <span>Sembunyikan</span>
        </div>
      {/if}
    </button>
  </div>
</aside>
