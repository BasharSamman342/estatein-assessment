import { api, type RequestOptions } from './client'
import {
  mockClientItems,
  mockFaqs,
  mockLocations,
  mockProperties,
  mockPropertyPage,
  mockTeamMembers,
  mockTestimonials,
} from './mockData'

export interface AuthUser {
  id: number
  name: string
  email: string
}

export interface AuthSession {
  token: string
  user: AuthUser
}

export type QueryParams = Record<string, unknown>

// Set VITE_USE_MOCK_DATA=true in .env.local to develop the public site without the API.
const useMockData = import.meta.env.VITE_USE_MOCK_DATA === 'true'

function query(params: QueryParams = {}): string {
  const search = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') search.set(key, String(value))
  })
  const text = search.toString()
  return text ? `?${text}` : ''
}

export const resources = {
  properties: (params: QueryParams = {}, options?: RequestOptions) =>
    useMockData
      ? Promise.resolve(mockPropertyPage(params))
      : api.getPage<unknown>(`/properties${query(params)}`, options),
  property: (slug: string, options?: RequestOptions) =>
    useMockData
      ? Promise.resolve(mockProperties.find((property) => property.slug === slug) ?? null)
      : api.get(`/properties/${slug}`, options),
  propertyFilters: (locale: string, options?: RequestOptions) =>
    useMockData
      ? Promise.resolve({ locations: mockLocations(locale) })
      : api.get<{ locations: string[] }>(`/property-filters${query({ locale })}`, options),
  testimonials: (options?: RequestOptions) =>
    useMockData ? Promise.resolve(mockTestimonials) : api.get<unknown[]>('/testimonials', options),
  faqs: (options?: RequestOptions) =>
    useMockData ? Promise.resolve(mockFaqs) : api.get<unknown[]>('/faqs', options),
  teamMembers: (options?: RequestOptions) =>
    useMockData ? Promise.resolve(mockTeamMembers) : api.get<unknown[]>('/team-members', options),
  clients: (options?: RequestOptions) =>
    useMockData ? Promise.resolve(mockClientItems) : api.get<unknown[]>('/clients', options),
  inquiries: (body: unknown) => api.send<{ id: number }>('/inquiries', { method: 'POST', body }),
  login: (body: unknown) =>
    api.send<AuthSession>('/login', { method: 'POST', body }) as Promise<AuthSession>,
  logout: () => api.send('/logout', { method: 'POST', auth: true }),
  me: () => api.get<AuthUser>('/me', { auth: true }),
  admin(name: string) {
    const root = `/admin/${name}`
    return {
      list: () => api.get<unknown[]>(root, { auth: true }),
      create: (body: unknown) => api.send(root, { method: 'POST', body, auth: true }),
      update: (id: string | number, body: unknown) =>
        api.send(`${root}/${id}`, { method: 'PUT', body, auth: true }),
      remove: (id: string | number) => api.send(`${root}/${id}`, { method: 'DELETE', auth: true }),
    }
  },
}
