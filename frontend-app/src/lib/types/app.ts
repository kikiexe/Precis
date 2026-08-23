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
  lat_in: number;
  lng_in: number;
  status: 'ON_TIME' | 'LATE';
  late_minutes: number;
  created_at: string;
}

export interface CashAdvance {
  id: string;
  user_id: string;
  user_name: string;
  amount: number;
  purpose: string;
  request_date: string;
  status: 'PENDING' | 'APPROVED' | 'DEDUCTED' | 'REJECTED';
  approved_by?: string;
  deducted_at?: string;
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
