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

  let {
    activePage = 'penjualan',
    isCollapsed = false,
    onSelectPage,
    onToggleCollapse,
  }: Props = $props();

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
  class={`relative z-20 flex shrink-0 flex-col justify-between border-r border-zinc-800/80 bg-[#121215] font-sans text-white shadow-md transition-all duration-200 select-none ${
    isCollapsed ? 'w-16' : 'w-56'
  }`}
>
  <div>
    <!-- Brand Header -->
    <div
      class="flex h-14 items-center gap-3 overflow-hidden border-b border-zinc-800 bg-[#18181d] px-4"
    >
      <div
        class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-zinc-700 bg-zinc-800 text-sm font-bold tracking-wider text-white"
      >
        P
      </div>
      {#if !isCollapsed}
        <div class="truncate">
          <div class="text-xs font-bold tracking-wider text-white uppercase">PRÉCIS POS</div>
          <div class="font-mono text-[10px] text-zinc-400">Kiosk System</div>
        </div>
      {/if}
    </div>

    <!-- Navigation List (Moka Layout in Dark) -->
    <nav class="space-y-1 p-2">
      {#each navItems as item}
        {@const Icon = item.icon}
        <button
          type="button"
          onclick={() => onSelectPage(item.id)}
          title={item.label}
          class={`flex w-full cursor-pointer items-center gap-3 rounded-lg px-3.5 py-3 text-xs font-semibold tracking-wide transition-all ${
            activePage === item.id
              ? 'border border-zinc-700 bg-zinc-800 text-white shadow-xs'
              : 'text-zinc-400 hover:bg-zinc-800/50 hover:text-white'
          } ${isCollapsed ? 'justify-center px-0' : ''}`}
        >
          <Icon
            class={`size-4.5 shrink-0 ${activePage === item.id ? 'text-white' : 'text-zinc-400'}`}
          />
          {#if !isCollapsed}
            <span class="truncate">{item.label}</span>
          {/if}
        </button>
      {/each}
    </nav>
  </div>

  <!-- Bottom: Branch & Collapse Toggle -->
  <div class="space-y-2 border-t border-zinc-800 bg-[#18181d] p-2">
    {#if !isCollapsed}
      <div
        class="flex items-center gap-2 truncate rounded-lg border border-zinc-700/60 bg-zinc-800/80 px-3 py-2 text-[11px] font-medium text-zinc-200"
      >
        <Store class="size-3.5 shrink-0 text-zinc-400" />
        <span class="truncate">Précis Coffee - Outlet 01</span>
      </div>
    {/if}

    <button
      type="button"
      onclick={onToggleCollapse}
      class="flex w-full cursor-pointer items-center justify-center rounded-lg bg-zinc-800/60 py-2 text-xs font-medium text-zinc-400 transition-all hover:bg-zinc-700/80 hover:text-white"
      aria-label="Toggle Sidebar"
    >
      {#if isCollapsed}
        <ChevronRight class="size-4" />
      {:else}
        <div class="flex items-center gap-1.5 text-[11px]">
          <ChevronLeft class="size-3.5" />
          <span>Sembunyikan</span>
        </div>
      {/if}
    </button>
  </div>
</aside>
