import { apiClient } from './api-client';
import type { CashAdvance } from '../types/app';

export class CashAdvanceService {
  public async requestCashAdvance(
    amount: number
  ): Promise<{ id: string; amount: number; request_date: string; status: string }> {
    const payload = {
      amount,
    };

    const response = await apiClient.post<{
      id: string;
      amount: number;
      request_date: string;
      status: string;
    }>('/cash-advances', payload);

    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Gagal mengajukan permohonan kasbon.');
  }

  public async getMyCashAdvances(): Promise<CashAdvance[]> {
    const response = await apiClient.get<CashAdvance[]>('/cash-advances/my');
    if (response.data && Array.isArray(response.data)) {
      return response.data;
    }

    return [];
  }

  public async getAdminCashAdvances(status?: string, branchId?: string): Promise<CashAdvance[]> {
    const params: Record<string, string> = {};
    if (status) params.status = status;
    if (branchId) params.branch_id = branchId;

    const response = await apiClient.get<CashAdvance[]>('/admin/cash-advances', params);
    if (response.data && Array.isArray(response.data)) {
      return response.data;
    }

    return [];
  }

  public async approveCashAdvance(
    id: string
  ): Promise<{ id: string; amount: number; status: string; approved_by_user_id?: string }> {
    const response = await apiClient.post<{
      id: string;
      amount: number;
      status: string;
      approved_by_user_id?: string;
    }>(`/admin/cash-advances/${id}/approve`);

    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Gagal menyetujui permohonan kasbon.');
  }

  public async rejectCashAdvance(
    id: string
  ): Promise<{ id: string; amount: number; status: string; approved_by_user_id?: string }> {
    const response = await apiClient.post<{
      id: string;
      amount: number;
      status: string;
      approved_by_user_id?: string;
    }>(`/admin/cash-advances/${id}/reject`);

    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Gagal menolak permohonan kasbon.');
  }
}

export const cashAdvanceService = new CashAdvanceService();
