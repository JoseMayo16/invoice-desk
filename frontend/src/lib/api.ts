import axios, { AxiosError } from 'axios'

/**
 * Same-origin requests with the Laravel session cookie. Axios copies the
 * XSRF-TOKEN cookie into the X-XSRF-TOKEN header on every write.
 */
export const api = axios.create({
  baseURL: '/api',
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: 'application/json' },
})

/** Must be called once before the first login or register. */
export function csrf() {
  return axios.get('/sanctum/csrf-cookie', { withCredentials: true })
}

export type FieldErrors = Record<string, string[]>

/** Laravel's 422 body as a field -> messages map; empty for other errors. */
export function fieldErrors(error: unknown): FieldErrors {
  if (error instanceof AxiosError && error.response?.status === 422) {
    return error.response.data?.errors ?? {}
  }
  return {}
}

/** A human message for any failed request. */
export function errorMessage(error: unknown): string {
  if (error instanceof AxiosError) {
    const data = error.response?.data as { message?: string } | undefined
    if (data?.message) return data.message
    if (!error.response) return 'Cannot reach the server. Check your connection.'
    return `Request failed (${error.response.status}).`
  }
  return 'Something went wrong.'
}
