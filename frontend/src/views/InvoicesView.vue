<script setup lang="ts">
import { ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import StatusBadge from '../components/StatusBadge.vue'
import { api, errorMessage } from '../lib/api'
import { formatDate, formatMoney } from '../lib/money'
import type { Invoice, Paginated } from '../lib/types'

const route = useRoute()
const router = useRouter()
const tabs = [
  { value: '', label: 'All' },
  { value: 'draft', label: 'Drafts' },
  { value: 'sent', label: 'Sent' },
  { value: 'overdue', label: 'Overdue' },
  { value: 'paid', label: 'Paid' },
  { value: 'void', label: 'Void' },
]

const page = ref<Paginated<Invoice> | null>(null)
const loading = ref(false)
const failure = ref('')
const search = ref(String(route.query.q ?? ''))

async function load() {
  loading.value = true
  failure.value = ''
  try {
    page.value = (await api.get<Paginated<Invoice>>('/invoices', { params: route.query })).data
  } catch (e) {
    failure.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

function setQuery(changes: Record<string, string | undefined>) {
  const query = { ...route.query, ...changes }
  for (const key of Object.keys(query)) if (!query[key]) delete query[key]
  router.replace({ query })
}

let timer: ReturnType<typeof setTimeout>
watch(search, (q) => {
  clearTimeout(timer)
  timer = setTimeout(() => setQuery({ q: q || undefined, page: undefined }), 300)
})
watch(() => route.query, load, { immediate: true })
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">Invoices</h1>
    <RouterLink to="/invoices/new" class="btn-primary">New invoice</RouterLink>
  </div>

  <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <nav class="flex gap-1 overflow-x-auto" aria-label="Filter by status">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        class="rounded-lg px-3 py-1.5 text-sm font-medium whitespace-nowrap"
        :class="(route.query.status ?? '') === tab.value ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100'"
        :aria-pressed="(route.query.status ?? '') === tab.value"
        @click="setQuery({ status: tab.value || undefined, page: undefined })"
      >{{ tab.label }}</button>
    </nav>
    <input v-model="search" type="search" class="input sm:max-w-64" placeholder="Search number or client" aria-label="Search invoices" />
  </div>

  <p v-if="failure" class="field-error mt-4">{{ failure }}</p>

  <div class="card mt-4 overflow-x-auto" :class="{ 'opacity-60': loading }">
    <table class="w-full min-w-[44rem] text-sm">
      <thead class="border-b border-zinc-200 text-left text-xs text-zinc-500">
        <tr>
          <th class="px-4 py-3 font-medium">Number</th>
          <th class="px-4 py-3 font-medium">Client</th>
          <th class="px-4 py-3 font-medium">Issued</th>
          <th class="px-4 py-3 font-medium">Due</th>
          <th class="px-4 py-3 text-right font-medium">Total</th>
          <th class="px-4 py-3 text-right font-medium">Balance</th>
          <th class="px-4 py-3 font-medium">Status</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="inv in page?.data"
          :key="inv.id"
          class="cursor-pointer border-b border-zinc-100 whitespace-nowrap last:border-0 hover:bg-zinc-50"
          @click="router.push(`/invoices/${inv.id}`)"
        >
          <td class="px-4 py-3"><RouterLink :to="`/invoices/${inv.id}`" class="num font-medium hover:underline" @click.stop>{{ inv.number }}</RouterLink></td>
          <td class="max-w-56 truncate px-4 py-3 whitespace-normal">{{ inv.client?.name }}</td>
          <td class="px-4 py-3 text-zinc-600">{{ formatDate(inv.issue_date) }}</td>
          <td class="px-4 py-3 text-zinc-600">{{ formatDate(inv.due_date) }}</td>
          <td class="num px-4 py-3 text-right">{{ formatMoney(inv.total, inv.currency) }}</td>
          <td class="num px-4 py-3 text-right" :class="inv.balance ? 'font-medium' : 'text-zinc-400'">{{ formatMoney(inv.balance, inv.currency) }}</td>
          <td class="px-4 py-3"><StatusBadge :status="inv.status" /></td>
        </tr>
      </tbody>
    </table>
    <p v-if="page && !page.data.length" class="px-4 py-10 text-center text-sm text-zinc-500">No invoices match these filters.</p>
  </div>

  <div v-if="page && page.meta.last_page > 1" class="mt-4 flex items-center justify-between text-sm">
    <span class="text-zinc-500">Page {{ page.meta.current_page }} of {{ page.meta.last_page }} · {{ page.meta.total }} invoices</span>
    <div class="flex gap-2">
      <button class="btn-secondary" :disabled="page.meta.current_page <= 1" @click="setQuery({ page: String(page.meta.current_page - 1) })">Previous</button>
      <button class="btn-secondary" :disabled="page.meta.current_page >= page.meta.last_page" @click="setQuery({ page: String(page.meta.current_page + 1) })">Next</button>
    </div>
  </div>
</template>
