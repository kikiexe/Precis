import { apiClient } from './api-client';
import type {
  PayrollSlipData,
  PayrollPreviewData,
  DisbursePayrollResult,
} from '../types/app';

export class PayrollService {
  public async getMySlip(
    periodStart?: string,
    periodEnd?: string
  ): Promise<PayrollSlipData | null> {
    const params: Record<string, string> = {};
    if (periodStart) params.period_start = periodStart;
    if (periodEnd) params.period_end = periodEnd;

    const response = await apiClient.get<PayrollSlipData>('/payroll/my-slip', params);
    if (response.data) {
      return response.data;
    }

    return null;
  }

  public async calculatePreview(
    periodStart?: string,
    periodEnd?: string,
    branchId?: string
  ): Promise<PayrollPreviewData | null> {
    const params: Record<string, string> = {};
    if (periodStart) params.period_start = periodStart;
    if (periodEnd) params.period_end = periodEnd;
    if (branchId) params.branch_id = branchId;

    const response = await apiClient.get<PayrollPreviewData>('/admin/payroll/preview', params);
    if (response.data) {
      return response.data;
    }

    return null;
  }

  public async disbursePayroll(
    periodStart: string,
    periodEnd: string,
    branchId?: string
  ): Promise<DisbursePayrollResult> {
    const payload = {
      period_start: periodStart,
      period_end: periodEnd,
      branch_id: branchId || undefined,
    };

    const response = await apiClient.post<DisbursePayrollResult>('/admin/payroll/disburse', payload);
    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Gagal mengeksekusi pencairan gaji.');
  }

  public async downloadBankCsv(
    periodStart: string,
    periodEnd: string,
    format: 'BCA' | 'MANDIRI' = 'BCA',
    branchId?: string
  ): Promise<void> {
    const token = apiClient.getToken();
    const workspaceId = apiClient.getWorkspaceId();

    const query = new URLSearchParams({
      period_start: periodStart,
      period_end: periodEnd,
      format,
    });
    if (branchId) query.set('branch_id', branchId);

    const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1';
    const url = `${baseUrl}/admin/payroll/export-csv?${query.toString()}`;

    const headers: Record<string, string> = {};
    if (token) headers['Authorization'] = `Bearer ${token}`;
    if (workspaceId) headers['X-Workspace-Id'] = workspaceId;

    const res = await fetch(url, {
      method: 'GET',
      headers,
    });

    if (!res.ok) {
      throw new Error(`Gagal mengunduh file CSV payroll: HTTP ${res.status}`);
    }

    const blob = await res.blob();
    const filename = `payroll_${format.toLowerCase()}_${periodStart}.csv`;

    const blobUrl = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = blobUrl;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(blobUrl);
  }
}

export const payrollService = new PayrollService();
