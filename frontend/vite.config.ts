import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vitest/config'

// The Vue app and the Laravel API share one origin in development (through this
// proxy) and in production (Laravel serves the build), so Sanctum can rely on its
// session cookie instead of tokens kept in the browser.
const api = process.env.API_URL ?? 'http://127.0.0.1:8000'

export default defineConfig(({ command }) => ({
  plugins: [vue(), tailwindcss()],
  base: command === 'build' ? '/app/' : '/',
  server: {
    proxy: {
      '/api': api,
      '/sanctum': api,
    },
  },
  build: {
    outDir: '../backend/public/app',
    emptyOutDir: true,
  },
  test: {
    environment: 'jsdom',
  },
}))
