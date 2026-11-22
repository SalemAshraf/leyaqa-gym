<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import { useI18n } from '@/lib/i18n';

const page = usePage();
const { t, locale } = useI18n();
watchEffect(() => {
    const l = page.props.locale || 'ar';
    document.documentElement.lang = l;
    document.documentElement.dir = l === 'ar' ? 'rtl' : 'ltr';
});
</script>

<template>
    <div class="min-h-screen bg-canvas lg:flex">
        <div class="flex min-h-screen flex-1 flex-col items-center justify-center p-6">
            <Link href="/" class="mb-6 flex items-center gap-3 text-2xl font-bold text-ink-900">
                <img src="/icon.svg" alt="" class="h-11 w-11 rounded-xl shadow" />{{ t('app') }}
            </Link>
            <div class="card w-full max-w-md">
                <slot />
            </div>
        </div>
        <div class="hidden w-[42%] flex-col justify-center bg-ink-900 p-12 text-brand-50 lg:flex">
            <img src="/icon.svg" alt="" class="mb-8 h-16 w-16 rounded-2xl shadow-lg" />
            <h2 class="text-3xl font-bold leading-snug text-white">{{ t('au_side_h') }}</h2>
            <p class="mt-4 max-w-md text-lg leading-relaxed text-slate-300">{{ t('au_side_p') }}</p>
        </div>
    </div>
</template>
