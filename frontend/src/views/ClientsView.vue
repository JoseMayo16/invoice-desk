<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import ClientForm from '../components/ClientForm.vue'
import ModalDialog from '../components/ModalDialog.vue'
import { api, errorMessage } from '../lib/api'
import { formatMoney } from '../lib/money'
import type { Client, Paginated } from '../lib/types'
import { useAuth } from '../stores/auth'

const auth = useAuth()
const clients = ref<Client[]>([])
const search = ref('')
const editing = ref<Client | null>(null)
const formOpen = ref(false)
const failure = ref('')

async function load() {
  try {
    clients.value = (await api.get<Paginated<Client>>('/clients', { params: { q: search.value, per_page: 100 } })).data.data
  } catch (e) {
    failure.value = errorMessage(e)
  }
}

function open(client: Client | null) {
  editing.value = client
  formOpen.value = true
}

function saved() {
  formOpen.value = false
  load()
}

async function remove(client: Client) {
  if (!confirm(`Delete ${client.name}? This cannot be undone.`)) return
  try {
    await api.delete(`/clients/${client.id}`)
    load()
  } catch (e) {
    alert(errorMessage(e))
  }
}

let timer: ReturnType<typeof setTimeout>
watch(search, () => {
  clearTimeout(timer)
  timer = setTimeout(load, 300)
})
onMounted(load)
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">Clients</h1>
    <button class="btn-primary" @click="open(null)">Add client</button>
  </div>
  <input v-model="search" type="search" class="input mt-5 sm:max-w-64" placeholder="Search name or email" aria-label="Search clients" />
  <p v-if="failure" class="field-error mt-4">{{ failure }}</p>

  <div class="card mt-4 overflow-x-auto">
    <table class="w-full min-w-[40rem] text-sm">
      <thead class="border-b border-zinc-200 text-left text-xs text-zinc-500">
        <tr>
          <th class="px-4 py-3 font-medium">Name</th>
          <th class="px-4 py-3 font-medium">Email</th>
          <th class="px-4 py-3 text-right font-medium">Invoices</th>
          <th class="px-4 py-3 text-right font-medium">Outstanding</th>
          <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="c in clients" :key="c.id" class="border-b border-zinc-100 whitespace-nowrap last:border-0">
          <td class="px-4 py-3 font-medium">{{ c.name }}</td>
          <td class="px-4 py-3 text-zinc-600">{{ c.email }}</td>
          <td class="num px-4 py-3 text-right">
            <RouterLink v-if="c.invoices_count" :to="`/invoices?client_id=${c.id}`" class="hover:underline">{{ c.invoices_count }}</RouterLink>
            <span v-else class="text-zinc-400">0</span>
          </td>
          <td class="num px-4 py-3 text-right" :class="c.outstanding ? 'font-medium' : 'text-zinc-400'">{{ formatMoney(c.outstanding ?? 0, auth.currency) }}</td>
          <td class="px-4 py-3 text-right whitespace-nowrap">
            <button class="btn-ghost px-2 py-1" @click="open(c)">Edit</button>
            <button v-if="auth.isOwner" class="btn-ghost px-2 py-1 text-red-700" @click="remove(c)">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
    <p v-if="!clients.length && !failure" class="px-4 py-10 text-center text-sm text-zinc-500">No clients yet.</p>
  </div>

  <ModalDialog :open="formOpen" :title="editing ? 'Edit client' : 'New client'" @close="formOpen = false">
    <ClientForm v-if="formOpen" :client="editing" @saved="saved" @cancel="formOpen = false" />
  </ModalDialog>
</template>
