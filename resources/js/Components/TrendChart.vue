<script setup>
import { computed } from 'vue';
import { useI18n } from '@/lib/i18n';
const props = defineProps({ rows: Array, valueKey: { type: String, default: 'revenue' }, format: Function, maxLabels: { type: Number, default: 8 } });
const { num } = useI18n();
const max = computed(() => Math.max(1, ...props.rows.map((r) => r[props.valueKey])));
const h = (v) => Math.max(v ? 4 : 0, (v / max.value) * 120);
const fmt = (v) => (props.format ? props.format(v) : num(v));
const labelOf = (r) => (r.month ? r.month.slice(5) : (r.day ? r.day.slice(5) : r.hour));
// Thin labels so dense series (24 hours, 30+ days) never overflow the card on mobile.
const step = computed(() => Math.max(1, Math.ceil(props.rows.length / props.maxLabels)));
const showLabel = (i) => (i % step.value === 0 && props.rows.length - 1 - i >= Math.ceil(step.value / 2)) || i === props.rows.length - 1;
</script>
<template>
    <div class="overflow-hidden">
        <div class="flex items-end justify-between gap-2" style="height: 150px" dir="ltr">
            <div v-for="(r, i) in rows" :key="r.month || r.day || r.hour" class="flex min-w-0 flex-1 flex-col items-center justify-end gap-1">
                <div class="flex w-full items-end justify-center" style="height: 130px">
                    <div class="w-1/2 rounded-t bg-ink-800" :style="{ height: h(r[valueKey]) + 'px' }" :title="fmt(r[valueKey])"><span class="sr-only">{{ r[valueKey] }}</span></div>
                </div>
                <span class="relative block h-4 w-0" dir="ltr">
                    <span v-if="showLabel(i)" class="absolute top-0 whitespace-nowrap text-[11px] text-slate-500"
                        :class="i === 0 ? 'left-0' : (i === rows.length - 1 ? 'right-0' : 'left-1/2 -translate-x-1/2')">{{ labelOf(r) }}</span>
                </span>
            </div>
        </div>
    </div>
</template>
