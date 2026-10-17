import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { api, csrf } from '../lib/api'
import type { User } from '../lib/types'

export const useAuth = defineStore('auth', () => {
  const user = ref<User | null>(null)
  let loaded: Promise<void> | null = null

  const isOwner = computed(() => user.value?.role === 'owner')
  const currency = computed(() => user.value?.company.currency ?? 'USD')

  /** Resolves once we know whether a session exists. Called by the router guard. */
  function ready() {
    loaded ??= api
      .get<User>('/me')
      .then((r) => { user.value = r.data })
      .catch(() => { user.value = null })
    return loaded
  }

  async function login(email: string, password: string) {
    await csrf()
    user.value = (await api.post<User>('/login', { email, password, remember: true })).data
  }

  async function register(form: Record<string, string>) {
    await csrf()
    user.value = (await api.post<User>('/register', form)).data
  }

  async function logout() {
    await api.post('/logout')
    user.value = null
  }

  return { user, isOwner, currency, ready, login, register, logout }
})
