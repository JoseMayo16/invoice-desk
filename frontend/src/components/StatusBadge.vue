<script setup lang="ts">
import { computed } from 'vue'
import type { InvoiceStatus } from '../lib/types'

const props = defineProps<{ status: InvoiceStatus }>()

// A coloured dot plus the word: the label carries the meaning, the colour only helps scanning.
const styles: Record<InvoiceStatus, { label: string; dot: string; text: string }> = {
  draft: { label: 'Draft', dot: 'border border-zinc-400 bg-transparent', text: 'text-zinc-600' },
  sent: { label: 'Sent', dot: 'bg-sky-600', text: 'text-zinc-800' },
  overdue: { label: 'Overdue', dot: 'bg-red-600', text: 'text-red-700' },
  paid: { label: 'Paid', dot: 'bg-brand-600', text: 'text-brand-700' },
  void: { label: 'Void', dot: 'bg-zinc-300', text: 'text-zinc-400 line-through' },
}
const style = computed(() => styles[props.status])
</script>

<template>
  <span class="inline-flex items-center gap-1.5 text-xs font-medium" :class="style.text">
    <span class="size-1.5 shrink-0 rounded-full" :class="style.dot" aria-hidden="true" />
    {{ style.label }}
  </span>
</template>
