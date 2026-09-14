const API_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8080/api/v1'

type RequestOptions = Omit<RequestInit, 'body'> & {
  body?: unknown
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

    throw new Error('API request failed with status ' + response.status)
  }

  return response.json() as Promise<T>
}
