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

<header class="sticky top-0 z-40 border-b border-[#262626] bg-[#17171c] font-sans text-[#ffffff]">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="flex h-16 items-center justify-between">
      <!-- Left: Logo & Portal Title -->
      <div class="flex items-center space-x-4">
        <div class="flex items-center space-x-2.5">
          <img
            src="/logo.png"
            alt="Précis Logo"
            class="size-8 rounded-[10px] border border-white/20 object-cover"
          />
          <div>
            <div class="flex items-center space-x-2">
              <span class="text-base font-medium tracking-tight text-white">PRÉCIS</span>
              <span
                class="rounded-full bg-white/15 px-2 py-0.5 font-mono text-[10px] font-medium text-white"
                >Superadmin</span
              >
            </div>
            <p class="font-mono text-[10px] text-[#93939f]">Root Management Engine</p>
          </div>
        </div>

        <!-- Navigation Tabs -->
        <nav class="hidden space-x-1 border-l border-[#262626] pl-6 md:flex">
          <button
            type="button"
            onclick={() => onSelectTab('metrics')}
            class={`flex cursor-pointer items-center space-x-1.5 rounded-full px-3.5 py-1.5 text-xs font-medium transition-all ${
              activeTab === 'metrics'
                ? 'bg-white text-[#17171c] shadow-none'
                : 'text-[#93939f] hover:bg-white/10 hover:text-white'
            }`}
          >
            <BarChart3 class="size-3.5" />
            <span>Metrik SaaS</span>
          </button>

          <button
            type="button"
            onclick={() => onSelectTab('invoices')}
            class={`relative flex cursor-pointer items-center space-x-1.5 rounded-full px-3.5 py-1.5 text-xs font-medium transition-all ${
              activeTab === 'invoices'
                ? 'bg-white text-[#17171c] shadow-none'
                : 'text-[#93939f] hover:bg-white/10 hover:text-white'
            }`}
          >
            <CheckCircle2 class="size-3.5" />
            <span>Verifikasi Faktur</span>
            {#if pendingInvoicesCount > 0}
              <span
                class="py-0.2 ml-1.5 rounded-full bg-[#ff7759] px-2 font-mono text-[10px] font-medium text-white"
              >
                {pendingInvoicesCount}
              </span>
            {/if}
          </button>

          <button
            type="button"
            onclick={() => onSelectTab('tenants')}
            class={`flex cursor-pointer items-center space-x-1.5 rounded-full px-3.5 py-1.5 text-xs font-medium transition-all ${
              activeTab === 'tenants'
                ? 'bg-white text-[#17171c] shadow-none'
                : 'text-[#93939f] hover:bg-white/10 hover:text-white'
            }`}
          >
            <Building2 class="size-3.5" />
            <span>Direktori Tenant</span>
          </button>

          <button
            type="button"
            onclick={() => onSelectTab('plans')}
            class={`flex cursor-pointer items-center space-x-1.5 rounded-full px-3.5 py-1.5 text-xs font-medium transition-all ${
              activeTab === 'plans'
                ? 'bg-white text-[#17171c] shadow-none'
                : 'text-[#93939f] hover:bg-white/10 hover:text-white'
            }`}
          >
            <Layers class="size-3.5" />
            <span>Master Paket</span>
          </button>
        </nav>
      </div>

      <!-- Right: Superadmin User & Logout -->
      <div class="flex items-center space-x-3">
        {#if user}
          <div
            class="hidden items-center space-x-2 rounded-full border border-white/5 bg-white/5 px-3 py-1 text-xs text-[#d9d9dd] sm:flex"
          >
            <User class="size-3.5 text-[#93939f]" />
            <span class="font-mono text-[11px] text-white">{user.email}</span>
          </div>
        {/if}

        <button
          type="button"
          onclick={onLogout}
          class="flex cursor-pointer items-center space-x-1.5 rounded-full bg-white/10 px-3.5 py-1.5 text-xs font-medium text-white transition-all hover:bg-[#b30000]"
          title="Logout Superadmin"
        >
          <LogOut class="size-3.5" />
          <span class="hidden sm:inline">Keluar</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Navigation Bar -->
  <div
    class="flex gap-1 overflow-x-auto border-t border-[#262626] bg-[#17171c] p-1.5 text-xs md:hidden"
  >
    <button
      type="button"
      onclick={() => onSelectTab('metrics')}
      class={`cursor-pointer rounded-full px-3 py-1.5 whitespace-nowrap transition-all ${activeTab === 'metrics' ? 'bg-white font-medium text-[#17171c]' : 'text-[#93939f]'}`}
    >
      Metrik
    </button>
    <button
      type="button"
      onclick={() => onSelectTab('invoices')}
      class={`flex cursor-pointer items-center space-x-1 rounded-full px-3 py-1.5 whitespace-nowrap transition-all ${
        activeTab === 'invoices' ? 'bg-white font-medium text-[#17171c]' : 'text-[#93939f]'
      }`}
    >
      <span>Verifikasi</span>
      {#if pendingInvoicesCount > 0}
        <span class="rounded-full bg-[#ff7759] px-1.5 font-mono text-[10px] font-medium text-white"
          >{pendingInvoicesCount}</span
        >
      {/if}
    </button>
    <button
      type="button"
      onclick={() => onSelectTab('tenants')}
      class={`cursor-pointer rounded-full px-3 py-1.5 whitespace-nowrap transition-all ${activeTab === 'tenants' ? 'bg-white font-medium text-[#17171c]' : 'text-[#93939f]'}`}
    >
      Tenant
    </button>
    <button
      type="button"
      onclick={() => onSelectTab('plans')}
      class={`cursor-pointer rounded-full px-3 py-1.5 whitespace-nowrap transition-all ${activeTab === 'plans' ? 'bg-white font-medium text-[#17171c]' : 'text-[#93939f]'}`}
    >
      Paket
    </button>
  </div>
</header>
