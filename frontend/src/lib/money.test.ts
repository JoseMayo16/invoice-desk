import { describe, expect, it } from 'vitest'
import { formatMoney, lineCents, toCents, totalsCents } from './money'

// Same cases as tests/Unit/InvoiceCalculatorTest.php, so the live totals in the
// editor and the server's totals cannot drift apart.
describe('invoice math', () => {
  it.each([
    ['whole units', { quantity: 2, unit_price: 150, tax_rate: 19 }, 30000, 5700],
    ['no tax', { quantity: 3, unit_price: 9.99, tax_rate: 0 }, 2997, 0],
    ['fractional quantity', { quantity: 1.5, unit_price: 33.33, tax_rate: 0 }, 5000, 0],
    ['tax rounds half up', { quantity: 1, unit_price: 10.5, tax_rate: 10 }, 1050, 105],
    ['tax rounds per line', { quantity: 1, unit_price: 0.01, tax_rate: 50 }, 1, 1],
    ['decimal tax rate', { quantity: 1, unit_price: 100, tax_rate: 8.5 }, 10000, 850],
  ])('%s', (_, item, subtotal, tax) => {
    expect(lineCents(item)).toEqual({ subtotal, tax })
  })

  it('totals are sums of the rounded lines', () => {
    const line = { quantity: 1, unit_price: 0.01, tax_rate: 50 }
    expect(totalsCents([line, line])).toEqual({ subtotal: 2, tax: 2, total: 4 })
  })

  it('matches the invoice in the API tests', () => {
    const totals = totalsCents([
      { quantity: 1, unit_price: 2400, tax_rate: 19 },
      { quantity: 12, unit_price: 15.5, tax_rate: 0 },
    ])
    expect(totals).toEqual({ subtotal: 258600, tax: 45600, total: 304200 })
  })

  it('converts strings and floats to cents without drift', () => {
    expect(toCents('120.50')).toBe(12050)
    expect(toCents(19.99)).toBe(1999)
    expect(toCents(0.07)).toBe(7)
  })

  it('formats money', () => {
    expect(formatMoney(1234.5, 'USD')).toBe('$1,234.50')
  })
})
