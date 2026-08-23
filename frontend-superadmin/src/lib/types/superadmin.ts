export interface SuperadminUser {
  id: string;
  name: string;
  email: string;
}

export interface PaymentConfirmationRecord {
  id: string;
  bank_account_name: string;
  transfer_amount: number;
  proof_image_url: string;
  verified_at: string | null;
  created_at: string;
}

export interface InvoiceUserWorkspace {
  id: string;
  name: string;
  slug: string;
}

export interface InvoiceUser {
  id: string;
  name: string;
  email: string;
  subscription_status: 'TRIAL' | 'ACTIVE' | 'GRACE_PERIOD' | 'SUSPENDED';
  workspaces: InvoiceUserWorkspace[];
}

export interface InvoiceRecord {
  id: string;
  invoice_number: string;
  amount_base: number;
  unique_code: number;
  total_amount: number;
  status: 'UNPAID' | 'PENDING_VERIFICATION' | 'PAID' | 'EXPIRED';
  due_date: string | null;
  created_at: string;
  user: InvoiceUser | null;
  confirmation: PaymentConfirmationRecord | null;
}

export interface TenantBranch {
  id: string;
  name: string;
}

export interface TenantWorkspace {
  id: string;
  name: string;
  slug: string;
  status: 'ACTIVE' | 'INACTIVE';
  branches_count: number;
  branches: TenantBranch[];
}

export interface TenantRecord {
  id: string;
  name: string;
  email: string;
  subscription_status: 'TRIAL' | 'ACTIVE' | 'GRACE_PERIOD' | 'SUSPENDED';
  subscription_expires_at: string | null;
  days_remaining: number | null;
  max_workspaces: number;
  created_at: string;
  workspaces: TenantWorkspace[];
}

export interface SaaSMetrics {
  mrr: number;
  arr: number;
  total_revenue: number;
  tenants: {
    total: number;
    active: number;
    grace_period: number;
    suspended: number;
    trial: number;
  };
  total_branches: number;
  invoices: {
    pending: number;
    unpaid: number;
    paid: number;
  };
  timestamp: string;
}

export interface SubscriptionPlanRecord {
  id: string;
  name: string;
  max_workspaces: number;
  monthly_price: number;
  annual_price: number;
  is_active: boolean;
}

export interface SuperadminAuthResponse {
  token: string;
  superadmin: SuperadminUser;
}
