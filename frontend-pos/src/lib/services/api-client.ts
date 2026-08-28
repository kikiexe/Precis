import type {
  ApiResponse,
  ApiErrorPayload,
  RequestOptions,
  DeviceUnauthorizedHandler,
  SubscriptionSuspendedHandler,
} from '../types/api';

export class PosApiError extends Error {
  public readonly status: number;
  public readonly data: ApiErrorPayload | null;
  public readonly errors: Record<string, string[]> | undefined;

  constructor(status: number, message: string, data: ApiErrorPayload | null = null) {
    super(message);
    this.name = 'PosApiError';
    this.status = status;
    this.data = data;
    this.errors = data?.errors;
    Object.setPrototypeOf(this, PosApiError.prototype);
  }
}

const STORAGE_KEY_DEVICE_TOKEN = 'precis_pos_device_token';

export function getDefaultPosApiBaseUrl(): string {
  const envUrl = (import.meta.env.VITE_API_BASE_URL as string) || '';
  if (envUrl && !envUrl.includes('localhost') && !envUrl.includes('127.0.0.1')) {
    return envUrl;
  }
  return '/api/v1';
}

export class PosApiClient {
  private baseUrl: string;
  private deviceToken: string | null = null;

  private deviceUnauthorizedHandlers: Set<DeviceUnauthorizedHandler> = new Set();
  private subscriptionSuspendedHandlers: Set<SubscriptionSuspendedHandler> = new Set();

  constructor(baseUrl?: string) {
    this.baseUrl = baseUrl || getDefaultPosApiBaseUrl();
    this.loadPersistedDeviceToken();
  }

  private loadPersistedDeviceToken(): void {
    if (typeof window !== 'undefined' && window.localStorage) {
      try {
        this.deviceToken = window.localStorage.getItem(STORAGE_KEY_DEVICE_TOKEN);
      } catch {
        // abaikan kegagalan akses storage
      }
    }
  }

  public setDeviceToken(token: string | null): void {
    this.deviceToken = token;
    if (typeof window !== 'undefined' && window.localStorage) {
      try {
        if (token) {
          window.localStorage.setItem(STORAGE_KEY_DEVICE_TOKEN, token);
        } else {
          window.localStorage.removeItem(STORAGE_KEY_DEVICE_TOKEN);
        }
      } catch {
        // abaikan kegagalan akses storage
      }
    }
  }

  public getDeviceToken(): string | null {
    return this.deviceToken;
  }

  public onDeviceUnauthorized(handler: DeviceUnauthorizedHandler): () => void {
    this.deviceUnauthorizedHandlers.add(handler);
    return () => this.deviceUnauthorizedHandlers.delete(handler);
  }

  public onSubscriptionSuspended(handler: SubscriptionSuspendedHandler): () => void {
    this.subscriptionSuspendedHandlers.add(handler);
    return () => this.subscriptionSuspendedHandlers.delete(handler);
  }

  private buildUrl(endpoint: string, params?: RequestOptions['params']): string {
    let cleanBase = (this.baseUrl || getDefaultPosApiBaseUrl()).replace(/\/+$/, '');
    if (
      typeof window !== 'undefined' &&
      (cleanBase.includes('localhost') || cleanBase.includes('127.0.0.1'))
    ) {
      cleanBase = '/api/v1';
    }
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
    const base = typeof window !== 'undefined' ? window.location.origin : 'http://127.0.0.1:8000';
    const url = new URL(`${cleanBase}${cleanEndpoint}`, base);

    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          url.searchParams.append(key, String(value));
        }
      });
    }

    return url.toString();
  }

  public async request<T = unknown>(
    endpoint: string,
    options: RequestOptions = {}
  ): Promise<ApiResponse<T>> {
    const { params, skipDeviceToken = false, body, headers = {}, ...customInit } = options;

    const url = this.buildUrl(endpoint, params);
    const requestHeaders = new Headers(headers);

    requestHeaders.set('Accept', 'application/json');

    if (!skipDeviceToken && this.deviceToken) {
      requestHeaders.set('X-Device-Token', this.deviceToken);
    }

    let formattedBody: BodyInit | null | undefined = undefined;

    if (body instanceof FormData) {
      formattedBody = body;
    } else if (body !== undefined && body !== null) {
      requestHeaders.set('Content-Type', 'application/json');
      formattedBody = JSON.stringify(body);
    }

    let response: Response;

    try {
      response = await fetch(url, {
        ...customInit,
        headers: requestHeaders,
        body: formattedBody,
      });
    } catch (networkError) {
      throw new PosApiError(
        0,
        'Koneksi jaringan gagal. Transaksi offline tetap dapat diproses secara lokal.',
        null
      );
    }

    let responsePayload: ApiResponse<T> | ApiErrorPayload | null = null;
    const contentType = response.headers.get('content-type');

    if (contentType && contentType.includes('application/json')) {
      try {
        responsePayload = await response.json();
      } catch {
        responsePayload = null;
      }
    }

    if (!response.ok) {
      const status = response.status;
      const errorData = responsePayload as ApiErrorPayload | null;
      let errorMessage = errorData?.message;

      // Extract specific validation message if available
      if (errorData?.errors && typeof errorData.errors === 'object') {
        const errorEntries = Object.values(errorData.errors);
        if (
          errorEntries.length > 0 &&
          Array.isArray(errorEntries[0]) &&
          errorEntries[0].length > 0
        ) {
          errorMessage = errorEntries[0][0];
        }
      }

      if (!errorMessage) {
        errorMessage = `Permintaan POS gagal dengan status ${status}`;
      }

      // Sanitize raw database or SQLSTATE leakages
      if (
        errorMessage.includes('SQLSTATE') ||
        errorMessage.includes('syntax error') ||
        errorMessage.includes('Connection refused')
      ) {
        errorMessage =
          'Terjadi kendala pada penyimpanan database server. Silakan coba beberapa saat lagi.';
      }

      if (status === 401 || status === 403) {
        this.deviceUnauthorizedHandlers.forEach((handler) => handler(errorMessage));
      } else if (status === 402) {
        this.subscriptionSuspendedHandlers.forEach((handler) => handler(errorMessage));
      }

      throw new PosApiError(status, errorMessage, errorData);
    }

    return (responsePayload as ApiResponse<T>) || { message: 'Operasi POS berhasil.' };
  }

  public get<T = unknown>(
    endpoint: string,
    options?: Omit<RequestOptions, 'method' | 'body'>
  ): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { ...options, method: 'GET' });
  }

  public post<T = unknown>(
    endpoint: string,
    body?: unknown,
    options?: Omit<RequestOptions, 'method' | 'body'>
  ): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { ...options, method: 'POST', body });
  }

  public put<T = unknown>(
    endpoint: string,
    body?: unknown,
    options?: Omit<RequestOptions, 'method' | 'body'>
  ): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { ...options, method: 'PUT', body });
  }

  public patch<T = unknown>(
    endpoint: string,
    body?: unknown,
    options?: Omit<RequestOptions, 'method' | 'body'>
  ): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { ...options, method: 'PATCH', body });
  }

  public delete<T = unknown>(
    endpoint: string,
    options?: Omit<RequestOptions, 'method'>
  ): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { ...options, method: 'DELETE' });
  }
}

export const posApiClient = new PosApiClient();
