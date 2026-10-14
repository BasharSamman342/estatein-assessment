import { createPinia, setActivePinia } from 'pinia'
import { useLocaleStore } from './locale'

beforeEach(() => {
  localStorage.clear()
  setActivePinia(createPinia())
  document.documentElement.lang = 'en'
  document.documentElement.dir = 'ltr'
})

test('arabic sets rtl and survives a new store', () => {
  const store = useLocaleStore()
  store.setLocale('ar')
  expect(document.documentElement.dir).toBe('rtl')
  expect(document.documentElement.lang).toBe('ar')

  setActivePinia(createPinia())
  const restored = useLocaleStore()
  restored.applyToDocument()
  expect(restored.locale).toBe('ar')
  expect(restored.dir).toBe('rtl')
})
