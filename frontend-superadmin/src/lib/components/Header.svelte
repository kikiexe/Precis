<script lang="ts">
  import { BarChart3, CheckCircle2, Building2, Layers, LogOut, User } from 'lucide-svelte';
  import type { SuperadminUser } from '../types/superadmin';

  interface Props {
    activeTab: 'metrics' | 'invoices' | 'tenants' | 'plans';
    pendingInvoicesCount: number;
    user: SuperadminUser | null;
    onSelectTab: (tab: 'metrics' | 'invoices' | 'tenants' | 'plans') => void;
    onLogout: () => void;
  }

  let { activeTab, pendingInvoicesCount, user, onSelectTab, onLogout }: Props = $props();
</script>

<header class="bg-[#17171c] text-[#ffffff] border-b border-[#262626] sticky top-0 z-40 font-sans">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">
      <!-- Left: Logo & Portal Title -->
      <div class="flex items-center space-x-4">
        <div class="flex items-center space-x-2.5">
          <div class="w-8 h-8 bg-white text-[#17171c] rounded-[10px] flex items-center justify-center font-medium tracking-wider">
            P
          </div>
          <div>
            <div class="flex items-center space-x-2">
              <span class="font-medium text-base tracking-tight text-white">PRÉCIS</span>
              <span class="text-[10px] px-2 py-0.5 bg-white/15 text-white font-mono rounded-full font-medium">Superadmin</span>
            </div>
            <p class="text-[10px] text-[#93939f] font-mono">Root Management Engine</p>
          </div>
        </div>

        <!-- Navigation Tabs -->
        <nav class="hidden md:flex space-x-1 pl-6 border-l border-[#262626]">
          <button
            type="button"
            onclick={() => onSelectTab('metrics')}
            class={`px-3.5 py-1.5 text-xs font-medium rounded-full flex items-center space-x-1.5 transition-all cursor-pointer ${
              activeTab === 'metrics'
                ? 'bg-white text-[#17171c] shadow-none'
                : 'text-[#93939f] hover:bg-white/10 hover:text-white'
            }`}
          >
            <BarChart3 class="w-3.5 h-3.5" />
            <span>Metrik SaaS</span>
          </button>

          <button
            type="button"
            onclick={() => onSelectTab('invoices')}
            class={`px-3.5 py-1.5 text-xs font-medium rounded-full flex items-center space-x-1.5 transition-all cursor-pointer relative ${
              activeTab === 'invoices'
                ? 'bg-white text-[#17171c] shadow-none'
                : 'text-[#93939f] hover:bg-white/10 hover:text-white'
            }`}
          >
            <CheckCircle2 class="w-3.5 h-3.5" />
            <span>Verifikasi Faktur</span>
            {#if pendingInvoicesCount > 0}
              <span class="ml-1.5 px-2 py-0.2 bg-[#ff7759] text-white text-[10px] font-mono font-medium rounded-full">
                {pendingInvoicesCount}
              </span>
            {/if}
          </button>

          <button
            type="button"
            onclick={() => onSelectTab('tenants')}
            class={`px-3.5 py-1.5 text-xs font-medium rounded-full flex items-center space-x-1.5 transition-all cursor-pointer ${
              activeTab === 'tenants'
                ? 'bg-white text-[#17171c] shadow-none'
                : 'text-[#93939f] hover:bg-white/10 hover:text-white'
            }`}
          >
            <Building2 class="w-3.5 h-3.5" />
            <span>Direktori Tenant</span>
          </button>

          <button
            type="button"
            onclick={() => onSelectTab('plans')}
            class={`px-3.5 py-1.5 text-xs font-medium rounded-full flex items-center space-x-1.5 transition-all cursor-pointer ${
              activeTab === 'plans'
                ? 'bg-white text-[#17171c] shadow-none'
                : 'text-[#93939f] hover:bg-white/10 hover:text-white'
            }`}
          >
            <Layers class="w-3.5 h-3.5" />
            <span>Master Paket</span>
          </button>
        </nav>
      </div>

      <!-- Right: Superadmin User & Logout -->
      <div class="flex items-center space-x-3">
        {#if user}
          <div class="hidden sm:flex items-center space-x-2 text-xs text-[#d9d9dd] bg-white/5 px-3 py-1 rounded-full border border-white/5">
            <User class="w-3.5 h-3.5 text-[#93939f]" />
            <span class="font-mono text-white text-[11px]">{user.email}</span>
          </div>
        {/if}

        <button
          type="button"
          onclick={onLogout}
          class="flex items-center space-x-1.5 px-3.5 py-1.5 bg-white/10 hover:bg-[#b30000] text-white text-xs font-medium rounded-full transition-all cursor-pointer"
          title="Logout Superadmin"
        >
          <LogOut class="w-3.5 h-3.5" />
          <span class="hidden sm:inline">Keluar</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Navigation Bar -->
  <div class="md:hidden flex border-t border-[#262626] overflow-x-auto text-xs bg-[#17171c] p-1.5 gap-1">
    <button
      type="button"
      onclick={() => onSelectTab('metrics')}
      class={`px-3 py-1.5 rounded-full whitespace-nowrap cursor-pointer transition-all ${activeTab === 'metrics' ? 'bg-white text-[#17171c] font-medium' : 'text-[#93939f]'}`}
    >
      Metrik
    </button>
    <button
      type="button"
      onclick={() => onSelectTab('invoices')}
      class={`px-3 py-1.5 rounded-full whitespace-nowrap flex items-center space-x-1 cursor-pointer transition-all ${
        activeTab === 'invoices' ? 'bg-white text-[#17171c] font-medium' : 'text-[#93939f]'
      }`}
    >
      <span>Verifikasi</span>
      {#if pendingInvoicesCount > 0}
        <span class="px-1.5 bg-[#ff7759] text-white text-[10px] font-mono rounded-full font-medium">{pendingInvoicesCount}</span>
      {/if}
    </button>
    <button
      type="button"
      onclick={() => onSelectTab('tenants')}
      class={`px-3 py-1.5 rounded-full whitespace-nowrap cursor-pointer transition-all ${activeTab === 'tenants' ? 'bg-white text-[#17171c] font-medium' : 'text-[#93939f]'}`}
    >
      Tenant
    </button>
    <button
      type="button"
      onclick={() => onSelectTab('plans')}
      class={`px-3 py-1.5 rounded-full whitespace-nowrap cursor-pointer transition-all ${activeTab === 'plans' ? 'bg-white text-[#17171c] font-medium' : 'text-[#93939f]'}`}
    >
      Paket
    </button>
  </div>
</header>
