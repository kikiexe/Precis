export interface ApiResponse<T = unknown> {
  message?: string;
  data?: T;
  [key: string]: unknown;
}

export interface ApiErrorPayload {
  message: string;
  errors?: Record<string, string[]>;
  subscription_status?: string;
}

export interface RequestOptions extends Omit<RequestInit, 'body'> {
  params?: Record<string, string | number | boolean | undefined | null>;
  skipDeviceToken?: boolean;
  body?: unknown;
}

export type DeviceUnauthorizedHandler = (message: string) => void;
export type SubscriptionSuspendedHandler = (message: string) => void;
