<script setup>
import { computed } from 'vue';
const props = defineProps({ label: String, value: [String, Number], hint: String, tone: { type: String, default: 'slate' }, series: { type: Array, default: () => [] }, flat: Boolean });
const pts = computed(() => {
    const v = props.series;
    if (v.length < 2) return '';
    const mx = Math.max(1, ...v);
    return v.map((x, i) => `${(i * 100) / (v.length - 1)},${30 - 3 - (x / mx) * 24}`).join(' ');
});
</script>
<template>
    <div class="min-w-0 p-4 sm:p-5" :class="flat ? '' : 'card'">
        <p class="text-sm leading-snug text-slate-500">{{ label }}</p>
        <p class="mt-1 whitespace-nowrap text-xl font-bold tracking-tight text-ink-900 sm:text-2xl" :class="{ 'text-rose-600': tone === 'bad', '!text-brand-700': tone === 'good' }">{{ value }}</p>
        <div class="mt-2 flex items-end justify-between gap-3">
            <p v-if="hint" class="min-w-0 text-xs text-slate-400">{{ hint }}</p>
            <svg v-if="pts" viewBox="0 0 100 30" preserveAspectRatio="none" class="h-7 w-20 shrink-0" dir="ltr" aria-hidden="true"><polyline :points="pts" fill="none" stroke="#34D399" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" /></svg>
        </div>
    </div>
</template>
