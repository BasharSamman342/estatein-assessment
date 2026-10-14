import { mountApp } from '../test/mountApp'

beforeEach(() => {
  localStorage.clear()
  document.documentElement.lang = 'en'
  document.documentElement.dir = 'ltr'
  window.history.replaceState({}, '', '/')
})

test('a guest is sent from the dashboard to login', async () => {
  const { router } = mountApp()
  await router.push('/admin')
  expect(router.currentRoute.value.name).toBe('admin-login')
  expect(router.currentRoute.value.query.redirect).toBe('/admin')
})

test('a signed-in visitor is sent from login to the dashboard', async () => {
  localStorage.setItem('estatein.token', 'abc')
  const { router } = mountApp()
  await router.push('/admin/login')
  expect(router.currentRoute.value.name).toBe('admin-dashboard')
})

test('an admin route forces ltr even when the locale is arabic', async () => {
  localStorage.setItem('estatein.locale', 'ar')
  const { router } = mountApp()
  await router.push('/admin/login')
  expect(document.documentElement.dir).toBe('ltr')
  expect(document.documentElement.lang).toBe('en')
  await router.push('/')
  expect(document.documentElement.dir).toBe('rtl')
})
