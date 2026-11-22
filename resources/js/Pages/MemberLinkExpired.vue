<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const ar = computed(() => (usePage().props.locale || 'ar') === 'ar');
const L = computed(() => ar.value
    ? { title: 'الرابط انتهى', body: 'هذا الرابط اتستخدم أو انتهت صلاحيته. اطلب رابطا جديدا من الجيم.' }
    : { title: 'Link expired', body: 'This link was already used or expired. Ask your gym for a new one.' });
</script>

<template>
    <Head :title="L.title" />
    <div :dir="ar ? 'rtl' : 'ltr'" class="flex min-h-screen items-center justify-center bg-slate-50 px-6">
        <div class="card max-w-sm text-center">
            <h1 class="text-lg font-bold text-ink-900">{{ L.title }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ L.body }}</p>
        </div>
    </div>
</template>
