import { apiClient } from './api-client';
import type { ShiftRosterItem, PendingSwapItem, ShiftTemplateItem } from '../types/app';

export class ShiftService {
  public async getRoster(
    branchId?: string,
    startDate?: string,
    endDate?: string
  ): Promise<ShiftRosterItem[]> {
    const params: Record<string, string> = {};
    if (branchId) params.branch_id = branchId;
    if (startDate) params.start_date = startDate;
    if (endDate) params.end_date = endDate;

    const response = await apiClient.get<ShiftRosterItem[]>('/shifts/roster', params);
    if (response.data && Array.isArray(response.data)) {
      return response.data;
    }

    return [];
  }

  public async assignShift(
    shiftTemplateId: string,
    assignedUserId: string,
    date: string
  ): Promise<{ id: string; shift_template_id: string; assigned_user_id: string; date: string }> {
    const payload = {
      shift_template_id: shiftTemplateId,
      assigned_user_id: assignedUserId,
      date,
    };

    const response = await apiClient.post<{
      id: string;
      shift_template_id: string;
      assigned_user_id: string;
      date: string;
    }>('/shifts/assign', payload);

    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Gagal menetapkan jadwal shift.');
  }

  public async deleteAssignment(shiftAssignmentId: string): Promise<boolean> {
    await apiClient.delete(`/shifts/assignments/${shiftAssignmentId}`);
    return true;
  }

  public async requestSwap(
    shiftAssignmentId: string,
    targetUserId: string
  ): Promise<{
    id: string;
    assigned_user_id: string;
    actual_user_id: string;
    is_swap: boolean;
    swap_status: string;
  }> {
    const payload = {
      shift_assignment_id: shiftAssignmentId,
      target_user_id: targetUserId,
    };

    const response = await apiClient.post<{
      id: string;
      assigned_user_id: string;
      actual_user_id: string;
      is_swap: boolean;
      swap_status: string;
    }>('/shifts/swap-requests', payload);

    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Gagal mengajukan pertukaran shift.');
  }

  public async getPendingSwapRequests(branchId?: string): Promise<PendingSwapItem[]> {
    const params: Record<string, string> = {};
    if (branchId) params.branch_id = branchId;

    const response = await apiClient.get<PendingSwapItem[]>('/admin/shifts/swap-requests', params);
    if (response.data && Array.isArray(response.data)) {
      return response.data;
    }

    return [];
  }

  public async approveSwap(
    shiftAssignmentId: string
  ): Promise<{ id: string; swap_status: string }> {
    const response = await apiClient.post<{ id: string; swap_status: string }>(
      `/admin/shifts/swap-requests/${shiftAssignmentId}/approve`
    );

    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Gagal menyetujui permohonan tukar shift.');
  }

  public async rejectSwap(shiftAssignmentId: string): Promise<{ id: string; swap_status: string }> {
    const response = await apiClient.post<{ id: string; swap_status: string }>(
      `/admin/shifts/swap-requests/${shiftAssignmentId}/reject`
    );

    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Gagal menolak permohonan tukar shift.');
  }

  public async getTemplates(branchId?: string): Promise<ShiftTemplateItem[]> {
    const params: Record<string, string> = {};
    if (branchId) params.branch_id = branchId;

    const response = await apiClient.get<ShiftTemplateItem[]>('/shifts/templates', params);
    if (response.data && Array.isArray(response.data)) {
      return response.data;
    }

    return [];
  }

  public async createTemplate(payload: {
    name: string;
    expected_clock_in: string;
    expected_clock_out: string;
    branch_id?: string | null;
  }): Promise<ShiftTemplateItem> {
    const response = await apiClient.post<ShiftTemplateItem>('/shifts/templates', payload);
    if (response.data) {
      return response.data;
    }
    throw new Error(response.message || 'Gagal membuat template shift.');
  }

  public async deleteTemplate(templateId: string): Promise<void> {
    await apiClient.delete(`/shifts/templates/${templateId}`);
  }
}

export const shiftService = new ShiftService();
