import { apiClient } from './api-client';
import type {
  SubscriptionPlanItem,
  SubscriptionInvoice,
  InvoicePaymentConfirmation,
  PresignUploadResponseData,
} from '../types/app';

export class BillingService {
  /**
   * ambil daftar paket langganan aktif
   */
  public async getPlans(): Promise<SubscriptionPlanItem[]> {
    const response = await apiClient.get<SubscriptionPlanItem[]>('/billing/plans');
    return response.data || [];
  }

  /**
   * ambil riwayat seluruh faktur tagihan milik akun login
   */
  public async getInvoices(): Promise<SubscriptionInvoice[]> {
    const response = await apiClient.get<SubscriptionInvoice[]>('/billing/invoices');
    return response.data || [];
  }

  /**
   * buat faktur tagihan baru dengan kode unik 3 digit acak
   */
  public async createInvoice(
    planId: string,
    billingCycle: 'MONTHLY' | 'ANNUAL' = 'MONTHLY'
  ): Promise<SubscriptionInvoice> {
    const payload = {
      plan_id: planId,
      billing_cycle: billingCycle,
    };

    const response = await apiClient.post<SubscriptionInvoice>('/billing/invoices', payload);
    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Gagal membuat faktur tagihan langganan.');
  }

  /**
   * unggah berkas gambar bukti transfer ke Cloudflare R2 via presigned URL
   */
  public async uploadProofImage(file: File): Promise<string> {
    const allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowedMimeTypes.includes(file.type)) {
      throw new Error('Format gambar bukti transfer harus berupa JPEG, PNG, atau WebP.');
    }

    const maxSizeBytes = 2 * 1024 * 1024; // 2 Megabyte
    if (file.size > maxSizeBytes) {
      throw new Error('Ukuran berkas bukti transfer tidak boleh melebihi 2MB.');
    }

    // 1. request presigned upload URL ke backend
    const presignPayload = {
      filename: file.name,
      mime_type: file.type === 'image/png' ? 'image/webp' : file.type,
      size_bytes: file.size,
    };

    const presignRes = await apiClient.post<PresignUploadResponseData>(
      '/media/presign-upload',
      presignPayload
    );

    if (!presignRes.data || !presignRes.data.upload_url) {
      throw new Error(presignRes.message || 'Gagal mendapatkan izin unggah berkas.');
    }

    const { upload_url, public_url } = presignRes.data;

    // 2. unggah langsung ke Cloudflare R2
    const uploadRes = await fetch(upload_url, {
      method: 'PUT',
      headers: {
        'Content-Type': presignPayload.mime_type,
      },
      body: file,
    });

    if (!uploadRes.ok) {
      throw new Error(`Gagal mengunggah foto bukti ke media storage: HTTP ${uploadRes.status}`);
    }

    return public_url;
  }

  /**
   * kirim konfirmasi bukti pembayaran faktur tagihan
   */
  public async submitProof(
    invoiceId: string,
    bankAccountName: string,
    transferAmount: number,
    proofImageUrl: string
  ): Promise<InvoicePaymentConfirmation> {
    const payload = {
      bank_account_name: bankAccountName,
      transfer_amount: transferAmount,
      proof_image_url: proofImageUrl,
    };

    const response = await apiClient.post<InvoicePaymentConfirmation>(
      `/billing/invoices/${invoiceId}/proof`,
      payload
    );

    if (response.data) {
      return response.data;
    }

    throw new Error(response.message || 'Gagal mengirimkan bukti pembayaran.');
  }
}

export const billingService = new BillingService();
