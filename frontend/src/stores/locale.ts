import { defineStore } from 'pinia'
import { computed, shallowRef } from 'vue'

const storageKey = 'estatein.locale'

export const useLocaleStore = defineStore('locale', () => {
  const stored = localStorage.getItem(storageKey)
  const locale = shallowRef(stored === 'ar' ? 'ar' : 'en')
  const dir = computed(() => (locale.value === 'ar' ? 'rtl' : 'ltr'))

  function applyToDocument() {
    document.documentElement.lang = locale.value
    document.documentElement.dir = dir.value
  }

  function setLocale(next: string) {
    locale.value = next === 'ar' ? 'ar' : 'en'
    localStorage.setItem(storageKey, locale.value)
    applyToDocument()
  }

  return { locale, dir, applyToDocument, setLocale }
})
