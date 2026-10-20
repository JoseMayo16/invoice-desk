<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import ClientForm from '../components/ClientForm.vue'
import ModalDialog from '../components/ModalDialog.vue'
import { api, errorMessage, fieldErrors, type FieldErrors } from '../lib/api'
import { formatCents, lineCents, totalsCents } from '../lib/money'
import type { Client, Invoice, InvoiceItem, Paginated } from '../lib/types'
import { useAuth } from '../stores/auth'

const props = defineProps<{ id?: string }>()
const router = useRouter()
const auth = useAuth()

const today = new Date().toISOString().slice(0, 10)
const in30 = new Date(Date.now() + 30 * 864e5).toISOString().slice(0, 10)

type Line = InvoiceItem & { key: number }
let nextKey = 1
const blank = (): Line => ({ key: nextKey++, description: '', quantity: 1, unit_price: 0, tax_rate: 0 })

const form = reactive({ client_id: null as number | null, issue_date: today, due_date: in30, notes: '' })
const items = ref<Line[]>([blank()])
const clients = ref<Client[]>([])
const number = ref('')
const currency = ref(auth.currency)
const errors = ref<FieldErrors>({})
const failure = ref('')
const saving = ref(false)
const clientModal = ref(false)

const totals = computed(() => totalsCents(items.value))

onMounted(async () => {
  const requests: Promise<unknown>[] = [
    api.get<Paginated<Client>>('/clients', { params: { per_page: 100 } }).then((r) => { clients.value = r.data.data }),
  ]
  if (props.id) {
    requests.push(api.get<{ data: Invoice }>(`/invoices/${props.id}`).then(({ data: { data: inv } }) => {
      if (!inv.can?.update) router.replace(`/invoices/${inv.id}`)
      number.value = inv.number
      currency.value = inv.currency
      Object.assign(form, { client_id: inv.client?.id ?? null, issue_date: inv.issue_date, due_date: inv.due_date, notes: inv.notes ?? '' })
      items.value = (inv.items ?? []).map((i) => ({ ...i, key: nextKey++ }))
    }))
  }
  try {
    await Promise.all(requests)
  } catch (e) {
    failure.value = errorMessage(e)
  }
})

function err(field: string) {
  return errors.value[field]?.[0]
}

function addLine() {
  items.value.push(blank())
}

function removeLine(index: number) {
  items.value.splice(index, 1)
  if (!items.value.length) addLine()
}

function clientAdded(client: Client) {
  clients.value = [...clients.value, client].sort((a, b) => a.name.localeCompare(b.name))
  form.client_id = client.id
  clientModal.value = false
}

async function save() {
  saving.value = true
  errors.value = {}
  failure.value = ''
  const payload = {
    ...form,
    items: items.value.map(({ description, quantity, unit_price, tax_rate }) => ({ description, quantity, unit_price, tax_rate })),
  }
  try {
    const { data } = props.id
      ? await api.put<{ data: Invoice }>(`/invoices/${props.id}`, payload)
      : await api.post<{ data: Invoice }>('/invoices', payload)
    router.push(`/invoices/${data.data.id}`)
  } catch (e) {
    errors.value = fieldErrors(e)
    failure.value = Object.keys(errors.value).length ? 'Please fix the highlighted fields.' : errorMessage(e)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <RouterLink :to="id ? `/invoices/${id}` : '/invoices'" class="text-sm text-zinc-500 hover:text-zinc-800">← Back</RouterLink>
      <h1 class="page-title">{{ id ? `Edit ${number}` : 'New invoice' }}</h1>
    </div>
  </div>

  <form class="mt-6 space-y-4" novalidate @submit.prevent="save">
    <section class="card grid gap-4 p-5 sm:grid-cols-3">
      <div class="sm:col-span-3">
        <label class="label" for="client">Client</label>
        <div class="flex gap-2">
          <select id="client" v-model="form.client_id" class="input" :aria-invalid="!!err('client_id')">
            <option :value="null" disabled>Choose a client</option>
            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <button type="button" class="btn-secondary" @click="clientModal = true">New client</button>
        </div>
        <p v-if="err('client_id')" class="field-error">{{ err('client_id') }}</p>
      </div>
      <div>
        <label class="label" for="issue">Issue date</label>
        <input id="issue" v-model="form.issue_date" type="date" class="input" :aria-invalid="!!err('issue_date')" />
        <p v-if="err('issue_date')" class="field-error">{{ err('issue_date') }}</p>
      </div>
      <div>
        <label class="label" for="due">Due date</label>
        <input id="due" v-model="form.due_date" type="date" class="input" :aria-invalid="!!err('due_date')" />
        <p v-if="err('due_date')" class="field-error">{{ err('due_date') }}</p>
      </div>
      <div>
        <span class="label">Currency</span>
        <p class="input flex items-center bg-zinc-50 text-zinc-600">{{ currency }}</p>
      </div>
    </section>

    <section class="card p-5">
      <h2 class="font-semibold">Line items</h2>
      <p v-if="err('items')" class="field-error">{{ err('items') }}</p>
      <div class="mt-3 hidden grid-cols-[1fr_6rem_8rem_6rem_7rem_2.5rem] gap-2 text-xs font-medium text-zinc-500 md:grid">
        <span>Description</span><span class="text-right">Qty</span><span class="text-right">Unit price</span>
        <span class="text-right">Tax %</span><span class="text-right">Amount</span><span />
      </div>
      <div v-for="(line, i) in items" :key="line.key" class="mt-2 grid grid-cols-2 gap-2 border-b border-zinc-100 pb-3 md:grid-cols-[1fr_6rem_8rem_6rem_7rem_2.5rem] md:border-0 md:pb-0">
        <div class="col-span-2 md:col-span-1">
          <input v-model="line.description" class="input" placeholder="What did you do?" :aria-label="`Line ${i + 1} description`" :aria-invalid="!!err(`items.${i}.description`)" />
          <p v-if="err(`items.${i}.description`)" class="field-error">{{ err(`items.${i}.description`) }}</p>
        </div>
        <div>
          <input v-model.number="line.quantity" type="number" min="0" step="0.01" class="input text-right" :aria-label="`Line ${i + 1} quantity`" :aria-invalid="!!err(`items.${i}.quantity`)" />
          <p v-if="err(`items.${i}.quantity`)" class="field-error">{{ err(`items.${i}.quantity`) }}</p>
        </div>
        <div>
          <input v-model.number="line.unit_price" type="number" min="0" step="0.01" class="input text-right" :aria-label="`Line ${i + 1} unit price`" :aria-invalid="!!err(`items.${i}.unit_price`)" />
          <p v-if="err(`items.${i}.unit_price`)" class="field-error">{{ err(`items.${i}.unit_price`) }}</p>
        </div>
        <div>
          <input v-model.number="line.tax_rate" type="number" min="0" max="100" step="0.01" class="input text-right" :aria-label="`Line ${i + 1} tax rate`" :aria-invalid="!!err(`items.${i}.tax_rate`)" />
          <p v-if="err(`items.${i}.tax_rate`)" class="field-error">{{ err(`items.${i}.tax_rate`) }}</p>
        </div>
        <p class="num self-center text-right text-sm">{{ formatCents(lineCents(line).subtotal, currency) }}</p>
        <button type="button" class="btn-ghost self-center p-2" :aria-label="`Remove line ${i + 1}`" @click="removeLine(i)">
          <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" /></svg>
        </button>
      </div>
      <button type="button" class="btn-ghost mt-3 text-brand-700" @click="addLine">+ Add line</button>

      <dl class="mt-4 ml-auto grid max-w-xs grid-cols-2 gap-y-1 border-t border-zinc-200 pt-4 text-sm">
        <dt class="text-zinc-500">Subtotal</dt><dd class="num text-right">{{ formatCents(totals.subtotal, currency) }}</dd>
        <dt class="text-zinc-500">Tax</dt><dd class="num text-right">{{ formatCents(totals.tax, currency) }}</dd>
        <dt class="font-semibold">Total</dt><dd class="num text-right font-semibold">{{ formatCents(totals.total, currency) }}</dd>
      </dl>
    </section>

    <section class="card p-5">
      <label class="label" for="notes">Notes for the client</label>
      <textarea id="notes" v-model="form.notes" rows="3" class="input h-auto py-1.5" placeholder="Payment instructions, thanks, terms…" />
    </section>

    <div class="flex items-center justify-end gap-3">
      <p v-if="failure" class="field-error mr-auto">{{ failure }}</p>
      <RouterLink :to="id ? `/invoices/${id}` : '/invoices'" class="btn-secondary">Cancel</RouterLink>
      <button class="btn-primary" :disabled="saving">{{ saving ? 'Saving…' : id ? 'Save changes' : 'Create draft' }}</button>
    </div>
  </form>

  <ModalDialog :open="clientModal" title="New client" @close="clientModal = false">
    <ClientForm v-if="clientModal" @saved="clientAdded" @cancel="clientModal = false" />
  </ModalDialog>
</template>
