import { apiClient } from './api-client';
import type { LoginResponseData, UserProfile } from '../types/app';

export class AuthService {
  public async login(
    email: string,
    password: string,
    deviceName?: string
  ): Promise<LoginResponseData> {
    const payload = {
      email,
      password,
      device_name: deviceName || 'Precis Web Portal',
    };

    const response = await apiClient.post<LoginResponseData>('/auth/login', payload, {
      skipAuth: true,
      skipWorkspace: true,
    });

    if (response.data) {
      apiClient.setToken(response.data.token);

      // tetapkan workspace aktif pertama jika ada
      if (response.data.workspaces && response.data.workspaces.length > 0) {
        const firstWorkspace = response.data.workspaces[0];
        apiClient.setWorkspaceId(firstWorkspace.workspace_id);
      }

      return response.data;
    }

    throw new Error(response.message || 'Gagal melakukan login.');
  }

  public async register(
    name: string,
    email: string,
    password: string,
    workspaceName?: string,
    deviceName?: string
  ): Promise<LoginResponseData> {
    const payload = {
      name,
      email,
      password,
      workspace_name: workspaceName,
      device_name: deviceName || 'Precis Web Portal',
    };

    const response = await apiClient.post<LoginResponseData>('/auth/register', payload, {
      skipAuth: true,
      skipWorkspace: true,
    });

    if (response.data) {
      apiClient.setToken(response.data.token);

      // tetapkan workspace aktif pertama jika ada
      if (response.data.workspaces && response.data.workspaces.length > 0) {
        const firstWorkspace = response.data.workspaces[0];
        apiClient.setWorkspaceId(firstWorkspace.workspace_id);
      }

      return response.data;
    }

    throw new Error(response.message || 'Gagal melakukan registrasi.');
  }

  public async getProfile(): Promise<UserProfile> {
    const response = await apiClient.get<UserProfile>('/auth/me', {
      skipWorkspace: true,
    });

    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Gagal memuat data profil pengguna.');
  }

  public async updateBankAccount(
    bankName: string,
    accountNumber: string,
    accountHolder: string
  ): Promise<{ bank_name: string; bank_account_number: string; bank_account_holder: string }> {
    const response = await apiClient.put<{
      bank_name: string;
      bank_account_number: string;
      bank_account_holder: string;
    }>(
      '/auth/bank-account',
      {
        bank_name: bankName,
        bank_account_number: accountNumber,
        bank_account_holder: accountHolder,
      },
      { skipWorkspace: true }
    );

    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Gagal memperbarui rekening bank.');
  }

  public async updateProfile(name: string): Promise<{ id: string; name: string; email: string }> {
    const response = await apiClient.put<{ id: string; name: string; email: string }>(
      '/auth/profile',
      { name },
      { skipWorkspace: true }
    );

    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Gagal memperbarui profil.');
  }

  public async updatePassword(
    currentPassword: string,
    password: string,
    passwordConfirmation: string
  ): Promise<string> {
    const response = await apiClient.put<{ message: string }>(
      '/auth/password',
      {
        current_password: currentPassword,
        password,
        password_confirmation: passwordConfirmation,
      },
      { skipWorkspace: true }
    );

    return response.message || 'Kata sandi berhasil diperbarui.';
  }

  public async logout(): Promise<void> {
    try {
      await apiClient.post('/auth/logout', {}, { skipWorkspace: true });
    } catch {
      // abaikan error pada server saat logout dan tetap bersihkan sesi lokal
    } finally {
      apiClient.clearSession();
    }
  }

  public async forgotPassword(email: string): Promise<string> {
    const response = await apiClient.post<{ message: string }>(
      '/auth/forgot-password',
      { email },
      {
        skipAuth: true,
        skipWorkspace: true,
      }
    );

    return response.message || 'Tautan pemulihan kata sandi telah dikirim ke email Anda.';
  }

  public async resetPassword(
    email: string,
    token: string,
    password: string,
    passwordConfirmation: string
  ): Promise<string> {
    const payload = {
      email,
      token,
      password,
      password_confirmation: passwordConfirmation,
    };

    const response = await apiClient.post<{ message: string }>('/auth/reset-password', payload, {
      skipAuth: true,
      skipWorkspace: true,
    });

    return response.message || 'Kata sandi berhasil diperbarui. Silakan login kembali.';
  }

  public async verifyEmail(token: string): Promise<string> {
    const response = await apiClient.post<{ message: string }>(
      '/auth/verify-email',
      { token },
      {
        skipAuth: true,
        skipWorkspace: true,
      }
    );

    return response.message || 'Alamat email berhasil diverifikasi.';
  }

  public async resendVerification(email: string): Promise<string> {
    const response = await apiClient.post<{ message: string }>(
      '/auth/resend-verification',
      { email },
      {
        skipAuth: true,
        skipWorkspace: true,
      }
    );

    return response.message || 'Tautan verifikasi email baru telah dikirimkan ke email Anda.';
  }
}

export const authService = new AuthService();
