<script lang="ts">
  import { onMount } from 'svelte';
  import { superadminApiClient } from './lib/services/api-client';
  import { superadminService } from './lib/services/superadmin-service';
  import type {
    SuperadminUser,
    SaaSMetrics,
    InvoiceRecord,
    TenantRecord,
  } from './lib/types/superadmin';

  import SuperadminSidebar from './lib/components/SuperadminSidebar.svelte';
  import MetricsCards from './lib/components/MetricsCards.svelte';
  import InvoiceVerificationHub from './lib/components/InvoiceVerificationHub.svelte';
  import TenantDirectory from './lib/components/TenantDirectory.svelte';
  import LoginView from './lib/components/LoginView.svelte';
  import { Menu, X } from 'lucide-svelte';

  // App state dengan Svelte 5 runes
  let isAuthenticated = $state(false);
  let isInitializing = $state(true);
  let currentUser = $state<SuperadminUser | null>(null);
  let activeTab = $state<'metrics' | 'invoices' | 'tenants'>('metrics');
  let isSidebarCollapsed = $state(false);
  let isMobileSidebarOpen = $state(false);

  let metrics = $state<SaaSMetrics | null>(null);
  let invoices = $state<InvoiceRecord[]>([]);
  let tenants = $state<TenantRecord[]>([]);

  let isLoadingData = $state(false);

  let pendingInvoicesCount = $derived(
    invoices.filter((inv) => inv.status === 'PENDING_VERIFICATION').length
  );

  onMount(async () => {
    // Daftarkan handler saat token kadaluwarsa (401)
    superadminApiClient.onUnauthorized(() => {
      isAuthenticated = false;
      currentUser = null;
    });

    const token = superadminApiClient.getToken();
    if (token) {
      try {
        currentUser = await superadminService.getProfile();
        isAuthenticated = true;
        await loadAllDashboardData();
      } catch {
        superadminApiClient.clearSession();
        isAuthenticated = false;
        currentUser = null;
      }
    }

    isInitializing = false;
  });

  async function loadAllDashboardData() {
    isLoadingData = true;
    try {
      const [metricsData, invoicesData, tenantsData] = await Promise.all([
        superadminService.getMetrics().catch(() => null),
        superadminService.getInvoices().catch(() => []),
        superadminService.getTenants().catch(() => []),
      ]);

      if (metricsData) metrics = metricsData;
      invoices = invoicesData;
      tenants = tenantsData;
    } finally {
      isLoadingData = false;
    }
  }

  async function handleLogin(email: string, pass: string) {
    const authResult = await superadminService.login(email, pass);
    currentUser = authResult.superadmin;
    isAuthenticated = true;
    await loadAllDashboardData();
  }

  async function handleLogout() {
    await superadminService.logout();
    isAuthenticated = false;
    currentUser = null;
  }

  async function handleVerifyInvoice(invoiceId: string) {
    await superadminService.verifyInvoice(invoiceId);
    await loadAllDashboardData();
  }

  async function handleUpdateTenantStatus(
    tenantId: string,
    status: 'ACTIVE' | 'SUSPENDED' | 'GRACE_PERIOD' | 'TRIAL'
  ) {
    await superadminService.updateTenantStatus(tenantId, status);
    await loadAllDashboardData();
  }

  async function handleExtendSubscription(tenantId: string, days: number) {
    await superadminService.extendTenantSubscription(tenantId, days);
    await loadAllDashboardData();
  }
</script>

{#if isInitializing}
  <div class="min-h-screen bg-[#eeece7]/40 flex items-center justify-center font-sans">
    <div class="text-center space-y-3">
      <div class="w-8 h-8 border-2 border-[#17171c] border-t-transparent rounded-full animate-spin mx-auto"></div>
      <p class="text-xs font-mono text-[#75758a]">Memvalidasi sesi Précis Superadmin...</p>
    </div>
  </div>
{:else if !isAuthenticated}
  <LoginView onLogin={handleLogin} />
{:else}
  <div class="min-h-screen bg-[#eeece7]/30 flex flex-col md:flex-row font-sans overflow-hidden">
    <!-- Desktop Sidebar -->
    <div class="hidden md:flex h-screen sticky top-0 shrink-0">
      <SuperadminSidebar
        {activeTab}
        {pendingInvoicesCount}
        {currentUser}
        isCollapsed={isSidebarCollapsed}
        onSelectTab={(tab) => (activeTab = tab)}
        onToggleCollapse={() => (isSidebarCollapsed = !isSidebarCollapsed)}
        onLogout={handleLogout}
      />
    </div>

    <!-- Mobile Top Bar with Hamburger -->
    <div class="md:hidden bg-[#17171c] text-white px-4 py-3 flex items-center justify-between border-b border-[#262626] shrink-0">
      <div class="flex items-center gap-2.5">
        <img src="/logo.png" alt="Précis Logo" class="w-7 h-7 rounded-lg object-cover border border-white/20" />
        <span class="font-medium text-xs tracking-tight uppercase">PRÉCIS Superadmin</span>
      </div>

      <button
        type="button"
        onclick={() => (isMobileSidebarOpen = !isMobileSidebarOpen)}
        class="p-1.5 rounded-lg bg-white/10 hover:bg-white/15 text-white cursor-pointer"
        aria-label="Buka Menu Navigasi"
      >
        {#if isMobileSidebarOpen}
          <X class="w-5 h-5" />
        {:else}
          <Menu class="w-5 h-5" />
        {/if}
      </button>
    </div>

    <!-- Mobile Sidebar Drawer Overlay -->
    {#if isMobileSidebarOpen}
      <div
        role="presentation"
        class="fixed inset-0 z-40 bg-black/60 backdrop-blur-xs md:hidden"
        onclick={() => (isMobileSidebarOpen = false)}
        onkeydown={(e) => e.key === 'Escape' && (isMobileSidebarOpen = false)}
      >
        <div
          role="dialog"
          aria-modal="true"
          tabindex="-1"
          class="w-64 h-full bg-[#17171c]"
          onclick={(e) => e.stopPropagation()}
          onkeydown={(e) => e.stopPropagation()}
        >
          <SuperadminSidebar
            {activeTab}
            {pendingInvoicesCount}
            {currentUser}
            isCollapsed={false}
            onSelectTab={(tab) => {
              activeTab = tab;
              isMobileSidebarOpen = false;
            }}
            onToggleCollapse={() => {}}
            onLogout={handleLogout}
          />
        </div>
      </div>
    {/if}

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto h-screen p-4 sm:p-6 lg:p-8 space-y-6">
      {#if activeTab === 'metrics'}
        <MetricsCards
          {metrics}
          isLoading={isLoadingData}
          onRefresh={loadAllDashboardData}
        />
      {:else if activeTab === 'invoices'}
        <InvoiceVerificationHub
          {invoices}
          isLoading={isLoadingData}
          onVerifyInvoice={handleVerifyInvoice}
          onRefresh={loadAllDashboardData}
        />
      {:else if activeTab === 'tenants'}
        <TenantDirectory
          {tenants}
          isLoading={isLoadingData}
          onUpdateStatus={handleUpdateTenantStatus}
          onExtendSubscription={handleExtendSubscription}
          onRefresh={loadAllDashboardData}
        />
      {/if}
    </main>
  </div>
{/if}
