<script setup lang="ts">
import { formatDate, formatMoney } from '../lib/money'
import type { InvoiceItem } from '../lib/types'

defineProps<{
  number: string
  from: { name: string; email?: string | null; address?: string | null; tax_id?: string | null }
  to: { name: string; address?: string | null; tax_id?: string | null }
  issueDate: string
  dueDate: string
  currency: string
  items: InvoiceItem[]
  subtotal: number
  taxTotal: number
  total: number
  amountPaid: number
  balance: number
  notes?: string | null
}>()
</script>

<template>
  <article class="card overflow-hidden">
    <div class="h-1.5 bg-brand-600" />
    <div class="p-6 sm:p-10">
      <header class="flex flex-col gap-6 sm:flex-row sm:justify-between">
        <div class="text-sm">
          <p class="text-base font-semibold">{{ from.name }}</p>
          <p v-if="from.address" class="whitespace-pre-line text-zinc-500">{{ from.address }}</p>
          <p v-if="from.email" class="text-zinc-500">{{ from.email }}</p>
          <p v-if="from.tax_id" class="text-zinc-500">Tax ID {{ from.tax_id }}</p>
        </div>
        <div class="sm:text-right">
          <p class="text-2xl font-bold tracking-wide text-brand-700">INVOICE</p>
          <dl class="mt-2 grid grid-cols-[auto_auto] gap-x-4 gap-y-0.5 text-sm whitespace-nowrap sm:justify-end">
            <dt class="text-zinc-500">Number</dt><dd class="num">{{ number }}</dd>
            <dt class="text-zinc-500">Issued</dt><dd>{{ formatDate(issueDate) }}</dd>
            <dt class="text-zinc-500">Due</dt><dd>{{ formatDate(dueDate) }}</dd>
          </dl>
        </div>
      </header>

      <section class="mt-8 text-sm">
        <p class="text-xs font-semibold tracking-wider text-zinc-500 uppercase">Bill to</p>
        <p class="font-semibold">{{ to.name }}</p>
        <p v-if="to.address" class="whitespace-pre-line text-zinc-500">{{ to.address }}</p>
        <p v-if="to.tax_id" class="text-zinc-500">Tax ID {{ to.tax_id }}</p>
      </section>

      <div class="mt-8 overflow-x-auto">
        <table class="w-full min-w-[32rem] text-sm">
          <thead>
            <tr class="border-b-2 border-ink text-left text-xs tracking-wider text-zinc-500 uppercase">
              <th class="py-2 pr-3 font-semibold">Description</th>
              <th class="py-2 pr-3 text-right font-semibold">Qty</th>
              <th class="py-2 pr-3 text-right font-semibold">Unit price</th>
              <th class="py-2 pr-3 text-right font-semibold">Tax</th>
              <th class="py-2 text-right font-semibold">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, i) in items" :key="i" class="border-b border-zinc-200">
              <td class="py-2.5 pr-3">{{ item.description }}</td>
              <td class="num py-2.5 pr-3 text-right">{{ item.quantity }}</td>
              <td class="num py-2.5 pr-3 text-right">{{ formatMoney(item.unit_price, currency) }}</td>
              <td class="num py-2.5 pr-3 text-right">{{ item.tax_rate }}%</td>
              <td class="num py-2.5 text-right">{{ formatMoney(item.line_subtotal ?? 0, currency) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <dl class="mt-6 ml-auto grid max-w-xs grid-cols-2 gap-y-1.5 text-sm">
        <dt class="text-zinc-500">Subtotal</dt><dd class="num text-right">{{ formatMoney(subtotal, currency) }}</dd>
        <dt class="text-zinc-500">Tax</dt><dd class="num text-right">{{ formatMoney(taxTotal, currency) }}</dd>
        <dt class="border-t border-ink pt-2 font-semibold">Total</dt>
        <dd class="num border-t border-ink pt-2 text-right font-semibold">{{ formatMoney(total, currency) }} {{ currency }}</dd>
        <template v-if="amountPaid > 0">
          <dt class="text-zinc-500">Paid</dt><dd class="num text-right text-zinc-500">−{{ formatMoney(amountPaid, currency) }}</dd>
          <dt class="font-semibold">Balance due</dt><dd class="num text-right font-semibold">{{ formatMoney(balance, currency) }}</dd>
        </template>
      </dl>

      <p v-if="notes" class="mt-8 border-t border-zinc-200 pt-4 text-sm whitespace-pre-line text-zinc-600">{{ notes }}</p>
    </div>
  </article>
</template>
