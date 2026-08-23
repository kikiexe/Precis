<script lang="ts">
  import { onMount } from 'svelte';
  import { apiClient } from './lib/services/api-client';
  import { authService } from './lib/services/auth-service';
  import type {
    User,
    UserWorkspace,
    AttendanceRecord,
    ShiftSwapRequest,
    CashAdvance,
    ShiftAssignment,
    PayrollSlip,
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

  // membersihkan data collection yang belum memiliki nilai
  let todayAttendance = $state<AttendanceRecord | null>(null);
  let allAttendances = $state<AttendanceRecord[]>([]);
  let swapRequests = $state<ShiftSwapRequest[]>([]);
  let cashAdvances = $state<CashAdvance[]>([]);
  let shifts = $state<ShiftAssignment[]>([]);
  let payrollSlip = $state<PayrollSlip | null>(null);

  // ambil permintaan PENDING
  let pendingApprovalsCount = $derived(
    swapRequests.filter((s) => s.status === 'PENDING').length +
      cashAdvances.filter((k) => k.status === 'PENDING').length
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
    } catch {
      authService.logout();
      isAuthenticated = false;
    } finally {
      isCheckingSession = false;
    }
  }

  function handleLoginSuccess(data: LoginResponseData) {
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
  }

  function handleSwitchWorkspace(workspace: UserWorkspace) {
    activeWorkspaceId = workspace.workspace_id;
    apiClient.setWorkspaceId(workspace.workspace_id);

    currentUser = {
      ...currentUser,
      role: workspace.role,
      branch_id: workspace.branch_id || 'branch-default',
      branch_name: workspace.branch_name || workspace.workspace_name,
    };
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

  function handleCreateSwap(req: Omit<ShiftSwapRequest, 'id' | 'created_at' | 'status'>) {
    const newSwap: ShiftSwapRequest = {
      ...req,
      id: `swap-${Date.now()}`,
      status: 'PENDING',
      created_at: new Date().toISOString(),
    };
    swapRequests = [newSwap, ...swapRequests];
  }

  function handleApproveSwap(swapId: string) {
    swapRequests = swapRequests.map((s) => (s.id === swapId ? { ...s, status: 'APPROVED' as const } : s));
  }

  function handleRejectSwap(swapId: string) {
    swapRequests = swapRequests.map((s) => (s.id === swapId ? { ...s, status: 'REJECTED' as const } : s));
  }

  function handleCreateKasbon(amount: number, purpose: string) {
    const newKasbon: CashAdvance = {
      id: `kasbon-${Date.now()}`,
      user_id: currentUser.id,
      user_name: currentUser.name,
      amount,
      purpose,
      request_date: new Date().toISOString().split('T')[0],
      status: 'PENDING',
    };
    cashAdvances = [newKasbon, ...cashAdvances];
  }

  function handleApproveKasbon(kasbonId: string) {
    cashAdvances = cashAdvances.map((k) =>
      k.id === kasbonId
        ? { ...k, status: 'APPROVED' as const, approved_by: currentUser.name }
        : k
    );
  }

  function handleRejectKasbon(kasbonId: string) {
    cashAdvances = cashAdvances.map((k) => (k.id === kasbonId ? { ...k, status: 'REJECTED' as const } : k));
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
            {shifts}
            {swapRequests}
            onSubmitSwap={handleCreateSwap}
          />
        {:else if activeTab === 'finance'}
          <FinanceSection
            {cashAdvances}
            {payrollSlip}
            onRequestKasbon={handleCreateKasbon}
          />
        {:else if activeTab === 'admin'}
          <AdminAuditSection
            attendances={allAttendances}
            pendingSwaps={swapRequests.filter((s) => s.status === 'PENDING')}
            pendingKasbons={cashAdvances.filter((k) => k.status === 'PENDING')}
            payrollList={[]}
            onApproveSwap={handleApproveSwap}
            onRejectSwap={handleRejectSwap}
            onApproveKasbon={handleApproveKasbon}
            onRejectKasbon={handleRejectKasbon}
          />
        {:else if activeTab === 'billing'}
          <BillingSection
            onSimulateUpload={() => alert('Bukti transfer terkirim untuk verifikasi Superadmin.')}
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

    <!-- shared modal -->
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
      onClose={() => (isBillingModalOpen = false)}
    />
  </div>
{/if}
