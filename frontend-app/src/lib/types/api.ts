export interface ApiResponse<T = unknown> {
  message?: string;
  data?: T;
  [key: string]: unknown;
}

export interface ApiErrorPayload {
  message: string;
  errors?: Record<string, string[]>;
  subscription_status?: string;
  member_role?: string;
}

export interface RequestOptions extends Omit<RequestInit, 'body'> {
  params?: Record<string, string | number | boolean | undefined | null>;
  skipAuth?: boolean;
  skipWorkspace?: boolean;
  body?: unknown;
}

export type UnauthorizedHandler = () => void;
export type ForbiddenHandler = (message: string) => void;
export type SubscriptionSuspendedHandler = (message: string) => void;
export type GracePeriodWarningHandler = () => void;
