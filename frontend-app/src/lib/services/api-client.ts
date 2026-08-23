import type {
  ApiResponse,
  ApiErrorPayload,
  RequestOptions,
  UnauthorizedHandler,
  ForbiddenHandler,
  SubscriptionSuspendedHandler,
  GracePeriodWarningHandler,
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

const STORAGE_KEY_AUTH_TOKEN = 'precis_auth_token';
const STORAGE_KEY_WORKSPACE_ID = 'precis_workspace_id';

export class ApiClient {
  private baseUrl: string;
  private token: string | null = null;
  private workspaceId: string | null = null;

  private unauthorizedHandlers: Set<UnauthorizedHandler> = new Set();
  private forbiddenHandlers: Set<ForbiddenHandler> = new Set();
  private subscriptionSuspendedHandlers: Set<SubscriptionSuspendedHandler> = new Set();
  private gracePeriodWarningHandlers: Set<GracePeriodWarningHandler> = new Set();

  constructor(baseUrl?: string) {
    this.baseUrl = baseUrl || (import.meta.env.VITE_API_BASE_URL as string) || 'http://localhost:8000/api/v1';
    this.loadPersistedContext();
  }

  private loadPersistedContext(): void {
    if (typeof window !== 'undefined' && window.localStorage) {
      try {
        this.token = window.localStorage.getItem(STORAGE_KEY_AUTH_TOKEN);
        this.workspaceId = window.localStorage.getItem(STORAGE_KEY_WORKSPACE_ID);
      } catch {
        // abaikan kegagalan akses storage pada restricted environment
      }
    }
  }

  public setToken(token: string | null): void {
    this.token = token;
    if (typeof window !== 'undefined' && window.localStorage) {
      try {
        if (token) {
          window.localStorage.setItem(STORAGE_KEY_AUTH_TOKEN, token);
        } else {
          window.localStorage.removeItem(STORAGE_KEY_AUTH_TOKEN);
        }
      } catch {
        // abaikan kegagalan akses storage
      }
    }
  }

  public getToken(): string | null {
    return this.token;
  }

  public setWorkspaceId(workspaceId: string | null): void {
    this.workspaceId = workspaceId;
    if (typeof window !== 'undefined' && window.localStorage) {
      try {
        if (workspaceId) {
          window.localStorage.setItem(STORAGE_KEY_WORKSPACE_ID, workspaceId);
        } else {
          window.localStorage.removeItem(STORAGE_KEY_WORKSPACE_ID);
        }
      } catch {
        // abaikan kegagalan akses storage
      }
    }
  }

  public getWorkspaceId(): string | null {
    return this.workspaceId;
  }

  public clearSession(): void {
    this.setToken(null);
    this.setWorkspaceId(null);
  }

  public onUnauthorized(handler: UnauthorizedHandler): () => void {
    this.unauthorizedHandlers.add(handler);
    return () => this.unauthorizedHandlers.delete(handler);
  }

  public onForbidden(handler: ForbiddenHandler): () => void {
    this.forbiddenHandlers.add(handler);
    return () => this.forbiddenHandlers.delete(handler);
  }

  public onSubscriptionSuspended(handler: SubscriptionSuspendedHandler): () => void {
    this.subscriptionSuspendedHandlers.add(handler);
    return () => this.subscriptionSuspendedHandlers.delete(handler);
  }

  public onGracePeriodWarning(handler: GracePeriodWarningHandler): () => void {
    this.gracePeriodWarningHandlers.add(handler);
    return () => this.gracePeriodWarningHandlers.delete(handler);
  }

  private buildUrl(endpoint: string, params?: RequestOptions['params']): string {
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
    const url = new URL(`${this.baseUrl}${cleanEndpoint}`);

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
    const { params, skipAuth = false, skipWorkspace = false, body, headers = {}, ...customInit } = options;

    const url = this.buildUrl(endpoint, params);
    const requestHeaders = new Headers(headers);

    requestHeaders.set('Accept', 'application/json');

    if (!skipAuth && this.token) {
      requestHeaders.set('Authorization', `Bearer ${this.token}`);
    }

    if (!skipWorkspace && this.workspaceId) {
      requestHeaders.set('X-Workspace-Id', this.workspaceId);
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
      throw new ApiError(0, 'Koneksi jaringan gagal. Periksa koneksi internet Anda.', null);
    }

    // periksa peringatan masa tenggang langganan dari header respon
    const subscriptionWarning = response.headers.get('X-Subscription-Warning');
    if (subscriptionWarning === 'GRACE_PERIOD') {
      this.gracePeriodWarningHandlers.forEach((handler) => handler());
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
      const defaultMessage = `Permintaan gagal dengan status ${status}`;
      const errorMessage = errorData?.message || defaultMessage;

      if (status === 401) {
        this.unauthorizedHandlers.forEach((handler) => handler());
      } else if (status === 402) {
        this.subscriptionSuspendedHandlers.forEach((handler) => handler(errorMessage));
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

  public put<T = unknown>(endpoint: string, body?: unknown, options?: Omit<RequestOptions, 'method' | 'body'>): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { ...options, method: 'PUT', body });
  }

  public patch<T = unknown>(endpoint: string, body?: unknown, options?: Omit<RequestOptions, 'method' | 'body'>): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { ...options, method: 'PATCH', body });
  }

  public delete<T = unknown>(endpoint: string, options?: Omit<RequestOptions, 'method'>): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { ...options, method: 'DELETE' });
  }
}

export const apiClient = new ApiClient();
