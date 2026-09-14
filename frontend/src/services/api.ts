const API_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8080/api/v1'

type RequestOptions = Omit<RequestInit, 'body'> & {
  body?: unknown
}

type ErrorPayload = {
  message?: string
  errors?: Record<string, string[]>
}

export class ApiError extends Error {
  constructor(
    message: string,
    readonly status: number,
    readonly errors: Record<string, string[]> = {},
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

export async function api<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const token = localStorage.getItem('pms_token')
  const response = await fetch(API_URL + path, {
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { Authorization: 'Bearer ' + token } : {}),
      ...options.headers,
    },
    body: options.body === undefined ? undefined : JSON.stringify(options.body),
  })

  if (!response.ok) {
    if (response.status === 401) {
      localStorage.removeItem('pms_token')
    }

    const payload = (await response.json().catch(() => ({}))) as ErrorPayload
    throw new ApiError(payload.message ?? 'La richiesta non è riuscita.', response.status, payload.errors)
  }

  if (response.status === 204) {
    return undefined as T
  }

  return response.json() as Promise<T>
}


export async function apiDownload(path: string): Promise<Blob> {
  const token = localStorage.getItem('pms_token')
  const response = await fetch(API_URL + path, {
    headers: {
      Accept: 'text/csv',
      ...(token ? { Authorization: 'Bearer ' + token } : {}),
    },
  })

  if (!response.ok) {
    const payload = (await response.json().catch(() => ({}))) as ErrorPayload
    throw new ApiError(payload.message ?? 'Il download non è riuscito.', response.status, payload.errors)
  }

  return response.blob()
}
