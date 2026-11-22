<script setup>
import { computed } from 'vue';
const props = defineProps({ rows: Array, format: Function, color: { type: String, default: 'bg-brand-500' } });
const max = computed(() => Math.max(1, ...props.rows.map((r) => r.value)));
</script>
<template>
    <ul class="space-y-3">
        <li v-for="r in rows" :key="r.label">
            <div class="mb-1 flex items-baseline justify-between gap-3 text-sm">
                <span class="min-w-0 truncate">{{ r.label }}</span>
                <bdi class="shrink-0 font-semibold">{{ format ? format(r.value) : r.value }}</bdi>
            </div>
            <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full" :class="color" :style="{ width: (r.value / max) * 100 + '%' }" /></div>
        </li>
    </ul>
</template>
