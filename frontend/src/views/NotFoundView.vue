<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center gap-3 px-4 text-center">
    <p class="num text-sm text-zinc-500">404</p>
    <h1 class="text-2xl font-bold">This page does not exist</h1>
    <RouterLink to="/" class="btn-secondary mt-2">Go to the dashboard</RouterLink>
  </div>
</template>
