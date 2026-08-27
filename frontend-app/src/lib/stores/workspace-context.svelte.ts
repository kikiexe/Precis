import { apiClient } from '../services/api-client';
import { shiftService } from '../services/shift-service';
import { attendanceService } from '../services/attendance-service';
import { cashAdvanceService } from '../services/cash-advance-service';
import { payrollService } from '../services/payroll-service';
import { billingService } from '../services/billing-service';
import { teamService } from '../services/team-service';
import { inventoryService } from '../services/inventory-service';
import type {
  User,
  UserProfile,
  UserWorkspace,
  AttendanceRecord,
  ShiftRosterItem,
  ShiftTemplateItem,
  PendingSwapItem,
  CashAdvance,
  PayrollSlipData,
  PayrollPreviewData,
  SubscriptionInvoice,
  SubscriptionPlanItem,
  TeamMember,
  BranchItem,
} from '../types/app';

export class WorkspaceContext {
  currentUser = $state<User>({
    id: 'usr-default',
    name: 'Pengguna',
    role: 'STAFF',
    email: '',
    branch_id: '',
    branch_name: '',
    base_salary: 3000000,
  });

  userProfile = $state<UserProfile | null>(null);
  userWorkspaces = $state<UserWorkspace[]>([]);
  activeWorkspaceId = $state<string | null>(null);
  workspaceBranches = $state<BranchItem[]>([]);
  selectedBranchFilter = $state<string>('ALL');

  todayAttendance = $state<AttendanceRecord | null>(null);
  allAttendances = $state<AttendanceRecord[]>([]);
  rosterShifts = $state<ShiftRosterItem[]>([]);
  shiftTemplates = $state<ShiftTemplateItem[]>([]);
  pendingSwaps = $state<PendingSwapItem[]>([]);
  myCashAdvances = $state<CashAdvance[]>([]);
  adminPendingKasbons = $state<CashAdvance[]>([]);
  myPayrollSlip = $state<PayrollSlipData | null>(null);
  adminPayrollPreview = $state<PayrollPreviewData | null>(null);
  subscriptionInvoices = $state<SubscriptionInvoice[]>([]);
  subscriptionPlans = $state<SubscriptionPlanItem[]>([]);
  teamMembers = $state<TeamMember[]>([]);

  pendingApprovalsCount = $derived(
    this.pendingSwaps.length + this.adminPendingKasbons.length
  );

  currentWorkspace = $derived(
    this.userWorkspaces.find((w) => w.workspace_id === this.activeWorkspaceId) || null
  );

  setSessionData(
    profile: {
      id: string;
      name: string;
      email: string;
      bank_name?: string | null;
      bank_account_number?: string | null;
      bank_account_holder?: string | null;
      subscription_status?: string;
      subscription_expires_at?: string;
      max_workspaces?: number;
      workspaces?: UserWorkspace[];
    },
    workspaces: UserWorkspace[]
  ) {
    this.userProfile = {
      ...profile,
      workspaces,
    };
    this.userWorkspaces = workspaces;

    const savedWsId = apiClient.getWorkspaceId();
    const matchedWs = workspaces.find((w) => w.workspace_id === savedWsId) || workspaces[0];

    if (matchedWs) {
      this.activeWorkspaceId = matchedWs.workspace_id;
      apiClient.setWorkspaceId(matchedWs.workspace_id);

      this.currentUser = {
        id: profile.id,
        name: profile.name,
        role: matchedWs.role,
        job_title: matchedWs.job_title,
        permissions: matchedWs.permissions || [],
        email: profile.email,
        bank_name: profile.bank_name,
        bank_account_number: profile.bank_account_number,
        bank_account_holder: profile.bank_account_holder,
        branch_id: matchedWs.branch_id || '',
        branch_name: matchedWs.branch_name || matchedWs.workspace_name,
        base_salary: 3000000,
      };
    } else {
      this.activeWorkspaceId = null;
      apiClient.setWorkspaceId(null);
      this.currentUser = {
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
    }
  }

  async switchWorkspace(workspace: UserWorkspace) {
    this.activeWorkspaceId = workspace.workspace_id;
    apiClient.setWorkspaceId(workspace.workspace_id);

    this.currentUser = {
      ...this.currentUser,
      role: workspace.role,
      job_title: workspace.job_title,
      permissions: workspace.permissions || [],
      branch_id: workspace.branch_id || this.currentUser.branch_id,
      branch_name: workspace.branch_name || workspace.workspace_name,
    };

    await this.loadWorkspaceData();
  }

  async loadWorkspaceData() {
    if (!this.activeWorkspaceId || this.userWorkspaces.length === 0) {
      this.rosterShifts = [];
      this.pendingSwaps = [];
      this.allAttendances = [];
      this.myCashAdvances = [];
      this.adminPendingKasbons = [];
      this.myPayrollSlip = null;
      this.adminPayrollPreview = null;
      this.teamMembers = [];
      return;
    }

    try {
      const branchesData = await inventoryService.getBranches().catch(() => []);
      this.workspaceBranches = branchesData;

      const branchId =
        this.selectedBranchFilter !== 'ALL'
          ? this.selectedBranchFilter
          : (this.currentUser.branch_id || undefined);
      
      const isManagement =
        this.currentUser.role === 'OWNER' ||
        this.currentUser.role === 'ADMIN' ||
        this.currentUser.role === 'MANAGER';

      const canViewPayroll =
        this.currentUser.role === 'OWNER' ||
        this.currentUser.role === 'ADMIN' ||
        Boolean(
          this.currentUser.permissions?.includes('payroll.view') ||
          this.currentUser.permissions?.includes('payroll.disburse')
        );

      const [
        rosterData,
        templatesData,
        swapData,
        wallData,
        myKasbons,
        adminKasbons,
        slipData,
        previewData,
        invoiceData,
        planData,
        membersData,
      ] = await Promise.all([
        shiftService.getRoster(branchId).catch(() => []),
        shiftService.getTemplates(branchId).catch(() => []),
        isManagement
          ? shiftService.getPendingSwapRequests(branchId).catch(() => [])
          : Promise.resolve([]),
        isManagement
          ? attendanceService.getWallOfFaces(branchId).catch(() => [])
          : Promise.resolve([]),
        cashAdvanceService.getMyCashAdvances().catch(() => []),
        isManagement
          ? cashAdvanceService.getAdminCashAdvances('PENDING', branchId).catch(() => [])
          : Promise.resolve([]),
        payrollService.getMySlip().catch(() => null),
        canViewPayroll
          ? payrollService.calculatePreview(undefined, undefined, branchId).catch(() => null)
          : Promise.resolve(null),
        billingService.getInvoices().catch(() => []),
        billingService.getPlans().catch(() => []),
        isManagement
          ? teamService.getMembers().catch(() => [])
          : Promise.resolve([]),
      ]);

      this.rosterShifts = rosterData;
      this.shiftTemplates = templatesData;
      this.pendingSwaps = swapData;
      this.myCashAdvances = myKasbons;
      this.adminPendingKasbons = adminKasbons;
      this.myPayrollSlip = slipData;
      this.adminPayrollPreview = previewData;
      this.subscriptionInvoices = invoiceData;
      this.subscriptionPlans = planData;
      this.teamMembers = membersData;

      if (wallData && wallData.length > 0) {
        this.allAttendances = wallData.map((item: any) => ({
          id: item.id,
          user_id: item.user_id || item.user?.id || '',
          user_name: item.user_name || item.user?.name || 'Staf',
          avatar_url: item.avatar_url || item.photo_in_url || '',
          branch_name: item.branch_name || item.branch?.name || '',
          shift_name: item.shift_name || item.shift?.name || 'Shift Pagi',
          clock_in_time: item.clock_in_time,
          clock_out_time: item.clock_out_time,
          photo_in_url: item.photo_in_url,
          photo_out_url: item.photo_out_url,
          lat_in: item.lat_in || -7.7654,
          lng_in: item.lng_in || 110.4091,
          status: (item.late_minutes && item.late_minutes > 0) ? 'LATE' : 'ON_TIME',
          late_minutes: item.late_minutes || 0,
          overtime_minutes: item.overtime_minutes || 0,
          created_at: item.created_at || item.clock_in_time,
        }));
      } else {
        this.allAttendances = [];
      }
    } catch (e) {
      console.warn('Gagal memuat data workspace:', e);
    }
  }

  reset() {
    this.userProfile = null;
    this.userWorkspaces = [];
    this.activeWorkspaceId = null;
    this.workspaceBranches = [];
    this.selectedBranchFilter = 'ALL';
    this.todayAttendance = null;
    this.allAttendances = [];
    this.rosterShifts = [];
    this.shiftTemplates = [];
    this.pendingSwaps = [];
    this.myCashAdvances = [];
    this.adminPendingKasbons = [];
    this.myPayrollSlip = null;
    this.adminPayrollPreview = null;
    this.subscriptionInvoices = [];
    this.subscriptionPlans = [];
    this.teamMembers = [];
  }
}

export const workspaceContext = new WorkspaceContext();
