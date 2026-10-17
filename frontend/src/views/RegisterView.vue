<script setup lang="ts">
import LogoMark from '../components/LogoMark.vue'
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { errorMessage, fieldErrors, type FieldErrors } from '../lib/api'
import { useAuth } from '../stores/auth'

const auth = useAuth()
const router = useRouter()
const currencies = ['USD', 'EUR', 'GBP', 'COP', 'MXN', 'ARS', 'CLP', 'PEN', 'CAD', 'AUD']
const form = reactive({ name: '', email: '', password: '', password_confirmation: '', company_name: '', currency: 'USD' })
const errors = ref<FieldErrors>({})
const failure = ref('')
const busy = ref(false)

async function submit() {
  busy.value = true
  errors.value = {}
  failure.value = ''
  try {
    await auth.register(form)
    router.push('/dashboard')
  } catch (e) {
    errors.value = fieldErrors(e)
    if (!Object.keys(errors.value).length) failure.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
      <div class="mb-8 flex items-center gap-2 text-lg font-bold">
        <LogoMark class="size-8" /> InvoiceDesk
      </div>
      <h1 class="text-2xl font-bold">Create your account</h1>
      <p class="mt-1 text-sm text-zinc-500">You will be the owner of your company's workspace.</p>

      <form class="mt-6 grid gap-4 sm:grid-cols-2" novalidate @submit.prevent="submit">
        <div class="sm:col-span-2">
          <label class="label" for="company">Company or studio name</label>
          <input id="company" v-model="form.company_name" class="input" :aria-invalid="!!errors.company_name" />
          <p v-if="errors.company_name" class="field-error">{{ errors.company_name[0] }}</p>
        </div>
        <div>
          <label class="label" for="name">Your name</label>
          <input id="name" v-model="form.name" class="input" autocomplete="name" :aria-invalid="!!errors.name" />
          <p v-if="errors.name" class="field-error">{{ errors.name[0] }}</p>
        </div>
        <div>
          <label class="label" for="currency">Currency</label>
          <select id="currency" v-model="form.currency" class="input">
            <option v-for="c in currencies" :key="c">{{ c }}</option>
          </select>
        </div>
        <div class="sm:col-span-2">
          <label class="label" for="email">Email</label>
          <input id="email" v-model="form.email" type="email" class="input" autocomplete="email" :aria-invalid="!!errors.email" />
          <p v-if="errors.email" class="field-error">{{ errors.email[0] }}</p>
        </div>
        <div>
          <label class="label" for="password">Password</label>
          <input id="password" v-model="form.password" type="password" class="input" autocomplete="new-password" :aria-invalid="!!errors.password" />
          <p v-if="errors.password" class="field-error">{{ errors.password[0] }}</p>
        </div>
        <div>
          <label class="label" for="password2">Confirm password</label>
          <input id="password2" v-model="form.password_confirmation" type="password" class="input" autocomplete="new-password" />
        </div>
        <p v-if="failure" class="field-error sm:col-span-2">{{ failure }}</p>
        <button class="btn-primary sm:col-span-2" :disabled="busy">{{ busy ? 'Creating…' : 'Create account' }}</button>
      </form>
      <p class="mt-6 text-center text-sm text-zinc-500">
        Already have an account? <RouterLink to="/login" class="font-semibold text-brand-700 hover:underline">Sign in</RouterLink>
      </p>
    </div>
  </div>
</template>
