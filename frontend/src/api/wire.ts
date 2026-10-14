import type { Router } from 'vue-router'
import { configureApi } from './client'
import { useAuthStore } from '../stores/auth'

export function wireApi(router: Router) {
  const auth = useAuthStore()
  configureApi({
    getToken: () => auth.token,
    onUnauthorized: () => {
      auth.clearSession()
      if (router.currentRoute.value.meta.requiresAuth) {
        void router.replace({ name: 'admin-login', query: { expired: '1' } })
      }
    },
  })
}
