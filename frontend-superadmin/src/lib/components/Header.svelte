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

<header class="bg-[#161616] text-[#ffffff] border-b border-[#262626] sticky top-0 z-40">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">
      <!-- Left: Logo & Portal Title -->
      <div class="flex items-center space-x-4">
        <div class="flex items-center space-x-2">
          <div class="w-8 h-8 bg-[#0f62fe] flex items-center justify-center font-bold text-white tracking-wider">
            P
          </div>
          <div>
            <div class="flex items-center space-x-2">
              <span class="font-bold text-lg tracking-tight text-white">PRÉCIS</span>
              <span class="text-xs px-2 py-0.5 bg-[#da1e28] text-white font-mono uppercase font-semibold">Superadmin</span>
            </div>
            <p class="text-[10px] text-[#8c8c8c] uppercase tracking-wider font-mono">Root Management Engine</p>
          </div>
        </div>

        <!-- Navigation Tabs -->
        <nav class="hidden md:flex space-x-1 pl-6 border-l border-[#393939]">
          <button
            type="button"
            onclick={() => onSelectTab('metrics')}
            class={`px-3 py-2 text-xs font-medium flex items-center space-x-1.5 transition-colors ${
              activeTab === 'metrics'
                ? 'bg-[#0f62fe] text-white'
                : 'text-[#c6c6c6] hover:bg-[#262626] hover:text-white'
            }`}
          >
            <BarChart3 class="w-4 h-4" />
            <span>Metrik SaaS</span>
          </button>

          <button
            type="button"
            onclick={() => onSelectTab('invoices')}
            class={`px-3 py-2 text-xs font-medium flex items-center space-x-1.5 transition-colors relative ${
              activeTab === 'invoices'
                ? 'bg-[#0f62fe] text-white'
                : 'text-[#c6c6c6] hover:bg-[#262626] hover:text-white'
            }`}
          >
            <CheckCircle2 class="w-4 h-4" />
            <span>Verifikasi Faktur</span>
            {#if pendingInvoicesCount > 0}
              <span class="ml-1.5 px-1.5 py-0.2 bg-[#da1e28] text-white text-[10px] font-mono font-bold">
                {pendingInvoicesCount}
              </span>
            {/if}
          </button>

          <button
            type="button"
            onclick={() => onSelectTab('tenants')}
            class={`px-3 py-2 text-xs font-medium flex items-center space-x-1.5 transition-colors ${
              activeTab === 'tenants'
                ? 'bg-[#0f62fe] text-white'
                : 'text-[#c6c6c6] hover:bg-[#262626] hover:text-white'
            }`}
          >
            <Building2 class="w-4 h-4" />
            <span>Direktori Tenant</span>
          </button>

          <button
            type="button"
            onclick={() => onSelectTab('plans')}
            class={`px-3 py-2 text-xs font-medium flex items-center space-x-1.5 transition-colors ${
              activeTab === 'plans'
                ? 'bg-[#0f62fe] text-white'
                : 'text-[#c6c6c6] hover:bg-[#262626] hover:text-white'
            }`}
          >
            <Layers class="w-4 h-4" />
            <span>Master Paket</span>
          </button>
        </nav>
      </div>

      <!-- Right: Superadmin User & Logout -->
      <div class="flex items-center space-x-4">
        {#if user}
          <div class="hidden sm:flex items-center space-x-2 text-xs text-[#c6c6c6]">
            <User class="w-3.5 h-3.5 text-[#8c8c8c]" />
            <span class="font-mono text-white">{user.email}</span>
          </div>
        {/if}

        <button
          type="button"
          onclick={onLogout}
          class="flex items-center space-x-1.5 px-3 py-1.5 bg-[#262626] hover:bg-[#da1e28] text-white text-xs font-medium transition-colors"
          title="Logout Superadmin"
        >
          <LogOut class="w-3.5 h-3.5" />
          <span class="hidden sm:inline">Keluar</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Navigation Bar -->
  <div class="md:hidden flex border-t border-[#393939] overflow-x-auto text-xs bg-[#1f1f1f]">
    <button
      type="button"
      onclick={() => onSelectTab('metrics')}
      class={`px-3 py-2.5 whitespace-nowrap ${activeTab === 'metrics' ? 'bg-[#0f62fe] text-white' : 'text-[#c6c6c6]'}`}
    >
      Metrik
    </button>
    <button
      type="button"
      onclick={() => onSelectTab('invoices')}
      class={`px-3 py-2.5 whitespace-nowrap flex items-center space-x-1 ${
        activeTab === 'invoices' ? 'bg-[#0f62fe] text-white' : 'text-[#c6c6c6]'
      }`}
    >
      <span>Verifikasi</span>
      {#if pendingInvoicesCount > 0}
        <span class="px-1 bg-[#da1e28] text-white text-[10px] font-mono">{pendingInvoicesCount}</span>
      {/if}
    </button>
    <button
      type="button"
      onclick={() => onSelectTab('tenants')}
      class={`px-3 py-2.5 whitespace-nowrap ${activeTab === 'tenants' ? 'bg-[#0f62fe] text-white' : 'text-[#c6c6c6]'}`}
    >
      Tenant
    </button>
    <button
      type="button"
      onclick={() => onSelectTab('plans')}
      class={`px-3 py-2.5 whitespace-nowrap ${activeTab === 'plans' ? 'bg-[#0f62fe] text-white' : 'text-[#c6c6c6]'}`}
    >
      Paket
    </button>
  </div>
</header>
