import { apiClient } from './api-client';
import type { WorkspaceRole, PermissionsCatalog } from '../types/app';

export class RoleService {
  /**
   * Mengambil seluruh daftar custom & system roles di workspace aktif
   */
  async getRoles(): Promise<WorkspaceRole[]> {
    const res = await apiClient.get<{ roles: WorkspaceRole[] }>('/roles');
    return ((res.roles || res.data) as unknown as WorkspaceRole[]) || [];
  }

  /**
   * Mengambil katalog seluruh permission modul dan preset template
   */
  async getPermissionsCatalog(): Promise<PermissionsCatalog> {
    const res = await apiClient.get<PermissionsCatalog>('/roles/permissions-catalog');
    return res as unknown as PermissionsCatalog;
  }

  /**
   * Membuat peran kustom baru
   */
  async createRole(data: {
    name: string;
    description?: string;
    permissions: string[];
  }): Promise<WorkspaceRole> {
    const res = await apiClient.post<{ message: string; role: WorkspaceRole }>('/roles', data);
    const role = (res.role || res.data) as unknown as WorkspaceRole | undefined;
    if (!role) {
      throw new Error(res.message || 'Gagal membuat peran kustom.');
    }
    return role;
  }

  /**
   * Memperbarui nama, deskripsi, atau checklist permissions suatu role
   */
  async updateRole(
    id: string,
    data: {
      name?: string;
      description?: string | null;
      permissions?: string[];
    }
  ): Promise<WorkspaceRole> {
    const res = await apiClient.put<{ message: string; role: WorkspaceRole }>(`/roles/${id}`, data);
    const role = (res.role || res.data) as unknown as WorkspaceRole | undefined;
    if (!role) {
      throw new Error(res.message || 'Gagal memperbarui peran.');
    }
    return role;
  }

  /**
   * Menghapus peran kustom yang tidak lagi digunakan
   */
  async deleteRole(id: string): Promise<void> {
    await apiClient.delete<{ message: string }>(`/roles/${id}`);
  }
}

export const roleService = new RoleService();
