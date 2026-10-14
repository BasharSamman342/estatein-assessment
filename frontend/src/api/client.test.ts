import { api, configureApi, HttpError } from './client'

beforeEach(() => {
  configureApi({ getToken: () => 'secret-token', onUnauthorized: vi.fn() })
})

afterEach(() => {
  vi.unstubAllGlobals()
})

test('GET returns the data field and sends no token by default', async () => {
  const fetchMock = vi.fn<
    (url: string, init?: { headers?: Record<string, string> }) => Promise<Response>
  >(async () => new Response(JSON.stringify({ data: [{ id: 1 }] }), { status: 200 }))
  vi.stubGlobal('fetch', fetchMock)

  await expect(api.get('/testimonials')).resolves.toEqual([{ id: 1 }])
  expect(fetchMock.mock.calls[0]?.[1]?.headers?.Authorization).toBeUndefined()
})

test('GET page preserves Laravel pagination metadata', async () => {
  vi.stubGlobal(
    'fetch',
    vi.fn(
      async () =>
        new Response(
          JSON.stringify({
            data: [{ id: 1 }],
            meta: { current_page: 1, last_page: 2, per_page: 9, total: 10 },
            links: { next: 'http://localhost:8000/api/properties?page=2' },
          }),
          { status: 200 },
        ),
    ),
  )

  await expect(api.getPage('/properties?per_page=9')).resolves.toMatchObject({
    data: [{ id: 1 }],
    meta: { current_page: 1, last_page: 2 },
  })
})

test('an authenticated 401 clears the session hook', async () => {
  const onUnauthorized = vi.fn()
  configureApi({ getToken: () => 'secret-token', onUnauthorized })
  vi.stubGlobal(
    'fetch',
    vi.fn(
      async () => new Response(JSON.stringify({ message: 'Unauthenticated.' }), { status: 401 }),
    ),
  )

  await expect(api.get('/me', { auth: true })).rejects.toBeInstanceOf(HttpError)
  expect(onUnauthorized).toHaveBeenCalledOnce()
})

test('a 422 exposes field errors', async () => {
  vi.stubGlobal(
    'fetch',
    vi.fn(
      async () =>
        new Response(
          JSON.stringify({
            message: 'The given data was invalid.',
            errors: { email: ['The email field is required.'] },
          }),
          { status: 422 },
        ),
    ),
  )

  await expect(api.send('/inquiries', { method: 'POST', body: {} })).rejects.toMatchObject({
    status: 422,
    errors: { email: ['The email field is required.'] },
  })
})
