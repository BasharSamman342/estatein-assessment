import { createPinia } from 'pinia'
import { mount } from '@vue/test-utils'
import { createEstateI18n } from '../i18n'
import { createEstateRouter } from '../router'
import App from '../App.vue'

export function mountApp() {
  const pinia = createPinia()
  const router = createEstateRouter()
  const i18n = createEstateI18n()
  const wrapper = mount(App, { global: { plugins: [pinia, router, i18n] } })
  return { wrapper, router, pinia }
}
