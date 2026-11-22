<script setup>
import { computed } from 'vue';
import { useI18n } from '@/lib/i18n';
const props = defineProps({ rows: Array, valueKey: { type: String, default: 'revenue' }, format: Function });
const { num } = useI18n();
const max = computed(() => Math.max(1, ...props.rows.map((r) => r[props.valueKey])));
const h = (v) => Math.max(v ? 4 : 0, (v / max.value) * 120);
const fmt = (v) => (props.format ? props.format(v) : num(v));
</script>
<template>
    <div>
        <div class="flex items-end justify-between gap-2" style="height: 150px" dir="ltr">
            <div v-for="r in rows" :key="r.month || r.hour" class="flex flex-1 flex-col items-center justify-end gap-1">
                <div class="flex w-full items-end justify-center" style="height: 130px">
                    <div class="w-1/2 rounded-t bg-ink-800" :style="{ height: h(r[valueKey]) + 'px' }" :title="fmt(r[valueKey])"><span class="sr-only">{{ r[valueKey] }}</span></div>
                </div>
                <span class="text-[11px] text-slate-500">{{ r.month ? r.month.slice(5) : r.hour }}</span>
            </div>
        </div>
    </div>
</template>
