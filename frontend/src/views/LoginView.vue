<script setup lang="ts">
import LogoMark from '../components/LogoMark.vue'
import { reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { errorMessage, fieldErrors, type FieldErrors } from '../lib/api'
import { useAuth } from '../stores/auth'

const auth = useAuth()
const route = useRoute()
const router = useRouter()
const form = reactive({ email: '', password: '' })
const errors = ref<FieldErrors>({})
const failure = ref('')
const busy = ref(false)

async function submit() {
  busy.value = true
  errors.value = {}
  failure.value = ''
  try {
    await auth.login(form.email, form.password)
    router.push(typeof route.query.next === 'string' ? route.query.next : '/dashboard')
  } catch (e) {
    errors.value = fieldErrors(e)
    if (!Object.keys(errors.value).length) failure.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}

function useDemo(email: string) {
  form.email = email
  form.password = 'password'
  submit()
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">
      <div class="mb-8 flex items-center gap-2 text-lg font-semibold">
        <LogoMark class="size-8" /> InvoiceDesk
      </div>
      <h1 class="page-title">Sign in</h1>
      <p class="mt-1 text-sm text-zinc-500">Invoices, clients and online payments in one place.</p>

      <form class="mt-6 space-y-4" novalidate @submit.prevent="submit">
        <div>
          <label class="label" for="email">Email</label>
          <input id="email" v-model="form.email" type="email" class="input" autocomplete="username" :aria-invalid="!!errors.email" required />
          <p v-if="errors.email" class="field-error">{{ errors.email[0] }}</p>
        </div>
        <div>
          <label class="label" for="password">Password</label>
          <input id="password" v-model="form.password" type="password" class="input" autocomplete="current-password" :aria-invalid="!!errors.password" required />
          <p v-if="errors.password" class="field-error">{{ errors.password[0] }}</p>
        </div>
        <p v-if="failure" class="field-error">{{ failure }}</p>
        <button class="btn-primary w-full" :disabled="busy">{{ busy ? 'Signing in…' : 'Sign in' }}</button>
      </form>

      <div class="card mt-6 p-4 text-sm">
        <p class="font-medium">Try the demo</p>
        <p class="mt-0.5 text-zinc-500">A sample studio with a year of invoices.</p>
        <div class="mt-3 flex gap-2">
          <button class="btn-secondary flex-1" :disabled="busy" @click="useDemo('owner@demo.test')">As owner</button>
          <button class="btn-secondary flex-1" :disabled="busy" @click="useDemo('member@demo.test')">As member</button>
        </div>
      </div>

      <p class="mt-6 text-center text-sm text-zinc-500">
        New here? <RouterLink to="/register" class="font-semibold text-brand-700 hover:underline">Create an account</RouterLink>
      </p>
    </div>
  </div>
</template>
