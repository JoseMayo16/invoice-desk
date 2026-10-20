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
  <div class="flex flex-wrap items-end justify-between gap-3 border-b border-zinc-200 pb-4">
    <div>
      <h1 class="page-title">Overview</h1>
      <p v-if="data" class="mt-0.5 text-sm text-zinc-500">All amounts in {{ data.currency }}</p>
    </div>
    <RouterLink to="/invoices/new" class="btn-primary">New invoice</RouterLink>
  </div>
  <p v-if="failure" class="field-error mt-4">{{ failure }}</p>

  <template v-if="data">
    <section class="mt-6 grid grid-cols-2 rounded-md border border-zinc-200 lg:grid-cols-4" aria-label="Key numbers">
      <div class="border-r border-b border-zinc-200 p-4 lg:border-b-0">
        <p class="text-xs text-zinc-500">Outstanding</p>
        <p class="num mt-1.5 text-xl font-medium">{{ formatMoney(data.outstanding, data.currency) }}</p>
        <p class="mt-0.5 text-xs text-zinc-500">Sent, not yet paid</p>
      </div>
      <RouterLink to="/invoices?status=overdue" class="border-b border-zinc-200 p-4 hover:bg-zinc-50 lg:border-r lg:border-b-0">
        <p class="text-xs text-zinc-500">Overdue</p>
        <p class="num mt-1.5 text-xl font-medium" :class="data.overdue_count ? 'text-red-700' : ''">{{ formatMoney(data.overdue, data.currency) }}</p>
        <p class="mt-0.5 text-xs text-zinc-500">{{ data.overdue_count }} {{ data.overdue_count === 1 ? 'invoice' : 'invoices' }} past due</p>
      </RouterLink>
      <div class="border-r border-zinc-200 p-4">
        <p class="text-xs text-zinc-500">Paid this month</p>
        <p class="num mt-1.5 text-xl font-medium text-brand-700">{{ formatMoney(data.paid_this_month, data.currency) }}</p>
        <p class="mt-0.5 text-xs text-zinc-500">Payments received</p>
      </div>
      <RouterLink to="/invoices?status=draft" class="p-4 hover:bg-zinc-50">
        <p class="text-xs text-zinc-500">Drafts</p>
        <p class="num mt-1.5 text-xl font-medium">{{ data.draft_count }}</p>
        <p class="mt-0.5 text-xs text-zinc-500">Not sent yet</p>
      </RouterLink>
    </section>

    <section class="mt-8">
      <div class="flex items-baseline justify-between gap-3">
        <h2 class="text-sm font-semibold">Payments received</h2>
        <p class="text-xs text-zinc-500">Last 12 months</p>
      </div>
      <RevenueChart class="mt-3" :data="data.revenue_by_month" :currency="data.currency" />
      <details class="mt-1 text-sm">
        <summary class="cursor-pointer text-xs text-zinc-500 hover:text-zinc-800">Show as table</summary>
        <table class="mt-2 w-full max-w-sm">
          <tr v-for="row in data.revenue_by_month" :key="row.month" class="border-b border-zinc-100">
            <td class="py-1">{{ row.month }}</td>
            <td class="num py-1 text-right">{{ formatMoney(row.amount, data.currency) }}</td>
          </tr>
        </table>
      </details>
    </section>

    <div class="mt-8 grid gap-8 lg:grid-cols-2">
      <section>
        <div class="flex items-baseline justify-between gap-3 border-b border-zinc-200 pb-2">
          <h2 class="text-sm font-semibold">Overdue invoices</h2>
          <RouterLink to="/invoices?status=overdue" class="text-xs text-zinc-500 hover:text-zinc-900">View all</RouterLink>
        </div>
        <p v-if="!data.overdue_invoices.length" class="py-6 text-sm text-zinc-500">Nothing is overdue.</p>
        <ul class="divide-y divide-zinc-100">
          <li v-for="inv in data.overdue_invoices" :key="inv.id">
            <RouterLink :to="`/invoices/${inv.id}`" class="-mx-2 flex items-center justify-between gap-3 rounded-md px-2 py-2 hover:bg-zinc-50">
              <span class="min-w-0">
                <span class="block truncate text-sm">{{ inv.client?.name }}</span>
                <span class="num text-xs text-zinc-500">{{ inv.number }} · due {{ formatDate(inv.due_date) }}</span>
              </span>
              <span class="num text-sm text-red-700">{{ formatMoney(inv.balance, inv.currency) }}</span>
            </RouterLink>
          </li>
        </ul>
      </section>
      <section>
        <div class="flex items-baseline justify-between gap-3 border-b border-zinc-200 pb-2">
          <h2 class="text-sm font-semibold">Top clients</h2>
          <p class="text-xs text-zinc-500">Billed, last 12 months</p>
        </div>
        <ul class="divide-y divide-zinc-100">
          <li v-for="c in data.top_clients" :key="c.id" class="flex items-center justify-between gap-3 py-2 text-sm">
            <span class="min-w-0 truncate">{{ c.name }} <span class="text-xs text-zinc-500">· {{ c.invoices }} invoices</span></span>
            <span class="num">{{ formatMoney(c.billed, data.currency) }}</span>
          </li>
        </ul>
      </section>
    </div>
  </template>
  <div v-else-if="!failure" class="mt-6 h-24 animate-pulse rounded-md bg-zinc-100" aria-busy="true" />
</template>
