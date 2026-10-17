<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import InvoicePaper from '../components/InvoicePaper.vue'
import StatusBadge from '../components/StatusBadge.vue'
import { api, errorMessage } from '../lib/api'
import { formatMoney } from '../lib/money'
import type { PublicInvoice } from '../lib/types'

const props = defineProps<{ token: string }>()
const route = useRoute()
const invoice = ref<PublicInvoice | null>(null)
const failure = ref('')
const paying = ref(false)
const returnedFromCheckout = route.query.paid === '1'
let poll: ReturnType<typeof setInterval> | undefined

async function load() {
  try {
    invoice.value = (await api.get<{ data: PublicInvoice }>(`/public/invoices/${props.token}`)).data.data
    if (invoice.value.status === 'paid') clearInterval(poll)
  } catch (e) {
    failure.value = errorMessage(e)
  }
}

onMounted(() => {
  load()
  // Stripe confirms through a webhook a moment after the redirect: check again briefly.
  if (returnedFromCheckout) {
    let tries = 0
    poll = setInterval(() => (++tries > 10 ? clearInterval(poll) : load()), 2000)
  }
})
onUnmounted(() => clearInterval(poll))

async function pay() {
  paying.value = true
  try {
    const { data } = await api.post<{ url: string }>(`/public/invoices/${props.token}/checkout`)
    window.location.href = data.url
  } catch (e) {
    alert(errorMessage(e))
    paying.value = false
  }
}
</script>

<template>
  <div class="min-h-screen px-4 py-8 sm:py-12">
    <div class="mx-auto max-w-3xl">
      <p v-if="failure" class="card p-8 text-center text-zinc-600">This invoice link is not valid or has been withdrawn.</p>
      <template v-if="invoice">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <p class="text-sm text-zinc-500">Invoice from <span class="font-semibold text-zinc-800">{{ invoice.from.name }}</span></p>
            <StatusBadge :status="invoice.status" />
          </div>
          <div class="flex gap-2">
            <a :href="`/api/public/invoices/${token}/pdf`" class="btn-secondary">Download PDF</a>
            <button v-if="invoice.can_pay_online" class="btn-primary" :disabled="paying" @click="pay">
              {{ paying ? 'Opening checkout…' : `Pay ${formatMoney(invoice.balance, invoice.currency)}` }}
            </button>
          </div>
        </div>
        <p v-if="returnedFromCheckout && invoice.status !== 'paid'" class="mb-4 rounded-lg bg-sky-50 px-4 py-3 text-sm text-sky-800" role="status">
          Thanks! We are confirming your payment with the bank. This page updates on its own.
        </p>
        <p v-if="invoice.status === 'paid'" class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
          This invoice is paid. Thank you!
        </p>
        <InvoicePaper
          :number="invoice.number"
          :from="invoice.from"
          :to="invoice.to"
          :issue-date="invoice.issue_date"
          :due-date="invoice.due_date"
          :currency="invoice.currency"
          :items="invoice.items"
          :subtotal="invoice.subtotal"
          :tax-total="invoice.tax_total"
          :total="invoice.total"
          :amount-paid="invoice.amount_paid"
          :balance="invoice.balance"
          :notes="invoice.notes"
        />
        <p class="mt-6 text-center text-xs text-zinc-400">Sent with InvoiceDesk</p>
      </template>
    </div>
  </div>
</template>
