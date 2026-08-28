import { apiClient } from './api-client';
import type { WorkspaceInvitationItem, PublicInvitationDetails } from '../types/app';

export class InvitationService {
  /**
   * Ambil daftar undangan pending untuk workspace yang aktif
   */
  async getPendingInvitations(): Promise<WorkspaceInvitationItem[]> {
    const res = await apiClient.get<WorkspaceInvitationItem[]>('/admin/invitations');
    return res.data || [];
  }

  /**
   * Kirim undangan ke alamat email calon karyawan baru
   */
  async inviteMember(payload: {
    email: string;
    job_title: string;
    role?: string;
    role_id?: string | null;
    base_salary: number;
    branch_id?: string | null;
  }): Promise<WorkspaceInvitationItem> {
    const res = await apiClient.post<WorkspaceInvitationItem>('/admin/invitations', payload);
    if (res.data) {
      return res.data;
    }
    throw new Error(res.message || 'Gagal mengirimkan undangan tim.');
  }

  /**
   * Batalkan undangan yang belum diterima
   */
  async cancelInvitation(invitationId: string): Promise<void> {
    await apiClient.delete<{ message: string }>(`/admin/invitations/${invitationId}`);
  }

  /**
   * Kirim ulang email undangan
   */
  async resendInvitation(invitationId: string): Promise<WorkspaceInvitationItem> {
    const res = await apiClient.post<WorkspaceInvitationItem>(
      `/admin/invitations/${invitationId}/resend`
    );
    if (res.data) {
      return res.data;
    }
    throw new Error(res.message || 'Gagal mengirim ulang undangan.');
  }

  /**
   * Ambil informasi detail undangan berdasarkan token publik
   */
  async getInvitationByToken(token: string): Promise<PublicInvitationDetails> {
    const res = await apiClient.get<PublicInvitationDetails>(`/invitations/${token}`, {
      skipAuth: true,
      skipWorkspace: true,
    });
    if (res.data) {
      return res.data;
    }
    throw new Error(res.message || 'Informasi undangan tidak ditemukan.');
  }

  /**
   * Terima undangan tim untuk bergabung ke workspace
   */
  async acceptInvitation(
    token: string,
    name?: string,
    password?: string
  ): Promise<{
    token: string;
    user: { id: string; name: string; email: string };
    member: { id: string; role: string; job_title: string };
  }> {
    const res = await apiClient.post<{
      token: string;
      user: { id: string; name: string; email: string };
      member: { id: string; role: string; job_title: string };
    }>(
      `/invitations/${token}/accept`,
      {
        name: name || undefined,
        password: password || undefined,
      },
      { skipAuth: true, skipWorkspace: true }
    );
    if (res.data) {
      return res.data;
    }
    throw new Error(res.message || 'Gagal menerima undangan tim.');
  }

  /**
   * Tolak undangan tim
   */
  async rejectInvitation(token: string): Promise<void> {
    await apiClient.post<{ message: string }>(
      `/invitations/${token}/reject`,
      {},
      { skipAuth: true, skipWorkspace: true }
    );
  }
}

export const invitationService = new InvitationService();
