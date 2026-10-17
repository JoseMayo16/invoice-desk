<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'

const props = defineProps<{ open: boolean; title: string }>()
const emit = defineEmits<{ close: [] }>()
const dialog = ref<HTMLDialogElement>()

function sync() {
  if (!dialog.value) return
  if (props.open && !dialog.value.open) dialog.value.showModal()
  if (!props.open && dialog.value.open) dialog.value.close()
}
watch(() => props.open, sync)
onMounted(sync)
</script>

<template>
  <dialog
    ref="dialog"
    class="m-auto w-[min(32rem,calc(100vw-2rem))] rounded-xl p-0 shadow-2xl backdrop:bg-zinc-900/40"
    @close="emit('close')"
    @click.self="emit('close')"
  >
    <div class="p-5 sm:p-6">
      <div class="mb-4 flex items-start justify-between gap-4">
        <h2 class="text-lg font-semibold">{{ title }}</h2>
        <button class="btn-ghost -m-2 p-2" aria-label="Close" @click="emit('close')">
          <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" /></svg>
        </button>
      </div>
      <slot />
    </div>
  </dialog>
</template>
