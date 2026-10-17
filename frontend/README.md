# InvoiceDesk app

Vue 3 + TypeScript frontend of [InvoiceDesk](../README.md). `npm run dev` proxies `/api` and
`/sanctum` to the Laravel server on port 8000; `npm run build` writes into `../backend/public/app`.

```bash
npm install
npm run dev
npx vitest run
```
