import type { InvoiceItem } from './types'

/**
 * Mirrors App\Services\InvoiceCalculator on the server: integer cents, each line
 * rounded once (half away from zero), totals are sums of lines. The editor uses
 * it for live totals; the server recomputes and is the source of truth.
 */
export function toCents(amount: number | string): number {
  return roundHalfAway(Number(amount) * 100)
}

export function lineCents(item: Pick<InvoiceItem, 'quantity' | 'unit_price' | 'tax_rate'>) {
  const subtotal = roundHalfAway(Number(item.quantity) * toCents(item.unit_price))
  const tax = roundHalfAway((subtotal * Number(item.tax_rate || 0)) / 100)
  return { subtotal, tax }
}

export function totalsCents(items: Pick<InvoiceItem, 'quantity' | 'unit_price' | 'tax_rate'>[]) {
  let subtotal = 0
  let tax = 0
  for (const item of items) {
    const line = lineCents(item)
    subtotal += line.subtotal
    tax += line.tax
  }
  return { subtotal, tax, total: subtotal + tax }
}

/** PHP's round(): .5 goes away from zero (Math.round sends -0.5 to 0). */
function roundHalfAway(value: number): number {
  const rounded = Math.round(Math.abs(value) + Number.EPSILON * Math.abs(value))
  return Math.sign(value) * rounded
}

const formatters = new Map<string, Intl.NumberFormat>()

export function formatMoney(amount: number, currency: string): string {
  let f = formatters.get(currency)
  if (!f) {
    f = new Intl.NumberFormat('en-US', { style: 'currency', currency, currencyDisplay: 'narrowSymbol' })
    formatters.set(currency, f)
  }
  return f.format(amount)
}

export function formatCents(cents: number, currency: string): string {
  return formatMoney(cents / 100, currency)
}

export function formatDate(iso: string | null | undefined): string {
  if (!iso) return ''
  const date = new Date(iso.length === 10 ? `${iso}T00:00:00` : iso)
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
