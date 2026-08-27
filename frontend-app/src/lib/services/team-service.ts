import { apiClient } from './api-client';
import type { TeamMember } from '../types/app';

class TeamService {
  async getMembers(): Promise<TeamMember[]> {
    const res = await apiClient.get<TeamMember[]>('/admin/members');
    return res.data || [];
  }

  async addMember(data: {
    name: string;
    email: string;
    job_title: string;
    role?: string;
    role_id?: string | null;
    branch_id?: string | null;
    base_salary: number;
  }): Promise<TeamMember> {
    const res = await apiClient.post<TeamMember>('/admin/members', data);
    if (!res.data) {
      throw new Error(res.message || 'Gagal menambahkan karyawan.');
    }
    return res.data;
  }

  async updateMember(
    id: string,
    data: {
      job_title?: string;
      role?: string;
      role_id?: string | null;
      branch_id?: string | null;
      base_salary?: number;
      is_active?: boolean;
    }
  ): Promise<TeamMember> {
    const res = await apiClient.put<TeamMember>(`/admin/members/${id}`, data);
    if (!res.data) {
      throw new Error(res.message || 'Gagal memperbarui data karyawan.');
    }
    return res.data;
  }

  async deleteMember(id: string): Promise<void> {
    await apiClient.delete(`/admin/members/${id}`);
  }
}

export const teamService = new TeamService();
