const baseUrl = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api'

export interface RequestOptions {
  method?: string
  body?: unknown
  auth?: boolean
  signal?: AbortSignal
}

export type FieldErrors = Record<string, string[]>

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: PaginationMeta
  links: {
    first?: string | null
    last?: string | null
    next?: string | null
    prev?: string | null
  }
}

export interface ApiConfig {
  getToken: () => string | null | undefined
  onUnauthorized: () => void
}

interface ApiPayload {
  message?: string
  errors?: FieldErrors
  data?: unknown
}

let getToken: ApiConfig['getToken'] = () => null
let onUnauthorized: ApiConfig['onUnauthorized'] = () => {}

export function configureApi(options: ApiConfig): void {
  getToken = options.getToken
  onUnauthorized = options.onUnauthorized
}

export class HttpError extends Error {
  status: number
  errors: FieldErrors

  constructor(status: number, message: string, errors: FieldErrors = {}) {
    super(message)
    this.name = 'HttpError'
    this.status = status
    this.errors = errors
  }
}

async function request<T>(
  path: string,
  { method = 'GET', body, auth = false, signal }: RequestOptions = {},
  unwrapData = true,
): Promise<T | null> {
  const headers: Record<string, string> = { Accept: 'application/json' }
  const isForm = typeof FormData !== 'undefined' && body instanceof FormData
  if (body !== undefined && !isForm) headers['Content-Type'] = 'application/json'
  if (auth) {
    const token = getToken()
    if (token) headers.Authorization = `Bearer ${token}`
  }

  const response = await fetch(`${baseUrl}${path}`, {
    method,
    headers,
    body: body === undefined ? undefined : isForm ? body : JSON.stringify(body),
    signal,
  })

  if (response.status === 204) return null

  const payload = (await response.json().catch(() => ({
    message: response.statusText,
  }))) as ApiPayload

  if (!response.ok) {
    if (response.status === 401 && auth) onUnauthorized()
    throw new HttpError(response.status, payload.message ?? 'Request failed', payload.errors ?? {})
  }

  return (unwrapData ? payload.data ?? payload : payload) as T
}

export const api = {
  get<T = unknown>(path: string, options: RequestOptions = {}): Promise<T> {
    return request<T>(path, options) as Promise<T>
  },
  getPage<T = unknown>(path: string, options: RequestOptions = {}): Promise<PaginatedResponse<T>> {
    return request<PaginatedResponse<T>>(path, options, false) as Promise<PaginatedResponse<T>>
  },
  send<T = unknown>(path: string, options: RequestOptions): Promise<T | null> {
    return request<T>(path, options)
  },
}
