export interface ApiResponse<T = unknown> {
  message: string;
  data?: T;
}

export interface ApiErrorPayload {
  message: string;
  errors?: Record<string, string[]>;
  [key: string]: unknown;
}

export interface RequestOptions extends Omit<RequestInit, 'body'> {
  params?: Record<string, string | number | boolean | undefined | null>;
  skipAuth?: boolean;
  body?: unknown;
}

export type UnauthorizedHandler = () => void;
export type ForbiddenHandler = (message: string) => void;
