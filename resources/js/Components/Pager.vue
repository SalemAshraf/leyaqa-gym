<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ links: Array });
</script>
<template>
    <nav v-if="links && links.length > 3" class="mt-4 flex flex-wrap items-center justify-center gap-1">
        <template v-for="(l, i) in links" :key="i">
            <Link v-if="l.url" :href="l.url" preserve-scroll class="rounded-md px-3 py-1.5 text-sm"
                :class="l.active ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'" v-html="l.label.replace('&laquo;', '«').replace('&raquo;', '»')" />
            <span v-else class="rounded-md px-3 py-1.5 text-sm text-slate-300" v-html="l.label.replace('&laquo;', '«').replace('&raquo;', '»')" />
        </template>
    </nav>
</template>
