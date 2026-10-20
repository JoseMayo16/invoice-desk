<script setup lang="ts">
import { computed, ref } from 'vue'
import { formatMoney } from '../lib/money'

const props = defineProps<{ data: { month: string; amount: number }[]; currency: string }>()

// One series, one colour. Thin bars, rounded at the data end, a recessive grid,
// the latest value labelled, and every value reachable on hover, focus and in the table.
const W = 960
const H = 240
const pad = { top: 16, right: 8, bottom: 26, left: 56 }
const active = ref<number | null>(null)

const max = computed(() => niceMax(Math.max(1, ...props.data.map((d) => d.amount))))
const ticks = computed(() => [0, 0.25, 0.5, 0.75, 1].map((t) => t * max.value))
const band = computed(() => (W - pad.left - pad.right) / Math.max(1, props.data.length))
const barW = computed(() => Math.min(28, band.value * 0.5))

function niceMax(v: number) {
  const step = 10 ** Math.floor(Math.log10(v))
  return Math.ceil(v / step) * step
}
function x(i: number) {
  return pad.left + i * band.value + (band.value - barW.value) / 2
}
function y(v: number) {
  return pad.top + (1 - v / max.value) * (H - pad.top - pad.bottom)
}
function label(month: string, short = true) {
  const [yr, mo] = month.split('-').map(Number)
  return new Date(yr, mo - 1, 1).toLocaleDateString('en-US', short ? { month: 'short' } : { month: 'long', year: 'numeric' })
}
function compact(v: number) {
  return new Intl.NumberFormat('en-US', { notation: 'compact', maximumFractionDigits: 1 }).format(v)
}
function barPath(i: number, v: number) {
  const top = y(v)
  const bottom = y(0)
  const r = Math.min(1.5, barW.value / 2, bottom - top)
  const l = x(i)
  const rt = l + barW.value
  return `M${l},${bottom}V${top + r}Q${l},${top} ${l + r},${top}H${rt - r}Q${rt},${top} ${rt},${top + r}V${bottom}Z`
}
</script>

<template>
  <div class="relative">
    <svg :viewBox="`0 0 ${W} ${H}`" class="w-full" role="img" aria-label="Payments received per month over the last twelve months">
      <g v-for="t in ticks" :key="t">
        <line :x1="pad.left" :x2="W - pad.right" :y1="y(t)" :y2="y(t)" :class="t ? 'stroke-zinc-100' : 'stroke-zinc-300'" />
        <text :x="pad.left - 8" :y="y(t) + 4" text-anchor="end" class="fill-zinc-500 font-mono text-[10px]">{{ compact(t) }}</text>
      </g>
      <g v-for="(d, i) in data" :key="d.month">
        <rect
          :x="pad.left + i * band"
          :y="pad.top"
          :width="band"
          :height="H - pad.top - pad.bottom"
          class="cursor-default fill-transparent outline-none hover:fill-zinc-50 focus:fill-zinc-50"
          tabindex="0"
          :aria-label="`${label(d.month, false)}: ${formatMoney(d.amount, currency)}`"
          @mouseenter="active = i"
          @mouseleave="active = null"
          @focus="active = i"
          @blur="active = null"
        />
        <path v-if="d.amount > 0" :d="barPath(i, d.amount)" class="pointer-events-none fill-brand-600" />
        <text :x="x(i) + barW / 2" :y="H - 8" text-anchor="middle" class="fill-zinc-500 text-[10px]">{{ label(d.month) }}</text>
      </g>
      <text
        v-if="data.length"
        :x="x(data.length - 1) + barW / 2"
        :y="y(data[data.length - 1].amount) - 6"
        text-anchor="middle"
        class="fill-zinc-700 font-mono text-[10px] font-medium"
      >{{ compact(data[data.length - 1].amount) }}</text>
    </svg>
    <div
      v-if="active !== null"
      class="pointer-events-none absolute -translate-x-1/2 rounded-md border border-zinc-200 bg-white px-2.5 py-1.5 text-xs shadow-sm"
      :style="{ left: `${((x(active) + barW / 2) / W) * 100}%`, top: '0' }"
    >
      <div class="text-zinc-500">{{ label(data[active].month, false) }}</div>
      <div class="num font-medium">{{ formatMoney(data[active].amount, currency) }}</div>
    </div>
  </div>
</template>
