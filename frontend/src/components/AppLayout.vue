<script setup lang="ts">
import LogoMark from './LogoMark.vue'
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuth } from '../stores/auth'

const auth = useAuth()
const router = useRouter()
const menuOpen = ref(false)

const nav = [
  { to: '/dashboard', label: 'Dashboard', icon: 'M4 13h6V4H4zm10 7h6v-9h-6zM4 20h6v-4H4zm10-11h6V4h-6z' },
  { to: '/invoices', label: 'Invoices', icon: 'M7 3h8l4 4v14H7zM14 3v5h5M10 12h6M10 16h6' },
  { to: '/clients', label: 'Clients', icon: 'M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1M9.5 10a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7M21 19v-1a4 4 0 0 0-3-3.87M15.5 3.13a3.5 3.5 0 0 1 0 6.75' },
]

async function logout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen lg:flex">
    <aside class="border-b border-zinc-200 bg-white lg:fixed lg:inset-y-0 lg:w-60 lg:border-r lg:border-b-0">
      <div class="flex h-14 items-center justify-between px-4 lg:h-16 lg:px-5">
        <RouterLink to="/dashboard" class="flex items-center gap-2 font-bold">
          <LogoMark class="size-7" />
          InvoiceDesk
        </RouterLink>
        <button class="btn-ghost lg:hidden" :aria-expanded="menuOpen" aria-label="Menu" @click="menuOpen = !menuOpen">
          <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
      </div>
      <nav class="px-3 pb-3 lg:block" :class="menuOpen ? 'block' : 'hidden'">
        <RouterLink
          v-for="item in nav"
          :key="item.to"
          :to="item.to"
          class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900"
          active-class="!bg-brand-50 !text-brand-700"
          @click="menuOpen = false"
        >
          <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path :d="item.icon" /></svg>
          {{ item.label }}
        </RouterLink>
        <div class="mt-4 border-t border-zinc-200 pt-4 lg:fixed lg:bottom-0 lg:w-54 lg:pb-4">
          <p class="truncate px-3 text-sm font-semibold">{{ auth.user?.name }}</p>
          <p class="truncate px-3 text-xs text-zinc-500">
            {{ auth.user?.company.name }} · {{ auth.isOwner ? 'Owner' : 'Member' }}
          </p>
          <button class="btn-ghost mt-2 w-full justify-start" @click="logout">Sign out</button>
        </div>
      </nav>
    </aside>
    <main class="min-w-0 flex-1 lg:pl-60">
      <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <slot />
      </div>
    </main>
  </div>
</template>
