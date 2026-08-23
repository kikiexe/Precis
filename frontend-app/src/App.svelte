<script lang="ts">
  import { onMount } from 'svelte';
  import type { User, UserWorkspace, AttendanceRecord, ShiftSwapRequest, CashAdvance, PayrollSlip, ShiftAssignment, LoginResponseData } from './lib/types/app';
  import { apiClient } from './lib/services/api-client';
  import { authService } from './lib/services/auth-service';
  import AppHeader from './lib/components/app/AppHeader.svelte';
  import AppSidebar from './lib/components/app/AppSidebar.svelte';
  import BottomNav from './lib/components/app/BottomNav.svelte';
  import StaffPresensiSection from './lib/components/app/StaffPresensiSection.svelte';
  import LiveCameraModal from './lib/components/app/LiveCameraModal.svelte';
  import ShiftSection from './lib/components/app/ShiftSection.svelte';
  import FinanceSection from './lib/components/app/FinanceSection.svelte';
  import AdminAuditSection from './lib/components/app/AdminAuditSection.svelte';
  import BillingSection from './lib/components/app/BillingSection.svelte';
  import BillingModal from './lib/components/app/BillingModal.svelte';
  import LoginView from './lib/components/auth/LoginView.svelte';

  // state autentikasi dan sesi pengguna
  let isInitializing = $state(true);
  let isAuthenticated = $state(false);
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
    const token = apiClient.getToken();
    if (!token) {
      isInitializing = false;
      isAuthenticated = false;
      return;
    }

    try {
      const profile = await authService.getProfile();
      userWorkspaces = profile.workspaces || [];

      // ambil workspace tersimpan atau gunakan workspace pertama
      const savedWorkspaceId = apiClient.getWorkspaceId();
      const matchedWorkspace = userWorkspaces.find((w) => w.workspace_id === savedWorkspaceId) || userWorkspaces[0];

      if (matchedWorkspace) {
        activeWorkspaceId = matchedWorkspace.workspace_id;
        apiClient.setWorkspaceId(matchedWorkspace.workspace_id);

        currentUser = {
          id: profile.id,
          name: profile.name,
          email: profile.email,
          role: matchedWorkspace.role,
          branch_id: matchedWorkspace.branch_id || 'all-branch',
          branch_name: matchedWorkspace.branch_name || matchedWorkspace.workspace_name,
          base_salary: 3500000,
        };
      } else {
        currentUser = {
          id: profile.id,
          name: profile.name,
          email: profile.email,
          role: 'STAFF',
          branch_id: 'default',
          branch_name: 'Outlet',
          base_salary: 3000000,
        };
      }

      isAuthenticated = true;
    } catch {
      apiClient.clearSession();
      isAuthenticated = false;
    } finally {
      isInitializing = false;
    }
  }

  function handleLoginSuccess(loginData: LoginResponseData) {
    userWorkspaces = loginData.workspaces || [];
    const firstWs = userWorkspaces[0];

    if (firstWs) {
      activeWorkspaceId = firstWs.workspace_id;
      apiClient.setWorkspaceId(firstWs.workspace_id);

      currentUser = {
        id: loginData.user.id,
        name: loginData.user.name,
        email: loginData.user.email,
        role: firstWs.role,
        branch_id: firstWs.branch_id || 'all-branch',
        branch_name: firstWs.branch_name || firstWs.workspace_name,
        base_salary: 3500000,
      };

      if (firstWs.role === 'OWNER' || firstWs.role === 'ADMIN') {
        activeTab = 'admin';
      } else {
        activeTab = 'presensi';
      }
    } else {
      currentUser = {
        id: loginData.user.id,
        name: loginData.user.name,
        email: loginData.user.email,
        role: 'STAFF',
        branch_id: 'default',
        branch_name: 'Outlet',
        base_salary: 3000000,
      };
      activeTab = 'presensi';
    }

    isAuthenticated = true;
  }

  function handleSwitchWorkspace(workspace: UserWorkspace) {
    activeWorkspaceId = workspace.workspace_id;
    apiClient.setWorkspaceId(workspace.workspace_id);

    currentUser = {
      ...currentUser,
      role: workspace.role,
      branch_id: workspace.branch_id || 'all-branch',
      branch_name: workspace.branch_name || workspace.workspace_name,
    };

    // sesuaikan tab aktif berdasarkan peran jika diperlukan
    if (workspace.role === 'STAFF' && (activeTab === 'admin' || activeTab === 'billing')) {
      activeTab = 'presensi';
    }
  }

  async function handleLogout() {
    await authService.logout();
    isAuthenticated = false;
    userWorkspaces = [];
    activeWorkspaceId = null;
    activeTab = 'presensi';
  }

  function handleCaptureSuccess(record: AttendanceRecord) {
    todayAttendance = record;
    allAttendances = [record, ...allAttendances];
  }

  function handleCreateSwap(req: ShiftSwapRequest) {
    swapRequests = [req, ...swapRequests];
  }

  function handleCreateKasbon(amount: number, purpose: string) {
    const newKasbon: CashAdvance = {
      id: `kb-${Date.now()}`,
      user_id: currentUser.id,
      user_name: currentUser.name,
      amount,
      purpose,
      request_date: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }),
      status: 'PENDING',
    };
    cashAdvances = [newKasbon, ...cashAdvances];
  }

  function handleApproveSwap(swapId: string) {
    swapRequests = swapRequests.map((s) => (s.id === swapId ? { ...s, status: 'APPROVED' } : s));
  }

  function handleRejectSwap(swapId: string) {
    swapRequests = swapRequests.map((s) => (s.id === swapId ? { ...s, status: 'REJECTED' } : s));
  }

  function handleApproveKasbon(kasbonId: string) {
    cashAdvances = cashAdvances.map((k) =>
      k.id === kasbonId ? { ...k, status: 'APPROVED', approved_by: currentUser.name } : k
    );
  }

  function handleRejectKasbon(kasbonId: string) {
    cashAdvances = cashAdvances.map((k) => (k.id === kasbonId ? { ...k, status: 'REJECTED' } : k));
  }
</script>

{#if isInitializing}
  <div class="min-h-screen bg-[#f4f4f4] flex flex-col items-center justify-center select-none">
    <div class="w-10 h-10 bg-[#0f62fe] text-white flex items-center justify-center font-bold text-lg animate-pulse mb-3">
      P
    </div>
    <div class="text-xs font-mono text-[#525252]">Memuat sesi akun Précis...</div>
  </div>
{:else if !isAuthenticated}
  <LoginView onLoginSuccess={handleLoginSuccess} />
{:else}
  <div class="min-h-screen bg-[#f4f4f4] flex flex-row text-[#161616] font-sans antialiased">
    <!-- 1. navigasi sidebar desktop -->
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

    <!-- 2. area konten utama -->
    <div class="flex-1 flex flex-col min-h-screen bg-[#f4f4f4] overflow-y-auto">
      <!-- baris header atas khusus tampilan mobile -->
      <div class="block lg:hidden">
        <AppHeader
          {currentUser}
          {userWorkspaces}
          {activeWorkspaceId}
          onSwitchWorkspace={handleSwitchWorkspace}
          onOpenBilling={() => (isBillingModalOpen = true)}
          onLogout={handleLogout}
        />
      </div>

      <!-- bagian tampilan aktif -->
      <main class="flex-1">
        {#if activeTab === 'presensi'}
          <StaffPresensiSection
            {currentUser}
            {todayAttendance}
            onOpenLiveCamera={() => (isCameraModalOpen = true)}
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
          onSelectTab={(tab) => (activeTab = tab)}
        />
      </div>
    </div>

    <!-- shared modal -->
    <LiveCameraModal
      isOpen={isCameraModalOpen}
      {currentUser}
      onClose={() => (isCameraModalOpen = false)}
      onSuccess={handleCaptureSuccess}
    />

    <BillingModal
      isOpen={isBillingModalOpen}
      onClose={() => (isBillingModalOpen = false)}
    />
  </div>
{/if}
