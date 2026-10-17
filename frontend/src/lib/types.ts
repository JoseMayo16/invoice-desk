export type InvoiceStatus = 'draft' | 'sent' | 'overdue' | 'paid' | 'void'

export interface Company {
  id: number
  name: string
  email: string | null
  address: string | null
  tax_id: string | null
  currency: string
  invoice_prefix: string
}

export interface User {
  id: number
  name: string
  email: string
  role: 'owner' | 'member'
  company: Company
}

export interface Client {
  id: number
  name: string
  email: string | null
  phone: string | null
  address: string | null
  tax_id: string | null
  notes: string | null
  invoices_count?: number
  outstanding?: number
}

export interface InvoiceItem {
  description: string
  quantity: number
  unit_price: number
  tax_rate: number
  line_subtotal?: number
  line_tax?: number
}

export interface Payment {
  id: number
  amount: number
  method: 'card' | 'bank_transfer' | 'cash' | 'other'
  reference: string | null
  paid_at: string
}

export interface Invoice {
  id: number
  number: string
  status: InvoiceStatus
  client?: Client
  issue_date: string
  due_date: string
  currency: string
  notes: string | null
  subtotal: number
  tax_total: number
  total: number
  amount_paid: number
  balance: number
  items?: InvoiceItem[]
  payments?: Payment[]
  public_url?: string
  sent_at: string | null
  paid_at: string | null
  can?: { update: boolean; delete: boolean; record_payment: boolean }
}

export interface Paginated<T> {
  data: T[]
  meta: { current_page: number; last_page: number; total: number; per_page: number }
}

export interface Dashboard {
  currency: string
  outstanding: number
  overdue: number
  overdue_count: number
  paid_this_month: number
  draft_count: number
  revenue_by_month: { month: string; amount: number }[]
  top_clients: { id: number; name: string; billed: number; invoices: number }[]
  overdue_invoices: Invoice[]
}

export interface PublicInvoice {
  number: string
  status: InvoiceStatus
  from: { name: string; email: string | null; address: string | null; tax_id: string | null }
  to: { name: string; address: string | null; tax_id: string | null }
  issue_date: string
  due_date: string
  currency: string
  notes: string | null
  items: InvoiceItem[]
  subtotal: number
  tax_total: number
  total: number
  amount_paid: number
  balance: number
  can_pay_online: boolean
}
