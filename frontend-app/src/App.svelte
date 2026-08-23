<script lang="ts">
  import { onMount } from 'svelte';
  import { apiClient } from './lib/services/api-client';
  import { authService } from './lib/services/auth-service';
  import { shiftService } from './lib/services/shift-service';
  import { attendanceService } from './lib/services/attendance-service';
  import { cashAdvanceService } from './lib/services/cash-advance-service';
  import { payrollService } from './lib/services/payroll-service';
  import { billingService } from './lib/services/billing-service';
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
  } from './lib/types/app';
  import LoginView from './lib/components/auth/LoginView.svelte';
  import AppHeader from './lib/components/app/AppHeader.svelte';
  import AppSidebar from './lib/components/app/AppSidebar.svelte';
  import BottomNav from './lib/components/app/BottomNav.svelte';
  import StaffPresensiSection from './lib/components/app/StaffPresensiSection.svelte';
  import ShiftSection from './lib/components/app/ShiftSection.svelte';
  import FinanceSection from './lib/components/app/FinanceSection.svelte';
  import AdminAuditSection from './lib/components/app/AdminAuditSection.svelte';
  import BillingSection from './lib/components/app/BillingSection.svelte';
  import LiveCameraModal from './lib/components/app/LiveCameraModal.svelte';
  import BillingModal from './lib/components/app/BillingModal.svelte';

  // manajemen autentikasi & sesi
  let isAuthenticated = $state(false);
  let isCheckingSession = $state(true);
  let userWorkspaces = $state<UserWorkspace[]>([]);
  let activeWorkspaceId = $state<string | null>(null);
  let userProfile = $state<UserProfile | null>(null);

  // profil pengguna aktif
  let currentUser = $state<User>({
    id: 'usr-default',
    name: 'Pengguna',
    role: 'STAFF',
    email: '',
    branch_id: '',
    branch_name: '',
    base_salary: 3000000,
  });

  let activeTab = $state<'presensi' | 'shift' | 'finance' | 'admin' | 'billing'>('presensi');

  // visibilitas modal
  let isCameraModalOpen = $state(false);
  let cameraActionType = $state<'CLOCK_IN' | 'CLOCK_OUT'>('CLOCK_IN');
  let isBillingModalOpen = $state(false);

  // data dinamis
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

  // ambil permintaan PENDING
  let pendingApprovalsCount = $derived(
    pendingSwaps.length + adminPendingKasbons.length
  );

  onMount(() => {
    // daftarkan listener error global dari api client
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
      isAuthenticated = false;
      isCheckingSession = false;
      return;
    }

    try {
      const profile = await authService.getProfile();
      userProfile = profile;
      userWorkspaces = profile.workspaces || [];

      if (userWorkspaces.length > 0) {
        const savedWsId = apiClient.getWorkspaceId();
        const matchedWs = userWorkspaces.find((w) => w.workspace_id === savedWsId) || userWorkspaces[0];
        activeWorkspaceId = matchedWs.workspace_id;
        apiClient.setWorkspaceId(matchedWs.workspace_id);

        currentUser = {
          id: profile.id,
          name: profile.name,
          role: matchedWs.role,
          email: profile.email,
          branch_id: matchedWs.branch_id || 'branch-default',
          branch_name: matchedWs.branch_name || matchedWs.workspace_name,
          base_salary: 3000000,
        };
      } else {
        currentUser = {
          id: profile.id,
          name: profile.name,
          role: 'STAFF',
          email: profile.email,
          branch_id: '',
          branch_name: 'Semua Cabang',
          base_salary: 3000000,
        };
      }

      isAuthenticated = true;
      await loadWorkspaceData();
    } catch {
      authService.logout();
      isAuthenticated = false;
    } finally {
      isCheckingSession = false;
    }
  }

  async function loadWorkspaceData() {
    try {
      const branchId = currentUser.branch_id || undefined;
      const isAdminOrOwner = currentUser.role === 'OWNER' || currentUser.role === 'ADMIN';

      const [rosterData, swapData, wallData, myKasbons, adminKasbons, slipData, previewData, invoiceData, planData] = await Promise.all([
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
      ]);

      rosterShifts = rosterData;
      pendingSwaps = swapData;
      myCashAdvances = myKasbons;
      adminPendingKasbons = adminKasbons;
      myPayrollSlip = slipData;
      adminPayrollPreview = previewData;
      subscriptionInvoices = invoiceData;
      subscriptionPlans = planData;

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
        branch_id: firstWs.branch_id || 'branch-default',
        branch_name: firstWs.branch_name || firstWs.workspace_name,
        base_salary: 3000000,
      };
    } else {
      currentUser = {
        id: data.user.id,
        name: data.user.name,
        role: 'STAFF',
        email: data.user.email,
        branch_id: '',
        branch_name: 'Cabang Utama',
        base_salary: 3000000,
      };
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
      branch_id: workspace.branch_id || 'branch-default',
      branch_name: workspace.branch_name || workspace.workspace_name,
    };

    await loadWorkspaceData();
  }

  async function handleLogout() {
    await authService.logout();
    isAuthenticated = false;
    userWorkspaces = [];
    activeWorkspaceId = null;
    activeTab = 'presensi';
  }

  function handleOpenLiveCamera(type: 'CLOCK_IN' | 'CLOCK_OUT') {
    cameraActionType = type;
    isCameraModalOpen = true;
  }

  function handleCaptureSuccess(record: AttendanceRecord) {
    todayAttendance = record;
    allAttendances = [record, ...allAttendances.filter((a) => a.id !== record.id)];
  }

  async function handleCreateSwap(shiftAssignmentId: string, targetUserId: string) {
    await shiftService.requestSwap(shiftAssignmentId, targetUserId);
    await loadWorkspaceData();
  }

  async function handleAssignShift(shiftTemplateId: string, assignedUserId: string, date: string) {
    await shiftService.assignShift(shiftTemplateId, assignedUserId, date);
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

  async function handleFilterPayrollPeriod(periodStart: string, periodEnd: string) {
    const preview = await payrollService.calculatePreview(periodStart, periodEnd, currentUser.branch_id || undefined);
    adminPayrollPreview = preview;
  }

  async function handleDisbursePayroll(periodStart: string, periodEnd: string) {
    await payrollService.disbursePayroll(periodStart, periodEnd, currentUser.branch_id || undefined);
    await loadWorkspaceData();
  }

  async function handleExportCsv(periodStart: string, periodEnd: string, format: 'BCA' | 'MANDIRI') {
    await payrollService.downloadBankCsv(periodStart, periodEnd, format, currentUser.branch_id || undefined);
  }

  async function handleInvoiceUpdated() {
    try {
      const profile = await authService.getProfile();
      userProfile = profile;
    } catch {
      // biarkan silent jika gagal memuat profil terbaru
    }
    await loadWorkspaceData();
  }
</script>

{#if isCheckingSession}
  <!-- indikator pemuatan status login awal -->
  <div class="min-h-screen bg-[#f4f4f4] flex flex-col justify-center items-center p-4 select-none">
    <div class="w-10 h-10 bg-[#0f62fe] text-white flex items-center justify-center font-bold text-lg font-display animate-pulse mb-3">
      P
    </div>
    <div class="text-xs font-mono text-[#525252]">Memuat sesi akun Précis...</div>
  </div>
{:else if !isAuthenticated}
  <!-- tampilan login portal -->
  <LoginView onLoginSuccess={handleLoginSuccess} />
{:else}
  <!-- dashboard portal terautentikasi -->
  <div class="min-h-screen bg-[#f4f4f4] flex flex-row overflow-x-hidden font-sans select-none">
    <!-- sidebar khusus desktop -->
    <AppSidebar
      {currentUser}
      {userWorkspaces}
      {activeWorkspaceId}
      {activeTab}
      {pendingApprovalsCount}
      onSelectTab={(tab) => (activeTab = tab)}
      onSwitchWorkspace={handleSwitchWorkspace}
      onLogout={handleLogout}
    />

    <!-- area konten utama -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
      <!-- header atas -->
      <AppHeader
        {currentUser}
        {userWorkspaces}
        {activeWorkspaceId}
        onSwitchWorkspace={handleSwitchWorkspace}
        onOpenBilling={() => (isBillingModalOpen = true)}
        onLogout={handleLogout}
      />

      <!-- konten halaman aktif -->
      <main class="flex-1">
        {#if activeTab === 'presensi'}
          <StaffPresensiSection
            {currentUser}
            {todayAttendance}
            onOpenLiveCamera={handleOpenLiveCamera}
            onOpenSlipModal={() => (activeTab = 'finance')}
            onOpenKasbonTab={() => (activeTab = 'finance')}
          />
        {:else if activeTab === 'shift'}
          <ShiftSection
            {currentUser}
            allUsers={[]}
            {rosterShifts}
            onSubmitSwap={handleCreateSwap}
            onAssignShift={handleAssignShift}
          />
        {:else if activeTab === 'finance'}
          <FinanceSection
            cashAdvances={myCashAdvances}
            payrollSlip={myPayrollSlip}
            onRequestKasbon={handleCreateKasbon}
          />
        {:else if activeTab === 'admin'}
          <AdminAuditSection
            attendances={allAttendances}
            {pendingSwaps}
            pendingKasbons={adminPendingKasbons}
            payrollPreview={adminPayrollPreview}
            onApproveSwap={handleApproveSwap}
            onRejectSwap={handleRejectSwap}
            onApproveKasbon={handleApproveKasbon}
            onRejectKasbon={handleRejectKasbon}
            onFilterPayrollPeriod={handleFilterPayrollPeriod}
            onDisbursePayroll={handleDisbursePayroll}
            onExportCsv={handleExportCsv}
          />
        {:else if activeTab === 'billing'}
          <BillingSection
            {userProfile}
            invoices={subscriptionInvoices}
            plans={subscriptionPlans}
            onInvoiceUpdated={handleInvoiceUpdated}
          />
        {/if}
      </main>

      <!-- navigasi bawah khusus tampilan mobile -->
      <div class="block lg:hidden">
        <BottomNav
          activeTab={activeTab === 'billing' ? 'admin' : activeTab}
          {pendingApprovalsCount}
          onSelectTab={(tab: 'presensi' | 'shift' | 'finance' | 'admin') => (activeTab = tab)}
        />
      </div>
    </div>

    <!-- modal bersama -->
    <LiveCameraModal
      isOpen={isCameraModalOpen}
      {currentUser}
      actionType={cameraActionType}
      branchId={currentUser.branch_id}
      onClose={() => (isCameraModalOpen = false)}
      onSuccess={handleCaptureSuccess}
    />

    <BillingModal
      isOpen={isBillingModalOpen}
      {userProfile}
      activeInvoice={subscriptionInvoices[0] || null}
      plans={subscriptionPlans}
      onClose={() => (isBillingModalOpen = false)}
      onInvoiceUpdated={handleInvoiceUpdated}
    />
  </div>
{/if}
