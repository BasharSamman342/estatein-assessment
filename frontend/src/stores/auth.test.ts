import { createPinia, setActivePinia } from 'pinia'
import { resources } from '../api/resources'
import { useAuthStore } from './auth'

vi.mock('../api/resources', () => ({
  resources: {
    login: vi.fn(),
    logout: vi.fn(),
  },
}))

beforeEach(() => {
  localStorage.clear()
  setActivePinia(createPinia())
})

test('login stores the bearer token', async () => {
  vi.mocked(resources.login).mockResolvedValue({
    token: 'abc',
    user: { id: 1, name: 'Estatein Admin', email: 'admin@estatein.test' },
  })
  const store = useAuthStore()
  await store.login({ email: 'admin@estatein.test', password: 'password' })
  expect(store.token).toBe('abc')
  expect(localStorage.getItem('estatein.token')).toBe('abc')
  expect(store.user?.name).toBe('Estatein Admin')
})

test('a failed login shows the server message and leaves the token empty', async () => {
  vi.mocked(resources.login).mockRejectedValue(
    Object.assign(new Error('These credentials do not match our records.'), { status: 401 }),
  )
  const store = useAuthStore()
  await store.login({ email: 'admin@estatein.test', password: 'nope' })
  expect(store.token).toBe('')
  expect(store.errorMessage).toBe('These credentials do not match our records.')
  expect(store.status).toBe('error')
})
