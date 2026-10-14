import { mountApp } from '../../test/mountApp'

beforeEach(() => {
  localStorage.clear()
  document.documentElement.lang = 'en'
  document.documentElement.dir = 'ltr'
  window.history.replaceState({}, '', '/')
})

test('the language switcher flips direction', async () => {
  const { wrapper, router } = mountApp()
  await router.push('/')
  await wrapper.get('[data-testid="locale-ar"]').trigger('click')
  expect(document.documentElement.dir).toBe('rtl')
  expect(wrapper.get('[data-testid="nav-properties"]').text()).toBe('العقارات')
})
