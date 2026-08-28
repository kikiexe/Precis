import { apiClient } from './api-client';
import type {
  PresignUploadResponseData,
  ClockInResponseData,
  ClockOutResponseData,
  WallOfFacesItem,
} from '../types/app';

export class AttendanceService {
  public async presignUpload(
    filename: string,
    mimeType: string,
    sizeBytes: number
  ): Promise<PresignUploadResponseData> {
    const payload = {
      filename,
      mime_type: mimeType,
      size_bytes: sizeBytes,
    };

    const response = await apiClient.post<PresignUploadResponseData>(
      '/media/presign-upload',
      payload
    );
    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Gagal mendapatkan URL upload media.');
  }

  public async uploadBinaryToStorage(
    uploadUrl: string,
    blob: Blob,
    mimeType = 'image/webp'
  ): Promise<void> {
    const res = await fetch(uploadUrl, {
      method: 'PUT',
      headers: {
        'Content-Type': mimeType,
      },
      body: blob,
    });

    if (!res.ok) {
      throw new Error(`Upload foto gagal dengan status ${res.status}.`);
    }
  }

  public async clockIn(
    branchId: string,
    latitude: number,
    longitude: number,
    photoUrl: string,
    notes?: string
  ): Promise<ClockInResponseData> {
    const payload = {
      branch_id: branchId,
      latitude,
      longitude,
      photo_url: photoUrl,
      notes: notes || undefined,
    };

    const response = await apiClient.post<ClockInResponseData>('/attendances/clock-in', payload);
    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Gagal mencatat presensi masuk.');
  }

  public async clockOut(
    branchId: string,
    latitude: number,
    longitude: number,
    photoUrl: string,
    notes?: string
  ): Promise<ClockOutResponseData> {
    const payload = {
      branch_id: branchId,
      latitude,
      longitude,
      photo_url: photoUrl,
      notes: notes || undefined,
    };

    const response = await apiClient.post<ClockOutResponseData>('/attendances/clock-out', payload);
    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Gagal mencatat presensi keluar.');
  }

  public async getWallOfFaces(branchId?: string, date?: string): Promise<WallOfFacesItem[]> {
    const params: Record<string, string> = {};
    if (branchId) params.branch_id = branchId;
    if (date) params.date = date;

    const response = await apiClient.get<WallOfFacesItem[]>(
      '/admin/attendances/wall-of-faces',
      params
    );
    if (response.data && Array.isArray(response.data)) {
      return response.data;
    }

    return [];
  }
}

export const attendanceService = new AttendanceService();
