import { superadminApiClient } from './api-client';
import type {
  SuperadminAuthResponse,
  SuperadminUser,
  InvoiceRecord,
  SaaSMetrics,
  TenantRecord,
  SubscriptionPlanRecord,
} from '../types/superadmin';

export const superadminService = {
  async login(email: string, password: string): Promise<SuperadminAuthResponse> {
    const response = await superadminApiClient.post<SuperadminAuthResponse>(
      '/superadmin/auth/login',
      { email, password },
      { skipAuth: true }
    );
    if (response.data?.token) {
      superadminApiClient.setToken(response.data.token);
    }
    return response.data!;
  },

  async getProfile(): Promise<SuperadminUser> {
    const response = await superadminApiClient.get<SuperadminUser>('/superadmin/auth/me');
    return response.data!;
  },

  async logout(): Promise<void> {
    try {
      await superadminApiClient.post('/superadmin/auth/logout');
    } finally {
      superadminApiClient.clearSession();
    }
  },

  async getMetrics(): Promise<SaaSMetrics> {
    const response = await superadminApiClient.get<SaaSMetrics>('/superadmin/metrics');
    return response.data!;
  },

  async getInvoices(status?: string): Promise<InvoiceRecord[]> {
    const params = status ? { status } : undefined;
    const response = await superadminApiClient.get<InvoiceRecord[]>('/superadmin/invoices', { params });
    return response.data || [];
  },

  async verifyInvoice(invoiceId: string): Promise<{ id: string; status: string; subscription_status: string }> {
    const response = await superadminApiClient.post<{ id: string; status: string; subscription_status: string }>(
      `/superadmin/invoices/${invoiceId}/verify`
    );
    return response.data!;
  },

  async getTenants(status?: string): Promise<TenantRecord[]> {
    const params = status ? { status } : undefined;
    const response = await superadminApiClient.get<TenantRecord[]>('/superadmin/tenants', { params });
    return response.data || [];
  },

  async updateTenantStatus(tenantId: string, status: 'ACTIVE' | 'SUSPENDED' | 'GRACE_PERIOD' | 'TRIAL'): Promise<TenantRecord> {
    const response = await superadminApiClient.post<TenantRecord>(`/superadmin/tenants/${tenantId}/status`, { status });
    return response.data!;
  },

  async extendTenantSubscription(tenantId: string, days: number = 30): Promise<TenantRecord> {
    const response = await superadminApiClient.post<TenantRecord>(`/superadmin/tenants/${tenantId}/extend`, { days });
    return response.data!;
  },

  async getPlans(): Promise<SubscriptionPlanRecord[]> {
    const response = await superadminApiClient.get<SubscriptionPlanRecord[]>('/superadmin/plans');
    return response.data || [];
  },
};
