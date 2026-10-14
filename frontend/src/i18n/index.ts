import { createI18n } from 'vue-i18n'
import { ar, en } from './messages'

export function createEstateI18n(locale: 'en' | 'ar' = 'en') {
  return createI18n({
    legacy: false,
    locale,
    fallbackLocale: 'en',
    messages: { en, ar },
  })
}
