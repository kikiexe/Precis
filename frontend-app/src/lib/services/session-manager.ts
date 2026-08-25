import { apiClient } from './api-client';
import { authService } from './auth-service';
import { workspaceContext } from '../stores/workspace-context.svelte';
import type { LoginResponseData } from '../types/app';

export class SessionManager {
  async initializeSession(): Promise<boolean> {
    const token = apiClient.getToken();
    if (!token) {
      return false;
    }

    try {
      const profile = await authService.getProfile();
      if (profile) {
        workspaceContext.setSessionData(profile, profile.workspaces || []);
        await workspaceContext.loadWorkspaceData();
        return true;
      }
      return false;
    } catch {
      apiClient.clearSession();
      workspaceContext.reset();
      return false;
    }
  }

  async handleLoginSuccess(data: LoginResponseData): Promise<void> {
    workspaceContext.setSessionData(data.user, data.workspaces || []);
    await workspaceContext.loadWorkspaceData();
  }

  async logout(): Promise<void> {
    try {
      await authService.logout();
    } catch (e) {
      console.warn('Gagal logout di server:', e);
    } finally {
      apiClient.clearSession();
      workspaceContext.reset();
    }
  }
}

export const sessionManager = new SessionManager();
