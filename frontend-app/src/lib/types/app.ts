export type Role = 'STAFF' | 'ADMIN' | 'OWNER' | 'SUPERADMIN';

export interface UserWorkspace {
  workspace_id: string;
  workspace_name: string;
  workspace_slug: string;
  role: Role;
  branch_id: string | null;
  branch_name: string | null;
}

export interface UserProfile {
  id: string;
  name: string;
  email: string;
  subscription_status?: string;
  subscription_expires_at?: string;
  max_workspaces?: number;
  workspaces: UserWorkspace[];
}

export interface LoginResponseData {
  token: string;
  user: {
    id: string;
    name: string;
    email: string;
    subscription_status?: string;
  };
  workspaces: UserWorkspace[];
}

export interface User {
  id: string;
  name: string;
  role: Role;
  email: string;
  avatar_url?: string;
  branch_id: string;
  branch_name: string;
  base_salary: number;
}

export interface ShiftTemplateItem {
  id: string;
  name: string;
  branch_id: string;
  branch_name?: string;
  expected_clock_in: string;
  expected_clock_out: string;
}

export interface ShiftRosterItem {
  id: string;
  date: string;
  is_swap: boolean;
  swap_status: 'NONE' | 'PENDING' | 'APPROVED' | 'REJECTED';
  template: ShiftTemplateItem | null;
  assigned_user: {
    id: string;
    name: string;
    email: string;
  };
  actual_user?: {
    id: string;
    name: string;
    email: string;
  } | null;
  swap_approved_by?: {
    id: string;
    name: string;
    email: string;
  } | null;
}

export interface PendingSwapItem {
  id: string;
  date: string;
  template: ShiftTemplateItem | null;
  assigned_user: {
    id: string;
    name: string;
    email: string;
  };
  actual_user: {
    id: string;
    name: string;
    email: string;
  };
  created_at?: string;
}

export interface ShiftAssignment {
  id: string;
  user_id: string;
  user_name: string;
  date: string;
  shift_name: string;
  start_time: string;
  end_time: string;
  branch_name: string;
  is_swap?: boolean;
  swap_status?: 'NONE' | 'PENDING' | 'APPROVED' | 'REJECTED';
}

export interface ShiftSwapRequest {
  id: string;
  requestor_user_id: string;
  requestor_name: string;
  target_user_id: string;
  target_name: string;
  original_date: string;
  target_date: string;
  reason: string;
  status: 'PENDING' | 'APPROVED' | 'REJECTED';
  created_at: string;
}

export interface AttendanceRecord {
  id: string;
  user_id: string;
  user_name: string;
  avatar_url: string;
  branch_name: string;
  shift_name: string;
  clock_in_time: string;
  clock_out_time?: string;
  photo_in_url: string;
  photo_out_url?: string;
  lat_in: number;
  lng_in: number;
  status: 'ON_TIME' | 'LATE';
  late_minutes: number;
  overtime_minutes?: number;
  created_at: string;
}

export interface PresignUploadResponseData {
  upload_url: string;
  key: string;
  public_url: string;
  expires_in_seconds: number;
}

export interface ClockInResponseData {
  id: string;
  branch_id: string;
  clock_in_time: string;
  late_minutes: number;
  status: 'ON_TIME' | 'LATE';
  photo_in_url: string;
}

export interface ClockOutResponseData {
  id: string;
  branch_id: string;
  clock_out_time: string;
  overtime_minutes: number;
  photo_out_url: string;
}

export interface WallOfFacesItem {
  id: string;
  user_id: string;
  user_name: string;
  avatar_url?: string;
  branch_id: string;
  branch_name: string;
  shift_name?: string;
  clock_in_time: string;
  clock_out_time?: string;
  photo_in_url: string;
  photo_out_url?: string;
  status: 'ON_TIME' | 'LATE';
  late_minutes: number;
  date: string;
}

export interface CashAdvance {
  id: string;
  user_id?: string;
  user_name?: string;
  user?: {
    id: string;
    name: string;
    email: string;
  };
  amount: number;
  purpose?: string;
  request_date: string;
  status: 'PENDING' | 'APPROVED' | 'DEDUCTED' | 'REJECTED';
  approved_by?: {
    id: string;
    name: string;
    email: string;
  } | string | null;
  deducted_at_payroll_date?: string | null;
  created_at?: string;
}

export interface PayrollMemberItem {
  workspace_member_id: string;
  user_id: string;
  name: string;
  email?: string;
  role: Role;
  branch_id?: string | null;
  branch_name?: string | null;
  base_salary: number;
  total_late_minutes: number;
  late_penalty: number;
  total_overtime_minutes: number;
  overtime_pay: number;
  cash_advance_deduction: number;
  net_salary: number;
}

export interface PayrollPreviewTotals {
  total_base_salary: number;
  total_overtime_pay: number;
  total_late_penalty: number;
  total_cash_advance_deduction: number;
  total_net_salary: number;
}

export interface PayrollPreviewData {
  period_start: string;
  period_end: string;
  items: PayrollMemberItem[];
  totals?: PayrollPreviewTotals;
  total_members?: number;
  total_base_salary?: number;
  total_overtime_pay?: number;
  total_late_penalty?: number;
  total_cash_advance_deduction?: number;
  total_net_salary?: number;
}

export interface PayrollSlipData {
  id?: string | null;
  user_name?: string;
  user_email?: string;
  user?: {
    id: string;
    name: string;
    email: string;
  };
  role?: Role;
  branch_name?: string | null;
  period_start: string;
  period_end: string;
  base_salary: number;
  total_late_minutes?: number;
  late_penalty: number;
  total_overtime_minutes?: number;
  overtime_pay: number;
  cash_advance_deduction: number;
  net_salary: number;
  status?: 'DISBURSED' | 'ESTIMATED';
  disbursement_status?: 'PAID' | 'PENDING';
  disbursed_at?: string | null;
}

export interface DisbursePayrollResult {
  period_start: string;
  period_end: string;
  disbursed_count: number;
  total_amount?: number;
  total_net_disbursed?: number;
}

export interface PayrollSlip {
  period: string;
  user_name: string;
  role: string;
  branch_name: string;
  base_salary: number;
  overtime_hours: number;
  overtime_pay: number;
  late_minutes: number;
  late_penalty: number;
  cash_advance_deduction: number;
  net_salary: number;
  disbursement_status: 'PAID' | 'PENDING';
}

export interface SubscriptionInvoice {
  invoice_number: string;
  plan_name: string;
  outlet_quota: number;
  amount_base: number;
  unique_code: number;
  total_amount: number;
  due_date: string;
  status: 'UNPAID' | 'PENDING_VERIFICATION' | 'PAID';
}
