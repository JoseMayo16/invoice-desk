import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from './stores/auth'

declare module 'vue-router' {
  interface RouteMeta {
    /** Only for signed-out visitors (login, register). */
    guest?: boolean
    /** Open to anyone with the link (the customer's pay page). */
    public?: boolean
  }
}

export const router = createRouter({
  // Pages live at the site root; only the built assets sit under /app/.
  history: createWebHistory('/'),
  routes: [
    { path: '/', redirect: '/dashboard' },
    { path: '/login', component: () => import('./views/LoginView.vue'), meta: { guest: true } },
    { path: '/register', component: () => import('./views/RegisterView.vue'), meta: { guest: true } },
    { path: '/dashboard', component: () => import('./views/DashboardView.vue') },
    { path: '/invoices', component: () => import('./views/InvoicesView.vue') },
    { path: '/invoices/new', component: () => import('./views/InvoiceEditView.vue') },
    { path: '/invoices/:id', component: () => import('./views/InvoiceDetailView.vue'), props: true },
    { path: '/invoices/:id/edit', component: () => import('./views/InvoiceEditView.vue'), props: true },
    { path: '/clients', component: () => import('./views/ClientsView.vue') },
    { path: '/pay/:token', component: () => import('./views/PublicInvoiceView.vue'), props: true, meta: { public: true } },
    { path: '/:path(.*)*', component: () => import('./views/NotFoundView.vue'), meta: { public: true } },
  ],
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach(async (to) => {
  if (to.meta.public) return true
  const auth = useAuth()
  await auth.ready()
  if (to.meta.guest) return auth.user ? '/dashboard' : true
  if (!auth.user) return { path: '/login', query: { next: to.fullPath } }
  return true
})
