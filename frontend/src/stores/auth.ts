import { defineStore } from 'pinia'
import { ref, shallowRef } from 'vue'
import { resources, type AuthUser } from '../api/resources'

const storageKey = 'estatein.token'

export type AuthStatus = 'idle' | 'submitting' | 'ready' | 'error'

export const useAuthStore = defineStore('auth', () => {
  const token = shallowRef(localStorage.getItem(storageKey) ?? '')
  const user = ref<AuthUser | null>(null)
  const status = shallowRef<AuthStatus>('idle')
  const errorMessage = shallowRef('')

  function persist(value: string) {
    token.value = value
    if (value) localStorage.setItem(storageKey, value)
    else localStorage.removeItem(storageKey)
  }

  async function login(credentials: { email: string; password: string }) {
    status.value = 'submitting'
    errorMessage.value = ''
    try {
      const result = await resources.login(credentials)
      persist(result.token)
      user.value = result.user
      status.value = 'ready'
      return true
    } catch (error: unknown) {
      persist('')
      user.value = null
      errorMessage.value = error instanceof Error ? error.message : 'Request failed'
      status.value = 'error'
      return false
    }
  }

  function clearSession() {
    persist('')
    user.value = null
    status.value = 'idle'
  }

  async function logout() {
    try {
      await resources.logout()
    } finally {
      clearSession()
    }
  }

  function hydrate() {
    token.value = localStorage.getItem(storageKey) ?? ''
  }

  return { token, user, status, errorMessage, login, logout, clearSession, hydrate }
})
