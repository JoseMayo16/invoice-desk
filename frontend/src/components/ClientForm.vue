<script setup lang="ts">
import { reactive, ref } from 'vue'
import { api, errorMessage, fieldErrors, type FieldErrors } from '../lib/api'
import type { Client } from '../lib/types'

const props = defineProps<{ client?: Client | null }>()
const emit = defineEmits<{ saved: [client: Client]; cancel: [] }>()

const form = reactive({
  name: props.client?.name ?? '',
  email: props.client?.email ?? '',
  phone: props.client?.phone ?? '',
  tax_id: props.client?.tax_id ?? '',
  address: props.client?.address ?? '',
  notes: props.client?.notes ?? '',
})
const errors = ref<FieldErrors>({})
const failure = ref('')
const saving = ref(false)

async function save() {
  saving.value = true
  errors.value = {}
  failure.value = ''
  try {
    const { data } = props.client
      ? await api.put<{ data: Client }>(`/clients/${props.client.id}`, form)
      : await api.post<{ data: Client }>('/clients', form)
    emit('saved', data.data)
  } catch (e) {
    errors.value = fieldErrors(e)
    if (!Object.keys(errors.value).length) failure.value = errorMessage(e)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form class="grid gap-4 sm:grid-cols-2" novalidate @submit.prevent="save">
    <div class="sm:col-span-2">
      <label class="label" for="c-name">Name</label>
      <input id="c-name" v-model="form.name" class="input" :aria-invalid="!!errors.name" required autofocus />
      <p v-if="errors.name" class="field-error">{{ errors.name[0] }}</p>
    </div>
    <div>
      <label class="label" for="c-email">Email</label>
      <input id="c-email" v-model="form.email" type="email" class="input" :aria-invalid="!!errors.email" />
      <p v-if="errors.email" class="field-error">{{ errors.email[0] }}</p>
    </div>
    <div>
      <label class="label" for="c-phone">Phone</label>
      <input id="c-phone" v-model="form.phone" class="input" :aria-invalid="!!errors.phone" />
    </div>
    <div>
      <label class="label" for="c-tax">Tax ID</label>
      <input id="c-tax" v-model="form.tax_id" class="input" :aria-invalid="!!errors.tax_id" />
    </div>
    <div class="sm:col-span-2">
      <label class="label" for="c-address">Billing address</label>
      <textarea id="c-address" v-model="form.address" rows="2" class="input h-auto py-1.5" />
    </div>
    <div class="sm:col-span-2">
      <label class="label" for="c-notes">Notes</label>
      <textarea id="c-notes" v-model="form.notes" rows="2" class="input h-auto py-1.5" placeholder="Only visible to your team" />
    </div>
    <p v-if="failure" class="field-error sm:col-span-2">{{ failure }}</p>
    <div class="flex justify-end gap-2 sm:col-span-2">
      <button type="button" class="btn-secondary" @click="emit('cancel')">Cancel</button>
      <button class="btn-primary" :disabled="saving">{{ saving ? 'Saving…' : client ? 'Save changes' : 'Add client' }}</button>
    </div>
  </form>
</template>
