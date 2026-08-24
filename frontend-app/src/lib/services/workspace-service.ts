import { apiClient } from './api-client';
import type { UserWorkspace } from '../types/app';

export class WorkspaceService {
  public async createWorkspace(name: string, branchName?: string): Promise<{
    workspace: { id: string; name: string; slug: string };
    workspaces: UserWorkspace[];
  }> {
    const payload = {
      name,
      branch_name: branchName || undefined,
    };

    const response = await apiClient.post<{
      workspace: { id: string; name: string; slug: string };
      workspaces: UserWorkspace[];
    }>('/workspaces', payload, {
      skipWorkspace: true,
    });

    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Gagal membuat workspace baru.');
  }
}

export const workspaceService = new WorkspaceService();
