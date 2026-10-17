<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import RevenueChart from '../components/RevenueChart.vue'
import { api, errorMessage } from '../lib/api'
import { formatDate, formatMoney } from '../lib/money'
import type { Dashboard } from '../lib/types'

const data = ref<Dashboard | null>(null)
const failure = ref('')

onMounted(async () => {
  try {
    data.value = (await api.get<Dashboard>('/dashboard')).data
  } catch (e) {
    failure.value = errorMessage(e)
  }
})
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">Dashboard</h1>
    <RouterLink to="/invoices/new" class="btn-primary">New invoice</RouterLink>
  </div>
  <p v-if="failure" class="field-error mt-4">{{ failure }}</p>

  <template v-if="data">
    <section class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Key numbers">
      <div class="card p-4">
        <p class="text-sm text-zinc-500">Outstanding</p>
        <p class="mt-1 text-xl font-bold sm:text-2xl">{{ formatMoney(data.outstanding, data.currency) }}</p>
        <p class="text-xs text-zinc-500">Sent and not yet paid</p>
      </div>
      <RouterLink to="/invoices?status=overdue" class="card p-4 hover:border-red-300">
        <p class="text-sm text-zinc-500">Overdue</p>
        <p class="mt-1 text-xl font-bold sm:text-2xl" :class="data.overdue_count ? 'text-red-700' : ''">{{ formatMoney(data.overdue, data.currency) }}</p>
        <p class="text-xs text-zinc-500">{{ data.overdue_count }} {{ data.overdue_count === 1 ? 'invoice' : 'invoices' }} past due</p>
      </RouterLink>
      <div class="card p-4">
        <p class="text-sm text-zinc-500">Paid this month</p>
        <p class="mt-1 text-xl font-bold sm:text-2xl">{{ formatMoney(data.paid_this_month, data.currency) }}</p>
        <p class="text-xs text-zinc-500">Payments received</p>
      </div>
      <RouterLink to="/invoices?status=draft" class="card p-4 hover:border-zinc-300">
        <p class="text-sm text-zinc-500">Drafts</p>
        <p class="mt-1 text-xl font-bold sm:text-2xl">{{ data.draft_count }}</p>
        <p class="text-xs text-zinc-500">Not sent yet</p>
      </RouterLink>
    </section>

    <section class="card mt-4 p-5">
      <h2 class="font-semibold">Payments received</h2>
      <p class="text-sm text-zinc-500">Last 12 months, in {{ data.currency }}</p>
      <RevenueChart class="mt-4" :data="data.revenue_by_month" :currency="data.currency" />
      <details class="mt-2 text-sm">
        <summary class="cursor-pointer text-zinc-500">Show as table</summary>
        <table class="mt-2 w-full max-w-sm">
          <tr v-for="row in data.revenue_by_month" :key="row.month" class="border-b border-zinc-100">
            <td class="py-1">{{ row.month }}</td>
            <td class="num py-1 text-right">{{ formatMoney(row.amount, data.currency) }}</td>
          </tr>
        </table>
      </details>
    </section>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
      <section class="card p-5">
        <h2 class="font-semibold">Overdue invoices</h2>
        <p v-if="!data.overdue_invoices.length" class="mt-3 text-sm text-zinc-500">Nothing overdue. Nice.</p>
        <ul class="mt-2 divide-y divide-zinc-100">
          <li v-for="inv in data.overdue_invoices" :key="inv.id">
            <RouterLink :to="`/invoices/${inv.id}`" class="-mx-2 flex items-center justify-between gap-3 rounded-lg px-2 py-2.5 hover:bg-zinc-50">
              <span class="min-w-0">
                <span class="block truncate text-sm font-medium">{{ inv.client?.name }}</span>
                <span class="num text-xs text-zinc-500">{{ inv.number }} · due {{ formatDate(inv.due_date) }}</span>
              </span>
              <span class="num text-sm font-semibold text-red-700">{{ formatMoney(inv.balance, inv.currency) }}</span>
            </RouterLink>
          </li>
        </ul>
      </section>
      <section class="card p-5">
        <h2 class="font-semibold">Top clients</h2>
        <p class="text-sm text-zinc-500">Billed in the last 12 months</p>
        <ul class="mt-2 divide-y divide-zinc-100">
          <li v-for="c in data.top_clients" :key="c.id" class="flex items-center justify-between gap-3 py-2.5 text-sm">
            <span class="min-w-0 truncate">{{ c.name }} <span class="text-zinc-500">· {{ c.invoices }} invoices</span></span>
            <span class="num font-medium">{{ formatMoney(c.billed, data.currency) }}</span>
          </li>
        </ul>
      </section>
    </div>
  </template>
  <div v-else-if="!failure" class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4" aria-busy="true">
    <div v-for="n in 4" :key="n" class="card h-24 animate-pulse bg-zinc-100" />
  </div>
</template>
