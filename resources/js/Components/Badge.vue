<script setup>
import { computed } from 'vue';
const props = defineProps({ tone: { type: String, default: 'slate' } });
const cls = computed(() => ({
    slate: 'bg-slate-100 text-slate-700', brand: 'bg-brand-100 text-brand-800', amber: 'bg-amber-100 text-amber-800',
    rose: 'bg-rose-100 text-rose-800', blue: 'bg-sky-100 text-sky-800', green: 'bg-emerald-100 text-emerald-800', violet: 'bg-violet-100 text-violet-800',
}[props.tone]));
</script>
<template><span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="cls"><slot /></span></template>
