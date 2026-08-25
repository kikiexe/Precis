<script lang="ts">
  import { onMount } from 'svelte';
  import { apiClient } from './lib/services/api-client';
  import { shiftService } from './lib/services/shift-service';
  import { cashAdvanceService } from './lib/services/cash-advance-service';
  import { payrollService } from './lib/services/payroll-service';
  import { billingService } from './lib/services/billing-service';
  import { workspaceService } from './lib/services/workspace-service';
  import { workspaceContext } from './lib/stores/workspace-context.svelte';
  import { sessionManager } from './lib/services/session-manager';
  import type { LoginResponseData, UserWorkspace } from './lib/types/app';
  import LoginView from './lib/components/auth/LoginView.svelte';
  import AppHeader from './lib/components/app/AppHeader.svelte';
  import AppSidebar from './lib/components/app/AppSidebar.svelte';
  import BottomNav from './lib/components/app/BottomNav.svelte';
  import DashboardSection from './lib/components/app/DashboardSection.svelte';
  import KatalogSection from './lib/components/app/KatalogSection.svelte';
  import TimSection from './lib/components/app/TimSection.svelte';
  import FinanceSection from './lib/components/app/FinanceSection.svelte';
  import SettingsSection from './lib/components/app/SettingsSection.svelte';
  import StaffHomeSection from './lib/components/app/StaffHomeSection.svelte';
  import StaffPresensiSection from './lib/components/app/StaffPresensiSection.svelte';
  import ShiftSection from './lib/components/app/ShiftSection.svelte';
  import BillingModal from './lib/components/app/BillingModal.svelte';
  import CreateWorkspaceModal from './lib/components/app/CreateWorkspaceModal.svelte';
  import AcceptInvitationView from './lib/components/auth/AcceptInvitationView.svelte';
  import VerifyEmailView from './lib/components/auth/VerifyEmailView.svelte';

  let isAuthenticated = $state(false);
  let isCheckingSession = $state(true);
  let invitationToken = $state<string | null>(null);
  let verificationToken = $state<string | null>(null);

  let activeDomain = $state<'home' | 'dashboard' | 'katalog' | 'tim' | 'finance' | 'settings' | 'presensi' | 'shift'>('dashboard');
  let activeSubTab = $state<string>('');

  let isBillingModalOpen = $state(false);
  let isCreateWorkspaceModalOpen = $state(false);

  onMount(() => {
    if (typeof window !== 'undefined') {
      const urlParams = new URLSearchParams(window.location.search);
      const path = window.location.pathname;

      const invToken = urlParams.get('invite_token') || (path.includes('invite') ? urlParams.get('token') : null);
      const vToken = urlParams.get('verify_token') || (path.includes('verify-email') ? urlParams.get('token') : null);

      if (invToken) {
        invitationToken = invToken;
      } else if (vToken) {
        verificationToken = vToken;
      }
    }

    const unsubscribeUnauthorized = apiClient.onUnauthorized(() => {
      isAuthenticated = false;
      workspaceContext.reset();
    });

    const unsubscribeSuspended = apiClient.onSubscriptionSuspended(() => {
      isBillingModalOpen = true;
    });

    initialize();

    return () => {
      unsubscribeUnauthorized();
      unsubscribeSuspended();
    };
  });

  async function initialize() {
    isCheckingSession = true;
    try {
      isAuthenticated = await sessionManager.initializeSession();
      if (isAuthenticated) {
        activeDomain =
          workspaceContext.currentUser.role === 'OWNER' || workspaceContext.currentUser.role === 'ADMIN'
            ? 'dashboard'
            : 'home';
      }
    } finally {
      isCheckingSession = false;
    }
  }

  async function handleLoginSuccess(data: LoginResponseData) {
    await sessionManager.handleLoginSuccess(data);
    isAuthenticated = true;
    activeDomain =
      workspaceContext.currentUser.role === 'OWNER' || workspaceContext.currentUser.role === 'ADMIN'
        ? 'dashboard'
        : 'home';
    if (workspaceContext.userWorkspaces.length === 0) {
      isCreateWorkspaceModalOpen = true;
    }
  }

  async function handleSwitchWorkspace(workspace: UserWorkspace) {
    await workspaceContext.switchWorkspace(workspace);
    activeDomain =
      workspace.role === 'OWNER' || workspace.role === 'ADMIN' ? 'dashboard' : 'home';
  }

  async function handleLogout() {
    await sessionManager.logout();
    isAuthenticated = false;
  }

  function handleSelectNav(domain: 'home' | 'dashboard' | 'katalog' | 'tim' | 'finance' | 'settings' | 'presensi' | 'shift', subTab?: string) {
    activeDomain = domain;
    activeSubTab = subTab || '';
  }

  async function handleCreateWorkspace(name: string, branchName: string) {
    const res = await workspaceService.createWorkspace(name, branchName);
    workspaceContext.userWorkspaces = res.workspaces || [];

    const newWs =
      workspaceContext.userWorkspaces.find((w) => w.workspace_id === res.workspace.id) ||
      workspaceContext.userWorkspaces[workspaceContext.userWorkspaces.length - 1];
    if (newWs) {
      await handleSwitchWorkspace(newWs);
    }
  }
</script>

{#if invitationToken}
  <AcceptInvitationView
    token={invitationToken}
    onAccepted={async () => {
      invitationToken = null;
      if (typeof window !== 'undefined') {
        window.history.replaceState({}, document.title, '/');
      }
      await initialize();
    }}
    onRejected={() => {
      invitationToken = null;
      if (typeof window !== 'undefined') {
        window.history.replaceState({}, document.title, '/');
      }
    }}
  />
{:else if verificationToken}
  <VerifyEmailView
    token={verificationToken}
    onCompleted={() => {
      verificationToken = null;
      if (typeof window !== 'undefined') {
        window.history.replaceState({}, document.title, '/');
      }
    }}
  />
{:else if isCheckingSession}
  <div class="min-h-screen bg-[#eeece7] flex items-center justify-center font-sans">
    <div class="text-center space-y-3">
      <div class="w-8 h-8 bg-[#17171c] rounded-lg mx-auto animate-pulse"></div>
      <div class="text-xs font-mono text-[#75758a]">Memuat Sesi Pr&eacute;cis...</div>
    </div>
  </div>
{:else if !isAuthenticated}
  <LoginView onLoginSuccess={handleLoginSuccess} />
{:else}
  <div class="min-h-screen bg-[#eeece7]/40 flex flex-row overflow-x-hidden font-sans select-none">
    <AppSidebar
      currentUser={workspaceContext.currentUser}
      userWorkspaces={workspaceContext.userWorkspaces}
      activeWorkspaceId={workspaceContext.activeWorkspaceId}
      branches={workspaceContext.workspaceBranches}
      selectedBranchId={workspaceContext.selectedBranchFilter}
      onSelectBranch={(id) => {
        workspaceContext.selectedBranchFilter = id;
        workspaceContext.loadWorkspaceData();
      }}
      {activeDomain}
      {activeSubTab}
      pendingApprovalsCount={workspaceContext.pendingApprovalsCount}
      onSelectNav={handleSelectNav}
      onSwitchWorkspace={handleSwitchWorkspace}
      onOpenCreateWorkspaceModal={() => (isCreateWorkspaceModalOpen = true)}
      onLogout={handleLogout}
    />

    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
      <AppHeader
        currentUser={workspaceContext.currentUser}
        userWorkspaces={workspaceContext.userWorkspaces}
        activeWorkspaceId={workspaceContext.activeWorkspaceId}
        branches={workspaceContext.workspaceBranches}
        selectedBranchId={workspaceContext.selectedBranchFilter}
        onSelectBranch={(id) => {
          workspaceContext.selectedBranchFilter = id;
          workspaceContext.loadWorkspaceData();
        }}
        onSwitchWorkspace={handleSwitchWorkspace}
        onOpenCreateWorkspaceModal={() => (isCreateWorkspaceModalOpen = true)}
        onOpenBilling={() => handleSelectNav('settings', 'billing')}
        onLogout={handleLogout}
      />

      <main class="flex-1 p-4 sm:p-6 md:p-8 max-w-7xl mx-auto w-full pb-24 lg:pb-8 min-w-0 overflow-x-hidden">
        {#if activeDomain === 'dashboard'}
          <DashboardSection
            currentUser={workspaceContext.currentUser}
            branches={workspaceContext.workspaceBranches}
            selectedBranchId={workspaceContext.selectedBranchFilter}
            onSelectBranch={(id) => {
              workspaceContext.selectedBranchFilter = id;
              workspaceContext.loadWorkspaceData();
            }}
            onNavigate={handleSelectNav}
          />
        {:else if activeDomain === 'katalog'}
          <KatalogSection
            currentUser={workspaceContext.currentUser}
            initialSubTab={activeSubTab}
          />
        {:else if activeDomain === 'tim'}
          <TimSection
            currentUser={workspaceContext.currentUser}
            initialSubTab={activeSubTab}
            teamMembers={workspaceContext.teamMembers}
            attendances={workspaceContext.allAttendances}
            rosterShifts={workspaceContext.rosterShifts}
            shiftTemplates={workspaceContext.shiftTemplates}
            pendingSwaps={workspaceContext.pendingSwaps}
            pendingKasbons={workspaceContext.adminPendingKasbons}
            onApproveSwap={async (id) => {
              await shiftService.approveSwap(id);
              await workspaceContext.loadWorkspaceData();
            }}
            onRejectSwap={async (id) => {
              await shiftService.rejectSwap(id);
              await workspaceContext.loadWorkspaceData();
            }}
            onApproveKasbon={async (id) => {
              await cashAdvanceService.approveCashAdvance(id);
              await workspaceContext.loadWorkspaceData();
            }}
            onRejectKasbon={async (id) => {
              await cashAdvanceService.rejectCashAdvance(id);
              await workspaceContext.loadWorkspaceData();
            }}
            onAssignShift={async (tId, uId, d) => {
              await shiftService.assignShift(tId, uId, d);
              await workspaceContext.loadWorkspaceData();
            }}
            onRefreshMembers={() => workspaceContext.loadWorkspaceData()}
          />
        {:else if activeDomain === 'finance'}
          <FinanceSection
            currentUser={workspaceContext.currentUser}
            branches={workspaceContext.workspaceBranches}
            initialSubTab={activeSubTab}
            cashAdvances={workspaceContext.myCashAdvances}
            payrollSlip={workspaceContext.myPayrollSlip}
            payrollPreview={workspaceContext.adminPayrollPreview}
            onRequestKasbon={async (amount) => {
              await cashAdvanceService.requestCashAdvance(amount);
              await workspaceContext.loadWorkspaceData();
            }}
            onFilterPayrollPeriod={async (start, end) => {
              workspaceContext.adminPayrollPreview = await payrollService.calculatePreview(
                start,
                end,
                workspaceContext.currentUser.branch_id || undefined
              );
            }}
            onDisbursePayroll={async (start, end) => {
              await payrollService.disbursePayroll(start, end);
              await workspaceContext.loadWorkspaceData();
            }}
            onExportCsv={async (start, end, format) => {
              await payrollService.downloadBankCsv(start, end, format);
            }}
          />
        {:else if activeDomain === 'settings'}
          <SettingsSection
            currentUser={workspaceContext.currentUser}
            branches={workspaceContext.workspaceBranches}
            initialSubTab={activeSubTab}
            subscriptionInvoices={workspaceContext.subscriptionInvoices}
            subscriptionPlans={workspaceContext.subscriptionPlans}
            onOpenBillingModal={() => (isBillingModalOpen = true)}
            onSubmitPaymentProof={async (invId, accName, amount, proofUrl) => {
              await billingService.submitProof(invId, accName, amount, proofUrl);
              await workspaceContext.loadWorkspaceData();
            }}
            onBranchUpdated={() => workspaceContext.loadWorkspaceData()}
          />
        {:else if activeDomain === 'home'}
          <StaffHomeSection
            currentUser={workspaceContext.currentUser}
            userWorkspaces={workspaceContext.userWorkspaces}
            todayAttendance={workspaceContext.todayAttendance}
            rosterShifts={workspaceContext.rosterShifts}
            myPayrollSlip={workspaceContext.myPayrollSlip}
            myCashAdvances={workspaceContext.myCashAdvances}
            teamMembers={workspaceContext.teamMembers}
            onNavigate={(domain, subTab) => handleSelectNav(domain, subTab)}
            onOpenCreateWorkspaceModal={() => (isCreateWorkspaceModalOpen = true)}
            onRefreshSession={initialize}
            onSubmitSwap={async (sId, tId) => {
              await shiftService.requestSwap(sId, tId);
              await workspaceContext.loadWorkspaceData();
            }}
            onRequestKasbon={async (amount) => {
              await cashAdvanceService.requestCashAdvance(amount);
              await workspaceContext.loadWorkspaceData();
            }}
          />
        {:else if activeDomain === 'presensi'}
          <StaffPresensiSection
            currentUser={workspaceContext.currentUser}
            todayAttendance={workspaceContext.todayAttendance}
            onSuccessAttendance={(record) => {
              workspaceContext.todayAttendance = record;
              workspaceContext.loadWorkspaceData();
            }}
            onNavigateHome={() => handleSelectNav('home')}
          />
        {:else if activeDomain === 'shift'}
          <ShiftSection
            currentUser={workspaceContext.currentUser}
            allUsers={workspaceContext.teamMembers.map((m) => ({
              id: m.id,
              name: m.name,
              role: m.role,
              email: m.email,
              branch_id: m.branch_id || workspaceContext.currentUser.branch_id,
              branch_name: m.branch_name || workspaceContext.currentUser.branch_name,
              base_salary: m.base_salary,
            }))}
            rosterShifts={workspaceContext.rosterShifts}
            shiftTemplates={workspaceContext.shiftTemplates}
            onSubmitSwap={async (sId, tId) => {
              await shiftService.requestSwap(sId, tId);
              await workspaceContext.loadWorkspaceData();
            }}
            onAssignShift={async (tId, uId, d) => {
              await shiftService.assignShift(tId, uId, d);
              await workspaceContext.loadWorkspaceData();
            }}
            onRefreshTemplates={() => workspaceContext.loadWorkspaceData()}
          />
        {/if}
      </main>

      <div class="block lg:hidden">
        <BottomNav
          role={workspaceContext.currentUser.role}
          {activeDomain}
          pendingApprovalsCount={workspaceContext.pendingApprovalsCount}
          onSelectNav={handleSelectNav}
        />
      </div>
    </div>

    <BillingModal
      isOpen={isBillingModalOpen}
      userProfile={workspaceContext.userProfile}
      activeInvoice={workspaceContext.subscriptionInvoices[0] || null}
      plans={workspaceContext.subscriptionPlans}
      onClose={() => (isBillingModalOpen = false)}
      onInvoiceUpdated={() => workspaceContext.loadWorkspaceData()}
    />

    <CreateWorkspaceModal
      isOpen={isCreateWorkspaceModalOpen}
      isOnboarding={workspaceContext.userWorkspaces.length === 0}
      onClose={() => (isCreateWorkspaceModalOpen = false)}
      onSubmit={handleCreateWorkspace}
    />
  </div>
{/if}
