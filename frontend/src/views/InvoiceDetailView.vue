<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import InvoicePaper from '../components/InvoicePaper.vue'
import ModalDialog from '../components/ModalDialog.vue'
import StatusBadge from '../components/StatusBadge.vue'
import { api, errorMessage, fieldErrors, type FieldErrors } from '../lib/api'
import { formatDate, formatMoney } from '../lib/money'
import type { Invoice } from '../lib/types'
import { useAuth } from '../stores/auth'

const props = defineProps<{ id: string }>()
const router = useRouter()
const auth = useAuth()

const invoice = ref<Invoice | null>(null)
const failure = ref('')
const busy = ref('')
const notice = ref('')
const paymentOpen = ref(false)
const payment = reactive({ amount: 0, method: 'bank_transfer', paid_at: new Date().toISOString().slice(0, 10), reference: '' })
const paymentErrors = ref<FieldErrors>({})

const company = computed(() => auth.user!.company)
const methods = { card: 'Card', bank_transfer: 'Bank transfer', cash: 'Cash', other: 'Other' }

async function load() {
  try {
    invoice.value = (await api.get<{ data: Invoice }>(`/invoices/${props.id}`)).data.data
  } catch (e) {
    failure.value = errorMessage(e)
  }
}
onMounted(load)

async function act(name: string, request: () => Promise<{ data: { data: Invoice } }>, message: string) {
  busy.value = name
  notice.value = ''
  try {
    invoice.value = (await request()).data.data
    notice.value = message
  } catch (e) {
    alert(errorMessage(e))
  } finally {
    busy.value = ''
  }
}

const send = () => act('send', () => api.post(`/invoices/${props.id}/send`), `Emailed to ${invoice.value?.client?.email}.`)
const voidIt = () => confirm('Void this invoice? It stays in your records but cannot be paid.')
  && act('void', () => api.post(`/invoices/${props.id}/void`), 'Invoice voided.')

async function duplicate() {
  busy.value = 'duplicate'
  try {
    const { data } = await api.post<{ data: Invoice }>(`/invoices/${props.id}/duplicate`)
    router.push(`/invoices/${data.data.id}/edit`)
  } catch (e) {
    alert(errorMessage(e))
  } finally {
    busy.value = ''
  }
}

async function remove() {
  if (!confirm(`Delete ${invoice.value?.number}? This cannot be undone.`)) return
  try {
    await api.delete(`/invoices/${props.id}`)
    router.push('/invoices')
  } catch (e) {
    alert(errorMessage(e))
  }
}

function openPayment() {
  payment.amount = invoice.value?.balance ?? 0
  paymentErrors.value = {}
  paymentOpen.value = true
}

async function recordPayment() {
  busy.value = 'payment'
  paymentErrors.value = {}
  try {
    invoice.value = (await api.post<{ data: Invoice }>(`/invoices/${props.id}/payments`, payment)).data.data
    paymentOpen.value = false
    notice.value = 'Payment recorded.'
  } catch (e) {
    paymentErrors.value = fieldErrors(e)
    if (!Object.keys(paymentErrors.value).length) alert(errorMessage(e))
  } finally {
    busy.value = ''
  }
}

async function copyLink() {
  await navigator.clipboard.writeText(invoice.value!.public_url!)
  notice.value = 'Payment link copied.'
}
</script>

<template>
  <p v-if="failure" class="field-error">{{ failure }}</p>
  <template v-if="invoice">
    <RouterLink to="/invoices" class="text-sm text-zinc-500 hover:text-zinc-800">← Invoices</RouterLink>
    <div class="mt-1 flex flex-wrap items-start justify-between gap-4">
      <div>
        <div class="flex items-center gap-3">
          <h1 class="num text-2xl font-bold">{{ invoice.number }}</h1>
          <StatusBadge :status="invoice.status" />
        </div>
        <p class="mt-1 text-sm text-zinc-500">
          {{ invoice.client?.name }} · {{ formatMoney(invoice.total, invoice.currency) }}
          <template v-if="invoice.balance && invoice.status !== 'draft'"> · {{ formatMoney(invoice.balance, invoice.currency) }} due {{ formatDate(invoice.due_date) }}</template>
        </p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button v-if="invoice.status === 'draft'" class="btn-primary" :disabled="!!busy" @click="send">{{ busy === 'send' ? 'Sending…' : 'Send to client' }}</button>
        <button v-if="invoice.can?.record_payment" class="btn-primary" :disabled="!!busy" @click="openPayment">Record payment</button>
        <RouterLink v-if="invoice.can?.update" :to="`/invoices/${invoice.id}/edit`" class="btn-secondary">Edit</RouterLink>
        <a :href="`/api/invoices/${invoice.id}/pdf`" target="_blank" rel="noopener" class="btn-secondary">PDF</a>
        <button v-if="invoice.public_url" class="btn-secondary" @click="copyLink">Copy pay link</button>
        <button class="btn-secondary" :disabled="!!busy" @click="duplicate">Duplicate</button>
        <button v-if="invoice.status === 'sent' || invoice.status === 'overdue' || invoice.status === 'draft'" class="btn-ghost" :disabled="!!busy" @click="voidIt">Void</button>
        <button v-if="invoice.can?.delete" class="btn-danger" @click="remove">Delete</button>
      </div>
    </div>
    <p v-if="notice" class="mt-4 rounded-lg bg-brand-50 px-4 py-2.5 text-sm text-brand-700" role="status">{{ notice }}</p>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_18rem]">
      <InvoicePaper
        :number="invoice.number"
        :from="company"
        :to="{ name: invoice.client?.name ?? '', address: invoice.client?.address, tax_id: invoice.client?.tax_id }"
        :issue-date="invoice.issue_date"
        :due-date="invoice.due_date"
        :currency="invoice.currency"
        :items="invoice.items ?? []"
        :subtotal="invoice.subtotal"
        :tax-total="invoice.tax_total"
        :total="invoice.total"
        :amount-paid="invoice.amount_paid"
        :balance="invoice.balance"
        :notes="invoice.notes"
      />
      <aside class="space-y-4">
        <section class="card p-4 text-sm">
          <h2 class="font-semibold">Payments</h2>
          <p v-if="!invoice.payments?.length" class="mt-2 text-zinc-500">No payments yet.</p>
          <ul class="mt-2 divide-y divide-zinc-100">
            <li v-for="p in invoice.payments" :key="p.id" class="py-2">
              <div class="flex justify-between"><span class="num font-medium">{{ formatMoney(p.amount, invoice.currency) }}</span><span class="text-zinc-500">{{ formatDate(p.paid_at) }}</span></div>
              <div class="text-xs text-zinc-500">{{ methods[p.method] }}<template v-if="p.reference"> · {{ p.reference }}</template></div>
            </li>
          </ul>
        </section>
        <section class="card p-4 text-sm">
          <h2 class="font-semibold">Activity</h2>
          <ul class="mt-2 space-y-1 text-zinc-600">
            <li>Issued {{ formatDate(invoice.issue_date) }}</li>
            <li v-if="invoice.sent_at">Sent {{ formatDate(invoice.sent_at) }}</li>
            <li v-if="invoice.paid_at">Paid {{ formatDate(invoice.paid_at) }}</li>
          </ul>
          <a v-if="invoice.public_url" :href="invoice.public_url" target="_blank" rel="noopener" class="mt-3 inline-block font-medium text-brand-700 hover:underline">Open the client's view</a>
        </section>
      </aside>
    </div>

    <ModalDialog :open="paymentOpen" title="Record a payment" @close="paymentOpen = false">
      <form class="grid gap-4 sm:grid-cols-2" novalidate @submit.prevent="recordPayment">
        <div>
          <label class="label" for="p-amount">Amount ({{ invoice.currency }})</label>
          <input id="p-amount" v-model.number="payment.amount" type="number" step="0.01" min="0" class="input" :aria-invalid="!!paymentErrors.amount" />
          <p v-if="paymentErrors.amount" class="field-error">{{ paymentErrors.amount[0] }}</p>
        </div>
        <div>
          <label class="label" for="p-date">Date received</label>
          <input id="p-date" v-model="payment.paid_at" type="date" class="input" :aria-invalid="!!paymentErrors.paid_at" />
          <p v-if="paymentErrors.paid_at" class="field-error">{{ paymentErrors.paid_at[0] }}</p>
        </div>
        <div>
          <label class="label" for="p-method">Method</label>
          <select id="p-method" v-model="payment.method" class="input">
            <option value="bank_transfer">Bank transfer</option>
            <option value="cash">Cash</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div>
          <label class="label" for="p-ref">Reference</label>
          <input id="p-ref" v-model="payment.reference" class="input" placeholder="Optional" />
        </div>
        <div class="flex justify-end gap-2 sm:col-span-2">
          <button type="button" class="btn-secondary" @click="paymentOpen = false">Cancel</button>
          <button class="btn-primary" :disabled="busy === 'payment'">Record payment</button>
        </div>
      </form>
    </ModalDialog>
  </template>
</template>
