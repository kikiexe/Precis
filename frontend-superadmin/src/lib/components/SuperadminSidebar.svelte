<script lang="ts">
  import { TrendingUp, Receipt, Users, LogOut, ChevronLeft, ChevronRight, Shield } from 'lucide-svelte';
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
  class={`bg-[#17171c] text-white border-r border-[#262626] flex flex-col justify-between select-none transition-all duration-200 shrink-0 relative z-20 font-sans ${
    isCollapsed ? 'w-16' : 'w-56'
  }`}
>
  <div>
    <!-- Brand Header -->
    <div class="h-16 border-b border-[#262626] flex items-center px-4 gap-3 overflow-hidden">
      <img src="/logo.png" alt="Précis Logo" class="w-8 h-8 rounded-[10px] object-cover shrink-0 border border-white/20" />
      {#if !isCollapsed}
        <div class="truncate">
          <div class="flex items-center gap-1.5">
            <span class="font-medium text-xs tracking-tight text-white uppercase">PRÉCIS</span>
            <span class="text-[9px] px-1.5 py-0.2 bg-white/15 text-[#edfce9] font-mono rounded-full font-medium">Root</span>
          </div>
          <div class="text-[10px] font-mono text-[#93939f]">Superadmin Console</div>
        </div>
      {/if}
    </div>

    <!-- Navigation List -->
    <nav class="p-2.5 space-y-1.5">
      {#each navItems as item}
        {@const Icon = item.icon}
        <button
          type="button"
          onclick={() => onSelectTab(item.id)}
          title={item.label}
          class={`w-full flex items-center gap-3 px-3 py-2.5 text-xs transition-all cursor-pointer rounded-[12px] ${
            activeTab === item.id
              ? 'bg-white/15 text-white font-medium shadow-none'
              : 'text-[#93939f] hover:text-white hover:bg-white/10'
          } ${isCollapsed ? 'justify-center px-0' : ''}`}
        >
          <Icon class={`w-4 h-4 shrink-0 ${activeTab === item.id ? 'text-[#edfce9]' : 'text-[#93939f]'}`} />
          {#if !isCollapsed}
            <span class="truncate flex-1 text-left">{item.label}</span>
            {#if item.badge && pendingInvoicesCount > 0}
              <span class="px-2 py-0.5 text-[10px] font-mono font-bold bg-[#e5484d] text-white rounded-full">
                {pendingInvoicesCount}
              </span>
            {/if}
          {/if}
        </button>
      {/each}
    </nav>
  </div>

  <!-- Bottom Section: Superadmin User Profile & Logout -->
  <div class="p-2.5 border-t border-[#262626] space-y-2">
    {#if !isCollapsed}
      <div class="px-3 py-2 bg-white/5 rounded-[12px] text-[11px] font-mono text-[#93939f] flex items-center gap-2 truncate border border-white/5">
        <Shield class="w-3.5 h-3.5 text-[#edfce9] shrink-0" />
        <span class="truncate">{currentUser?.email || 'admin@gmail.com'}</span>
      </div>
    {/if}

    <button
      type="button"
      onclick={onLogout}
      title="Keluar dari Root Superadmin"
      class={`w-full flex items-center gap-2.5 px-3 py-2 text-xs text-[#93939f] hover:text-[#e5484d] hover:bg-white/10 rounded-[12px] transition-all cursor-pointer ${
        isCollapsed ? 'justify-center px-0' : ''
      }`}
    >
      <LogOut class="w-4 h-4 shrink-0" />
      {#if !isCollapsed}
        <span class="truncate">Keluar</span>
      {/if}
    </button>

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
