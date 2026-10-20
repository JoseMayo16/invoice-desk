<script setup lang="ts">
import LogoMark from './LogoMark.vue'
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuth } from '../stores/auth'

const auth = useAuth()
const router = useRouter()
const menuOpen = ref(false)

const nav = [
  { to: '/dashboard', label: 'Overview', icon: 'M4 13h6V4H4zm10 7h6v-9h-6zM4 20h6v-4H4zm10-11h6V4h-6z' },
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
    <aside class="border-b border-zinc-200 bg-zinc-50 lg:fixed lg:inset-y-0 lg:flex lg:w-56 lg:flex-col lg:border-r lg:border-b-0">
      <div class="flex h-12 items-center justify-between px-4 lg:h-14">
        <RouterLink to="/dashboard" class="flex min-w-0 items-center gap-2.5">
          <LogoMark class="size-6 shrink-0" />
          <span class="min-w-0 leading-tight">
            <span class="block truncate text-sm font-semibold">{{ auth.user?.company.name }}</span>
            <span class="block text-xs text-zinc-500">InvoiceDesk</span>
          </span>
        </RouterLink>
        <button class="btn-ghost px-2 lg:hidden" :aria-expanded="menuOpen" aria-label="Menu" @click="menuOpen = !menuOpen">
          <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
      </div>
      <nav class="px-2 pb-3 lg:flex lg:flex-1 lg:flex-col lg:pt-2" :class="menuOpen ? 'block' : 'hidden'">
        <RouterLink
          v-for="item in nav"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-2.5 rounded-md px-2.5 py-1.5 text-sm text-zinc-600 hover:bg-zinc-200/60 hover:text-zinc-900"
          active-class="!bg-zinc-200/70 !text-ink font-medium"
          @click="menuOpen = false"
        >
          <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path :d="item.icon" /></svg>
          {{ item.label }}
        </RouterLink>
        <div class="mt-4 border-t border-zinc-200 px-2.5 pt-3 lg:mt-auto">
          <p class="truncate text-sm font-medium">{{ auth.user?.name }}</p>
          <p class="truncate font-mono text-xs text-zinc-500">{{ auth.isOwner ? 'owner' : 'member' }}</p>
          <button class="mt-2 text-xs text-zinc-500 hover:text-zinc-900 hover:underline" @click="logout">Sign out</button>
        </div>
      </nav>
    </aside>
    <main class="min-w-0 flex-1 lg:pl-56">
      <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-10 lg:py-8">
        <slot />
      </div>
    </main>
  </div>
</template>
