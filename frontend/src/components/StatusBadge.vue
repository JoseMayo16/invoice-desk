<script setup lang="ts">
import { computed } from 'vue'
import type { InvoiceStatus } from '../lib/types'

const props = defineProps<{ status: InvoiceStatus }>()

// Colour never carries the meaning alone: every state also has its own icon and label.
const styles: Record<InvoiceStatus, { label: string; cls: string; icon: string }> = {
  draft: { label: 'Draft', cls: 'bg-zinc-100 text-zinc-700', icon: 'M4 20h4L18.5 9.5a2.1 2.1 0 0 0-4-4L4 16z' },
  sent: { label: 'Sent', cls: 'bg-sky-50 text-sky-700', icon: 'M22 2 11 13M22 2l-7 20-4-9-9-4z' },
  overdue: { label: 'Overdue', cls: 'bg-red-50 text-red-700', icon: 'M12 8v5m0 3.5v.01M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18' },
  paid: { label: 'Paid', cls: 'bg-emerald-50 text-emerald-700', icon: 'M5 12.5l4.5 4.5L19 7.5' },
  void: { label: 'Void', cls: 'bg-zinc-100 text-zinc-500 line-through', icon: 'M6 6l12 12M18 6 6 18' },
}
const style = computed(() => styles[props.status])
</script>

<template>
  <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold" :class="style.cls">
    <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path :d="style.icon" /></svg>
    {{ style.label }}
  </span>
</template>
