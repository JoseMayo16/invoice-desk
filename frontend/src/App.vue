<script setup lang="ts">
import { RouterView, useRoute } from 'vue-router'
import AppLayout from './components/AppLayout.vue'

const route = useRoute()
</script>

<template>
  <AppLayout v-if="!route.meta.public && !route.meta.guest">
    <RouterView />
  </AppLayout>
  <RouterView v-else />
</template>
