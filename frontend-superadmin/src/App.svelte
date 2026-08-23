<script lang="ts">
  import { onMount } from 'svelte';
  import { superadminApiClient } from './lib/services/api-client';
  import { superadminService } from './lib/services/superadmin-service';
  import type {
    SuperadminUser,
    SaaSMetrics,
    InvoiceRecord,
    TenantRecord,
    SubscriptionPlanRecord,
  } from './lib/types/superadmin';

  import Header from './lib/components/Header.svelte';
  import MetricsCards from './lib/components/MetricsCards.svelte';
  import InvoiceVerificationHub from './lib/components/InvoiceVerificationHub.svelte';
  import TenantDirectory from './lib/components/TenantDirectory.svelte';
  import PlansOverview from './lib/components/PlansOverview.svelte';
  import LoginView from './lib/components/LoginView.svelte';

  // app state dengan svelte 5 runes
  let isAuthenticated = $state(false);
  let isInitializing = $state(true);
  let currentUser = $state<SuperadminUser | null>(null);
  let activeTab = $state<'metrics' | 'invoices' | 'tenants' | 'plans'>('metrics');

  let metrics = $state<SaaSMetrics | null>(null);
  let invoices = $state<InvoiceRecord[]>([]);
  let tenants = $state<TenantRecord[]>([]);
  let plans = $state<SubscriptionPlanRecord[]>([]);

  let isLoadingData = $state(false);

  let pendingInvoicesCount = $derived(
    invoices.filter((inv) => inv.status === 'PENDING_VERIFICATION').length
  );

  onMount(async () => {
    // daftarkan handler saat token kadaluwarsa (401)
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
      const [metricsData, invoicesData, tenantsData, plansData] = await Promise.all([
        superadminService.getMetrics().catch(() => null),
        superadminService.getInvoices().catch(() => []),
        superadminService.getTenants().catch(() => []),
        superadminService.getPlans().catch(() => []),
      ]);

      if (metricsData) metrics = metricsData;
      invoices = invoicesData;
      tenants = tenantsData;
      plans = plansData;
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
  <div class="min-h-screen bg-[#eeece7]/40 flex flex-col font-sans">
    <!-- superadmin top header -->
    <Header
      {activeTab}
      {pendingInvoicesCount}
      user={currentUser}
      onSelectTab={(tab) => (activeTab = tab)}
      onLogout={handleLogout}
    />

    <!-- main content container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
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
      {:else if activeTab === 'plans'}
        <PlansOverview
          {plans}
          isLoading={isLoadingData}
          onRefresh={loadAllDashboardData}
        />
      {/if}
    </main>

    <!-- superadmin footer -->
    <footer class="bg-[#17171c] text-[#93939f] border-t border-[#262626] py-3.5 text-center text-[11px] font-mono">
      <span>Précis Platform SaaS Engine &bull; Root Console &bull; PostgreSQL 16 &bull; Laravel Octane</span>
    </footer>
  </div>
{/if}
