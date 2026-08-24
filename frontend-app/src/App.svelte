<script lang="ts">
  import { onMount } from 'svelte';
  import { apiClient } from './lib/services/api-client';
  import { authService } from './lib/services/auth-service';
  import { shiftService } from './lib/services/shift-service';
  import { attendanceService } from './lib/services/attendance-service';
  import { cashAdvanceService } from './lib/services/cash-advance-service';
  import { payrollService } from './lib/services/payroll-service';
  import { billingService } from './lib/services/billing-service';
  import { workspaceService } from './lib/services/workspace-service';
  import type {
    User,
    UserProfile,
    UserWorkspace,
    AttendanceRecord,
    ShiftRosterItem,
    PendingSwapItem,
    CashAdvance,
    PayrollSlipData,
    PayrollPreviewData,
    SubscriptionInvoice,
    SubscriptionPlanItem,
    LoginResponseData,
    TeamMember,
  } from './lib/types/app';
  import { teamService } from './lib/services/team-service';
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

  // State autentikasi & sesi
  let isAuthenticated = $state(false);
  let isCheckingSession = $state(true);
  let userWorkspaces = $state<UserWorkspace[]>([]);
  let activeWorkspaceId = $state<string | null>(null);
  let userProfile = $state<UserProfile | null>(null);

  // State URL routing parameter khusus
  let invitationToken = $state<string | null>(null);
  let verificationToken = $state<string | null>(null);

  // Profil pengguna aktif
  let currentUser = $state<User>({
    id: 'usr-default',
    name: 'Pengguna',
    role: 'STAFF',
    email: '',
    branch_id: '',
    branch_name: '',
    base_salary: 3000000,
  });

  // Domain & SubTab Navigasi
  let activeDomain = $state<'home' | 'dashboard' | 'katalog' | 'tim' | 'finance' | 'settings' | 'presensi' | 'shift'>('dashboard');
  let activeSubTab = $state<string>('');

  // Visibilitas modal
  let isBillingModalOpen = $state(false);
  let isCreateWorkspaceModalOpen = $state(false);

  // Data dinamis
  let todayAttendance = $state<AttendanceRecord | null>(null);
  let allAttendances = $state<AttendanceRecord[]>([]);
  let rosterShifts = $state<ShiftRosterItem[]>([]);
  let pendingSwaps = $state<PendingSwapItem[]>([]);
  let myCashAdvances = $state<CashAdvance[]>([]);
  let adminPendingKasbons = $state<CashAdvance[]>([]);
  let myPayrollSlip = $state<PayrollSlipData | null>(null);
  let adminPayrollPreview = $state<PayrollPreviewData | null>(null);
  let subscriptionInvoices = $state<SubscriptionInvoice[]>([]);
  let subscriptionPlans = $state<SubscriptionPlanItem[]>([]);
  let teamMembers = $state<TeamMember[]>([]);

  let pendingApprovalsCount = $derived(
    pendingSwaps.length + adminPendingKasbons.length
  );

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
      userWorkspaces = [];
      activeWorkspaceId = null;
    });

    const unsubscribeSuspended = apiClient.onSubscriptionSuspended(() => {
      isBillingModalOpen = true;
    });

    initializeSession();

    return () => {
      unsubscribeUnauthorized();
      unsubscribeSuspended();
    };
  });

  async function initializeSession() {
    isCheckingSession = true;
    const token = apiClient.getToken();
    if (!token) {
      isCheckingSession = false;
      return;
    }

    try {
      const profile = await authService.getProfile();
      if (profile) {
        userProfile = profile;
        userWorkspaces = profile.workspaces || [];

        const savedWsId = apiClient.getWorkspaceId();
        const matchedWs = userWorkspaces.find((w) => w.workspace_id === savedWsId) || userWorkspaces[0];

        if (matchedWs) {
          activeWorkspaceId = matchedWs.workspace_id;
          apiClient.setWorkspaceId(matchedWs.workspace_id);

          currentUser = {
            id: profile.id,
            name: profile.name,
            role: matchedWs.role,
            email: profile.email,
            bank_name: profile.bank_name,
            bank_account_number: profile.bank_account_number,
            bank_account_holder: profile.bank_account_holder,
            branch_id: matchedWs.branch_id || '',
            branch_name: matchedWs.branch_name || matchedWs.workspace_name,
            base_salary: 3000000,
          };
          activeDomain = matchedWs.role === 'OWNER' || matchedWs.role === 'ADMIN' ? 'dashboard' : 'home';
        } else {
          activeWorkspaceId = null;
          apiClient.setWorkspaceId(null);
          currentUser = {
            id: profile.id,
            name: profile.name,
            role: 'STAFF',
            email: profile.email,
            bank_name: profile.bank_name,
            bank_account_number: profile.bank_account_number,
            bank_account_holder: profile.bank_account_holder,
            branch_id: '',
            branch_name: '',
            base_salary: 0,
          };
          activeDomain = 'home';
        }

        isAuthenticated = true;
        await loadWorkspaceData();
      }
    } catch {
      apiClient.clearSession();
      isAuthenticated = false;
    } finally {
      isCheckingSession = false;
    }
  }

  async function loadWorkspaceData() {
    if (!activeWorkspaceId || userWorkspaces.length === 0) {
      rosterShifts = [];
      pendingSwaps = [];
      allAttendances = [];
      myCashAdvances = [];
      adminPendingKasbons = [];
      myPayrollSlip = null;
      adminPayrollPreview = null;
      teamMembers = [];
      return;
    }

    try {
      const branchId = currentUser.branch_id || undefined;
      const isAdminOrOwner = currentUser.role === 'OWNER' || currentUser.role === 'ADMIN';

      const [rosterData, swapData, wallData, myKasbons, adminKasbons, slipData, previewData, invoiceData, planData, membersData] = await Promise.all([
        shiftService.getRoster(branchId).catch(() => []),
        isAdminOrOwner
          ? shiftService.getPendingSwapRequests(branchId).catch(() => [])
          : Promise.resolve([]),
        isAdminOrOwner
          ? attendanceService.getWallOfFaces(branchId).catch(() => [])
          : Promise.resolve([]),
        cashAdvanceService.getMyCashAdvances().catch(() => []),
        isAdminOrOwner
          ? cashAdvanceService.getAdminCashAdvances('PENDING', branchId).catch(() => [])
          : Promise.resolve([]),
        payrollService.getMySlip().catch(() => null),
        isAdminOrOwner
          ? payrollService.calculatePreview(undefined, undefined, branchId).catch(() => null)
          : Promise.resolve(null),
        billingService.getInvoices().catch(() => []),
        billingService.getPlans().catch(() => []),
        isAdminOrOwner
          ? teamService.getMembers().catch(() => [])
          : Promise.resolve([]),
      ]);

      rosterShifts = rosterData;
      pendingSwaps = swapData;
      myCashAdvances = myKasbons;
      adminPendingKasbons = adminKasbons;
      myPayrollSlip = slipData;
      adminPayrollPreview = previewData;
      subscriptionInvoices = invoiceData;
      subscriptionPlans = planData;
      teamMembers = membersData;

      if (wallData.length > 0) {
        allAttendances = wallData.map((item) => ({
          id: item.id,
          user_id: item.user_id,
          user_name: item.user_name,
          avatar_url: item.avatar_url || '',
          branch_name: item.branch_name,
          shift_name: item.shift_name || 'Shift Pagi',
          clock_in_time: item.clock_in_time,
          clock_out_time: item.clock_out_time,
          photo_in_url: item.photo_in_url,
          photo_out_url: item.photo_out_url,
          lat_in: -7.7829,
          lng_in: 110.3671,
          status: item.status,
          late_minutes: item.late_minutes,
          created_at: item.date,
        }));
      }
    } catch (e) {
      console.warn('Gagal memuat data workspace:', e);
    }
  }

  async function handleLoginSuccess(data: LoginResponseData) {
    userWorkspaces = data.workspaces || [];

    if (userWorkspaces.length > 0) {
      const firstWs = userWorkspaces[0];
      activeWorkspaceId = firstWs.workspace_id;
      apiClient.setWorkspaceId(firstWs.workspace_id);

      currentUser = {
        id: data.user.id,
        name: data.user.name,
        role: firstWs.role,
        email: data.user.email,
        bank_name: data.user.bank_name,
        bank_account_number: data.user.bank_account_number,
        bank_account_holder: data.user.bank_account_holder,
        branch_id: firstWs.branch_id || '',
        branch_name: firstWs.branch_name || firstWs.workspace_name,
        base_salary: 3000000,
      };
      activeDomain = firstWs.role === 'OWNER' || firstWs.role === 'ADMIN' ? 'dashboard' : 'home';
    } else {
      activeWorkspaceId = null;
      apiClient.setWorkspaceId(null);
      currentUser = {
        id: data.user.id,
        name: data.user.name,
        role: 'STAFF',
        email: data.user.email,
        bank_name: data.user.bank_name,
        bank_account_number: data.user.bank_account_number,
        bank_account_holder: data.user.bank_account_holder,
        branch_id: '',
        branch_name: '',
        base_salary: 0,
      };
      activeDomain = 'home';
      isCreateWorkspaceModalOpen = true;
    }

    isAuthenticated = true;
    await loadWorkspaceData();
  }

  async function handleSwitchWorkspace(workspace: UserWorkspace) {
    activeWorkspaceId = workspace.workspace_id;
    apiClient.setWorkspaceId(workspace.workspace_id);

    currentUser = {
      ...currentUser,
      role: workspace.role,
      branch_id: workspace.branch_id || currentUser.branch_id,
      branch_name: workspace.branch_name || workspace.workspace_name,
    };
    activeDomain = workspace.role === 'OWNER' || workspace.role === 'ADMIN' ? 'dashboard' : 'home';
    await loadWorkspaceData();
  }

  async function handleLogout() {
    try {
      await authService.logout();
    } catch (e) {
      console.warn('Gagal logout di server:', e);
    } finally {
      apiClient.clearSession();
      isAuthenticated = false;
      userWorkspaces = [];
      activeWorkspaceId = null;
      userProfile = null;
    }
  }

  function handleSelectNav(domain: 'home' | 'dashboard' | 'katalog' | 'tim' | 'finance' | 'settings' | 'presensi' | 'shift', subTab?: string) {
    activeDomain = domain;
    activeSubTab = subTab || '';
  }

  async function handleCreateSwap(shiftAssignmentId: string, targetUserId: string) {
    await shiftService.requestSwap(shiftAssignmentId, targetUserId);
    await loadWorkspaceData();
  }

  async function handleAssignShift(templateId: string, userId: string, date: string) {
    await shiftService.assignShift(templateId, userId, date);
    await loadWorkspaceData();
  }

  async function handleApproveSwap(swapId: string) {
    await shiftService.approveSwap(swapId);
    await loadWorkspaceData();
  }

  async function handleRejectSwap(swapId: string) {
    await shiftService.rejectSwap(swapId);
    await loadWorkspaceData();
  }

  async function handleCreateKasbon(amount: number) {
    await cashAdvanceService.requestCashAdvance(amount);
    await loadWorkspaceData();
  }

  async function handleApproveKasbon(kasbonId: string) {
    await cashAdvanceService.approveCashAdvance(kasbonId);
    await loadWorkspaceData();
  }

  async function handleRejectKasbon(kasbonId: string) {
    await cashAdvanceService.rejectCashAdvance(kasbonId);
    await loadWorkspaceData();
  }

  async function handleFilterPayrollPeriod(start: string, end: string) {
    const preview = await payrollService.calculatePreview(start, end, currentUser.branch_id || undefined);
    adminPayrollPreview = preview;
  }

  async function handleDisbursePayroll(start: string, end: string) {
    await payrollService.disbursePayroll(start, end);
    await loadWorkspaceData();
  }

  async function handleExportCsv(start: string, end: string, format: 'BCA' | 'MANDIRI') {
    await payrollService.downloadBankCsv(start, end, format);
  }

  async function handleSubmitPaymentProof(invoiceId: string, accountName: string, amount: number, proofUrl: string) {
    await billingService.submitProof(invoiceId, accountName, amount, proofUrl);
    await loadWorkspaceData();
  }

  async function handleCreateWorkspace(name: string, branchName: string) {
    const res = await workspaceService.createWorkspace(name, branchName);
    userWorkspaces = res.workspaces || [];

    const newWs = userWorkspaces.find((w) => w.workspace_id === res.workspace.id) || userWorkspaces[userWorkspaces.length - 1];
    if (newWs) {
      await handleSwitchWorkspace(newWs);
    }
  }

  function handleInvoiceUpdated() {
    loadWorkspaceData();
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
      await initializeSession();
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
      <div class="text-xs font-mono text-[#75758a]">Memuat Sesi Précis...</div>
    </div>
  </div>
{:else if !isAuthenticated}
  <LoginView onLoginSuccess={handleLoginSuccess} />
{:else}
  <div class="min-h-screen bg-[#eeece7]/40 flex flex-row overflow-x-hidden font-sans select-none">
    <!-- Sidebar Khusus Desktop -->
    <AppSidebar
      {currentUser}
      {userWorkspaces}
      {activeWorkspaceId}
      {activeDomain}
      {activeSubTab}
      {pendingApprovalsCount}
      onSelectNav={handleSelectNav}
      onSwitchWorkspace={handleSwitchWorkspace}
      onOpenCreateWorkspaceModal={() => (isCreateWorkspaceModalOpen = true)}
      onLogout={handleLogout}
    />

    <!-- Area Konten Utama -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
      <!-- Header Atas -->
      <AppHeader
        {currentUser}
        {userWorkspaces}
        {activeWorkspaceId}
        onSwitchWorkspace={handleSwitchWorkspace}
        onOpenCreateWorkspaceModal={() => (isCreateWorkspaceModalOpen = true)}
        onOpenBilling={() => handleSelectNav('settings', 'billing')}
        onLogout={handleLogout}
      />

      <!-- Konten Halaman Aktif -->
      <main class="flex-1 p-4 sm:p-6 md:p-8 max-w-7xl mx-auto w-full pb-24 lg:pb-8 min-w-0 overflow-x-hidden">
        {#if activeDomain === 'dashboard'}
          <DashboardSection
            {currentUser}
            onNavigate={handleSelectNav}
          />
        {:else if activeDomain === 'katalog'}
          <KatalogSection
            {currentUser}
            initialSubTab={activeSubTab}
          />
        {:else if activeDomain === 'tim'}
          <TimSection
            {currentUser}
            initialSubTab={activeSubTab}
            {teamMembers}
            attendances={allAttendances}
            {rosterShifts}
            {pendingSwaps}
            pendingKasbons={adminPendingKasbons}
            onApproveSwap={handleApproveSwap}
            onRejectSwap={handleRejectSwap}
            onApproveKasbon={handleApproveKasbon}
            onRejectKasbon={handleRejectKasbon}
            onAssignShift={handleAssignShift}
            onRefreshMembers={loadWorkspaceData}
          />
        {:else if activeDomain === 'finance'}
          <FinanceSection
            {currentUser}
            initialSubTab={activeSubTab}
            cashAdvances={myCashAdvances}
            payrollSlip={myPayrollSlip}
            payrollPreview={adminPayrollPreview}
            onRequestKasbon={handleCreateKasbon}
            onFilterPayrollPeriod={handleFilterPayrollPeriod}
            onDisbursePayroll={handleDisbursePayroll}
            onExportCsv={handleExportCsv}
          />
        {:else if activeDomain === 'settings'}
          <SettingsSection
            {currentUser}
            initialSubTab={activeSubTab}
            subscriptionInvoices={subscriptionInvoices}
            subscriptionPlans={subscriptionPlans}
            onOpenBillingModal={() => (isBillingModalOpen = true)}
            onSubmitPaymentProof={handleSubmitPaymentProof}
          />
        {:else if activeDomain === 'home'}
          <StaffHomeSection
            {currentUser}
            {userWorkspaces}
            {todayAttendance}
            {rosterShifts}
            onNavigate={(domain) => handleSelectNav(domain)}
            onOpenCreateWorkspaceModal={() => (isCreateWorkspaceModalOpen = true)}
            onRefreshSession={initializeSession}
          />
        {:else if activeDomain === 'presensi'}
          <StaffPresensiSection
            {currentUser}
            {todayAttendance}
            onSuccessAttendance={(record) => {
              todayAttendance = record;
              loadWorkspaceData();
            }}
            onNavigateHome={() => handleSelectNav('home')}
          />
        {:else if activeDomain === 'shift'}
          <ShiftSection
            {currentUser}
            allUsers={[]}
            {rosterShifts}
            onSubmitSwap={handleCreateSwap}
            onAssignShift={handleAssignShift}
          />
        {/if}
      </main>

      <!-- Navigasi Bawah Khusus Tampilan Mobile -->
      <div class="block lg:hidden">
        <BottomNav
          role={currentUser.role}
          {activeDomain}
          {pendingApprovalsCount}
          onSelectNav={handleSelectNav}
        />
      </div>
    </div>

    <!-- Modal Bersama -->
    <BillingModal
      isOpen={isBillingModalOpen}
      {userProfile}
      activeInvoice={subscriptionInvoices[0] || null}
      plans={subscriptionPlans}
      onClose={() => (isBillingModalOpen = false)}
      onInvoiceUpdated={handleInvoiceUpdated}
    />

    <CreateWorkspaceModal
      isOpen={isCreateWorkspaceModalOpen}
      isOnboarding={userWorkspaces.length === 0}
      onClose={() => (isCreateWorkspaceModalOpen = false)}
      onSubmit={handleCreateWorkspace}
    />
  </div>
{/if}
