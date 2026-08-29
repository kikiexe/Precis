<script lang="ts">
  import {
    TrendingUp,
    Receipt,
    Users,
    LogOut,
    ChevronLeft,
    ChevronRight,
    Shield,
  } from 'lucide-svelte';
  import type { SuperadminUser } from '../types/superadmin';

  interface Props {
    activeTab: 'metrics' | 'invoices' | 'tenants';
    pendingInvoicesCount: number;
    currentUser: SuperadminUser | null;
    isCollapsed: boolean;
    onSelectTab: (tab: 'metrics' | 'invoices' | 'tenants') => void;
    onToggleCollapse: () => void;
    onLogout: () => void;
  }

  let {
    activeTab,
    pendingInvoicesCount = 0,
    currentUser,
    isCollapsed = false,
    onSelectTab,
    onToggleCollapse,
    onLogout,
  }: Props = $props();

  const navItems = [
    { id: 'metrics' as const, label: 'Metrik Global', icon: TrendingUp },
    { id: 'invoices' as const, label: 'Verifikasi Faktur', icon: Receipt, badge: true },
    { id: 'tenants' as const, label: 'Direktori Tenant', icon: Users },
  ];
</script>

<aside
  class={`relative z-20 flex shrink-0 flex-col justify-between border-r border-[#262626] bg-[#17171c] font-sans text-white transition-all duration-200 select-none ${
    isCollapsed ? 'w-16' : 'w-56'
  }`}
>
  <div>
    <!-- Brand Header -->
    <div class="flex h-16 items-center gap-3 overflow-hidden border-b border-[#262626] px-4">
      <img
        src="/logo.png"
        alt="Précis Logo"
        class="size-8 shrink-0 rounded-[10px] border border-white/20 object-cover"
      />
      {#if !isCollapsed}
        <div class="truncate">
          <div class="flex items-center gap-1.5">
            <span class="text-xs font-medium tracking-tight text-white uppercase">PRÉCIS</span>
            <span
              class="py-0.2 rounded-full bg-white/15 px-1.5 font-mono text-[9px] font-medium text-[#edfce9]"
              >Root</span
            >
          </div>
          <div class="font-mono text-[10px] text-[#93939f]">Superadmin Console</div>
        </div>
      {/if}
    </div>

    <!-- Navigation List -->
    <nav class="space-y-1.5 p-2.5">
      {#each navItems as item}
        {@const Icon = item.icon}
        <button
          type="button"
          onclick={() => onSelectTab(item.id)}
          title={item.label}
          class={`flex w-full cursor-pointer items-center gap-3 rounded-[12px] px-3 py-2.5 text-xs transition-all ${
            activeTab === item.id
              ? 'bg-white/15 font-medium text-white shadow-none'
              : 'text-[#93939f] hover:bg-white/10 hover:text-white'
          } ${isCollapsed ? 'justify-center px-0' : ''}`}
        >
          <Icon
            class={`size-4 shrink-0 ${activeTab === item.id ? 'text-[#edfce9]' : 'text-[#93939f]'}`}
          />
          {#if !isCollapsed}
            <span class="flex-1 truncate text-left">{item.label}</span>
            {#if item.badge && pendingInvoicesCount > 0}
              <span
                class="rounded-full bg-[#e5484d] px-2 py-0.5 font-mono text-[10px] font-bold text-white"
              >
                {pendingInvoicesCount}
              </span>
            {/if}
          {/if}
        </button>
      {/each}
    </nav>
  </div>

  <!-- Bottom Section: Superadmin User Profile & Logout -->
  <div class="space-y-2 border-t border-[#262626] p-2.5">
    {#if !isCollapsed}
      <div
        class="flex items-center gap-2 truncate rounded-[12px] border border-white/5 bg-white/5 px-3 py-2 font-mono text-[11px] text-[#93939f]"
      >
        <Shield class="size-3.5 shrink-0 text-[#edfce9]" />
        <span class="truncate">{currentUser?.email || 'admin@gmail.com'}</span>
      </div>
    {/if}

    <button
      type="button"
      onclick={onLogout}
      title="Keluar dari Root Superadmin"
      class={`flex w-full cursor-pointer items-center gap-2.5 rounded-[12px] px-3 py-2 text-xs text-[#93939f] transition-all hover:bg-white/10 hover:text-[#e5484d] ${
        isCollapsed ? 'justify-center px-0' : ''
      }`}
    >
      <LogOut class="size-4 shrink-0" />
      {#if !isCollapsed}
        <span class="truncate">Keluar</span>
      {/if}
    </button>

    <button
      type="button"
      onclick={onToggleCollapse}
      class="flex w-full cursor-pointer items-center justify-center rounded-full bg-white/10 py-2 text-xs text-[#93939f] transition-all hover:bg-white/15 hover:text-white"
      aria-label="Toggle Sidebar"
    >
      {#if isCollapsed}
        <ChevronRight class="size-4" />
      {:else}
        <div class="flex items-center gap-1.5 font-mono text-[11px]">
          <ChevronLeft class="size-3.5" />
          <span>Sembunyikan</span>
        </div>
      {/if}
    </button>
  </div>
</aside>
