import type {
  ApiResponse,
  ApiErrorPayload,
  RequestOptions,
  UnauthorizedHandler,
  ForbiddenHandler,
} from '../types/api';

export class ApiError extends Error {
  public readonly status: number;
  public readonly data: ApiErrorPayload | null;
  public readonly errors: Record<string, string[]> | undefined;

  constructor(status: number, message: string, data: ApiErrorPayload | null = null) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.data = data;
    this.errors = data?.errors;
    Object.setPrototypeOf(this, ApiError.prototype);
  }
}

const STORAGE_KEY_SUPERADMIN_TOKEN = 'precis_superadmin_token';

export function getDefaultSuperadminApiBaseUrl(): string {
  const envUrl = (import.meta.env.VITE_API_BASE_URL as string) || '';
  if (envUrl && !envUrl.includes('localhost') && !envUrl.includes('127.0.0.1')) {
    return envUrl;
  }
  return '/api/v1';
}

export class SuperadminApiClient {
  private baseUrl: string;
  private token: string | null = null;
  private unauthorizedHandlers: Set<UnauthorizedHandler> = new Set();
  private forbiddenHandlers: Set<ForbiddenHandler> = new Set();

  constructor(baseUrl?: string) {
    this.baseUrl = baseUrl || getDefaultSuperadminApiBaseUrl();
    this.loadPersistedToken();
  }

  private loadPersistedToken(): void {
    if (typeof window !== 'undefined' && window.localStorage) {
      try {
        this.token = window.localStorage.getItem(STORAGE_KEY_SUPERADMIN_TOKEN);
      } catch {
        // abaikan kegagalan akses storage
      }
    }
  }

  public setToken(token: string | null): void {
    this.token = token;
    if (typeof window !== 'undefined' && window.localStorage) {
      try {
        if (token) {
          window.localStorage.setItem(STORAGE_KEY_SUPERADMIN_TOKEN, token);
        } else {
          window.localStorage.removeItem(STORAGE_KEY_SUPERADMIN_TOKEN);
        }
      } catch {
        // abaikan kegagalan akses storage
      }
    }
  }

  public getToken(): string | null {
    return this.token;
  }

  public clearSession(): void {
    this.setToken(null);
  }

  public onUnauthorized(handler: UnauthorizedHandler): () => void {
    this.unauthorizedHandlers.add(handler);
    return () => this.unauthorizedHandlers.delete(handler);
  }

  public onForbidden(handler: ForbiddenHandler): () => void {
    this.forbiddenHandlers.add(handler);
    return () => this.forbiddenHandlers.delete(handler);
  }

  private buildUrl(endpoint: string, params?: RequestOptions['params']): string {
    let cleanBase = (this.baseUrl || getDefaultSuperadminApiBaseUrl()).replace(/\/+$/, '');
    if (typeof window !== 'undefined' && (cleanBase.includes('localhost') || cleanBase.includes('127.0.0.1'))) {
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

  public async request<T = unknown>(endpoint: string, options: RequestOptions = {}): Promise<ApiResponse<T>> {
    const { params, skipAuth = false, body, headers = {}, ...customInit } = options;

    const url = this.buildUrl(endpoint, params);
    const requestHeaders = new Headers(headers);

    requestHeaders.set('Accept', 'application/json');

    if (!skipAuth && this.token) {
      requestHeaders.set('Authorization', `Bearer ${this.token}`);
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
    } catch {
      throw new ApiError(0, 'Koneksi jaringan gagal. Periksa koneksi backend Anda.', null);
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
        if (errorEntries.length > 0 && Array.isArray(errorEntries[0]) && errorEntries[0].length > 0) {
          errorMessage = errorEntries[0][0];
        }
      }

      if (!errorMessage) {
        errorMessage = `Permintaan Superadmin gagal dengan status ${status}`;
      }

      // Sanitize raw database or SQLSTATE leakages
      if (errorMessage.includes('SQLSTATE') || errorMessage.includes('syntax error') || errorMessage.includes('Connection refused')) {
        errorMessage = 'Terjadi kendala pada penyimpanan database server. Silakan coba beberapa saat lagi.';
      }

      if (status === 401) {
        this.unauthorizedHandlers.forEach((handler) => handler());
      } else if (status === 403) {
        this.forbiddenHandlers.forEach((handler) => handler(errorMessage));
      }

      throw new ApiError(status, errorMessage, errorData);
    }

    return (responsePayload as ApiResponse<T>) || { message: 'Operasi berhasil.' };
  }

  public get<T = unknown>(endpoint: string, options?: Omit<RequestOptions, 'method' | 'body'>): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { ...options, method: 'GET' });
  }

  public post<T = unknown>(endpoint: string, body?: unknown, options?: Omit<RequestOptions, 'method' | 'body'>): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { ...options, method: 'POST', body });
  }
}

export const superadminApiClient = new SuperadminApiClient();
